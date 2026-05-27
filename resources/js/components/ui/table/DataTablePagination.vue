<script setup lang="ts">
/**
 * DataTablePagination — strip canonica con numeri pagine cliccabili.
 *
 * Layout: [range "1–25 di 124"]                  [« ‹ 1 … 5 › »]
 *
 * Stato server-side: la pagina passa current/total/perPage e ascolta
 * `update:page`. Numeri pagine + ellipsis via primitive shadcn Pagination.
 * Niente selector "per pagina" — il valore è fisso lato pagina/server.
 */
import { computed } from 'vue'
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'

const props = defineProps<{
    /** Pagina corrente (1-based). */
    page: number
    /** Numero righe per pagina. */
    perPage: number
    /** Numero totale righe (server-side). */
    total: number
}>()

const emit = defineEmits<{
    (e: 'update:page', page: number): void
}>()

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1))
const to = computed(() => Math.min(props.page * props.perPage, props.total))
</script>

<template>
    <div class="mt-4 flex items-center justify-between px-1 text-13">
        <span class="font-mono text-2xs tabular text-muted-foreground">
            {{ from.toLocaleString('it-IT') }}–{{ to.toLocaleString('it-IT') }}
            <span class="px-1 text-muted-foreground/60">di</span>
            {{ total.toLocaleString('it-IT') }}
        </span>

        <Pagination
            v-slot="{ page: cur }"
            :total="total"
            :items-per-page="perPage"
            :sibling-count="0"
            show-edges
            :default-page="page"
            :page="page"
            @update:page="(v) => emit('update:page', v)"
        >
            <PaginationContent v-slot="{ items }" class="gap-1">
                <PaginationFirst />
                <PaginationPrevious />
                <template v-for="(item, idx) in items" :key="idx">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :value="item.value"
                        :is-active="cur === item.value"
                        size="icon-sm"
                        class="h-8 w-8 font-mono text-2xs tabular"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis v-else :index="idx" class="text-muted-foreground" />
                </template>
                <PaginationNext />
                <PaginationLast />
            </PaginationContent>
        </Pagination>

        <span class="sr-only">Pagina {{ page }} di {{ totalPages }}</span>
    </div>
</template>
