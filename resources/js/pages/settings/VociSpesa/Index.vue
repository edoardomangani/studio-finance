<script setup lang="ts">
/**
 * Settings — Voci di spesa template.
 *
 * Pagina dedicata (sidebar in modalità settings): solo tabella + dialog
 * create/edit + confirm archivio. La CTA "Nuova voce" vive nel topbar
 * via Teleport.
 */
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import VociSpesaController from '@/actions/App/Http/Controllers/Settings/VociSpesaController';
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
import type { EnumOption, TipoCalcoloVoceSpesa, VoceSpesa } from '@/types';

const props = defineProps<{
    vociSpesa: VoceSpesa[];
    tipiCalcolo: EnumOption[];
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
    nome: string;
    tipo_calcolo: TipoCalcoloVoceSpesa;
    aliquota_default: number | null;
    minimale_default: number | null;
    massimale_default: number | null;
    quota_default: number | null;
    attiva: boolean;
    ordine: number;
};

const emptyForm = (): FormPayload => ({
    nome: '',
    tipo_calcolo: 'fissa_annuale',
    aliquota_default: null,
    minimale_default: null,
    massimale_default: null,
    quota_default: null,
    attiva: true,
    ordine: 0,
});

const dialogOpen = ref(false);
const editing = ref<VoceSpesa | null>(null);
const form = useForm<FormPayload>(emptyForm());

const archiveOpen = ref(false);
const archiveTarget = ref<VoceSpesa | null>(null);

const isPerc = computed(
    () =>
        form.tipo_calcolo === 'perc_reddito_irpef' ||
        form.tipo_calcolo === 'perc_volume_affari_iva',
);
const isFissa = computed(() => form.tipo_calcolo === 'fissa_annuale');

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

function openEdit(voce: VoceSpesa): void {
    editing.value = voce;
    form.clearErrors();
    const next: FormPayload = {
        nome: voce.nome,
        tipo_calcolo: voce.tipo_calcolo,
        aliquota_default:
            voce.aliquota_default !== null
                ? Number(voce.aliquota_default)
                : null,
        minimale_default:
            voce.minimale_default !== null
                ? Number(voce.minimale_default)
                : null,
        massimale_default:
            voce.massimale_default !== null
                ? Number(voce.massimale_default)
                : null,
        quota_default:
            voce.quota_default !== null ? Number(voce.quota_default) : null,
        attiva: voce.attiva,
        ordine: voce.ordine,
    };
    form.defaults(next);
    form.reset();
    dialogOpen.value = true;
}

function onSubmit(): void {
    if (!isPerc.value) {
        form.aliquota_default = null;
        form.minimale_default = null;
        form.massimale_default = null;
    }
    if (!isFissa.value) {
        form.quota_default = null;
    }

    if (editing.value) {
        form.patch(
            VociSpesaController.update.url({ voceSpesa: editing.value.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogOpen.value = false;
                },
            },
        );
    } else {
        form.post(VociSpesaController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
    }
}

function askArchive(voce: VoceSpesa): void {
    archiveTarget.value = voce;
    archiveOpen.value = true;
}

function confirmArchive(): void {
    if (!archiveTarget.value) return;
    useForm({}).delete(
        VociSpesaController.destroy.url({ voceSpesa: archiveTarget.value.id }),
        {
            preserveScroll: true,
            onFinish: () => {
                archiveOpen.value = false;
                archiveTarget.value = null;
            },
        },
    );
}

function formatDefault(voce: VoceSpesa): string {
    if (voce.tipo_calcolo === 'fissa_annuale') {
        return voce.quota_default !== null
            ? `€ ${Number(voce.quota_default).toFixed(2)}`
            : '—';
    }
    if (voce.tipo_calcolo === 'somma_bolli') {
        return 'derivata';
    }
    return voce.aliquota_default !== null
        ? `${Number(voce.aliquota_default).toFixed(2)} %`
        : '—';
}

// suppress unused warning
void props;
</script>

<template>
    <Head title="Voci di spesa" />

    <!-- CTA primary nel topbar (slot #page-topbar-actions). -->
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
                <TableHead class="w-[60px]" />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="vociSpesa.length === 0" :colspan="5">
                Nessuna voce di spesa nel catalogo.
            </TableEmpty>
            <TableRow
                v-for="voce in vociSpesa"
                v-else
                :key="voce.id"
                :class="['cursor-pointer transition-colors hover:bg-muted/40', !voce.attiva && 'opacity-60']"
                @click="openEdit(voce)"
            >
                <TableCell class="font-medium text-foreground">
                    {{ voce.nome }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ voce.tipo_calcolo_label }}
                </TableCell>
                <TableCell class="text-right tabular text-foreground">
                    {{ formatDefault(voce) }}
                </TableCell>
                <TableCell class="text-right">
                    <Badge :variant="voce.attiva ? 'default' : 'outline'">
                        {{ voce.attiva ? 'Attiva' : 'Disattiva' }}
                    </Badge>
                </TableCell>
                <TableCell class="text-right" @click.stop>
                    <div class="flex items-center justify-end gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Archivia voce"
                            @click="askArchive(voce)"
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
                <form id="voce-spesa-form" class="space-y-4" @submit.prevent="onSubmit">
                    <FormField label="Nome" for="voce-nome" required>
                        <Input
                            id="voce-nome"
                            v-model="form.nome"
                            placeholder="Es. Imposta sostitutiva"
                        />
                        <template v-if="form.errors.nome" #error>{{ form.errors.nome }}</template>
                    </FormField>

                    <FormField label="Tipo di calcolo" for="voce-tipo" required>
                        <Select v-model="form.tipo_calcolo">
                            <SelectTrigger id="voce-tipo" class="w-full">
                                <SelectValue placeholder="Seleziona…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in tipiCalcolo"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.tipo_calcolo" #error>{{ form.errors.tipo_calcolo }}</template>
                    </FormField>

                    <div v-if="isPerc" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <FormField label="Aliquota (%)" for="voce-aliquota">
                            <NumberField
                                id="voce-aliquota"
                                v-model="form.aliquota_default"
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
                            <template v-if="form.errors.aliquota_default" #error>{{ form.errors.aliquota_default }}</template>
                        </FormField>

                        <FormField label="Minimale (€)" for="voce-min">
                            <NumberField
                                id="voce-min"
                                v-model="form.minimale_default"
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
                            <template v-if="form.errors.minimale_default" #error>{{ form.errors.minimale_default }}</template>
                        </FormField>

                        <FormField label="Massimale (€)" for="voce-max">
                            <NumberField
                                id="voce-max"
                                v-model="form.massimale_default"
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
                            <template v-if="form.errors.massimale_default" #error>{{ form.errors.massimale_default }}</template>
                        </FormField>
                    </div>

                    <FormField v-if="isFissa" label="Importo annuale (€)" for="voce-quota">
                        <NumberField
                            id="voce-quota"
                            v-model="form.quota_default"
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
                        <template v-if="form.errors.quota_default" #error>{{ form.errors.quota_default }}</template>
                    </FormField>

                    <div class="flex items-center gap-3 pt-2">
                        <Switch id="voce-attiva" v-model="form.attiva" />
                        <label for="voce-attiva" class="text-13 text-foreground">
                            Voce attiva (proposta nei nuovi anni)
                        </label>
                    </div>
                </form>
            </DialogBody>
            <DialogStandardFooter>
                <Button
                    type="submit"
                    form="voce-spesa-form"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ editing ? 'Salva modifiche' : 'Crea voce' }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>

    <!-- ConfirmDialog archivia -->
    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la voce?"
        :description="archiveTarget
            ? `«${archiveTarget.nome}» verrà nascosta dal catalogo. Le istanze già create negli anni esistenti restano invariate; non sarà più proposta nei nuovi anni.`
            : undefined"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
