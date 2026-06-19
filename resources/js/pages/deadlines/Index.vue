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
        <!-- Nuova scadenza: mobile icon 36, desktop con label. -->
        <Button
            size="icon-md"
            class="lg:hidden"
            aria-label="Nuova scadenza"
            @click="table?.openCreate()"
        >
            <PhPlus :size="16" weight="bold" />
        </Button>
        <Button
            size="sm"
            class="hidden lg:inline-flex"
            @click="table?.openCreate()"
        >
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
            <!-- Mobile: icon 36 (badge a corner). Desktop: con label. -->
            <Button
                type="button"
                variant="outline"
                size="icon-md"
                class="relative lg:hidden"
                :aria-pressed="filtersOpen"
                aria-label="Filtri"
                @click="filtersOpen = !filtersOpen"
            >
                <PhFunnel :size="16" />
                <Badge
                    v-if="activeFilterCount > 0"
                    variant="secondary"
                    class="tabular absolute -top-1 -right-1 h-4 min-w-4 px-1 text-2xs"
                >
                    {{ activeFilterCount }}
                </Badge>
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="relative hidden lg:inline-flex"
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
            <!-- Toggle stati: nella subbar solo da lg (su mobile è una riga
                 propria sopra la lista, vedi sotto). -->
            <ToggleGroup
                :model-value="statusTab"
                type="single"
                variant="boxed"
                size="sm"
                class="hidden lg:flex"
                @update:model-value="setStatus"
            >
                <ToggleGroupItem
                    v-for="tab in STATE_TABS"
                    :key="tab.value"
                    :value="tab.value"
                    :aria-label="tab.label"
                >
                    <component :is="tab.icon" :size="14" />
                    {{ tab.label }}
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    </Teleport>

    <!-- Toggle stati: su mobile riga propria full-width sopra la lista (la
         subbar è già piena di search + Filtri). Da lg vive nella subbar. -->
    <ToggleGroup
        :model-value="statusTab"
        type="single"
        variant="boxed"
        size="sm"
        class="mb-3 w-full lg:hidden"
        @update:model-value="setStatus"
    >
        <ToggleGroupItem
            v-for="tab in STATE_TABS"
            :key="tab.value"
            :value="tab.value"
            :aria-label="tab.label"
            class="flex-1"
        >
            <component :is="tab.icon" :size="14" />
            {{ tab.label }}
        </ToggleGroupItem>
    </ToggleGroup>

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
