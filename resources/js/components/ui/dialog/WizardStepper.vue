<script setup lang="ts">
/**
 * WizardStepper — mini-pills indicator per Dialog wizard multi-step.
 *
 * Pattern minimal: una pill per step, attiva sage, inattive border.
 * Niente label né numeri; il titolo step vive nel sottotitolo del header
 * (es. "Step 1 di 4 · Dati progetto"). Funziona meglio per wizard con
 * step ≤ 6 e flusso lineare.
 *
 *   <DialogStandardHeader code="M.WIZ" title="Nuovo progetto" :description="...">
 *     <template #trailing>
 *       <WizardStepper :current="step" :total="4" />
 *     </template>
 *   </DialogStandardHeader>
 */
defineProps<{
    /** Step corrente (1-based). */
    current: number
    /** Numero totale di step. */
    total: number
}>()
</script>

<template>
    <div
        role="progressbar"
        :aria-valuenow="current"
        aria-valuemin="1"
        :aria-valuemax="total"
        :aria-label="`Step ${current} di ${total}`"
        class="flex items-center gap-1"
    >
        <span
            v-for="i in total"
            :key="i"
            class="h-1.5 w-3.5 rounded-full transition-colors"
            :class="i === current ? 'bg-accent-vivid w-7' : 'bg-border'"
        />
    </div>
</template>
