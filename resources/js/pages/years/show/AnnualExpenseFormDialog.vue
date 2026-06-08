<script setup lang="ts">
/**
 * AnnualExpenseFormDialog — crea una spesa una-tantum per l'anno corrente: un
 * costo specifico, non ricorrente, a importo fisso. Per le voci ricorrenti si
 * usano i template in Impostazioni (così i prossimi anni le ereditano). Form
 * piatto (nome + importo) → dialog, secondo il rasoio del progetto.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import AnnualExpenseController from '@/actions/App/Http/Controllers/AnnualExpenseController';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

const props = defineProps<{ yearId: number }>();
const open = defineModel<boolean>('open', { default: false });

const form = useForm<{ year_id: number; name: string; amount: string }>({
    year_id: props.yearId,
    name: '',
    amount: '',
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({ year_id: props.yearId, name: '', amount: '' });
    form.reset();
});

function submit(): void {
    form.post(AnnualExpenseController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Creazione non riuscita. Riprova.');
            }
        },
    });
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        title="Nuova spesa dell'anno"
        description="Un costo specifico di quest'anno, a importo fisso. Per una voce ricorrente aggiungila ai template in Impostazioni."
        submit-form="annual-expense-form"
        submit-label="Aggiungi spesa"
        :submitting="form.processing"
    >
        <form id="annual-expense-form" @submit.prevent="submit">
            <FieldGroup>
                <FormField label="Nome" for="annual-expense-name" required>
                    <Input id="annual-expense-name" v-model="form.name" placeholder="Es. Contributo straordinario" />
                    <template v-if="form.errors.name" #error>{{ form.errors.name }}</template>
                </FormField>

                <FormField label="Importo annuale" for="annual-expense-amount" required>
                    <Input
                        id="annual-expense-amount"
                        v-model="form.amount"
                        inputmode="decimal"
                        placeholder="0.00"
                        class="tabular"
                    />
                    <template v-if="form.errors.amount" #error>{{ form.errors.amount }}</template>
                </FormField>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
