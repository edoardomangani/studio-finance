<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { PhCaretRight } from '@phosphor-icons/vue';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <nav
        class="flex min-w-0 flex-1 items-center gap-1.5"
        aria-label="Breadcrumb"
    >
        <template v-for="(item, index) in breadcrumbs" :key="index">
            <Link
                v-if="index !== breadcrumbs.length - 1"
                :href="item.href"
                class="shrink-0 truncate text-xs text-muted-foreground transition-colors hover:text-foreground"
            >
                {{ item.title }}
            </Link>
            <span
                v-else
                class="truncate text-13 font-medium text-foreground uppercase"
                aria-current="page"
            >
                {{ item.title }}
            </span>
            <PhCaretRight
                v-if="index !== breadcrumbs.length - 1"
                :size="11"
                weight="bold"
                class="shrink-0 text-muted-foreground/40"
                aria-hidden="true"
            />
        </template>
    </nav>
</template>
