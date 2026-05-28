<script setup lang="ts">
/**
 * Clients — Show page.
 *
 * Dettaglio cliente: anagrafica + storico fatturato. Quest'ultimo è la
 * lista di tutte le fatture verso il cliente (DESC su issued_at), con
 * "Nuova fattura" pre-filtrato sul cliente come CTA della sezione.
 *
 * Topbar actions: Modifica (apre dialog) + Archivia (confirm dialog).
 */
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhPencil, PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
import FormSection from '@/components/forms/FormSection.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateIT, formatEUR } from '@/lib/format';
import { index as clientsIndex } from '@/routes/clients';
import { create as invoicesCreate, show as invoiceShow } from '@/routes/invoices';
import type { Client, InvoiceListItem } from '@/types';

const props = defineProps<{
    client: Client;
    invoices: InvoiceListItem[];
}>();

setLayoutProps({
    pageTitle: props.client.name,
    pageCrumbs: [
        { label: 'Clienti', href: clientsIndex().url },
        { label: props.client.name },
    ],
    subbar: false,
});

const editOpen = ref(false);
const archiveOpen = ref(false);
const archiveForm = useForm({});

function confirmArchive(): void {
    archiveForm.delete(
        ClientController.destroy.url({ client: props.client.id }),
        {
            onSuccess: () => {
                router.visit(clientsIndex().url);
            },
        },
    );
}

function openInvoice(invoice: InvoiceListItem): void {
    router.visit(invoiceShow(invoice.id).url);
}

/* Totale fatturato (somma di tutti i total) per chip in header sezione. */
const totalBilled = computed(() =>
    props.invoices.reduce((sum, i) => sum + i.total, 0),
);

/* Deep link "?client=X" gestito da InvoiceController@create. Wayfinder
   non genera helper per query string custom, quindi concateno a mano. */
const createInvoiceUrl = computed(() =>
    `${invoicesCreate().url}?client=${props.client.id}`,
);
</script>

<template>
    <Head :title="client.name" />

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
        <Button type="button" size="sm" @click="editOpen = true">
            <PhPencil :size="14" />
            Modifica
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] px-4 py-6 md:px-6">
        <FormSection first title="Anagrafica">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-3">
                <div class="md:col-span-3">
                    <dt class="text-xs text-muted-foreground">Denominazione</dt>
                    <dd class="mt-0.5 text-13 font-medium text-foreground">
                        {{ client.name }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">P.IVA</dt>
                    <dd class="mt-0.5 tabular text-13 text-foreground">
                        {{ client.vat_number ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Codice Fiscale</dt>
                    <dd class="mt-0.5 tabular text-13 text-foreground">
                        {{ client.tax_code ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Ritenuta 8%</dt>
                    <dd class="mt-0.5">
                        <Badge v-if="client.bank_withholding" variant="outline">
                            Sì
                        </Badge>
                        <span v-else class="text-13 text-muted-foreground">No</span>
                    </dd>
                </div>
                <div v-if="client.notes" class="md:col-span-3">
                    <dt class="text-xs text-muted-foreground">Note</dt>
                    <dd class="mt-0.5 text-13 leading-relaxed text-foreground whitespace-pre-line">
                        {{ client.notes }}
                    </dd>
                </div>
            </dl>
        </FormSection>

        <FormSection title="Storico fatturato">
            <template #actions>
                <span
                    v-if="invoices.length > 0"
                    class="tabular text-xs text-muted-foreground"
                >
                    {{ invoices.length }} {{ invoices.length === 1 ? 'fattura' : 'fatture' }} · {{ formatEUR(totalBilled) }}
                </span>
                <Button as-child size="sm" variant="outline">
                    <Link :href="createInvoiceUrl">
                        <PhPlus :size="14" weight="bold" />
                        Nuova fattura
                    </Link>
                </Button>
            </template>

            <Table v-if="invoices.length > 0" boxed>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[110px]">Data</TableHead>
                        <TableHead class="w-[130px]">Numero</TableHead>
                        <TableHead class="text-right">Totale</TableHead>
                        <TableHead class="w-[100px] text-right">Ritenuta</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="cursor-pointer transition-colors hover:bg-muted/40"
                        @click="openInvoice(invoice)"
                    >
                        <TableCell class="tabular text-muted-foreground">
                            {{ formatDateIT(invoice.issued_at) }}
                        </TableCell>
                        <TableCell class="tabular font-medium text-foreground">
                            <span class="block max-w-[120px] truncate" :title="invoice.number">
                                {{ invoice.number }}
                            </span>
                        </TableCell>
                        <TableCell class="tabular text-right font-medium text-foreground">
                            {{ formatEUR(invoice.total) }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Badge v-if="invoice.bank_withholding" variant="outline">
                                Sì
                            </Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <p v-else class="text-13 text-muted-foreground">
                Nessuna fattura ancora. Crea la prima dal pulsante in alto.
            </p>
        </FormSection>
    </div>

    <ClientFormDialog v-model:open="editOpen" :client="client" />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare il cliente?"
        :description="`«${client.name}» verrà nascosto dall'elenco. Le fatture esistenti restano invariate.`"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
