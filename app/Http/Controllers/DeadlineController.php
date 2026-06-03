<?php

namespace App\Http\Controllers;

use App\Services\DeadlineService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scadenze: vista cronologica pluriennale con filtri (stato, tipo, anno) e
 * importo previsto per riga. Thin controller: query/mapping in
 * [[DeadlineService]]. Tenancy via global scope [[App\Concerns\BelongsToUser]].
 */
class DeadlineController extends Controller
{
    public function __construct(private readonly DeadlineService $deadlines) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = $this->stringOrNull($request->query('status'));
        $kind = $this->stringOrNull($request->query('kind'));
        $year = $this->intOrNull($request->query('year'));

        return Inertia::render('deadlines/Index', [
            'deadlines' => $this->deadlines->paginate([
                'search' => $search,
                'status' => $status,
                'kind' => $kind,
                'year' => $year,
            ]),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'kind' => $kind,
                'year' => $year,
            ],
            'availableYears' => $this->deadlines->availableYears(),
            'statusOptions' => $this->deadlines->statusOptions(),
            'kindOptions' => $this->deadlines->kindOptions(),
        ]);
    }

    private function intOrNull(mixed $raw): ?int
    {
        $value = is_string($raw) ? trim($raw) : $raw;

        return is_numeric($value) ? (int) $value : null;
    }

    private function stringOrNull(mixed $raw): ?string
    {
        $value = is_string($raw) ? trim($raw) : '';

        return $value === '' ? null : $value;
    }
}
