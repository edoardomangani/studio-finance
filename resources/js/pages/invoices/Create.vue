<script setup lang="ts">
/**
 * Invoices — Create page.
 *
 * Form fattura manuale. Renderizza [[InvoiceForm]] come partial; topbar
 * actions (Annulla + Salva) sono Teleport che agganciano il form via
 * `form="invoice-form"` (HTML5 form attribute).
 *
 * Deep link `?client=X` pre-seleziona il cliente (utile da
 * `/clients/{id}` → "Nuova fattura").
 */
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { PhCheck, PhFloppyDisk } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import InvoiceForm from '@/pages/invoices/InvoiceForm.vue';
import { index as invoicesIndex } from '@/routes/invoices';
import type { ClientForPicker, Invoice } from '@/types';

const props = defineProps<{
    clients: ClientForPicker[];
    preselectedClientId: number | null;
    sourceInvoice: Invoice | null;
}>();

const isDuplicating = !!props.sourceInvoice;

setLayoutProps({
    pageTitle: isDuplicating ? 'Duplica fattura' : 'Nuova fattura',
    pageCrumbs: [
        { label: 'Fatture', href: invoicesIndex().url },
        { label: isDuplicating ? 'Duplica' : 'Nuova' },
    ],
    subbar: false,
});

const invoiceForm = ref<InstanceType<typeof InvoiceForm> | null>(null);
</script>

<template>
    <Head :title="isDuplicating ? 'Duplica fattura' : 'Nuova fattura'" />

    <Teleport to="#page-topbar-actions" defer>
        <!-- Annulla solo desktop: su mobile il back nel topbar fa da Annulla. -->
        <Button
            as-child
            variant="outline"
            size="sm"
            class="hidden lg:inline-flex"
        >
            <Link :href="invoicesIndex().url">Annulla</Link>
        </Button>
        <!-- Primario: mobile check 36, desktop con label. -->
        <Button
            type="submit"
            form="invoice-form"
            size="icon-md"
            class="lg:hidden"
            :disabled="invoiceForm?.processing"
            :aria-busy="invoiceForm?.processing"
            aria-label="Aggiungi fattura"
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
            Aggiungi fattura
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px]">
        <InvoiceForm
            ref="invoiceForm"
            :clients="clients"
            :preselected-client-id="preselectedClientId"
            :source-invoice="sourceInvoice"
        />
    </div>
</template>
