<script setup lang="ts">
/**
 * ColorDot — pallino colorato compatto, riusabile per indicatori di
 * categoria / tipologia (project type, activity type, status, ecc.).
 *
 * Supporta sia un valore hex diretto (`:color="#5C7A6E"`) sia una chiave
 * di palette risolta tramite mapping (`:color="cobalt" :palette="palette"`).
 *
 *   <ColorDot :color="t.color" :palette="palette" size="md" />
 */
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        /** Hex diretto o chiave swatch (es. "cobalt"). */
        color: string | null | undefined;
        /** Mappa swatch-name → hex (es. config('swatch_palette')). Se assente, `color` è usato direttamente. */
        palette?: Record<string, string>;
        /** Dimensione: sm=8px · md=10px (default) · lg=12px. */
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'md' },
);

const resolved = computed<string>(() => {
    if (!props.color) {
        return 'var(--c-line)';
    }

    if (props.palette && props.palette[props.color]) {
        return props.palette[props.color];
    }

    return props.color;
});

const sizeClass = computed(() => {
    if (props.size === 'sm') {
        return 'size-2';
    }

    if (props.size === 'lg') {
        return 'size-3';
    }

    return 'size-2.5';
});
</script>

<template>
    <span
        class="shrink-0 rounded-full"
        :class="sizeClass"
        :style="{ background: resolved }"
        :title="color ?? undefined"
        aria-hidden="true"
    />
</template>
