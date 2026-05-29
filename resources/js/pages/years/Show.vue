<script setup lang="ts">
/**
 * Years — Show page (placeholder Fase 6).
 *
 * Mostra le spese annuali generate all'apertura. I KPI fiscali (reddito
 * IRPEF, imposta sostitutiva, crediti, vista mensile) arrivano in Fase 9
 * con i servizi di calcolo: qui restano volutamente fuori per non simulare
 * numeri non ancora calcolati.
 */
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatEUR } from '@/lib/format';
import { index as yearsIndex } from '@/routes/years';
import type { YearShow } from '@/types';

const props = defineProps<{
    year: YearShow;
}>();

setLayoutProps({
    pageTitle: `Anno ${props.year.year}`,
    pageCrumbs: [
        { label: 'Anni', href: yearsIndex().url },
        { label: String(props.year.year) },
    ],
    subbar: false,
});

function formatCoefficient(value: number): string {
    return `${value.toLocaleString('it-IT', { maximumFractionDigits: 2 })}%`;
}
</script>

<template>
    <Head :title="`Anno ${props.year.year}`" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child variant="outline" size="sm">
            <Link :href="yearsIndex().url">Tutti gli anni</Link>
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[920px] px-4 py-6 md:px-6">
        <header class="mb-6 flex items-baseline justify-between">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-medium tabular text-foreground">{{ props.year.year }}</h1>
                <Badge v-if="props.year.pre_opened" variant="secondary">Pre-aperto</Badge>
            </div>
            <dl class="flex items-baseline gap-2 text-13 text-muted-foreground">
                <dt>Coefficiente</dt>
                <dd class="tabular text-foreground">{{ formatCoefficient(props.year.profitability_coefficient) }}</dd>
            </dl>
        </header>

        <section>
            <h2 class="kicker mb-2 text-muted-foreground">Voci di spesa</h2>
            <Table boxed>
                <TableHeader>
                    <TableRow>
                        <TableHead>Voce</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead class="w-[120px] text-right">Aliquota</TableHead>
                        <TableHead class="w-[140px] text-right">Quota</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="expense in props.year.expenses" :key="expense.id">
                        <TableCell class="font-medium text-foreground">{{ expense.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ expense.calculation_type_label }}</TableCell>
                        <TableCell class="tabular text-right text-muted-foreground">
                            <span v-if="expense.rate !== null">{{ expense.rate.toLocaleString('it-IT', { maximumFractionDigits: 2 }) }}%</span>
                            <span v-else>—</span>
                        </TableCell>
                        <TableCell class="tabular text-right text-muted-foreground">
                            <span v-if="expense.amount !== null">{{ formatEUR(expense.amount) }}</span>
                            <span v-else>—</span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>

        <p class="mt-4 text-13 text-muted-foreground">
            {{ props.year.deadlines_count }} scadenze generate. I calcoli fiscali e la vista mensile
            arriveranno con i servizi di calcolo.
        </p>
    </div>
</template>
