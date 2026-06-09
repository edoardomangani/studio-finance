<script setup lang="ts">
/**
 * Dialog di propagazione profilo (F11). Si apre dopo il salvataggio del profilo
 * quando coefficiente e/o anno di inizio cambiano e ci sono anni esistenti.
 * Checklist di anni (tutti deselezionati di default); conferma → propaga ai soli
 * anni scelti (coefficiente sull'Anno, anno-inizio sull'aliquota IS).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import ProfessionalController from '@/actions/App/Http/Controllers/Settings/ProfessionalController';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { formatPercent } from '@/lib/format';

type YearOption = {
    id: number;
    year: number;
    profitability_coefficient: number;
};

const props = defineProps<{
    open: boolean;
    years: YearOption[];
    propagateCoefficient: boolean;
    propagateStartYear: boolean;
}>();

const emit = defineEmits<{ 'update:open': [boolean] }>();

const form = useForm<{
    year_ids: number[];
    coefficient: boolean;
    start_year: boolean;
}>({
    year_ids: [],
    coefficient: false,
    start_year: false,
});

const description = computed<string>(() => {
    if (props.propagateCoefficient && props.propagateStartYear) {
        return 'Hai modificato coefficiente e anno di inizio attività. Scegli a quali anni applicarli.';
    }
    if (props.propagateCoefficient) {
        return 'Hai modificato il coefficiente di redditività. Scegli a quali anni applicarlo.';
    }

    return "Hai modificato l'anno di inizio attività (aliquota imposta sostitutiva). Scegli a quali anni applicarlo.";
});

const allSelected = computed<boolean>(
    () => props.years.length > 0 && form.year_ids.length === props.years.length,
);

function toggleYear(id: number, checked: boolean | 'indeterminate'): void {
    form.year_ids =
        checked === true
            ? [...form.year_ids, id]
            : form.year_ids.filter((x) => x !== id);
}

function toggleAll(): void {
    form.year_ids = allSelected.value ? [] : props.years.map((y) => y.id);
}

function submit(): void {
    form.coefficient = props.propagateCoefficient;
    form.start_year = props.propagateStartYear;

    form.post(ProfessionalController.propagate.url(), {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
        onError: (errors) => {
            // Gli errori di campo (year_ids) restano inline; per il resto un avviso.
            if (!errors.year_ids) {
                toast.error('Propagazione non riuscita. Riprova.');
            }
        },
    });
}

// Reset alla chiusura: la prossima apertura riparte con la checklist vuota.
watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.reset();
            form.clearErrors();
        }
    },
);
</script>

<template>
    <ResponsiveDialog
        :open="open"
        title="Propaga agli anni esistenti"
        :description="description"
        submit-form="propagate-form"
        submit-label="Propaga"
        :submitting="form.processing"
        @update:open="emit('update:open', $event)"
    >
        <form id="propagate-form" @submit.prevent="submit">
            <FieldGroup>
                <div class="flex items-center justify-between">
                    <span class="kicker text-muted-foreground">Anni</span>
                    <Button
                        type="button"
                        variant="link"
                        size="sm"
                        class="h-auto p-0"
                        @click="toggleAll"
                    >
                        {{ allSelected ? 'Deseleziona tutti' : 'Seleziona tutti' }}
                    </Button>
                </div>

                <Field
                    v-for="y in years"
                    :key="y.id"
                    orientation="horizontal"
                    class="justify-between"
                >
                    <div class="flex items-center gap-2.5">
                        <Checkbox
                            :id="`propagate-year-${y.id}`"
                            :model-value="form.year_ids.includes(y.id)"
                            @update:model-value="
                                (v) => toggleYear(y.id, v)
                            "
                        />
                        <FieldLabel
                            :for="`propagate-year-${y.id}`"
                            class="tabular font-medium"
                        >
                            {{ y.year }}
                        </FieldLabel>
                    </div>
                    <span class="tabular text-13 text-muted-foreground">
                        Coeff. {{ formatPercent(y.profitability_coefficient) }}
                    </span>
                </Field>

                <FieldError v-if="form.errors.year_ids">
                    {{ form.errors.year_ids }}
                </FieldError>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
