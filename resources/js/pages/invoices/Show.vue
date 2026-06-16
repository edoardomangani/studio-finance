<script setup lang="ts">
/**
 * Invoices — Show page (dossier, paper-receipt).
 *
 * Gerarchia visiva in 2 zone:
 * 1. **Hero band** — card a zone come cliente/anno: a sinistra l'identità
 *    (numero + data + cliente-link · P.IVA/CF · ritenuta default), divisa da
 *    una hairline dal valore-guida a destra (Totale + netto a incassare se
 *    c'è ritenuta).
 * 2. **Voci fattura** — box paper-receipt con tutte le righe + totale e (se
 *    ritenuta) sottrazione e netto. La metafora del documento chiarisce "ecco
 *    com'è composta la fattura".
 *
 * Niente sezione "Cliente" separata in fondo: tutto il meta vive nell'hero.
 *
 * Topbar actions: Modifica (link) + kebab (Duplica · Archivia).
 */
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    PhArchive,
    PhCopy,
    PhDotsThreeVertical,
    PhPencil,
} from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateIT, formatEUR } from '@/lib/format';
import { originTrail, withOrigin } from '@/lib/origin';
import { show as clientShow } from '@/routes/clients';
import {
    index as invoicesIndex,
    create as invoiceCreate,
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
            onError: () => toast.error('Archiviazione non riuscita. Riprova.'),
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
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    size="icon-lg"
                    aria-label="Azioni"
                >
                    <PhDotsThreeVertical :size="14" weight="bold" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem as-child>
                    <Link
                        :href="
                            invoiceCreate.url({ query: { from: invoice.id } })
                        "
                    >
                        <PhCopy :size="14" />
                        Duplica
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    variant="destructive"
                    @select="archiveOpen = true"
                >
                    <PhArchive :size="14" />
                    Archivia
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
        <Button as-child size="sm">
            <Link :href="editUrl">
                <PhPencil :size="14" />
                Modifica
            </Link>
        </Button>
    </Teleport>

    <div
        class="mx-auto flex w-full max-w-[820px] flex-col gap-[18px] py-8 md:px-6 md:py-10"
    >
        <!-- ─── HERO (banda a zone: identità · Totale) ─── -->
        <div
            class="flex flex-col gap-4 rounded-lg border border-border bg-card px-6 py-5 sm:flex-row sm:items-center sm:gap-0"
        >
            <!-- Identità + cliente meta -->
            <div class="flex min-w-0 flex-col gap-1.5 sm:flex-1 sm:pr-6">
                <h1
                    class="text-[22px] leading-tight font-medium tracking-[-0.02em] text-foreground"
                >
                    Fattura # {{ invoice.number }}
                    <span class="text-13 font-normal text-muted-foreground">
                        del {{ formatDateIT(invoice.issued_at) }}
                    </span>
                </h1>
                <!-- Cliente + P.IVA/CF su una riga + badge ritenuta default.
                     Link su denominazione porta a /clients/{id} (storico + edit). -->
                <div
                    class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-13"
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

            <!-- Hairline divisorio (solo desktop) -->
            <span
                class="hidden w-px self-stretch bg-border-soft sm:block"
                aria-hidden="true"
            />

            <!-- Outcome economico: Totale (+ netto a incassare se ritenuta) -->
            <div class="flex flex-col gap-1 sm:items-end sm:pl-6 sm:text-right">
                <p class="kicker text-muted-foreground">Totale fattura</p>
                <p
                    class="tabular text-3xl leading-none font-medium tracking-[-0.022em] text-foreground"
                >
                    {{ formatEUR(invoice.total) }}
                </p>
                <p
                    v-if="invoice.bank_withholding"
                    class="text-2xs text-muted-foreground"
                >
                    netto a incassare {{ formatEUR(netAmount) }}
                </p>
            </div>
        </div>

        <!-- ─── VOCI FATTURA (paper-receipt box) ─── -->
        <section>
            <h2 class="section-title mb-2.5">Voci fattura</h2>
            <div class="rounded-lg border border-border bg-card px-6 py-5">
                <dl class="flex flex-col gap-2.5 text-13">
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
