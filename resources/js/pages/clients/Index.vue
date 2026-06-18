<script setup lang="ts">
/**
 * Clients — Index page.
 *
 * Tabella paginata (50/pagina) con search reattiva su denominazione,
 * P.IVA e codice fiscale. CTA "Nuovo cliente" + ricerca nel topbar via
 * Teleport. Click su riga → /clients/{id}. Dropdown azioni per Modifica
 * (apre dialog inline) e Archivia (soft delete, confirm dialog).
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PhArchive,
    PhMagnifyingGlass,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { onUnmounted, ref, watch } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTablePagination,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { useInitials } from '@/composables/useInitials';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
import { index as clientsIndex } from '@/routes/clients';
import type { Client, PaginatedList } from '@/types';

const props = defineProps<{
    clients: PaginatedList<Client>;
    search: string;
}>();

setLayoutProps({
    pageTitle: 'Clienti',
    pageCrumbs: [{ label: 'Clienti' }],
    subbar: true,
});

const { getInitials } = useInitials();

const searchTerm = ref(props.search);

// Search reattiva con debounce 250ms. Inertia preserveState/preserveScroll
// + replace per non sporcare la cronologia. Cleanup del timeout su unmount
// per evitare callback dopo che il componente è smontato.
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            clientsIndex().url,
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 250);
});

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const newClientOpen = ref(false);

const editOpen = ref(false);
const editTarget = ref<Client | null>(null);

const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<Client>((client) =>
        ClientController.destroy.url({ client: client.id }),
    );

function askEdit(client: Client): void {
    editTarget.value = client;
    editOpen.value = true;
}

function openClient(client: Client): void {
    router.visit(ClientController.show.url({ client: client.id }));
}

function goToPage(page: number): void {
    router.get(
        clientsIndex().url,
        { search: props.search || undefined, page },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Clienti" />

    <Teleport to="#page-topbar-actions" defer>
        <!-- Nuovo cliente: mobile icon 36, desktop con label. -->
        <Button
            type="button"
            size="icon-md"
            class="lg:hidden"
            aria-label="Nuovo cliente"
            @click="newClientOpen = true"
        >
            <PhPlus :size="16" weight="bold" />
        </Button>
        <Button
            type="button"
            size="sm"
            class="hidden lg:inline-flex"
            @click="newClientOpen = true"
        >
            <PhPlus :size="14" weight="bold" />
            Nuovo cliente
        </Button>
    </Teleport>

    <Teleport to="#page-topbar-search" defer>
        <InputGroup>
            <InputGroupAddon>
                <PhMagnifyingGlass :size="14" />
            </InputGroupAddon>
            <InputGroupInput
                v-model="searchTerm"
                type="search"
                placeholder="Cerca per denominazione, P.IVA o CF…"
            />
        </InputGroup>
    </Teleport>

    <!-- Desktop / tablet-landscape (≥lg): tabella completa. -->
    <div class="hidden lg:block">
    <DataTable>
        <DataTableHeader>
            <TableHead class="w-[40%]">Denominazione</TableHead>
            <TableHead>P.IVA</TableHead>
            <TableHead>Codice Fiscale</TableHead>
            <TableHead class="w-[120px] text-right">Ritenuta</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="clients.data.length === 0" :colspan="5">
                {{
                    search
                        ? 'Nessun cliente trovato.'
                        : 'Nessun cliente. Creane uno dal pulsante in alto.'
                }}
            </TableEmpty>
            <DataTableRow
                v-for="client in clients.data"
                v-else
                :key="client.id"
                interactive
                @click="openClient(client)"
            >
                <TableCell class="font-medium text-foreground">
                    {{ client.name }}
                </TableCell>
                <TableCell class="tabular text-muted-foreground">
                    {{ client.vat_number ?? '—' }}
                </TableCell>
                <TableCell class="tabular text-muted-foreground">
                    {{ client.tax_code ?? '—' }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge v-if="client.bank_withholding" variant="outline">
                        Sì
                    </Badge>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <template #actions>
                    <DropdownMenuItem @select="askEdit(client)">
                        <PhPencil :size="14" />
                        Modifica
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        @select="askArchive(client)"
                    >
                        <PhArchive :size="14" />
                        Archivia
                    </DropdownMenuItem>
                </template>
            </DataTableRow>
        </DataTableBody>
    </DataTable>
    </div>

    <!-- Mobile / tablet-portrait (<lg): lista a card — monogramma + nome,
         sotto P.IVA · CF. Niente kebab: tap → dettaglio cliente (azioni lì). -->
    <div class="lg:hidden">
        <div
            v-if="clients.data.length === 0"
            class="py-10 text-center text-sm text-muted-foreground"
        >
            {{
                search
                    ? 'Nessun cliente trovato.'
                    : 'Nessun cliente. Creane uno dal pulsante in alto.'
            }}
        </div>
        <ul v-else class="divide-y divide-border">
            <li v-for="client in clients.data" :key="client.id">
                <Link
                    :href="ClientController.show.url({ client: client.id })"
                    class="flex items-center gap-3 py-2.5 transition-colors active:bg-accent"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent text-xs font-semibold text-accent-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(client.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-foreground">
                            {{ client.name }}
                        </div>
                        <div
                            v-if="client.vat_number || client.tax_code"
                            class="mt-0.5 truncate text-xs text-muted-foreground"
                        >
                            <span v-if="client.vat_number"
                                >P.IVA
                                <span class="tabular font-medium text-foreground">{{
                                    client.vat_number
                                }}</span></span
                            >
                            <template v-if="client.vat_number && client.tax_code">
                                ·
                            </template>
                            <span v-if="client.tax_code"
                                >CF
                                <span class="tabular font-medium text-foreground">{{
                                    client.tax_code
                                }}</span></span
                            >
                        </div>
                    </div>
                </Link>
            </li>
        </ul>
    </div>

    <!-- Pagination: componente canonico del design system, fuori dal box. -->
    <DataTablePagination
        v-if="clients.last_page > 1"
        :page="clients.current_page"
        :per-page="clients.per_page"
        :total="clients.total"
        @update:page="goToPage"
    />

    <ClientFormDialog v-model:open="newClientOpen" :client="null" />
    <ClientFormDialog v-model:open="editOpen" :client="editTarget" />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare il cliente?"
        :description="
            archiveTarget
                ? `«${archiveTarget.name}» verrà nascosto dall'elenco. Le fatture esistenti restano invariate.`
                : undefined
        "
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
