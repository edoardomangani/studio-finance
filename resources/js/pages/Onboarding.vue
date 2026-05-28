<script setup lang="ts">
/**
 * Onboarding — pagina bloccante mostrata al primo login.
 * Form a step unico: nome, coefficiente redditività %, anno inizio attività.
 * Submit crea ProfessionalProfile (+ in Fase 2 lo studiofinance templates
 * seeder) e reindirizza al dashboard.
 *
 * Layout `null` (registrato in app.ts): nessun sidebar né topbar, l'utente
 * deve completare l'onboarding prima di entrare nella shell.
 */
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Spinner } from '@/components/ui/spinner';
import FormField from '@/components/forms/FormField.vue';
import { store as onboardingStore } from '@/routes/onboarding';

defineProps<{
    defaults: {
        nome: string;
        coefficiente_redditivita: number;
        anno_inizio_attivita: number;
    };
}>();


const form = useForm({
    nome: '',
    coefficiente_redditivita: 78,
    anno_inizio_attivita: new Date().getFullYear(),
});

function submit() {
    form.submit(onboardingStore());
}
</script>

<template>
    <Head title="Onboarding" />

    <div class="flex min-h-full items-center justify-center bg-background px-4 py-12">
        <div class="w-full max-w-md">
            <header class="mb-8">
                <span class="kicker">Studiofinance</span>
                <h1 class="mt-2 text-2xl font-medium tracking-tight text-foreground">
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
                        for="onb-nome"
                        :invalid="!!form.errors.nome"
                        hint="Come compari nei documenti e nei breadcrumb."
                    >
                        <Input
                            id="onb-nome"
                            v-model="form.nome"
                            :placeholder="defaults.nome"
                            autocomplete="name"
                            autofocus
                        />
                        <template v-if="form.errors.nome" #error>
                            {{ form.errors.nome }}
                        </template>
                    </FormField>

                    <FormField
                        label="Coefficiente di redditività (%)"
                        for="onb-coef"
                        :invalid="!!form.errors.coefficiente_redditivita"
                        hint="Per gli architetti iscritti a Inarcassa è 78%."
                    >
                        <NumberField
                            id="onb-coef"
                            v-model="form.coefficiente_redditivita"
                            :min="0"
                            :max="100"
                            :step="1"
                            :format-options="{
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2,
                            }"
                        >
                            <NumberFieldContent>
                                <NumberFieldDecrement />
                                <NumberFieldInput class="tabular" />
                                <NumberFieldIncrement />
                            </NumberFieldContent>
                        </NumberField>
                        <template v-if="form.errors.coefficiente_redditivita" #error>
                            {{ form.errors.coefficiente_redditivita }}
                        </template>
                    </FormField>

                    <FormField
                        label="Anno inizio attività"
                        for="onb-anno"
                        :invalid="!!form.errors.anno_inizio_attivita"
                        hint="Determina l'aliquota imposta sostitutiva: 5% per i primi cinque anni, 15% poi."
                    >
                        <NumberField
                            id="onb-anno"
                            v-model="form.anno_inizio_attivita"
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
                        <template v-if="form.errors.anno_inizio_attivita" #error>
                            {{ form.errors.anno_inizio_attivita }}
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
