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
import { DecimalInput, Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EXPENSE_KIND_META } from '@/lib/expenseKind';
import type { ExpenseKind } from '@/types';

const props = defineProps<{ yearId: number; families: Record<ExpenseKind, string> }>();
const open = defineModel<boolean>('open', { default: false });

// La una-tantum è un importo fisso: famiglia tra costi fissi/previdenza/bolli
// (non Imposte: l'imposta sostitutiva è unica e arriva dai template). Etichetta
// = nome (rinominabile) della famiglia dell'utente, fallback al default del tipo.
const FAMILY_OPTIONS: ExpenseKind[] = ['fixed', 'pension', 'stamp_duty'];

function familyLabel(kind: ExpenseKind): string {
    return props.families[kind] ?? EXPENSE_KIND_META[kind].label;
}

const form = useForm<{ year_id: number; name: string; amount: string; kind: ExpenseKind }>({
    year_id: props.yearId,
    name: '',
    amount: '',
    kind: 'fixed',
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({ year_id: props.yearId, name: '', amount: '', kind: 'fixed' });
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
                    <DecimalInput
                        id="annual-expense-amount"
                        v-model="form.amount"
                        :min="0"
                        placeholder="0,00"
                        class="tabular"
                    />
                    <template v-if="form.errors.amount" #error>{{ form.errors.amount }}</template>
                </FormField>

                <FormField label="Famiglia" for="annual-expense-kind" required>
                    <Select v-model="form.kind">
                        <SelectTrigger id="annual-expense-kind" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="k in FAMILY_OPTIONS" :key="k" :value="k">
                                {{ familyLabel(k) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.kind" #error>{{ form.errors.kind }}</template>
                </FormField>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
