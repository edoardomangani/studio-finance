<script setup lang="ts">
/**
 * Years — Index page.
 *
 * Vista pluriennale densa: una riga per anno aperto, con coefficiente e
 * conteggi di spese/scadenze. Gli anni "pre-aperti" (creati implicitamente
 * dal cross-year) portano un badge dedicato e zero scadenze finché non
 * vengono aperti formalmente. Riga cliccabile → vista anno.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { PhPlus } from '@phosphor-icons/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { open as yearsOpen, show as yearShow } from '@/routes/years';
import type { YearListItem } from '@/types';

const props = defineProps<{
    years: YearListItem[];
}>();

setLayoutProps({
    pageTitle: 'Anni',
    pageCrumbs: [{ label: 'Anni' }],
    subbar: false,
});

function openYear(year: YearListItem): void {
    router.visit(yearShow(year.year).url);
}

function formatCoefficient(value: number): string {
    return `${value.toLocaleString('it-IT', { maximumFractionDigits: 2 })}%`;
}
</script>

<template>
    <Head title="Anni" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child size="sm">
            <Link :href="yearsOpen().url">
                <PhPlus :size="14" weight="bold" />
                Apri nuovo anno
            </Link>
        </Button>
    </Teleport>

    <Empty v-if="props.years.length === 0" class="mt-6 border">
        <EmptyHeader>
            <EmptyTitle>Nessun anno aperto</EmptyTitle>
            <EmptyDescription>
                Apri il primo anno per generare spese, scadenze e pagamenti pianificati.
            </EmptyDescription>
        </EmptyHeader>
        <Button as-child size="sm">
            <Link :href="yearsOpen().url">
                <PhPlus :size="14" weight="bold" />
                Apri nuovo anno
            </Link>
        </Button>
    </Empty>

    <Table v-else boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[120px]">Anno</TableHead>
                <TableHead class="w-[140px] text-right">Coefficiente</TableHead>
                <TableHead class="text-right">Voci di spesa</TableHead>
                <TableHead class="text-right">Scadenze</TableHead>
                <TableHead class="w-[140px]">Stato</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow
                v-for="year in props.years"
                :key="year.id"
                class="cursor-pointer transition-colors hover:bg-muted/40"
                @click="openYear(year)"
            >
                <TableCell class="tabular font-medium text-foreground" @click.stop>
                    <Link
                        :href="yearShow(year.year).url"
                        class="rounded-sm outline-none focus-visible:ring-1 focus-visible:ring-accent-line"
                    >
                        {{ year.year }}
                    </Link>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatCoefficient(year.profitability_coefficient) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ year.expenses_count }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ year.deadlines_count }}
                </TableCell>
                <TableCell>
                    <Badge v-if="year.pre_opened" variant="secondary">Pre-aperto</Badge>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
