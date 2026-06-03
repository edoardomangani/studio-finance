<script setup lang="ts">
/**
 * ResponsiveDialog — guscio unico per create/edit e azioni su riga.
 *
 * Una sola implementazione, due facce desktop scelte da `mode`; su mobile
 * (<768px) entrambe collassano in un bottom sheet stile iOS.
 *
 *   mode="dialog" (default) → desktop: dialog centrato · mobile: bottom sheet
 *   mode="sheet"            → desktop: pannello destro · mobile: bottom sheet
 *
 * Il rasoio del progetto: i form di anagrafica/configurazione usano `dialog`;
 * l'azione su una riga esistente (registra pagamento, cambia stato) usa
 * `sheet`. I documenti con calcolo live o creazione annidata (fattura, anno)
 * NON usano questo guscio: vanno a pagina.
 *
 * Azione primaria — il guscio la rende dove va, secondo la piattaforma:
 *  - Drawer (mobile / desktop sheet): check icona nell'header in alto a dx
 *    (pattern iOS), la X a sx chiude. Niente bottone in fondo.
 *  - Dialog (desktop dialog): bottone con label nel footer, accanto ad Annulla.
 * Passa `submit-form` (id del <form>) + `submit-label` + `:submitting` e il
 * guscio costruisce il bottone giusto in entrambi i contesti. Per casi fuori
 * standard usa lo slot #primary (override completo) e #footer (azioni
 * secondarie, es. ripristini reversibili).
 *
 * @example form di config
 *   <ResponsiveDialog
 *     v-model:open="open"
 *     title="Nuovo cliente"
 *     :description="…"
 *     submit-form="client-form"
 *     :submit-label="isEditing ? 'Salva modifiche' : 'Aggiungi cliente'"
 *     :submitting="form.processing"
 *   >
 *     <form id="client-form" @submit.prevent="submit">…</form>
 *   </ResponsiveDialog>
 */
import { PhCheck, PhX } from '@phosphor-icons/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogStandardFooter,
    DialogStandardHeader,
} from '@/components/ui/dialog';
import type { DialogContentSize } from '@/components/ui/dialog';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Spinner } from '@/components/ui/spinner';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        /** desktop: 'dialog' = centrato, 'sheet' = pannello destro. Default 'dialog'. */
        mode?: 'dialog' | 'sheet';
        /** Larghezza del dialog centrato (mode='dialog', desktop). */
        size?: DialogContentSize;
        /** Label del bottone Annulla nel footer del dialog desktop. */
        cancelLabel?: string;
        /** id del <form> che l'azione primaria invia. Abilita il bottone di submit. */
        submitForm?: string;
        /** Label dell'azione primaria nel footer desktop (sul mobile è un check). */
        submitLabel?: string;
        /** Disabilita e mostra lo spinner sull'azione primaria durante l'invio. */
        submitting?: boolean;
    }>(),
    {
        mode: 'dialog',
        size: 'default',
        cancelLabel: 'Annulla',
        submitting: false,
    },
);

const open = defineModel<boolean>('open', { default: false });

const isMobile = useMediaQuery('(max-width: 767px)');

// Drawer quando siamo su mobile (sempre bottom sheet) o quando in modalità
// sheet su desktop (pannello destro). Altrimenti dialog centrato.
const asDrawer = computed(() => isMobile.value || props.mode === 'sheet');
</script>

<template>
    <Drawer
        v-if="asDrawer"
        v-model:open="open"
        :direction="isMobile ? 'bottom' : 'right'"
        :should-scale-background="false"
    >
        <DrawerContent
            class="data-[vaul-drawer-direction=bottom]:max-h-[90vh] data-[vaul-drawer-direction=right]:w-full data-[vaul-drawer-direction=right]:sm:max-w-md"
        >
            <!-- Header iOS: X a sx, titolo centrato, azione primaria a dx. -->
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
                <div class="flex justify-self-end">
                    <slot name="primary">
                        <Button
                            v-if="submitForm"
                            type="submit"
                            :form="submitForm"
                            variant="default"
                            size="icon"
                            :disabled="submitting"
                            :aria-busy="submitting"
                            :aria-label="submitLabel ?? 'Conferma'"
                        >
                            <Spinner v-if="submitting" />
                            <PhCheck v-else :size="20" weight="bold" />
                        </Button>
                    </slot>
                </div>
            </header>
            <DrawerDescription class="sr-only">
                {{ description ?? title }}
            </DrawerDescription>

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

    <Dialog v-else v-model:open="open">
        <DialogContent :size="size">
            <DialogStandardHeader :title="title" :description="description" />
            <DialogBody>
                <slot />
            </DialogBody>
            <DialogStandardFooter
                v-if="submitForm || $slots.primary || $slots.footer"
                :cancel-label="cancelLabel"
            >
                <slot name="footer" />
                <slot name="primary">
                    <Button
                        v-if="submitForm"
                        type="submit"
                        :form="submitForm"
                        :disabled="submitting"
                        :aria-busy="submitting"
                    >
                        <Spinner v-if="submitting" />
                        {{ submitLabel }}
                    </Button>
                </slot>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>
</template>
