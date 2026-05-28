<script setup lang="ts">
/**
 * Clients — Show page.
 *
 * Dettaglio cliente: anagrafica + sezione "Storico fatturato" (placeholder
 * vuoto in Fase 3 — popolata in Fase 4 con l'entità Fattura).
 *
 * Topbar actions: Modifica (apre dialog) + Archivia (confirm dialog).
 */
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { PhArchive, PhPencil } from '@phosphor-icons/vue';
import { ref } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ClientFormDialog from '@/pages/clients/ClientFormDialog.vue';
import FormSection from '@/components/forms/FormSection.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { index as clientsIndex } from '@/routes/clients';
import type { Client } from '@/types';

const props = defineProps<{
    client: Client;
}>();

setLayoutProps({
    pageTitle: props.client.name,
    pageCrumbs: [
        { label: 'Clienti', href: clientsIndex().url },
        { label: props.client.name },
    ],
    subbar: false,
});

const editOpen = ref(false);
const archiveOpen = ref(false);
const archiveForm = useForm({});

function confirmArchive(): void {
    archiveForm.delete(
        ClientController.destroy.url({ client: props.client.id }),
        {
            onSuccess: () => {
                router.visit(clientsIndex().url);
            },
        },
    );
}
</script>

<template>
    <Head :title="client.name" />

    <Teleport to="#page-topbar-actions" defer>
        <Button
            type="button"
            variant="outline"
            size="sm"
            @click="archiveOpen = true"
        >
            <PhArchive :size="14" />
            Archivia
        </Button>
        <Button type="button" size="sm" @click="editOpen = true">
            <PhPencil :size="14" />
            Modifica
        </Button>
    </Teleport>

    <div class="mx-auto w-full max-w-[820px] px-4 py-6 md:px-6">
        <FormSection first title="Anagrafica">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-3">
                <div class="md:col-span-3">
                    <dt class="text-xs text-muted-foreground">Denominazione</dt>
                    <dd class="mt-0.5 text-13 font-medium text-foreground">
                        {{ client.name }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">P.IVA</dt>
                    <dd class="mt-0.5 tabular text-13 text-foreground">
                        {{ client.vat_number ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Codice Fiscale</dt>
                    <dd class="mt-0.5 tabular text-13 text-foreground">
                        {{ client.tax_code ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Ritenuta 8%</dt>
                    <dd class="mt-0.5">
                        <Badge v-if="client.bank_withholding" variant="outline">
                            Sì
                        </Badge>
                        <span v-else class="text-13 text-muted-foreground">No</span>
                    </dd>
                </div>
                <div v-if="client.notes" class="md:col-span-3">
                    <dt class="text-xs text-muted-foreground">Note</dt>
                    <dd class="mt-0.5 text-13 leading-relaxed text-foreground whitespace-pre-line">
                        {{ client.notes }}
                    </dd>
                </div>
            </dl>
        </FormSection>

        <FormSection title="Storico fatturato">
            <p class="text-13 text-muted-foreground">
                Lo storico fatturato comparirà qui quando inizierai a registrare
                fatture verso questo cliente.
            </p>
        </FormSection>
    </div>

    <ClientFormDialog v-model:open="editOpen" :client="client" />

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare il cliente?"
        :description="`«${client.name}» verrà nascosto dall'elenco. Le fatture esistenti restano invariate.`"
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
