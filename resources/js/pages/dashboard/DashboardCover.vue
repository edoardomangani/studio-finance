<script setup lang="ts">
/**
 * "Da coprire": i due numeri cross-anno come card indipendenti impilate — spese
 * maturate a oggi (competenza, col totale "in tutto") sopra, scadenze aperte
 * (cassa) sotto, col badge delle prossime scadenze agganciato a quella. Le due
 * card si dividono l'altezza della colonna (flex-1) per allinearsi al box "Spese
 * del mese" affiancato.
 */
import { Badge } from '@/components/ui/badge';
import { formatEUR } from '@/lib/format';
import type { DashboardToCover } from '@/types';

defineProps<{ toCover: DashboardToCover }>();
</script>

<template>
    <div class="flex h-full flex-col gap-4">
        <!-- Spese a oggi (competenza) -->
        <div
            class="flex flex-1 flex-col justify-center rounded-lg border border-border bg-card p-5"
        >
            <p class="kicker text-muted-foreground">Spese a oggi</p>
            <p
                class="tabular mt-1.5 text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
            >
                {{ formatEUR(toCover.expenses_due_to_date) }}
            </p>
            <p class="mt-2 text-2xs text-muted-foreground">
                In tutto
                <span class="tabular font-medium text-foreground">{{
                    formatEUR(toCover.expenses_due)
                }}</span>
            </p>
        </div>

        <!-- Scadenze aperte (cassa) -->
        <div
            class="flex flex-1 flex-col justify-center rounded-lg border border-border bg-card p-5"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="kicker text-muted-foreground">Scadenze aperte</p>
                <Badge
                    v-if="toCover.upcoming_deadlines_count > 0"
                    variant="warning"
                    class="tabular"
                >
                    {{ toCover.upcoming_deadlines_count }} in arrivo
                </Badge>
            </div>
            <p
                class="tabular mt-1.5 text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
            >
                {{ formatEUR(toCover.deadlines_due) }}
            </p>
        </div>
    </div>
</template>
