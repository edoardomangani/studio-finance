<script setup lang="ts">
/**
 * ClientPicker — combobox autocomplete per selezionare un cliente dal
 * form fattura. Filtra denominazione + P.IVA + CF in client-side
 * (volumi attesi: max ~100 clienti per utente, no necessità di server search).
 *
 * Inline create: l'ultima riga della lista è "+ Crea nuovo cliente: {query}".
 * Click → apre [[ClientFormDialog]] in modalità inline, alla conferma il
 * controller fa back() + flash `new_client_id`. Watch su `usePage().props.
 * new_client_id` rileva il flash e auto-seleziona il cliente creato.
 *
 * Sentinel value `-1` per la riga "Crea nuovo": gli ID reali sono positivi,
 * quindi non c'è collisione. Quando il combobox seleziona -1, ripristiniamo
 * il selectedId precedente e apriamo il dialog.
 */
import { Link, usePage } from '@inertiajs/vue3';
import { PhCheck, PhPlus, PhArrowRight } from '@phosphor-icons/vue';
import { computed, nextTick, ref, watch } from 'vue';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxSeparator,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import type { ClientForPicker } from '@/types';

const CREATE_SENTINEL = -1;

const props = defineProps<{
    clients: ClientForPicker[];
    id?: string;
    invalid?: boolean;
}>();

const emit = defineEmits<{
    (e: 'select', client: ClientForPicker): void;
}>();

const selectedId = defineModel<number | null>({ default: null });

const searchTerm = ref('');
const open = ref(false);
const createDialogOpen = ref(false);

/* Reka-ui Combobox, dopo una selezione, può scrivere il model value
   (un numero, l'ID) dentro ComboboxInput al successivo apertura. Reset
   esplicito di searchTerm ogni volta che la lista riapre → il search
   parte sempre da pulito, senza "5" o ID residui. */
watch(open, (isOpen) => {
    if (isOpen) {
        searchTerm.value = '';
    }
});

const selectedClient = computed<ClientForPicker | null>(() =>
    selectedId.value !== null
        ? (props.clients.find((c) => c.id === selectedId.value) ?? null)
        : null,
);

const filtered = computed<ClientForPicker[]>(() => {
    const q = searchTerm.value.toLowerCase().trim();

    if (!q) {
        return props.clients;
    }

    return props.clients.filter(
        (c) =>
            c.name.toLowerCase().includes(q)
            || (c.vat_number ?? '').toLowerCase().includes(q)
            || (c.tax_code ?? '').toLowerCase().includes(q),
    );
});

function handleUpdate(value: unknown): void {
    // reka-ui Combobox è generic su qualsiasi value; restringiamo a number
    // (l'unica forma usata: ID cliente o CREATE_SENTINEL=-1).
    const id = typeof value === 'number' ? value : null;

    if (id === CREATE_SENTINEL) {
        // Rollback alla selezione precedente: il sentinel non è un cliente
        // valido, esiste solo per dare keyboard-nav alla CTA "Crea nuovo".
        const previous = selectedId.value;

        nextTick(() => {
            selectedId.value = previous === CREATE_SENTINEL ? null : previous;
        });

        createDialogOpen.value = true;
        open.value = false;

        return;
    }

    selectedId.value = id;
    searchTerm.value = '';

    if (id !== null) {
        const c = props.clients.find((x) => x.id === id);

        if (c) {
            emit('select', c);
        }
    }
}

// Watch shared Inertia prop `new_client_id`: ClientFormDialog inline mode →
// controller flasha l'id del nuovo cliente → qui auto-selezioniamo.
const page = usePage<{ new_client_id?: number }>();
watch(
    () => page.props.new_client_id,
    (newId) => {
        if (typeof newId !== 'number') {
            return;
        }

        // Il prop `clients` è già refreshato dalla redirect-back: il nuovo
        // cliente esiste nella lista quando questo watcher si attiva.
        const created = props.clients.find((c) => c.id === newId);

        if (created) {
            selectedId.value = newId;
            emit('select', created);
            searchTerm.value = '';
        }
    },
);
</script>

<template>
    <Combobox
        :model-value="selectedId ?? undefined"
        v-model:open="open"
        @update:model-value="handleUpdate"
    >
        <ComboboxAnchor class="w-full">
            <ComboboxTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    class="w-full justify-between font-normal"
                    :class="invalid ? 'border-destructive' : ''"
                >
                    <span :class="selectedClient ? '' : 'text-muted-foreground/70'">
                        {{ selectedClient?.name ?? 'Cerca cliente…' }}
                    </span>
                    <div class="flex items-center">
                        <span
                            v-if="selectedClient?.vat_number"
                            class="ml-2 tabular text-xs text-muted-foreground"
                        >
                            {{ selectedClient.vat_number }}
                        </span>
                        <PhArrowRight class="ml-2 size-3.5 rotate-90 opacity-50"/>
                    </div>
                    
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList class="w-(--reka-combobox-trigger-width)">
            <div class="relative">
                <ComboboxInput
                    v-model="searchTerm"
                    :display-value="() => ''"
                    placeholder="Cerca per denominazione, P.IVA o CF…"
                />
            </div>

            <ComboboxEmpty>
                Nessun cliente trovato.
            </ComboboxEmpty>

            <ComboboxGroup v-if="filtered.length > 0">
                <ComboboxItem
                    v-for="c in filtered"
                    :key="c.id"
                    :value="c.id"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex flex-col">
                        <span>{{ c.name }}</span>
                        <span
                            v-if="c.vat_number || c.tax_code"
                            class="tabular text-xs text-muted-foreground"
                        >
                            {{ c.vat_number ?? c.tax_code }}
                        </span>
                    </div>
                    <ComboboxItemIndicator>
                        <PhCheck class="size-3.5" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>

            <ComboboxSeparator v-if="filtered.length > 0" />

            <ComboboxGroup>
                <ComboboxItem
                    :value="CREATE_SENTINEL"
                    class="text-foreground text-xs"
                >
                    <PhPlus class="size-3 text-muted-foreground" />
                    <span>
                        Crea nuovo cliente<span v-if="searchTerm">: «{{ searchTerm }}»</span>
                    </span>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>

    <!-- Fallback no-clients: se la lista è completamente vuota, mostriamo un
         link di scappamento alla pagina clienti. Edge case raro (volumi
         tipici partono almeno con 1-2 clienti) ma non sguscia l'utente. -->
    <p
        v-if="clients.length === 0"
        class="mt-1 text-xs text-muted-foreground"
    >
        Nessun cliente registrato.
        <Link
            :href="'/clients'"
            class="font-medium underline-offset-2 hover:underline"
        >
            Vai ai clienti
        </Link>
        oppure usa "Crea nuovo" qui sopra.
    </p>

    <ClientFormDialog
        v-model:open="createDialogOpen"
        :client="null"
        :initial-name="searchTerm"
        inline
    />
</template>
