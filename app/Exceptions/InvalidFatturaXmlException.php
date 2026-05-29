<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * XML malformato o privo dei campi obbligatori di una FatturaPA.
 *
 * Lanciata da [[App\Services\InvoiceXmlParser]] quando il file caricato
 * non è interpretabile come fattura elettronica italiana. Il controller
 * la cattura per marcare la riga come "scartato" nella preview di import.
 */
class InvalidFatturaXmlException extends RuntimeException {}
