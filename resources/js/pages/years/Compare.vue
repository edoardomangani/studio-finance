<script setup lang="ts">
/**
 * Years — Compare page (confronto pluriennale).
 *
 * Vista "report" secondaria della sezione Anni: una riga per anno, con
 * coefficiente e conteggi di spese/scadenze. L'ingresso della sezione atterra
 * invece sull'anno corrente (cockpit); qui si arriva dal link "Confronto anni"
 * della vista anno o quando non esiste ancora alcun anno (empty state). Gli
 * anni "pre-aperti" portano un badge dedicato. Riga cliccabile → vista anno.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { PhPlus } from '@phosphor-icons/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { formatPercent } from '@/lib/format';
import {
    create as yearCreate,
    index as yearsIndex,
    show as yearShow,
} from '@/routes/years';
import type { YearListItem } from '@/types';

const props = defineProps<{
    years: YearListItem[];
}>();

setLayoutProps({
    pageTitle: 'Confronto anni',
    pageCrumbs: [
        { label: 'Anni', href: yearsIndex().url },
        { label: 'Confronto' },
    ],
    subbar: false,
});

function openYear(year: YearListItem): void {
    router.visit(yearShow(year.year).url);
}
</script>

<template>
    <Head title="Confronto anni" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child size="sm">
            <Link :href="yearCreate().url">
                <PhPlus :size="14" weight="bold" />
                Apri nuovo anno
            </Link>
        </Button>
    </Teleport>

    <!-- Desktop (md+): tabella densa. -->
    <DataTable class="hidden md:table">
        <DataTableHeader :has-actions="false">
            <TableHead class="w-[120px]">Anno</TableHead>
            <TableHead class="w-[140px] text-right">Coefficiente</TableHead>
            <TableHead class="text-right">Voci di spesa</TableHead>
            <TableHead class="text-right">Scadenze</TableHead>
            <TableHead class="w-[140px]">Stato</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="props.years.length === 0" :colspan="5">
                Nessun anno aperto. Aprine uno dal pulsante in alto.
            </TableEmpty>
            <DataTableRow
                v-for="year in props.years"
                v-else
                :key="year.id"
                interactive
                @click="openYear(year)"
            >
                <TableCell
                    class="tabular font-medium text-foreground"
                    @click.stop
                >
                    <Link
                        :href="yearShow(year.year).url"
                        class="rounded-sm outline-none focus-visible:ring-1 focus-visible:ring-accent-line"
                    >
                        {{ year.year }}
                    </Link>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatPercent(year.profitability_coefficient) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ year.expenses_count }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ year.deadlines_count }}
                </TableCell>
                <TableCell>
                    <Badge v-if="year.pre_opened" variant="secondary"
                        >Pre-aperto</Badge
                    >
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
            </DataTableRow>
        </DataTableBody>
    </DataTable>

    <!-- Mobile (< md): card-stack, ogni card è un Link a piena area touch. -->
    <div class="md:hidden">
        <div
            v-if="props.years.length === 0"
            class="rounded-lg border border-dashed border-border p-6 text-center text-13 text-muted-foreground"
        >
            Nessun anno aperto. Aprine uno dal pulsante in alto.
        </div>
        <ul v-else class="flex flex-col gap-2">
            <li v-for="year in props.years" :key="year.id">
                <Link
                    :href="yearShow(year.year).url"
                    class="block rounded-lg border border-border bg-card p-3 transition-colors outline-none hover:bg-muted/40 focus-visible:ring-1 focus-visible:ring-accent-line"
                >
                    <div class="flex items-baseline justify-between">
                        <span
                            class="tabular text-base font-medium text-foreground"
                            >{{ year.year }}</span
                        >
                        <Badge v-if="year.pre_opened" variant="secondary"
                            >Pre-aperto</Badge
                        >
                    </div>
                    <dl
                        class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-13 text-muted-foreground"
                    >
                        <div class="flex items-baseline gap-1.5">
                            <dt>Coefficiente</dt>
                            <dd class="tabular text-foreground">
                                {{
                                    formatPercent(
                                        year.profitability_coefficient,
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <dt>Voci</dt>
                            <dd class="tabular text-foreground">
                                {{ year.expenses_count }}
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <dt>Scadenze</dt>
                            <dd class="tabular text-foreground">
                                {{ year.deadlines_count }}
                            </dd>
                        </div>
                    </dl>
                </Link>
            </li>
        </ul>
    </div>
</template>
