<script setup lang="ts">
/**
 * ChoicePillCheck — checkbox pill compatto (Badge cliccabile multi-select).
 *
 * v-model boolean. Per gruppi multi gestisci array nel parent.
 * Slot default per il label della pill.
 *
 *   <div class="flex flex-wrap gap-2">
 *     <ChoicePillCheck
 *       v-for="opt"
 *       :model-value="checks.includes(opt.value)"
 *       @update:model-value="toggleCheck(opt.value)"
 *     >
 *       {{ opt.label }}
 *     </ChoicePillCheck>
 *   </div>
 */
import type { HTMLAttributes } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'

defineProps<{
    modelValue?: boolean
    class?: HTMLAttributes['class']
}>()

defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()
</script>

<template>
    <Label
        :class="cn('relative inline-flex cursor-pointer', $props.class)"
    >
        <Checkbox
            :model-value="modelValue"
            class="peer sr-only"
            @update:model-value="$emit('update:modelValue', $event === true)"
        />
        <span
            class="inline-flex items-center rounded-full border border-input bg-card px-3 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-background peer-data-[state=checked]:border-accent-vivid peer-data-[state=checked]:bg-accent-vivid/10 peer-data-[state=checked]:text-accent-strong"
        >
            <slot />
        </span>
    </Label>
</template>
