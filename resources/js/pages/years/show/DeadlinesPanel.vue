<script setup lang="ts">
/**
 * Tab Scadenze della vista anno: stessa tabella (e azioni: sheet, modifica,
 * archivio) della pagina Scadenze, scopata sull'anno, via [[DeadlinesTable]].
 * CTA "Nuova scadenza" apre il form ad-hoc esposto dalla tabella.
 */
import { PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import DeadlinesTable from '@/pages/deadlines/DeadlinesTable.vue';
import type {
    AnnualExpenseForPicker,
    EnumOption,
    YearOption,
    YearShow,
} from '@/types';

const props = defineProps<{
    year: YearShow;
    annualExpenses: AnnualExpenseForPicker[];
    yearOptions: YearOption[];
    kindOptions: EnumOption[];
}>();

const table = ref<InstanceType<typeof DeadlinesTable> | null>(null);

// Niente toggle di stato (un solo anno, poche righe): le aperte salgono in
// cima, le chiuse (completate / non dovute) in coda, poi per data.
const sortedDeadlines = computed(() => {
    const rank = (status: string): number => (status === 'open' ? 0 : 1);

    return [...props.year.deadlines].sort(
        (a, b) =>
            rank(a.status) - rank(b.status) || a.due_at.localeCompare(b.due_at),
    );
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <header class="flex items-center justify-between">
            <h2 class="kicker text-muted-foreground">
                {{ year.deadlines.length }} scadenze
            </h2>
            <Button size="sm" @click="table?.openCreate()">
                <PhPlus :size="14" weight="bold" />
                Nuova scadenza
            </Button>
        </header>

        <DeadlinesTable
            ref="table"
            :deadlines="sortedDeadlines"
            :annual-expenses="annualExpenses"
            :year-options="yearOptions"
            :kind-options="kindOptions"
            with-totals
        >
            <template #empty>Nessuna scadenza in quest'anno.</template>
        </DeadlinesTable>
    </div>
</template>
