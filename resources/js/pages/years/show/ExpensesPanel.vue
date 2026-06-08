<script setup lang="ts">
/**
 * Tab Spese annuali della vista anno: famiglia importi per voce (previsto /
 * definitivo / pagato / da pagare). Click su una riga → side-sheet con la
 * derivazione del calcolo (FormulaBlock) e la modifica dei valori della voce.
 * Kebab per riga: Modifica (apre lo sheet) e — solo per le voci una-tantum
 * senza pagamenti — Elimina. Le voci da template non si cancellano: sono la
 * struttura fiscale dell'anno (alimentano calcoli e scadenze).
 */
import { router } from '@inertiajs/vue3';
import { PhPencil, PhPlus, PhTrash } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import AnnualExpenseController from '@/actions/App/Http/Controllers/AnnualExpenseController';
import { Button } from '@/components/ui/button';
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
import { formatEUR } from '@/lib/format';
import AnnualExpenseFormDialog from '@/pages/years/show/AnnualExpenseFormDialog.vue';
import AnnualExpenseSheet from '@/pages/years/show/AnnualExpenseSheet.vue';
import type { YearShow, YearShowExpense } from '@/types';

defineProps<{ year: YearShow }>();

const sheetOpen = ref(false);
const selected = ref<YearShowExpense | null>(null);

function openExpense(expense: YearShowExpense): void {
    selected.value = expense;
    sheetOpen.value = true;
}

// Eliminabile solo se una-tantum (no template) e senza pagamenti registrati.
function canDelete(expense: YearShowExpense): boolean {
    return expense.is_custom && expense.paid === 0;
}

const deleteOpen = ref(false);
const deleteTarget = ref<YearShowExpense | null>(null);

function askDelete(expense: YearShowExpense): void {
    deleteTarget.value = expense;
    deleteOpen.value = true;
}

function runDelete(): void {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(AnnualExpenseController.destroy.url({ annualExpense: deleteTarget.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
        },
        onError: () => toast.error('Eliminazione non riuscita. Riprova.'),
    });
}

// Creazione spesa una-tantum dell'anno.
const createOpen = ref(false);
</script>

<template>
    <div class="flex flex-col gap-3">
        <header class="flex items-center justify-between">
            <h2 class="kicker text-muted-foreground">{{ year.expenses.length }} voci di spesa</h2>
            <Button size="sm" @click="createOpen = true">
                <PhPlus :size="14" weight="bold" />
                Aggiungi spesa
            </Button>
        </header>

        <DataTable>
            <DataTableHeader>
                <TableHead>Voce</TableHead>
                <TableHead class="text-right">Previsto</TableHead>
                <TableHead class="text-right">Definitivo</TableHead>
                <TableHead class="text-right">Pagato</TableHead>
                <TableHead class="text-right">Da pagare</TableHead>
            </DataTableHeader>
            <DataTableBody>
                <TableEmpty v-if="year.expenses.length === 0" :colspan="6">
                    Nessuna voce di spesa.
                </TableEmpty>
                <DataTableRow
                    v-for="e in year.expenses"
                    v-else
                    :key="e.id"
                    interactive
                    :active="sheetOpen && selected?.id === e.id"
                    @click="openExpense(e)"
                >
                    <TableCell class="font-medium text-foreground">
                        {{ e.name }}
                        <span class="block text-xs font-normal text-muted-foreground">{{ e.calculation_type_label }}</span>
                    </TableCell>
                    <TableCell class="tabular text-right text-muted-foreground">{{ formatEUR(e.expected) }}</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(e.definitive) }}</TableCell>
                    <TableCell class="tabular text-right text-muted-foreground">{{ formatEUR(e.paid) }}</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(e.due) }}</TableCell>
                    <template #actions>
                        <DropdownMenuItem @select="openExpense(e)">
                            <PhPencil :size="14" />
                            Modifica
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="canDelete(e)"
                            variant="destructive"
                            @select="askDelete(e)"
                        >
                            <PhTrash :size="14" />
                            Elimina
                        </DropdownMenuItem>
                    </template>
                </DataTableRow>
            </DataTableBody>
            <TableFooter v-if="year.expenses.length > 0">
                <TableRow class="font-medium">
                    <TableCell class="text-foreground">Totale</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(year.totals.expenses_expected) }}</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(year.totals.expenses_definitive) }}</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(year.totals.expenses_paid) }}</TableCell>
                    <TableCell class="tabular text-right text-foreground">{{ formatEUR(year.totals.expenses_due) }}</TableCell>
                    <TableCell />
                </TableRow>
            </TableFooter>
        </DataTable>

        <AnnualExpenseSheet v-model:open="sheetOpen" :expense="selected" />
        <AnnualExpenseFormDialog v-model:open="createOpen" :year-id="year.id" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Eliminare la spesa?"
            :description="
                deleteTarget
                    ? `«${deleteTarget.name}» verrà rimossa da quest'anno. Puoi ricrearla quando vuoi.`
                    : undefined
            "
            confirm-label="Elimina"
            destructive
            @confirm="runDelete"
        />
    </div>
</template>
