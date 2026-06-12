<script setup lang="ts">
/**
 * "Da coprire": i due numeri cross-anno in un'unica card a due zone divise da un
 * hairline — spese maturate a oggi (competenza, col totale dopo lo slash) sopra,
 * scadenze aperte (cassa) sotto, col badge delle prossime scadenze agganciato a
 * quella. Le due zone si dividono l'altezza (flex-1) per allinearsi al box
 * "Spese del mese" affiancato.
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
            <p class="kicker text-muted-foreground">Spese a oggi</p>
            <p class="mt-1.5 flex items-baseline gap-1.5">
                <span
                    class="tabular text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
                    >{{ formatEUR(toCover.expenses_due_to_date) }}</span
                >
                <span
                    class="tabular text-13 whitespace-nowrap text-muted-foreground"
                    >/ {{ formatEUR(toCover.expenses_due) }}</span
                >
            </p>
        </div>

        <div class="mx-5 h-px bg-border" />

        <!-- Scadenze aperte (cassa) -->
        <div class="flex flex-1 flex-col justify-center px-5 py-4">
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
