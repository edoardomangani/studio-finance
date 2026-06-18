import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-13 transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-3.5 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-input-focus aria-invalid:border-input-invalid cursor-pointer",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground hover:bg-primary/90 font-medium",
        destructive:
          "bg-destructive text-white hover:bg-destructive/90 dark:bg-destructive/60 font-medium",
        // Outline — neutro al riposo. Hover e stato pressed (aria-pressed=true)
        // condividono lo stesso look sage tonale: il toggle "on" è la
        // continuazione del feedback hover, non una variant separata.
        outline:
          "border bg-card text-foreground hover:bg-accent-vivid/[0.06] hover:border-accent-vivid/30 hover:text-accent-strong aria-pressed:bg-accent-vivid/[0.06] aria-pressed:border-accent-vivid/30 aria-pressed:text-accent-strong dark:bg-input/30 dark:border-input dark:hover:bg-input/50 font-medium",
        // Secondary tonale sage — ispirato a Tracciato "Memoria di studio".
        // Per azioni contestuali soft (Riapri, Apri memoria, Resend). Ink resta primary.
        secondary:
          "bg-accent-vivid/10 text-accent-strong hover:bg-accent-vivid/15 font-medium",
        ghost:
          "text-muted-foreground hover:bg-accent hover:text-foreground dark:hover:bg-accent/50",
        link: "text-primary underline-offset-4 hover:underline font-medium",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5 text-xs",
        "lg": "h-10 rounded-md px-6 has-[>svg]:px-4",
        "icon": "size-6",
        "icon-sm": "size-6",
        "icon-lg": "size-8",
        // 36×36 — icon-button pari all'altezza di input/Button default (h-9).
        // È la dimensione standard per gli icon-button in topbar/subbar mobile.
        "icon-md": "size-9",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
