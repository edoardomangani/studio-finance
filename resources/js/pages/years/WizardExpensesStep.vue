<script setup lang="ts">
/**
 * Wizard apertura anno — Step 2 (Voci di spesa).
 *
 * Tabella densa delle voci ereditate dai template. La checkbox a sinistra
 * include/esclude la voce dall'anno (riga esclusa attenuata + campi
 * disabilitati). Per riga sono editabili i campi pertinenti al tipo di calcolo
 * (aliquota/min/max per le percentuali, quota per le fisse) con input numerici
 * semplici allineati a destra, stesso pattern delle fatture; la voce Bolli è
 * read-only (importo = somma bolli, derivata).
 *
 * Muta direttamente le righe reattive passate dal wizard (single source of
 * truth = il useForm dell'orchestratore).
 */
import { Checkbox } from '@/components/ui/checkbox';
import { DecimalInput } from '@/components/ui/input';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import type { ExpenseCalculationType, YearWizardExpense } from '@/types';

// Two-way: l'orchestratore possiede lo useForm, qui editiamo le righe via
// v-model:expenses (no mutazione diretta di prop).
const expenses = defineModel<YearWizardExpense[]>('expenses', {
    required: true,
});

const CALCULATION_LABELS: Record<ExpenseCalculationType, string> = {
    percentage_of_irpef_income: '% reddito IRPEF',
    percentage_of_iva_revenue: '% volume IVA',
    fixed_annual: 'Quota fissa',
    sum_of_bolli: 'Somma bolli',
};

function isPercentage(type: ExpenseCalculationType): boolean {
    return (
        type === 'percentage_of_irpef_income' ||
        type === 'percentage_of_iva_revenue'
    );
}

function isFixed(type: ExpenseCalculationType): boolean {
    return type === 'fixed_annual';
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <p class="text-13 text-muted-foreground">
            Valori ereditati dai template, modificabili per quest'anno.
            Deseleziona una voce per escluderla dall'anno corrente; le scadenze
            collegate non verranno generate.
        </p>

        <DataTable container-class="hidden lg:block">
            <DataTableHeader :has-actions="false">
                <TableHead class="w-[52px]"
                    ><span class="sr-only">Includi</span></TableHead
                >
                <TableHead>Voce</TableHead>
                <TableHead class="w-[120px]">Tipo</TableHead>
                <TableHead class="w-[110px] text-right">Aliquota %</TableHead>
                <TableHead class="w-[120px] text-right">Minimale</TableHead>
                <TableHead class="w-[120px] text-right">Massimale</TableHead>
                <TableHead class="w-[120px] text-right">Quota €</TableHead>
            </DataTableHeader>
            <DataTableBody>
                <TableEmpty v-if="expenses.length === 0" :colspan="7">
                    Nessuna voce di spesa attiva. Aggiungine dalle impostazioni
                    prima di aprire l'anno.
                </TableEmpty>
                <DataTableRow
                    v-for="(expense, index) in expenses"
                    v-else
                    :key="expense.expense_item_id ?? `row-${index}`"
                    :class="expense.included ? '' : 'opacity-50'"
                >
                    <TableCell class="p-0">
                        <!-- Cella intera cliccabile: area touch ampia attorno al
                             checkbox 16px (la <label> inoltra il click al
                             checkbox, che è un button labelable). -->
                        <label
                            class="flex h-9 cursor-pointer items-center justify-center"
                        >
                            <Checkbox
                                v-model="expense.included"
                                :aria-label="`Includi ${expense.name}`"
                            />
                        </label>
                    </TableCell>
                    <TableCell class="font-medium text-foreground">
                        <span
                            class="block max-w-[220px] truncate"
                            :title="expense.name"
                            >{{ expense.name }}</span
                        >
                    </TableCell>
                    <TableCell class="text-muted-foreground">
                        {{ CALCULATION_LABELS[expense.calculation_type] }}
                    </TableCell>

                    <TableCell class="text-right">
                        <DecimalInput
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.rate"
                            :min="0"
                            :max="100"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Aliquota ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <DecimalInput
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.minimum"
                            :min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Minimale ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <DecimalInput
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.maximum"
                            :min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Massimale ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <DecimalInput
                            v-if="isFixed(expense.calculation_type)"
                            v-model="expense.amount"
                            :min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Quota ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>
                </DataTableRow>
            </DataTableBody>
        </DataTable>

        <!-- Mobile (<lg): card editabile per voce. Checkbox include/esclude,
             sotto i campi pertinenti al tipo con input a destra. -->
        <div class="lg:hidden">
            <div
                v-if="expenses.length === 0"
                class="rounded-lg border border-dashed border-border p-6 text-center text-13 text-muted-foreground"
            >
                Nessuna voce di spesa attiva. Aggiungine dalle impostazioni
                prima di aprire l'anno.
            </div>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="(expense, index) in expenses"
                    :key="expense.expense_item_id ?? `row-${index}`"
                    class="py-3"
                    :class="expense.included ? '' : 'opacity-50'"
                >
                    <label class="flex items-center gap-2.5">
                        <Checkbox
                            v-model="expense.included"
                            :aria-label="`Includi ${expense.name}`"
                        />
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-foreground">
                                {{ expense.name }}
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                {{ CALCULATION_LABELS[expense.calculation_type] }}
                            </span>
                        </span>
                    </label>

                    <div
                        v-if="isPercentage(expense.calculation_type)"
                        class="mt-2.5 flex flex-col gap-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Aliquota %</span>
                            <DecimalInput
                                v-model="expense.rate"
                                :min="0"
                                :max="100"
                                :disabled="!expense.included"
                                class="tabular w-36 text-right"
                                :aria-label="`Aliquota ${expense.name}`"
                            />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Minimale</span>
                            <DecimalInput
                                v-model="expense.minimum"
                                :min="0"
                                :disabled="!expense.included"
                                class="tabular w-36 text-right"
                                :aria-label="`Minimale ${expense.name}`"
                            />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Massimale</span>
                            <DecimalInput
                                v-model="expense.maximum"
                                :min="0"
                                :disabled="!expense.included"
                                class="tabular w-36 text-right"
                                :aria-label="`Massimale ${expense.name}`"
                            />
                        </div>
                    </div>

                    <div
                        v-else-if="isFixed(expense.calculation_type)"
                        class="mt-2.5 flex items-center justify-between gap-3"
                    >
                        <span class="text-sm text-muted-foreground">Quota €</span>
                        <DecimalInput
                            v-model="expense.amount"
                            :min="0"
                            :disabled="!expense.included"
                            class="tabular w-36 text-right"
                            :aria-label="`Quota ${expense.name}`"
                        />
                    </div>

                    <p v-else class="mt-1.5 text-xs text-muted-foreground">
                        Importo derivato dalla somma dei bolli delle fatture.
                    </p>
                </li>
            </ul>
        </div>
    </section>
</template>
