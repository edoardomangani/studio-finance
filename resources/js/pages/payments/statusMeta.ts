import { PhCheckCircle, PhCircleDashed, PhProhibit } from '@phosphor-icons/vue';
import type { Component } from 'vue';
import type { PaymentStatus } from '@/types';

/**
 * Presentazione dello stato pagamento: variante del badge + icona. Specchia
 * [[deadlines/statusMeta]] (stessi codici visivi): pianificato = atteso,
 * pagato = cassa avvenuta, non dovuto = precluso.
 */
export const PAYMENT_STATUS_META: Record<
    PaymentStatus,
    { variant: 'default' | 'secondary' | 'outline'; icon: Component }
> = {
    planned: { variant: 'default', icon: PhCircleDashed },
    paid: { variant: 'secondary', icon: PhCheckCircle },
    not_due: { variant: 'outline', icon: PhProhibit },
};
