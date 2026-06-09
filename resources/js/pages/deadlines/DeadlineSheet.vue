<script setup lang="ts">
/**
 * DeadlineSheet — sheet di una scadenza, aperto dal click su una riga di
 * [[deadlines/Index.vue]]. Si alimenta dai dati di riga (nessuna fetch).
 *
 * Layout su [[ActionSheet]] (bottom drawer mobile / pannello destro desktop):
 * pagamento APERTO → form di registrazione (F7), primario a check in alto
 * (planned→paid, open→completed); adempimento APERTO → check in alto che lo
 * segna svolto (open→completed). Altri stati → lettura + reversibilità (F9)
 * confermata inline nel footer.
 */
import { router, useForm } from '@inertiajs/vue3';
import { PhCheck } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ActionSheet from '@/components/ActionSheet.vue';
import DateField from '@/components/forms/DateField.vue';
import FormField from '@/components/forms/FormField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatDateIT, formatEUR, todayISO } from '@/lib/format';
import { DEADLINE_STATUS_META } from '@/pages/deadlines/statusMeta';
import {
    fulfill as fulfillRoute,
    markNotDue as markNotDueRoute,
    payment as registerPaymentRoute,
    reopen as reopenRoute,
} from '@/routes/deadlines';
import type { DeadlineListItem } from '@/types';

const props = defineProps<{
    deadline: DeadlineListItem | null;
}>();

const open = defineModel<boolean>('open', { default: false });

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

const expectedHint = computed(() => {
    if (!props.deadline) {
        return '';
    }

    return props.deadline.expected_amount !== null
        ? `Previsto ${formatEUR(props.deadline.expected_amount)}`
        : 'Nessun previsto: inserisci l’importo dall’F24.';
});

const form = useForm<{ description: string; amount: string; paid_at: string }>({
    description: '',
    amount: '',
    paid_at: '',
});

const canRestoreExpected = computed(
    () =>
        props.deadline?.expected_amount != null &&
        form.amount !== String(props.deadline.expected_amount),
);

// Conferma inline (no dialog annidato): una transizione di reversibilità in
// attesa di conferma nel footer.
type PendingReversal = {
    description: string;
    confirmLabel: string;
    url: string;
    destructive: boolean;
};
const pending = ref<PendingReversal | null>(null);
const reversing = ref(false);

// Footer visibile solo quando c'è un'azione (primario a parte, in header).
const showFooter = computed(
    () =>
        pending.value !== null ||
        isPayableOpen.value ||
        props.deadline?.status === 'completed' ||
        props.deadline?.status === 'not_due',
);

// Precompila il form e azzera la conferma ad ogni apertura.
watch(open, (isOpen) => {
    if (!isOpen || !props.deadline) {
        return;
    }

    pending.value = null;
    form.clearErrors();
    form.defaults({
        description: props.deadline.name,
        amount:
            props.deadline.expected_amount !== null
                ? String(props.deadline.expected_amount)
                : '',
        paid_at: todayISO(),
    });
    form.reset();
});

function askMarkNotDue(): void {
    if (!props.deadline) {
        return;
    }

    pending.value = {
        description:
            'La scadenza e il pagamento collegato verranno segnati come non dovuti.',
        confirmLabel: 'Marca non dovuta',
        url: markNotDueRoute({ deadline: props.deadline.id }).url,
        destructive: false,
    };
}

function askUndoCompletion(): void {
    if (!props.deadline) {
        return;
    }

    pending.value = {
        description:
            props.deadline.kind === 'fulfillment'
                ? 'Il completamento verrà annullato: l’adempimento tornerà aperto.'
                : 'Il completamento verrà annullato: importo e data del pagamento verranno azzerati.',
        confirmLabel: 'Annulla completamento',
        url: reopenRoute({ deadline: props.deadline.id }).url,
        destructive: true,
    };
}

function askReopen(): void {
    if (!props.deadline) {
        return;
    }

    pending.value = {
        description:
            'La scadenza tornerà aperta e potrai registrare di nuovo il pagamento.',
        confirmLabel: 'Riapri scadenza',
        url: reopenRoute({ deadline: props.deadline.id }).url,
        destructive: false,
    };
}

function runReversal(): void {
    if (!pending.value) {
        return;
    }

    router.post(
        pending.value.url,
        {},
        {
            preserveScroll: true,
            onStart: () => {
                reversing.value = true;
            },
            onFinish: () => {
                reversing.value = false;
            },
            onSuccess: () => {
                pending.value = null;
                open.value = false;
            },
            onError: () => {
                toast.error('Operazione non riuscita. Riprova.');
            },
        },
    );
}

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

function restoreExpected(): void {
    if (props.deadline?.expected_amount != null) {
        form.amount = String(props.deadline.expected_amount);
    }
}

function submit(): void {
    if (!props.deadline) {
        return;
    }

    form.post(registerPaymentRoute({ deadline: props.deadline.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        // Gli errori di validazione vanno inline nei FormField; il toast copre
        // i fallimenti non-di-validazione.
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Registrazione non riuscita. Riprova.');
            }
        },
    });
}
</script>

<template>
    <ActionSheet v-model:open="open" :title="deadline?.name ?? 'Scadenza'">
        <!-- Primario stile iOS: check in alto a destra. -->
        <template v-if="isPayableOpen" #primary>
            <Button
                type="submit"
                form="register-payment"
                size="icon"
                aria-label="Registra pagamento"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                <PhCheck v-else :size="18" weight="bold" />
            </Button>
        </template>
        <template v-else-if="isFulfillableOpen" #primary>
            <Button
                type="button"
                size="icon"
                aria-label="Segna come svolto"
                :disabled="fulfilling"
                @click="markFulfilled"
            >
                <Spinner v-if="fulfilling" />
                <PhCheck v-else :size="18" weight="bold" />
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
            <form
                v-if="isPayableOpen"
                id="register-payment"
                class="pt-4"
                @submit.prevent="submit"
            >
                <FieldGroup>
                    <FormField label="Descrizione" for="payment-description">
                        <Input
                            id="payment-description"
                            v-model="form.description"
                        />
                        <template v-if="form.errors.description" #error>{{
                            form.errors.description
                        }}</template>
                    </FormField>

                    <FormField label="Data del pagamento" for="payment-date">
                        <DateField
                            id="payment-date"
                            v-model="form.paid_at"
                            :max="todayISO()"
                        />
                        <template v-if="form.errors.paid_at" #error>{{
                            form.errors.paid_at
                        }}</template>
                    </FormField>

                    <FormField label="Importo" for="payment-amount">
                        <DecimalInput
                            id="payment-amount"
                            v-model="form.amount"
                            :min="0"
                            placeholder="0,00"
                            class="tabular"
                        />
                        <template #hint>
                            <span
                                class="flex items-center justify-between gap-2"
                            >
                                <span>{{ expectedHint }}</span>
                                <button
                                    v-if="canRestoreExpected"
                                    type="button"
                                    class="text-accent-vivid hover:underline"
                                    @click="restoreExpected"
                                >
                                    Ripristina previsto
                                </button>
                            </span>
                        </template>
                        <template v-if="form.errors.amount" #error>{{
                            form.errors.amount
                        }}</template>
                    </FormField>
                </FieldGroup>
            </form>

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

            <p v-else class="pt-4 text-13 text-muted-foreground">
                <span v-if="isFulfillableOpen"
                    >Adempimento senza pagamento: usa il check in alto per
                    segnarlo come svolto.</span
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
                        @click="pending = null"
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

            <!-- Azioni secondarie per stato (il primario è il check in header). -->
            <Button
                v-else-if="isPayableOpen"
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
        </template>
    </ActionSheet>
</template>
