<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard generale (Fase 9.b): mese in corso, "da coprire" cross-anno e
 * pannello anno. Thin controller: tutta l'orchestrazione (un load per anno via
 * cache + calcoli puri) vive in [[DashboardService]]. Tenancy via global scope
 * [[App\Concerns\BelongsToUser]].
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'dashboard' => $this->dashboard->forShow(),
        ]);
    }
}
