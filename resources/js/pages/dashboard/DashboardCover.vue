<script setup lang="ts">
/**
 * "Da coprire": due numeri in un'unica card a due zone divise da un hairline —
 * spese maturate a oggi e ancora da pagare (competenza, netto dei crediti) sopra,
 * scadenze aperte da versare (cassa, minimi fissi inclusi) sotto, col badge delle
 * prossime scadenze agganciato a quella. Le due zone si dividono l'altezza
 * (flex-1) per allinearsi al box "Spese del mese" affiancato.
 */
import { Badge } from '@/components/ui/badge';
import { formatEUR } from '@/lib/format';
import type { DashboardToCover } from '@/types';

defineProps<{ toCover: DashboardToCover }>();
</script>

<template>
    <div class="flex h-full flex-col rounded-lg border border-border bg-card">
        <!-- Spese a oggi (competenza) -->
        <div class="flex flex-1 flex-col justify-center px-5 py-4">
            <p class="kicker text-muted-foreground">Spese da pagare a oggi</p>
            <p class="mt-1.5 flex items-baseline gap-1.5">
                <span
                    class="tabular text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
                    >{{ formatEUR(toCover.expenses_due_to_date) }}</span
                >
            </p>
        </div>

        <div class="mx-5 h-px bg-border" />

        <!-- Scadenze da versare (cassa) -->
        <div class="flex flex-1 flex-col justify-center px-5 py-4">
            <div class="flex items-center justify-between gap-3">
                <p class="kicker text-muted-foreground">Scadenze da versare</p>
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
