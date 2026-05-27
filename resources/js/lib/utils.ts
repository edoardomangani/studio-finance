import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';

/**
 * tailwind-merge esteso con i font-size custom del DS (text-2xs 11px, text-13 13px).
 * Senza questa estensione, tailwind-merge tratta `text-13` come text-color e
 * lo considera in conflitto con `text-foreground` / `text-primary-foreground`
 * → rimuove `text-13` → l'elemento cade a 16px (default browser).
 */
const twMerge = extendTailwindMerge({
    extend: {
        classGroups: {
            'font-size': [{ text: ['2xs', '13'] }],
        },
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
