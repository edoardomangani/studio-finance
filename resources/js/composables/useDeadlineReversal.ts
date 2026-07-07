import { router } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    markNotDue as markNotDueRoute,
    reopen as reopenRoute,
} from '@/routes/deadlines';
import type { DeadlineListItem } from '@/types';

/**
 * Una transizione di reversibilità (F9) in attesa di conferma inline nel footer
 * dello sheet — niente dialog annidato.
 */
export type PendingReversal = {
    description: string;
    confirmLabel: string;
    url: string;
    destructive: boolean;
};

/**
 * useDeadlineReversal — macchina a stati delle azioni reversibili su una
 * scadenza (marca non dovuta, annulla completamento, riapri). `ask*` arma la
 * conferma; `runReversal` la esegue e chiude lo sheet. Estratto da
 * [[DeadlineSheet]] per isolare il flusso dal form di registrazione pagamento.
 */
export function useDeadlineReversal(
    deadline: Ref<DeadlineListItem | null>,
    open: Ref<boolean>,
) {
    const pending = ref<PendingReversal | null>(null);
    const reversing = ref(false);

    function clear(): void {
        pending.value = null;
    }

    function askMarkNotDue(): void {
        if (!deadline.value) {
            return;
        }

        pending.value = {
            description:
                'La scadenza e il pagamento collegato verranno segnati come non dovuti.',
            confirmLabel: 'Marca non dovuta',
            url: markNotDueRoute({ deadline: deadline.value.id }).url,
            destructive: false,
        };
    }

    function askUndoCompletion(): void {
        if (!deadline.value) {
            return;
        }

        pending.value = {
            description:
                deadline.value.kind === 'fulfillment'
                    ? 'Il completamento verrà annullato: l’adempimento tornerà aperto.'
                    : 'Il completamento verrà annullato: importo e data del pagamento verranno azzerati.',
            confirmLabel: 'Annulla completamento',
            url: reopenRoute({ deadline: deadline.value.id }).url,
            destructive: true,
        };
    }

    function askReopen(): void {
        if (!deadline.value) {
            return;
        }

        pending.value = {
            description:
                'La scadenza tornerà aperta e potrai registrare di nuovo il pagamento.',
            confirmLabel: 'Riapri scadenza',
            url: reopenRoute({ deadline: deadline.value.id }).url,
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

    return {
        pending,
        reversing,
        clear,
        askMarkNotDue,
        askUndoCompletion,
        askReopen,
        runReversal,
    };
}
