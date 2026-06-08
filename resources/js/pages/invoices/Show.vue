<script setup lang="ts">
/**
 * Invoices — Show page (dossier, paper-receipt).
 *
 * Gerarchia visiva in 2 zone:
 * 1. **Hero header** — identità (numero grande + cliente come link + data)
 *    con meta inline (P.IVA o CF · ritenuta default · storico). A destra
 *    outcome economico (totale + eventuale ritenuta + netto).
 * 2. **Voci fattura** — box paper-receipt sobrio (border + bg-muted/30)
 *    con tutte le righe + totale interno + (se ritenuta) sottrazione e
 *    netto. La metafora del documento dentro la pagina chiarisce "ecco
 *    com'è composta la fattura".
 *
 * Niente sezione "Cliente" separata in fondo: tutto il meta vive nel hero.
 *
 * Topbar actions: Modifica (link) + Archivia (confirm).
 */
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhPencil } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { formatDateIT, formatEUR } from '@/lib/format';
import { originTrail, withOrigin } from '@/lib/origin';
import { show as clientShow } from '@/routes/clients';
import {
    index as invoicesIndex,
    edit as invoiceEdit,
    show as invoiceShow,
} from '@/routes/invoices';
import type { Invoice } from '@/types';

const props = defineProps<{
    invoice: Invoice;
}>();

// Punto d'accesso: se assente (deep-link) → fallback alla lista Fatture.
const origin = originTrail();
const prefix =
    origin.length > 0
        ? origin
        : [{ label: 'Fatture', href: invoicesIndex().url }];

// Modifica eredita l'origine + questa fattura; l'archivio torna alla superficie
// di provenienza (ultimo crumb), non per forza alla lista.
const editUrl = withOrigin(invoiceEdit(props.invoice.id).url, [
    ...prefix,
    { label: props.invoice.number, href: invoiceShow(props.invoice.id).url },
]);
const backUrl = prefix[prefix.length - 1].href;

setLayoutProps({
    pageTitle: `Fattura ${props.invoice.number}`,
    pageCrumbs: [...prefix, { label: props.invoice.number }],
    subbar: false,
});

const archiveOpen = ref(false);
const archiveForm = useForm({});

function confirmArchive(): void {
    archiveForm.delete(
        InvoiceController.destroy.url({ invoice: props.invoice.id }),
        {
            onSuccess: () => {
                router.visit(backUrl);
            },
        },
    );
}

const netAmount = computed(
    () => props.invoice.total - props.invoice.withholding_amount,
);
</script>

<template>
    <Head :title="`Fattura ${invoice.number}`" />

    <Teleport to="#page-topbar-actions" defer>
        <Button
            type="button"
            variant="outline"
            size="sm"
            @click="archiveOpen = true"
        >
            <PhArchive :size="14" />
            Archivia
        </Button>
        <Button as-child size="sm">
            <Link :href="editUrl">
                <PhPencil :size="14" />
                Modifica
            </Link>
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] py-8 md:px-6 md:py-10">
        <!-- ─── HERO ─── -->
        <header
            class="grid grid-cols-1 gap-y-6 border-b border-border pb-8 md:grid-cols-[1fr_auto] md:gap-x-10"
        >
            <!-- Identità + cliente meta a sx -->
            <div class="min-w-0 space-y-2">
                <h1 class="tabular text-2xl font-medium text-foreground">
                    Fattura # {{ invoice.number }}
                    <span
                        class="ml-1 text-sm font-normal text-muted-foreground"
                    >
                        del {{ formatDateIT(invoice.issued_at) }}
                    </span>
                </h1>
                <!-- Cliente + P.IVA/CF su una riga + badge ritenuta default.
                     Link su denominazione porta a /clients/{id} (storico + edit). -->
                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-2 text-13"
                >
                    <Link
                        :href="clientShow(invoice.client.id).url"
                        class="font-medium text-foreground underline-offset-2 hover:underline"
                    >
                        {{ invoice.client.name }}
                    </Link>
                    <span
                        v-if="
                            invoice.client.vat_number || invoice.client.tax_code
                        "
                        aria-hidden="true"
                        class="text-muted-foreground/40"
                        >·</span
                    >
                    <span
                        v-if="invoice.client.vat_number"
                        class="tabular text-xs text-muted-foreground"
                    >
                        P.IVA {{ invoice.client.vat_number }}
                    </span>
                    <span
                        v-else-if="invoice.client.tax_code"
                        class="tabular text-xs text-muted-foreground"
                    >
                        CF {{ invoice.client.tax_code }}
                    </span>
                    <Badge
                        v-if="invoice.client.bank_withholding"
                        variant="white"
                        class="text-2xs"
                    >
                        Ritenuta di default
                    </Badge>
                </div>
            </div>

            <!-- Outcome economico a dx -->
            <div class="text-right">
                <p class="kicker text-muted-foreground">Totale fattura</p>
                <p class="tabular text-3xl font-medium text-foreground">
                    {{ formatEUR(invoice.total) }}
                </p>
            </div>
        </header>

        <!-- ─── VOCI FATTURA (paper-receipt box) ─── -->
        <section class="mt-8">
            <h2 class="kicker mb-3 text-muted-foreground">Voci fattura</h2>
            <div class="rounded-md border border-border bg-card px-6 py-5">
                <dl class="space-y-2 text-13">
                    <div class="flex items-baseline justify-between">
                        <dt class="text-muted-foreground">Imponibile</dt>
                        <dd class="tabular text-foreground">
                            {{ formatEUR(invoice.amount) }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt class="text-muted-foreground">
                            Bollo
                            <span
                                v-if="!invoice.stamp_charged_to_client"
                                class="text-2xs"
                                >(a tuo carico, fuori totale)</span
                            >
                        </dt>
                        <dd
                            class="tabular"
                            :class="
                                invoice.stamp_charged_to_client
                                    ? 'text-foreground'
                                    : 'text-muted-foreground line-through'
                            "
                        >
                            {{ formatEUR(invoice.stamp_amount) }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt class="text-muted-foreground">
                            Cassa Inarcassa 4%
                        </dt>
                        <dd class="tabular text-foreground">
                            {{ formatEUR(invoice.inarcassa_amount) }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <dt class="text-muted-foreground">
                            Art.15
                            <span class="text-2xs">(spese anticipate)</span>
                        </dt>
                        <dd class="tabular text-foreground">
                            {{ formatEUR(invoice.art_15_amount) }}
                        </dd>
                    </div>
                    <div
                        class="flex items-baseline justify-between border-t border-border pt-2"
                    >
                        <dt class="font-medium text-foreground">Totale</dt>
                        <dd class="tabular font-medium text-foreground">
                            {{ formatEUR(invoice.total) }}
                        </dd>
                    </div>
                    <template v-if="invoice.bank_withholding">
                        <div class="flex items-baseline justify-between">
                            <dt class="text-muted-foreground">
                                Ritenuta bancaria
                            </dt>
                            <dd class="tabular text-muted-foreground">
                                − {{ formatEUR(invoice.withholding_amount) }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between border-t border-border-soft pt-2"
                        >
                            <dt class="font-medium text-foreground">
                                Netto a incassare
                            </dt>
                            <dd class="tabular font-medium text-foreground">
                                {{ formatEUR(netAmount) }}
                            </dd>
                        </div>
                    </template>
                </dl>
            </div>
        </section>
    </div>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la fattura?"
        :description="`«${invoice.number}» verrà nascosta dall'elenco. I dati restano per i calcoli storici.`"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
