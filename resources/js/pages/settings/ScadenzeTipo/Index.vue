<script setup lang="ts">
/**
 * Settings — Scadenze tipo template.
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova scadenza" vive nel topbar
 * via Teleport.
 *
 * Tipo `pagamento` richiede voce di spesa + anno_riferimento.
 * Tipo `adempimento` non ha collegamenti.
 */
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhPlus } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import ScadenzeTipoController from '@/actions/App/Http/Controllers/Settings/ScadenzeTipoController';
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
import type {
    AnnoDataScadenza,
    AnnoRiferimentoSpesa,
    EnumOption,
    ScadenzaTipo,
    TipoScadenza,
} from '@/types';

const props = defineProps<{
    scadenzeTipo: ScadenzaTipo[];
    tipiScadenza: EnumOption[];
    anniData: EnumOption[];
    anniRiferimento: EnumOption[];
    vociAttive: { id: number; nome: string }[];
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
    nome: string;
    giorno: number;
    mese: number;
    tipo: TipoScadenza;
    voce_spesa_id: number | null;
    anno_data_scadenza: AnnoDataScadenza;
    anno_riferimento_spesa: AnnoRiferimentoSpesa;
    attiva: boolean;
};

const emptyForm = (): FormPayload => ({
    nome: '',
    giorno: 30,
    mese: 6,
    tipo: 'pagamento',
    voce_spesa_id: null,
    anno_data_scadenza: 'corrente',
    anno_riferimento_spesa: 'corrente',
    attiva: true,
});

const MESI_LABEL = [
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
const editing = ref<ScadenzaTipo | null>(null);
const form = useForm<FormPayload>(emptyForm());

const archiveOpen = ref(false);
const archiveTarget = ref<ScadenzaTipo | null>(null);

const isPagamento = computed(() => form.tipo === 'pagamento');

const dialogTitle = computed(() =>
    editing.value ? 'Modifica scadenza tipo' : 'Nuova scadenza tipo',
);
const dialogDescription = computed(() =>
    editing.value
        ? 'Modifica i parametri della scadenza. Le istanze già create negli anni esistenti restano invariate.'
        : 'Aggiungi una scadenza ricorrente al catalogo. Verrà generata nei prossimi anni che apri.',
);

watch(
    () => form.tipo,
    (nextTipo) => {
        if (nextTipo === 'adempimento') {
            form.voce_spesa_id = null;
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

function openEdit(scadenza: ScadenzaTipo): void {
    editing.value = scadenza;
    form.clearErrors();
    const next: FormPayload = {
        nome: scadenza.nome,
        giorno: scadenza.giorno,
        mese: scadenza.mese,
        tipo: scadenza.tipo,
        voce_spesa_id: scadenza.voce_spesa_id,
        anno_data_scadenza: scadenza.anno_data_scadenza,
        anno_riferimento_spesa: scadenza.anno_riferimento_spesa,
        attiva: scadenza.attiva,
    };
    form.defaults(next);
    form.reset();
    dialogOpen.value = true;
}

function onSubmit(): void {
    if (editing.value) {
        form.patch(
            ScadenzeTipoController.update.url({
                scadenzaTipo: editing.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogOpen.value = false;
                },
            },
        );
    } else {
        form.post(ScadenzeTipoController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
}

function askArchive(scadenza: ScadenzaTipo): void {
    archiveTarget.value = scadenza;
    archiveOpen.value = true;
}

function confirmArchive(): void {
    if (!archiveTarget.value) return;
    useForm({}).delete(
        ScadenzeTipoController.destroy.url({
            scadenzaTipo: archiveTarget.value.id,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                archiveOpen.value = false;
                archiveTarget.value = null;
            },
        },
    );
}

function formatDate(scadenza: ScadenzaTipo): string {
    const dd = String(scadenza.giorno).padStart(2, '0');
    const mm = String(scadenza.mese).padStart(2, '0');
    
    return scadenza.anno_data_scadenza === 'successivo'
        ? `${dd}/${mm}/(N+1)`
        : `${dd}/${mm}`;
}

// suppress unused warning
void props;
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
                <TableHead>Anno riferimento</TableHead>
                <TableHead class="w-[80px] text-right">Stato</TableHead>
                <TableHead class="w-[60px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="scadenzeTipo.length === 0" :colspan="7">
                Nessuna scadenza tipo nel catalogo.
            </TableEmpty>
            <TableRow
                v-for="scadenza in scadenzeTipo"
                v-else
                :key="scadenza.id"
                :class="['cursor-pointer transition-colors hover:bg-muted/40', !scadenza.attiva && 'opacity-60']"
                @click="openEdit(scadenza)"
            >
                <TableCell class="tabular text-foreground">
                    {{ formatDate(scadenza) }}
                </TableCell>
                <TableCell class="font-medium text-foreground">
                    {{ scadenza.nome }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ scadenza.tipo_label }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ scadenza.voce_spesa_nome ?? '—' }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ scadenza.tipo === 'pagamento' ? scadenza.anno_riferimento_label : '—' }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="scadenza.attiva ? 'default' : 'outline'">
                        {{ scadenza.attiva ? 'Attiva' : 'Disattiva' }}
                    </Badge>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <div class="flex items-center justify-end gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Archivia scadenza"
                            @click="askArchive(scadenza)"
                        >
                            <PhArchive :size="14" />
                        </Button>
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <!-- Dialog create/edit -->
    <Dialog v-model:open="dialogOpen">
        <DialogContent size="default">
            <DialogStandardHeader
                :title="dialogTitle"
                :description="dialogDescription"
            />
            <DialogBody>
                <form id="scadenza-tipo-form" class="space-y-4" @submit.prevent="onSubmit">
                    <FormField label="Nome" for="scad-nome" required>
                        <Input
                            id="scad-nome"
                            v-model="form.nome"
                            placeholder="Es. Saldo imposta sostitutiva"
                        />
                        <template v-if="form.errors.nome" #error>{{ form.errors.nome }}</template>
                    </FormField>

                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Giorno" for="scad-giorno" required>
                            <NumberField
                                id="scad-giorno"
                                v-model="form.giorno"
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
                            <template v-if="form.errors.giorno" #error>{{ form.errors.giorno }}</template>
                        </FormField>

                        <FormField label="Mese" for="scad-mese" required>
                            <Select v-model.number="form.mese">
                                <SelectTrigger id="scad-mese" class="w-full">
                                    <SelectValue placeholder="Seleziona…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, idx) in MESI_LABEL"
                                        :key="idx"
                                        :value="idx + 1"
                                    >
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <template v-if="form.errors.mese" #error>{{ form.errors.mese }}</template>
                        </FormField>
                    </div>

                    <FormField label="Tipo" for="scad-tipo" required>
                        <Select v-model="form.tipo">
                            <SelectTrigger id="scad-tipo" class="w-full">
                                <SelectValue placeholder="Seleziona…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in tipiScadenza"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.tipo" #error>{{ form.errors.tipo }}</template>
                    </FormField>

                    <FormField
                        label="Anno della scadenza"
                        for="scad-anno-data"
                        required
                        hint="Cade nello stesso anno del wizard o nell'anno successivo (es. saldo IS, bolli Q4)."
                    >
                        <Select v-model="form.anno_data_scadenza">
                            <SelectTrigger id="scad-anno-data" class="w-full">
                                <SelectValue placeholder="Seleziona…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in anniData"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.anno_data_scadenza" #error>{{ form.errors.anno_data_scadenza }}</template>
                    </FormField>

                    <FormField
                        v-if="isPagamento"
                        label="Voce di spesa collegata"
                        for="scad-voce"
                        required
                    >
                        <Select v-model.number="form.voce_spesa_id">
                            <SelectTrigger id="scad-voce" class="w-full">
                                <SelectValue placeholder="Seleziona una voce…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="voce in vociAttive"
                                    :key="voce.id"
                                    :value="voce.id"
                                >
                                    {{ voce.nome }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.voce_spesa_id" #error>{{ form.errors.voce_spesa_id }}</template>
                    </FormField>

                    <FormField
                        v-if="isPagamento"
                        label="Anno di riferimento"
                        for="scad-anno-rif"
                        required
                        hint="Imposta «successivo» per scadenze che pagano la spesa dell'anno N+1 (es. parcella commercialista a dicembre)."
                    >
                        <Select v-model="form.anno_riferimento_spesa">
                            <SelectTrigger id="scad-anno-rif" class="w-full">
                                <SelectValue placeholder="Seleziona…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in anniRiferimento"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.anno_riferimento_spesa" #error>{{ form.errors.anno_riferimento_spesa }}</template>
                    </FormField>

                    <div class="flex items-center gap-3 pt-2">
                        <Switch id="scad-attiva" v-model="form.attiva" />
                        <label for="scad-attiva" class="text-13 text-foreground">
                            Scadenza attiva (generata nei nuovi anni)
                        </label>
                    </div>
                </form>
            </DialogBody>
            <DialogStandardFooter>
                <Button
                    type="submit"
                    form="scadenza-tipo-form"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ editing ? 'Salva modifiche' : 'Crea scadenza' }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la scadenza?"
        :description="archiveTarget
            ? `«${archiveTarget.nome}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
