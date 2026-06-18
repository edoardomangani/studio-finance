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
import {
    PhArchive,
    PhDotsThreeVertical,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
            onError: () => toast.error('Archiviazione non riuscita. Riprova.'),
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

/* Deep link "?client=X" gestito da InvoiceController@create. Wayfinder
   non genera helper per query string custom, quindi concateno a mano. */
const createInvoiceUrl = computed(
    () => `${invoicesCreate().url}?client=${props.client.id}`,
);
</script>

<template>
    <Head :title="client.name" />

    <Teleport to="#page-topbar-actions" defer>
        <!-- Un solo kebab: Modifica · Archivia. -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    size="icon-md"
                    aria-label="Azioni"
                >
                    <PhDotsThreeVertical :size="16" weight="bold" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @select="editOpen = true">
                    <PhPencil :size="14" />
                    Modifica
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
    </Teleport>

    <div class="mx-auto flex w-full max-w-[860px] flex-col gap-6">
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
                    <span v-if="client.created_at" class="hidden sm:inline"
                        >Cliente dal
                        <span class="tabular font-medium text-foreground">{{
                            formatDateIT(client.created_at)
                        }}</span></span
                    >
                    <span
                        >Fatturato
                        <span class="tabular font-medium text-foreground">{{
                            formatEUR(totalBilled)
                        }}</span></span
                    >
                </div>
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
                <Button as-child size="sm" variant="link">
                    <Link :href="createInvoiceUrl">
                        <PhPlus :size="14" weight="bold" />
                        Nuova fattura
                    </Link>
                </Button>
            </div>

            <!-- Desktop / tablet-landscape (≥lg): tabella. -->
            <DataTable v-if="invoices.length > 0" class="hidden lg:block">
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

            <!-- Mobile (<lg): lista-card — numero · data a sx, totale a dx. -->
            <ul
                v-if="invoices.length > 0"
                class="divide-y divide-border lg:hidden"
            >
                <li v-for="invoice in invoices" :key="invoice.id">
                    <Link
                        :href="invoiceShow(invoice.id).url"
                        class="flex items-center gap-3 py-2.5 text-13 transition-colors active:bg-accent"
                    >
                        <span class="min-w-0 flex-1 truncate">
                            <span class="tabular font-medium text-foreground">{{
                                invoice.number
                            }}</span>
                            <span class="text-muted-foreground">
                                · {{ formatDateIT(invoice.issued_at) }}</span
                            >
                        </span>
                        <span class="tabular shrink-0 font-medium text-foreground">
                            {{ formatEUR(invoice.total) }}
                        </span>
                    </Link>
                </li>
            </ul>
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
