<script setup lang="ts">
/**
 * DeadlineSheet — side-sheet di una scadenza, aperto dal click su una riga di
 * [[deadlines/Index.vue]]. Si alimenta dai dati di riga (nessuna fetch).
 *
 * Scadenza di pagamento APERTA → form di registrazione in primo piano (F7):
 * descrizione, data effettiva, importo precompilato col previsto (RB8) ma
 * modificabile. Submit: planned→paid, open→completed.
 *
 * Altri stati → lettura + reversibilità (F9), confermata inline nel footer:
 * annulla completamento, marca non dovuta, riapri.
 *
 * `side` responsive: destra su desktop, bottom su mobile (tap→registra, CTA h-12).
 */
import { router, useForm } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { formatDateIT, formatEUR } from '@/lib/format';
import {
    markNotDue as markNotDueRoute,
    payment as registerPaymentRoute,
    reopen as reopenRoute,
} from '@/routes/deadlines';
import type { DeadlineListItem, DeadlineStatus } from '@/types';

const props = defineProps<{
    deadline: DeadlineListItem | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const isMobile = useMediaQuery('(max-width: 767px)');

const STATUS_VARIANT: Record<DeadlineStatus, 'default' | 'secondary' | 'outline'> = {
    open: 'default',
    completed: 'secondary',
    not_due: 'outline',
};

const isPayableOpen = computed(
    () => props.deadline?.kind === 'payment' && props.deadline?.status === 'open',
);

const expectedHint = computed(() => {
    if (!props.deadline) {
        return '';
    }

    return props.deadline.expected_amount !== null
        ? `Previsto ${formatEUR(props.deadline.expected_amount)} · modificabile`
        : 'Nessun previsto: inserisci l’importo dall’F24.';
});

const canRestoreExpected = computed(
    () =>
        props.deadline?.expected_amount != null &&
        form.amount !== String(props.deadline.expected_amount),
);

const form = useForm<{ description: string; amount: string; paid_at: string }>({
    description: '',
    amount: '',
    paid_at: '',
});

function todayISO(): string {
    const now = new Date();
    const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);

    return local.toISOString().slice(0, 10);
}

// Conferma inline (no dialog annidato nello sheet): una transizione di
// reversibilità in attesa di conferma nel footer.
type PendingReversal = {
    description: string;
    confirmLabel: string;
    url: string;
    destructive: boolean;
};
const pending = ref<PendingReversal | null>(null);
const reversing = ref(false);

// Precompila il form e azzera la conferma ad ogni apertura.
watch(open, (isOpen) => {
    if (!isOpen || !props.deadline) {
        return;
    }

    pending.value = null;
    form.clearErrors();
    form.defaults({
        description: props.deadline.name,
        amount: props.deadline.expected_amount !== null ? String(props.deadline.expected_amount) : '',
        paid_at: todayISO(),
    });
    form.reset();
});

function askMarkNotDue(): void {
    if (!props.deadline) {
        return;
    }

    pending.value = {
        description: 'La scadenza e il pagamento collegato verranno segnati come non dovuti.',
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
        description: 'Il completamento verrà annullato: importo e data del pagamento verranno azzerati.',
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
        description: 'La scadenza tornerà aperta e potrai registrare di nuovo il pagamento.',
        confirmLabel: 'Riapri scadenza',
        url: reopenRoute({ deadline: props.deadline.id }).url,
        destructive: false,
    };
}

function runReversal(): void {
    if (!pending.value) {
        return;
    }

    router.post(pending.value.url, {}, {
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
    });
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
    });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            :side="isMobile ? 'bottom' : 'right'"
            :class="isMobile ? 'max-h-[90vh]' : 'w-full sm:max-w-md'"
        >
            <SheetHeader>
                <SheetTitle>{{ deadline?.name ?? 'Scadenza' }}</SheetTitle>
            </SheetHeader>

            <div v-if="deadline" class="flex min-h-0 flex-1 flex-col overflow-y-auto px-6 py-4">
                <!-- Meta scadenza: contesto in lettura, niente card. -->
                <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 border-b border-border pb-4 text-13">
                    <dt class="text-muted-foreground">Scadenza</dt>
                    <dd class="tabular text-right text-foreground">{{ formatDateIT(deadline.due_at) }}</dd>
                    <dt class="text-muted-foreground">Anno</dt>
                    <dd class="tabular text-right text-foreground">{{ deadline.year }}</dd>
                    <template v-if="deadline.annual_expense_name">
                        <dt class="text-muted-foreground">Voce di spesa</dt>
                        <dd class="text-right text-foreground">{{ deadline.annual_expense_name }}</dd>
                    </template>
                    <dt class="text-muted-foreground">Stato</dt>
                    <dd class="text-right">
                        <Badge :variant="STATUS_VARIANT[deadline.status]">{{ deadline.status_label }}</Badge>
                    </dd>
                </dl>

                <!-- Pagamento aperto → form di registrazione. -->
                <form
                    v-if="isPayableOpen"
                    id="register-payment"
                    class="pt-4"
                    @submit.prevent="submit"
                >
                    <FieldGroup>
                        <FormField label="Descrizione" for="payment-description">
                            <Input id="payment-description" v-model="form.description" />
                            <template v-if="form.errors.description" #error>{{ form.errors.description }}</template>
                        </FormField>

                        <FormField label="Data del pagamento" for="payment-date">
                            <Input
                                id="payment-date"
                                v-model="form.paid_at"
                                type="date"
                                :max="todayISO()"
                            />
                            <template v-if="form.errors.paid_at" #error>{{ form.errors.paid_at }}</template>
                        </FormField>

                        <FormField label="Importo" for="payment-amount" :hint="expectedHint">
                            <Input
                                id="payment-amount"
                                v-model="form.amount"
                                inputmode="decimal"
                                placeholder="0.00"
                                class="tabular"
                            />
                            <template #hint>
                                <span class="flex items-center justify-between gap-2">
                                    <span>{{ expectedHint }}</span>
                                    <button
                                        v-if="canRestoreExpected"
                                        type="button"
                                        class="text-accent-vivid hover:underline"
                                        @click="restoreExpected"
                                    >
                                        ripristina previsto
                                    </button>
                                </span>
                            </template>
                            <template v-if="form.errors.amount" #error>{{ form.errors.amount }}</template>
                        </FormField>
                    </FieldGroup>
                </form>

                <!-- Pagamento registrato → lettura. -->
                <dl
                    v-else-if="deadline.payment && deadline.payment.status === 'paid'"
                    class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 pt-4 text-13"
                >
                    <dt class="text-muted-foreground">Importo pagato</dt>
                    <dd class="tabular text-right font-medium text-foreground">
                        {{ deadline.payment.amount !== null ? formatEUR(deadline.payment.amount) : '—' }}
                    </dd>
                    <dt class="text-muted-foreground">Data</dt>
                    <dd class="tabular text-right text-foreground">{{ formatDateIT(deadline.payment.paid_at) }}</dd>
                </dl>

                <p v-else class="pt-4 text-13 text-muted-foreground">
                    <span v-if="deadline.kind === 'fulfillment'">Adempimento, nessun pagamento collegato.</span>
                    <span v-else-if="deadline.status === 'not_due'">Scadenza segnata come non dovuta.</span>
                    <span v-else>Nessun pagamento da registrare.</span>
                </p>
            </div>

            <SheetFooter>
                <!-- Conferma inline di una reversibilità. -->
                <div v-if="pending" class="w-full space-y-3">
                    <p class="text-13 text-muted-foreground">{{ pending.description }}</p>
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" class="flex-1" :disabled="reversing" @click="pending = null">
                            Annulla
                        </Button>
                        <Button
                            type="button"
                            :variant="pending.destructive ? 'destructive' : 'default'"
                            class="flex-1"
                            :disabled="reversing"
                            @click="runReversal"
                        >
                            <Spinner v-if="reversing" />
                            {{ pending.confirmLabel }}
                        </Button>
                    </div>
                </div>

                <!-- Azioni per stato. -->
                <template v-else-if="isPayableOpen">
                    <Button type="submit" form="register-payment" class="h-12 w-full" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Registra pagamento
                    </Button>
                    <Button type="button" variant="ghost" size="sm" class="w-full" @click="askMarkNotDue">
                        Marca come non dovuta
                    </Button>
                </template>

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

                <SheetClose v-else as-child>
                    <Button type="button" variant="outline" class="w-full">Chiudi</Button>
                </SheetClose>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
