<script setup lang="ts">
/**
 * Barra impilata + legenda per una composizione di importi (es. spese del mese
 * per famiglia): un segmento per voce, larghezza = quota sul totale, colore dato
 * dal chiamante. Interattiva come il grafico mensile: hover → valore (tooltip),
 * click → fissa la voce evidenziata (le altre sbiadiscono), cross-highlight barra
 * ↔ legenda. Root con `justify-between`: testata opzionale (slot `header`) + barra
 * in alto, legenda in basso quando il box viene allungato (flex-1 dal chiamante).
 */
import { computed, ref } from 'vue';
import { formatEUR } from '@/lib/format';

type StackItem = {
    id: string | number;
    label: string;
    amount: number;
    color: string;
};

const props = withDefaults(
    defineProps<{ items: StackItem[]; emptyLabel?: string }>(),
    { emptyLabel: 'Nessuna spesa nel mese.' },
);

const total = computed(() =>
    props.items.reduce((sum, item) => sum + item.amount, 0),
);

const segments = computed(() =>
    props.items.map((item) => ({
        ...item,
        pct: total.value > 0 ? (item.amount / total.value) * 100 : 0,
    })),
);

// Click fissa la voce, hover la mostra al volo (l'hover prevale sul click).
type ItemId = string | number;
const selected = ref<ItemId | null>(null);
const hovered = ref<ItemId | null>(null);
const active = computed<ItemId | null>(() => hovered.value ?? selected.value);

function toggle(id: ItemId): void {
    selected.value = selected.value === id ? null : id;
}
</script>

<template>
    <div
        class="flex flex-col justify-between gap-4 rounded-lg border border-border bg-card p-4"
    >
        <div class="flex flex-col gap-3">
            <slot name="header" />
            <div class="flex h-3.5 overflow-hidden rounded-sm bg-muted">
                <div
                    v-for="segment in segments"
                    :key="segment.id"
                    class="cursor-pointer border-r border-card transition-opacity last:border-r-0"
                    :class="
                        active !== null && active !== segment.id
                            ? 'opacity-25'
                            : ''
                    "
                    :style="{
                        width: `${segment.pct}%`,
                        background: segment.color,
                    }"
                    :title="`${segment.label}: ${formatEUR(segment.amount)} · ${segment.pct.toFixed(0)}%`"
                    @click="toggle(segment.id)"
                    @mouseenter="hovered = segment.id"
                    @mouseleave="hovered = null"
                />
            </div>
        </div>
        <div class="flex flex-col gap-0.5">
            <p
                v-if="!segments.length"
                class="px-1.5 py-1 text-13 text-muted-foreground"
            >
                {{ emptyLabel }}
            </p>
            <button
                v-for="segment in segments"
                :key="segment.id"
                type="button"
                class="-mx-1.5 flex items-baseline justify-between gap-3 rounded px-1.5 py-1 text-left transition-colors hover:bg-muted/60"
                :class="active === segment.id ? 'bg-muted' : ''"
                @click="toggle(segment.id)"
                @mouseenter="hovered = segment.id"
                @mouseleave="hovered = null"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <i
                        class="size-2 shrink-0 rounded-[2px]"
                        :style="{ background: segment.color }"
                    />
                    <span class="truncate text-13 text-muted-foreground">{{
                        segment.label
                    }}</span>
                </span>
                <span
                    class="tabular shrink-0 text-13 font-medium text-foreground"
                    >{{ formatEUR(segment.amount) }}</span
                >
            </button>
        </div>
    </div>
</template>
