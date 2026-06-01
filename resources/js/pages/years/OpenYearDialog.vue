<script setup lang="ts">
/**
 * Years — Open wizard (dialog, F6).
 *
 * Stepper lineare a 3 step dentro un Dialog `wide`. Il body ha altezza
 * cappata con scroll interno (header/footer fissi), così lo step 3 con molte
 * scadenze non fa crescere il dialog oltre lo schermo:
 *   1. Anno          — scelta dell'anno, coefficiente dal profilo.
 *   2. Voci di spesa — tabella editabile delle voci ereditate dai template.
 *   3. Scadenze      — anteprima con date editabili + alert cross-year.
 *
 * Il piano è caricato in modo asincrono (GET JSON `years.plan`) all'apertura
 * e a ogni cambio anno: una visita Inertia chiuderebbe il dialog. Lo stato di
 * editing vive in un `useForm`; gli step 2-3 sono componenti che mutano le
 * sue collezioni reattive. Al submit Inertia segue il redirect a `years.show`.
 */
import { useForm } from '@inertiajs/vue3';
import { PhArrowLeft, PhArrowRight, PhCheck } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogFooter,
    DialogStandardHeader,
    WizardStepper,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { clampNumber } from '@/lib/clampNumber';
import WizardDeadlinesStep from '@/pages/years/WizardDeadlinesStep.vue';
import WizardExpensesStep from '@/pages/years/WizardExpensesStep.vue';
import { plan as yearsPlan, store as yearsStore } from '@/routes/years';
import type { YearPlan, YearWizardExpense } from '@/types';

type WizardForm = {
    // Anno: number (stepper NumberField, intero bounded). Coefficiente:
    // number | string (input grezzo, vuoto = '').
    year: number;
    profitability_coefficient: number | string;
    note: string;
    cross_year_confirmed: boolean;
    expenses: YearWizardExpense[];
    deadlines: YearPlan['deadlines'];
};

const props = defineProps<{
    suggestedYear: number;
}>();

const open = defineModel<boolean>('open', { required: true });

const TOTAL_STEPS = 3;
const STEP_TITLES = ['Anno', 'Voci di spesa', 'Scadenze'] as const;

const plan = ref<YearPlan | null>(null);
const loading = ref(false);
const loadedYear = ref<number | null>(null);
const step = ref(1);

const form = useForm<WizardForm>({
    year: props.suggestedYear,
    profitability_coefficient: 0,
    note: '',
    cross_year_confirmed: false,
    expenses: [],
    deadlines: [],
});

const needsCrossYearConfirm = computed(() => plan.value?.next_year_needs_preopen ?? false);
const yearChanged = computed(() => form.year !== loadedYear.value);
const canOpen = computed(() => !needsCrossYearConfirm.value || form.cross_year_confirmed);

function hydrate(loaded: YearPlan): void {
    form.year = loaded.year;
    form.profitability_coefficient = loaded.profitability_coefficient;
    form.note = loaded.note ?? '';
    form.cross_year_confirmed = false;
    form.expenses = loaded.expenses.map((e) => ({
        expense_item_id: e.expense_item_id,
        name: e.name,
        calculation_type: e.calculation_type,
        rate: e.rate ?? '',
        minimum: e.minimum ?? '',
        maximum: e.maximum ?? '',
        amount: e.amount ?? '',
        included: true,
    }));
    form.deadlines = loaded.deadlines.map((d) => ({ ...d }));
    form.clearErrors();
}

async function fetchPlan(year: number): Promise<boolean> {
    loading.value = true;

    try {
        const response = await fetch(yearsPlan({ query: { year } }).url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const loaded = (await response.json()) as YearPlan;
        plan.value = loaded;
        loadedYear.value = loaded.year;
        hydrate(loaded);

        return true;
    } catch {
        toast.error('Impossibile caricare il piano dell\'anno. Riprova.');

        return false;
    } finally {
        loading.value = false;
    }
}

// All'apertura: parti dallo step 1 e carica il piano dell'anno suggerito.
// Alla chiusura: resetta così la riapertura riparte pulita.
watch(open, (isOpen) => {
    if (isOpen) {
        step.value = 1;
        form.year = props.suggestedYear;
        void fetchPlan(props.suggestedYear);
    } else {
        plan.value = null;
        loadedYear.value = null;
    }
});

// Errore di unicità anno (store) → torna allo step 1 per mostrarlo in contesto.
watch(
    () => form.errors.year,
    (error) => {
        if (error) {
            step.value = 1;
        }
    },
);

async function next(): Promise<void> {
    if (step.value === 1) {
        if (yearChanged.value) {
            const ok = await fetchPlan(form.year);

            if (!ok) {
                return;
            }
        }

        step.value = 2;

        return;
    }

    if (step.value === 2) {
        step.value = 3;
    }
}

function back(): void {
    if (step.value > 1) {
        step.value--;
    }
}

function submit(): void {
    form
        .transform((data) => ({ ...data, year: Number(data.year) }))
        .post(yearsStore().url, {
            preserveScroll: true,
            // Gli errori di validazione arrivano in form.errors (+ watcher su
            // errors.year → step 1); il toast copre gli errori non-validazione.
            onError: (errors) => {
                if (Object.keys(errors).length === 0) {
                    toast.error('Impossibile aprire l\'anno. Riprova.');
                }
            },
        });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent size="wide">
            <DialogStandardHeader
                title="Apri nuovo anno"
                :description="`Apri un nuovo anno. Step ${step} di ${TOTAL_STEPS} - ${STEP_TITLES[step - 1]}`"
            >
                <template #trailing>
                    <WizardStepper :current="step" :total="TOTAL_STEPS" />
                </template>
            </DialogStandardHeader>

            <DialogBody class="max-h-[60vh] overflow-auto" :aria-busy="loading">
                <!-- Skeleton durante il caricamento del piano. -->
                <div v-if="loading && !plan" class="flex flex-col gap-3">
                    <Skeleton class="h-9 w-48" />
                    <Skeleton class="h-64 w-full" />
                </div>

                <template v-else-if="plan">
                    <!-- Step 1 — Anno -->
                    <section v-show="step === 1" class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
                        <FormField
                            label="Anno da aprire"
                            for="wizard-year"
                            :invalid="!!form.errors.year"
                            :hint="yearChanged ? 'Cambiando anno il piano viene ricalcolato (date scadenze e cross-year).' : undefined"
                        >
                            <NumberField
                                id="wizard-year"
                                v-model="form.year"
                                :min="1990"
                                :max="2100"
                                :step="1"
                                :format-options="{ useGrouping: false, maximumFractionDigits: 0 }"
                            >
                                <NumberFieldContent>
                                    <NumberFieldDecrement />
                                    <NumberFieldInput class="tabular" />
                                    <NumberFieldIncrement />
                                </NumberFieldContent>
                            </NumberField>
                            <template v-if="form.errors.year" #error>{{ form.errors.year }}</template>
                        </FormField>

                        <FormField
                            label="Coefficiente di redditività (%)"
                            for="wizard-coeff"
                            :invalid="!!form.errors.profitability_coefficient"
                            hint="Precompilato dal profilo, modificabile per quest'anno."
                        >
                            <Input
                                id="wizard-coeff"
                                v-model="form.profitability_coefficient"
                                type="number"
                                inputmode="decimal"
                                step="0.01"
                                min="0"
                                max="100"
                                class="tabular"
                                @blur="form.profitability_coefficient = clampNumber(form.profitability_coefficient, 0, 100)"
                            />
                            <template v-if="form.errors.profitability_coefficient" #error>{{ form.errors.profitability_coefficient }}</template>
                        </FormField>
                    </section>

                    <!-- Step 2 — Voci di spesa -->
                    <WizardExpensesStep v-show="step === 2" v-model:expenses="form.expenses" />

                    <!-- Step 3 — Scadenze -->
                    <WizardDeadlinesStep
                        v-show="step === 3"
                        v-model:deadlines="form.deadlines"
                        v-model:cross-year-confirmed="form.cross_year_confirmed"
                        :cross-year-deadlines="plan.cross_year_deadlines"
                        :next-year="plan.next_year"
                        :needs-confirm="needsCrossYearConfirm"
                    />
                </template>
            </DialogBody>

            <!-- Footer su una riga sola anche su mobile (Indietro accanto al
                 primary), override del flex-col-reverse di default. -->
            <DialogFooter class="shrink-0 flex-row items-center justify-between border-t border-border px-6 py-4 sm:justify-between">
                <Button
                    v-if="step > 1"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="back"
                >
                    <PhArrowLeft :size="14" />
                    Indietro
                </Button>
                <span v-else />

                <Button
                    v-if="step < TOTAL_STEPS"
                    type="button"
                    size="sm"
                    :disabled="loading || !plan"
                    @click="next"
                >
                    <Spinner v-if="loading" />
                    {{ yearChanged && step === 1 ? 'Ricalcola piano' : 'Avanti' }}
                    <PhArrowRight v-if="!loading" :size="14" />
                </Button>
                <Button
                    v-else
                    type="button"
                    size="sm"
                    :disabled="!canOpen || form.processing"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" />
                    <PhCheck v-else :size="14" weight="bold" />
                    Apri anno {{ form.year }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
