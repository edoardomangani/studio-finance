<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

// Texture di dominio: scadenze plausibili (voci fiscali reali), non dati finti.
const upcomingDeadlines = [
    { label: 'IVA 2° trimestre', due: '16 set', amount: '1.247,00' },
    { label: 'F24 acconto IRPEF', due: '30 nov', amount: '980,50' },
    { label: 'Diritto camerale', due: '16 giu', amount: '77,00' },
];
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-2">
        <!-- Pannello brand — solo desktop -->
        <aside
            class="relative hidden flex-col justify-between overflow-hidden bg-accent-strong p-12 text-white lg:flex"
        >
            <!-- profondità: scurimento in basso, niente glow -->
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_80%_at_15%_0%,rgba(255,255,255,0.06),transparent_55%)]"
            />
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-[linear-gradient(to_top,rgba(0,0,0,0.28),transparent)]"
            />

            <!-- Lockup -->
            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-2.5"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-7 shrink-0"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <g transform="translate(12 12)">
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                        />
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            transform="rotate(60)"
                        />
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            transform="rotate(120)"
                        />
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            transform="rotate(180)"
                        />
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            transform="rotate(240)"
                        />
                        <path
                            d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            transform="rotate(300)"
                        />
                    </g>
                </svg>
                <span class="text-[15px] font-semibold tracking-tight">
                    Studio<span class="text-white/55">Finance</span>
                </span>
            </Link>

            <!-- Posizionamento -->
            <div class="relative z-10 max-w-md">
                <h2
                    class="text-3xl/[1.15] font-medium tracking-tight text-balance"
                >
                    Fatture e scadenze,<br />una base sola.
                </h2>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/55">
                    Il gestionale dello studio: clienti, fatturato e scadenze
                    fiscali tenuti insieme, sempre allineati.
                </p>
            </div>

            <!-- Dettaglio di prodotto come texture -->
            <div
                class="relative z-10 rounded-lg border border-white/10 bg-white/[0.04] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-sm"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-2xs font-medium tracking-wide text-white/40 uppercase"
                    >
                        Prossime scadenze
                    </span>
                    <span class="relative flex size-1.5">
                        <span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-white/50"
                        />
                        <span
                            class="relative inline-flex size-1.5 rounded-full bg-white/70"
                        />
                    </span>
                </div>
                <ul class="mt-3 divide-y divide-white/5">
                    <li
                        v-for="row in upcomingDeadlines"
                        :key="row.label"
                        class="flex items-center justify-between gap-4 py-2"
                    >
                        <span class="truncate text-13 text-white/80">{{
                            row.label
                        }}</span>
                        <span class="flex shrink-0 items-center gap-3">
                            <span class="text-2xs text-white/35">{{
                                row.due
                            }}</span>
                            <span class="text-13 text-white/90 tabular-nums"
                                >€ {{ row.amount }}</span
                            >
                        </span>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Colonna form -->
        <main class="flex flex-col justify-center px-6 py-12 sm:px-12">
            <div class="mx-auto w-full max-w-sm">
                <!-- Brand su mobile (pannello nascosto): stesso lockup del desktop -->
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2.5 lg:hidden"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-7 shrink-0 text-foreground"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <g transform="translate(12 12)">
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                            />
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                                transform="rotate(60)"
                            />
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                                transform="rotate(120)"
                            />
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                                transform="rotate(180)"
                            />
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                                transform="rotate(240)"
                            />
                            <path
                                d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                                transform="rotate(300)"
                            />
                        </g>
                    </svg>
                    <span
                        class="text-[15px] font-semibold tracking-tight text-foreground"
                    >
                        Studio<span class="text-muted-foreground">Finance</span>
                    </span>
                </Link>

                <div class="mt-8 flex flex-col gap-2 lg:mt-0">
                    <h1
                        class="text-2xl font-medium tracking-tight"
                        v-if="title"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="text-sm leading-relaxed text-muted-foreground"
                        v-if="description"
                    >
                        {{ description }}
                    </p>
                </div>

                <div class="mt-8 flex flex-col gap-6">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
