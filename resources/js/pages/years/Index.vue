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
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatPercent } from '@/lib/format';
import OpenYearDialog from '@/pages/years/OpenYearDialog.vue';
import { show as yearShow } from '@/routes/years';
import type { YearListItem } from '@/types';

const props = defineProps<{
    years: YearListItem[];
    suggestedYear: number;
}>();

const wizardOpen = ref(false);

setLayoutProps({
    pageTitle: 'Anni',
    pageCrumbs: [{ label: 'Anni' }],
    subbar: false,
});

function openYear(year: YearListItem): void {
    router.visit(yearShow(year.year).url);
}

</script>

<template>
    <Head title="Anni" />

    <Teleport to="#page-topbar-actions" defer>
        <Button type="button" size="sm" @click="wizardOpen = true">
            <PhPlus :size="14" weight="bold" />
            Apri nuovo anno
        </Button>
    </Teleport>

    <OpenYearDialog v-model:open="wizardOpen" :suggested-year="props.suggestedYear" />

    <!-- Desktop (md+): tabella densa. -->
    <Table boxed class="hidden md:table">
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
            <TableEmpty v-if="props.years.length === 0" :colspan="5">
                Nessun anno aperto. Aprine uno dal pulsante in alto.
            </TableEmpty>
            <TableRow
                v-for="year in props.years"
                v-else
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
                    {{ formatPercent(year.profitability_coefficient) }}
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
                        <span class="tabular text-base font-medium text-foreground">{{ year.year }}</span>
                        <Badge v-if="year.pre_opened" variant="secondary">Pre-aperto</Badge>
                    </div>
                    <dl class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-13 text-muted-foreground">
                        <div class="flex items-baseline gap-1.5">
                            <dt>Coefficiente</dt>
                            <dd class="tabular text-foreground">{{ formatPercent(year.profitability_coefficient) }}</dd>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <dt>Voci</dt>
                            <dd class="tabular text-foreground">{{ year.expenses_count }}</dd>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <dt>Scadenze</dt>
                            <dd class="tabular text-foreground">{{ year.deadlines_count }}</dd>
                        </div>
                    </dl>
                </Link>
            </li>
        </ul>
    </div>
</template>
