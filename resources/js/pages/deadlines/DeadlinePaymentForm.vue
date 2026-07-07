<script setup lang="ts">
/**
 * DeadlinePaymentForm — form di registrazione pagamento (F7) di una scadenza
 * aperta, estratto da [[DeadlineSheet]]. Rende il solo `<form
 * id="register-payment">`: l'azione primaria (check in header su mobile,
 * bottone nel footer su desktop) vive nello sheet e lo invia via
 * `form="register-payment"`. Espone `processing` per lo stato dei bottoni.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import DateField from '@/components/forms/DateField.vue';
import FormField from '@/components/forms/FormField.vue';
import { FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { formatEUR, todayISO } from '@/lib/format';
import { payment as registerPaymentRoute } from '@/routes/deadlines';
import type { DeadlineListItem } from '@/types';

const props = defineProps<{ deadline: DeadlineListItem }>();
const open = defineModel<boolean>('open', { default: false });

const form = useForm<{ description: string; amount: string; paid_at: string }>({
    description: '',
    amount: '',
    paid_at: '',
});

const expectedHint = computed(() =>
    props.deadline.expected_amount !== null
        ? `Previsto ${formatEUR(props.deadline.expected_amount)}`
        : 'Nessun previsto: inserisci l’importo dall’F24.',
);

const canRestoreExpected = computed(
    () =>
        props.deadline.expected_amount != null &&
        form.amount !== String(props.deadline.expected_amount),
);

// Precompila il form ad ogni apertura (immediate: al primo mount lo sheet è già
// aperto, quindi il watch da solo non scatterebbe).
watch(
    open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        form.clearErrors();
        form.defaults({
            description: props.deadline.name,
            amount:
                props.deadline.expected_amount !== null
                    ? String(props.deadline.expected_amount)
                    : '',
            paid_at: todayISO(),
        });
        form.reset();
    },
    { immediate: true },
);

function restoreExpected(): void {
    if (props.deadline.expected_amount != null) {
        form.amount = String(props.deadline.expected_amount);
    }
}

function submit(): void {
    form.post(registerPaymentRoute({ deadline: props.deadline.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        // Gli errori di validazione vanno inline nei FormField; il toast copre
        // i fallimenti non-di-validazione.
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Registrazione non riuscita. Riprova.');
            }
        },
    });
}

defineExpose({ processing: computed(() => form.processing) });
</script>

<template>
    <form id="register-payment" class="pt-4" @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Descrizione" for="payment-description">
                <Input id="payment-description" v-model="form.description" />
                <template v-if="form.errors.description" #error>{{
                    form.errors.description
                }}</template>
            </FormField>

            <FormField label="Data del pagamento" for="payment-date">
                <DateField
                    id="payment-date"
                    v-model="form.paid_at"
                    :max="todayISO()"
                />
                <template v-if="form.errors.paid_at" #error>{{
                    form.errors.paid_at
                }}</template>
            </FormField>

            <FormField label="Importo" for="payment-amount">
                <DecimalInput
                    id="payment-amount"
                    v-model="form.amount"
                    :min="0"
                    placeholder="0,00"
                    class="tabular"
                />
                <template #hint>
                    <span class="flex items-center justify-between gap-2">
                        <span>{{ expectedHint }}</span>
                        <button
                            v-if="canRestoreExpected"
                            type="button"
                            class="text-accent-vivid hover:underline"
                            @click="restoreExpected"
                        >
                            Ripristina previsto
                        </button>
                    </span>
                </template>
                <template v-if="form.errors.amount" #error>{{
                    form.errors.amount
                }}</template>
            </FormField>
        </FieldGroup>
    </form>
</template>
