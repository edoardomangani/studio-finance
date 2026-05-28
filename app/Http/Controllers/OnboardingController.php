<?php

namespace App\Http\Controllers;

use App\Actions\Studiofinance\CompleteOnboarding;
use App\Http\Requests\CompleteOnboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Mostra la pagina di onboarding. Se l'utente ha già completato
     * l'onboarding viene reindirizzato alla dashboard.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->isOnboarded()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding', [
            'defaults' => [
                'nome' => $request->user()->name,
                'coefficiente_redditivita' => 78.00,
                'anno_inizio_attivita' => (int) date('Y'),
            ],
        ]);
    }

    /**
     * Crea il ProfessionalProfile (e in Fase 2 farà partire il seeding
     * di voci di spesa e scadenze tipo via Action atomica).
     */
    public function store(CompleteOnboardingRequest $request, CompleteOnboarding $completeOnboarding): RedirectResponse
    {
        $completeOnboarding($request->user(), $request->validated());

        return redirect()->route('dashboard');
    }
}
