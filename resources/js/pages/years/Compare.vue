<script setup lang="ts">
/**
 * Years — Compare page (confronto pluriennale).
 *
 * Tabella di confronto: una riga per anno. Un ToggleGroup scambia il set di KPI
 * (mai mischiati): focus **Personale** (5 colonne, cosa entra/resta) o **UNICO**
 * (8 colonne fiscali della dichiarazione). L'anno in corso porta importi "a oggi"
 * (stima) segnalati dal badge di stato; i pre-aperti hanno un badge dedicato.
 * Riga cliccabile → vista anno.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatEUR } from '@/lib/format';
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
    subbar: true,
});

type Focus = 'personale' | 'unico';
// Solo le chiavi numeriche di YearListItem: i KPI formattati in EUR (niente cast).
type NumericKey = {
    [K in keyof YearListItem]: YearListItem[K] extends number ? K : never;
}[keyof YearListItem];
type Column = { key: NumericKey; label: string };

const focus = ref<Focus>('personale');

function setFocus(value: unknown): void {
    if (value === 'personale' || value === 'unico') {
        focus.value = value;
    }
}

const personaleColumns: Column[] = [
    { key: 'invoice_total', label: 'Fatturato' },
    { key: 'expenses', label: 'Spese' },
    { key: 'net', label: 'Netto' },
    { key: 'paid', label: 'Pagato' },
    { key: 'due', label: 'Da pagare' },
];

const unicoColumns: Column[] = [
    { key: 'vat_turnover', label: "Volume d'affari" },
    { key: 'irpef_income_net', label: 'Reddito IRPEF' },
    { key: 'pension_contributions_paid', label: 'Contributi pagati' },
    { key: 'imposta_sostitutiva', label: 'Imposta sost.' },
    { key: 'withholdings', label: 'Ritenute' },
    { key: 'previous_year_credit', label: 'Credito IS N-1' },
    { key: 'bank_income', label: 'Netto bancario' },
    { key: 'bank_income_monthly', label: 'Netto banc. /mese' },
];

const columns = computed<Column[]>(() =>
    focus.value === 'personale' ? personaleColumns : unicoColumns,
);

// Anno + colonne del focus + Stato.
const colspan = computed<number>(() => columns.value.length + 2);

type Status = { label: string; variant: 'secondary' | 'warning'; muted?: boolean };

function statusFor(year: YearListItem): Status {
    if (year.pre_opened) {
        return { label: 'Pre-aperto', variant: 'secondary' };
    }
    if (year.time_state === 'current') {
        return { label: 'In corso', variant: 'warning' };
    }

    // Chiuso: neutro, badge "fantasma" (sage spento).
    return { label: 'Chiuso', variant: 'secondary', muted: true };
}

// Stato precomputato per id: evita di richiamare statusFor 3× per riga nel template.
const statuses = computed<Record<number, Status>>(() =>
    Object.fromEntries(props.years.map((year) => [year.id, statusFor(year)])),
);

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

    <!-- Niente ricerca qui: il toggle KPI prende lo slot più a sinistra del subbar. -->
    <Teleport to="#page-topbar-search" defer>
        <ToggleGroup
            :model-value="focus"
            type="single"
            variant="boxed"
            size="sm"
            aria-label="Focus KPI"
            @update:model-value="setFocus"
        >
            <ToggleGroupItem value="personale" aria-label="Importi personali">Personale</ToggleGroupItem>
            <ToggleGroupItem value="unico" aria-label="Importi UNICO">UNICO</ToggleGroupItem>
        </ToggleGroup>
    </Teleport>

    <!-- Desktop (md+): tabella densa, colonne del focus attivo. -->
    <DataTable class="hidden md:table">
        <DataTableHeader :has-actions="false">
            <TableHead class="w-[100px]">Anno</TableHead>
            <!-- whitespace-nowrap: le label fiscali (UNICO, 8 col) restano su una
                 riga e, oltre il container, il box scrolla invece di comprimersi. -->
            <TableHead v-for="col in columns" :key="col.key" class="whitespace-nowrap text-right">
                {{ col.label }}
            </TableHead>
            <TableHead class="w-[120px]">Stato</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="props.years.length === 0" :colspan="colspan">
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
                <TableCell
                    v-for="col in columns"
                    :key="col.key"
                    class="tabular text-right text-foreground"
                >
                    {{ formatEUR(year[col.key]) }}
                </TableCell>
                <TableCell>
                    <Badge
                        :variant="statuses[year.id].variant"
                        :class="statuses[year.id].muted ? 'bg-transparent text-muted-foreground' : ''"
                    >
                        {{ statuses[year.id].label }}
                    </Badge>
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
                        <span class="tabular text-base font-medium text-foreground">{{ year.year }}</span>
                        <Badge
                            :variant="statuses[year.id].variant"
                            :class="statuses[year.id].muted ? 'bg-transparent text-muted-foreground' : ''"
                        >
                            {{ statuses[year.id].label }}
                        </Badge>
                    </div>
                    <dl class="mt-2 grid grid-cols-2 gap-x-5 gap-y-1.5 text-13 text-muted-foreground">
                        <div
                            v-for="col in columns"
                            :key="col.key"
                            class="flex items-baseline justify-between gap-1.5"
                        >
                            <dt>{{ col.label }}</dt>
                            <dd class="tabular text-foreground">{{ formatEUR(year[col.key]) }}</dd>
                        </div>
                    </dl>
                </Link>
            </li>
        </ul>
    </div>
</template>
