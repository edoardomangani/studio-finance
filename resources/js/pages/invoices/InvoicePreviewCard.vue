<script setup lang="ts">
/**
 * InvoicePreviewCard — singola anteprima di import fattura.
 *
 * Implementato come [[AccordionItem]] shadcn (Root vive in [[Import.vue]],
 * `type="multiple" v-model="openIds"`): così expand/collapse ha
 * aria-expanded, focus visible, keyboard nav, animazione altezza built-in.
 * Lo stato `expanded` NON vive più sulla preview — derivato da `openIds`
 * lato parent.
 *
 * Stato:
 * - **pronto**: parsing OK + cliente risolto. Verde discreto.
 * - **da_confermare**: parsing OK ma cliente non risolto. Auto-open.
 * - **scartato**: parsing fallito OR user-toggled via checkbox header.
 *   Esclusa dal submit.
 *
 * Header trigger area è LIMITATA al caret + numero + data: il click su
 * cliente / total / badge NON apre la card (sono informational, no tap
 * accidentali). Il checkbox a sinistra controlla l'inclusione nel batch
 * (uncheckato = scartato): scan rapido senza dover aprire.
 *
 * `v-model`: oggetto preview completo. Modifiche emesse via
 * `update:modelValue` → parent ricostruisce la lista.
 */
import {
    PhCaretRight,
    PhCheckCircle,
    PhFile,
    PhUploadSimple,
    PhWarning,
    PhWarningCircle,
} from '@phosphor-icons/vue';
import { AccordionHeader, AccordionTrigger } from 'reka-ui';
import { computed } from 'vue';
import ClientPicker from '@/components/ClientPicker.vue';
import FormField from '@/components/forms/FormField.vue';
import { AccordionContent, AccordionItem } from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import { formatDateIT, formatEUR } from '@/lib/format';
import type { ClientForPicker, ImportDiscrepancy } from '@/types';

export type ImportPreviewLocal = {
    /** Local UUID-like per tracking nel v-for + AccordionItem `value`. */
    id: string;
    filename: string;
    parsed: boolean;
    error?: string;
    number: string;
    issued_at: string;
    amount: string;
    inarcassa_amount: string;
    stamp_amount: string;
    art_15_amount: string;
    /** Rivalsa bollo: true = bollo nel totale, false = a carico studio. */
    stamp_charged_to_client: boolean;
    bank_withholding: boolean;
    client_mode: 'existing' | 'new';
    existing_client_id: number | null;
    new_client: {
        name: string;
        vat_number: string;
        tax_code: string;
        bank_withholding: boolean;
    };
    /** Uncheckato (= excluded true) → scartato dal batch import. */
    excluded: boolean;
    discrepancies?: Partial<
        Record<'stamp_amount' | 'inarcassa_amount', ImportDiscrepancy>
    >;
};

const props = defineProps<{
    clients: ClientForPicker[];
}>();

const preview = defineModel<ImportPreviewLocal>({ required: true });

defineEmits<{
    /** Card scartata da parse-fail: l'utente vuole correggere e ricaricare.
     *  Il parent scrolla il drop zone in viewport + lo evidenzia. */
    (e: 'reupload-request'): void;
}>();

const status = computed<'pronto' | 'da_confermare' | 'scartato'>(() => {
    if (preview.value.excluded || !preview.value.parsed) {
        return 'scartato';
    }

    if (preview.value.client_mode === 'existing') {
        return preview.value.existing_client_id !== null
            ? 'pronto'
            : 'da_confermare';
    }

    const hasName = preview.value.new_client.name.trim() !== '';
    const hasId =
        preview.value.new_client.vat_number.trim() !== '' ||
        preview.value.new_client.tax_code.trim() !== '';

    return hasName && hasId ? 'pronto' : 'da_confermare';
});

const statusBadge = computed(
    () =>
        ({
            pronto: {
                label: 'Pronto',
                variant: 'outline' as const,
                class: 'border-positive/40 text-positive',
            },
            da_confermare: {
                label: 'Da confermare',
                variant: 'outline' as const,
                class: 'border-foreground/30 text-foreground',
            },
            scartato: {
                label: 'Scartato',
                variant: 'secondary' as const,
                class: '',
            },
        })[status.value],
);

/** Classe bg + border per AccordionItem in base allo stato. */
const itemClass = computed(() => ({
    'border-border bg-card': status.value === 'pronto',
    'border-foreground/15 bg-muted/40': status.value === 'da_confermare',
    'border-border bg-muted/30 opacity-70': status.value === 'scartato',
}));

const clientLabel = computed(() => {
    if (!preview.value.parsed) {
        return preview.value.filename;
    }

    if (
        preview.value.client_mode === 'existing' &&
        preview.value.existing_client_id !== null
    ) {
        return (
            props.clients.find((c) => c.id === preview.value.existing_client_id)
                ?.name ?? '—'
        );
    }

    return preview.value.new_client.name || '— cliente nuovo';
});

function toFloat(v: string): number {
    const n = parseFloat(v);

    return Number.isFinite(n) ? n : 0;
}

const totalFloat = computed(() => {
    if (!preview.value.parsed) {
        return 0;
    }

    const p = preview.value;

    return (
        toFloat(p.amount) +
        toFloat(p.inarcassa_amount) +
        (p.stamp_charged_to_client ? toFloat(p.stamp_amount) : 0) +
        toFloat(p.art_15_amount)
    );
});

const stampDiscrepancy = computed(
    () => preview.value.discrepancies?.stamp_amount,
);
const inarcassaDiscrepancy = computed(
    () => preview.value.discrepancies?.inarcassa_amount,
);
const hasAnyDiscrepancy = computed(
    () => !!(stampDiscrepancy.value || inarcassaDiscrepancy.value),
);

/** Checkbox "Includi nel batch" — invertito rispetto a `excluded`.
 *  Uncheckato = excluded=true = stato visivo scartato. Spostato in header
 *  per scan rapido senza dover espandere la card. */
const included = computed<boolean>({
    get: () => !preview.value.excluded,
    set: (val) => {
        preview.value = { ...preview.value, excluded: !val };
    },
});

/** RadioGroup ha `modelValue: string`. Wrap con narrowing su
 *  'existing' | 'new' per type safety. */
const clientModeStr = computed({
    get: () => preview.value.client_mode,
    set: (value: string) => {
        if (value === 'existing' || value === 'new') {
            preview.value = { ...preview.value, client_mode: value };
        }
    },
});

function onClientPickerSelect(client: ClientForPicker): void {
    preview.value = {
        ...preview.value,
        existing_client_id: client.id,
        new_client: {
            ...preview.value.new_client,
            bank_withholding: client.bank_withholding,
        },
    };
}

const triggerAriaLabel = computed(
    () => `Espandi anteprima ${preview.value.number || preview.value.filename}`,
);
</script>

<template>
    <AccordionItem
        :value="preview.id"
        class="overflow-hidden rounded-md border transition-colors"
        :class="itemClass"
    >
        <!-- Header row: Checkbox come sibling indipendente + Trigger che
             occupa TUTTO il resto.
             Layout interno trigger: grid 3-col mobile (2 righe) / flex inline md+.
             Negative margins (-my-3 -ml-3 -mr-4) + padding compensatorio
             estendono il bg di hover/focus fino ai bordi della card
             (clip via `overflow-hidden` su AccordionItem) — così click
             e feedback visivo coprono "tutto tranne il checkbox". -->
        <div
            class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30"
        >
            <!-- Checkbox: includi nel batch (uncheck = scartato). Disabled
                 su parse-fail: file rotto non si può "includere".
                 `@click.stop` impedisce bubble al trigger sibling. -->
            <Checkbox
                v-model="included"
                :disabled="!preview.parsed"
                :aria-label="`Includi ${preview.number || preview.filename} nell'import`"
                class="shrink-0"
                @click.stop
            />

            <AccordionHeader class="contents">
                <AccordionTrigger
                    :aria-label="triggerAriaLabel"
                    :class="[
                        'grid min-w-0 flex-1 cursor-pointer grid-cols-[auto_1fr_auto] items-center gap-x-3 gap-y-1',
                        '-my-3 -mr-4 -ml-3 py-3 pr-4 pl-3',
                        'text-left transition-colors outline-none',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                        '[&[data-state=open]_[data-caret]]:rotate-90',
                        'md:flex md:items-center md:gap-3',
                    ]"
                >
                    <!-- Caret: mobile span entrambe le righe + self-center
                         (centrato verticalmente contro le 2 righe, no
                         disallineamento). Desktop: single row. -->
                    <PhCaretRight
                        :size="14"
                        data-caret
                        class="row-span-2 shrink-0 self-center text-muted-foreground transition-transform duration-200 md:row-span-1 md:self-auto"
                    />

                    <!-- ID cluster: lingua naturale "Fattura #N del DD/MM/YYYY".
                         "Fattura" e "del" come connettori lessicali (muted),
                         numero e data come info portante (foreground). Gap-1.5
                         stretto = leggono come UNA frase, non 4 chunks.
                         Parse-fail: filename solo (no prefisso). -->
                    <div class="flex min-w-0 items-baseline gap-1.5">
                        <template v-if="preview.parsed">
                            <span class="shrink-0 text-13 text-muted-foreground"
                                >Fattura</span
                            >
                            <span
                                class="tabular shrink-0 text-13 font-medium text-foreground"
                                >#{{ preview.number || '—' }}</span
                            >
                            <span class="shrink-0 text-13 text-muted-foreground"
                                >del</span
                            >
                            <span
                                class="tabular shrink-0 text-13 text-foreground"
                                >{{ formatDateIT(preview.issued_at) }}</span
                            >
                        </template>
                        <span
                            v-else
                            class="min-w-0 truncate text-13 font-medium text-foreground"
                        >
                            {{ preview.filename }}
                        </span>
                    </div>

                    <!-- Divider verticale tra ID cluster e Cliente. SOLO
                         desktop: hairline 1px, h-4 (16px tall), bg-border.
                         Su mobile il cliente va a riga 2, niente separatore
                         necessario. Sibling del cluster con md:order-1 (tra
                         ID order-0 e cliente order-2). -->
                    <span
                        v-if="preview.parsed"
                        class="hidden h-4 w-px shrink-0 bg-border md:order-1 md:block"
                        aria-hidden="true"
                    />

                    <!-- Badge: row 1 col 3 mobile (justify-end), order-last
                         desktop. Status indicator sempre a destra. -->
                    <Badge
                        :variant="statusBadge.variant"
                        :class="[
                            'col-start-3 row-start-1 shrink-0 justify-self-end text-2xs md:order-last md:col-auto md:row-auto md:justify-self-auto',
                            statusBadge.class,
                        ]"
                    >
                        <PhCheckCircle
                            v-if="status === 'pronto'"
                            :size="11"
                            weight="fill"
                        />
                        <PhWarning
                            v-else-if="status === 'da_confermare'"
                            :size="11"
                        />
                        <PhWarningCircle v-else :size="11" />
                        {{ statusBadge.label }}
                    </Badge>

                    <!-- Cliente: row 2 col 2 mobile, order-2 flex-1 desktop.
                         Su desktop il divider verticale (sibling sopra)
                         crea la separazione visiva tra ID e cliente.
                         `text-foreground` (no muted) → peso primario co-equal
                         al numero. -->
                    <span
                        v-if="preview.parsed"
                        class="col-start-2 row-start-2 min-w-0 truncate text-13 text-foreground md:order-2 md:col-auto md:row-auto md:flex-1"
                    >
                        {{ clientLabel }}
                    </span>

                    <!-- Total (row 2 col 3 mobile, order-3 desktop):
                         "quanto", accanto al cliente nel mobile per logica
                         "questo cliente per questa cifra". -->
                    <span
                        v-if="preview.parsed"
                        class="tabular col-start-3 row-start-2 shrink-0 justify-self-end text-13 font-medium text-foreground md:order-3 md:col-auto md:row-auto md:justify-self-auto"
                    >
                        {{ formatEUR(totalFloat) }}
                    </span>
                </AccordionTrigger>
            </AccordionHeader>
        </div>

        <!-- Body card espanso. AccordionContent gestisce animazione altezza
             via `data-[state=*]:animate-accordion-*`. Override del padding
             default del wrapper (`pt-0 pb-4`) via `py-4` + aggiunta border-t. -->
        <AccordionContent class="border-t border-border-soft px-4 py-4">
            <!-- Parsing error: card non editabile, motivo + recovery CTA.
                 Niente "Rimuovi" per-card: l'utente può lasciare la card
                 in lista (auto-esclusa perché !parsed = stato scartato),
                 oppure usare il bulk "Rimuovi N scartate" in toolbar per
                 pulizia visiva. "Carica file corretto" resta come unica
                 azione di recovery. -->
            <div v-if="!preview.parsed" class="space-y-3">
                <p class="text-13 text-foreground">
                    <strong>Errore di parsing:</strong>
                    {{ preview.error || 'XML non valido.' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Il file
                    <span class="tabular">{{ preview.filename }}</span> non è
                    una fattura elettronica XML valida o ha una struttura non
                    riconosciuta. Verifica che non sia un file
                    <code class="font-mono text-xs">.p7m</code>
                    firmato (non supportato).
                </p>
                <div class="flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="$emit('reupload-request')"
                    >
                        <PhUploadSimple :size="14" />
                        Carica file corretto
                    </Button>
                </div>
            </div>

            <!-- Parsing OK: form editabile -->
            <div v-else class="space-y-6">
                <!-- Banner discrepanze: palette warning (signal-warning ocra/
                     ambra calmo del design system, NON giallo squillante).
                     Lead-heading + body. Stesso ambra riappare sui field
                     divergenti come filo conduttore visivo. -->
                <div
                    v-if="hasAnyDiscrepancy"
                    class="flex items-start gap-2.5 rounded-md border border-warning/40 bg-warning-foreground px-3 py-2.5"
                >
                    <PhWarning
                        :size="16"
                        weight="fill"
                        class="mt-px shrink-0 text-warning"
                    />
                    <div class="space-y-0.5 text-xs">
                        <p class="font-medium text-foreground">
                            Controlla bollo e cassa
                        </p>
                        <p class="text-muted-foreground">
                            Gli importi del XML divergono dal calcolo standard
                            (Inarcassa 4%, bollo €2 sopra €77,47). Puoi
                            importare comunque: l'XML è il documento legale.
                        </p>
                    </div>
                </div>

                <!-- Anagrafica fattura: sezione IMPLICITA (no h3).
                     La card stessa è già il contenitore di sezione; un h3
                     "Anagrafica" qui sarebbe sezione-dentro-sezione. I 4
                     campi importi sono visualmente coesi grazie al FieldGroup. -->
                <FieldGroup>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <FormField
                            label="Numero"
                            :for="`prev-${preview.id}-number`"
                        >
                            <Input
                                :id="`prev-${preview.id}-number`"
                                v-model="preview.number"
                                autocomplete="off"
                            />
                        </FormField>
                        <FormField
                            label="Data emissione"
                            :for="`prev-${preview.id}-date`"
                        >
                            <Input
                                :id="`prev-${preview.id}-date`"
                                v-model="preview.issued_at"
                                type="date"
                            />
                        </FormField>
                    </div>

                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <FormField
                            label="Imponibile"
                            :for="`prev-${preview.id}-amount`"
                        >
                            <DecimalInput
                                :id="`prev-${preview.id}-amount`"
                                v-model="preview.amount"
                                :min="0"
                                :max="9999999.99"
                                class="tabular text-right"
                            />
                        </FormField>
                        <FormField
                            label="Bollo"
                            :for="`prev-${preview.id}-stamp`"
                            :hint="
                                stampDiscrepancy
                                    ? `Standard ${formatEUR(stampDiscrepancy.expected)}`
                                    : undefined
                            "
                        >
                            <DecimalInput
                                :id="`prev-${preview.id}-stamp`"
                                v-model="preview.stamp_amount"
                                :min="0"
                                :max="9999999.99"
                                :class="[
                                    'tabular text-right',
                                    stampDiscrepancy
                                        ? 'border-warning/60 bg-warning-foreground'
                                        : '',
                                ]"
                            />
                            <template v-if="stampDiscrepancy" #hint>
                                <span
                                    class="inline-flex items-center gap-1 text-warning"
                                >
                                    <PhWarning :size="11" weight="fill" />
                                    Standard
                                    {{ formatEUR(stampDiscrepancy.expected) }}
                                </span>
                            </template>
                        </FormField>
                        <FormField
                            label="Cassa 4%"
                            :for="`prev-${preview.id}-cassa`"
                            :hint="
                                inarcassaDiscrepancy
                                    ? `Standard ${formatEUR(inarcassaDiscrepancy.expected)}`
                                    : undefined
                            "
                        >
                            <DecimalInput
                                :id="`prev-${preview.id}-cassa`"
                                v-model="preview.inarcassa_amount"
                                :min="0"
                                :max="9999999.99"
                                :class="[
                                    'tabular text-right',
                                    inarcassaDiscrepancy
                                        ? 'border-warning/60 bg-warning-foreground'
                                        : '',
                                ]"
                            />
                            <template v-if="inarcassaDiscrepancy" #hint>
                                <span
                                    class="inline-flex items-center gap-1 text-warning"
                                >
                                    <PhWarning :size="11" weight="fill" />
                                    Standard
                                    {{
                                        formatEUR(inarcassaDiscrepancy.expected)
                                    }}
                                </span>
                            </template>
                        </FormField>
                        <FormField
                            label="Art.15"
                            :for="`prev-${preview.id}-art15`"
                        >
                            <DecimalInput
                                :id="`prev-${preview.id}-art15`"
                                v-model="preview.art_15_amount"
                                :min="0"
                                :max="9999999.99"
                                class="tabular text-right"
                            />
                        </FormField>
                    </div>

                    <Field orientation="horizontal">
                        <Switch
                            :id="`prev-${preview.id}-stamp-charged`"
                            v-model="preview.stamp_charged_to_client"
                        />
                        <FieldLabel
                            :for="`prev-${preview.id}-stamp-charged`"
                            class="font-normal"
                        >
                            Bollo a carico del cliente
                        </FieldLabel>
                    </Field>

                    <Field orientation="horizontal">
                        <Switch
                            :id="`prev-${preview.id}-with`"
                            v-model="preview.bank_withholding"
                        />
                        <FieldLabel
                            :for="`prev-${preview.id}-with`"
                            class="font-normal"
                        >
                            Soggetta a ritenuta bancaria
                        </FieldLabel>
                    </Field>
                </FieldGroup>

                <!-- Cliente: sezione ESPLICITA. Il titolo guadagna posto
                     perché introduce un cambio di pattern d'interazione
                     (radio binario + form condizionale), non solo "altri
                     campi". RadioGroup invece di Tabs: la scelta è una
                     decisione del form, non una navigazione tra viste. -->
                <div>
                    <h3 class="kicker mb-3 text-muted-foreground">Cliente</h3>
                    <RadioGroup
                        v-model="clientModeStr"
                        class="flex flex-row gap-6"
                        :aria-label="`Cliente per ${preview.number || preview.filename}`"
                    >
                        <div class="flex items-center gap-2">
                            <RadioGroupItem
                                :id="`prev-${preview.id}-mode-existing`"
                                value="existing"
                            />
                            <label
                                :for="`prev-${preview.id}-mode-existing`"
                                class="cursor-pointer text-13 font-normal text-foreground"
                            >
                                Esistente
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioGroupItem
                                :id="`prev-${preview.id}-mode-new`"
                                value="new"
                            />
                            <label
                                :for="`prev-${preview.id}-mode-new`"
                                class="cursor-pointer text-13 font-normal text-foreground"
                            >
                                Nuovo da XML
                            </label>
                        </div>
                    </RadioGroup>

                    <div v-if="preview.client_mode === 'existing'" class="mt-4">
                        <FormField
                            label="Cliente"
                            :for="`prev-${preview.id}-client`"
                        >
                            <ClientPicker
                                :id="`prev-${preview.id}-client`"
                                v-model="preview.existing_client_id"
                                :clients="clients"
                                @select="onClientPickerSelect"
                            />
                        </FormField>
                    </div>

                    <div v-else class="mt-4">
                        <FieldGroup>
                            <FormField
                                label="Denominazione"
                                :for="`prev-${preview.id}-new-name`"
                                required
                            >
                                <Input
                                    :id="`prev-${preview.id}-new-name`"
                                    v-model="preview.new_client.name"
                                />
                            </FormField>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <FormField
                                    label="P.IVA"
                                    :for="`prev-${preview.id}-new-vat`"
                                >
                                    <Input
                                        :id="`prev-${preview.id}-new-vat`"
                                        v-model="preview.new_client.vat_number"
                                        autocomplete="off"
                                    />
                                </FormField>
                                <FormField
                                    label="Codice Fiscale"
                                    :for="`prev-${preview.id}-new-tax`"
                                >
                                    <Input
                                        :id="`prev-${preview.id}-new-tax`"
                                        v-model="preview.new_client.tax_code"
                                        autocomplete="off"
                                    />
                                </FormField>
                            </div>
                            <Field orientation="horizontal">
                                <Switch
                                    :id="`prev-${preview.id}-new-with`"
                                    v-model="
                                        preview.new_client.bank_withholding
                                    "
                                />
                                <FieldLabel
                                    :for="`prev-${preview.id}-new-with`"
                                    class="font-normal"
                                >
                                    Cliente soggetto a ritenuta di default
                                </FieldLabel>
                            </Field>
                            <p class="text-xs text-muted-foreground">
                                Almeno P.IVA o Codice Fiscale obbligatori.
                            </p>
                        </FieldGroup>
                    </div>
                </div>

                <!-- Sorgente file: footer caption discreta, in fondo a tutto.
                     Info debug di basso peso (text-2xs, muted/60), serve
                     solo per ricondurre la card al XML originale se serve. -->
                <p
                    class="flex items-center gap-1 text-[10px] text-muted-foreground/60"
                >
                    <PhFile :size="11" class="shrink-0" />
                    <span class="truncate font-mono">{{
                        preview.filename
                    }}</span>
                </p>

                <!-- Niente footer "Rimuovi": l'unica azione di esclusione
                     dal batch è il checkbox in header (Scarta). Il bulk
                     "Rimuovi N scartate" in toolbar Import.vue cleanup
                     visivo della lista quando serve. -->
            </div>
        </AccordionContent>
    </AccordionItem>
</template>
