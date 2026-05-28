<script setup lang="ts">
/**
 * Settings — Expense items template (catalogo voci di spesa).
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova voce" vive nel topbar
 * via Teleport.
 */
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    PhArchive,
    PhDotsThreeVertical,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ExpenseItemController from '@/actions/App/Http/Controllers/Settings/ExpenseItemController';
import FormField from '@/components/forms/FormField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ConfirmDialog,
    Dialog,
    DialogBody,
    DialogContent,
    DialogStandardFooter,
    DialogStandardHeader,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
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
import type { EnumOption, ExpenseCalculationType, ExpenseItem } from '@/types';

defineProps<{
    expenseItems: ExpenseItem[];
    calculationTypes: EnumOption[];
}>();

setLayoutProps({
    pageTitle: 'Voci di spesa',
    pageCrumbs: [
        { label: 'Impostazioni' },
        { label: 'Voci di spesa' },
    ],
    subbar: false,
});

type FormPayload = {
    name: string;
    calculation_type: ExpenseCalculationType;
    default_rate: number | null;
    default_minimum: number | null;
    default_maximum: number | null;
    default_amount: number | null;
    active: boolean;
    position: number;
};

const emptyForm = (): FormPayload => ({
    name: '',
    calculation_type: 'fixed_annual',
    default_rate: null,
    default_minimum: null,
    default_maximum: null,
    default_amount: null,
    active: true,
    position: 0,
});

const dialogOpen = ref(false);
const editing = ref<ExpenseItem | null>(null);
const form = useForm<FormPayload>(emptyForm());

const { archiveOpen, archiveTarget, askArchive, confirmArchive } = useArchiveAction<ExpenseItem>(
    (item) => ExpenseItemController.destroy.url({ expenseItem: item.id }),
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
        default_rate: item.default_rate,
        default_minimum: item.default_minimum,
        default_maximum: item.default_maximum,
        default_amount: item.default_amount,
        active: item.active,
        position: item.position,
    };
    form.defaults(next);
    form.reset();
    dialogOpen.value = true;
}

function onSubmit(): void {
    if (!isPercentage.value) {
        form.default_rate = null;
        form.default_minimum = null;
        form.default_maximum = null;
    }
    if (!isFixed.value) {
        form.default_amount = null;
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
            ? `€ ${item.default_amount.toFixed(2)}`
            : '—';
    }
    if (item.calculation_type === 'sum_of_bolli') {
        return 'derivata';
    }
    return item.default_rate !== null
        ? `${item.default_rate.toFixed(2)} %`
        : '—';
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

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[40%]">Nome</TableHead>
                <TableHead>Tipo calcolo</TableHead>
                <TableHead class="text-right">Default</TableHead>
                <TableHead class="w-[80px] text-right">Stato</TableHead>
                <TableHead class="w-[48px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="expenseItems.length === 0" :colspan="5">
                Nessuna voce di spesa. Creane una dal pulsante in alto.
            </TableEmpty>
            <TableRow
                v-for="item in expenseItems"
                v-else
                :key="item.id"
                :class="['cursor-pointer transition-colors hover:bg-muted/40', !item.active && 'opacity-60']"
                @click="openEdit(item)"
            >
                <TableCell class="font-medium text-foreground">
                    {{ item.name }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ item.calculation_type_label }}
                </TableCell>
                <TableCell class="text-right tabular text-foreground">
                    {{ formatDefault(item) }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="item.active ? 'default' : 'outline'">
                        {{ item.active ? 'Attiva' : 'Inattiva' }}
                    </Badge>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Azioni voce"
                            >
                                <PhDotsThreeVertical :size="14" weight="bold" />
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
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <Dialog v-model:open="dialogOpen">
        <DialogContent size="default">
            <DialogStandardHeader
                :title="dialogTitle"
                :description="dialogDescription"
            />
            <DialogBody>
                <form id="expense-item-form" @submit.prevent="onSubmit">
                    <FieldGroup>
                        <FormField label="Nome" for="item-name" required>
                            <Input
                                id="item-name"
                                v-model="form.name"
                                placeholder="Es. Imposta sostitutiva"
                            />
                            <template v-if="form.errors.name" #error>{{ form.errors.name }}</template>
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
                            <template v-if="form.errors.calculation_type" #error>{{ form.errors.calculation_type }}</template>
                        </FormField>

                        <div v-if="isPercentage" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <FormField label="Aliquota (%)" for="item-rate">
                                <NumberField
                                    id="item-rate"
                                    v-model="form.default_rate"
                                    :min="0"
                                    :max="100"
                                    :step="0.5"
                                    :format-options="{ maximumFractionDigits: 2 }"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput class="tabular" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <template v-if="form.errors.default_rate" #error>{{ form.errors.default_rate }}</template>
                            </FormField>

                            <FormField label="Minimale (€)" for="item-min">
                                <NumberField
                                    id="item-min"
                                    v-model="form.default_minimum"
                                    :min="0"
                                    :step="50"
                                    :format-options="{ maximumFractionDigits: 2 }"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput class="tabular" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <template v-if="form.errors.default_minimum" #error>{{ form.errors.default_minimum }}</template>
                            </FormField>

                            <FormField label="Massimale (€)" for="item-max">
                                <NumberField
                                    id="item-max"
                                    v-model="form.default_maximum"
                                    :min="0"
                                    :step="1000"
                                    :format-options="{ maximumFractionDigits: 2 }"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput class="tabular" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <template v-if="form.errors.default_maximum" #error>{{ form.errors.default_maximum }}</template>
                            </FormField>
                        </div>

                        <FormField v-if="isFixed" label="Importo annuale (€)" for="item-amount">
                            <NumberField
                                id="item-amount"
                                v-model="form.default_amount"
                                :min="0"
                                :step="1"
                                :format-options="{ maximumFractionDigits: 2 }"
                            >
                                <NumberFieldContent>
                                    <NumberFieldDecrement />
                                    <NumberFieldInput class="tabular" />
                                    <NumberFieldIncrement />
                                </NumberFieldContent>
                            </NumberField>
                            <template v-if="form.errors.default_amount" #error>{{ form.errors.default_amount }}</template>
                        </FormField>

                        <Field orientation="horizontal">
                            <Switch id="item-active" v-model="form.active" />
                            <FieldLabel for="item-active" class="font-normal">
                                Voce attiva (proposta nei nuovi anni)
                            </FieldLabel>
                        </Field>
                    </FieldGroup>
                </form>
            </DialogBody>
            <DialogStandardFooter>
                <Button
                    type="submit"
                    form="expense-item-form"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ editing ? 'Salva modifiche' : 'Aggiungi voce' }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la voce?"
        :description="archiveTarget
            ? `«${archiveTarget.name}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate; non sarà più proposta nei nuovi anni.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
