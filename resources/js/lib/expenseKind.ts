import { PhArrowsClockwise, PhScales, PhStamp, PhUmbrella } from '@phosphor-icons/vue';
import type { Component } from 'vue';
import type { ExpenseKind } from '@/types';

/**
 * Presentazione fissa dei quattro tipi di spesa ("famiglie"): icona + colore +
 * label di default. Petrol per i fiscali (imposte, previdenza), tinte smorzate
 * per bolli e costi fissi; l'icona fa da differenziatore primario così i colori
 * restano sobri. Gli stessi colori alimentano la barra impilata della dashboard
 * e i badge nelle tabelle spese. Il NOME mostrato è quello (rinominabile) della
 * famiglia dell'utente, non la label qui.
 */
export const EXPENSE_KIND_META: Record<ExpenseKind, { label: string; color: string; icon: Component }> = {
    tax: { label: 'Imposte', color: 'oklch(40% 0.07 210)', icon: PhScales },
    pension: { label: 'Previdenza', color: 'oklch(54% 0.055 210)', icon: PhUmbrella },
    stamp_duty: { label: 'Bolli', color: 'oklch(64% 0.05 80)', icon: PhStamp },
    fixed: { label: 'Costi fissi', color: 'oklch(62% 0.015 286)', icon: PhArrowsClockwise },
};
