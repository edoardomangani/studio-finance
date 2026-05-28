<script setup lang="ts">
/**
 * Clients — Index page.
 *
 * Tabella clienti con search (denominazione/P.IVA/CF). CTA "Nuovo cliente"
 * in topbar via Teleport. Click su riga → /clients/{id}. Kebab menu per
 * archivia inline (soft delete).
 */
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    PhArchive,
    PhDotsThreeVertical,
    PhMagnifyingGlass,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { ref, watch } from 'vue';
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
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Client } from '@/types';
import { index as clientsIndex } from '@/routes/clients';

const props = defineProps<{
    clients: Client[];
    search: string;
}>();

setLayoutProps({
    pageTitle: 'Clienti',
    pageCrumbs: [{ label: 'Clienti' }],
    subbar: true,
});

const searchTerm = ref(props.search);

// Search reattiva: ricarica index quando l'utente cambia il testo (debounce
// implicito via Inertia preserveState/preserveScroll). Inertia mantiene
// l'URL aggiornato (`?search=...`).
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchTerm, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            clientsIndex().url,
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 250);
});

const newClientOpen = ref(false);

const editOpen = ref(false);
const editTarget = ref<Client | null>(null);

const archiveOpen = ref(false);
const archiveTarget = ref<Client | null>(null);
const archiveForm = useForm({});

function askEdit(client: Client): void {
    editTarget.value = client;
    editOpen.value = true;
}

function askArchive(client: Client): void {
    archiveTarget.value = client;
    archiveOpen.value = true;
}

function confirmArchive(): void {
    if (!archiveTarget.value) return;
    archiveForm.delete(
        ClientController.destroy.url({ client: archiveTarget.value.id }),
        {
            preserveScroll: true,
            onFinish: () => {
                archiveOpen.value = false;
                archiveTarget.value = null;
            },
        },
    );
}

function openClient(client: Client): void {
    router.visit(ClientController.show.url({ client: client.id }));
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
                <TableHead class="w-[120px] text-right">Ritenuta 8%</TableHead>
                <TableHead class="w-[60px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="clients.length === 0" :colspan="5">
                {{ search ? 'Nessun cliente trovato.' : 'Nessun cliente registrato. Crea il primo dal pulsante in alto.' }}
            </TableEmpty>
            <TableRow
                v-for="client in clients"
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

    <ClientFormDialog v-model:open="newClientOpen" :client="null" />
    <ClientFormDialog v-model:open="editOpen" :client="editTarget" />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare il cliente?"
        :description="archiveTarget
            ? `«${archiveTarget.name}» verrà nascosto dall'elenco. Le fatture esistenti restano invariate.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
