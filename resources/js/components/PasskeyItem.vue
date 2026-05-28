<script setup lang="ts">
import { PhKey, PhTrash } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Passkey } from '@/types/auth';

const props = defineProps<{
    passkey: Passkey;
}>();

const emit = defineEmits<{
    remove: [id: number, onError: () => void];
}>();

const isDeleting = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    emit('remove', props.passkey.id, () => {
        isDeleting.value = false;
    });
};
</script>

<template>
    <div
        class="border-border-soft flex items-center justify-between gap-3 border-b px-3 py-2 last:border-b-0"
    >
        <div class="flex min-w-0 items-center gap-3">
            <PhKey class="size-4 shrink-0 text-muted-foreground" />
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <p class="truncate text-13 font-medium text-foreground">
                        {{ passkey.name }}
                    </p>
                    <span
                        v-if="passkey.authenticator"
                        class="code-pill shrink-0"
                    >
                        {{ passkey.authenticator }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Aggiunta {{ passkey.created_at_diff }}<template
                        v-if="passkey.last_used_at_diff"
                    >
                        · ultimo uso {{ passkey.last_used_at_diff }}</template
                    >
                </p>
            </div>
        </div>

        <Dialog>
            <DialogTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-destructive"
                    aria-label="Rimuovi passkey"
                >
                    <PhTrash />
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>Rimuovere la passkey?</DialogTitle>
                <DialogDescription>
                    La passkey "{{ passkey.name }}" non sarà più utilizzabile
                    per accedere all'account.
                </DialogDescription>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="ghost">Annulla</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        {{ isDeleting ? 'Sto rimuovendo…' : 'Rimuovi passkey' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
