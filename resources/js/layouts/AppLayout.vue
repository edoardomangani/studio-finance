<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

type Crumb = { label: string; href?: string };
type StatusTone = 'positive' | 'negative' | 'neutral' | 'warning';
type Status = { label: string; tone?: StatusTone };

/* Layout props (popolati dalle pagine via setLayoutProps di Inertia v3).
   Default: undefined/false. Se non impostati, topbar mostra solo la fascia top
   (breadcrumb + ⌘K + bell), niente subbar. */
withDefaults(
    defineProps<{
        pageTitle?: string;
        pageCrumbs?: Crumb[];
        pageStatus?: Status;
        subbar?: boolean;
        bare?: boolean;
    }>(),
    { pageCrumbs: () => [], subbar: false, bare: false },
);
</script>

<template>
    <AppSidebarLayout
        :page-title="pageTitle"
        :page-crumbs="pageCrumbs"
        :page-status="pageStatus"
        :subbar="subbar"
        :bare="bare"
    >
        <slot />
    </AppSidebarLayout>
</template>
