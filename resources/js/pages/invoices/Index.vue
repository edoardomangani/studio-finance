<script setup lang="ts">
/**
 * Invoices — Index page.
 *
 * Tabella paginata (50/pagina). Subbar: search testuale live + bottone
 * "Filtri" (badge counter). I filtri puntuali (anno, cliente, ritenuta)
 * vivono in [[InvoiceFilters]] e sono renderizzati in **due container
 * distinti** a seconda del viewport:
 * - **Desktop (md+)**: `<aside>` push-inline a destra del main, via
 *   Teleport a `#page-right-sidebar`. Pattern StudioOS, non-overlay.
 * - **Mobile (<md)**: `<Sheet>` slide-over da destra (shadcn primitive).
 *
 * `isMobile` viene da `useMediaQuery('(max-width: 767px)')` per evitare
 * di montare Sheet su desktop (il backdrop bloccherebbe l'aside inline).
 * SSR-safe: initial false, settla on client mount.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PhArchive,
    PhDotsThreeVertical,
    PhFunnel,
    PhMagnifyingGlass,
    PhPencil,
    PhPlus,
    PhX,
} from '@phosphor-icons/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onUnmounted, ref, watch } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { formatDateIT, formatEUR } from '@/lib/format';
import InvoiceFilters from '@/pages/invoices/InvoiceFilters.vue';
import type { InvoiceFilterState } from '@/pages/invoices/InvoiceFilters.vue';
import { create as invoicesCreate, edit as invoiceEdit, index as invoicesIndex, show as invoiceShow } from '@/routes/invoices';
import type { ClientForPicker, InvoiceListItem, PaginatedList } from '@/types';

const props = defineProps<{
    invoices: PaginatedList<InvoiceListItem>;
    filters: {
        search: string;
        year: number | null;
        client_id: number | null;
        withholding: boolean | null;
    };
    availableYears: number[];
    clientsForFilter: ClientForPicker[];
}>();

setLayoutProps({
    pageTitle: 'Fatture',
    pageCrumbs: [{ label: 'Fatture' }],
    subbar: true,
});

const { archiveOpen, archiveTarget, askArchive, confirmArchive } = useArchiveAction<InvoiceListItem>(
    (invoice) => InvoiceController.destroy.url({ invoice: invoice.id }),
);

const isMobile = useMediaQuery('(max-width: 767px)');

// Search reattiva con debounce 250ms.
const searchTerm = ref(props.filters.search);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchTerm, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters({ search: value });
    }, 250);
});

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

// Stato filtri:
// - `filtersOpen`: visibilità del pannello (aside inline o Sheet mobile)
// - `filterState`: stato locale dei radio/select dei filtri, è la fonte
//   "draft" che diventa committed quando navighiamo. Su desktop applichiamo
//   live al cambio; su mobile aspettiamo "Applica" (UX più decisa).
const filtersOpen = ref(false);

const filterState = ref<InvoiceFilterState>({
    year: props.filters.year,
    client_id: props.filters.client_id,
    withholding: props.filters.withholding,
});

watch(
    () => [props.filters.year, props.filters.client_id, props.filters.withholding] as const,
    ([year, clientId, withholding]) => {
        filterState.value = { year, client_id: clientId, withholding };
    },
);

const activeFilterCount = computed(() => {
    let n = 0;

    if (props.filters.year !== null) {
        n++;
    }

    if (props.filters.client_id !== null) {
        n++;
    }

    if (props.filters.withholding !== null) {
        n++;
    }

    return n;
});

const hasActiveFilters = computed(
    () => activeFilterCount.value > 0 || !!props.filters.search,
);

function onFilterChangeDesktop(): void {
    // Desktop: applica live, ogni modifica della radio/select naviga.
    applyFilters({
        year: filterState.value.year,
        client_id: filterState.value.client_id,
        withholding: filterState.value.withholding,
    });
}

function applyDraftFiltersMobile(): void {
    applyFilters({
        year: filterState.value.year,
        client_id: filterState.value.client_id,
        withholding: filterState.value.withholding,
    });
    filtersOpen.value = false;
}

function clearAllFilters(): void {
    filterState.value = { year: null, client_id: null, withholding: null };
    applyFilters({ year: null, client_id: null, withholding: null });
}

function applyFilters(next: {
    search?: string;
    year?: number | null;
    client_id?: number | null;
    withholding?: boolean | null;
}): void {
    router.get(
        invoicesIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            year: (next.year !== undefined ? next.year : props.filters.year) ?? undefined,
            client_id: (next.client_id !== undefined ? next.client_id : props.filters.client_id) ?? undefined,
            withholding: next.withholding !== undefined
                ? (next.withholding === null ? undefined : (next.withholding ? 1 : 0))
                : (props.filters.withholding === null ? undefined : (props.filters.withholding ? 1 : 0)),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function goToPage(page: number): void {
    router.get(
        invoicesIndex().url,
        {
            search: props.filters.search || undefined,
            year: props.filters.year ?? undefined,
            client_id: props.filters.client_id ?? undefined,
            withholding: props.filters.withholding === null
                ? undefined
                : (props.filters.withholding ? 1 : 0),
            page,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function openInvoice(invoice: InvoiceListItem): void {
    router.visit(invoiceShow(invoice.id).url);
}
</script>

<template>
    <Head title="Fatture" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child size="sm">
            <Link :href="invoicesCreate().url">
                <PhPlus :size="14" weight="bold" />
                Nuova fattura
            </Link>
        </Button>
    </Teleport>

    <Teleport to="#page-topbar-search" defer>
        <InputGroup>
            <InputGroupAddon>
                <PhMagnifyingGlass :size="14" />
            </InputGroupAddon>
            <InputGroupInput
                v-model="searchTerm"
                type="search"
                placeholder="Cerca per numero o cliente…"
            />
        </InputGroup>
    </Teleport>

    <Teleport to="#page-topbar-filters" defer>
        <Button
            type="button"
            variant="outline"
            size="sm"
            class="relative"
            @click="filtersOpen = !filtersOpen"
        >
            <PhFunnel :size="14" />
            Filtri
            <Badge
                v-if="activeFilterCount > 0"
                variant="secondary"
                class="ml-1 h-4 min-w-4 px-1 text-2xs tabular"
            >
                {{ activeFilterCount }}
            </Badge>
        </Button>
    </Teleport>

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[100px]">Data</TableHead>
                <TableHead class="w-[110px]">Numero</TableHead>
                <TableHead>Cliente</TableHead>
                <TableHead class="text-right">Imponibile</TableHead>
                <TableHead class="text-right">Bollo</TableHead>
                <TableHead class="text-right">Cassa</TableHead>
                <TableHead class="text-right">Art.15</TableHead>
                <TableHead class="text-right">Totale</TableHead>
                <TableHead class="w-[80px] text-right">Ritenuta</TableHead>
                <TableHead class="w-[48px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="invoices.data.length === 0" :colspan="10">
                <span v-if="hasActiveFilters">
                    Nessuna fattura trovata con questi filtri.
                </span>
                <span v-else>
                    Nessuna fattura. Creane una dal pulsante in alto.
                </span>
            </TableEmpty>
            <TableRow
                v-for="invoice in invoices.data"
                v-else
                :key="invoice.id"
                class="cursor-pointer transition-colors hover:bg-muted/40"
                @click="openInvoice(invoice)"
            >
                <TableCell class="tabular text-muted-foreground">
                    {{ formatDateIT(invoice.issued_at) }}
                </TableCell>
                <TableCell class="tabular font-medium text-foreground">
                    <span class="block max-w-[110px] truncate" :title="invoice.number">
                        {{ invoice.number }}
                    </span>
                </TableCell>
                <TableCell class="text-foreground">
                    <span class="block truncate" :title="invoice.client.name">
                        {{ invoice.client.name }}
                    </span>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatEUR(invoice.amount) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatEUR(invoice.stamp_amount) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatEUR(invoice.inarcassa_amount) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ formatEUR(invoice.art_15_amount) }}
                </TableCell>
                <TableCell class="tabular text-right font-medium text-foreground">
                    {{ formatEUR(invoice.total) }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge v-if="invoice.bank_withholding" variant="outline">
                        Sì
                    </Badge>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Azioni fattura"
                            >
                                <PhDotsThreeVertical :size="14" weight="bold" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem as-child>
                                <Link :href="invoiceEdit(invoice.id).url">
                                    <PhPencil :size="14" />
                                    Modifica
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                @select="askArchive(invoice)"
                            >
                                <PhArchive :size="14" />
                                Archivia
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <footer
        v-if="invoices.last_page > 1"
        class="mt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular text-xs text-muted-foreground">
            {{ invoices.from }}–{{ invoices.to }} di {{ invoices.total }}
        </span>
        <Pagination
            :total="invoices.total"
            :items-per-page="invoices.per_page"
            :page="invoices.current_page"
            :sibling-count="1"
            show-edges
            @update:page="goToPage"
        >
            <PaginationContent v-slot="{ items }">
                <PaginationPrevious />
                <template v-for="(item, idx) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="item.value"
                        :value="item.value"
                        :is-active="item.value === invoices.current_page"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis
                        v-else
                        :key="`e-${idx}`"
                        :index="idx"
                    />
                </template>
                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </footer>

    <!-- DESKTOP: aside push-inline. Teleportato al mount-point del layout.
         Su mobile è hidden via Tailwind. -->
    <Teleport to="#page-right-sidebar" defer>
        <aside
            v-show="filtersOpen && !isMobile"
            class="hidden w-[260px] shrink-0 overflow-y-auto border-l border-border md:block"
        >
            <div class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-13 font-medium text-foreground">Filtri</span>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="activeFilterCount > 0"
                            type="button"
                            class="text-2xs text-muted-foreground hover:text-foreground"
                            @click="clearAllFilters"
                        >
                            Pulisci
                        </button>
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-foreground"
                            aria-label="Chiudi pannello filtri"
                            @click="filtersOpen = false"
                        >
                            <PhX :size="14" />
                        </button>
                    </div>
                </div>
                <InvoiceFilters
                    v-model="filterState"
                    :available-years="availableYears"
                    :clients="clientsForFilter"
                    @update:model-value="onFilterChangeDesktop"
                />
            </div>
        </aside>
    </Teleport>

    <!-- MOBILE: Sheet slide-over. Renderizzato solo se isMobile per evitare
         backdrop su desktop. Pattern "draft + Applica" (più decisivo che
         on-change live, coerente con mobile UX). -->
    <Sheet v-if="isMobile" v-model:open="filtersOpen">
        <SheetContent side="right" class="w-full max-w-sm">
            <SheetHeader>
                <SheetTitle>Filtra fatture</SheetTitle>
                <SheetDescription>
                    Restringi per anno, cliente o stato ritenuta.
                </SheetDescription>
            </SheetHeader>

            <div class="px-6 py-4">
                <InvoiceFilters
                    v-model="filterState"
                    :available-years="availableYears"
                    :clients="clientsForFilter"
                />
            </div>

            <SheetFooter class="flex flex-row items-center justify-between gap-2">
                <Button
                    v-if="activeFilterCount > 0"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearAllFilters"
                >
                    Pulisci filtri
                </Button>
                <span v-else />

                <div class="flex items-center gap-2">
                    <SheetClose as-child>
                        <Button type="button" variant="outline" size="sm">
                            Annulla
                        </Button>
                    </SheetClose>
                    <Button type="button" size="sm" @click="applyDraftFiltersMobile">
                        Applica
                    </Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la fattura?"
        :description="archiveTarget
            ? `«${archiveTarget.number}» verrà nascosta dall'elenco. I dati restano per i calcoli storici.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
