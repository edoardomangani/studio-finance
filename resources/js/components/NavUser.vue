<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { PhCaretUpDown } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isMobile, state } = useSidebar();
const { getInitials } = useInitials();

const showAvatar = computed(() => user.value.avatar && user.value.avatar !== '');
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        class="h-auto gap-2 px-2 py-1.5 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground group-data-[collapsible=icon]:p-0! group-data-[collapsible=icon]:justify-center"
                        data-test="sidebar-menu-button"
                    >
                        <Avatar class="size-6 shrink-0 overflow-hidden rounded group-data-[collapsible=icon]:size-5">
                            <AvatarImage
                                v-if="showAvatar"
                                :src="user.avatar!"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded bg-secondary text-2xs font-medium text-foreground"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="grid min-w-0 flex-1 text-left leading-tight group-data-[collapsible=icon]:hidden">
                            <span class="truncate text-xs font-medium text-foreground">
                                {{ user.name }}
                            </span>
                            <span class="truncate text-2xs text-muted-foreground">
                                {{ user.email }}
                            </span>
                        </div>
                        <PhCaretUpDown
                            :size="12"
                            weight="bold"
                            class="ml-auto shrink-0 text-muted-foreground group-data-[collapsible=icon]:hidden"
                        />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-md"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'top'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
