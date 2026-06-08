<?php

namespace App\Http\Controllers;

use App\Concerns\FlashesToast;
use App\Services\ArchiveService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Archivio (F11): vista dei record archiviati (soft delete) delle entità
 * archiviabili, con ripristino. Controller thin: delega tutto a [[ArchiveService]].
 */
class ArchivioController extends Controller
{
    use FlashesToast;

    public function index(ArchiveService $service): Response
    {
        return Inertia::render('archivio/Index', [
            'archive' => $service->forIndex(),
        ]);
    }

    public function restore(string $type, int $id, ArchiveService $service): RedirectResponse
    {
        $service->restore($type, $id);

        $this->flashSuccess('Elemento ripristinato.');

        return back();
    }
}
