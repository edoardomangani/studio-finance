<script setup lang="ts">
/**
 * ConfirmDialog — Mini dialog pre-cucinato per conferme (delete, archive,
 * azioni distruttive).
 *
 * Compone DialogContent size="mini" + DialogStandardHeader + DialogBody
 * (con il messaggio) + DialogStandardFooter (Annulla ghost + Conferma
 * primary o destructive).
 *
 *   <ConfirmDialog
 *     v-model:open="deleteOpen"
 *     title="Eliminare il cliente?"
 *     description="Tutte le associazioni a progetti verranno mantenute, ma il cliente non sarà più visibile in elenco."
 *     confirm-label="Elimina"
 *     destructive
 *     @confirm="performDelete"
 *   />
 */
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import Dialog from './Dialog.vue'
import DialogBody from './DialogBody.vue'
import DialogContent from './DialogContent.vue'
import DialogStandardFooter from './DialogStandardFooter.vue'
import DialogStandardHeader from './DialogStandardHeader.vue'

withDefaults(
    defineProps<{
        open: boolean
        /** Titolo conferma (es. "Eliminare il cliente?"). */
        title: string
        /** Messaggio descrittivo (opzionale, ma consigliato per le distruttive). */
        description?: string
        /** Label del bottone di conferma. Default "Conferma". */
        confirmLabel?: string
        /** Label del bottone di annulla. Default "Annulla". */
        cancelLabel?: string
        /** Stile destructive (rosso) sul bottone conferma. */
        destructive?: boolean
        /** Conferma in corso: disabilita il bottone e mostra lo spinner. */
        loading?: boolean
    }>(),
    {
        confirmLabel: 'Conferma',
        cancelLabel: 'Annulla',
        destructive: false,
        loading: false,
    },
)

defineEmits<{
    (e: 'update:open', open: boolean): void
    (e: 'confirm'): void
}>()
</script>

<template>
    <Dialog :open="open" @update:open="(v) => $emit('update:open', v)">
        <DialogContent size="mini">
            <DialogStandardHeader :title="title" :closable="false" />
            <DialogBody v-if="description">
                <p class="text-13 leading-relaxed text-muted-foreground">
                    {{ description }}
                </p>
            </DialogBody>
            <DialogStandardFooter :cancel-label="cancelLabel">
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="loading"
                    @click="$emit('confirm')"
                >
                    <Spinner v-if="loading" />
                    {{ confirmLabel }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>
</template>
