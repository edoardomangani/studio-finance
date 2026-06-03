<script setup lang="ts">
/**
 * FilterPanel — chrome riusabile dei pannelli filtri (fatture, scadenze, …).
 *
 * Possiede solo il contenitore responsive: aside push-inline su desktop
 * (Teleport a #page-right-sidebar) e Sheet slide-over su mobile, con footer
 * Pulisci/Annulla/Applica. I CAMPI sono passati dal chiamante via slot di
 * default (scoped), così restano specifici della pagina.
 *
 * Semantica apply: desktop applica live (lo slot chiama `requestLiveApply`
 * a ogni cambio), mobile è draft e applica solo con "Applica". Il bottone
 * "Filtri" del subbar resta della pagina (spesso accanto ad altri controlli).
 *
 * @example
 *   <FilterPanel v-model:open="open" :active-count="n" title="Filtra…" @apply="apply" @clear="clear">
 *     <template #default="{ requestLiveApply }">
 *       <MyFilters v-model="state" @update:model-value="requestLiveApply" />
 *     </template>
 *   </FilterPanel>
 */
import { useMediaQuery } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

withDefaults(
    defineProps<{
        activeCount?: number;
        title?: string;
        description?: string;
    }>(),
    { activeCount: 0, title: 'Filtri', description: undefined },
);

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    (e: 'apply'): void;
    (e: 'clear'): void;
}>();

const isMobile = useMediaQuery('(max-width: 767px)');

// Chiamato dallo slot a ogni cambio campo: su desktop applica subito (aside
// sempre visibile), su mobile è no-op (si attende "Applica").
function requestLiveApply(): void {
    if (!isMobile.value) {
        emit('apply');
    }
}

function applyMobile(): void {
    emit('apply');
    open.value = false;
}
</script>

<template>
    <div class="contents">
        <!-- DESKTOP: aside push-inline, apply live. -->
        <Teleport v-if="!isMobile" to="#page-right-sidebar" defer>
            <aside
                v-show="open"
                class="hidden w-[260px] shrink-0 overflow-y-auto border-l border-border md:block"
            >
                <div class="p-5">
                    <div class="mb-4 flex items-center justify-between h-8">
                        <span class="text-13 font-medium text-foreground">{{ title }}</span>
                        <Button
                            v-if="activeCount > 0"
                            type="button"
                            variant="link"
                            size="sm"
                            @click="emit('clear')"
                        >
                            Pulisci
                        </Button>
                    </div>
                    <slot :request-live-apply="requestLiveApply" />
                </div>
            </aside>
        </Teleport>

        <!-- MOBILE: Sheet slide-over, draft + Applica. -->
        <Sheet v-else v-model:open="open">
            <SheetContent side="right" class="w-full max-w-sm">
                <SheetHeader>
                    <SheetTitle>{{ title }}</SheetTitle>
                    <SheetDescription v-if="description">{{ description }}</SheetDescription>
                </SheetHeader>

                <div class="px-6 py-4">
                    <slot :request-live-apply="requestLiveApply" />
                </div>

                <SheetFooter class="flex flex-row items-center justify-between gap-2">
                    <Button
                        v-if="activeCount > 0"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="emit('clear')"
                    >
                        Pulisci filtri
                    </Button>
                    <span v-else />

                    <div class="flex items-center gap-2">
                        <SheetClose as-child>
                            <Button type="button" variant="outline" size="sm">Annulla</Button>
                        </SheetClose>
                        <Button type="button" size="sm" @click="applyMobile">Applica</Button>
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    </div>
</template>
