<script setup lang="ts">
/**
 * Settings — Recurring deadlines template (scadenze tipo).
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova scadenza" vive nel topbar
 * via Teleport.
 *
 * Kind `payment` richiede expense_item_id + expense_year_offset.
 * Kind `fulfillment` non ha collegamenti.
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { PhArchive, PhDotsThreeVertical, PhPencil, PhPlus } from '@phosphor-icons/vue';
import { ref } from 'vue';
import RecurringDeadlineController from '@/actions/App/Http/Controllers/Settings/RecurringDeadlineController';
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
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { DEADLINE_KIND_META } from '@/pages/deadlines/kindMeta';
import RecurringDeadlineFormDialog from '@/pages/settings/RecurringDeadlines/RecurringDeadlineFormDialog.vue';
import type { EnumOption, RecurringDeadline } from '@/types';

defineProps<{
    recurringDeadlines: RecurringDeadline[];
    kinds: EnumOption[];
    dueYearOffsets: EnumOption[];
    expenseYearOffsets: EnumOption[];
    quotaTypes: EnumOption[];
    activeExpenseItems: { id: number; name: string }[];
}>();

setLayoutProps({
    pageTitle: 'Scadenze tipo',
    pageCrumbs: [{ label: 'Impostazioni' }, { label: 'Scadenze tipo' }],
    subbar: false,
});

// Stesso testo per l'empty desktop (TableEmpty) e mobile (card).
const EMPTY_LABEL = 'Nessuna scadenza tipo. Creane una dal pulsante in alto.';

// Scadenza in modifica (null = creazione); il form vive in
// RecurringDeadlineFormDialog.
const dialogOpen = ref(false);
const editing = ref<RecurringDeadline | null>(null);

const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<RecurringDeadline>((deadline) =>
        RecurringDeadlineController.destroy.url({
            recurringDeadline: deadline.id,
        }),
    );

function openNew(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(deadline: RecurringDeadline): void {
    editing.value = deadline;
    dialogOpen.value = true;
}

function formatDate(deadline: RecurringDeadline): string {
    const dd = String(deadline.day).padStart(2, '0');
    const mm = String(deadline.month).padStart(2, '0');

    return deadline.due_year_offset === 'next'
        ? `${dd}/${mm} (N+1)`
        : `${dd}/${mm}`;
}
</script>

<template>
    <Head title="Scadenze tipo" />

    <Teleport to="#page-topbar-actions" defer>
        <Button type="button" size="sm" @click="openNew">
            <PhPlus :size="14" weight="bold" />
            Nuova scadenza
        </Button>
    </Teleport>

    <DataTable container-class="hidden lg:block">
        <DataTableHeader>
            <TableHead class="w-[90px]">Data</TableHead>
            <TableHead class="w-[35%]">Nome</TableHead>
            <TableHead>Tipo</TableHead>
            <TableHead>Voce collegata</TableHead>
            <TableHead>Anno spesa</TableHead>
            <TableHead>Tipo quota</TableHead>
            <TableHead class="w-[80px] text-right">Stato</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="recurringDeadlines.length === 0" :colspan="8">
                {{ EMPTY_LABEL }}
            </TableEmpty>
            <DataTableRow
                v-for="deadline in recurringDeadlines"
                v-else
                :key="deadline.id"
                interactive
                :class="!deadline.active && 'opacity-60'"
                @click="openEdit(deadline)"
            >
                <TableCell class="tabular text-foreground">
                    {{ formatDate(deadline) }}
                </TableCell>
                <TableCell class="font-medium text-foreground">
                    {{ deadline.name }}
                </TableCell>
                <TableCell>
                    <Badge
                        :variant="DEADLINE_KIND_META[deadline.kind].variant"
                        class="gap-1"
                    >
                        <component
                            :is="DEADLINE_KIND_META[deadline.kind].icon"
                            :size="12"
                        />
                        {{ deadline.kind_label }}
                    </Badge>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ deadline.expense_item_name ?? '—' }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{
                        deadline.kind === 'payment'
                            ? deadline.expense_year_offset_label
                            : '—'
                    }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ deadline.quota_type_label ?? '—' }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="deadline.active ? 'default' : 'outline'">
                        {{ deadline.active ? 'Attiva' : 'Inattiva' }}
                    </Badge>
                </TableCell>
                <template #actions>
                    <DropdownMenuItem @select="openEdit(deadline)">
                        <PhPencil :size="14" />
                        Modifica
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        @select="askArchive(deadline)"
                    >
                        <PhArchive :size="14" />
                        Archivia
                    </DropdownMenuItem>
                </template>
            </DataTableRow>
        </DataTableBody>
    </DataTable>

    <!-- Mobile (<lg): card list. Tap → modifica (il dettaglio); kebab
         Modifica/Archivia. Data a destra come valore; anno spesa e tipo quota
         restano nella modifica. -->
    <div class="lg:hidden">
        <div
            v-if="recurringDeadlines.length === 0"
            class="rounded-lg border border-dashed border-border p-6 text-center text-13 text-muted-foreground"
        >
            {{ EMPTY_LABEL }}
        </div>
        <ul v-else class="divide-y divide-border">
            <li
                v-for="deadline in recurringDeadlines"
                :key="deadline.id"
                class="flex items-start gap-2"
                :class="!deadline.active && 'opacity-60'"
            >
                <button
                    type="button"
                    class="min-w-0 flex-1 py-3 text-left transition-colors active:bg-accent"
                    @click="openEdit(deadline)"
                >
                    <div class="flex items-center gap-2">
                        <span class="truncate font-medium text-foreground">
                            {{ deadline.name }}
                        </span>
                        <Badge
                            v-if="!deadline.active"
                            variant="outline"
                            class="shrink-0 py-0 text-2xs"
                        >
                            Inattiva
                        </Badge>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                        <Badge
                            :variant="DEADLINE_KIND_META[deadline.kind].variant"
                            class="shrink-0 gap-1 py-0 text-2xs"
                        >
                            <component
                                :is="DEADLINE_KIND_META[deadline.kind].icon"
                                :size="11"
                            />
                            {{ deadline.kind_label }}
                        </Badge>
                        <span
                            v-if="deadline.expense_item_name"
                            class="min-w-0 truncate"
                        >
                            {{ deadline.expense_item_name }}
                        </span>
                        <span class="tabular ml-auto shrink-0 text-foreground">
                            {{ formatDate(deadline) }}
                        </span>
                    </div>
                </button>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-md"
                            class="mt-1.5 shrink-0"
                            aria-label="Azioni"
                        >
                            <PhDotsThreeVertical :size="18" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @select="openEdit(deadline)">
                            <PhPencil :size="14" />
                            Modifica
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant="destructive"
                            @select="askArchive(deadline)"
                        >
                            <PhArchive :size="14" />
                            Archivia
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>
    </div>

    <RecurringDeadlineFormDialog
        v-model:open="dialogOpen"
        :deadline="editing"
        :kinds="kinds"
        :due-year-offsets="dueYearOffsets"
        :expense-year-offsets="expenseYearOffsets"
        :quota-types="quotaTypes"
        :active-expense-items="activeExpenseItems"
    />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la scadenza?"
        :description="
            archiveTarget
                ? `«${archiveTarget.name}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate.`
                : undefined
        "
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
