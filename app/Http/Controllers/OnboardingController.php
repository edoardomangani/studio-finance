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
                'name' => $request->user()->name,
                'profitability_coefficient' => 78.00,
                'business_start_year' => (int) date('Y'),
            ],
        ]);
    }

    /**
     * Crea il ProfessionalProfile + seed templates iniziali tramite l'action
     * atomica CompleteOnboarding.
     */
    public function store(CompleteOnboardingRequest $request, CompleteOnboarding $completeOnboarding): RedirectResponse
    {
        $completeOnboarding($request->user(), $request->validated());

        return redirect()->route('dashboard');
    }
}
