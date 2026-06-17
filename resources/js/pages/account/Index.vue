<script setup lang="ts">
/**
 * Account — pagina-menu mobile raggiunta dall'avatar nel topbar (<lg).
 *
 * Aggrega ciò che non sta nella footbar: impostazioni personali (profilo,
 * sicurezza, aspetto), cataloghi di sistema, archivio e logout. Solo link a
 * route esistenti — nessuna logica propria. Su desktop la stessa roba vive
 * nella sidebar, quindi questa pagina è di fatto una superficie mobile.
 */
import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import {
    PhArchive,
    PhCaretRight,
    PhFolders,
    PhListChecks,
    PhPaintBrush,
    PhShieldCheck,
    PhSignOut,
    PhTag,
    PhUserCircle,
} from '@phosphor-icons/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { index as archivioIndex } from '@/routes/archivio';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';
import { index as expenseItemsIndex } from '@/routes/settings/expense-items';
import { index as familiesIndex } from '@/routes/settings/families';
import { index as recurringDeadlinesIndex } from '@/routes/settings/recurring-deadlines';

type AccountLink = { label: string; icon: Component; href: string };
type AccountSection = { label: string; items: AccountLink[] };

setLayoutProps({ pageTitle: 'Account', pageCrumbs: [{ label: 'Account' }] });

const page = usePage();
const user = computed(() => page.props.auth.user);

const sections: AccountSection[] = [
    {
        label: 'Account',
        items: [
            { label: 'Profilo', icon: PhUserCircle, href: profileEdit().url },
            { label: 'Sicurezza', icon: PhShieldCheck, href: securityEdit().url },
            { label: 'Aspetto', icon: PhPaintBrush, href: appearanceEdit().url },
        ],
    },
    {
        label: 'Sistema',
        items: [
            { label: 'Voci di spesa', icon: PhTag, href: expenseItemsIndex().url },
            { label: 'Famiglie di spesa', icon: PhFolders, href: familiesIndex().url },
            {
                label: 'Scadenze tipo',
                icon: PhListChecks,
                href: recurringDeadlinesIndex().url,
            },
            { label: 'Archivio', icon: PhArchive, href: archivioIndex().url },
        ],
    },
];

const handleLogout = (): void => {
    router.flushAll();
};
</script>

<template>
    <div class="mx-auto w-full max-w-md">
        <Head title="Account" />

        <!-- Intestazione utente -->
        <div class="mb-6 flex items-center gap-3">
            <UserInfo :user="user" :show-email="true" />
        </div>

        <div class="flex flex-col gap-6">
            <section v-for="section in sections" :key="section.label">
                <h2 class="kicker mb-2 px-1">{{ section.label }}</h2>
                <div
                    class="overflow-hidden rounded-lg border border-border bg-card"
                >
                    <Link
                        v-for="item in section.items"
                        :key="item.label"
                        :href="item.href"
                        class="flex items-center gap-3 border-b border-border-soft px-4 py-3 text-sm text-foreground transition-colors last:border-b-0 hover:bg-accent"
                    >
                        <component :is="item.icon" :size="18" class="shrink-0 text-muted-foreground" />
                        <span class="flex-1 truncate">{{ item.label }}</span>
                        <PhCaretRight :size="14" class="shrink-0 text-muted-foreground/50" />
                    </Link>
                </div>
            </section>

            <!-- Logout -->
            <div class="overflow-hidden rounded-lg border border-border bg-card">
                <Link
                    :href="logout()"
                    as="button"
                    data-test="logout-button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-sm text-destructive transition-colors hover:bg-destructive/10"
                    @click="handleLogout"
                >
                    <PhSignOut :size="18" class="shrink-0" />
                    <span class="flex-1 text-left">Esci</span>
                </Link>
            </div>
        </div>
    </div>
</template>
