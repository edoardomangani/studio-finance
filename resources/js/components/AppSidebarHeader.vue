<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { PhArrowLeft } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { account } from '@/routes';

type Crumb = { label: string; href?: string };
type StatusTone = 'positive' | 'negative' | 'neutral' | 'warning';
type Status = { label: string; tone?: StatusTone };

const props = withDefaults(
    defineProps<{
        title?: string;
        crumbs?: Crumb[];
        status?: Status;
        subbar?: boolean;
    }>(),
    { crumbs: () => [], subbar: false },
);

/* Breadcrumb = solo contesto pagina, no auto-prepend del nome studio.
   Studiofinance è single-tenant: il brand vive nel logo della sidebar
   (AppLogo). Quando arriverà multi-tenant, qui prependerà lo studio attivo.
   - Se crumbs sono passate, mostro quelle.
   - Se manca crumbs ma c'è title, mostro solo title.
   - Se manca tutto (es. dashboard root), il breadcrumb resta vuoto. */
const fullCrumbs = computed<Crumb[]>(() => {
    if (props.crumbs.length > 0) {
        return props.crumbs;
    }

    if (props.title) {
        return [{ label: props.title }];
    }

    return [];
});

const statusToneClass: Record<StatusTone, string> = {
    positive: 'pill pill--success',
    negative: 'pill pill--danger',
    neutral: 'pill pill--neutral',
    warning: 'pill pill--warning',
};

const page = usePage();
const { getInitials } = useInitials();
const user = computed(() => page.props.auth.user);
const showAvatar = computed(
    () => !!user.value.avatar && user.value.avatar !== '',
);

/* Top-left mobile (<lg): se la pagina dichiara crumb con href (= ha un parent)
   mostra una freccia back verso il parent più vicino; altrimenti l'avatar che
   apre la pagina-account. Da lg+ questa zona è nascosta (il toggle sidebar vive
   a cavallo del bordo in AppSidebar.vue). */
const backHref = computed<string | null>(() => {
    const linked = props.crumbs.filter((c) => c.href);

    return linked.length > 0 ? linked[linked.length - 1].href! : null;
});
</script>

<template>
    <header class="shrink-0 bg-background">
        <!-- ─── FASCIA TOP (h-12): breadcrumb-titolo · azioni pagina ─── -->
        <div
            class="flex h-12 items-center gap-2 border-b border-border-soft px-3 md:gap-3 md:px-5"
        >
            <!-- Mobile (<lg): avatar→account su pagina-radice, freccia back su
                 dettaglio. Da lg+ nascosto: il toggle sidebar vive a cavallo
                 del bordo (vedi AppSidebar.vue). -->
            <div class="-ml-1 flex shrink-0 items-center lg:hidden">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="flex size-9 items-center justify-center rounded-md text-foreground transition-colors hover:bg-accent"
                    aria-label="Indietro"
                >
                    <PhArrowLeft :size="18" weight="bold" />
                </Link>
                <Link
                    v-else
                    :href="account()"
                    class="flex items-center justify-center rounded-md p-1 transition-colors hover:bg-accent"
                    aria-label="Account"
                >
                    <Avatar class="size-7 overflow-hidden rounded">
                        <AvatarImage
                            v-if="showAvatar"
                            :src="user.avatar!"
                            :alt="user.name"
                        />
                        <AvatarFallback
                            class="rounded bg-secondary text-2xs font-medium text-foreground"
                        >
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                </Link>
            </div>

            <!-- Breadcrumb: parent muted, ultimo segmento promosso a titolo -->
            <nav
                class="flex min-w-0 flex-1 items-center gap-1.5"
                aria-label="Breadcrumb"
            >
                <template v-for="(c, i) in fullCrumbs" :key="i">
                    <Link
                        v-if="c.href"
                        :href="c.href"
                        class="shrink-0 truncate text-xs text-muted-foreground transition-colors hover:text-foreground"
                    >
                        {{ c.label }}
                    </Link>
                    <span
                        v-else-if="i === fullCrumbs.length - 1"
                        class="truncate text-13 font-medium text-foreground uppercase"
                    >
                        {{ c.label }}
                    </span>
                    <span
                        v-else
                        class="shrink-0 truncate text-xs text-muted-foreground"
                    >
                        {{ c.label }}
                    </span>
                    <span
                        v-if="i < fullCrumbs.length - 1"
                        aria-hidden="true"
                        class="shrink-0 text-xs text-muted-foreground/40 select-none"
                    >/</span>
                </template>

                <span
                    v-if="status"
                    :class="['ml-2', statusToneClass[status.tone ?? 'neutral']]"
                >
                    {{ status.label }}
                </span>
            </nav>

            <!-- Mount point: azioni primarie della pagina. -->
            <div
                id="page-topbar-actions"
                class="flex shrink-0 items-center gap-2"
            />
        </div>

        <!-- ─── SUBBAR (h-11): search testuale · filtri · viste ───
             Sempre presente nel DOM (i Teleport trovano i mount-point), ma
             nascosta con display:none se la pagina non dichiara `subbar: true`. -->
        <div
            v-show="subbar"
            class="flex h-11 items-center gap-3 border-b border-border px-3 md:px-5"
        >
            <!-- Mount point: search testuale della pagina (page-scoped). -->
            <div
                id="page-topbar-search"
                class="flex max-w-xs flex-1 items-center"
            />

            <!-- Mount point: bottone Filtri (apre pannello a destra). -->
            <div id="page-topbar-filters" class="flex items-center" />

            <!-- Mount point: toggle viste (Lista/Griglia, ecc). -->
            <div id="page-topbar-views" class="flex items-center" />

            <!-- Spacer per riempire la subbar (no actions qui — sono in topbar). -->
            <div class="flex-1" />
        </div>
    </header>
</template>
