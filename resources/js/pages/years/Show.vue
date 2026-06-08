<script setup lang="ts">
/**
 * Years — Show (cockpit dell'anno, Fase 9).
 *
 * Centro operativo dell'anno: si atterra qui entrando nella sezione Anni
 * (redirect all'anno corrente). Topbar con switcher tra gli anni e azioni di
 * livello-anno; corpo a tab (Panoramica · Fatture · Scadenze · Pagamenti ·
 * Spese), ciascuno scopato su quest'anno e con la propria azione. Le azioni
 * complete (registra pagamento, nuova fattura, ecc.) riusano le superfici già
 * esistenti. Desktop-first: il pass mobile è lo step successivo.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { PhArrowsLeftRight, PhPlus } from '@phosphor-icons/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import DeadlinesPanel from '@/pages/years/show/DeadlinesPanel.vue';
import ExpensesPanel from '@/pages/years/show/ExpensesPanel.vue';
import InvoicesPanel from '@/pages/years/show/InvoicesPanel.vue';
import OverviewPanel from '@/pages/years/show/OverviewPanel.vue';
import PaymentsPanel from '@/pages/years/show/PaymentsPanel.vue';
import {
    compare as yearsCompare,
    create as yearCreate,
    index as yearsIndex,
    show as yearShow,
} from '@/routes/years';
import type {
    AnnualExpenseForPicker,
    EnumOption,
    YearOption,
    YearShow,
} from '@/types';

const props = defineProps<{
    year: YearShow;
    // Opzioni per il form scadenza ad-hoc del tab Scadenze.
    annualExpenses: AnnualExpenseForPicker[];
    yearOptions: YearOption[];
    kindOptions: EnumOption[];
}>();

setLayoutProps({
    pageTitle: `Anno ${props.year.year}`,
    pageCrumbs: [
        { label: 'Anni', href: yearsIndex().url },
        { label: String(props.year.year) },
    ],
    subbar: false,
});

const TABS = [
    { value: 'overview', label: 'Panoramica' },
    { value: 'invoices', label: 'Fatture' },
    { value: 'deadlines', label: 'Scadenze' },
    { value: 'payments', label: 'Pagamenti' },
    { value: 'expenses', label: 'Spese' },
];

// Tab corrente sincronizzato con l'URL (?tab=…): switch istantaneo lato client
// (i dati di tutti i tab sono già in props), senza visita Inertia. pushState
// (non replace) così il back del browser torna al tab precedente; un listener
// popstate risincronizza il tab quando si naviga avanti/indietro.
function tabFromUrl(): string {
    const tab = new URLSearchParams(window.location.search).get('tab');

    return TABS.some((t) => t.value === tab) ? (tab as string) : 'overview';
}

const activeTab = ref(tabFromUrl());

function selectTab(value: string | number): void {
    const tab = String(value);

    if (tab === activeTab.value) {
        return;
    }

    activeTab.value = tab;
    const url = new URL(window.location.href);

    if (tab === 'overview') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    // Mantengo lo state di Inertia per non rompere il suo back fra le pagine.
    window.history.pushState(window.history.state, '', url);
}

function syncTabFromHistory(): void {
    activeTab.value = tabFromUrl();
}

onMounted(() => window.addEventListener('popstate', syncTabFromHistory));
onBeforeUnmount(() => window.removeEventListener('popstate', syncTabFromHistory));

function switchYear(value: unknown): void {
    const raw = Array.isArray(value) ? value[0] : value;
    const year = Number(raw);

    if (year && year !== props.year.year) {
        router.visit(yearShow(year).url);
    }
}
</script>

<template>
    <Head :title="`Anno ${year.year}`" />

    <Teleport to="#page-topbar-actions" defer>
        <div class="flex items-center gap-2">
            <ToggleGroup
                :model-value="String(year.year)"
                type="single"
                variant="boxed"
                size="sm"
                @update:model-value="switchYear"
            >
                <ToggleGroupItem
                    v-for="nav in year.years_nav"
                    :key="nav.year"
                    :value="String(nav.year)"
                    class="tabular"
                    :aria-label="`Anno ${nav.year}`"
                >
                    {{ nav.year }}
                    <span
                        v-if="nav.pre_opened"
                        class="ml-1 size-1.5 rounded-full bg-muted-foreground/50"
                        aria-hidden="true"
                    />
                </ToggleGroupItem>
            </ToggleGroup>

            <Badge v-if="year.pre_opened" variant="secondary">Pre-aperto</Badge>

            <Button
                v-if="year.years_nav.length > 1"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="yearsCompare().url">
                    <PhArrowsLeftRight :size="14" />
                    Confronto
                </Link>
            </Button>

            <Button v-if="year.meta.can_open_next" as-child size="sm">
                <Link :href="yearCreate().url">
                    <PhPlus :size="14" weight="bold" />
                    Apri {{ year.meta.next_year }}
                </Link>
            </Button>
        </div>
    </Teleport>

    <Tabs :model-value="activeTab" @update:model-value="selectTab">
        <TabsList>
            <TabsTrigger
                v-for="tab in TABS"
                :key="tab.value"
                :value="tab.value"
            >
                {{ tab.label }}
            </TabsTrigger>
        </TabsList>

        <TabsContent value="overview" class="mt-4">
            <OverviewPanel :year="year" @go="selectTab" />
        </TabsContent>
        <TabsContent value="invoices" class="mt-4">
            <InvoicesPanel :year="year" />
        </TabsContent>
        <TabsContent value="deadlines" class="mt-4">
            <DeadlinesPanel
                :year="year"
                :annual-expenses="annualExpenses"
                :year-options="yearOptions"
                :kind-options="kindOptions"
            />
        </TabsContent>
        <TabsContent value="payments" class="mt-4">
            <PaymentsPanel :year="year" :annual-expenses="annualExpenses" />
        </TabsContent>
        <TabsContent value="expenses" class="mt-4">
            <ExpensesPanel :year="year" />
        </TabsContent>
    </Tabs>
</template>
