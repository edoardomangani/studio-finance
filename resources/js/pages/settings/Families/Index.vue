<script setup lang="ts">
/**
 * Impostazioni → Famiglie di spesa: i quattro tipi fissi. L'utente può solo
 * RINOMINARLE (il tipo è immutabile), per usare una nomenclatura sua (es.
 * "Inarcassa" al posto di "Previdenza"). Niente create/elimina. Il badge in
 * cima a ogni riga si aggiorna mentre scrivi.
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PhFloppyDisk } from '@phosphor-icons/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ExpenseFamilyController from '@/actions/App/Http/Controllers/Settings/ExpenseFamilyController';
import FamilyBadge from '@/components/FamilyBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ExpenseKind } from '@/types';

type Family = {
    id: number;
    kind: ExpenseKind;
    name: string;
    description: string;
};

const props = defineProps<{ families: Family[] }>();

setLayoutProps({
    pageTitle: 'Famiglie di spesa',
    pageCrumbs: [{ label: 'Impostazioni' }, { label: 'Famiglie di spesa' }],
    subbar: false,
});

// Nome editato per famiglia (chiave = id), così il badge segue mentre scrivi.
const names = ref<Record<number, string>>(
    Object.fromEntries(props.families.map((f) => [f.id, f.name])),
);
const saving = ref<number | null>(null);

function isDirty(family: Family): boolean {
    const next = names.value[family.id]?.trim() ?? '';

    return next !== '' && next !== family.name;
}

function save(family: Family): void {
    if (!isDirty(family)) {
        return;
    }

    saving.value = family.id;
    router.patch(
        ExpenseFamilyController.update.url({ expenseFamily: family.id }),
        { name: names.value[family.id].trim() },
        {
            preserveScroll: true,
            onError: (errors) =>
                toast.error(errors.name ?? 'Rinomina non riuscita. Riprova.'),
            onFinish: () => (saving.value = null),
        },
    );
}
</script>

<template>
    <Head title="Famiglie di spesa" />

    <div class="flex flex-col gap-3">
        <div
            class="divide-y divide-border-soft overflow-hidden rounded-lg border border-border bg-card"
        >
            <div
                v-for="family in families"
                :key="family.id"
                class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <FamilyBadge
                        :kind="family.kind"
                        :name="names[family.id] || family.name"
                    />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        {{ family.description }}
                    </p>
                </div>

                <form
                    class="flex shrink-0 items-center gap-2"
                    @submit.prevent="save(family)"
                >
                    <Input
                        v-model="names[family.id]"
                        class="w-52"
                        :aria-label="`Nome della famiglia ${family.name}`"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        variant="secondary"
                        :disabled="!isDirty(family) || saving === family.id"
                    >
                        <PhFloppyDisk :size="14" weight="bold" />
                        Salva
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
