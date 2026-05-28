<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { PhCaretRight, PhList } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useSidebar } from '@/components/ui/sidebar';

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

/* Mobile-only sidebar trigger: sotto md la Sidebar diventa Sheet senza
   modo nativo di essere aperta. Su md+ esiste il toggle a cavallo del
   bordo in AppSidebar.vue, quindi qui md:hidden. */
const { toggleSidebar } = useSidebar();
</script>

<template>
    <header class="shrink-0 bg-background">
        <!-- ─── FASCIA TOP (h-12): breadcrumb-titolo · azioni pagina ─── -->
        <div
            class="border-border-soft flex h-12 items-center gap-2 border-b px-3 md:gap-3 md:px-5"
        >
            <!-- Mobile-only: hamburger per aprire la Sidebar in modalità Sheet
                 (sotto 768px). Su md+ nascosto: il toggle vive a cavallo del
                 bordo della sidebar (vedi AppSidebar.vue). -->
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="-ml-1 md:hidden"
                aria-label="Apri menu di navigazione"
                @click="toggleSidebar"
            >
                <PhList :size="16" weight="bold" />
            </Button>

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
                    <PhCaretRight
                        v-if="i < fullCrumbs.length - 1"
                        :size="11"
                        weight="bold"
                        class="shrink-0 text-muted-foreground/40"
                    />
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
