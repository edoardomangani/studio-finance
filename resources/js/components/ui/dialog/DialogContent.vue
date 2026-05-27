<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { cva, type VariantProps } from "class-variance-authority"
import { reactiveOmit } from "@vueuse/core"
import { PhX } from "@phosphor-icons/vue"
import {
  DialogClose,
  DialogContent,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { cn } from "@/lib/utils"
import DialogOverlay from "./DialogOverlay.vue"

/**
 * Dialog sizes — 4 varianti documentate nel DS:
 *  - mini       ~460px (conferme, alert, scelte rapide)
 *  - default    ~580px (form rapidi: edit cliente, nuova categoria)
 *  - wide       ~760px (form complessi: nuovo progetto, edit denso)
 *  - fullscreen 100vw  (form ultra-densi multi-step, viewer)
 */
const dialogContentVariants = cva(
  'bg-card data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid w-full translate-x-[-50%] translate-y-[-50%] gap-0 overflow-hidden border border-border/80 p-0 duration-200',
  {
    variants: {
      size: {
        mini:       'max-w-[calc(100%-2rem)] sm:max-w-[460px] rounded-lg',
        default:    'max-w-[calc(100%-2rem)] sm:max-w-[580px] rounded-lg',
        wide:       'max-w-[calc(100%-2rem)] sm:max-w-[760px] rounded-lg',
        fullscreen: 'w-[calc(100vw-3rem)] h-[calc(100vh-3rem)] max-w-none rounded-lg',
      },
    },
    defaultVariants: { size: 'default' },
  }
)

export type DialogContentSize = VariantProps<typeof dialogContentVariants>['size']

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(
  defineProps<DialogContentProps & {
    class?: HTMLAttributes["class"]
    showCloseButton?: boolean
    size?: DialogContentSize
  }>(),
  {
    showCloseButton: true,
    size: 'default',
  },
)
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = reactiveOmit(props, "class", "size")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay />
    <DialogContent
      data-slot="dialog-content"
      v-bind="{ ...$attrs, ...forwarded }"
      :class="cn(dialogContentVariants({ size }), props.class)"
    >
      <slot />

      <DialogClose
        v-if="showCloseButton"
        data-slot="dialog-close"
        class="text-muted-foreground hover:text-foreground absolute top-5 right-5 rounded-md p-1.25 opacity-80 transition-colors hover:opacity-100 outline-none disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-3.5 hover:bg-foreground/3 cursor-pointer"
      >
        <PhX />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
