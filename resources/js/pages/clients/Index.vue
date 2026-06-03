<script setup lang="ts">
/**
 * Clients — Index page.
 *
 * Tabella paginata (50/pagina) con search reattiva su denominazione,
 * P.IVA e codice fiscale. CTA "Nuovo cliente" + ricerca nel topbar via
 * Teleport. Click su riga → /clients/{id}. Dropdown azioni per Modifica
 * (apre dialog inline) e Archivia (soft delete, confirm dialog).
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PhArchive,
    PhDotsThreeVertical,
    PhMagnifyingGlass,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { onUnmounted, ref, watch } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
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
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
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
        <Button type="button" size="sm" @click="newClientOpen = true">
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

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[40%]">Denominazione</TableHead>
                <TableHead>P.IVA</TableHead>
                <TableHead>Codice Fiscale</TableHead>
                <TableHead class="w-[120px] text-right">Ritenuta</TableHead>
                <TableHead class="w-[48px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="clients.data.length === 0" :colspan="5">
                {{
                    search
                        ? 'Nessun cliente trovato.'
                        : 'Nessun cliente. Creane uno dal pulsante in alto.'
                }}
            </TableEmpty>
            <TableRow
                v-for="client in clients.data"
                v-else
                :key="client.id"
                class="cursor-pointer transition-colors hover:bg-muted/40"
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
                <TableCell class="text-right" @click.stop>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Azioni cliente"
                            >
                                <PhDotsThreeVertical :size="14" weight="bold" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
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
                        </DropdownMenuContent>
                    </DropdownMenu>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <!-- Pagination footer: visibile solo se più di una pagina. Counter
         risultati a sinistra, paginator numerico (shadcn) a destra.
         Stack verticale sotto sm (iPhone SE ~320px): counter + paginator
         insieme escono dal viewport, quindi gap-2 col su mobile. -->
    <footer
        v-if="clients.last_page > 1"
        class="mt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular text-xs text-muted-foreground">
            {{ clients.from }}–{{ clients.to }} di {{ clients.total }}
        </span>
        <Pagination
            :total="clients.total"
            :items-per-page="clients.per_page"
            :page="clients.current_page"
            :sibling-count="1"
            show-edges
            @update:page="goToPage"
        >
            <PaginationContent v-slot="{ items }">
                <PaginationPrevious />
                <template v-for="(item, idx) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="item.value"
                        :value="item.value"
                        :is-active="item.value === clients.current_page"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis v-else :key="`e-${idx}`" :index="idx" />
                </template>
                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </footer>

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
