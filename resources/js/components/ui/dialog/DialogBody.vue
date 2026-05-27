<script setup lang="ts">
/**
 * DialogBody — wrapper col padding canonico del DS (px-6 py-5).
 *
 * Allineato al chrome (header/footer px-6) — niente gerarchia inset, una
 * sola griglia uniforme. Stesso padding per tutti i size.
 */
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
  <div :class="cn('px-6 py-5', $props.class)">
    <slot />
  </div>
</template>
