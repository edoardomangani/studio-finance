<script setup lang="ts">
/**
 * AnnualExpenseSheet — dettaglio + modifica di una spesa annuale, aperto dal
 * click su una riga del tab Spese. Mostra il FormulaBlock (trasparenza calcolo,
 * lettura) e il form per correggere i valori di generazione (aliquota/minimale/
 * massimale per le percentuali, importo per le fisse, credito per l'imposta
 * sostitutiva). In coda, "Forza importo": l'override manuale (`effective_amount`)
 * che scavalca il calcolo per ogni tipo, bolli inclusi — vuoto = torna al
 * calcolato. `calculation_type` e `is_pension_contribution` non si toccano:
 * definiscono il tipo di voce. `ResponsiveDialog mode="sheet"`.
 */
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import AnnualExpenseController from '@/actions/App/Http/Controllers/AnnualExpenseController';
import FormField from '@/components/forms/FormField.vue';
import FormulaBlock from '@/components/FormulaBlock.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { formatEUR } from '@/lib/format';
import type { YearShowExpense } from '@/types';

const props = defineProps<{ expense: YearShowExpense | null }>();
const open = defineModel<boolean>('open', { default: false });

// Eliminabile solo se una-tantum (no template) e senza pagamenti registrati:
// le voci da template non si cancellano post-apertura.
const canDelete = computed(
    () => (props.expense?.is_custom ?? false) && (props.expense?.paid ?? 0) === 0,
);
const deleteOpen = ref(false);

function runDelete(): void {
    if (!props.expense) {
        return;
    }

    router.delete(AnnualExpenseController.destroy.url({ annualExpense: props.expense.id }), {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
            open.value = false;
        },
        onError: () => toast.error('Eliminazione non riuscita. Riprova.'),
    });
}

const isPercentage = computed(
    () =>
        props.expense?.calculation_type === 'percentage_of_irpef_income' ||
        props.expense?.calculation_type === 'percentage_of_iva_revenue',
);
const isFixed = computed(() => props.expense?.calculation_type === 'fixed_annual');
const isBolli = computed(() => props.expense?.calculation_type === 'sum_of_bolli');
const isIs = computed(() => props.expense?.is_imposta_sostitutiva ?? false);

type FormPayload = {
    name: string;
    rate: string;
    minimum: string;
    maximum: string;
    amount: string;
    previous_year_credit: string;
    effective_amount: string;
};

const str = (v: number | null): string => (v === null ? '' : String(v));

const form = useForm<FormPayload>({
    name: '',
    rate: '',
    minimum: '',
    maximum: '',
    amount: '',
    previous_year_credit: '',
    effective_amount: '',
});

watch(open, (isOpen) => {
    if (!isOpen || !props.expense) {
        return;
    }

    const e = props.expense;
    form.clearErrors();
    form.defaults({
        name: e.name,
        rate: str(e.rate),
        minimum: str(e.minimum),
        maximum: str(e.maximum),
        amount: str(e.amount),
        previous_year_credit: str(e.previous_year_credit),
        effective_amount: str(e.manual),
    });
    form.reset();
});

function submit(): void {
    if (!props.expense) {
        return;
    }

    // Invia solo i campi pertinenti al tipo (vuoto → null): il resto non viene
    // toccato a DB.
    const num = (v: string): number | null => (v.trim() === '' ? null : Number(v));

    form.transform(() => {
        const payload: Record<string, unknown> = { name: form.name.trim() };

        if (isPercentage.value) {
            payload.rate = num(form.rate);
            payload.minimum = num(form.minimum);
            payload.maximum = num(form.maximum);

            if (isIs.value) {
                payload.previous_year_credit = num(form.previous_year_credit);
            }
        } else if (isFixed.value) {
            payload.amount = num(form.amount);
        }

        // Override manuale: scavalca il calcolo, ammesso per ogni tipo. Vuoto →
        // null = torna al calcolato.
        payload.effective_amount = num(form.effective_amount);

        return payload;
    });

    form.patch(AnnualExpenseController.update.url({ annualExpense: props.expense.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Modifica non riuscita. Riprova.');
            }
        },
    });
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        mode="sheet"
        :title="expense?.name ?? 'Spesa'"
        :description="expense?.calculation_type_label"
        submit-form="expense-form"
        submit-label="Salva modifiche"
        :submitting="form.processing"
    >
        <div v-if="expense" class="flex flex-col gap-6">
            <!-- Derivazione del calcolo (lettura). -->
            <section>
                <h3 class="kicker mb-2 text-muted-foreground">Calcolo</h3>
                <FormulaBlock :formula="expense.formula" />
            </section>

            <form id="expense-form" class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- I bolli derivano dalle fatture: nessun parametro da correggere. -->
                <p v-if="isBolli" class="text-13 text-muted-foreground">
                    Importo calcolato dai bolli delle fatture dell'anno: non ci sono parametri da
                    modificare qui.
                </p>

                <FieldGroup v-else>
                    <FormField label="Nome" for="expense-name" required>
                        <Input id="expense-name" v-model="form.name" />
                        <template v-if="form.errors.name" #error>{{ form.errors.name }}</template>
                    </FormField>

                    <template v-if="isPercentage">
                        <FormField label="Aliquota (%)" for="expense-rate" required>
                            <DecimalInput
                                id="expense-rate"
                                v-model="form.rate"
                                :min="0"
                                :max="100"
                                class="tabular"
                            />
                            <template v-if="form.errors.rate" #error>{{ form.errors.rate }}</template>
                        </FormField>

                        <FormField label="Minimale" for="expense-minimum">
                            <DecimalInput
                                id="expense-minimum"
                                v-model="form.minimum"
                                :min="0"
                                placeholder="—"
                                class="tabular"
                            />
                            <template v-if="form.errors.minimum" #error>{{ form.errors.minimum }}</template>
                        </FormField>

                        <FormField label="Massimale" for="expense-maximum">
                            <DecimalInput
                                id="expense-maximum"
                                v-model="form.maximum"
                                :min="0"
                                placeholder="—"
                                class="tabular"
                            />
                            <template v-if="form.errors.maximum" #error>{{ form.errors.maximum }}</template>
                        </FormField>

                        <FormField
                            v-if="isIs"
                            label="Credito anno precedente"
                            for="expense-credit"
                        >
                            <DecimalInput
                                id="expense-credit"
                                v-model="form.previous_year_credit"
                                :min="0"
                                placeholder="—"
                                class="tabular"
                            />
                            <template v-if="form.errors.previous_year_credit" #error>{{
                                form.errors.previous_year_credit
                            }}</template>
                        </FormField>
                    </template>

                    <FormField v-else-if="isFixed" label="Importo annuale" for="expense-amount" required>
                        <DecimalInput
                            id="expense-amount"
                            v-model="form.amount"
                            :min="0"
                            class="tabular"
                        />
                        <template v-if="form.errors.amount" #error>{{ form.errors.amount }}</template>
                    </FormField>
                </FieldGroup>

                <!-- Override manuale: scavalca il calcolo, per ogni tipo. -->
                <section>
                    <h3 class="kicker mb-2 text-muted-foreground">Forza importo</h3>
                    <FieldGroup>
                        <FormField
                            label="Importo manuale"
                            for="expense-effective"
                            :hint="`Lascia vuoto per usare il calcolato: ${formatEUR(expense.calculated)}.`"
                        >
                            <DecimalInput
                                id="expense-effective"
                                v-model="form.effective_amount"
                                :min="0"
                                placeholder="—"
                                class="tabular"
                            />
                            <template v-if="form.errors.effective_amount" #error>{{
                                form.errors.effective_amount
                            }}</template>
                        </FormField>
                    </FieldGroup>
                </section>
            </form>
        </div>

        <!-- Elimina: solo una-tantum senza pagamenti. -->
        <template v-if="canDelete" #footer>
            <Button type="button" variant="destructive" size="sm" class="w-full" @click="deleteOpen = true">
                Elimina spesa
            </Button>
        </template>
    </ResponsiveDialog>

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Eliminare la spesa?"
        :description="
            expense
                ? `«${expense.name}» verrà rimossa da quest'anno. Puoi ricrearla quando vuoi.`
                : undefined
        "
        confirm-label="Elimina"
        destructive
        @confirm="runDelete"
    />
</template>
