<script setup lang="ts">
/**
 * Card "Da coprire": i due numeri cross-anno — spese maturate a oggi (competenza,
 * col totale "in tutto") e scadenze aperte (cassa) — col badge delle prossime
 * scadenze in testata. Estratta dall'hero (V4): blocco a sé, non più nella banda.
 */
import { Badge } from '@/components/ui/badge';
import { formatEUR } from '@/lib/format';
import type { DashboardToCover } from '@/types';

defineProps<{ toCover: DashboardToCover }>();
</script>

<template>
    <div
        class="flex flex-col gap-3.5 rounded-lg border border-border bg-card p-5"
    >
        <header class="flex items-center justify-between gap-3">
            <h2 class="kicker text-muted-foreground">Da coprire</h2>
            <Badge
                v-if="toCover.upcoming_deadlines_count > 0"
                variant="warning"
                class="tabular"
            >
                {{ toCover.upcoming_deadlines_count }} scadenze in arrivo
            </Badge>
        </header>
        <div class="flex items-stretch gap-5">
            <div class="flex-1">
                <p class="text-13 text-muted-foreground">Spese a oggi</p>
                <p
                    class="tabular mt-0.5 text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
                >
                    {{ formatEUR(toCover.expenses_due_to_date) }}
                </p>
                <p class="mt-1.5 text-2xs text-muted-foreground">
                    Maturate, non pagate · in tutto
                    <span class="tabular font-medium text-foreground">{{
                        formatEUR(toCover.expenses_due)
                    }}</span>
                </p>
            </div>
            <div class="w-px self-stretch bg-border-soft" />
            <div class="flex-1">
                <p class="text-13 text-muted-foreground">Scadenze aperte</p>
                <p
                    class="tabular mt-0.5 text-[1.75rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
                >
                    {{ formatEUR(toCover.deadlines_due) }}
                </p>
                <p class="mt-1.5 text-2xs text-muted-foreground">
                    Cassa pluriennale
                </p>
            </div>
        </div>
    </div>
</template>
