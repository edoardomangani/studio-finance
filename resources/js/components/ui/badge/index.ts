import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  // Base — geometria + stati focus/invalid (rounded-sm, px-2 py-0.5, text-xs font-medium, svg size-3).
  "inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-sm border px-2 py-0.5 text-xs font-medium transition-colors [&>svg]:pointer-events-none [&>svg]:size-3 focus-visible:border-input-focus aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        // Tonale sage — coerente con Button.secondary (Tracciato). Per stati neutri
        // (Bozza, Chiuso) usa la utility .pill-status-soft--neutral, non questa.
        secondary:
          "border-transparent bg-accent-vivid/10 text-accent-strong [a&]:hover:bg-accent-vivid/15",
        // Tonale ambra per stati che richiedono attenzione (scadenze aperte).
        warning:
          "border-transparent bg-warning-foreground text-warning",
        white:
          "bg-card text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        destructive:
          "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 dark:bg-destructive/60",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
