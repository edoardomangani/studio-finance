<script setup lang="ts">
/**
 * Wizard apertura anno — Step 3 (Scadenze).
 *
 * Anteprima delle scadenze che verranno generate, con data modificabile
 * inline. In testa, quando l'anno N+1 va pre-aperto per coprire una scadenza
 * cross-year (Commercialista), un alert informativo richiede la conferma
 * esplicita prima di poter aprire (RB8/RB10).
 *
 * Muta direttamente le righe reattive passate dall'orchestratore.
 */
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { YearPlanDeadline } from '@/types';

defineProps<{
    crossYearDeadlines: string[];
    nextYear: number;
    needsConfirm: boolean;
}>();

// Two-way: editiamo le date via v-model:deadlines (no mutazione diretta di prop).
const deadlines = defineModel<YearPlanDeadline[]>('deadlines', { required: true });
const crossYearConfirmed = defineModel<boolean>('crossYearConfirmed', { required: true });
</script>

<template>
    <section class="flex flex-col gap-3">
        <p class="text-13 text-muted-foreground">
            Date calcolate dai template, modificabili per quest'anno.
        </p>

        <!-- Alert cross-year: full-border petrol soft, niente side-stripe. -->
        <div
            v-if="needsConfirm"
            role="status"
            class="rounded-lg border border-accent-line bg-accent px-4 py-3 text-13 text-accent-foreground"
        >
            <p class="font-medium">L'anno {{ nextYear }} verrà creato come pre-aperto</p>
            <p class="mt-1 text-accent-foreground/90">
                Queste scadenze pagano una spesa dell'anno {{ nextYear }}, che non esiste ancora:
                {{ crossYearDeadlines.join(', ') }}. Aprendo, il sistema crea l'anno {{ nextYear }}
                con la sola spesa necessaria; potrai aprirlo formalmente in seguito.
            </p>
            <label class="mt-3 flex items-center gap-2 font-medium text-foreground">
                <Checkbox v-model="crossYearConfirmed" />
                Ho capito, crea l'anno {{ nextYear }} pre-aperto
            </label>
        </div>

        <Table boxed>
            <TableHeader>
                <TableRow>
                    <TableHead>Scadenza</TableHead>
                    <TableHead class="w-[120px]">Tipo</TableHead>
                    <TableHead class="w-[170px]">Data prevista</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableEmpty v-if="deadlines.length === 0" :colspan="3">
                    Nessuna scadenza da generare per quest'anno.
                </TableEmpty>
                <TableRow
                    v-for="(deadline, index) in deadlines"
                    v-else
                    :key="deadline.recurring_deadline_id ?? `row-${index}`"
                >
                    <TableCell class="font-medium text-foreground">
                        <span class="block max-w-[320px] truncate" :title="deadline.name">{{ deadline.name }}</span>
                    </TableCell>
                    <TableCell>
                        <Badge :variant="deadline.kind === 'payment' ? 'secondary' : 'outline'">
                            {{ deadline.kind === 'payment' ? 'Pagamento' : 'Adempimento' }}
                        </Badge>
                    </TableCell>
                    <TableCell>
                        <Input
                            v-model="deadline.due_at"
                            type="date"
                            class="tabular"
                            :aria-label="`Data ${deadline.name}`"
                        />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </section>
</template>
