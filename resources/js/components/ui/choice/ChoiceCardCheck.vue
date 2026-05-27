<script setup lang="ts">
/**
 * ChoiceCardCheck — checkbox card cliccabile (singolo elemento).
 *
 * Pattern shadcn-vue ufficiale: FieldLabel > Field horizontal > FieldContent + Checkbox.
 * Il FieldLabel applica auto bordi/radius/padding e il bg-sage quando checked.
 *
 *   <ChoiceCardCheck
 *     v-for="opt in opts"
 *     :model-value="checks.includes(opt.value)"
 *     :title="opt.title"
 *     :description="opt.description"
 *     @update:model-value="toggleCheck(opt.value)"
 *   />
 */
import type { HTMLAttributes } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/field'

defineProps<{
    modelValue?: boolean
    title: string
    description?: string
    class?: HTMLAttributes['class']
}>()

defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()
</script>

<template>
    <FieldLabel :class="$props.class">
        <Field orientation="horizontal">
            <FieldContent>
                <FieldTitle>{{ title }}</FieldTitle>
                <FieldDescription v-if="description">{{ description }}</FieldDescription>
            </FieldContent>
            <Checkbox
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', $event === true)"
            />
        </Field>
    </FieldLabel>
</template>
