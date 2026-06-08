<script setup lang="ts">
/**
 * Tab Fatture della vista anno: stessa tabella (e azioni) della pagina Fatture,
 * scopata sull'anno, via il componente condiviso [[InvoicesTable]]. CTA "Nuova
 * fattura" verso la pagina di creazione.
 */
import { Link } from '@inertiajs/vue3';
import { PhPlus } from '@phosphor-icons/vue';
import { Button } from '@/components/ui/button';
import InvoicesTable from '@/pages/invoices/InvoicesTable.vue';
import { create as invoiceCreate } from '@/routes/invoices';
import { index as yearsIndex, show as yearShow } from '@/routes/years';
import type { YearShow } from '@/types';

const props = defineProps<{ year: YearShow }>();

// Origine per le breadcrumb di dettaglio: Anni / {anno}.
const origin = [
    { label: 'Anni', href: yearsIndex().url },
    { label: String(props.year.year), href: yearShow(props.year.year).url },
];
</script>

<template>
    <div class="flex flex-col gap-3">
        <header class="flex items-center justify-between">
            <h2 class="kicker text-muted-foreground">
                {{ year.invoices.length }} fatture
            </h2>
            <Button as-child size="sm">
                <Link :href="invoiceCreate().url">
                    <PhPlus :size="14" weight="bold" />
                    Nuova fattura
                </Link>
            </Button>
        </header>

        <InvoicesTable :invoices="year.invoices" :origin="origin" with-totals>
            <template #empty>Nessuna fattura in quest'anno.</template>
        </InvoicesTable>
    </div>
</template>
