<script setup lang="ts">
/**
 * Settings — pagina personale unica, stile StudioOS.
 *
 * Sezioni in stack (FormSection):
 * - Profilo professionale (solo se onboarded): nome (= user.name), email,
 *   coefficiente, anno inizio.
 * - Sicurezza: password + 2FA + Passkeys.
 * - Aspetto: tema light/dark/auto.
 * - Elimina account.
 *
 * Niente sub-sidebar interna. Le impostazioni "di sistema" (catalogo voci
 * di spesa template + scadenze tipo) vivranno altrove, sotto il gruppo
 * "Sistema" del sidebar principale, in Fase 2.
 *
 * Nome professionista è user.name (single source of truth). Email pure su
 * User. Entrambi vengono salvati dal ProfessionalController.update insieme
 * a coefficiente e anno inizio.
 *
 * I bottoni Salva / Annulla globali stanno in topbar via Teleport (StudioOS
 * pattern). Ogni form (professional, security) ha il proprio isDirty: il
 * submit globale chiama solo i form dirty.
 */
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfessionalController from '@/actions/App/Http/Controllers/Settings/ProfessionalController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import FormField from '@/components/forms/FormField.vue';
import FormSection from '@/components/forms/FormSection.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Kbd } from '@/components/ui/kbd';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import { Spinner } from '@/components/ui/spinner';
import { useShortcut } from '@/composables/useShortcut';
import { clampNumber } from '@/lib/clampNumber';
import { send } from '@/routes/verification';
import type { ProfessionalProfile } from '@/types';

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
    professionalProfile: ProfessionalProfile | null;
    canManageTwoFactor?: boolean;
    canManagePasskeys?: boolean;
    passkeys?: unknown[];
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// Coefficiente come `number | string` (input grezzo, vuoto = ''); anno come
// number (stepper NumberField: intero bounded, non un importo).
const professionalForm = useForm<{
    name: string;
    email: string;
    profitability_coefficient: number | string;
    business_start_year: number;
}>({
    name: user.value.name,
    email: user.value.email,
    profitability_coefficient: props.professionalProfile
        ? Number(props.professionalProfile.profitability_coefficient)
        : 78,
    business_start_year:
        props.professionalProfile?.business_start_year ??
        new Date().getFullYear(),
});

const securityForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const securityDirty = computed(
    () =>
        Boolean(securityForm.current_password) ||
        Boolean(securityForm.password) ||
        Boolean(securityForm.password_confirmation),
);

const isAnyDirty = computed(
    () =>
        (props.professionalProfile && professionalForm.isDirty) ||
        securityDirty.value,
);
const isAnyProcessing = computed(
    () => professionalForm.processing || securityForm.processing,
);

setLayoutProps({
    pageTitle: 'Impostazioni',
    subbar: false,
});

function onSave(): void {
    if (props.professionalProfile && professionalForm.isDirty) {
        professionalForm.patch(ProfessionalController.update.url(), {
            preserveScroll: true,
        });
    }

    if (securityDirty.value) {
        securityForm.put(SecurityController.update.url(), {
            preserveScroll: true,
            onSuccess: () => securityForm.reset(),
            onError: () =>
                securityForm.reset(
                    'password',
                    'password_confirmation',
                    'current_password',
                ),
        });
    }
}

function onReset(): void {
    professionalForm.clearErrors();
    professionalForm.reset();
    securityForm.clearErrors();
    securityForm.reset();
}

useShortcut(
    'Enter',
    () => {
        if (isAnyDirty.value && !isAnyProcessing.value) {
            onSave();
        }
    },
    { meta: true },
);
</script>

<template>
    <Head title="Impostazioni" />

    <!-- Topbar: Salva / Annulla -->
    <Teleport to="#page-topbar-actions" defer>
        <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="!isAnyDirty || isAnyProcessing"
            @click="onReset"
        >
            Annulla
        </Button>
        <Button
            type="button"
            size="sm"
            :disabled="!isAnyDirty || isAnyProcessing"
            @click="onSave"
        >
            <Spinner v-if="isAnyProcessing" />
            Salva modifiche
            <Kbd class="ml-1">⌘↵</Kbd>
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] px-4 py-6 md:px-6">
        <!-- Profilo professionale (solo se onboarded) -->
        <FormSection
            v-if="professionalProfile"
            id="professional"
            first
            title="Profilo professionale"
        >
            <FormField label="Nome" for="prof-name" required>
                <Input
                    id="prof-name"
                    v-model="professionalForm.name"
                    autocomplete="name"
                    placeholder="Es. Mario Rossi"
                />
                <template v-if="professionalForm.errors.name" #error>{{ professionalForm.errors.name }}</template>
            </FormField>

            <FormField
                label="Email"
                for="prof-email"
                required
                hint="Cambiando email dovrai riverificarla."
            >
                <Input
                    id="prof-email"
                    v-model="professionalForm.email"
                    type="email"
                    autocomplete="username"
                    placeholder="nome@studio.it"
                />
                <template v-if="professionalForm.errors.email" #error>{{ professionalForm.errors.email }}</template>
            </FormField>

            <div
                v-if="mustVerifyEmail && !user.email_verified_at"
                class="-mt-2 mb-4"
            >
                <p class="text-xs text-muted-foreground">
                    L'indirizzo email non è ancora verificato.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-foreground"
                    >
                        Reinvia l'email di verifica
                    </Link>
                    .
                </p>
                <p
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-medium text-accent-strong"
                >
                    Una nuova email di verifica è stata inviata.
                </p>
            </div>

            <FormField
                label="Coefficiente di redditività (%)"
                for="prof-coef"
                required
                hint="Per architetti iscritti a Inarcassa è 78%."
            >
                <Input
                    id="prof-coef"
                    v-model="professionalForm.profitability_coefficient"
                    type="number"
                    inputmode="decimal"
                    step="0.01"
                    min="0"
                    max="100"
                    class="tabular"
                    @blur="professionalForm.profitability_coefficient = clampNumber(professionalForm.profitability_coefficient, 0, 100)"
                />
                <template v-if="professionalForm.errors.profitability_coefficient" #error>{{ professionalForm.errors.profitability_coefficient }}</template>
            </FormField>

            <FormField
                label="Anno inizio attività"
                for="prof-anno"
                required
                last
                hint="Determina l'aliquota imposta sostitutiva: 5% per i primi cinque anni, 15% poi."
            >
                <NumberField
                    id="prof-anno"
                    v-model="professionalForm.business_start_year"
                    :min="1990"
                    :max="new Date().getFullYear()"
                    :step="1"
                    :format-options="{ useGrouping: false, maximumFractionDigits: 0 }"
                >
                    <NumberFieldContent>
                        <NumberFieldDecrement />
                        <NumberFieldInput class="tabular" />
                        <NumberFieldIncrement />
                    </NumberFieldContent>
                </NumberField>
                <template v-if="professionalForm.errors.business_start_year" #error>{{ professionalForm.errors.business_start_year }}</template>
            </FormField>
        </FormSection>

        <!-- Sicurezza: password + 2FA + Passkeys -->
        <FormSection
            id="security"
            :first="!professionalProfile"
            title="Sicurezza"
        >
            <FormField label="Password attuale" for="current_password">
                <PasswordInput
                    id="current_password"
                    v-model="securityForm.current_password"
                    autocomplete="current-password"
                />
                <template v-if="securityForm.errors.current_password" #error>{{ securityForm.errors.current_password }}</template>
            </FormField>

            <FormField label="Nuova password" for="password">
                <PasswordInput
                    id="password"
                    v-model="securityForm.password"
                    autocomplete="new-password"
                />
                <template v-if="securityForm.errors.password" #error>{{ securityForm.errors.password }}</template>
            </FormField>

            <FormField label="Conferma nuova password" for="password_confirmation" last>
                <PasswordInput
                    id="password_confirmation"
                    v-model="securityForm.password_confirmation"
                    autocomplete="new-password"
                />
                <template v-if="securityForm.errors.password_confirmation" #error>{{ securityForm.errors.password_confirmation }}</template>
            </FormField>

            <div class="mt-8 space-y-8">
                <ManageTwoFactor
                    :can-manage-two-factor="canManageTwoFactor"
                    :two-factor-enabled="twoFactorEnabled"
                    :requires-confirmation="requiresConfirmation"
                />
                <ManagePasskeys
                    :can-manage-passkeys="canManagePasskeys"
                    :passkeys="(passkeys as never[])"
                />
            </div>
        </FormSection>

        <!-- Aspetto -->
        <FormSection id="appearance" title="Aspetto">
            <AppearanceTabs />
        </FormSection>

        <!-- Elimina account -->
        <div class="mt-12">
            <DeleteUser />
        </div>
    </div>
</template>
