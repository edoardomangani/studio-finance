<script setup lang="ts">
/**
 * DateField — input data responsive.
 *
 * - **Desktop (md+)**: input di testo digitabile in formato `dd/mm/yyyy` +
 *   icona calendario che apre un Popover con il Calendar shadcn (vedi
 *   DesignSystem 4.14). Si può scrivere a mano o scegliere col mouse.
 * - **Mobile (<md)**: `<input type="date">` nativo. Le keyboard mobili
 *   offrono un wheel/picker già ottimizzato per touch; sovrascriverlo con
 *   un Popover overlay sarebbe peggio (target più piccoli, perdiamo lo
 *   scroll inerziale dei picker native).
 *
 * v-model accetta una stringa ISO `YYYY-MM-DD` (compatibile col formato
 * server Laravel `date` + Inertia useForm). Internamente converte da/per
 * `CalendarDate` quando serve al Calendar e da/per `dd/mm/yyyy` per
 * l'input di testo desktop.
 *
 * Entrambi gli elementi vivono nel DOM (CSS toggle), no media-query JS:
 * evita layout shift su SSR/hydration di Inertia v3.
 */
import { CalendarDate, parseDate } from '@internationalized/date';
import { PhCalendarBlank } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatDateIT } from '@/lib/format';

withDefaults(
    defineProps<{
        id?: string;
        placeholder?: string;
    }>(),
    { placeholder: 'gg/mm/aaaa' },
);

const modelValue = defineModel<string>({ default: '' });

const popoverOpen = ref(false);

// Testo grezzo dd/mm/yyyy mostrato nell'input desktop.
const textValue = ref(modelValue.value ? formatDateIT(modelValue.value) : '');

// Sync esterno → input (selezione dal Calendar, reset del form, ecc.).
watch(modelValue, (iso) => {
    const formatted = iso ? formatDateIT(iso) : '';
    if (formatted !== textValue.value) {
        textValue.value = formatted;
    }
});

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

/** Converte `dd/mm/yyyy` → ISO `YYYY-MM-DD`, rifiutando date inesistenti
 *  (es. 31/02/2026): costruisco il CalendarDate e ricontrollo i campi, così
 *  l'eventuale clamp/overflow viene scartato. */
function parseITDate(input: string): string | null {
    const match = input.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

    if (!match) {
        return null;
    }

    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);

    try {
        const date = new CalendarDate(year, month, day);

        if (date.day !== day || date.month !== month || date.year !== year) {
            return null;
        }

        return date.toString();
    } catch {
        return null;
    }
}

function onTextInput(): void {
    const trimmed = textValue.value.trim();

    if (trimmed === '') {
        modelValue.value = '';
        return;
    }

    const iso = parseITDate(trimmed);

    if (iso) {
        modelValue.value = iso;
    }
}

// Su blur riallineo il testo al valore valido: se l'utente ha lasciato un
// input parziale/non valido lo riporto all'ultima data accettata (o lo svuoto).
function onTextBlur(): void {
    textValue.value = modelValue.value ? formatDateIT(modelValue.value) : '';
}

function onCalendarSelect(value: unknown): void {
    if (value instanceof CalendarDate) {
        modelValue.value = value.toString();
    }

    popoverOpen.value = false;
}
</script>

<template>
    <!-- Mobile (<md): input nativo. Touch keyboard del SO gestisce il picker. -->
    <Input :id="id" v-model="modelValue" type="date" class="md:hidden" />

    <!-- Desktop (md+): input digitabile dd/mm/yyyy + trigger calendario. -->
    <div class="relative hidden md:block">
        <Input
            :id="id"
            v-model="textValue"
            type="text"
            inputmode="numeric"
            :placeholder="placeholder"
            class="pr-9"
            @input="onTextInput"
            @blur="onTextBlur"
        />

        <Popover v-model:open="popoverOpen">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-9 items-center justify-center rounded-r-md text-muted-foreground/70 transition-colors hover:text-foreground focus-visible:outline-none"
                    aria-label="Apri calendario"
                >
                    <PhCalendarBlank :size="16" />
                </button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="end">
                <!-- `placeholder` = data su cui il calendar centra il focus
                     all'apertura. Se c'è già una selezione, parto da lì invece
                     di "oggi" (UX standard datepicker). -->
                <!-- `layout="month-and-year"`: l'intestazione diventa due dropdown
                     (mese + anno) → salto d'anno immediato senza cliccare « N volte. -->
                <Calendar
                    :model-value="calendarValue"
                    :placeholder="calendarValue"
                    layout="month-and-year"
                    weekday-format="short"
                    locale="it-IT"
                    @update:model-value="onCalendarSelect"
                />
            </PopoverContent>
        </Popover>
    </div>
</template>
