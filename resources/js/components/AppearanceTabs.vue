<script setup lang="ts">
import { PhDesktop, PhMoon, PhSun } from '@phosphor-icons/vue';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', icon: PhSun, label: 'Chiaro' },
    { value: 'dark', icon: PhMoon, label: 'Scuro' },
    { value: 'system', icon: PhDesktop, label: 'Sistema' },
] as const;
</script>

<template>
    <Tabs
        :model-value="appearance"
        @update:model-value="(v) => updateAppearance(v as Appearance)"
    >
        <TabsList>
            <TabsTrigger v-for="t in tabs" :key="t.value" :value="t.value">
                <component :is="t.icon" class="size-4" />
                {{ t.label }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
