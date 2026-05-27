<script setup lang="ts">
/**
 * ChoiceCardRadio — radio card cliccabile (singolo elemento).
 *
 * Pattern shadcn-vue ufficiale: FieldLabel > Field horizontal > FieldContent + RadioGroupItem.
 * Il FieldLabel applica auto bordi/radius/padding e il bg-sage quando data-state=checked
 * (vedi FieldLabel primitive: classi has-[>[data-slot=field]]: e has-data-[state=checked]:).
 *
 * Va dentro un <RadioGroup>:
 *
 *   <RadioGroup v-model="tipo">
 *     <ChoiceCardRadio
 *       v-for="opt in opts"
 *       :value="opt.value"
 *       :title="opt.title"
 *       :description="opt.description"
 *     />
 *   </RadioGroup>
 */
import type { HTMLAttributes } from 'vue'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/field'
import { RadioGroupItem } from '@/components/ui/radio-group'

defineProps<{
    /** Valore radio (chiave nel v-model del RadioGroup parent). */
    value: string
    /** Titolo della card. */
    title: string
    /** Descrizione sotto al titolo (opzionale). */
    description?: string
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <FieldLabel :class="$props.class">
        <Field orientation="horizontal">
            <FieldContent>
                <FieldTitle>{{ title }}</FieldTitle>
                <FieldDescription v-if="description">{{ description }}</FieldDescription>
            </FieldContent>
            <RadioGroupItem :value="value" />
        </Field>
    </FieldLabel>
</template>
