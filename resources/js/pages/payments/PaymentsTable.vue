<script setup lang="ts">
/**
 * PaymentsTable — tabella pagamenti condivisa tra la lista (payments/Index) e
 * il tab Pagamenti della vista anno. Auto-contenuta: colonne + kebab per riga
 * col form di modifica e la ConfirmDialog di eliminazione.
 *
 * Solo i pagamenti MANUALI si modificano/eliminano qui: i pagamenti da scadenza
 * hanno il ciclo di vita sulla scadenza (registra / annulla / riapri / non
 * dovuta), quindi il loro kebab rimanda alle Scadenze invece di editarli, per
 * non desincronizzare la scadenza collegata.
 *
 * Il chrome (search, filtri, paginazione) e il bottone "Nuovo pagamento"
 * restano al chiamante, che apre il form via `openCreate()` (esposto).
 */
import { Link, router } from '@inertiajs/vue3';
import { PhArrowSquareOut, PhPencil, PhTrash } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import { Badge } from '@/components/ui/badge';
import { ConfirmDialog } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableRow,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { formatDateIT, formatEUR } from '@/lib/format';
import PaymentFormDialog from '@/pages/payments/PaymentFormDialog.vue';
import { index as deadlinesIndex } from '@/routes/deadlines';
import type { AnnualExpenseForPicker, PaymentListItem } from '@/types';

// `withTotals`: footer con il totale importi. Solo quando i `payments` sono il
// set completo (vista anno), mai sulla lista paginata.
const props = withDefaults(
    defineProps<{
        payments: PaymentListItem[];
        annualExpenses: AnnualExpenseForPicker[];
        withTotals?: boolean;
    }>(),
    { withTotals: false },
);

const totalAmount = computed(() =>
    props.payments.reduce((acc, p) => acc + (p.amount ?? 0), 0),
);

// Dialog crea/modifica pagamento manuale: formPayment null = creazione.
const formOpen = ref(false);
const formPayment = ref<PaymentListItem | null>(null);

function openCreate(): void {
    formPayment.value = null;
    formOpen.value = true;
}

function openEdit(payment: PaymentListItem): void {
    formPayment.value = payment;
    formOpen.value = true;
}

// Eliminazione (solo manuali): kebab → ConfirmDialog → DELETE.
const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<PaymentListItem>((payment) =>
        PaymentController.destroy.url({ payment: payment.id }),
    );

// Pagamento da scadenza → si gestisce dalle Scadenze, filtrate sull'anno spesa.
function deadlinesUrl(payment: PaymentListItem): string {
    return payment.expense_year !== null
        ? deadlinesIndex({ query: { year: [payment.expense_year] } }).url
        : deadlinesIndex().url;
}

// Click sulla riga: i manuali si modificano, quelli da scadenza rimandano alla
// scadenza (coerente col resto delle tabelle, dove la riga è interattiva).
function openRow(payment: PaymentListItem): void {
    if (payment.is_manual) {
        openEdit(payment);
    } else {
        router.visit(deadlinesUrl(payment));
    }
}

defineExpose({ openCreate });
</script>

<template>
    <DataTable container-class="hidden lg:block">
        <DataTableHeader>
            <TableHead class="w-[100px]">Data</TableHead>
            <TableHead>Descrizione</TableHead>
            <TableHead>Voce di spesa</TableHead>
            <TableHead class="w-[110px]">Origine</TableHead>
            <TableHead class="w-[70px] text-right">Anno</TableHead>
            <TableHead class="w-[120px] text-right">Importo</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="payments.length === 0" :colspan="7">
                <slot name="empty">Nessun pagamento.</slot>
            </TableEmpty>
            <DataTableRow
                v-for="payment in payments"
                v-else
                :key="payment.id"
                interactive
                @click="openRow(payment)"
            >
                <TableCell class="tabular text-muted-foreground">{{ formatDateIT(payment.paid_at) }}</TableCell>
                <TableCell class="text-foreground">
                    <span v-if="payment.description" class="block truncate" :title="payment.description">
                        {{ payment.description }}
                    </span>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    <span v-if="payment.annual_expense_name" class="block truncate" :title="payment.annual_expense_name">
                        {{ payment.annual_expense_name }}
                    </span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell>
                    <Badge :variant="payment.is_manual ? 'outline' : 'secondary'">
                        {{ payment.is_manual ? 'Manuale' : 'Scadenza' }}
                    </Badge>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{ payment.expense_year ?? '—' }}</TableCell>
                <TableCell class="tabular text-right font-medium text-foreground">{{ formatEUR(payment.amount) }}</TableCell>
                <template #actions>
                    <template v-if="payment.is_manual">
                        <DropdownMenuItem @select="openEdit(payment)">
                            <PhPencil :size="14" />
                            Modifica
                        </DropdownMenuItem>
                        <DropdownMenuItem variant="destructive" @select="askArchive(payment)">
                            <PhTrash :size="14" />
                            Elimina
                        </DropdownMenuItem>
                    </template>
                    <DropdownMenuItem v-else as-child>
                        <Link :href="deadlinesUrl(payment)">
                            <PhArrowSquareOut :size="14" />
                            Vai alla scadenza
                        </Link>
                    </DropdownMenuItem>
                </template>
            </DataTableRow>
        </DataTableBody>
        <TableFooter v-if="withTotals && payments.length > 0">
            <TableRow class="font-medium">
                <TableCell class="text-foreground">Totale</TableCell>
                <TableCell />
                <TableCell />
                <TableCell />
                <TableCell />
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totalAmount) }}</TableCell>
                <TableCell />
            </TableRow>
        </TableFooter>
    </DataTable>

    <!-- Mobile: registro di cassa in righe piatte (la tabella densa vive da lg).
         Tap = stessa azione del click riga: manuale → modifica, scadenza → vai. -->
    <ul class="divide-y lg:hidden">
        <li
            v-if="payments.length === 0"
            class="px-1 py-10 text-center text-sm text-muted-foreground"
        >
            <slot name="empty">Nessun pagamento.</slot>
        </li>
        <li v-for="payment in payments" v-else :key="payment.id">
            <button
                type="button"
                class="flex w-full items-start gap-3 py-3 text-left active:bg-muted/40"
                @click="openRow(payment)"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-foreground">
                        {{ payment.description || payment.annual_expense_name || '—' }}
                    </p>
                    <p class="mt-0.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                        <span class="tabular shrink-0">{{ formatDateIT(payment.paid_at) }}</span>
                        <template v-if="payment.description && payment.annual_expense_name">
                            <span aria-hidden="true">·</span>
                            <span class="min-w-0 truncate">{{ payment.annual_expense_name }}</span>
                        </template>
                        <Badge
                            :variant="payment.is_manual ? 'outline' : 'secondary'"
                            class="ml-0.5 shrink-0"
                        >
                            {{ payment.is_manual ? 'Manuale' : 'Scadenza' }}
                        </Badge>
                    </p>
                </div>
                <span class="tabular shrink-0 pt-0.5 font-medium text-foreground">
                    {{ formatEUR(payment.amount) }}
                </span>
            </button>
        </li>
        <li
            v-if="withTotals && payments.length > 0"
            class="flex items-center justify-between py-3 font-medium"
        >
            <span class="text-foreground">Totale</span>
            <span class="tabular text-foreground">{{ formatEUR(totalAmount) }}</span>
        </li>
    </ul>

    <PaymentFormDialog
        v-model:open="formOpen"
        :payment="formPayment"
        :annual-expenses="annualExpenses"
    />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Eliminare il pagamento?"
        :description="
            archiveTarget
                ? `Il pagamento di ${formatEUR(archiveTarget.amount)} verrà eliminato e non concorrerà più ai totali della spesa.`
                : undefined
        "
        confirm-label="Elimina"
        destructive
        @confirm="confirmArchive"
    />
</template>
