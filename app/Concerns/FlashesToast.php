<?php

namespace App\Concerns;

use Inertia\Inertia;

/**
 * Helper toast flash. Usato dai controller per mandare un toast Inertia
 * nella response successiva senza ripetere la struttura `['type' => ..., ...]`.
 *
 *   $this->flashSuccess('Anagrafica aggiornata.');
 *   $this->flashError('Operazione fallita.');
 *   $this->flashInfo('Bozza salvata.');
 */
trait FlashesToast
{
    protected function flashSuccess(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }

    protected function flashError(string $message): void
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);
    }

    protected function flashInfo(string $message): void
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => $message]);
    }
}
