<script setup lang="ts">
/**
 * Tab Pagamenti della vista anno: stessa tabella (e azioni: modifica/elimina i
 * manuali, vai alla scadenza per i pagamenti collegati) della pagina Pagamenti,
 * scopata sull'anno, via [[PaymentsTable]]. CTA "Nuovo pagamento" apre il form
 * manuale esposto dalla tabella.
 */
import { PhPlus } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import PaymentsTable from '@/pages/payments/PaymentsTable.vue';
import type { AnnualExpenseForPicker, YearShow } from '@/types';

defineProps<{
    year: YearShow;
    annualExpenses: AnnualExpenseForPicker[];
}>();

const table = ref<InstanceType<typeof PaymentsTable> | null>(null);
</script>

<template>
    <div class="flex flex-col gap-3">
        <header class="flex items-center justify-between">
            <h2 class="kicker text-muted-foreground">{{ year.payments.length }} pagamenti</h2>
            <Button size="sm" @click="table?.openCreate()">
                <PhPlus :size="14" weight="bold" />
                Nuovo pagamento
            </Button>
        </header>

        <PaymentsTable ref="table" :payments="year.payments" :annual-expenses="annualExpenses" with-totals>
            <template #empty>Nessun pagamento registrato per quest'anno.</template>
        </PaymentsTable>
    </div>
</template>
