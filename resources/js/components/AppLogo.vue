<script setup lang="ts"></script>

<template>
    <span class="flex items-center gap-2">
        <svg
            viewBox="0 0 24 24"
            class="size-5 shrink-0 text-foreground"
            fill="currentColor"
            aria-hidden="true"
        >
            <g transform="translate(12 12)">
                <path d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z" />
                <path
                    d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                    transform="rotate(60)"
                />
                <path
                    d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                    transform="rotate(120)"
                />
                <path
                    d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                    transform="rotate(180)"
                />
                <path
                    d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                    transform="rotate(240)"
                />
                <path
                    d="M 0 -10 A 5 5 0 0 1 0 -2 A 5 5 0 0 1 0 -10 Z"
                    transform="rotate(300)"
                />
            </g>
        </svg>

        <span
            class="flex items-baseline gap-1.5 group-data-[collapsible=icon]:hidden"
        >
            <span
                class="text-sm leading-none font-semibold tracking-tight text-foreground"
            >
                Studio<span class="text-accent-vivid">FIN</span>
            </span>
            <span
                class="tabular -translate-y-px font-mono text-2xs font-medium text-muted-foreground"
            >
                v0
            </span>
        </span>
    </span>
</template>
