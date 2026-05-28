<script setup lang="ts">
/**
 * AppSidebar — sidebar principale.
 *
 * Modalità:
 * - Default: nav principale (Lavoro + Sistema).
 * - Settings (path /settings/voci-spesa o /settings/scadenze-tipo):
 *   nav sostituita dalle voci di impostazioni di sistema + bottone
 *   "← Indietro" che torna alla nav principale (link a Dashboard).
 *
 * Le impostazioni personali (profilo, sicurezza, aspetto) vivono al di
 * fuori di questa sidebar — si accede dal dropdown utente.
 */
import { Link } from '@inertiajs/vue3';
import {
    PhArrowLeft,
    PhBell,
    PhBookOpen,
    PhCalendarDots,
    PhCaretLeft,
    PhCaretRight,
    PhGearSix,
    PhHouse,
    PhListChecks,
    PhReceipt,
    PhTag,
    PhUsers,
} from '@phosphor-icons/vue';
import { computed, type Component } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, designSystem } from '@/routes';
import { index as scadenzeTipoIndex } from '@/routes/settings/scadenze-tipo';
import { index as vociSpesaIndex } from '@/routes/settings/voci-spesa';

type NavLink = {
    label: string;
    icon: Component;
    href: string | ReturnType<typeof dashboard>;
};

type NavSection = { label: string; items: NavLink[] };

const { isCurrentOrParentUrl, currentUrl } = useCurrentUrl();
const { state, toggleSidebar } = useSidebar();

const SETTINGS_PREFIXES = ['/settings/voci-spesa', '/settings/scadenze-tipo'];

/* Modalità settings: la sidebar swap il content quando l'utente è dentro
 * uno dei tab di sistema. /settings/profile (personali) NON triggerano lo
 * swap perché vivono fuori dalla nav principale. */
const isSettingsMode = computed(() =>
    SETTINGS_PREFIXES.some((prefix) => currentUrl.value.startsWith(prefix)),
);

const mainSections: NavSection[] = [
    {
        label: 'Lavoro',
        items: [
            { label: 'Dashboard', icon: PhHouse, href: dashboard() },
            { label: 'Fatture', icon: PhReceipt, href: dashboard() },
            { label: 'Clienti', icon: PhUsers, href: dashboard() },
            { label: 'Scadenze', icon: PhBell, href: dashboard() },
            { label: 'Anni', icon: PhCalendarDots, href: dashboard() },
        ],
    },
    {
        label: 'Sistema',
        items: [
            { label: 'Impostazioni', icon: PhGearSix, href: vociSpesaIndex() },
            { label: 'Design system', icon: PhBookOpen, href: designSystem() },
        ],
    },
];

const settingsSections: NavSection[] = [
    {
        label: 'Impostazioni',
        items: [
            { label: 'Voci di spesa', icon: PhTag, href: vociSpesaIndex() },
            {
                label: 'Scadenze tipo',
                icon: PhListChecks,
                href: scadenzeTipoIndex(),
            },
        ],
    },
];

const sections = computed(() =>
    isSettingsMode.value ? settingsSections : mainSections,
);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="border-r border-sidebar-border">
        <!-- Header altezza FISSA in entrambe modalità per non far ballare
             il contenuto sotto quando si collassa. Il logo (visibile solo
             in expanded) sta dentro, centrato verticalmente. -->
        <SidebarHeader class="flex h-14 shrink-0 items-start px-3">
            <Link
                :href="dashboard()"
                class="flex items-center px-2 py-0.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
                aria-label="Studiofinance · Home"
            >
                <AppLogo />
            </Link>
        </SidebarHeader>

        <SidebarContent class="gap-3 py-2">
            <!-- Settings mode: bottone "← Indietro" come prima cosa. -->
            <SidebarGroup v-if="isSettingsMode" class="px-0 py-1">
                <SidebarGroupContent>
                    <SidebarMenu class="gap-0.5">
                        <SidebarMenuItem>
                            <Link
                                :href="dashboard()"
                                class="group/nav relative flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75 transition-colors hover:text-foreground"
                            >
                                <PhArrowLeft :size="14" class="shrink-0" />
                                <span class="truncate group-data-[collapsible=icon]:hidden">
                                    Indietro
                                </span>
                            </Link>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup
                v-for="section in sections"
                :key="section.label"
                class="px-0 py-1"
            >
                <SidebarGroupLabel class="kicker px-4">
                    {{ section.label }}
                </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu class="gap-0.5">
                        <SidebarMenuItem
                            v-for="item in section.items"
                            :key="item.label"
                        >
                            <Link
                                :href="item.href"
                                class="group/nav relative flex items-center gap-2 px-4 py-1.75 text-13 transition-colors"
                                :class="
                                    isCurrentOrParentUrl(item.href)
                                        ? 'font-medium text-foreground'
                                        : 'text-foreground/75 hover:text-foreground'
                                "
                            >
                                <!-- Active: barra accent piena, sempre visibile -->
                                <span
                                    v-if="isCurrentOrParentUrl(item.href)"
                                    class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-accent-vivid"
                                    aria-hidden="true"
                                />
                                <!-- Idle: barra ghost muted, fade-in solo in hover -->
                                <span
                                    v-else
                                    class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-muted-foreground/40 opacity-0 transition-opacity group-hover/nav:opacity-100"
                                    aria-hidden="true"
                                />
                                <!-- Icona: active=fill sempre. Idle=regular↔fill crossfade su hover. -->
                                <component
                                    v-if="isCurrentOrParentUrl(item.href)"
                                    :is="item.icon"
                                    weight="fill"
                                    class="size-4 shrink-0"
                                />
                                <span
                                    v-else
                                    class="relative inline-flex size-4 shrink-0 items-center justify-center"
                                >
                                    <component
                                        :is="item.icon"
                                        weight="regular"
                                        class="absolute size-4 transition-opacity group-hover/nav:opacity-0"
                                    />
                                    <component
                                        :is="item.icon"
                                        weight="fill"
                                        class="absolute size-4 opacity-0 transition-opacity group-hover/nav:opacity-100"
                                    />
                                </span>
                                <span class="truncate group-data-[collapsible=icon]:hidden">
                                    {{ item.label }}
                                </span>
                            </Link>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border px-2 py-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <!-- Collapse toggle a cavallo del bordo destro della sidebar.
         FUORI dal Sidebar wrapper per non finire dentro il suo stacking
         context (z-10 fixed). Posizione fixed con left calcolata via
         peer-data sullo stato del Sidebar (che ha class "peer"). Variant
         outline = bg-card solido + border. Radius default (4px) per restare
         coerente col system — niente rounded-full, niente shadow. -->
    <Button
        variant="outline"
        size="icon"
        :aria-label="state === 'expanded' ? 'Collassa sidebar' : 'Espandi sidebar'"
        class="
            fixed top-3.5 z-50 hidden md:inline-flex
            text-muted-foreground bg-card hover:bg-card
            transition-[left,background-color,border-color,color]
            duration-200 ease-linear
            peer-data-[state=expanded]:left-[calc(var(--sidebar-width)-12px)]
            peer-data-[state=collapsed]:left-[calc(var(--sidebar-width-icon)-12px)]
        "
        @click="toggleSidebar"
    >
        <PhCaretLeft v-if="state === 'expanded'" :size="11" weight="bold" />
        <PhCaretRight v-else :size="11" weight="bold" />
    </Button>

    <slot />
</template>
