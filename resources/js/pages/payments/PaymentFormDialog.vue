<script setup lang="ts">
/**
 * PaymentFormDialog — registrazione di un pagamento manuale extra-scadenza (F8).
 *
 * Form piatto (spesa + importo + data + descrizione): nessun calcolo live,
 * nessuna creazione annidata, un solo passo → modale, secondo il rasoio del
 * progetto ([[ResponsiveDialog]] mode="dialog"). Submit dal footer (desktop)
 * o dal check in header (mobile); alla conferma il controller fa back() e la
 * lista pagamenti si rinfresca.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import AnnualExpensePicker from '@/components/AnnualExpensePicker.vue';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { todayISO } from '@/lib/format';
import type { AnnualExpenseForPicker } from '@/types';

defineProps<{
    annualExpenses: AnnualExpenseForPicker[];
}>();

const open = defineModel<boolean>('open', { default: false });

type FormPayload = {
    annual_expense_id: number | null;
    description: string;
    amount: string;
    paid_at: string;
};

const emptyForm = (): FormPayload => ({
    annual_expense_id: null,
    description: '',
    amount: '',
    paid_at: '',
});

const form = useForm<FormPayload>(emptyForm());

// Reset a ogni apertura: la data del pagamento parte da oggi.
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({ ...emptyForm(), paid_at: todayISO() });
    form.reset();
});

function submit(): void {
    // Descrizione vuota → null (campo nullable lato server).
    form.transform((data) => ({
        ...data,
        description: data.description.trim() || null,
    }));

    form.post(PaymentController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Registrazione non riuscita. Riprova.');
            }
        },
    });
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        title="Nuovo pagamento"
        description="Registra un pagamento manuale, non legato a una scadenza. Verrà imputato alla spesa scelta."
        submit-form="payment-form"
        submit-label="Registra pagamento"
        :submitting="form.processing"
    >
        <form id="payment-form" @submit.prevent="submit">
            <FieldGroup>
                <FormField label="Spesa" for="payment-expense" required>
                    <AnnualExpensePicker
                        id="payment-expense"
                        v-model="form.annual_expense_id"
                        :annual-expenses="annualExpenses"
                        :invalid="!!form.errors.annual_expense_id"
                    />
                    <template v-if="form.errors.annual_expense_id" #error>{{
                        form.errors.annual_expense_id
                    }}</template>
                </FormField>

                <FormField label="Data del pagamento" for="payment-date" required>
                    <Input
                        id="payment-date"
                        v-model="form.paid_at"
                        type="date"
                        :max="todayISO()"
                    />
                    <template v-if="form.errors.paid_at" #error>{{ form.errors.paid_at }}</template>
                </FormField>

                <FormField label="Importo" for="payment-amount" required>
                    <Input
                        id="payment-amount"
                        v-model="form.amount"
                        inputmode="decimal"
                        placeholder="0.00"
                        class="tabular"
                    />
                    <template v-if="form.errors.amount" #error>{{ form.errors.amount }}</template>
                </FormField>

                <FormField label="Descrizione" for="payment-description">
                    <Input
                        id="payment-description"
                        v-model="form.description"
                        placeholder="Es. Saldo IRPEF 2025"
                    />
                    <template v-if="form.errors.description" #error>{{
                        form.errors.description
                    }}</template>
                </FormField>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
