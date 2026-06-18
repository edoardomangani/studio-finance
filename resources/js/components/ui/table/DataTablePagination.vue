<script setup lang="ts">
/**
 * DataTablePagination — paginazione server-side, responsive.
 *
 * - Desktop (≥sm): range "1–25 di 124" + strip numerata [« ‹ 1 … 5 › »].
 * - Mobile (<sm): compatta [‹ Indietro · Pag X di Y · Avanti ›] — bersagli
 *   grandi, niente numeri/edge che si accavallano su schermo stretto.
 *
 * Stato: passa current/total/perPage e ascolta `update:page`.
 */
import { PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
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
    <div class="mt-4">
        <!-- Desktop (≥sm): range + strip numerata -->
        <div class="hidden items-center justify-between text-13 sm:flex">
            <span class="tabular text-xs text-muted-foreground">
                {{ from.toLocaleString('it-IT') }}–{{ to.toLocaleString('it-IT') }}
                <span class="text-muted-foreground/60">di</span>
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
                            class="h-8 w-8 text-xs tabular"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :index="idx" class="text-muted-foreground" />
                    </template>
                    <PaginationNext />
                    <PaginationLast />
                </PaginationContent>
            </Pagination>
        </div>

        <!-- Mobile (<sm): compatta -->
        <div class="flex items-center justify-between gap-2 sm:hidden">
            <Button
                variant="outline"
                size="sm"
                :disabled="page <= 1"
                @click="emit('update:page', page - 1)"
            >
                <PhCaretLeft :size="14" />
                Indietro
            </Button>
            <span class="tabular text-xs text-muted-foreground">
                Pag {{ page }} di {{ totalPages }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="page >= totalPages"
                @click="emit('update:page', page + 1)"
            >
                Avanti
                <PhCaretRight :size="14" />
            </Button>
        </div>

        <span class="sr-only">Pagina {{ page }} di {{ totalPages }}</span>
    </div>
</template>
