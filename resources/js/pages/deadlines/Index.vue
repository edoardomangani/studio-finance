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
    PhCheckCircle,
    PhCircleDashed,
    PhClock,
    PhFunnel,
    PhListBullets,
    PhMagnifyingGlass,
    PhPlus,
} from '@phosphor-icons/vue';
import { ref } from 'vue';
import type { Component } from 'vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { DataTablePagination } from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useDeadlineFilters } from '@/composables/useDeadlineFilters';
import DeadlineFilters from '@/pages/deadlines/DeadlineFilters.vue';
import DeadlinesTable from '@/pages/deadlines/DeadlinesTable.vue';
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

// Toggle stato: segmentatore primario. 'upcoming' (default) = prossime, aperte
// entro 3 mesi con le scadute incluse. 'closed' raccoglie completate + non
// dovute ("cose fatte"). Stesse icone degli stati riga.
const STATE_TABS: { value: string; label: string; icon: Component }[] = [
    { value: 'upcoming', label: 'Prossime', icon: PhClock },
    { value: 'open', label: 'Aperte', icon: PhCircleDashed },
    { value: 'closed', label: 'Completate', icon: PhCheckCircle },
    { value: 'all', label: 'Tutte', icon: PhListBullets },
];

setLayoutProps({
    pageTitle: 'Scadenze',
    pageCrumbs: [{ label: 'Scadenze' }],
    subbar: true,
});

// Tabella (con sheet, form ad-hoc e archivio) estratta in [[DeadlinesTable]]:
// la pagina resta chrome (search/filtri/paginazione) + la creazione, aperta
// via il metodo esposto dalla tabella.
const table = ref<InstanceType<typeof DeadlinesTable> | null>(null);

// Chrome filtri (search + toggle stato + pannello faccette + paginazione)
// estratta in composable.
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
        <Button size="sm" @click="table?.openCreate()">
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
                    class="tabular ml-1 h-4 min-w-4 px-1 text-2xs"
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

    <DeadlinesTable
        ref="table"
        :deadlines="deadlines.data"
        :annual-expenses="annualExpenses"
        :year-options="yearOptions"
        :kind-options="kindOptions"
    >
        <template #empty>
            <span v-if="hasActiveFilters">
                Nessuna scadenza trovata con questi filtri.
            </span>
            <span v-else>
                Nessuna scadenza. Apri un anno per generarle o creane una dal
                pulsante in alto.
            </span>
        </template>
    </DeadlinesTable>

    <DataTablePagination
        v-if="deadlines.last_page > 1"
        :page="deadlines.current_page"
        :per-page="deadlines.per_page"
        :total="deadlines.total"
        @update:page="goToPage"
    />

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
</template>
