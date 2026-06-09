<script setup lang="ts">
/**
 * Onboarding — pagina bloccante mostrata al primo login.
 * Form a step unico: nome, coefficiente redditività %, anno inizio attività.
 * Submit crea ProfessionalProfile + seeda expense items e recurring deadlines
 * via Action atomica, poi reindirizza al dashboard.
 *
 * Layout `null` (registrato in app.ts): niente sidebar né topbar.
 */
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Spinner } from '@/components/ui/spinner';
import { store as onboardingStore } from '@/routes/onboarding';

defineProps<{
    defaults: {
        name: string;
        profitability_coefficient: number;
        business_start_year: number;
    };
}>();

// Coefficiente come `number | string` (input grezzo, vuoto = ''); anno come
// number (stepper NumberField: intero bounded, non un importo).
const form = useForm<{
    name: string;
    profitability_coefficient: number | string;
    business_start_year: number;
}>({
    name: '',
    profitability_coefficient: 78,
    business_start_year: new Date().getFullYear(),
});

function submit() {
    form.submit(onboardingStore());
}
</script>

<template>
    <Head title="Onboarding" />

    <div
        class="flex min-h-full items-center justify-center bg-background px-4 py-12"
    >
        <div class="w-full max-w-md">
            <header class="mb-8">
                <span class="kicker">Studiofinance</span>
                <h1
                    class="mt-2 text-2xl font-medium tracking-tight text-foreground"
                >
                    Configura il tuo profilo
                </h1>
                <p class="mt-3 text-13 leading-relaxed text-muted-foreground">
                    Bastano tre dati per iniziare. Servono al sistema per
                    calcolare imposte e contributi nel modo corretto.
                </p>
            </header>

            <form @submit.prevent="submit">
                <FieldGroup>
                    <FormField
                        label="Nome professionista"
                        for="onb-name"
                        :invalid="!!form.errors.name"
                        hint="Come compari nei documenti e nei breadcrumb."
                    >
                        <Input
                            id="onb-name"
                            v-model="form.name"
                            :placeholder="defaults.name"
                            autocomplete="name"
                            autofocus
                        />
                        <template v-if="form.errors.name" #error>
                            {{ form.errors.name }}
                        </template>
                    </FormField>

                    <FormField
                        label="Coefficiente di redditività (%)"
                        for="onb-coef"
                        :invalid="!!form.errors.profitability_coefficient"
                        hint="Per gli architetti iscritti a Inarcassa è 78%."
                    >
                        <DecimalInput
                            id="onb-coef"
                            v-model="form.profitability_coefficient"
                            :min="0"
                            :max="100"
                            class="tabular"
                        />
                        <template
                            v-if="form.errors.profitability_coefficient"
                            #error
                        >
                            {{ form.errors.profitability_coefficient }}
                        </template>
                    </FormField>

                    <FormField
                        label="Anno inizio attività"
                        for="onb-year"
                        :invalid="!!form.errors.business_start_year"
                        hint="Determina l'aliquota imposta sostitutiva: 5% per i primi cinque anni, 15% poi."
                    >
                        <NumberField
                            id="onb-year"
                            v-model="form.business_start_year"
                            :min="1990"
                            :max="new Date().getFullYear()"
                            :step="1"
                            :format-options="{
                                useGrouping: false,
                                maximumFractionDigits: 0,
                            }"
                        >
                            <NumberFieldContent>
                                <NumberFieldDecrement />
                                <NumberFieldInput class="tabular" />
                                <NumberFieldIncrement />
                            </NumberFieldContent>
                        </NumberField>
                        <template v-if="form.errors.business_start_year" #error>
                            {{ form.errors.business_start_year }}
                        </template>
                    </FormField>
                </FieldGroup>

                <Button
                    type="submit"
                    class="mt-8 w-full"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    Continua
                </Button>
            </form>
        </div>
    </div>
</template>
