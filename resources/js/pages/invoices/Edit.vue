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
import { PhFloppyDisk } from '@phosphor-icons/vue';
import { ref } from 'vue';
import InvoiceForm from '@/pages/invoices/InvoiceForm.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index as invoicesIndex, show as invoiceShow } from '@/routes/invoices';
import type { ClientForPicker, Invoice } from '@/types';

const props = defineProps<{
    invoice: Invoice;
    clients: ClientForPicker[];
}>();

setLayoutProps({
    pageTitle: `Modifica fattura ${props.invoice.number}`,
    pageCrumbs: [
        { label: 'Fatture', href: invoicesIndex().url },
        { label: props.invoice.number, href: invoiceShow(props.invoice.id).url },
        { label: 'Modifica' },
    ],
    subbar: false,
});

const invoiceForm = ref<InstanceType<typeof InvoiceForm> | null>(null);
</script>

<template>
    <Head :title="`Modifica ${invoice.number}`" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child variant="outline" size="sm">
            <Link :href="invoiceShow(invoice.id).url">Annulla</Link>
        </Button>
        <Button
            type="submit"
            form="invoice-form"
            size="sm"
            :disabled="invoiceForm?.processing"
        >
            <Spinner v-if="invoiceForm?.processing" />
            <PhFloppyDisk v-else :size="14" weight="bold" />
            Salva modifiche
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] px-4 py-6 md:px-6">
        <InvoiceForm
            ref="invoiceForm"
            :clients="clients"
            :invoice="invoice"
        />
    </div>
</template>
