<script setup lang="ts">
/**
 * DateField — input data responsive.
 *
 * - **Desktop (md+)**: shadcn pattern Popover + Calendar (vedi DesignSystem
 *   4.14). Click → popover, scelta inline, formato localizzato dd/mm/yyyy.
 * - **Mobile (<md)**: `<input type="date">` nativo. Le keyboard mobili
 *   offrono un wheel/picker già ottimizzato per touch; sovrascriverlo con
 *   un Popover overlay sarebbe peggio (target più piccoli, perdiamo lo
 *   scroll inerziale dei picker native).
 *
 * v-model accetta una stringa ISO `YYYY-MM-DD` (compatibile col formato
 * server Laravel `date` + Inertia useForm). Internamente converte da/per
 * `CalendarDate` quando serve al Calendar.
 *
 * Entrambi gli elementi vivono nel DOM (CSS toggle), no media-query JS:
 * evita layout shift su SSR/hydration di Inertia v3.
 */
import { CalendarDate, parseDate } from '@internationalized/date';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { formatDateIT } from '@/lib/format';

withDefaults(
    defineProps<{
        id?: string;
        placeholder?: string;
    }>(),
    { placeholder: 'Seleziona data…' },
);

const modelValue = defineModel<string>({ default: '' });

const popoverOpen = ref(false);

const calendarValue = computed<CalendarDate | undefined>(() => {
    if (!modelValue.value) {
        return undefined;
    }

    try {
        return parseDate(modelValue.value);
    } catch {
        return undefined;
    }
});

function onCalendarSelect(value: unknown): void {
    if (value instanceof CalendarDate) {
        modelValue.value = value.toString();
    }

    popoverOpen.value = false;
}

const displayLabel = computed(() =>
    modelValue.value ? formatDateIT(modelValue.value) : '',
);
</script>

<template>
    <!-- Mobile (<md): input nativo. Touch keyboard del SO gestisce il picker. -->
    <Input
        :id="id"
        v-model="modelValue"
        type="date"
        class="md:hidden"
    />

    <!-- Desktop (md+): Popover + Calendar shadcn. -->
    <Popover v-model:open="popoverOpen">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                variant="outline"
                type="button"
                class="hidden w-full justify-start font-normal md:flex"
            >
                <span :class="modelValue ? '' : 'text-muted-foreground/70'">
                    {{ displayLabel || placeholder }}
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <!-- `placeholder` = data su cui il calendar centra il focus
                 all'apertura. Se c'è già una selezione, parto da lì invece
                 di "oggi" (UX standard datepicker). -->
            <Calendar
                :model-value="calendarValue"
                :placeholder="calendarValue"
                weekday-format="short"
                locale="it-IT"
                @update:model-value="onCalendarSelect"
            />
        </PopoverContent>
    </Popover>
</template>
