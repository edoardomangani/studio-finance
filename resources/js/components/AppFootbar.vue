<script setup lang="ts">
/**
 * AppFootbar — tab bar mobile, visibile solo sotto lg (<1024px).
 *
 * Sostituisce l'hamburger: le 5 destinazioni ad alta frequenza vivono qui,
 * tutto il resto (account, impostazioni, archivio) sta nella pagina-account
 * raggiunta dall'avatar nel topbar. Stesse route e stesso trattamento active
 * (icona fill + accent) della sidebar desktop, per coerenza tra le modalità.
 */
import { Link, usePage } from '@inertiajs/vue3';
import {
    PhCalendarDots,
    PhListChecks,
    PhReceipt,
    PhSquaresFour,
    PhUsers,
} from '@phosphor-icons/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { index as deadlinesIndex } from '@/routes/deadlines';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as yearsIndex } from '@/routes/years';

type FootbarLink = {
    label: string;
    icon: Component;
    href: string | ReturnType<typeof dashboard>;
    /** Pathname per il match "attivo" quando l'href porta una query. */
    activeMatch?: string | ReturnType<typeof dashboard>;
    badge?: number;
};

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();

const items = computed<FootbarLink[]>(() => [
    { label: 'Dashboard', icon: PhSquaresFour, href: dashboard() },
    { label: 'Anno', icon: PhCalendarDots, href: yearsIndex() },
    {
        label: 'Scadenze',
        icon: PhListChecks,
        href: deadlinesIndex({ query: { state: 'upcoming' } }),
        activeMatch: deadlinesIndex(),
        badge: page.props.nav?.upcomingDeadlines,
    },
    { label: 'Fatture', icon: PhReceipt, href: invoicesIndex() },
    { label: 'Clienti', icon: PhUsers, href: clientsIndex() },
]);

const isActive = (item: FootbarLink): boolean =>
    isCurrentOrParentUrl(item.activeMatch ?? item.href);
</script>

<template>
    <nav
        class="flex h-[calc(5.25rem+env(safe-area-inset-bottom))] shrink-0 items-start justify-between border-t border-sidebar-border bg-card px-6 pt-2.5 lg:hidden"
        aria-label="Navigazione principale"
    >
        <Link
            v-for="item in items"
            :key="item.label"
            :href="item.href"
            class="flex w-16 flex-col items-center justify-center gap-1.5 transition-colors"
            :class="isActive(item) ? 'text-accent-vivid' : 'text-muted-foreground'"
            :aria-current="isActive(item) ? 'page' : undefined"
        >
            <span class="relative inline-flex">
                <component
                    :is="item.icon"
                    :weight="isActive(item) ? 'fill' : 'regular'"
                    :size="24"
                />
                <Badge
                    v-if="item.badge"
                    variant="warning"
                    class="absolute -top-1.5 -right-2.5 min-w-4 justify-center px-1 py-0 text-2xs leading-none"
                >
                    {{ item.badge }}
                </Badge>
            </span>
            <span
                class="w-full truncate text-center text-[10px] leading-none font-semibold"
            >
                {{ item.label }}
            </span>
        </Link>
    </nav>
</template>
