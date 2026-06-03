<script setup lang="ts">
/**
 * Deadlines — Index page.
 *
 * Vista cronologica pluriennale delle scadenze (50/pagina), con l'importo
 * previsto (suggerimento, RB8) calcolato a runtime per ogni riga. Subbar:
 * search live sul nome + toggle stato (segmentatore primario) accanto al
 * bottone "Filtri" (tipo / anno) in aside desktop o Sheet mobile.
 *
 * Click su una riga → side-sheet (registrazione pagamento + reversibilità).
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    PhArchive,
    PhCheckCircle,
    PhCircleDashed,
    PhDotsThreeVertical,
    PhFunnel,
    PhListBullets,
    PhMagnifyingGlass,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { ref } from 'vue';
import type { Component } from 'vue';
import DeadlineController from '@/actions/App/Http/Controllers/DeadlineController';
import FilterPanel from '@/components/FilterPanel.vue';
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
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { useDeadlineFilters } from '@/composables/useDeadlineFilters';
import { formatDateIT, formatEUR } from '@/lib/format';
import DeadlineFilters from '@/pages/deadlines/DeadlineFilters.vue';
import DeadlineFormDialog from '@/pages/deadlines/DeadlineFormDialog.vue';
import DeadlineSheet from '@/pages/deadlines/DeadlineSheet.vue';
import { DEADLINE_STATUS_META } from '@/pages/deadlines/statusMeta';
import type {
    AnnualExpenseForPicker,
    DeadlineListItem,
    DeadlineStateFilter,
    EnumOption,
    PaginatedList,
    YearOption,
} from '@/types';

const props = defineProps<{
    deadlines: PaginatedList<DeadlineListItem>;
    filters: {
        search: string;
        state: DeadlineStateFilter | null;
        kind: string[];
        year: number[];
        due_year: number[];
        expense_item_id: number[];
    };
    availableYears: number[];
    availableDueYears: number[];
    expenseItems: { id: number; name: string }[];
    kindOptions: EnumOption[];
    annualExpenses: AnnualExpenseForPicker[];
    yearOptions: YearOption[];
}>();

// Toggle stato: segmentatore primario. 'closed' raccoglie completate + non
// dovute ("cose fatte"). Stesse icone degli stati riga.
const STATE_TABS: { value: string; label: string; icon: Component }[] = [
    { value: 'open', label: 'Aperte', icon: PhCircleDashed },
    { value: 'closed', label: 'Completate', icon: PhCheckCircle },
    { value: 'all', label: 'Tutte', icon: PhListBullets },
];

setLayoutProps({
    pageTitle: 'Scadenze',
    pageCrumbs: [{ label: 'Scadenze' }],
    subbar: true,
});

// Dialog crea/modifica scadenza ad-hoc: formDeadline null = creazione.
const formOpen = ref(false);
const formDeadline = ref<DeadlineListItem | null>(null);

function openCreate(): void {
    formDeadline.value = null;
    formOpen.value = true;
}

function openEdit(deadline: DeadlineListItem): void {
    formDeadline.value = deadline;
    formOpen.value = true;
}

// Archiviazione (solo ad-hoc non pagate): kebab → ConfirmDialog → DELETE.
const { archiveOpen, archiveTarget, askArchive, confirmArchive } = useArchiveAction<DeadlineListItem>(
    (deadline) => DeadlineController.destroy.url({ deadline: deadline.id }),
);

// Archiviabile: solo ad-hoc e solo se il pagamento collegato non è registrato.
function isArchivable(deadline: DeadlineListItem): boolean {
    return deadline.is_custom && deadline.payment?.status !== 'paid';
}

// Side-sheet: aperto al click su una riga, alimentato dai dati di riga.
const sheetOpen = ref(false);
const selectedDeadline = ref<DeadlineListItem | null>(null);

function openDeadline(deadline: DeadlineListItem): void {
    selectedDeadline.value = deadline;
    sheetOpen.value = true;
}

// Chrome filtri (search + toggle stato + pannello faccette + paginazione)
// estratta in composable: la pagina resta vista + dialog/sheet/archive.
const {
    searchTerm,
    statusTab,
    setStatus,
    filtersOpen,
    filterState,
    activeFilterCount,
    hasActiveFilters,
    applyPanelFilters,
    clearAllFilters,
    goToPage,
} = useDeadlineFilters(() => props.filters);
</script>

<template>
    <Head title="Scadenze" />

    <Teleport to="#page-topbar-actions" defer>
        <Button size="sm" @click="openCreate">
            <PhPlus :size="14" weight="bold" />
            Nuova scadenza
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
                placeholder="Cerca scadenza…"
            />
        </InputGroup>
    </Teleport>

    <Teleport to="#page-topbar-filters" defer>
        <div class="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="relative"
                :aria-pressed="filtersOpen"
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
            <ToggleGroup
                :model-value="statusTab"
                type="single"
                variant="boxed"
                size="sm"
                @update:model-value="setStatus"
            >
                <ToggleGroupItem
                    v-for="tab in STATE_TABS"
                    :key="tab.value"
                    :value="tab.value"
                    :aria-label="tab.label"
                >
                    <component :is="tab.icon" :size="14" />
                    <!-- Solo icona su phone per non far debordare il subbar. -->
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    </Teleport>

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[100px]">Scadenza</TableHead>
                <TableHead>Descrizione</TableHead>
                <TableHead>Voce di spesa</TableHead>
                <TableHead class="w-[110px]">Tipo</TableHead>
                <TableHead class="w-[70px] text-right">Anno</TableHead>
                <TableHead class="w-[120px] text-right">Previsto</TableHead>
                <TableHead class="w-[120px]">Stato</TableHead>
                <TableHead class="w-[48px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="deadlines.data.length === 0" :colspan="8">
                <span v-if="hasActiveFilters">Nessuna scadenza trovata con questi filtri.</span>
                <span v-else>Nessuna scadenza. Apri un anno per generarle o creane una dal pulsante in alto.</span>
            </TableEmpty>
            <TableRow
                v-for="deadline in deadlines.data"
                v-else
                :key="deadline.id"
                class="cursor-pointer transition-colors hover:bg-muted/40"
                :class="
                    sheetOpen && selectedDeadline?.id === deadline.id
                        ? 'bg-accent-strong/5 shadow-[inset_2px_0_0_0_var(--accent-strong)]'
                        : ''
                "
                @click="openDeadline(deadline)"
            >
                <TableCell class="tabular text-muted-foreground">
                    {{ formatDateIT(deadline.due_at) }}
                </TableCell>
                <TableCell class="text-foreground">
                    <span class="block truncate" :title="deadline.name">{{ deadline.name }}</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    <span v-if="deadline.annual_expense_name" class="block truncate" :title="deadline.annual_expense_name">
                        {{ deadline.annual_expense_name }}
                    </span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="text-muted-foreground">{{ deadline.kind_label }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{ deadline.year }}</TableCell>
                <TableCell class="tabular text-right text-foreground">
                    <span v-if="deadline.expected_amount !== null">{{ formatEUR(deadline.expected_amount) }}</span>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                    <Badge :variant="DEADLINE_STATUS_META[deadline.status].variant" class="gap-1">
                        <component :is="DEADLINE_STATUS_META[deadline.status].icon" :size="12" />
                        {{ deadline.status_label }}
                    </Badge>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Azioni scadenza"
                            >
                                <PhDotsThreeVertical :size="14" weight="bold" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @select="openEdit(deadline)">
                                <PhPencil :size="14" />
                                Modifica
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="isArchivable(deadline)"
                                variant="destructive"
                                @select="askArchive(deadline)"
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
        v-if="deadlines.last_page > 1"
        class="mt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular text-xs text-muted-foreground">
            {{ deadlines.from }}–{{ deadlines.to }} di {{ deadlines.total }}
        </span>
        <Pagination
            :total="deadlines.total"
            :items-per-page="deadlines.per_page"
            :page="deadlines.current_page"
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
                        :is-active="item.value === deadlines.current_page"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis v-else :key="`e-${idx}`" :index="idx" />
                </template>
                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </footer>

    <FilterPanel
        v-model:open="filtersOpen"
        :active-count="activeFilterCount"
        title="Filtri"
        @apply="applyPanelFilters"
        @clear="clearAllFilters"
    >
        <template #default="{ requestLiveApply }">
            <DeadlineFilters
                v-model="filterState"
                :kind-options="kindOptions"
                :available-years="availableYears"
                :available-due-years="availableDueYears"
                :expense-items="expenseItems"
                @update:model-value="requestLiveApply"
            />
        </template>
    </FilterPanel>

    <DeadlineSheet v-model:open="sheetOpen" :deadline="selectedDeadline" />

    <DeadlineFormDialog
        v-model:open="formOpen"
        :deadline="formDeadline"
        :annual-expenses="annualExpenses"
        :year-options="yearOptions"
        :kind-options="kindOptions"
    />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la scadenza?"
        :description="archiveTarget
            ? `«${archiveTarget.name}» verrà nascosta dall'elenco. Puoi crearne un'altra in qualsiasi momento.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
