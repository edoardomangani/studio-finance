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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableHead,
} from '@/components/ui/table';
import { formatDateIT, formatEUR } from '@/lib/format';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
import { index as clientsIndex } from '@/routes/clients';
import {
    create as invoicesCreate,
    show as invoiceShow,
} from '@/routes/invoices';
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

/* Iniziali per l'avatar: prima lettera delle prime due parole del nome. */
const initials = computed(() => {
    const words = props.client.name.trim().split(/\s+/);
    const letters =
        words.length >= 2
            ? words[0][0] + words[1][0]
            : props.client.name.slice(0, 2);

    return letters.toUpperCase();
});

/* Data dell'ultima fattura: invoices è ordinato DESC su issued_at. */
const lastInvoiceDate = computed<string | null>(
    () => props.invoices[0]?.issued_at ?? null,
);

/* Deep link "?client=X" gestito da InvoiceController@create. Wayfinder
   non genera helper per query string custom, quindi concateno a mano. */
const createInvoiceUrl = computed(
    () => `${invoicesCreate().url}?client=${props.client.id}`,
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

    <div class="mx-auto flex w-full max-w-[860px] flex-col gap-6 px-4 py-6 md:px-6">
        <!-- Identità: avatar a iniziali + nome + figure rapide -->
        <div class="flex items-center gap-3.5">
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-accent text-[15px] font-semibold text-accent-foreground"
                aria-hidden="true"
            >
                {{ initials }}
            </span>
            <div class="min-w-0">
                <h1
                    class="text-[22px] leading-tight font-medium tracking-[-0.02em] text-foreground"
                >
                    {{ client.name }}
                </h1>
                <div
                    class="mt-1 flex flex-wrap gap-x-3.5 gap-y-1 text-13 text-muted-foreground"
                >
                    <span v-if="client.vat_number"
                        >P.IVA
                        <span class="tabular font-medium text-foreground">{{
                            client.vat_number
                        }}</span></span
                    >
                    <span v-if="client.created_at"
                        >Cliente dal
                        <span class="tabular font-medium text-foreground">{{
                            formatDateIT(client.created_at)
                        }}</span></span
                    >
                </div>
            </div>
        </div>

        <!-- Riepilogo: banda KPI nello stile delle dashboard -->
        <div
            class="grid grid-cols-1 divide-y divide-border overflow-hidden rounded-lg border border-border bg-card sm:grid-cols-3 sm:divide-y-0"
        >
            <div class="px-5 py-4">
                <p class="kicker text-muted-foreground">Fatturato totale</p>
                <p
                    class="tabular mt-2 text-[1.625rem] leading-none font-medium tracking-tight text-foreground"
                >
                    {{ formatEUR(totalBilled) }}
                </p>
            </div>
            <div class="relative px-5 py-4">
                <span
                    class="absolute inset-y-3 left-0 hidden w-px bg-border sm:block"
                    aria-hidden="true"
                />
                <p class="kicker text-muted-foreground">Fatture</p>
                <p
                    class="tabular mt-2 text-[1.625rem] leading-none font-medium tracking-tight text-foreground"
                >
                    {{ invoices.length }}
                </p>
            </div>
            <div class="relative px-5 py-4">
                <span
                    class="absolute inset-y-3 left-0 hidden w-px bg-border sm:block"
                    aria-hidden="true"
                />
                <p class="kicker text-muted-foreground">Ultima fattura</p>
                <p
                    class="tabular mt-2 text-[1.625rem] leading-none font-medium tracking-tight"
                    :class="
                        lastInvoiceDate
                            ? 'text-foreground'
                            : 'text-muted-foreground'
                    "
                >
                    {{ formatDateIT(lastInvoiceDate) }}
                </p>
            </div>
        </div>

        <section>
            <h2 class="section-title mb-2.5">Anagrafica</h2>
            <div class="rounded-lg border border-border bg-card px-5 py-4">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3.5 md:grid-cols-2">
                    <div>
                        <dt class="text-xs text-muted-foreground">P.IVA</dt>
                        <dd class="tabular mt-0.5 text-13 text-foreground">
                            {{ client.vat_number ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Codice Fiscale
                        </dt>
                        <dd class="tabular mt-0.5 text-13 text-foreground">
                            {{ client.tax_code ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Ritenuta</dt>
                        <dd class="mt-0.5">
                            <Badge
                                v-if="client.bank_withholding"
                                variant="outline"
                            >
                                Sì
                            </Badge>
                            <span v-else class="text-13 text-muted-foreground"
                                >No</span
                            >
                        </dd>
                    </div>
                    <div v-if="client.created_at">
                        <dt class="text-xs text-muted-foreground">
                            Cliente dal
                        </dt>
                        <dd class="tabular mt-0.5 text-13 text-foreground">
                            {{ formatDateIT(client.created_at) }}
                        </dd>
                    </div>
                    <div v-if="client.notes" class="md:col-span-2">
                        <dt class="text-xs text-muted-foreground">Note</dt>
                        <dd
                            class="mt-0.5 text-13 leading-relaxed whitespace-pre-line text-foreground"
                        >
                            {{ client.notes }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section>
            <div class="mb-2.5 flex items-baseline justify-between gap-3">
                <h2 class="section-title">Storico fatturato</h2>
                <div class="flex items-baseline gap-4">
                    <span
                        v-if="invoices.length > 0"
                        class="tabular text-xs text-muted-foreground"
                    >
                        {{ invoices.length }}
                        {{ invoices.length === 1 ? 'fattura' : 'fatture' }} ·
                        {{ formatEUR(totalBilled) }}
                    </span>
                    <Button as-child size="sm" variant="link">
                        <Link :href="createInvoiceUrl">
                            <PhPlus weight="bold" class="size-3" />
                            Nuova fattura
                        </Link>
                    </Button>
                </div>
            </div>

            <DataTable v-if="invoices.length > 0">
                <DataTableHeader :has-actions="false">
                    <TableHead class="w-[110px]">Data</TableHead>
                    <TableHead class="w-[130px]">Numero</TableHead>
                    <TableHead class="text-right">Totale</TableHead>
                    <TableHead class="w-[100px] text-right">Ritenuta</TableHead>
                </DataTableHeader>
                <DataTableBody>
                    <DataTableRow
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        interactive
                        @click="openInvoice(invoice)"
                    >
                        <TableCell class="tabular text-muted-foreground">
                            {{ formatDateIT(invoice.issued_at) }}
                        </TableCell>
                        <TableCell class="tabular font-medium text-foreground">
                            <span
                                class="block max-w-[120px] truncate"
                                :title="invoice.number"
                            >
                                {{ invoice.number }}
                            </span>
                        </TableCell>
                        <TableCell
                            class="tabular text-right font-medium text-foreground"
                        >
                            {{ formatEUR(invoice.total) }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Badge
                                v-if="invoice.bank_withholding"
                                variant="outline"
                            >
                                Sì
                            </Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                    </DataTableRow>
                </DataTableBody>
            </DataTable>
            <p v-else class="text-13 text-muted-foreground">
                Nessuna fattura ancora. Crea la prima dal pulsante in alto.
            </p>
        </section>
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
