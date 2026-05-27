<script setup lang="ts">
/**
 * ChoiceCardSwitch — switch card singolo (toggle on/off con title + description).
 *
 * Pattern shadcn-vue ufficiale: FieldLabel > Field horizontal > FieldContent + Switch.
 *
 *   <ChoiceCardSwitch
 *     v-model="emailEnabled"
 *     title="Notifiche email"
 *     description="Riepilogo eventi via email"
 *   />
 */
import type { HTMLAttributes } from 'vue'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/field'
import { Switch } from '@/components/ui/switch'

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
            <Switch
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', $event)"
            />
        </Field>
    </FieldLabel>
</template>
