<script setup lang="ts">
/**
 * Years — Open wizard (F6).
 *
 * Stepper lineare a 3 step su pagina dedicata (no modal):
 *   1. Anno          — scelta dell'anno, coefficiente dal profilo.
 *   2. Voci di spesa — tabella editabile delle voci ereditate dai template,
 *                      con toggle di esclusione per quest'anno.
 *   3. Scadenze      — anteprima con date editabili + alert cross-year se va
 *                      pre-aperto l'anno N+1.
 *
 * Lo stato vive in un unico `useForm`; gli step 2-3 sono componenti che
 * mutano direttamente le sue collezioni reattive. Cambiare l'anno allo step 1
 * ricarica il piano dal server (date e cross-year dipendono dall'anno e
 * dall'esistenza di N+1, che il client non conosce).
 */
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArrowLeft, PhArrowRight, PhCheck } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { WizardStepper } from '@/components/ui/dialog';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Spinner } from '@/components/ui/spinner';
import WizardDeadlinesStep from '@/pages/years/WizardDeadlinesStep.vue';
import WizardExpensesStep from '@/pages/years/WizardExpensesStep.vue';
import { index as yearsIndex, open as yearsOpen, store as yearsStore } from '@/routes/years';
import type { YearPlan, YearPlanExpense } from '@/types';

type WizardExpense = YearPlanExpense & { included: boolean };

type WizardForm = {
    year: number;
    profitability_coefficient: number;
    note: string;
    cross_year_confirmed: boolean;
    expenses: WizardExpense[];
    deadlines: YearPlan['deadlines'];
};

const props = defineProps<{
    plan: YearPlan;
}>();

setLayoutProps({
    pageTitle: 'Apri nuovo anno',
    pageCrumbs: [
        { label: 'Anni', href: yearsIndex().url },
        { label: 'Apri anno' },
    ],
    subbar: false,
});

function buildForm(plan: YearPlan): WizardForm {
    return {
        year: plan.year,
        profitability_coefficient: plan.profitability_coefficient,
        note: plan.note ?? '',
        cross_year_confirmed: false,
        expenses: plan.expenses.map((e) => ({ ...e, included: true })),
        deadlines: plan.deadlines.map((d) => ({ ...d })),
    };
}

const form = useForm<WizardForm>(buildForm(props.plan));

const step = ref(1);
const reloading = ref(false);
const TOTAL_STEPS = 3;

const stepTitles = ['Anno', 'Voci di spesa', 'Scadenze'] as const;

// Cross-year: serve la conferma esplicita prima di aprire se l'anno N+1 va
// pre-aperto (RB8/RB10).
const needsCrossYearConfirm = computed(() => props.plan.next_year_needs_preopen);

const yearChanged = computed(() => form.year !== props.plan.year);

// Quando il piano cambia (reload dopo cambio anno), risincronizza il form.
watch(
    () => props.plan.year,
    () => {
        const next = buildForm(props.plan);
        form.year = next.year;
        form.profitability_coefficient = next.profitability_coefficient;
        form.note = next.note;
        form.cross_year_confirmed = false;
        form.expenses = next.expenses;
        form.deadlines = next.deadlines;
        form.defaults(next);
        form.clearErrors();
    },
);

// Errore di unicità anno → torna allo step 1 per mostrarlo in contesto.
watch(
    () => form.errors.year,
    (error) => {
        if (error) {
            step.value = 1;
        }
    },
);

const canOpen = computed(
    () => !needsCrossYearConfirm.value || form.cross_year_confirmed,
);

function reloadPlanForYear(): void {
    if (reloading.value) {
        return;
    }

    reloading.value = true;
    router.get(
        yearsOpen({ query: { year: form.year } }).url,
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => {
                reloading.value = false;
            },
        },
    );
}

function next(): void {
    if (step.value === 1) {
        if (yearChanged.value) {
            reloadPlanForYear();

            return;
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
        .transform((data) => ({
            ...data,
            // Il backend ricalcola comunque, ma normalizziamo qui i tipi.
            year: Number(data.year),
        }))
        .post(yearsStore().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Apri nuovo anno" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child variant="outline" size="sm">
            <Link :href="yearsIndex().url">Annulla</Link>
        </Button>
    </Teleport>

    <div class="mx-auto flex w-full max-w-[820px] flex-col px-4 py-6 md:px-6">
        <header class="mb-6 flex items-center justify-between">
            <div>
                <p class="kicker text-muted-foreground">Step {{ step }} di {{ TOTAL_STEPS }}</p>
                <h1 class="text-lg font-medium text-foreground">{{ stepTitles[step - 1] }}</h1>
            </div>
            <WizardStepper :current="step" :total="TOTAL_STEPS" />
        </header>

        <!-- Step 1 — Anno -->
        <section v-show="step === 1" class="flex flex-col gap-5">
            <Field :invalid="!!form.errors.year">
                <FieldLabel for="wizard-year">Anno da aprire</FieldLabel>
                <NumberField
                    id="wizard-year"
                    v-model="form.year"
                    :min="1990"
                    :max="2100"
                    :format-options="{ useGrouping: false, maximumFractionDigits: 0 }"
                    class="max-w-[200px]"
                >
                    <NumberFieldContent>
                        <NumberFieldInput class="tabular" />
                    </NumberFieldContent>
                </NumberField>
                <FieldDescription v-if="yearChanged">
                    Cambiando anno il piano viene ricalcolato (date scadenze e cross-year).
                </FieldDescription>
                <FieldError v-if="form.errors.year">{{ form.errors.year }}</FieldError>
            </Field>

            <dl class="flex items-baseline gap-2 text-13 text-muted-foreground">
                <dt>Coefficiente di redditività</dt>
                <dd class="tabular text-foreground">
                    {{ form.profitability_coefficient.toLocaleString('it-IT', { maximumFractionDigits: 2 }) }}%
                </dd>
            </dl>
            <p class="text-13 text-muted-foreground">
                Copiato dal profilo all'apertura. Lo modifichi per anno dalla vista anno.
            </p>
        </section>

        <!-- Step 2 — Voci di spesa -->
        <WizardExpensesStep v-show="step === 2" :expenses="form.expenses" />

        <!-- Step 3 — Scadenze -->
        <WizardDeadlinesStep
            v-show="step === 3"
            v-model:cross-year-confirmed="form.cross_year_confirmed"
            :deadlines="form.deadlines"
            :cross-year-deadlines="props.plan.cross_year_deadlines"
            :next-year="props.plan.next_year"
            :needs-confirm="needsCrossYearConfirm"
        />

        <footer class="mt-8 flex items-center justify-between border-t border-border pt-4">
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
                :disabled="reloading"
                @click="next"
            >
                <Spinner v-if="reloading" />
                {{ yearChanged && step === 1 ? 'Ricalcola piano' : 'Avanti' }}
                <PhArrowRight v-if="!reloading" :size="14" />
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
        </footer>
    </div>
</template>
