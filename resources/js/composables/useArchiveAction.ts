/**
 * useArchiveAction — pattern condiviso "kebab-action → ConfirmDialog → delete".
 *
 * Tutte le Index del dominio (Clients, Invoices, ExpenseItems,
 * RecurringDeadlines) usano lo stesso flow: click su "Archivia" del
 * dropdown apre una ConfirmDialog; conferma → DELETE Inertia. Questo
 * composable centralizza lo stato (open + target) e il submit, lasciando
 * al chiamante la generazione dell'URL destroy (Wayfinder fortemente
 * tipato per entità).
 *
 * Uso:
 *   const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
 *     useArchiveAction<InvoiceListItem>(
 *         (item) => InvoiceController.destroy.url({ invoice: item.id }),
 *     );
 */
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Ref } from 'vue';

type ItemWithId = { id: number };

export function useArchiveAction<T extends ItemWithId>(
    getDestroyUrl: (item: T) => string,
): {
    archiveOpen: Ref<boolean>;
    archiveTarget: Ref<T | null>;
    askArchive: (item: T) => void;
    confirmArchive: () => void;
} {
    const archiveOpen = ref(false);
    const archiveTarget = ref<T | null>(null) as Ref<T | null>;
    const form = useForm({});

    function askArchive(item: T): void {
        archiveTarget.value = item;
        archiveOpen.value = true;
    }

    function confirmArchive(): void {
        if (!archiveTarget.value) {
            return;
        }

        form.delete(getDestroyUrl(archiveTarget.value), {
            preserveScroll: true,
            onFinish: () => {
                archiveOpen.value = false;
                archiveTarget.value = null;
            },
        });
    }

    return {
        archiveOpen,
        archiveTarget,
        askArchive,
        confirmArchive,
    };
}
