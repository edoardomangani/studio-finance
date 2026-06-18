<script setup lang="ts">
/**
 * Invoices — Import XML batch page.
 *
 * Flusso utente:
 * 1. Drop zone in alto (drag&drop + click to browse), accept=".xml" multiple.
 * 2. Su drop/select → upload immediato a /invoices/import/parse via useForm.
 *    Server parsa ogni file via InvoiceXmlParser e ritorna `previews`.
 * 3. Lista di [[InvoicePreviewCard]] una sotto l'altra, ciascuna editabile.
 *    Stato per card: pronto / da_confermare / scartato.
 * 4. Topbar "Importa N fatture" enabled solo se almeno una pronta.
 *    Click → POST /invoices/import con array dei pronti + new client
 *    dedup intra-batch in Action lato server.
 *
 * Le anteprime vivono come stato locale (non persistite tra navigazioni
 * page reload). Il parse arricchisce ma non sostituisce: nuovi file
 * vengono appended.
 */
import {
    Head,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import {
    PhCaretDoubleDown,
    PhCaretDoubleUp,
    PhUploadSimple,
} from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import ImportXmlController from '@/actions/App/Http/Controllers/ImportXmlController';
import { Accordion } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import InvoicePreviewCard from '@/pages/invoices/InvoicePreviewCard.vue';
import type { ImportPreviewLocal } from '@/pages/invoices/InvoicePreviewCard.vue';
import { index as invoicesIndex } from '@/routes/invoices';
import type {
    ClientForPicker,
    ImportInvoicesSubmitPayload,
    ImportPreviewServer,
    ImportXmlUploadPayload,
} from '@/types';

const props = defineProps<{
    clients: ClientForPicker[];
    previews?: ImportPreviewServer[];
}>();

setLayoutProps({
    pageTitle: 'Importa fatture XML',
    pageCrumbs: [
        { label: 'Fatture', href: invoicesIndex().url },
        { label: 'Importa' },
    ],
    subbar: false,
});

/** Stato locale: array di preview con local state aggiuntivo. */
const previewList = ref<ImportPreviewLocal[]>([]);

/** Ids delle card attualmente aperte. Sincronizzato con `<Accordion
 *  type="multiple" v-model="openIds">`. Espande/collassa tramite questo
 *  array, no flag `expanded` sulla preview (sarebbe duplicazione di
 *  stato + race condition con Accordion). */
const openIds = ref<string[]>([]);

/** Drag-over visivo. */
const isDragging = ref(false);

/** Upload form per /invoices/import/parse — multi-file. */
const uploadForm = useForm<ImportXmlUploadPayload>({ files: [] });

/** Submit form per /invoices/import — array previews pronti. */
const submitForm = useForm<ImportInvoicesSubmitPayload>({ previews: [] });

const page = usePage<{ previews?: ImportPreviewServer[] }>();

/** ID locale opaco per il key di v-for. Non usiamo crypto.randomUUID
 *  perché richiede Secure Context (HTTPS o localhost), e Herd dev serve
 *  su http://*.test. Counter + base36 timestamp è sufficiente: gli ID
 *  vivono solo client-side per la durata della sessione.
 *
 *  IMPORTANTE: deve stare PRIMA del watch con `immediate: true` che ne
 *  fa uso indirettamente (toLocal → makeLocalId). `let` non è hoisted:
 *  metterlo dopo il watch produce TDZ error in SSR. */
let localIdCounter = 0;
function makeLocalId(): string {
    localIdCounter++;

    return `prev-${Date.now().toString(36)}-${localIdCounter}`;
}

// Quando arrivano nuove `previews` dal server (dopo parse), le mergiamo
// alla lista locale + popoliamo `openIds` con quelle da auto-espandere
// (parse-fail / cliente non matchato / discrepanze). Lo stato expanded
// vive SOLO in `openIds`, non sulla preview.
watch(
    () => page.props.previews,
    (incoming) => {
        if (!incoming || incoming.length === 0) {
            return;
        }

        const localOnes = incoming.map((p) => toLocal(p));
        const newOpenIds = incoming
            .map((p, i) => (shouldAutoExpand(p) ? localOnes[i].id : null))
            .filter((id): id is string => id !== null);

        previewList.value = [...previewList.value, ...localOnes];
        openIds.value = [...openIds.value, ...newOpenIds];
    },
    { immediate: true },
);

/** Card auto-aperta quando l'utente deve confermare qualcosa:
 *  parse fallito (vede l'errore), cliente non matchato (sceglie), o
 *  discrepanze rilevate (banner + hint inline). Tutto OK → collassata. */
function shouldAutoExpand(p: ImportPreviewServer): boolean {
    if (!p.parsed) {
        return true;
    }

    if ((p.matched_client_id ?? null) === null) {
        return true;
    }

    return !!(p.discrepancies && Object.keys(p.discrepancies).length > 0);
}

function toLocal(p: ImportPreviewServer): ImportPreviewLocal {
    const matched = p.matched_client_id ?? null;
    const matchedClient =
        matched !== null ? props.clients.find((c) => c.id === matched) : null;

    // Default ritenuta: il parser XML non sa nulla della ritenuta bancaria
    // 8% (è un'operazione bancaria, non documentale). Se il cliente
    // matched ha ritenuta default, la ereditiamo; altrimenti false.
    const withholdingDefault = matchedClient?.bank_withholding ?? false;

    return {
        id: makeLocalId(),
        filename: p.filename,
        parsed: p.parsed,
        error: p.error,
        number: p.number ?? '',
        issued_at: p.issued_at ?? '',
        amount: (p.amount ?? 0).toFixed(2),
        inarcassa_amount: (p.inarcassa_amount ?? 0).toFixed(2),
        stamp_amount: (p.stamp_amount ?? 0).toFixed(2),
        art_15_amount: (p.art_15_amount ?? 0).toFixed(2),
        // L'XML non porta la rivalsa bollo: default a carico cliente.
        stamp_charged_to_client: true,
        bank_withholding: withholdingDefault,
        client_mode: matched !== null ? 'existing' : 'new',
        existing_client_id: matched,
        new_client: {
            name: p.client_from_xml?.name ?? '',
            vat_number: p.client_from_xml?.vat_number ?? '',
            tax_code: p.client_from_xml?.tax_code ?? '',
            bank_withholding: false,
        },
        excluded: false,
        discrepancies: p.discrepancies,
    };
}

// ─── Counters ────────────────────────────────────────────────────────────

const counts = computed(() => {
    let pronto = 0;
    let daConfermare = 0;
    let scartato = 0;

    for (const p of previewList.value) {
        if (p.excluded || !p.parsed) {
            scartato++;
            continue;
        }

        const hasClient =
            p.client_mode === 'existing'
                ? p.existing_client_id !== null
                : p.new_client.name.trim() !== '' &&
                  (p.new_client.vat_number.trim() !== '' ||
                      p.new_client.tax_code.trim() !== '');

        if (hasClient) {
            pronto++;
        } else {
            daConfermare++;
        }
    }

    return { pronto, daConfermare, scartato };
});

const canSubmit = computed(
    () => counts.value.pronto > 0 && !submitForm.processing,
);

// ─── Drop zone handlers ──────────────────────────────────────────────────

function onFilesPicked(event: Event): void {
    const target = event.target as HTMLInputElement;

    if (target.files) {
        uploadFiles(Array.from(target.files));
        target.value = '';
    }
}

function onDrop(event: DragEvent): void {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer?.files;

    if (files && files.length > 0) {
        uploadFiles(
            Array.from(files).filter((f) =>
                f.name.toLowerCase().endsWith('.xml'),
            ),
        );
    }
}

function uploadFiles(files: File[]): void {
    if (files.length === 0) {
        return;
    }

    uploadForm.files = files;
    uploadForm.post(ImportXmlController.parse.url(), {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset('files');
        },
    });
}

// ─── Bulk actions ────────────────────────────────────────────────────────

/** True quando ALMENO una card è aperta: il toggle bulk diventa "Comprimi
 *  tutte"; altrimenti "Espandi tutte". Single button, no doppio CTA. */
const anyExpanded = computed(() => openIds.value.length > 0);

function toggleExpandAll(): void {
    openIds.value = anyExpanded.value ? [] : previewList.value.map((p) => p.id);
}

/** Scroll del drop zone in viewport + flash visivo. Chiamato quando una
 *  card scartata emette `reupload-request` (utente vuole sostituire un
 *  file rotto con quello corretto). */
const dropZoneRef = ref<HTMLElement | null>(null);
const dropZoneHighlight = ref(false);

function scrollToDropZone(): void {
    dropZoneRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    dropZoneHighlight.value = true;
    setTimeout(() => {
        dropZoneHighlight.value = false;
    }, 1500);
}

// ─── Submit ──────────────────────────────────────────────────────────────

function submit(): void {
    const payload = previewList.value
        .filter((p) => p.parsed && !p.excluded)
        .filter((p) => {
            if (p.client_mode === 'existing') {
                return p.existing_client_id !== null;
            }

            return (
                p.new_client.name.trim() !== '' &&
                (p.new_client.vat_number.trim() !== '' ||
                    p.new_client.tax_code.trim() !== '')
            );
        })
        .map((p) => ({
            number: p.number,
            issued_at: p.issued_at,
            amount: p.amount,
            inarcassa_amount: p.inarcassa_amount,
            stamp_amount: p.stamp_amount,
            art_15_amount: p.art_15_amount,
            stamp_charged_to_client: p.stamp_charged_to_client,
            bank_withholding: p.bank_withholding,
            client_mode: p.client_mode,
            existing_client_id:
                p.client_mode === 'existing' ? p.existing_client_id : null,
            new_client:
                p.client_mode === 'new'
                    ? {
                          name: p.new_client.name.trim(),
                          vat_number: p.new_client.vat_number.trim() || null,
                          tax_code: p.new_client.tax_code.trim() || null,
                          bank_withholding: p.new_client.bank_withholding,
                      }
                    : null,
        }));

    submitForm.previews = payload;
    submitForm.post(ImportXmlController.store.url());
}

// ─── Reset / Svuota ──────────────────────────────────────────────────────

const resetDialogOpen = ref(false);

function reset(): void {
    if (previewList.value.length === 0) {
        router.visit(invoicesIndex().url);

        return;
    }

    resetDialogOpen.value = true;
}

function confirmReset(): void {
    previewList.value = [];
    resetDialogOpen.value = false;
}

// ─── Mapping errori validation server-side a label leggibili ─────────────

/** Mappa path Laravel (es. `previews.0.new_client.vat_number`) →
 *  etichetta utente (es. "Fattura 1 · P.IVA cliente nuovo"). I path
 *  arrivano da `submitForm.errors` dopo POST /invoices/import. */
const FIELD_LABELS: Record<string, string> = {
    number: 'Numero',
    issued_at: 'Data emissione',
    amount: 'Imponibile',
    inarcassa_amount: 'Cassa Inarcassa',
    stamp_amount: 'Bollo',
    art_15_amount: 'Art.15',
    stamp_charged_to_client: 'Bollo a carico del cliente',
    bank_withholding: 'Ritenuta bancaria',
    client_mode: 'Modalità cliente',
    existing_client_id: 'Cliente esistente',
    'new_client.name': 'Denominazione cliente nuovo',
    'new_client.vat_number': 'P.IVA cliente nuovo',
    'new_client.tax_code': 'Codice Fiscale cliente nuovo',
    'new_client.bank_withholding': 'Ritenuta cliente nuovo',
};

function humanizeErrorField(path: string): string {
    // Laravel emette path tipo `previews.0.new_client.vat_number` o `previews`.
    const match = /^previews\.(\d+)\.(.+)$/.exec(path);

    if (match === null) {
        return path === 'previews' ? 'Elenco fatture' : path;
    }

    const [, indexStr, rest] = match;
    const fatturaNum = Number(indexStr) + 1;
    const label = FIELD_LABELS[rest] ?? rest;

    return `Fattura ${fatturaNum} · ${label}`;
}
</script>

<template>
    <Head title="Importa fatture XML" />

    <Teleport to="#page-topbar-actions" defer>
        <!-- Vuoto: "Annulla" è ridondante col back su mobile → solo desktop.
             Con anteprime: "Svuota" è un'azione vera, resta ovunque. -->
        <Button
            type="button"
            variant="outline"
            size="sm"
            :class="previewList.length === 0 ? 'hidden lg:inline-flex' : ''"
            @click="reset"
        >
            {{ previewList.length === 0 ? 'Annulla' : 'Svuota' }}
        </Button>
        <Button type="button" size="sm" :disabled="!canSubmit" @click="submit">
            <Spinner v-if="submitForm.processing" />
            Importa {{ counts.pronto }}
            <span class="hidden sm:inline">{{
                counts.pronto === 1 ? 'fattura' : 'fatture'
            }}</span>
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[1100px]">
        <!-- ─── Drop zone ─── -->
        <div
            ref="dropZoneRef"
            class="rounded-md border border-dashed bg-card transition-colors"
            :class="[
                isDragging
                    ? 'border-accent-vivid bg-accent-vivid/5'
                    : 'border-border hover:border-foreground/30',
                dropZoneHighlight
                    ? 'ring-2 ring-accent-vivid/60 ring-offset-2 ring-offset-background'
                    : '',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop="onDrop"
        >
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 px-6 py-10 text-center"
            >
                <PhUploadSimple :size="24" class="text-muted-foreground" />
                <span class="text-13 font-medium text-foreground">
                    Carica i file XML qui
                    <span class="text-muted-foreground">
                        (drag&amp;drop o tocca)</span
                    >
                </span>
                <span class="text-xs text-muted-foreground">
                    Max 20 file, 1 MB ciascuno. I file
                    <code class="font-mono">.p7m</code> firmati non sono
                    supportati.
                </span>
                <input
                    type="file"
                    multiple
                    accept=".xml,application/xml,text/xml"
                    class="sr-only"
                    @change="onFilesPicked"
                />
            </label>
        </div>

        <p v-if="uploadForm.errors.files" class="mt-2 text-xs text-destructive">
            {{ uploadForm.errors.files }}
        </p>
        <div
            v-if="uploadForm.processing"
            class="mt-3 flex items-center gap-2 text-xs text-muted-foreground"
        >
            <Spinner />
            Parsing dei file in corso…
        </div>

        <!-- ─── Empty state: flusso a 3 step "Come funziona" ─── -->
        <!-- Trattamento "tabulare": i 3 blocchi sono legati da border-y
             (top+bottom) + separatori verticali tra colonne (md:divide-x).
             Su mobile collassa a stack singolo con divide-y tra item.
             Niente bordo sulla `<section>` outer: la coerenza con la
             summary section (mt-10 md:mt-14) resta intatta — qui i bordi
             vivono sull'`<ol>`, non sul wrapper. -->
        <section
            v-if="previewList.length === 0 && !uploadForm.processing"
            class="mt-10 md:mt-14"
            aria-label="Come funziona l'import"
        >
            <div class="mb-4 flex h-8 items-center">
                <p class="kicker">Come funziona</p>
            </div>

            <ol
                class="grid list-none grid-cols-1 divide-y divide-border-soft border-y border-border-soft md:grid-cols-3 md:divide-x md:divide-y-0"
            >
                <li
                    class="px-0 py-6 md:px-6 md:py-7 md:first:pl-0 md:last:pr-0"
                >
                    <p
                        class="tabular text-2xl font-light text-muted-foreground/40"
                    >
                        01
                    </p>
                    <p class="mt-3 text-13 font-medium text-foreground">
                        Anteprima editabile
                    </p>
                    <p
                        class="mt-1.5 max-w-[28ch] text-xs leading-relaxed text-muted-foreground"
                    >
                        Ogni XML diventa una card con numero, data, importi e
                        cliente.
                    </p>
                </li>
                <li
                    class="px-0 py-6 md:px-6 md:py-7 md:first:pl-0 md:last:pr-0"
                >
                    <p
                        class="tabular text-2xl font-light text-muted-foreground/40"
                    >
                        02
                    </p>
                    <p class="mt-3 text-13 font-medium text-foreground">
                        Controllo calcoli
                    </p>
                    <p
                        class="mt-1.5 max-w-[28ch] text-xs leading-relaxed text-muted-foreground"
                    >
                        StudioFinance segnala se bollo o cassa divergono dallo
                        standard.
                    </p>
                </li>
                <li
                    class="px-0 py-6 md:px-6 md:py-7 md:first:pl-0 md:last:pr-0"
                >
                    <p
                        class="tabular text-2xl font-light text-muted-foreground/40"
                    >
                        03
                    </p>
                    <p class="mt-3 text-13 font-medium text-foreground">
                        Import in blocco
                    </p>
                    <p
                        class="mt-1.5 max-w-[28ch] text-xs leading-relaxed text-muted-foreground"
                    >
                        Confermi l'import e tutto entra. Clienti già a sistema
                        riconosciuti.
                    </p>
                </li>
            </ol>
        </section>

        <!-- ─── Summary + bulk actions ─── -->
        <!-- `mt-10 md:mt-14` matcha l'outer mt dell'empty state per
             coerenza visiva della distanza dalla dropzone in entrambi
             gli stati (con/senza fatture). -->
        <div
            v-if="previewList.length > 0"
            class="mt-10 mb-3 flex flex-wrap items-center justify-between gap-3 md:mt-14"
        >
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span class="kicker">Anteprime ({{ previewList.length }})</span>
                <span class="text-muted-foreground/40">·</span>
                <span
                    ><strong class="text-foreground">{{
                        counts.pronto
                    }}</strong>
                    pronte</span
                >
                <span v-if="counts.daConfermare > 0">
                    ·
                    <strong class="text-warning">{{
                        counts.daConfermare
                    }}</strong>
                    da confermare
                </span>
                <span v-if="counts.scartato > 0">
                    · <strong>{{ counts.scartato }}</strong> scartate
                </span>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="toggleExpandAll"
                >
                    <component
                        :is="anyExpanded ? PhCaretDoubleUp : PhCaretDoubleDown"
                        :size="14"
                    />
                    {{ anyExpanded ? 'Comprimi tutte' : 'Espandi tutte' }}
                </Button>
            </div>
        </div>

        <!-- ─── Lista anteprime: Accordion type="multiple" ─── -->
        <!-- Più card aperte insieme (architetto controlla N anteprime in
             parallelo). `v-model="openIds"` è l'unica fonte di verità
             dell'expand state — InvoicePreviewCard è un AccordionItem,
             non gestisce expand internamente. -->
        <Accordion v-model="openIds" type="multiple" class="space-y-3">
            <InvoicePreviewCard
                v-for="(preview, i) in previewList"
                :key="preview.id"
                v-model="previewList[i]"
                :clients="clients"
                @reupload-request="scrollToDropZone"
            />
        </Accordion>

        <!-- Submit errors riepilogati in fondo -->
        <div
            v-if="Object.keys(submitForm.errors).length > 0"
            class="mt-6 rounded-md border border-destructive/30 bg-destructive/5 p-4"
        >
            <p class="text-13 font-medium text-destructive">
                Errori di validazione su alcune fatture. Controlla i campi
                evidenziati.
            </p>
            <ul class="mt-2 text-xs text-destructive">
                <li v-for="(error, field) in submitForm.errors" :key="field">
                    {{ humanizeErrorField(field) }}: {{ error }}
                </li>
            </ul>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="resetDialogOpen"
        title="Svuotare le anteprime?"
        description="Perderai tutte le modifiche fatte fin qui. I file XML caricati non vengono eliminati dal tuo computer."
        confirm-label="Svuota"
        destructive
        @confirm="confirmReset"
    />
</template>
