<script setup lang="ts">
/**
 * FormSection — sezione di form titolata.
 *
 * Vale a livello di sezione di pagina: title (kicker) + padding verticale.
 * Lo stack interno dei field è delegato a {@link FieldGroup}, così il gap
 * tra Field è uniforme con i modali e tutto il resto del DS.
 *
 *   <FormSection title="Anagrafica">
 *     <FormField label="Ragione sociale" required>
 *       <Input v-model="form.name" />
 *     </FormField>
 *   </FormSection>
 */
import { FieldGroup } from '@/components/ui/field';

withDefaults(
    defineProps<{
        title?: string;
        first?: boolean;
        last?: boolean;
    }>(),
    { first: false, last: false },
);
</script>

<template>
    <section
        class="space-y-4 py-6"
        :class="{
            'pt-0': first,
            'pb-0': last,
        }"
    >
        <h2 v-if="title" class="section-title">{{ title }}</h2>
        <FieldGroup>
            <slot />
        </FieldGroup>
        <div
            v-if="$slots.actions"
            class="flex items-center justify-end gap-2 pt-2"
        >
            <slot name="actions" />
        </div>
    </section>
</template>
