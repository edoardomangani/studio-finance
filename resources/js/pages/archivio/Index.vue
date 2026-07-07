<script setup lang="ts">
/**
 * Archivio (F11): record archiviati (soft delete) di tutte le entità, a tab.
 * Riga uniforme (nome · dettaglio · importo? · archiviato il) + ripristino con
 * conferma. Solo lettura + restore: niente edit qui (si torna in lista e si
 * modifica dalla superficie propria dell'entità).
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PhArrowCounterClockwise } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatDateIT, formatEUR } from '@/lib/format';
import { restore as restoreRoute } from '@/routes/archivio';
import type { ArchiveData, ArchiveRow, ArchiveType } from '@/types';

const props = defineProps<{ archive: ArchiveData }>();

setLayoutProps({
    pageTitle: 'Archivio',
    pageCrumbs: [{ label: 'Archivio' }],
});

type TabDef = { type: ArchiveType; label: string; hasAmount: boolean };

const TABS: TabDef[] = [
    { type: 'clients', label: 'Clienti', hasAmount: false },
    { type: 'invoices', label: 'Fatture', hasAmount: true },
    { type: 'expense-items', label: 'Voci di spesa', hasAmount: false },
    { type: 'recurring-deadlines', label: 'Scadenze tipo', hasAmount: false },
    { type: 'payments', label: 'Pagamenti', hasAmount: true },
];

// Apre sul primo tab con contenuto: atterrare su un tab vuoto quando altrove
// ci sono record archiviati è disorientante.
const firstNonEmpty =
    TABS.find((t) => props.archive[t.type].length > 0)?.type ?? 'clients';
const activeTab = ref<ArchiveType>(firstNonEmpty);

const pending = ref<{ type: ArchiveType; row: ArchiveRow } | null>(null);
const confirmOpen = ref(false);
const restoring = ref(false);

const confirmTitle = computed<string>(() =>
    pending.value ? `Ripristina "${pending.value.row.name}"?` : '',
);

// Colonne: Nome · Dettaglio · [Importo] · Archiviato · azioni.
function colspanFor(tab: TabDef): number {
    return 4 + (tab.hasAmount ? 1 : 0);
}

function askRestore(type: ArchiveType, row: ArchiveRow): void {
    pending.value = { type, row };
    confirmOpen.value = true;
}

function confirmRestore(): void {
    if (!pending.value) {
        return;
    }

    restoring.value = true;
    router.post(
        restoreRoute({ type: pending.value.type, id: pending.value.row.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
                pending.value = null;
            },
            onError: () => {
                toast.error('Ripristino non riuscito. Riprova.');
            },
            onFinish: () => {
                restoring.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Archivio" />

    <Tabs v-model="activeTab">
        <TabsList
            class="max-w-full justify-start overflow-x-auto scrollbar-none lg:justify-center [&::-webkit-scrollbar]:hidden"
        >
            <TabsTrigger
                v-for="t in TABS"
                :key="t.type"
                :value="t.type"
                class="shrink-0"
            >
                {{ t.label }}
                <Badge
                    v-if="props.archive[t.type].length"
                    variant="secondary"
                    class="tabular ml-1.5"
                >
                    {{ props.archive[t.type].length }}
                </Badge>
            </TabsTrigger>
        </TabsList>

        <TabsContent v-for="t in TABS" :key="t.type" :value="t.type">
            <DataTable container-class="hidden lg:block">
                <DataTableHeader>
                    <TableHead>Nome</TableHead>
                    <TableHead>Dettaglio</TableHead>
                    <TableHead v-if="t.hasAmount" class="text-right">
                        Importo
                    </TableHead>
                    <TableHead class="w-[130px]">Archiviato</TableHead>
                </DataTableHeader>
                <DataTableBody>
                    <TableEmpty
                        v-if="props.archive[t.type].length === 0"
                        :colspan="colspanFor(t)"
                    >
                        Nessun elemento archiviato.
                    </TableEmpty>
                    <DataTableRow
                        v-for="row in props.archive[t.type]"
                        v-else
                        :key="row.id"
                    >
                        <TableCell class="font-medium text-foreground">
                            {{ row.name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ row.detail ?? '—' }}
                        </TableCell>
                        <TableCell
                            v-if="t.hasAmount"
                            class="tabular text-right text-foreground"
                        >
                            {{ row.amount !== null ? formatEUR(row.amount) : '—' }}
                        </TableCell>
                        <TableCell class="tabular text-muted-foreground">
                            {{ row.archived_at ? formatDateIT(row.archived_at) : '—' }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="askRestore(t.type, row)"
                            >
                                <PhArrowCounterClockwise :size="14" />
                                Ripristina
                            </Button>
                        </TableCell>
                    </DataTableRow>
                </DataTableBody>
            </DataTable>

            <!-- Mobile (<lg): card list. Sola lettura + Ripristina (unica azione,
                 quindi bottone visibile, non in kebab). -->
            <div class="lg:hidden">
                <div
                    v-if="props.archive[t.type].length === 0"
                    class="rounded-lg border border-dashed border-border p-6 text-center text-13 text-muted-foreground"
                >
                    Nessun elemento archiviato.
                </div>
                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="row in props.archive[t.type]"
                        :key="row.id"
                        class="flex items-start gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-foreground">
                                {{ row.name }}
                            </div>
                            <div
                                v-if="row.detail"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ row.detail }}
                            </div>
                            <div class="mt-0.5 text-xs text-muted-foreground">
                                <template v-if="t.hasAmount && row.amount !== null">
                                    <span class="tabular text-foreground">{{ formatEUR(row.amount) }}</span>
                                    ·
                                </template>
                                archiviato
                                <span class="tabular">{{ row.archived_at ? formatDateIT(row.archived_at) : '—' }}</span>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="shrink-0"
                            @click="askRestore(t.type, row)"
                        >
                            <PhArrowCounterClockwise :size="14" />
                            Ripristina
                        </Button>
                    </li>
                </ul>
            </div>
        </TabsContent>
    </Tabs>

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="confirmTitle"
        description="Tornerà nelle liste attive e nei calcoli."
        confirm-label="Ripristina"
        :loading="restoring"
        @confirm="confirmRestore"
    />
</template>
