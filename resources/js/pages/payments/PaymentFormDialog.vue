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
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import AnnualExpensePicker from '@/components/AnnualExpensePicker.vue';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { todayISO } from '@/lib/format';
import type { AnnualExpenseForPicker, PaymentListItem } from '@/types';

// `payment` valorizzato = modifica di un pagamento manuale; null = creazione.
const props = defineProps<{
    annualExpenses: AnnualExpenseForPicker[];
    payment?: PaymentListItem | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const isEditing = computed(() => props.payment != null);

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

// Precompila a ogni apertura: in modifica dai dati del pagamento, in creazione
// la data parte da oggi.
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults(
        props.payment
            ? {
                  annual_expense_id: props.payment.annual_expense_id,
                  description: props.payment.description ?? '',
                  amount: String(props.payment.amount),
                  paid_at: props.payment.paid_at ?? todayISO(),
              }
            : { ...emptyForm(), paid_at: todayISO() },
    );
    form.reset();
});

function submit(): void {
    // Descrizione vuota → null (campo nullable lato server).
    form.transform((data) => ({
        ...data,
        description: data.description.trim() || null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (errors: Record<string, string>) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Operazione non riuscita. Riprova.');
            }
        },
    };

    if (props.payment) {
        form.patch(
            PaymentController.update.url({ payment: props.payment.id }),
            options,
        );
    } else {
        form.post(PaymentController.store.url(), options);
    }
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        :title="isEditing ? 'Modifica pagamento' : 'Nuovo pagamento'"
        description="Cassa imputata a una spesa, senza una scadenza da tracciare. Per gli obblighi previsti usa le scadenze."
        submit-form="payment-form"
        :submit-label="isEditing ? 'Salva modifiche' : 'Registra pagamento'"
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

                <FormField
                    label="Data del pagamento"
                    for="payment-date"
                    required
                >
                    <Input
                        id="payment-date"
                        v-model="form.paid_at"
                        type="date"
                        :max="todayISO()"
                    />
                    <template v-if="form.errors.paid_at" #error>{{
                        form.errors.paid_at
                    }}</template>
                </FormField>

                <FormField label="Importo" for="payment-amount" required>
                    <DecimalInput
                        id="payment-amount"
                        v-model="form.amount"
                        :min="0"
                        placeholder="0,00"
                        class="tabular"
                    />
                    <template v-if="form.errors.amount" #error>{{
                        form.errors.amount
                    }}</template>
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
