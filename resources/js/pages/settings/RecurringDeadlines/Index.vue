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
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    PhArchive,
    PhDotsThreeVertical,
    PhPencil,
    PhPlus,
} from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import RecurringDeadlineController from '@/actions/App/Http/Controllers/Settings/RecurringDeadlineController';
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
import type {
    DeadlineKind,
    DueYearOffset,
    EnumOption,
    ExpenseYearOffset,
    QuotaType,
    RecurringDeadline,
} from '@/types';

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
    pageCrumbs: [
        { label: 'Impostazioni' },
        { label: 'Scadenze tipo' },
    ],
    subbar: false,
});

type FormPayload = {
    name: string;
    day: number;
    month: number;
    kind: DeadlineKind;
    expense_item_id: number | null;
    due_year_offset: DueYearOffset;
    expense_year_offset: ExpenseYearOffset;
    quota_type: QuotaType | null;
    active: boolean;
};

const emptyForm = (): FormPayload => ({
    name: '',
    day: 30,
    month: 6,
    kind: 'payment',
    expense_item_id: null,
    due_year_offset: 'current',
    expense_year_offset: 'current',
    quota_type: null,
    active: true,
});

const MONTH_LABELS = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre',
];

const dialogOpen = ref(false);
const editing = ref<RecurringDeadline | null>(null);
const form = useForm<FormPayload>(emptyForm());

const { archiveOpen, archiveTarget, askArchive, confirmArchive } = useArchiveAction<RecurringDeadline>(
    (deadline) => RecurringDeadlineController.destroy.url({ recurringDeadline: deadline.id }),
);

const isPayment = computed(() => form.kind === 'payment');

const dialogTitle = computed(() =>
    editing.value ? 'Modifica scadenza tipo' : 'Nuova scadenza tipo',
);
const dialogDescription = computed(() =>
    editing.value
        ? 'Modifica i parametri della scadenza. Le istanze già create negli anni esistenti restano invariate.'
        : 'Aggiungi una scadenza ricorrente al catalogo. Verrà generata nei prossimi anni che apri.',
);

watch(
    () => form.kind,
    (nextKind) => {
        if (nextKind === 'fulfillment') {
            form.expense_item_id = null;
            form.quota_type = null;
        }
    },
);

function openNew(): void {
    editing.value = null;
    form.clearErrors();
    form.defaults(emptyForm());
    form.reset();
    dialogOpen.value = true;
}

function openEdit(deadline: RecurringDeadline): void {
    editing.value = deadline;
    form.clearErrors();
    const next: FormPayload = {
        name: deadline.name,
        day: deadline.day,
        month: deadline.month,
        kind: deadline.kind,
        expense_item_id: deadline.expense_item_id,
        due_year_offset: deadline.due_year_offset,
        expense_year_offset: deadline.expense_year_offset,
        quota_type: deadline.quota_type,
        active: deadline.active,
    };
    form.defaults(next);
    form.reset();
    dialogOpen.value = true;
}

function onSubmit(): void {
    if (editing.value) {
        form.patch(
            RecurringDeadlineController.update.url({
                recurringDeadline: editing.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogOpen.value = false;
                },
            },
        );
    } else {
        form.post(RecurringDeadlineController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
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

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[90px]">Data</TableHead>
                <TableHead class="w-[35%]">Nome</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead>Voce collegata</TableHead>
                <TableHead>Anno spesa</TableHead>
                <TableHead class="w-[80px] text-right">Stato</TableHead>
                <TableHead class="w-[48px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="recurringDeadlines.length === 0" :colspan="7">
                Nessuna scadenza tipo. Creane una dal pulsante in alto.
            </TableEmpty>
            <TableRow
                v-for="deadline in recurringDeadlines"
                v-else
                :key="deadline.id"
                :class="['cursor-pointer transition-colors hover:bg-muted/40', !deadline.active && 'opacity-60']"
                @click="openEdit(deadline)"
            >
                <TableCell class="tabular text-foreground">
                    {{ formatDate(deadline) }}
                </TableCell>
                <TableCell class="font-medium text-foreground">
                    {{ deadline.name }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ deadline.kind_label }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ deadline.expense_item_name ?? '—' }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ deadline.kind === 'payment' ? deadline.expense_year_offset_label : '—' }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="deadline.active ? 'default' : 'outline'">
                        {{ deadline.active ? 'Attiva' : 'Inattiva' }}
                    </Badge>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Azioni scadenza"
                            >
                                <PhDotsThreeVertical :size="14" weight="bold" />
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
                <form id="deadline-form" @submit.prevent="onSubmit">
                    <FieldGroup>
                        <FormField label="Nome" for="d-name" required>
                            <Input
                                id="d-name"
                                v-model="form.name"
                                placeholder="Es. Saldo imposta sostitutiva"
                            />
                            <template v-if="form.errors.name" #error>{{ form.errors.name }}</template>
                        </FormField>

                        <div class="grid grid-cols-2 gap-4">
                            <FormField label="Giorno" for="d-day" required>
                                <NumberField
                                    id="d-day"
                                    v-model="form.day"
                                    :min="1"
                                    :max="31"
                                    :step="1"
                                    :format-options="{ maximumFractionDigits: 0 }"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput class="tabular" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <template v-if="form.errors.day" #error>{{ form.errors.day }}</template>
                            </FormField>

                            <FormField label="Mese" for="d-month" required>
                                <Select v-model.number="form.month">
                                    <SelectTrigger id="d-month" class="w-full">
                                        <SelectValue placeholder="Seleziona…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="(label, idx) in MONTH_LABELS"
                                            :key="idx"
                                            :value="idx + 1"
                                        >
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <template v-if="form.errors.month" #error>{{ form.errors.month }}</template>
                            </FormField>
                        </div>

                        <FormField label="Tipo" for="d-kind" required>
                            <Select v-model="form.kind">
                                <SelectTrigger id="d-kind" class="w-full">
                                    <SelectValue placeholder="Seleziona…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in kinds"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template v-if="form.errors.kind" #error>{{ form.errors.kind }}</template>
                        </FormField>

                        <FormField
                            label="Anno della scadenza"
                            for="d-due-year"
                            required
                            hint="Cade nello stesso anno del wizard o nell'anno successivo (es. saldo IS, bolli Q4)."
                        >
                            <Select v-model="form.due_year_offset">
                                <SelectTrigger id="d-due-year" class="w-full">
                                    <SelectValue placeholder="Seleziona…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in dueYearOffsets"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template v-if="form.errors.due_year_offset" #error>{{ form.errors.due_year_offset }}</template>
                        </FormField>

                        <FormField
                            v-if="isPayment"
                            label="Voce di spesa collegata"
                            for="d-item"
                            required
                        >
                            <Select
                                v-model.number="form.expense_item_id"
                                :disabled="activeExpenseItems.length === 0"
                            >
                                <SelectTrigger id="d-item" class="w-full">
                                    <SelectValue placeholder="Seleziona una voce…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in activeExpenseItems"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template
                                v-if="activeExpenseItems.length === 0"
                                #hint
                            >
                                Nessuna voce di spesa attiva. Vai a Voci di
                                spesa per crearne una prima di collegarla a
                                un pagamento.
                            </template>
                            <template v-if="form.errors.expense_item_id" #error>{{ form.errors.expense_item_id }}</template>
                        </FormField>

                        <FormField
                            v-if="isPayment"
                            label="Anno di riferimento spesa"
                            for="d-expense-year"
                            required
                            hint="Imposta «successivo» per scadenze che pagano la spesa dell'anno N+1 (es. parcella commercialista a dicembre)."
                        >
                            <Select v-model="form.expense_year_offset">
                                <SelectTrigger id="d-expense-year" class="w-full">
                                    <SelectValue placeholder="Seleziona…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in expenseYearOffsets"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template v-if="form.errors.expense_year_offset" #error>{{ form.errors.expense_year_offset }}</template>
                        </FormField>

                        <FormField
                            v-if="isPayment"
                            label="Tipo quota"
                            for="d-quota"
                            hint="Determina l'importo previsto suggerito alla registrazione del pagamento. Lascia vuoto per nessun suggerimento."
                        >
                            <Select
                                :model-value="form.quota_type ?? 'none'"
                                @update:model-value="(v) => (form.quota_type = v === 'none' ? null : (v as QuotaType))"
                            >
                                <SelectTrigger id="d-quota" class="w-full">
                                    <SelectValue placeholder="Nessuno" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Nessuno</SelectItem>
                                    <SelectItem
                                        v-for="opt in quotaTypes"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template v-if="form.errors.quota_type" #error>{{ form.errors.quota_type }}</template>
                        </FormField>

                        <Field orientation="horizontal">
                            <Switch id="d-active" v-model="form.active" />
                            <FieldLabel for="d-active" class="font-normal">
                                Scadenza attiva (generata nei nuovi anni)
                            </FieldLabel>
                        </Field>
                    </FieldGroup>
                </form>
            </DialogBody>
            <DialogStandardFooter>
                <Button
                    type="submit"
                    form="deadline-form"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ editing ? 'Salva modifiche' : 'Aggiungi scadenza' }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la scadenza?"
        :description="archiveTarget
            ? `«${archiveTarget.name}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
