<script setup lang="ts">
/**
 * DeadlinesTable — tabella scadenze condivisa tra la lista (deadlines/Index) e
 * il tab Scadenze della vista anno. Auto-contenuta: colonne, click-riga →
 * side-sheet (registrazione + reversibilità), kebab Modifica/Archivia (solo
 * ad-hoc) col form dialog e la ConfirmDialog. Il chrome (search, filtri,
 * paginazione) e il bottone "Nuova scadenza" restano al chiamante, che apre il
 * form via `openCreate()` (esposto). Lo stato vuoto è personalizzabile via slot.
 */
import { PhArchive, PhEye, PhPencil } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import DeadlineController from '@/actions/App/Http/Controllers/DeadlineController';
import { Badge } from '@/components/ui/badge';
import { ConfirmDialog } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableRow,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { formatDateIT, formatEUR } from '@/lib/format';
import DeadlineFormDialog from '@/pages/deadlines/DeadlineFormDialog.vue';
import DeadlineSheet from '@/pages/deadlines/DeadlineSheet.vue';
import { DEADLINE_KIND_META } from '@/pages/deadlines/kindMeta';
import { DEADLINE_STATUS_META } from '@/pages/deadlines/statusMeta';
import type {
    AnnualExpenseForPicker,
    DeadlineListItem,
    EnumOption,
    YearOption,
} from '@/types';

// `withTotals`: footer col totale (somma degli importi effettivi). Solo quando
// le `deadlines` sono il set completo (vista anno).
const props = withDefaults(
    defineProps<{
        deadlines: DeadlineListItem[];
        annualExpenses: AnnualExpenseForPicker[];
        yearOptions: YearOption[];
        kindOptions: EnumOption[];
        withTotals?: boolean;
    }>(),
    { withTotals: false },
);

// Pagamento registrato (importo confermato dall'F24), non solo pianificato.
function isPaid(deadline: DeadlineListItem): boolean {
    return (
        deadline.payment?.status === 'paid' && deadline.payment.amount !== null
    );
}

// Importo effettivo di riga: il versato se registrato, altrimenti il previsto
// (suggerimento). Le non dovute non hanno importo. Base di cella e totale.
function effectiveAmount(deadline: DeadlineListItem): number | null {
    if (deadline.status === 'not_due') {
        return null;
    }

    if (isPaid(deadline)) {
        return deadline.payment!.amount;
    }

    return deadline.expected_amount;
}

// Scostamento versato − previsto, mostrato solo se registrato e diverge.
function paymentDelta(deadline: DeadlineListItem): number | null {
    if (!isPaid(deadline) || deadline.expected_amount === null) {
        return null;
    }

    const delta = deadline.payment!.amount! - deadline.expected_amount;

    return Math.abs(delta) < 0.005 ? null : delta;
}

function formatDelta(delta: number): string {
    return `${delta > 0 ? '+' : '−'}${formatEUR(Math.abs(delta))}`;
}

const totalAmount = computed(() =>
    props.deadlines.reduce((acc, d) => acc + (effectiveAmount(d) ?? 0), 0),
);

// Side-sheet: aperto al click su una riga, alimentato dai dati di riga.
const sheetOpen = ref(false);
const selectedDeadline = ref<DeadlineListItem | null>(null);

function openDeadline(deadline: DeadlineListItem): void {
    selectedDeadline.value = deadline;
    sheetOpen.value = true;
}

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

// Archiviazione: solo ad-hoc e solo se il pagamento collegato non è registrato.
const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<DeadlineListItem>((deadline) =>
        DeadlineController.destroy.url({ deadline: deadline.id }),
    );

function isArchivable(deadline: DeadlineListItem): boolean {
    return deadline.is_custom && deadline.payment?.status !== 'paid';
}

// Il chiamante (topbar lista / header tab) apre la creazione.
defineExpose({ openCreate });
</script>

<template>
    <DataTable>
        <DataTableHeader>
            <TableHead class="w-[100px]">Scadenza</TableHead>
            <TableHead>Descrizione</TableHead>
            <TableHead>Voce di spesa</TableHead>
            <TableHead class="w-[110px]">Tipo</TableHead>
            <TableHead class="w-[70px] text-right">Anno</TableHead>
            <TableHead class="w-[130px] text-right">Importo</TableHead>
            <TableHead class="w-[120px]">Stato</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="deadlines.length === 0" :colspan="8">
                <slot name="empty">Nessuna scadenza.</slot>
            </TableEmpty>
            <DataTableRow
                v-for="deadline in deadlines"
                v-else
                :key="deadline.id"
                interactive
                :active="sheetOpen && selectedDeadline?.id === deadline.id"
                @click="openDeadline(deadline)"
            >
                <TableCell class="tabular text-muted-foreground">{{
                    formatDateIT(deadline.due_at)
                }}</TableCell>
                <TableCell class="text-foreground">
                    <span class="block truncate" :title="deadline.name">{{
                        deadline.name
                    }}</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    <span
                        v-if="deadline.annual_expense_name"
                        class="block truncate"
                        :title="deadline.annual_expense_name"
                    >
                        {{ deadline.annual_expense_name }}
                    </span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell>
                    <Badge
                        :variant="DEADLINE_KIND_META[deadline.kind].variant"
                        class="gap-1"
                    >
                        <component
                            :is="DEADLINE_KIND_META[deadline.kind].icon"
                            :size="12"
                        />
                        {{ deadline.kind_label }}
                    </Badge>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    deadline.year
                }}</TableCell>
                <TableCell
                    class="tabular text-right"
                    :title="
                        isPaid(deadline) && deadline.expected_amount !== null
                            ? `Previsto ${formatEUR(deadline.expected_amount)}, versato ${formatEUR(deadline.payment!.amount!)}`
                            : undefined
                    "
                >
                    <template v-if="effectiveAmount(deadline) !== null">
                        <span
                            :class="
                                isPaid(deadline)
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                            >{{ formatEUR(effectiveAmount(deadline)!) }}</span
                        >
                        <span
                            v-if="paymentDelta(deadline) !== null"
                            class="block text-2xs text-muted-foreground"
                            >{{ formatDelta(paymentDelta(deadline)!) }}</span
                        >
                    </template>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                    <Badge
                        :variant="DEADLINE_STATUS_META[deadline.status].variant"
                        class="gap-1"
                    >
                        <component
                            :is="DEADLINE_STATUS_META[deadline.status].icon"
                            :size="12"
                        />
                        {{ deadline.status_label }}
                    </Badge>
                </TableCell>
                <template #actions>
                    <DropdownMenuItem @select="openDeadline(deadline)">
                        <PhEye :size="14" />
                        Visualizza
                    </DropdownMenuItem>
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
                </template>
            </DataTableRow>
        </DataTableBody>
        <TableFooter v-if="withTotals && deadlines.length > 0">
            <TableRow class="font-medium">
                <TableCell class="text-foreground">Totale</TableCell>
                <TableCell />
                <TableCell />
                <TableCell />
                <TableCell />
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totalAmount)
                }}</TableCell>
                <TableCell />
                <TableCell />
            </TableRow>
        </TableFooter>
    </DataTable>

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
        :description="
            archiveTarget
                ? `«${archiveTarget.name}» verrà nascosta dall'elenco. Puoi crearne un'altra in qualsiasi momento.`
                : undefined
        "
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
