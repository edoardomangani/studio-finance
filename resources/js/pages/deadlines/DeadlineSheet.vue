<script setup lang="ts">
/**
 * DeadlineSheet — sheet di una scadenza, aperto dal click su una riga di
 * [[deadlines/Index.vue]]. Si alimenta dai dati di riga (nessuna fetch).
 *
 * Layout su [[ActionSheet]] (bottom drawer mobile / pannello destro desktop):
 * pagamento APERTO → [[DeadlinePaymentForm]], primario a check in alto
 * (planned→paid); adempimento APERTO → check in alto che lo segna svolto
 * (open→completed). Altri stati → lettura + reversibilità
 * ([[useDeadlineReversal]]) confermata inline nel footer.
 */
import { router } from '@inertiajs/vue3';
import {
    PhArchive,
    PhCheck,
    PhDotsThreeVertical,
    PhPencil,
} from '@phosphor-icons/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, toRef, watch } from 'vue';
import { toast } from 'vue-sonner';
import ActionSheet from '@/components/ActionSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import { useDeadlineReversal } from '@/composables/useDeadlineReversal';
import { formatDateIT, formatEUR } from '@/lib/format';
import DeadlinePaymentForm from '@/pages/deadlines/DeadlinePaymentForm.vue';
import { DEADLINE_STATUS_META } from '@/pages/deadlines/statusMeta';
import { fulfill as fulfillRoute } from '@/routes/deadlines';
import type { DeadlineListItem } from '@/types';

const props = defineProps<{
    deadline: DeadlineListItem | null;
}>();

const open = defineModel<boolean>('open', { default: false });

// Gestione scadenza dal mobile: lo sheet è il dettaglio, quindi emette
// Modifica/Archivia che il parent (DeadlinesTable) instrada su form/confirm.
// Su desktop quelle azioni restano nel kebab della tabella.
const emit = defineEmits<{ edit: []; archive: [] }>();

// Archiviabile solo le ad-hoc (custom) col pagamento non registrato — stessa
// regola del kebab tabella. Modifica invece è sempre disponibile.
const archivable = computed(
    () =>
        props.deadline?.is_custom === true &&
        props.deadline?.payment?.status !== 'paid',
);

const isMobile = useMediaQuery('(max-width: 767px)');

const isPayableOpen = computed(
    () =>
        props.deadline?.kind === 'payment' && props.deadline?.status === 'open',
);

// Adempimento aperto: si chiude con il check in header (open→completed).
const isFulfillableOpen = computed(
    () =>
        props.deadline?.kind === 'fulfillment' &&
        props.deadline?.status === 'open',
);

const fulfilling = ref(false);

// Form di registrazione pagamento: vive in DeadlinePaymentForm; leggiamo il suo
// `processing` per lo stato dei bottoni primari (header/footer).
const paymentForm = ref<InstanceType<typeof DeadlinePaymentForm> | null>(null);

// Reversibilità (marca non dovuta / annulla completamento / riapri).
const {
    pending,
    reversing,
    clear,
    askMarkNotDue,
    askUndoCompletion,
    askReopen,
    runReversal,
} = useDeadlineReversal(toRef(props, 'deadline'), open);

// Footer visibile quando c'è un'azione. Su desktop ospita anche il primario
// (su mobile il primario è il check nell'header): per gli stati "open" senza
// secondaria (adempimento) il footer serve solo da desktop.
const showFooter = computed(
    () =>
        pending.value !== null ||
        isPayableOpen.value ||
        (!isMobile.value && isFulfillableOpen.value) ||
        props.deadline?.status === 'completed' ||
        props.deadline?.status === 'not_due',
);

// Azzera la conferma di reversibilità ad ogni apertura.
watch(open, (isOpen) => {
    if (isOpen) {
        clear();
    }
});

function markFulfilled(): void {
    if (!props.deadline) {
        return;
    }

    router.post(
        fulfillRoute({ deadline: props.deadline.id }).url,
        {},
        {
            preserveScroll: true,
            onStart: () => {
                fulfilling.value = true;
            },
            onFinish: () => {
                fulfilling.value = false;
            },
            onSuccess: () => {
                open.value = false;
            },
            onError: () => {
                toast.error('Operazione non riuscita. Riprova.');
            },
        },
    );
}
</script>

<template>
    <ActionSheet v-model:open="open" :title="deadline?.name ?? 'Scadenza'">
        <!-- Header iOS (mobile): kebab "altre azioni" (Modifica · Archivia) +
             check del primario. Su desktop lo slot #primary non si mostra (le
             azioni sono nel kebab della tabella). -->
        <template #primary>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-md"
                        aria-label="Altre azioni"
                    >
                        <PhDotsThreeVertical :size="20" weight="bold" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @select="emit('edit')">
                        <PhPencil :size="14" />
                        Modifica
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="archivable"
                        variant="destructive"
                        @select="emit('archive')"
                    >
                        <PhArchive :size="14" />
                        Archivia
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <Button
                v-if="isPayableOpen"
                type="submit"
                form="register-payment"
                size="icon-md"
                aria-label="Registra pagamento"
                :disabled="paymentForm?.processing"
                :aria-busy="paymentForm?.processing"
            >
                <Spinner v-if="paymentForm?.processing" />
                <PhCheck v-else :size="20" weight="bold" />
            </Button>
            <Button
                v-else-if="isFulfillableOpen"
                type="button"
                size="icon-md"
                aria-label="Segna come svolto"
                :disabled="fulfilling"
                :aria-busy="fulfilling"
                @click="markFulfilled"
            >
                <Spinner v-if="fulfilling" />
                <PhCheck v-else :size="20" weight="bold" />
            </Button>
        </template>

        <template v-if="deadline">
            <!-- Meta scadenza: contesto in lettura, niente card. -->
            <dl
                class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 border-b border-border pb-4 text-13"
            >
                <dt class="text-muted-foreground">Scadenza</dt>
                <dd class="tabular text-right text-foreground">
                    {{ formatDateIT(deadline.due_at) }}
                </dd>
                <dt class="text-muted-foreground">Anno</dt>
                <dd class="tabular text-right text-foreground">
                    {{ deadline.year }}
                </dd>
                <template v-if="deadline.annual_expense_name">
                    <dt class="text-muted-foreground">Voce di spesa</dt>
                    <dd class="text-right text-foreground">
                        {{ deadline.annual_expense_name }}
                    </dd>
                </template>
                <dt class="text-muted-foreground">Stato</dt>
                <dd class="text-right">
                    <Badge
                        :variant="DEADLINE_STATUS_META[deadline.status].variant"
                        class="gap-1"
                    >
                        <component
                            :is="DEADLINE_STATUS_META[deadline.status].icon"
                            :size="12"
                        />
                        {{ deadline.status_label }}
                    </Badge>
                </dd>
            </dl>

            <!-- Pagamento aperto → form di registrazione (submit dal check header). -->
            <DeadlinePaymentForm
                v-if="isPayableOpen"
                ref="paymentForm"
                :deadline="deadline"
                v-model:open="open"
            />

            <!-- Pagamento registrato → lettura. -->
            <dl
                v-else-if="
                    deadline.payment && deadline.payment.status === 'paid'
                "
                class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 pt-4 text-13"
            >
                <dt class="text-muted-foreground">Importo pagato</dt>
                <dd class="tabular text-right font-medium text-foreground">
                    {{
                        deadline.payment.amount !== null
                            ? formatEUR(deadline.payment.amount)
                            : '—'
                    }}
                </dd>
                <dt class="text-muted-foreground">Data</dt>
                <dd class="tabular text-right text-foreground">
                    {{ formatDateIT(deadline.payment.paid_at) }}
                </dd>
            </dl>

            <p v-else class="pt-4 text-xs text-muted-foreground">
                <span v-if="isFulfillableOpen"
                    >Adempimento senza pagamento: usa «Segna come svolto» per
                    chiuderlo.</span
                >
                <span v-else-if="deadline.kind === 'fulfillment'"
                    >Adempimento svolto.</span
                >
                <span v-else-if="deadline.status === 'not_due'"
                    >Scadenza segnata come non dovuta.</span
                >
                <span v-else>Nessun pagamento da registrare.</span>
            </p>
        </template>

        <template v-if="showFooter" #footer>
            <!-- Conferma inline di una reversibilità. -->
            <div v-if="pending" class="space-y-3">
                <p class="text-13 text-muted-foreground">
                    {{ pending.description }}
                </p>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        class="flex-1"
                        :disabled="reversing"
                        @click="clear"
                    >
                        Annulla
                    </Button>
                    <Button
                        type="button"
                        :variant="
                            pending.destructive ? 'destructive' : 'default'
                        "
                        class="flex-1"
                        :disabled="reversing"
                        @click="runReversal"
                    >
                        <Spinner v-if="reversing" />
                        {{ pending.confirmLabel }}
                    </Button>
                </div>
            </div>

            <div v-else class="space-y-2">
                <!-- Desktop: il primario vive nel footer (su mobile è il check
                     nell'header iOS). -->
                <Button
                    v-if="isPayableOpen"
                    type="submit"
                    form="register-payment"
                    class="hidden w-full sm:flex"
                    :disabled="paymentForm?.processing"
                    :aria-busy="paymentForm?.processing"
                >
                    <Spinner v-if="paymentForm?.processing" />
                    <PhCheck v-else :size="16" weight="bold" />
                    Registra pagamento
                </Button>
                <Button
                    v-else-if="isFulfillableOpen"
                    type="button"
                    class="hidden w-full sm:flex"
                    :disabled="fulfilling"
                    :aria-busy="fulfilling"
                    @click="markFulfilled"
                >
                    <Spinner v-if="fulfilling" />
                    <PhCheck v-else :size="16" weight="bold" />
                    Segna come svolto
                </Button>

                <!-- Azioni secondarie per stato. -->
                <Button
                    v-if="isPayableOpen"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="w-full"
                    @click="askMarkNotDue"
                >
                    Marca come non dovuta
                </Button>
                <Button
                    v-else-if="deadline && deadline.status === 'completed'"
                    type="button"
                    variant="outline"
                    class="w-full"
                    @click="askUndoCompletion"
                >
                    Annulla completamento
                </Button>
                <Button
                    v-else-if="deadline && deadline.status === 'not_due'"
                    type="button"
                    class="w-full"
                    @click="askReopen"
                >
                    Riapri scadenza
                </Button>
            </div>
        </template>
    </ActionSheet>
</template>
