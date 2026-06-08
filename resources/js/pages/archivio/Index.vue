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

const activeTab = ref<ArchiveType>('clients');

const pending = ref<{ type: ArchiveType; row: ArchiveRow } | null>(null);
const confirmOpen = ref(false);
const restoring = ref(false);

const confirmTitle = computed<string>(() =>
    pending.value ? `Ripristina "${pending.value.row.name}"?` : '',
);

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
            onFinish: () => {
                restoring.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Archivio" />

    <Tabs v-model="activeTab" class="gap-4">
        <TabsList>
            <TabsTrigger v-for="t in TABS" :key="t.type" :value="t.type">
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
            <DataTable>
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
                        :colspan="t.hasAmount ? 5 : 4"
                    >
                        Niente di archiviato qui.
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
