<script setup lang="ts">
import { computed } from 'vue';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const className = computed(() => props.class);
</script>

<template>
    <main
        class="relative flex h-full min-w-0 flex-1 flex-col overflow-hidden bg-background"
        :class="className"
    >
        <slot />
    </main>
</template>
