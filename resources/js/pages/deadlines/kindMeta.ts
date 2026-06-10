import { PhCoins, PhFileText } from '@phosphor-icons/vue';
import type { Component } from 'vue';
import type { DeadlineKind } from '@/types';

/**
 * Presentazione del tipo scadenza: variante del badge + icona. Condivisa tra
 * lista ([[DeadlinesTable.vue]]), scadenze tipo ([[RecurringDeadlines/Index.vue]])
 * e wizard apertura anno ([[WizardDeadlinesStep.vue]]).
 * payment = genera un pagamento, fulfillment = adempimento informativo.
 */
export const DEADLINE_KIND_META: Record<
    DeadlineKind,
    { variant: 'secondary' | 'outline'; icon: Component }
> = {
    payment: { variant: 'secondary', icon: PhCoins },
    fulfillment: { variant: 'outline', icon: PhFileText },
};
