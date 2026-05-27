<script setup lang="ts">
/**
 * ChoicePillRadio — radio pill compatto (Badge cliccabile).
 *
 * Va dentro un <RadioGroup>. Slot default per il label della pill.
 * Stile: tonale sage tonale quando checked, neutro quando inattiva.
 *
 *   <RadioGroup v-model="tipo" class="flex flex-wrap gap-2">
 *     <ChoicePillRadio v-for="opt" :value="opt.value">
 *       {{ opt.label }}
 *     </ChoicePillRadio>
 *   </RadioGroup>
 */
import type { HTMLAttributes } from 'vue'
import { Label } from '@/components/ui/label'
import { RadioGroupItem } from '@/components/ui/radio-group'
import { cn } from '@/lib/utils'

defineProps<{
    /** Valore radio (chiave nel v-model del RadioGroup parent). */
    value: string
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <Label
        :class="cn('relative inline-flex cursor-pointer', $props.class)"
    >
        <RadioGroupItem :value="value" class="peer sr-only" />
        <span
            class="inline-flex items-center rounded-full border border-input bg-card px-3 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-background peer-data-[state=checked]:border-accent-vivid peer-data-[state=checked]:bg-accent-vivid/10 peer-data-[state=checked]:text-accent-strong"
        >
            <slot />
        </span>
    </Label>
</template>
