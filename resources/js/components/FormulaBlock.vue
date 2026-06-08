<script setup lang="ts">
/**
 * FormulaBlock — trasparenza calcoli (9.a).
 *
 * Compone la derivazione leggibile di una spesa annuale dai dati strutturati
 * di `YearExpenseFormula`: base → aliquota/limiti → calcolato → deduzioni →
 * definitivo. La logica fiscale vive nel backend; qui si formatta soltanto.
 */
import { computed } from 'vue';
import { formatEUR, formatPercent } from '@/lib/format';
import type { YearExpenseFormula } from '@/types';

const props = defineProps<{ formula: YearExpenseFormula }>();

type Step = { label: string; value: string; op?: string; strong?: boolean };

const steps = computed<Step[]>(() => {
    const f = props.formula;
    const out: Step[] = [];

    if (f.calculation_type === 'fixed_annual') {
        out.push({
            label: 'Importo fisso annuale',
            value: formatEUR(f.fixed_amount ?? f.calculated),
        });
    } else if (f.calculation_type === 'sum_of_bolli') {
        out.push({
            label: f.base_label ?? 'Bolli',
            value: formatEUR(f.base ?? f.calculated),
        });
    } else {
        // Voci percentuali (reddito IRPEF / volume affari IVA).
        out.push({
            label: f.base_label ?? 'Base',
            value: formatEUR(f.base ?? 0),
        });

        if (f.maximum !== null && (f.base ?? 0) > f.maximum) {
            out.push({
                label: 'Massimale',
                value: formatEUR(f.maximum),
                op: '↓',
            });
        }

        if (f.rate !== null) {
            out.push({
                label: 'Aliquota',
                value: formatPercent(f.rate),
                op: '×',
            });
        }

        if (f.minimum !== null) {
            out.push({
                label: 'Minimale',
                value: formatEUR(f.minimum),
                op: '↑',
            });
        }
    }

    out.push({ label: 'Calcolato', value: formatEUR(f.calculated), op: '=' });

    if (f.deductions > 0) {
        out.push({
            label: 'Deduzioni (ritenute, credito)',
            value: formatEUR(f.deductions),
            op: '−',
        });
    }

    if (f.manual !== null) {
        out.push({
            label: 'Importo manuale',
            value: formatEUR(f.manual),
            op: '·',
        });
    }

    out.push({
        label: 'Definitivo',
        value: formatEUR(f.definitive),
        op: '=',
        strong: true,
    });

    return out;
});
</script>

<template>
    <dl class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-1 text-13">
        <template v-for="(step, i) in steps" :key="i">
            <dt
                class="flex items-baseline gap-1.5"
                :class="
                    step.strong
                        ? 'font-medium text-foreground'
                        : 'text-muted-foreground'
                "
            >
                <span
                    v-if="step.op"
                    class="w-3 shrink-0 text-center text-muted-foreground/70"
                    >{{ step.op }}</span
                >
                <span v-else class="w-3 shrink-0" aria-hidden="true" />
                {{ step.label }}
            </dt>
            <dd
                class="tabular text-right"
                :class="
                    step.strong
                        ? 'font-medium text-foreground'
                        : 'text-foreground'
                "
            >
                {{ step.value }}
            </dd>
        </template>
    </dl>
</template>
