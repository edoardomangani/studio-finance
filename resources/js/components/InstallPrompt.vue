<script setup lang="ts">
/**
 * InstallPrompt — banner installazione PWA, solo mobile (<lg), dismissibile.
 *
 * Mostra il prompt nativo su Chrome/Android (canInstall) o l'hint manuale su
 * iOS (showIosHint). Sta sopra la footbar come riga sottile; sparisce dopo il
 * dismiss (persistito) o a installazione avvenuta.
 */
import { PhDownloadSimple, PhExport, PhX } from '@phosphor-icons/vue';
import { Button } from '@/components/ui/button';
import { useInstallPrompt } from '@/composables/useInstallPrompt';

const { canInstall, showIosHint, install, dismiss } = useInstallPrompt();
</script>

<template>
    <div
        v-if="canInstall || showIosHint"
        class="flex shrink-0 items-center gap-2.5 border-t border-sidebar-border bg-card px-4 py-2.5 lg:hidden"
    >
        <div
            class="flex size-7 shrink-0 items-center justify-center rounded-md bg-accent-vivid/10 text-accent-vivid"
        >
            <PhDownloadSimple :size="16" weight="bold" />
        </div>

        <!-- Chrome/Android: bottone installa -->
        <template v-if="canInstall">
            <span class="flex-1 text-13 text-foreground">
                Installa Studiofinance
            </span>
            <Button size="sm" @click="install">Installa</Button>
        </template>

        <!-- iOS: istruzione manuale -->
        <template v-else>
            <span class="flex-1 text-xs text-muted-foreground">
                Per installare: tocca
                <PhExport
                    :size="13"
                    class="inline -translate-y-px"
                    aria-hidden="true"
                />
                e poi «Aggiungi a Home»
            </span>
        </template>

        <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Chiudi"
            class="shrink-0"
            @click="dismiss"
        >
            <PhX :size="14" />
        </Button>
    </div>
</template>
