<script setup lang="ts">
/**
 * Settings — Expense items template (catalogo voci di spesa).
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova voce" vive nel topbar
 * via Teleport.
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { PhArchive, PhDotsThreeVertical, PhPencil, PhPlus } from '@phosphor-icons/vue';
import { ref } from 'vue';
import ExpenseItemController from '@/actions/App/Http/Controllers/Settings/ExpenseItemController';
import FamilyBadge from '@/components/FamilyBadge.vue';
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
import { formatEUR, formatPercent } from '@/lib/format';
import ExpenseItemFormDialog from '@/pages/settings/ExpenseItems/ExpenseItemFormDialog.vue';
import type { EnumOption, ExpenseItem } from '@/types';

defineProps<{
    expenseItems: ExpenseItem[];
    calculationTypes: EnumOption[];
    familyKinds: EnumOption[];
}>();

setLayoutProps({
    pageTitle: 'Voci di spesa',
    pageCrumbs: [{ label: 'Impostazioni' }, { label: 'Voci di spesa' }],
    subbar: false,
});

// Stesso testo per l'empty desktop (TableEmpty) e mobile (card).
const EMPTY_LABEL = 'Nessuna voce di spesa. Creane una dal pulsante in alto.';

// Voce in modifica (null = creazione); il form vive in ExpenseItemFormDialog.
const dialogOpen = ref(false);
const editing = ref<ExpenseItem | null>(null);

const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<ExpenseItem>((item) =>
        ExpenseItemController.destroy.url({ expenseItem: item.id }),
    );

function openNew(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(item: ExpenseItem): void {
    editing.value = item;
    dialogOpen.value = true;
}

function formatDefault(item: ExpenseItem): string {
    if (item.calculation_type === 'fixed_annual') {
        return item.default_amount !== null
            ? formatEUR(item.default_amount)
            : '—';
    }

    if (item.calculation_type === 'sum_of_bolli') {
        return 'derivata';
    }

    return item.default_rate !== null ? formatPercent(item.default_rate) : '—';
}
</script>

<template>
    <Head title="Voci di spesa" />

    <Teleport to="#page-topbar-actions" defer>
        <Button type="button" size="sm" @click="openNew">
            <PhPlus :size="14" weight="bold" />
            Nuova voce
        </Button>
    </Teleport>

    <DataTable container-class="hidden lg:block">
        <DataTableHeader>
            <TableHead class="w-[34%]">Nome</TableHead>
            <TableHead>Tipo calcolo</TableHead>
            <TableHead>Famiglia</TableHead>
            <TableHead class="text-right">Default</TableHead>
            <TableHead class="w-[120px] text-right">Minimo</TableHead>
            <TableHead class="w-[120px] text-right">Massimo</TableHead>
            <TableHead class="w-[80px] text-right">Stato</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="expenseItems.length === 0" :colspan="8">
                {{ EMPTY_LABEL }}
            </TableEmpty>
            <DataTableRow
                v-for="item in expenseItems"
                v-else
                :key="item.id"
                interactive
                :class="!item.active && 'opacity-60'"
                @click="openEdit(item)"
            >
                <TableCell class="font-medium text-foreground">
                    {{ item.name }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ item.calculation_type_label }}
                </TableCell>
                <TableCell>
                    <FamilyBadge :kind="item.kind" :name="item.family_name" />
                </TableCell>
                <TableCell class="tabular text-right text-foreground">
                    {{ formatDefault(item) }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    <span v-if="item.default_minimum !== null">{{
                        formatEUR(item.default_minimum)
                    }}</span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    <span v-if="item.default_maximum !== null">{{
                        formatEUR(item.default_maximum)
                    }}</span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="item.active ? 'default' : 'outline'">
                        {{ item.active ? 'Attiva' : 'Inattiva' }}
                    </Badge>
                </TableCell>
                <template #actions>
                    <DropdownMenuItem @select="openEdit(item)">
                        <PhPencil :size="14" />
                        Modifica
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        @select="askArchive(item)"
                    >
                        <PhArchive :size="14" />
                        Archivia
                    </DropdownMenuItem>
                </template>
            </DataTableRow>
        </DataTableBody>
    </DataTable>

    <!-- Mobile (<lg): card list. Tap → modifica (che è il dettaglio); kebab
         con Modifica/Archivia come da convenzione delle tabelle di settings. -->
    <div class="lg:hidden">
        <div
            v-if="expenseItems.length === 0"
            class="rounded-lg border border-dashed border-border p-6 text-center text-13 text-muted-foreground"
        >
            {{ EMPTY_LABEL }}
        </div>
        <ul v-else class="divide-y divide-border">
            <li
                v-for="item in expenseItems"
                :key="item.id"
                class="flex items-start gap-2"
                :class="!item.active && 'opacity-60'"
            >
                <button
                    type="button"
                    class="min-w-0 flex-1 py-3 text-left transition-colors active:bg-accent"
                    @click="openEdit(item)"
                >
                    <div class="flex items-center gap-2">
                        <span class="truncate font-medium text-foreground">
                            {{ item.name }}
                        </span>
                        <Badge
                            v-if="!item.active"
                            variant="outline"
                            class="shrink-0 py-0 text-2xs"
                        >
                            Inattiva
                        </Badge>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                        <FamilyBadge
                            :kind="item.kind"
                            :name="item.family_name"
                            class="shrink-0"
                        />
                        <span class="min-w-0 truncate">{{ item.calculation_type_label }}</span>
                        <span class="tabular ml-auto shrink-0 text-foreground">{{ formatDefault(item) }}</span>
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
                        <DropdownMenuItem @select="openEdit(item)">
                            <PhPencil :size="14" />
                            Modifica
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant="destructive"
                            @select="askArchive(item)"
                        >
                            <PhArchive :size="14" />
                            Archivia
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>
    </div>

    <ExpenseItemFormDialog
        v-model:open="dialogOpen"
        :item="editing"
        :calculation-types="calculationTypes"
        :family-kinds="familyKinds"
    />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la voce?"
        :description="
            archiveTarget
                ? `«${archiveTarget.name}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate; non sarà più proposta nei nuovi anni.`
                : undefined
        "
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
