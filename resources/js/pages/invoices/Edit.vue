<script setup lang="ts">
/**
 * Invoices — Edit page.
 *
 * Thin wrapper su [[InvoiceForm]] in modalità modifica. Stessa shape della
 * Create, props `invoice` pre-popola tutti i campi e il form passa a PATCH.
 *
 * Numero univoco per anno: la self-exclusion dal check di unicità è
 * gestita server-side in [[UpdateInvoiceRequest]] (route model binding
 * fornisce l'invoice corrente al validator).
 */
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { PhCheck, PhFloppyDisk } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { originTrail, withOrigin } from '@/lib/origin';
import InvoiceForm from '@/pages/invoices/InvoiceForm.vue';
import { index as invoicesIndex, show as invoiceShow } from '@/routes/invoices';
import type { ClientForPicker, Invoice } from '@/types';

const props = defineProps<{
    invoice: Invoice;
    clients: ClientForPicker[];
}>();

// Origine = trail fino alla fattura (l'ultimo crumb è lo Show). Default
// deep-link: Fatture / N.
const origin = originTrail();
const trail =
    origin.length > 0
        ? origin
        : [
              { label: 'Fatture', href: invoicesIndex().url },
              {
                  label: props.invoice.number,
                  href: invoiceShow(props.invoice.id).url,
              },
          ];

// Annulla torna allo Show conservando l'origine dello Show (trail senza l'ultimo).
const cancelUrl = withOrigin(
    invoiceShow(props.invoice.id).url,
    trail.slice(0, -1),
);

setLayoutProps({
    pageTitle: `Modifica fattura ${props.invoice.number}`,
    pageCrumbs: [...trail, { label: 'Modifica' }],
    subbar: false,
});

const invoiceForm = ref<InstanceType<typeof InvoiceForm> | null>(null);
</script>

<template>
    <Head :title="`Modifica ${invoice.number}`" />

    <Teleport to="#page-topbar-actions" defer>
        <!-- Annulla solo desktop: su mobile il back nel topbar fa da Annulla. -->
        <Button
            as-child
            variant="outline"
            size="sm"
            class="hidden lg:inline-flex"
        >
            <Link :href="cancelUrl">Annulla</Link>
        </Button>
        <!-- Primario: mobile check 36, desktop con label. -->
        <Button
            type="submit"
            form="invoice-form"
            size="icon-md"
            class="lg:hidden"
            :disabled="invoiceForm?.processing"
            :aria-busy="invoiceForm?.processing"
            aria-label="Salva modifiche"
        >
            <Spinner v-if="invoiceForm?.processing" />
            <PhCheck v-else :size="18" weight="bold" />
        </Button>
        <Button
            type="submit"
            form="invoice-form"
            size="sm"
            class="hidden lg:inline-flex"
            :disabled="invoiceForm?.processing"
        >
            <Spinner v-if="invoiceForm?.processing" />
            <PhFloppyDisk v-else :size="14" weight="bold" />
            Salva modifiche
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px]">
        <InvoiceForm ref="invoiceForm" :clients="clients" :invoice="invoice" />
    </div>
</template>
