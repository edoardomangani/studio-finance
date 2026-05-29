<?php

namespace App\Services;

use App\Exceptions\InvalidFatturaXmlException;
use SimpleXMLElement;

/**
 * InvoiceXmlParser — parser di FatturaPA XML (formato SdI v1.2+).
 *
 * Estrae i dati della fattura (numero, data, importi, ritenuta) e del
 * cliente (denominazione, P.IVA, CF) dal `CessionarioCommittente`.
 * Pensato per fatture **attive** emesse dall'utente Studiofinance:
 * - `CedentePrestatore` = utente (architetto, ignorato dal parser)
 * - `CessionarioCommittente` = cliente (estratto)
 *
 * **Limitazioni note**:
 * - Solo XML plain. P7M (file firmati PKCS#7) richiedono OpenSSL
 *   extraction e per ora non sono supportati: la pagina di import
 *   informerà l'utente di rimuovere la firma a monte.
 * - Estrazione `art_15_amount`: somma delle righe `DettaglioLinea`
 *   con `Natura = N1` (operazioni escluse ex art.15). Pragmatico ma
 *   non infallibile: alcuni gestionali usano N2.2 anche per anticipate.
 *   L'utente può correggere il valore nella preview prima dell'import.
 *
 * Eccezioni: [[InvalidFatturaXmlException]] se l'XML è malformato o
 * mancano i campi essenziali (Numero, Data, CessionarioCommittente).
 */
class InvoiceXmlParser
{
    /**
     * Codice SdI per la Cassa Nazionale Previdenza e Assistenza Ingegneri
     * e Architetti (Inarcassa). Tabella ufficiale Agenzia Entrate
     * "TabellaTipoCassa.xsd" — TC04 = Inarcassa.
     */
    private const INARCASSA_TIPO_CASSA = 'TC04';

    /**
     * Codice "Natura" SdI per operazioni escluse ex art.15 (spese
     * anticipate in nome e per conto del cliente). Vedi RB3.
     */
    private const ART_15_NATURA = 'N1';

    /**
     * Parsa il contenuto di un file XML FatturaPA.
     *
     * @return array{
     *     number: string,
     *     issued_at: string,
     *     amount: float,
     *     inarcassa_amount: float,
     *     stamp_amount: float,
     *     art_15_amount: float,
     *     bank_withholding: bool,
     *     client: array{name: string, vat_number: string|null, tax_code: string|null}
     * }
     *
     * @throws InvalidFatturaXmlException
     */
    public function parse(string $xmlContent): array
    {
        $xml = $this->loadXml($xmlContent);

        // Header → cliente (CessionarioCommittente).
        $cessionario = $xml->FatturaElettronicaHeader->CessionarioCommittente
            ?? throw new InvalidFatturaXmlException('CessionarioCommittente mancante.');

        // Body → dati documento + righe.
        $body = $xml->FatturaElettronicaBody
            ?? throw new InvalidFatturaXmlException('FatturaElettronicaBody mancante.');

        $documento = $body->DatiGenerali->DatiGeneraliDocumento
            ?? throw new InvalidFatturaXmlException('DatiGeneraliDocumento mancanti.');

        $number = (string) ($documento->Numero ?? '');
        $issuedAt = (string) ($documento->Data ?? '');

        if ($number === '' || $issuedAt === '') {
            throw new InvalidFatturaXmlException('Numero o data emissione mancanti.');
        }

        return [
            'number' => $number,
            'issued_at' => $issuedAt,
            'amount' => $this->extractAmount($body, $documento),
            'inarcassa_amount' => $this->extractInarcassa($documento),
            'stamp_amount' => $this->extractStamp($documento),
            'art_15_amount' => $this->extractArt15($body),
            'bank_withholding' => $this->extractWithholdingFlag(),
            'client' => $this->extractClient($cessionario),
        ];
    }

    /**
     * Carica l'XML rimuovendo eventuale namespace di default (FatturaPA
     * 1.2 usa `xmlns="http://ivaservizi.agenziaentrate.gov.it/...`).
     * Senza questo, SimpleXMLElement richiederebbe `registerXPathNamespace`
     * per ogni accesso. Strip via regex è pragmatico e safe sui doc
     * FatturaPA (struttura DOM identica indipendentemente dal namespace).
     *
     * **Sicurezza XXE**: NON usiamo `LIBXML_NOENT` (sostituirebbe le
     * entità esterne abilitando file:// disclosure via DOCTYPE
     * malevolo, es. `<!ENTITY xxe SYSTEM "file:///etc/passwd">`).
     * Usiamo invece `LIBXML_NONET` per bloccare qualunque resolution
     * di URL esterne. Le eventuali entità definite restano come
     * riferimenti `&xxe;` non espansi (safe).
     */
    private function loadXml(string $xmlContent): SimpleXMLElement
    {
        // Strip xmlns (default + prefissi tipo p:) per accesso diretto via ->
        $stripped = preg_replace('/xmlns(:\w+)?="[^"]*"/', '', $xmlContent) ?? $xmlContent;
        $stripped = preg_replace('/<(\/?)\w+:/', '<$1', $stripped) ?? $stripped;

        $prev = libxml_use_internal_errors(true);

        try {
            $xml = @simplexml_load_string($stripped, SimpleXMLElement::class, LIBXML_NONET);

            if ($xml === false) {
                throw new InvalidFatturaXmlException('XML malformato o non valido.');
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    /**
     * Imponibile = base della prestazione professionale, PURA.
     *
     * Studiofinance modella bollo, cassa e art.15 come voci SEPARATE da
     * `amount`. La FatturaPA invece può "aggregare" il bollo dentro la
     * base contributiva Cassa (regole Inarcassa) o entrambi dentro la
     * base IVA del riepilogo. Per ricondurci a `amount` puro, priorità:
     *
     * 1. Somma `DettaglioLinea/PrezzoTotale` escluse N1 (art.15): è la
     *    prestazione pura, no bollo (che vive in `DatiBollo`) né cassa
     *    (che vive in `DatiCassaPrevidenziale`).
     * 2. `DatiCassaPrevidenziale/ImponibileCassa` MENO bollo: la base
     *    Cassa per regole Inarcassa include il bollo, lo sottraiamo
     *    per tornare alla prestazione pura.
     * 3. Last resort: `DatiRiepilogo/ImponibileImporto` (non-N1) MENO
     *    cassa MENO bollo. Approssimazione per XML che esportano solo
     *    i riepiloghi.
     */
    private function extractAmount(SimpleXMLElement $body, SimpleXMLElement $documento): float
    {
        // Priority 1: somma DettaglioLinea/PrezzoTotale escluse N1.
        $sum = 0.0;
        $hasLinee = false;

        foreach ($body->DatiBeniServizi->DettaglioLinee->DettaglioLinea ?? [] as $linea) {
            $hasLinee = true;

            if ((string) ($linea->Natura ?? '') === self::ART_15_NATURA) {
                continue;
            }

            $sum += $this->parseDecimal($linea->PrezzoTotale ?? '0');
        }

        if ($hasLinee) {
            return $this->round2($sum);
        }

        // Priority 2: ImponibileCassa MENO bollo.
        foreach ($documento->DatiCassaPrevidenziale ?? [] as $cassa) {
            if ((string) ($cassa->TipoCassa ?? '') !== self::INARCASSA_TIPO_CASSA) {
                continue;
            }

            $imponibileCassa = $this->parseDecimal($cassa->ImponibileCassa ?? '0');

            if ($imponibileCassa > 0) {
                return $this->round2(max(0.0, $imponibileCassa - $this->extractStamp($documento)));
            }
        }

        // Priority 3: DatiRiepilogo MENO cassa MENO bollo.
        $riepilogoSum = 0.0;

        foreach ($body->DatiBeniServizi->DatiRiepilogo ?? [] as $riepilogo) {
            if ((string) ($riepilogo->Natura ?? '') === self::ART_15_NATURA) {
                continue;
            }

            $riepilogoSum += $this->parseDecimal($riepilogo->ImponibileImporto ?? '0');
        }

        $riepilogoSum -= $this->extractInarcassa($documento);
        $riepilogoSum -= $this->extractStamp($documento);

        return $this->round2(max(0.0, $riepilogoSum));
    }

    /**
     * Cassa Inarcassa: cerca il blocco DatiCassaPrevidenziale con
     * TipoCassa = TC04 (codice SdI Inarcassa). Altri tipi cassa
     * (TC01 = avvocati, TC02 = dottori commercialisti, ecc.) sono
     * ignorati: questo prodotto è focalizzato su architetti Inarcassa.
     */
    private function extractInarcassa(SimpleXMLElement $documento): float
    {
        foreach ($documento->DatiCassaPrevidenziale ?? [] as $cassa) {
            if ((string) ($cassa->TipoCassa ?? '') === self::INARCASSA_TIPO_CASSA) {
                return $this->round2(
                    $this->parseDecimal($cassa->ImportoContributoCassa ?? '0'),
                );
            }
        }

        return 0.0;
    }

    private function extractStamp(SimpleXMLElement $documento): float
    {
        $bollo = $documento->DatiBollo ?? null;

        if ($bollo === null) {
            return 0.0;
        }

        return $this->round2($this->parseDecimal($bollo->ImportoBollo ?? '0'));
    }

    /**
     * Art.15 = somma PrezzoTotale delle linee con Natura = N1.
     * Spesso 0; l'utente lo correggerà in preview se serve.
     * Fallback su DatiRiepilogo come per `extractAmount`.
     */
    private function extractArt15(SimpleXMLElement $body): float
    {
        $sum = 0.0;
        $hasLinee = false;

        foreach ($body->DatiBeniServizi->DettaglioLinee->DettaglioLinea ?? [] as $linea) {
            $hasLinee = true;

            if ((string) ($linea->Natura ?? '') !== self::ART_15_NATURA) {
                continue;
            }

            $sum += $this->parseDecimal($linea->PrezzoTotale ?? '0');
        }

        if (! $hasLinee) {
            foreach ($body->DatiBeniServizi->DatiRiepilogo ?? [] as $riepilogo) {
                if ((string) ($riepilogo->Natura ?? '') !== self::ART_15_NATURA) {
                    continue;
                }

                $sum += $this->parseDecimal($riepilogo->ImponibileImporto ?? '0');
            }
        }

        return $this->round2($sum);
    }

    /**
     * Ritenuta bancaria 8% (RT/8 sul totale): **non è presente nell'XML**.
     *
     * Studiofinance modella la ritenuta bancaria 8% che la banca applica
     * al bonifico per bonus fiscali (legge 90/2014 ecbo.) — è un'operazione
     * BANCARIA, non un campo della fattura emessa.
     *
     * `DatiRitenuta` in FatturaPA riguarda la **ritenuta d'acconto**
     * (RT01/RT02), categorialmente diversa. In più, per i forfettari la
     * ritenuta d'acconto non si applica per legge, quindi DatiRitenuta
     * praticamente non compare mai negli XML degli architetti Inarcassa.
     *
     * Ritorniamo sempre `false`: l'utente toggla manualmente in preview
     * (può ereditare dal cliente matched a livello UI). Quando in futuro
     * si dovesse supportare un regime con ritenuta d'acconto leggibile
     * dall'XML, aggiungere qui un parametro `$documento` e leggere
     * `DatiRitenuta` filtrando per TipoRitenuta.
     */
    private function extractWithholdingFlag(): bool
    {
        return false;
    }

    /**
     * @return array{name: string, vat_number: string|null, tax_code: string|null}
     */
    private function extractClient(SimpleXMLElement $cessionario): array
    {
        $anagrafici = $cessionario->DatiAnagrafici
            ?? throw new InvalidFatturaXmlException('DatiAnagrafici cliente mancanti.');

        // Denominazione (persone giuridiche) o Nome + Cognome (fisiche).
        $anagrafica = $anagrafici->Anagrafica ?? null;
        $denominazione = trim((string) ($anagrafica->Denominazione ?? ''));

        if ($denominazione === '' && $anagrafica !== null) {
            $nome = trim((string) ($anagrafica->Nome ?? ''));
            $cognome = trim((string) ($anagrafica->Cognome ?? ''));
            $denominazione = trim("$nome $cognome");
        }

        if ($denominazione === '') {
            throw new InvalidFatturaXmlException('Denominazione cliente non determinabile.');
        }

        // P.IVA: IdFiscaleIVA/IdCodice. Prefisso paese (IT) preservato
        // solo se != IT (es. fornitore EU); per IT prendiamo solo le 11 cifre.
        $vatNumber = null;
        $idFiscale = $anagrafici->IdFiscaleIVA ?? null;

        if ($idFiscale !== null) {
            $idCodice = trim((string) ($idFiscale->IdCodice ?? ''));
            $idPaese = strtoupper(trim((string) ($idFiscale->IdPaese ?? '')));

            if ($idCodice !== '') {
                $vatNumber = $idPaese === 'IT'
                    ? $idCodice
                    : trim($idPaese.$idCodice);
            }
        }

        $taxCode = trim((string) ($anagrafici->CodiceFiscale ?? '')) ?: null;

        return [
            'name' => $denominazione,
            'vat_number' => $vatNumber,
            'tax_code' => $taxCode,
        ];
    }

    /**
     * Parsa una stringa decimale FatturaPA. Lo schema SdI usa `.` come
     * separatore decimale, ma alcuni gestionali esportano col `,`
     * italiano: gestiamo entrambi.
     */
    private function parseDecimal(SimpleXMLElement|string $raw): float
    {
        $str = trim((string) $raw);

        if ($str === '') {
            return 0.0;
        }

        $normalized = str_replace(',', '.', $str);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function round2(float $n): float
    {
        return round($n, 2);
    }
}
