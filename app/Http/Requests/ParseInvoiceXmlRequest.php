<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload batch di XML FatturaPA per la fase di anteprima.
 *
 * Limiti pensati per uso umano (10-20 file/batch, ~50-200KB tipici):
 * - max 20 file per richiesta
 * - max 1 MB per file (FatturaPA reali sono <500 KB; >1MB sospetto)
 * - solo `.xml` (no .p7m firmati, non supportati per ora)
 */
class ParseInvoiceXmlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isOnboarded();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'mimetypes:application/xml,text/xml', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Carica almeno un file XML.',
            'files.max' => 'Massimo 20 file per batch.',
            'files.*.file' => 'File non valido.',
            'files.*.mimetypes' => 'Solo XML accettati (i file .p7m firmati non sono supportati: rimuovi la firma prima di caricare).',
            'files.*.max' => 'File troppo grande (max 1 MB).',
        ];
    }
}
