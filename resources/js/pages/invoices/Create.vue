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
import { PhFloppyDisk } from '@phosphor-icons/vue';
import { ref } from 'vue';
import InvoiceForm from '@/pages/invoices/InvoiceForm.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index as invoicesIndex } from '@/routes/invoices';
import type { ClientForPicker } from '@/types';

defineProps<{
    clients: ClientForPicker[];
    preselectedClientId: number | null;
}>();

setLayoutProps({
    pageTitle: 'Nuova fattura',
    pageCrumbs: [
        { label: 'Fatture', href: invoicesIndex().url },
        { label: 'Nuova' },
    ],
    subbar: false,
});

const invoiceForm = ref<InstanceType<typeof InvoiceForm> | null>(null);
</script>

<template>
    <Head title="Nuova fattura" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child variant="outline" size="sm">
            <Link :href="invoicesIndex().url">Annulla</Link>
        </Button>
        <Button
            type="submit"
            form="invoice-form"
            size="sm"
            :disabled="invoiceForm?.processing"
        >
            <Spinner v-if="invoiceForm?.processing" />
            <PhFloppyDisk v-else :size="14" weight="bold" />
            Aggiungi fattura
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] px-4 py-6 md:px-6">
        <InvoiceForm
            ref="invoiceForm"
            :clients="clients"
            :preselected-client-id="preselectedClientId"
        />
    </div>
</template>
