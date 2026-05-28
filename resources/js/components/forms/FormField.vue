<script setup lang="ts">
/**
 * FormField — riga di form (Field con label + slot input + hint/error opzionali).
 *
 * Wrapper minimo sopra Field shadcn. Le prop `required` e `invalid` si
 * propagano al Field context (asterisco automatico, aria-invalid).
 *
 *   <FormField label="Email" required hint="Verrà usata per le notifiche.">
 *     <Input v-model="form.email" type="email" />
 *   </FormField>
 *
 *   <FormField label="Nome" invalid>
 *     <Input v-model="form.name" />
 *     <template #error>Campo obbligatorio</template>
 *   </FormField>
 */
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';

defineProps<{
    /** Label visibile sopra l'input. Opzionale per casi inline (search topbar). */
    label?: string;
    required?: boolean;
    invalid?: boolean;
    hint?: string;
    /** Lega `<label for>` all'input. Passare lo stesso valore dell'`id`
     * sull'input figlio per a11y completa (click label → focus input). */
    for?: string;
}>();
</script>

<template>
    <Field :required="required" :invalid="invalid">
        <FieldLabel v-if="label" :for="$props.for">{{ label }}</FieldLabel>
        <slot />
        <FieldDescription v-if="hint">
            <slot name="hint">{{ hint }}</slot>
        </FieldDescription>
        <FieldError v-if="$slots.error">
            <slot name="error" />
        </FieldError>
    </Field>
</template>
