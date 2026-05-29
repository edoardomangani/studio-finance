<?php

use App\Exceptions\InvalidFatturaXmlException;
use App\Services\InvoiceXmlParser;

/** Wrapper sopra Pest::fixture() per leggere i contenuti, non il path. */
function fatturaXml(string $name): string
{
    return file_get_contents(fixture('FatturaPA/'.$name)) ?: '';
}

it('parsa una fattura minimale (no cassa, no bollo, no ritenuta, prefisso p:)', function (): void {
    $parser = new InvoiceXmlParser;
    $data = $parser->parse(fatturaXml('minimal.xml'));

    expect($data['number'])->toBe('2026/001')
        ->and($data['issued_at'])->toBe('2026-03-15')
        ->and($data['amount'])->toBe(1000.00)
        ->and($data['inarcassa_amount'])->toBe(0.00)
        ->and($data['stamp_amount'])->toBe(0.00)
        ->and($data['art_15_amount'])->toBe(0.00)
        ->and($data['bank_withholding'])->toBeFalse()
        ->and($data['client']['name'])->toBe('Acme Costruzioni srl')
        ->and($data['client']['vat_number'])->toBe('09021270013')
        ->and($data['client']['tax_code'])->toBeNull();
});

it('parsa una fattura completa (cassa + bollo + ritenuta + art.15 + Nome/Cognome)', function (): void {
    $parser = new InvoiceXmlParser;
    $data = $parser->parse(fatturaXml('full.xml'));

    // Imponibile = somma linee NON-N1 = 1000.00 (la linea N1 da 50 esclusa).
    expect($data['amount'])->toBe(1000.00)
        // Cassa = TC02 ImportoContributoCassa = 40.08
        ->and($data['inarcassa_amount'])->toBe(40.08)
        // Bollo = DatiBollo/ImportoBollo = 2.00
        ->and($data['stamp_amount'])->toBe(2.00)
        // Art.15 = somma linee N1 = 50.00
        ->and($data['art_15_amount'])->toBe(50.00)
        // Ritenuta bancaria 8% NON è nel XML (operazione bancaria, non
        // documentale). Il parser ritorna sempre false; l'utente decide
        // in preview se attivarla (eventualmente eredita dal cliente).
        ->and($data['bank_withholding'])->toBeFalse()
        // Cliente persona fisica: Nome + Cognome concatenati
        ->and($data['client']['name'])->toBe('Mario Rossi')
        ->and($data['client']['vat_number'])->toBe('11122233344')
        ->and($data['client']['tax_code'])->toBe('RSSMRA80A01H501Z');
});

it('lancia InvalidFatturaXmlException su XML malformato', function (): void {
    $parser = new InvoiceXmlParser;

    expect(fn () => $parser->parse('not even xml'))
        ->toThrow(InvalidFatturaXmlException::class);
});

it('lancia InvalidFatturaXmlException su struttura non-FatturaPA', function (): void {
    $parser = new InvoiceXmlParser;

    expect(fn () => $parser->parse(fatturaXml('malformed.xml')))
        ->toThrow(InvalidFatturaXmlException::class);
});

it('lancia eccezione se Numero o Data mancano', function (): void {
    $xml = '<?xml version="1.0"?>
    <FatturaElettronica>
      <FatturaElettronicaHeader>
        <CessionarioCommittente>
          <DatiAnagrafici>
            <Anagrafica><Denominazione>X</Denominazione></Anagrafica>
          </DatiAnagrafici>
        </CessionarioCommittente>
      </FatturaElettronicaHeader>
      <FatturaElettronicaBody>
        <DatiGenerali>
          <DatiGeneraliDocumento>
            <Data>2026-03-15</Data>
          </DatiGeneraliDocumento>
        </DatiGenerali>
      </FatturaElettronicaBody>
    </FatturaElettronica>';

    $parser = new InvoiceXmlParser;

    expect(fn () => $parser->parse($xml))
        ->toThrow(InvalidFatturaXmlException::class, 'Numero o data');
});

it('gestisce decimali con virgola italiana', function (): void {
    $xml = str_replace(
        '<PrezzoTotale>1000.00</PrezzoTotale>',
        '<PrezzoTotale>1000,50</PrezzoTotale>',
        fatturaXml('minimal.xml'),
    );

    $parser = new InvoiceXmlParser;
    $data = $parser->parse($xml);

    expect($data['amount'])->toBe(1000.50);
});
