import { PhCheckCircle, PhCircleDashed, PhProhibit } from '@phosphor-icons/vue';
import type { Component } from 'vue';
import type { DeadlineStatus } from '@/types';

/**
 * Presentazione dello stato scadenza: variante del badge + icona. Condivisa
 * tra la lista ([[deadlines/Index.vue]]) e il side-sheet ([[DeadlineSheet.vue]]).
 * aperta = da fare, completata = fatta, non dovuta = preclusa.
 */
export const DEADLINE_STATUS_META: Record<
    DeadlineStatus,
    { variant: 'default' | 'secondary' | 'outline'; icon: Component }
> = {
    open: { variant: 'default', icon: PhCircleDashed },
    completed: { variant: 'secondary', icon: PhCheckCircle },
    not_due: { variant: 'outline', icon: PhProhibit },
};
