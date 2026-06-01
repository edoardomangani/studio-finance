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
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { clampNumber } from '@/lib/clampNumber';
import type { ExpenseCalculationType, YearWizardExpense } from '@/types';

// Two-way: l'orchestratore possiede lo useForm, qui editiamo le righe via
// v-model:expenses (no mutazione diretta di prop).
const expenses = defineModel<YearWizardExpense[]>('expenses', { required: true });

const CALCULATION_LABELS: Record<ExpenseCalculationType, string> = {
    percentage_of_irpef_income: '% reddito IRPEF',
    percentage_of_iva_revenue: '% volume IVA',
    fixed_annual: 'Quota fissa',
    sum_of_bolli: 'Somma bolli',
};

function isPercentage(type: ExpenseCalculationType): boolean {
    return type === 'percentage_of_irpef_income' || type === 'percentage_of_iva_revenue';
}

function isFixed(type: ExpenseCalculationType): boolean {
    return type === 'fixed_annual';
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <p class="text-13 text-muted-foreground">
            Valori ereditati dai template, modificabili per quest'anno. Deseleziona una voce
            per escluderla dall'anno corrente; le scadenze collegate non verranno generate.
        </p>

        <Table boxed>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[52px]"><span class="sr-only">Includi</span></TableHead>
                    <TableHead>Voce</TableHead>
                    <TableHead class="w-[120px]">Tipo</TableHead>
                    <TableHead class="w-[110px] text-right">Aliquota %</TableHead>
                    <TableHead class="w-[120px] text-right">Minimale</TableHead>
                    <TableHead class="w-[120px] text-right">Massimale</TableHead>
                    <TableHead class="w-[120px] text-right">Quota €</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableEmpty v-if="expenses.length === 0" :colspan="7">
                    Nessuna voce di spesa attiva. Aggiungine dalle impostazioni prima di aprire l'anno.
                </TableEmpty>
                <TableRow
                    v-for="(expense, index) in expenses"
                    v-else
                    :key="expense.expense_item_id ?? `row-${index}`"
                    :class="expense.included ? '' : 'opacity-50'"
                >
                    <TableCell class="p-0">
                        <!-- Cella intera cliccabile: area touch ampia attorno al
                             checkbox 16px (la <label> inoltra il click al
                             checkbox, che è un button labelable). -->
                        <label class="flex h-9 cursor-pointer items-center justify-center">
                            <Checkbox
                                v-model="expense.included"
                                :aria-label="`Includi ${expense.name}`"
                            />
                        </label>
                    </TableCell>
                    <TableCell class="font-medium text-foreground">
                        <span class="block max-w-[220px] truncate" :title="expense.name">{{ expense.name }}</span>
                    </TableCell>
                    <TableCell class="text-muted-foreground">
                        {{ CALCULATION_LABELS[expense.calculation_type] }}
                    </TableCell>

                    <TableCell class="text-right">
                        <Input
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.rate"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            max="100"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Aliquota ${expense.name}`"
                            @blur="expense.rate = clampNumber(expense.rate, 0, 100)"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <Input
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.minimum"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Minimale ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <Input
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.maximum"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Massimale ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <Input
                            v-if="isFixed(expense.calculation_type)"
                            v-model="expense.amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            :disabled="!expense.included"
                            class="tabular text-right"
                            :aria-label="`Quota ${expense.name}`"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </section>
</template>
