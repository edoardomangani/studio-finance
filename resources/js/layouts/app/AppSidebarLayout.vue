<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';

type Crumb = { label: string; href?: string };
type StatusTone = 'positive' | 'negative' | 'neutral' | 'warning';
type Status = { label: string; tone?: StatusTone };

withDefaults(
    defineProps<{
        pageTitle?: string;
        pageCrumbs?: Crumb[];
        pageStatus?: Status;
        subbar?: boolean;
        /**
         * Quando true, il main non applica padding canonico — la pagina
         * gestisce da sé layout/padding (utile per DesignSystem.vue e simili).
         */
        bare?: boolean;
    }>(),
    { pageCrumbs: () => [], subbar: false, bare: false },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader
                :title="pageTitle"
                :crumbs="pageCrumbs"
                :status="pageStatus"
                :subbar="subbar"
            />
            <!-- View container: main scrollabile + mount-point per filter sidebar.
                 Pagine senza filtri: scrivono direttamente il content (slot).
                 Pagine con filtri: <Teleport to="#page-right-sidebar"> per
                 piazzare FilterSidebar come flex sibling del main. -->
            <div class="min-h-0 flex-1 overflow-hidden">
                <div class="flex h-full">
                    <main
                        class="min-w-0 flex-1 overflow-y-auto"
                        :class="bare ? '' : 'p-6 md:p-8'"
                    >
                        <slot />
                    </main>
                    <div id="page-right-sidebar" class="contents" />
                </div>
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
