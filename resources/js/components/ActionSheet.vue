<script setup lang="ts">
/**
 * ActionSheet — sheet di dettaglio/azione riusabile per tutta la piattaforma.
 *
 * Drawer (vaul) responsive: bottom su mobile (con grabber per il drag), pannello
 * da destra su desktop. Header stile iOS: X (chiudi) a sinistra, titolo
 * centrato, azione primaria a destra (slot #primary, tipicamente un check) —
 * mostrata SOLO su mobile. Su desktop il primario va messo dal consumer nel
 * footer (slot #footer), come bottone etichettato.
 * I CONTENUTI vanno nello slot default; le azioni secondarie nello slot #footer.
 *
 * @example
 *   <ActionSheet v-model:open="open" title="Saldo Inarcassa">
 *     <template #primary><Button type="submit" form="f" size="icon"><PhCheck/></Button></template>
 *     …corpo…
 *     <template #footer><Button variant="ghost">Azione secondaria</Button></template>
 *   </ActionSheet>
 */
import { PhX } from '@phosphor-icons/vue';
import { useMediaQuery } from '@vueuse/core';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerTitle,
} from '@/components/ui/drawer';

defineProps<{ title: string; description?: string }>();

const open = defineModel<boolean>('open', { default: false });

const isMobile = useMediaQuery('(max-width: 767px)');
</script>

<template>
    <Drawer
        v-model:open="open"
        :direction="isMobile ? 'bottom' : 'right'"
        :should-scale-background="false"
    >
        <DrawerContent
            class="data-[vaul-drawer-direction=right]:w-full data-[vaul-drawer-direction=right]:sm:max-w-md"
        >
            <!-- Header iOS: X a sx, titolo centrato, primario a dx. -->
            <header
                class="grid h-12 shrink-0 grid-cols-[1fr_auto_1fr] items-center gap-2 border-b border-border px-2"
            >
                <div class="justify-self-start">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Chiudi"
                        @click="open = false"
                    >
                        <PhX :size="18" />
                    </Button>
                </div>
                <DrawerTitle
                    class="truncate px-2 text-center text-13 font-medium text-foreground"
                >
                    {{ title }}
                </DrawerTitle>
                <!-- Primario nell'header SOLO su mobile (pattern iOS). Su
                     desktop (pannello destro) il primario va nel footer, gestito
                     dal consumer che conosce il proprio layout. -->
                <div class="flex justify-self-end">
                    <slot v-if="isMobile" name="primary" />
                </div>
            </header>
            <DrawerDescription class="sr-only">{{
                description ?? title
            }}</DrawerDescription>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <slot />
            </div>

            <footer
                v-if="$slots.footer"
                class="shrink-0 border-t border-border px-4 py-3"
            >
                <slot name="footer" />
            </footer>
        </DrawerContent>
    </Drawer>
</template>
