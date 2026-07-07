<script setup lang="ts">
/**
 * Settings — Expense items template (catalogo voci di spesa).
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova voce" vive nel topbar
 * via Teleport.
 */
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhDotsThreeVertical, PhPencil, PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ExpenseItemController from '@/actions/App/Http/Controllers/Settings/ExpenseItemController';
import FamilyBadge from '@/components/FamilyBadge.vue';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
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
import type { EnumOption, ExpenseCalculationType, ExpenseItem, ExpenseKind } from '@/types';

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

// Campi numerici `number | string`: input grezzi (no formattazione in edit), il
// vuoto è '' (Laravel ConvertEmptyStringsToNull → null lato server).
type FormPayload = {
    name: string;
    calculation_type: ExpenseCalculationType;
    kind: ExpenseKind;
    default_rate: number | string;
    default_minimum: number | string;
    default_maximum: number | string;
    default_amount: number | string;
    active: boolean;
    position: number;
};

const emptyForm = (): FormPayload => ({
    name: '',
    calculation_type: 'fixed_annual',
    kind: 'fixed',
    default_rate: '',
    default_minimum: '',
    default_maximum: '',
    default_amount: '',
    active: true,
    position: 0,
});

const dialogOpen = ref(false);
const editing = ref<ExpenseItem | null>(null);
const form = useForm<FormPayload>(emptyForm());

const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<ExpenseItem>((item) =>
        ExpenseItemController.destroy.url({ expenseItem: item.id }),
    );

const isPercentage = computed(
    () =>
        form.calculation_type === 'percentage_of_irpef_income' ||
        form.calculation_type === 'percentage_of_iva_revenue',
);
const isFixed = computed(() => form.calculation_type === 'fixed_annual');

const dialogTitle = computed(() =>
    editing.value ? 'Modifica voce di spesa' : 'Nuova voce di spesa',
);
const dialogDescription = computed(() =>
    editing.value
        ? 'Modifica i default del template. Le istanze già create negli anni esistenti restano invariate.'
        : 'Aggiungi una voce di spesa al catalogo. Verrà proposta nei prossimi anni che apri.',
);

function openNew(): void {
    editing.value = null;
    form.clearErrors();
    form.defaults(emptyForm());
    form.reset();
    dialogOpen.value = true;
}

function openEdit(item: ExpenseItem): void {
    editing.value = item;
    form.clearErrors();
    const next: FormPayload = {
        name: item.name,
        calculation_type: item.calculation_type,
        kind: item.kind,
        default_rate: item.default_rate ?? '',
        default_minimum: item.default_minimum ?? '',
        default_maximum: item.default_maximum ?? '',
        default_amount: item.default_amount ?? '',
        active: item.active,
        position: item.position,
    };
    form.defaults(next);
    form.reset();
    dialogOpen.value = true;
}

function onSubmit(): void {
    if (!isPercentage.value) {
        form.default_rate = '';
        form.default_minimum = '';
        form.default_maximum = '';
    }

    if (!isFixed.value) {
        form.default_amount = '';
    }

    if (editing.value) {
        form.patch(
            ExpenseItemController.update.url({ expenseItem: editing.value.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogOpen.value = false;
                },
            },
        );
    } else {
        form.post(ExpenseItemController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
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
                Nessuna voce di spesa. Creane una dal pulsante in alto.
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
            Nessuna voce di spesa. Creane una dal pulsante in alto.
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

    <ResponsiveDialog
        v-model:open="dialogOpen"
        :title="dialogTitle"
        :description="dialogDescription"
        submit-form="expense-item-form"
        :submit-label="editing ? 'Salva modifiche' : 'Aggiungi voce'"
        :submitting="form.processing"
    >
        <form id="expense-item-form" @submit.prevent="onSubmit">
            <FieldGroup>
                <FormField label="Nome" for="item-name" required>
                    <Input
                        id="item-name"
                        v-model="form.name"
                        placeholder="Es. Imposta sostitutiva"
                    />
                    <template v-if="form.errors.name" #error>{{
                        form.errors.name
                    }}</template>
                </FormField>

                <FormField label="Tipo di calcolo" for="item-type" required>
                    <Select v-model="form.calculation_type">
                        <SelectTrigger id="item-type" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in calculationTypes"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.calculation_type" #error>{{
                        form.errors.calculation_type
                    }}</template>
                </FormField>

                <FormField label="Famiglia" for="item-kind" required>
                    <Select v-model="form.kind">
                        <SelectTrigger id="item-kind" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in familyKinds"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.kind" #error>{{
                        form.errors.kind
                    }}</template>
                </FormField>

                <div
                    v-if="isPercentage"
                    class="grid grid-cols-1 gap-4 md:grid-cols-3"
                >
                    <FormField label="Aliquota (%)" for="item-rate">
                        <DecimalInput
                            id="item-rate"
                            v-model="form.default_rate"
                            :min="0"
                            :max="100"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_rate" #error>{{
                            form.errors.default_rate
                        }}</template>
                    </FormField>

                    <FormField label="Minimale (€)" for="item-min">
                        <DecimalInput
                            id="item-min"
                            v-model="form.default_minimum"
                            :min="0"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_minimum" #error>{{
                            form.errors.default_minimum
                        }}</template>
                    </FormField>

                    <FormField label="Massimale (€)" for="item-max">
                        <DecimalInput
                            id="item-max"
                            v-model="form.default_maximum"
                            :min="0"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_maximum" #error>{{
                            form.errors.default_maximum
                        }}</template>
                    </FormField>
                </div>

                <FormField
                    v-if="isFixed"
                    label="Importo annuale (€)"
                    for="item-amount"
                >
                    <DecimalInput
                        id="item-amount"
                        v-model="form.default_amount"
                        :min="0"
                        class="tabular text-right"
                        placeholder="0,00"
                    />
                    <template v-if="form.errors.default_amount" #error>{{
                        form.errors.default_amount
                    }}</template>
                </FormField>

                <Field orientation="horizontal">
                    <Switch id="item-active" v-model="form.active" />
                    <FieldLabel for="item-active" class="font-normal">
                        Voce attiva (proposta nei nuovi anni)
                    </FieldLabel>
                </Field>
            </FieldGroup>
        </form>
    </ResponsiveDialog>

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
