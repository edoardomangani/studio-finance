<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\ImportInvoices;
use App\Concerns\FlashesToast;
use App\Exceptions\InvalidFatturaXmlException;
use App\Http\Requests\ParseInvoiceXmlRequest;
use App\Http\Requests\StoreImportInvoicesRequest;
use App\Services\ClientService;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceService;
use App\Services\InvoiceXmlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Import XML multiplo di fatture FatturaPA.
 *
 * Flusso:
 * 1. GET `/invoices/import` → render pagina vuota con drop zone + lista
 *    clienti per il picker.
 * 2. POST `/invoices/import/parse` → riceve i file, parsa ognuno via
 *    [[InvoiceXmlParser]], fa match cliente per P.IVA→CF nel set
 *    dell'utente, flasha le `previews` per la pagina.
 * 3. POST `/invoices/import` → riceve le anteprime editate, esegue
 *    [[ImportInvoices]] in transazione, redirect /invoices con toast.
 *
 * Le anteprime non sono persistite tra step (lo stato vive client-side
 * fino al confirm). Gli XML caricati non vengono al momento salvati
 * permanentemente (colonna `xml_path` rimane null in v1); l'archivio
 * XML è un'estensione futura.
 */
class ImportXmlController extends Controller
{
    use FlashesToast;

    public function __construct(
        private readonly InvoiceXmlParser $parser,
        private readonly InvoiceService $invoices,
        private readonly ImportInvoices $importAction,
        private readonly InvoiceCalculator $calculator,
        private readonly ClientService $clients,
    ) {}

    public function show(Request $request): Response
    {
        // Le previews arrivano via session flash dopo parse(). Includiamo
        // il prop SOLO se ci sono: nella render vuota (primo accesso o
        // refresh) il watcher Vue non firea, lo stato locale resta intatto.
        $props = ['clients' => $this->invoices->clientsForPicker()];
        $previews = $request->session()->get('import_previews');

        if ($previews !== null) {
            $props['previews'] = $previews;
        }

        return Inertia::render('invoices/Import', $props);
    }

    /**
     * Parsa il batch di XML e flasha le anteprime per la show().
     *
     * Redirect esplicito a `invoices.import.show`: URL fisso, refreshabile
     * (GET supportato), e la session flash di Laravel propaga le previews
     * come prop one-shot al render successivo.
     */
    public function parse(ParseInvoiceXmlRequest $request): RedirectResponse
    {
        $files = $request->file('files') ?? [];
        $previews = [];

        foreach ($files as $file) {
            $previews[] = $this->buildPreview($file);
        }

        return to_route('invoices.import.show')
            ->with('import_previews', $previews);
    }

    public function store(StoreImportInvoicesRequest $request): RedirectResponse
    {
        $user = $request->user();

        $summary = ($this->importAction)($user, $request->validated('previews'));

        $this->flashSuccess($this->summaryMessage($summary));

        return to_route('invoices.index');
    }

    /**
     * Costruisce l'anteprima per un singolo file: parsing + match cliente.
     * In caso di errore di parsing, ritorna un'anteprima `parsed=false`
     * con il motivo; il frontend la marca come scartata + espande la
     * card mostrando l'errore inline.
     *
     * @return array<string, mixed>
     */
    private function buildPreview(UploadedFile $file): array
    {
        $filename = $this->sanitizeFilename($file->getClientOriginalName());

        try {
            $parsed = $this->parser->parse((string) $file->get());
        } catch (InvalidFatturaXmlException $e) {
            return [
                'filename' => $filename,
                'parsed' => false,
                'error' => $e->getMessage(),
            ];
        }

        $matched = $this->clients->findByFiscalIds(
            $parsed['client']['vat_number'] ?? null,
            $parsed['client']['tax_code'] ?? null,
        );

        return [
            'filename' => $filename,
            'parsed' => true,
            'number' => $parsed['number'],
            'issued_at' => $parsed['issued_at'],
            'amount' => $parsed['amount'],
            'inarcassa_amount' => $parsed['inarcassa_amount'],
            'stamp_amount' => $parsed['stamp_amount'],
            'art_15_amount' => $parsed['art_15_amount'],
            'bank_withholding' => $parsed['bank_withholding'],
            'client_from_xml' => $parsed['client'],
            'matched_client_id' => $matched?->id,
            'discrepancies' => $this->detectDiscrepancies($parsed),
        ];
    }

    /**
     * Confronta gli importi estratti dall'XML con quelli "attesi" dal
     * calcolatore canonico (regime Inarcassa standard 4% + bollo soglia
     * 77.47). Una divergenza > €0,01 segnala probabile errore del
     * gestionale di emissione o regime non standard. L'utente vede il
     * warning per field in anteprima ma può importare comunque (XML è
     * il documento legale, vince lui).
     *
     * @param  array<string, mixed>  $parsed
     * @return array<string, array{xml: float, expected: float, delta: float}>
     */
    private function detectDiscrepancies(array $parsed): array
    {
        $amount = (float) ($parsed['amount'] ?? 0);
        $stampXml = (float) ($parsed['stamp_amount'] ?? 0);
        $inarcassaXml = (float) ($parsed['inarcassa_amount'] ?? 0);

        $expectedStamp = $this->calculator->defaultStamp($amount);
        // La cassa attesa usa il bollo come è nell'XML, non il default:
        // se l'utente ha emesso fattura con bollo override €5, la cassa
        // attesa è (amount + 5) × 4%, non (amount + 2) × 4%.
        $expectedInarcassa = $this->calculator->defaultInarcassa($amount, $stampXml);

        $discrepancies = [];

        if (abs($stampXml - $expectedStamp) > InvoiceCalculator::HEURISTIC_TOLERANCE) {
            $discrepancies['stamp_amount'] = [
                'xml' => $stampXml,
                'expected' => $expectedStamp,
                'delta' => round($stampXml - $expectedStamp, 2),
            ];
        }

        if (abs($inarcassaXml - $expectedInarcassa) > InvoiceCalculator::HEURISTIC_TOLERANCE) {
            $discrepancies['inarcassa_amount'] = [
                'xml' => $inarcassaXml,
                'expected' => $expectedInarcassa,
                'delta' => round($inarcassaXml - $expectedInarcassa, 2),
            ];
        }

        return $discrepancies;
    }

    /**
     * Sanitizza il filename per la display nelle anteprime: rimuove
     * caratteri di controllo invisibili (es. U+202E reverse override) che
     * potrebbero spoofare l'estensione visualizzata, e cap della lunghezza.
     * Vue auto-escape già protegge da XSS in `{{ }}`, questa è defense-in-
     * depth cosmetica.
     */
    private function sanitizeFilename(string $name): string
    {
        // Rimuove control chars + Unicode bidi override / formatting chars.
        $clean = preg_replace('/[\x00-\x1F\x7F\p{C}]/u', '', $name) ?? $name;
        $clean = trim($clean);

        if ($clean === '') {
            $clean = 'file.xml';
        }

        return mb_substr($clean, 0, 120);
    }

    /**
     * @param  array{invoices_created: int, clients_created: int}  $summary
     */
    private function summaryMessage(array $summary): string
    {
        $i = $summary['invoices_created'];
        $c = $summary['clients_created'];

        $inv = $i === 1 ? '1 fattura importata' : "{$i} fatture importate";
        $cli = $c === 0
            ? 'nessun nuovo cliente'
            : ($c === 1 ? '1 nuovo cliente creato' : "{$c} nuovi clienti creati");

        return "{$inv}, {$cli}.";
    }
}
