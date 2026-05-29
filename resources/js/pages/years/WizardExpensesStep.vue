<script setup lang="ts">
/**
 * Wizard apertura anno — Step 2 (Voci di spesa).
 *
 * Tabella densa delle voci ereditate dai template. Per riga sono editabili i
 * campi pertinenti al tipo di calcolo (aliquota/min/max per le percentuali,
 * quota per le fisse); la voce Bolli è read-only (importo = somma bolli,
 * derivata). Il toggle "Attiva" esclude la voce da quest'anno: la riga
 * esclusa si attenua e i suoi campi si disabilitano.
 *
 * Muta direttamente le righe reattive passate dal wizard (single source of
 * truth = il useForm dell'orchestratore).
 */
import {
    NumberField,
    NumberFieldContent,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { ExpenseCalculationType, YearPlanExpense } from '@/types';

type WizardExpense = YearPlanExpense & { included: boolean };

defineProps<{
    expenses: WizardExpense[];
}>();

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
            Valori ereditati dai template, modificabili per quest'anno. Disattiva una voce
            per escluderla dall'anno corrente; le scadenze collegate non verranno generate.
        </p>

        <Table boxed>
            <TableHeader>
                <TableRow>
                    <TableHead>Voce</TableHead>
                    <TableHead class="w-[120px]">Tipo</TableHead>
                    <TableHead class="w-[110px] text-right">Aliquota %</TableHead>
                    <TableHead class="w-[120px] text-right">Minimale</TableHead>
                    <TableHead class="w-[120px] text-right">Massimale</TableHead>
                    <TableHead class="w-[120px] text-right">Quota €</TableHead>
                    <TableHead class="w-[72px] text-center">Attiva</TableHead>
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
                    <TableCell class="font-medium text-foreground">
                        <span class="block max-w-[220px] truncate" :title="expense.name">{{ expense.name }}</span>
                    </TableCell>
                    <TableCell class="text-muted-foreground">
                        {{ CALCULATION_LABELS[expense.calculation_type] }}
                    </TableCell>

                    <TableCell class="text-right">
                        <NumberField
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.rate"
                            :min="0"
                            :max="100"
                            :step="0.5"
                            :disabled="!expense.included"
                            :format-options="{ maximumFractionDigits: 2 }"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="tabular text-right" :aria-label="`Aliquota ${expense.name}`" />
                            </NumberFieldContent>
                        </NumberField>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <NumberField
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.minimum"
                            :min="0"
                            :step="50"
                            :disabled="!expense.included"
                            :format-options="{ maximumFractionDigits: 2 }"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="tabular text-right" :aria-label="`Minimale ${expense.name}`" />
                            </NumberFieldContent>
                        </NumberField>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <NumberField
                            v-if="isPercentage(expense.calculation_type)"
                            v-model="expense.maximum"
                            :min="0"
                            :step="50"
                            :disabled="!expense.included"
                            :format-options="{ maximumFractionDigits: 2 }"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="tabular text-right" :aria-label="`Massimale ${expense.name}`" />
                            </NumberFieldContent>
                        </NumberField>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-right">
                        <NumberField
                            v-if="isFixed(expense.calculation_type)"
                            v-model="expense.amount"
                            :min="0"
                            :step="10"
                            :disabled="!expense.included"
                            :format-options="{ maximumFractionDigits: 2 }"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="tabular text-right" :aria-label="`Quota ${expense.name}`" />
                            </NumberFieldContent>
                        </NumberField>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell class="text-center">
                        <Switch
                            v-model="expense.included"
                            :aria-label="`Includi ${expense.name}`"
                        />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </section>
</template>
