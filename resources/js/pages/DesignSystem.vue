<script setup lang="ts">
/**
 * DesignSystem — manifesto eseguibile dei pattern UI.
 *
 * Strategia rebuild (Phase B): le sezioni vengono popolate una per fase.
 * Ogni Phase B aggiunge pattern e regole d'uso. Le sezioni "coming"
 * sono placeholder finché non arrivano le rispettive fasi.
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import {
    PhArrowRight,
    PhBell,
    PhBookOpen,
    PhBuildings,
    PhCalendarDots,
    PhCaretRight,
    PhCheck,
    PhCurrencyEur,
    PhDownload,
    PhEnvelope,
    PhFolder,
    PhReceipt,
    PhGearSix,
    PhHouse,
    PhInfo,
    PhMagnifyingGlass,
    PhPencilSimple,
    PhPlus,
    PhRows,
    PhSpinnerGap,
    PhSquaresFour,
    PhTextB,
    PhTextItalic,
    PhTextUnderline,
    PhTrash,
    PhUser,
    PhX,
} from '@phosphor-icons/vue';
import { computed, ref, watchEffect } from 'vue';
import { toast } from 'vue-sonner';
import FormField from '@/components/forms/FormField.vue';
import FormSection from '@/components/forms/FormSection.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Checkbox } from '@/components/ui/checkbox';
import {
    ChoiceCardCheck,
    ChoiceCardRadio,
    ChoiceCardSwitch,
    ChoicePillCheck,
    ChoicePillRadio,
} from '@/components/ui/choice';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import {
    ConfirmDialog,
    Dialog,
    DialogBody,
    DialogContent,
    DialogStandardFooter,
    DialogStandardHeader,
    DialogTrigger,
    WizardStepper,
} from '@/components/ui/dialog';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSet,
} from '@/components/ui/field';
import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from '@/components/ui/hover-card';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Kbd } from '@/components/ui/kbd';
import { Label } from '@/components/ui/label';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Progress } from '@/components/ui/progress';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { RangeCalendar } from '@/components/ui/range-calendar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Slider } from '@/components/ui/slider';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTablePagination,
    DataTableRow,
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';
import { Textarea } from '@/components/ui/textarea';
import { Toggle } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

setLayoutProps({
    pageTitle: 'Design system',
    subbar: false,
    bare: true,
});

type Section = {
    id: string;
    label: string;
    phase:
        | 'B.1'
        | 'B.2'
        | 'B.3'
        | 'B.4'
        | 'B.5'
        | 'B.6'
        | 'B.7'
        | 'B.8'
        | 'B.9'
        | 'B.10';
    status: 'ready' | 'pending';
    /** Sotto-temi che la sezione coprirà (mostrati nel placeholder pending). */
    topics?: string[];
};

const sections: Section[] = [
    {
        id: 'foundations',
        label: '01 Foundations',
        phase: 'B.1',
        status: 'ready',
    },
    { id: 'buttons', label: '02 Buttons', phase: 'B.2', status: 'ready' },
    { id: 'badges', label: '03 Badges', phase: 'B.2', status: 'ready' },
    { id: 'forms', label: '04 Form fields', phase: 'B.2', status: 'ready' },
    { id: 'tabs', label: '05 Tabs', phase: 'B.3', status: 'ready' },
    { id: 'dialog', label: '06 Dialog', phase: 'B.3', status: 'ready' },
    { id: 'tables', label: '07 Tables', phase: 'B.4', status: 'ready' },
    { id: 'topbar', label: '08 Topbar', phase: 'B.5', status: 'ready' },
    { id: 'sidebar', label: '09 Sidebar nav', phase: 'B.6', status: 'ready' },
    {
        id: 'side-panels',
        label: '10 Side panels',
        phase: 'B.7',
        status: 'ready',
    },
    {
        id: 'form-layout',
        label: '11 Form layout',
        phase: 'B.8',
        status: 'ready',
    },
    { id: 'modals', label: '12 Modal patterns', phase: 'B.9', status: 'ready' },
    {
        id: 'pagination',
        label: '13 Pagination',
        phase: 'B.10',
        status: 'ready',
    },
    { id: 'progress', label: '14 Progress', phase: 'B.10', status: 'ready' },
    { id: 'empty', label: '15 Empty state', phase: 'B.10', status: 'ready' },
    { id: 'toggle', label: '16 Toggle group', phase: 'B.10', status: 'ready' },
    {
        id: 'aspect-ratio',
        label: '17 Aspect ratio',
        phase: 'B.10',
        status: 'ready',
    },
    {
        id: 'hover-card',
        label: '18 Hover card',
        phase: 'B.10',
        status: 'ready',
    },
    { id: 'loading', label: '19 Loading', phase: 'B.10', status: 'ready' },
    {
        id: 'tooltip',
        label: '20 Tooltip+Popover',
        phase: 'B.10',
        status: 'ready',
    },
    { id: 'toast', label: '21 Toast (Sonner)', phase: 'B.2', status: 'ready' },
];

const activeId = ref<string>('foundations');

/* ═══════════════════════════════════════════════════════════════
   1.1 PALETTE BASE — 17 colori cromatici, organizzati per famiglia.
   Sono i colori "veri" del DS: tutti gli altri token semantici
   (livello 2) puntano qui via var(--c-*).
   ═══════════════════════════════════════════════════════════════ */
type Swatch = { var: string; value: string; note?: string };
type SwatchGroup = { title: string; subtitle?: string; swatches: Swatch[] };

const palette: SwatchGroup[] = [
    {
        title: 'Greys',
        subtitle: 'Scala cool zinc — bg / surface / line / ink',
        swatches: [
            {
                var: '--c-bg',
                value: '#FAFAFA',
                note: 'Canvas pagina (zinc-50).',
            },
            {
                var: '--c-bg-2',
                value: '#F4F4F5',
                note: 'Muted bg generico (zinc-100).',
            },
            {
                var: '--c-bg-3',
                value: '#F4F4F5',
                note: 'Sidebar nav bg (zinc-100).',
            },
            {
                var: '--c-surface',
                value: '#FFFFFF',
                note: 'Card / popover — surface elevata.',
            },
            {
                var: '--c-line',
                value: '#E4E4E7',
                note: 'Border standard (zinc-200).',
            },
            {
                var: '--c-line-2',
                value: '#EFEFF1',
                note: 'Border-soft / hover bg generico.',
            },
            {
                var: '--c-ink',
                value: '#18181B',
                note: 'Testo primary (zinc-900).',
            },
            {
                var: '--c-ink-2',
                value: '#71717A',
                note: 'Testo secondary (zinc-500).',
            },
            {
                var: '--c-ink-3',
                value: '#A1A1AA',
                note: 'Placeholder (zinc-400).',
            },
        ],
    },
    {
        title: 'Petrol',
        subtitle: 'Accent · decoro testuale (vivid) + UI funzionale (strong) + soft 10%',
        swatches: [
            {
                var: '--c-sage',
                value: 'oklch(52% 0.105 210)',
                note: 'accent-vivid · FIN logo, link inline, emphasis testuali.',
            },
            {
                var: '--c-sage-strong',
                value: 'oklch(32% 0.06 210)',
                note: 'accent-strong · focus ring, item sidebar attivo, button accent.',
            },
            {
                var: '--c-sage-bg',
                value: 'oklch(32% 0.06 210 / 10%)',
                note: 'accent (soft) · hover row, selected state, chip info.',
            },
        ],
    },
    {
        title: 'Red',
        subtitle:
            'Destructive — errori / delete / azioni distruttive (cool alert)',
        swatches: [
            { var: '--c-red', value: '#DC2626', note: 'Default red.' },
            {
                var: '--c-red-strong',
                value: '#B91C1C',
                note: 'Hover / pressed.',
            },
            {
                var: '--c-red-bg',
                value: '#FEF2F2',
                note: 'Bg morbido (alert errore).',
            },
        ],
    },
    {
        title: 'Yellow',
        subtitle: 'Highlight — evidenziato / search match',
        swatches: [
            {
                var: '--c-yellow',
                value: 'oklch(95% 0.06 95)',
                note: 'Giallo evidenziatore.',
            },
            {
                var: '--c-yellow-strong',
                value: 'oklch(90% 0.10 90)',
                note: 'Variante più carica.',
            },
        ],
    },
    {
        title: 'Ocre',
        subtitle: 'Warning — scadenza in arrivo, attenzione non bloccante',
        swatches: [
            {
                var: '--c-ocre',
                value: 'oklch(68% 0.115 75)',
                note: 'Default warning (chip "in arrivo", "scadenza imminente").',
            },
            {
                var: '--c-ocre-strong',
                value: 'oklch(56% 0.12 75)',
                note: 'Hover / pressed.',
            },
            {
                var: '--c-ocre-bg',
                value: 'oklch(68% 0.115 75 / 14%)',
                note: 'Bg morbido (alert warning).',
            },
        ],
    },
];

/* ═══════════════════════════════════════════════════════════════
   1.2 TOKEN SEMANTICI — mappatura ai colori della palette base.
   Questi sono i nomi shadcn-vue da usare nei componenti
   (bg-background, text-foreground, ecc.).
   ═══════════════════════════════════════════════════════════════ */
type SemanticToken = { token: string; mapsTo: string; use: string };
type SemanticGroup = { title: string; tokens: SemanticToken[] };

const semantics: SemanticGroup[] = [
    {
        title: 'Surfaces',
        tokens: [
            {
                token: '--background',
                mapsTo: '--c-bg',
                use: 'Canvas pagina (body bg).',
            },
            {
                token: '--foreground',
                mapsTo: '--c-ink',
                use: 'Testo principale.',
            },
            {
                token: '--card',
                mapsTo: '--c-surface',
                use: 'Bg di card / table elevate.',
            },
            {
                token: '--card-foreground',
                mapsTo: '--c-ink',
                use: 'Testo su card.',
            },
            {
                token: '--popover',
                mapsTo: '--c-surface',
                use: 'Bg dropdown / popover / tooltip.',
            },
            {
                token: '--popover-foreground',
                mapsTo: '--c-ink',
                use: 'Testo su popover.',
            },
        ],
    },
    {
        title: 'Subtle backgrounds',
        tokens: [
            {
                token: '--muted',
                mapsTo: '--c-bg-2',
                use: 'Bg sottile per testo secondario, code inline.',
            },
            {
                token: '--muted-foreground',
                mapsTo: '--c-ink-2',
                use: 'Testo secondario.',
            },
            {
                token: '--accent',
                mapsTo: '--c-line-2',
                use: 'Hover state menu / dropdown item.',
            },
            {
                token: '--accent-foreground',
                mapsTo: '--c-ink',
                use: 'Testo su accent.',
            },
        ],
    },
    {
        title: 'Brand primary',
        tokens: [
            {
                token: '--primary',
                mapsTo: '--c-ink',
                use: 'Bottoni primari (nero pieno), link attivi.',
            },
            {
                token: '--primary-foreground',
                mapsTo: '--c-bg',
                use: 'Testo su bg primary.',
            },
        ],
    },
    {
        title: 'Border & focus',
        tokens: [
            { token: '--border', mapsTo: '--c-line', use: 'Bordi standard.' },
            {
                token: '--border-soft',
                mapsTo: '--c-line-2',
                use: 'Bordi sottili / divider interni.',
            },
            { token: '--input', mapsTo: '--c-line', use: 'Border input form.' },
            { token: '--ring', mapsTo: '--c-ink', use: 'Focus ring.' },
        ],
    },
    {
        title: 'Sidebar nav',
        tokens: [
            {
                token: '--sidebar-background',
                mapsTo: '--c-bg-3',
                use: 'Sidebar nav bg (più calda di muted).',
            },
            {
                token: '--sidebar-foreground',
                mapsTo: '--c-ink',
                use: 'Testo voci nav.',
            },
            {
                token: '--sidebar-primary',
                mapsTo: '--c-ink',
                use: 'Voce primary (logo, current state enfasi).',
            },
            {
                token: '--sidebar-primary-foreground',
                mapsTo: '--c-bg',
                use: 'Testo su primary.',
            },
            {
                token: '--sidebar-accent',
                mapsTo: '--accent (soft 10%)',
                use: 'Fallback per consumer shadcn interni. Le voci nav usano direttamente bg-accent-vivid/10 (Studiofinance).',
            },
            {
                token: '--sidebar-accent-foreground',
                mapsTo: '--c-ink',
                use: 'Testo voce attiva (resta nero, non sage).',
            },
            {
                token: '--sidebar-border',
                mapsTo: '--c-line',
                use: 'Border-r di separazione.',
            },
            {
                token: '--sidebar-ring',
                mapsTo: '--c-ink',
                use: 'Focus ring nav.',
            },
        ],
    },
    {
        title: 'Accent (petrol)',
        tokens: [
            {
                token: '--accent-vivid',
                mapsTo: 'oklch(52% 0.105 210)',
                use: 'Decoro testuale che dichiara il colore (FIN logo, link inline). Mai sui bottoni.',
            },
            {
                token: '--accent-strong',
                mapsTo: 'oklch(32% 0.06 210)',
                use: 'UI funzionale: focus ring, item sidebar attivo, button accent.',
            },
            {
                token: '--accent (soft 10%)',
                mapsTo: 'oklch(32% 0.06 210 / 10%)',
                use: 'Fill morbido (hover row, selected state, chip info).',
            },
            {
                token: '--accent-line',
                mapsTo: 'oklch(32% 0.06 210 / 28%)',
                use: 'Border focus, underline link.',
            },
        ],
    },
    {
        title: 'Destructive',
        tokens: [
            {
                token: '--destructive',
                mapsTo: '--c-red',
                use: 'Bottoni destructive (delete).',
            },
            {
                token: '--destructive-strong',
                mapsTo: '--c-red-strong',
                use: 'Hover / pressed.',
            },
            {
                token: '--destructive-soft',
                mapsTo: '--c-red-bg',
                use: 'Bg morbido (alert errore).',
            },
            {
                token: '--destructive-foreground',
                mapsTo: '--c-bg',
                use: 'Testo su bg destructive pieno.',
            },
        ],
    },
    {
        title: 'Highlight',
        tokens: [
            {
                token: '--highlight',
                mapsTo: '--c-yellow',
                use: 'Bg evidenziato (text-mark, search match).',
            },
            {
                token: '--highlight-strong',
                mapsTo: '--c-yellow-strong',
                use: 'Variante più carica.',
            },
        ],
    },
    {
        title: 'Form fields & placeholder',
        tokens: [
            {
                token: '--field-foreground',
                mapsTo: '--c-ink',
                use: 'Testo dentro input/textarea.',
            },
            {
                token: '--placeholder-foreground',
                mapsTo: '--c-ink-3',
                use: 'Placeholder.',
            },
        ],
    },
];

/* Scala typography: combina Tailwind nativo + token custom (text-2xs, text-13). */
const typographyScale = [
    { class: 'text-4xl', px: '36 / 40', use: 'Display rare' },
    { class: 'text-3xl', px: '30 / 36', use: 'h1 pagina' },
    { class: 'text-2xl', px: '24 / 32', use: 'h2 sezione' },
    { class: 'text-xl', px: '20 / 28', use: 'h3' },
    { class: 'text-lg', px: '18 / 28', use: 'h4 / lead' },
    { class: 'text-base', px: '16 / 24', use: 'body principale' },
    { class: 'text-sm', px: '14 / 20', use: 'body, form input default' },
    {
        class: 'text-13',
        px: '13 / 18',
        use: 'body compatto, tabelle dense, breadcrumb',
        custom: true,
    },
    { class: 'text-xs', px: '12 / 16', use: 'label secondario, helper' },
    {
        class: 'text-2xs',
        px: '11 / 16',
        use: 'micro labels (kicker, kbd)',
        custom: true,
    },
];

const fontFeatureSettings = [
    { tag: 'ss01', desc: 'Stylistic set 1 (Switzer specifica).' },
    { tag: 'ss02', desc: 'Stylistic set 2.' },
    { tag: 'cv01', desc: 'Character variant 01.' },
    { tag: 'cv11', desc: 'Character variant 11.' },
    {
        tag: 'tnum',
        desc: 'Tabular nums (mono): cifre a larghezza fissa.',
        mono: true,
    },
    { tag: 'zero', desc: 'Slashed zero per distinguere da O.', mono: true },
];

/* Scale di spacing Tailwind più usate (4px base). */
const spacingScale = [
    { class: 'p-0.5', px: 2 },
    { class: 'p-1', px: 4 },
    { class: 'p-1.5', px: 6 },
    { class: 'p-2', px: 8 },
    { class: 'p-2.5', px: 10 },
    { class: 'p-3', px: 12 },
    { class: 'p-4', px: 16 },
    { class: 'p-5', px: 20 },
    { class: 'p-6', px: 24 },
    { class: 'p-8', px: 32 },
    { class: 'p-10', px: 40 },
    { class: 'p-12', px: 48 },
];

const radiusScale = [
    { class: 'rounded-none', px: 0 },
    { class: 'rounded-sm', px: 2, note: '--radius-sm' },
    { class: 'rounded', px: 4, note: '--radius-md' },
    { class: 'rounded-md', px: 6, note: '--radius (default)' },
    { class: 'rounded-lg', px: 8 },
    { class: 'rounded-xl', px: 12 },
    { class: 'rounded-full', px: 9999 },
];

/* ═══════════════════════════════════════════════════════════════
   02 BUTTONS — varianti shadcn (cva in components/ui/button/index.ts).
   ═══════════════════════════════════════════════════════════════ */
type ButtonVariantInfo = { variant: string; use: string };
const buttonVariantsInfo: ButtonVariantInfo[] = [
    {
        variant: 'default',
        use: 'Primary unico — ink nero. Azione principale (Salva, Crea, Conferma).',
    },
    {
        variant: 'destructive',
        use: 'Azione distruttiva (Elimina, Archivia, Revoca).',
    },
    {
        variant: 'outline',
        use: 'Annulla / azioni di contesto neutre. Hover accent tonale; supporta aria-pressed per stato "on" persistente (toggle button).',
    },
    {
        variant: 'secondary',
        use: 'Tonale accent filled — azione contestuale soft (Mostra dettagli, Riapri, Approva).',
    },
    { variant: 'ghost', use: 'Tertiary (icon button, item menu, hover-only).' },
    {
        variant: 'link',
        use: 'Azione testuale inline (raro fuori dai blocchi text).',
    },
];

const buttonSizes = [
    { size: 'sm', px: 'h-8', use: 'Toolbar densi, filtri, table actions' },
    { size: 'default', px: 'h-9', use: 'Default forms e azioni principali' },
    { size: 'lg', px: 'h-10', use: 'Hero CTA, modali wide' },
    { size: 'icon-sm', px: 'h-5', use: 'Icon-only ultra-compatto' },
    { size: 'icon', px: 'h-6', use: 'Icon-only inline (chiusure, ⋯)' },
    { size: 'icon-lg', px: 'h-8', use: 'Icon-only toolbar' },
];

/* ═══════════════════════════════════════════════════════════════
   03 BADGES — varianti shadcn + pill-status utility CSS.
   ═══════════════════════════════════════════════════════════════ */
type BadgeVariantInfo = { variant: string; use: string };
const badgeVariantsInfo: BadgeVariantInfo[] = [
    {
        variant: 'default',
        use: 'Badge nero pieno (count, etichetta neutra forte).',
    },
    {
        variant: 'secondary',
        use: 'Tonale accent — coerente con Button.secondary. Per stati positivi soft (Pagato, Completato). Per Pianificato/Aperto/Non dovuto usa pill.',
    },
    {
        variant: 'destructive',
        use: 'Badge rosso pieno (errore, blocco, alert grave).',
    },
    {
        variant: 'outline',
        use: 'Badge solo bordo (count discreto, sezione meta).',
    },
];

const pillStatuses = [
    {
        className: 'pill pill--success',
        label: 'Pagato',
        use: 'Pagamento confermato / scadenza completata / fattura saldata',
    },
    {
        className: 'pill pill--warning',
        label: 'In arrivo',
        use: 'Scadenza imminente (30-60 giorni) / attenzione non bloccante',
    },
    {
        className: 'pill pill--danger',
        label: 'Scaduto',
        use: 'Pagamento oltre la data prevista / errore di import / blocco',
    },
    {
        className: 'pill pill--info',
        label: 'Pianificato',
        use: 'Pagamento generato dalla scadenza, importo ancora vuoto',
    },
    {
        className: 'pill pill--muted',
        label: 'Non dovuto',
        use: 'Scadenza marcata come non dovuta (deroga, credito sufficiente)',
    },
    {
        className: 'pill pill--neutral',
        label: 'Aperto',
        use: 'Scadenza aperta di default / record bozza',
    },
];

/* ═══════════════════════════════════════════════════════════════
   04 FORM FIELDS — state per esempi live.
   ═══════════════════════════════════════════════════════════════ */
const demoText = ref('');
const demoTextarea = ref('');
const demoSelect = ref('');
const demoCheck1 = ref(true);
const demoCheck2 = ref(false);
const demoCheck3 = ref(false);
const demoRadio = ref('residential');
const demoSwitch1 = ref(true);
const demoSwitch2 = ref(false);
const demoCombobox = ref<string>('');
const demoMultiselect = ref<string[]>(['residential', 'commercial']);
const demoSlider = ref<number[]>([35]);
const demoNumber = ref<number>(0);
const demoOtp = ref<string>('');
const demoTags = ref<string[]>(['Milano', 'Studio']);
const demoCalendar = ref<unknown>();
const demoDatePicker = ref<unknown>();
const demoRange = ref<unknown>();
const datePickerOpen = ref(false);
const demoTab = ref<string>('panoramica');

/* Choice Card / Choice Pill demo state. */
const demoCardRadio = ref<string>('residential');
const demoCardChecks = ref<string[]>(['residential', 'commercial']);
const demoCardSwitches = ref<Record<string, boolean>>({
    notif: true,
    auto: false,
    digest: true,
});
const demoPillRadio = ref<string>('residential');
const demoPillChecks = ref<string[]>(['residential', 'commercial']);
function togglePillCheck(value: string) {
    const i = demoPillChecks.value.indexOf(value);

    if (i >= 0) {
        demoPillChecks.value.splice(i, 1);
    } else {
        demoPillChecks.value.push(value);
    }
}

/* B.9 Modal patterns — demo state. */
const demoConfirmOpen = ref(false);
const demoWizardOpen = ref(false);
const demoWizardStep = ref(1);
function demoConfirm() {
    toast.success('Cliente eliminato.');
    demoConfirmOpen.value = false;
}

/* B.10 Misc — demo state. */
const demoSearch = ref<string>('');
const demoBudget = ref<string>('');
const demoProgress = ref<number>(45);
const demoView = ref<string>('list');
const demoFormat = ref<string[]>(['bold']);
const demoBoldOn = ref<boolean>(false);
const demoPagPage = ref<number>(3);
const demoSkeletonLoaded = ref<boolean>(false);
const demoPopoverOpen = ref<boolean>(false);
const demoButtonToggle = ref<boolean>(false);

/* B.8 Form layout — demo state. */
const demoForm = ref({
    name: 'Acme Architettura srl',
    piva: '01234567890',
    city: 'Milano',
    notes: '',
});

/* ═══════════════════════════════════════════════════════════════
   07 TABLES — demo dataset fatture.
   ═══════════════════════════════════════════════════════════════ */
type DemoInvoice = {
    code: string;
    client: string;
    date: string;
    state: 'success' | 'warning' | 'danger' | 'info' | 'muted' | 'neutral';
    stateLabel: string;
    importo: number;
    ritenuta: boolean;
};

/** Demo data — fatture per esempio tabella sezione 07. */
const demoProjects: DemoInvoice[] = [
    {
        code: 'FT 2026-01',
        client: 'Bianchi Studio srl',
        date: '15/01/2026',
        state: 'success',
        stateLabel: 'Pagata',
        importo: 4523.0,
        ritenuta: false,
    },
    {
        code: 'FT 2026-02',
        client: 'Rossi Costruzioni',
        date: '03/02/2026',
        state: 'success',
        stateLabel: 'Pagata',
        importo: 3890.12,
        ritenuta: true,
    },
    {
        code: 'FT 2026-03',
        client: 'Frassati srl',
        date: '24/02/2026',
        state: 'warning',
        stateLabel: 'In arrivo',
        importo: 12450.3,
        ritenuta: false,
    },
    {
        code: 'FT 2026-04',
        client: 'Comune di Trento',
        date: '10/03/2026',
        state: 'info',
        stateLabel: 'Pianificata',
        importo: 1850.0,
        ritenuta: false,
    },
    {
        code: 'FT 2026-05',
        client: 'Studio Verdi',
        date: '28/03/2026',
        state: 'danger',
        stateLabel: 'Scaduta',
        importo: 920.0,
        ritenuta: false,
    },
    {
        code: 'FT 2026-06',
        client: 'Famiglia Romano',
        date: '12/04/2026',
        state: 'neutral',
        stateLabel: 'Aperta',
        importo: 6300.0,
        ritenuta: true,
    },
];

const demoSelected = ref<string[]>([]);
const demoAllSelected = computed(
    () =>
        demoProjects.length > 0 &&
        demoSelected.value.length === demoProjects.length,
);
function toggleAll() {
    demoSelected.value = demoAllSelected.value
        ? []
        : demoProjects.map((p) => p.code);
}
function toggleRow(code: string) {
    const i = demoSelected.value.indexOf(code);

    if (i >= 0) {
        demoSelected.value.splice(i, 1);
    } else {
        demoSelected.value.push(code);
    }
}
function fmtEur(n: number): string {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(n);
}

const demoPage = ref(1);
const demoPerPage = ref(25);

/**
 * Demo data — voci di spesa standard del regime forfettario.
 * Usato come dataset di esempio in Select/Combobox/Multiselect/Radio/ChoiceCard
 * di tutta la sezione 04 Form fields.
 */
const projectTypes = [
    { value: 'imposta-sostitutiva', label: 'Imposta sostitutiva' },
    { value: 'inarcassa-soggettivo', label: 'Inarcassa Soggettivo' },
    { value: 'inarcassa-integrativo', label: 'Inarcassa Integrativo' },
    { value: 'inarcassa-maternita', label: 'Inarcassa Maternità' },
    { value: 'bolli', label: 'Bolli' },
    { value: 'commercialista', label: 'Commercialista' },
];

/* Helper per Date Picker — formatta @internationalized/date a dd/mm/yyyy. */
function formatDate(d: unknown): string {
    if (!d || typeof d !== 'object') {
        return '';
    }

    const dt = d as { day?: number; month?: number; year?: number };

    if (!dt.day || !dt.month || !dt.year) {
        return '';
    }

    const dd = String(dt.day).padStart(2, '0');
    const mm = String(dt.month).padStart(2, '0');

    return `${dd}/${mm}/${dt.year}`;
}

/* Smooth scroll alle ancore + tracking della sezione attiva via IntersectionObserver. */
const main = ref<HTMLElement | null>(null);

const goTo = (id: string) => {
    activeId.value = id;
    const el = document.getElementById(`s-${id}`);

    if (el && main.value) {
        main.value.scrollTo({ top: el.offsetTop - 24, behavior: 'smooth' });
    }
};

watchEffect((onCleanup) => {
    if (!main.value) {
return;
}

    const els = sections
        .map((s) => document.getElementById(`s-${s.id}`))
        .filter((el): el is HTMLElement => el !== null);

    if (els.length === 0) {
return;
}

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort(
                    (a, b) =>
                        a.boundingClientRect.top - b.boundingClientRect.top,
                )[0];

            if (visible) {
                const id = visible.target.id.replace(/^s-/, '');
                activeId.value = id;
            }
        },
        { root: main.value, rootMargin: '-20% 0px -70% 0px', threshold: 0 },
    );
    els.forEach((el) => observer.observe(el));
    onCleanup(() => observer.disconnect());
});
</script>

<template>
    <Head title="Design system" />

    <!-- Layout interno: TOC sidebar a sinistra + main scrollabile a destra.
         Pattern Studiofinance (single overflow-y-auto su entrambi). -->
    <div class="flex h-full">
        <!-- TOC: navigazione tra sezioni -->
        <nav
            class="w-[228px] shrink-0 overflow-y-auto border-r border-border"
            aria-label="Indice sezioni Design system"
        >
            <div class="p-5">
                <h2 class="text-13 font-medium text-foreground">Sezioni</h2>
                <p class="mt-1 text-2xs text-muted-foreground">
                    Build: Phase B in corso · ogni sotto-fase aggiunge una
                    sezione.
                </p>
            </div>

            <ul class="px-3 pb-6">
                <li v-for="s in sections" :key="s.id" class="my-0.5">
                    <button
                        type="button"
                        class="group flex w-full items-center justify-between gap-2 rounded px-3 py-1.5 text-left text-13 transition-colors"
                        :class="
                            activeId === s.id
                                ? 'bg-accent-vivid/10 font-medium text-foreground'
                                : 'text-foreground/75 hover:bg-accent-vivid/5 hover:text-foreground'
                        "
                        @click="goTo(s.id)"
                    >
                        <span class="truncate">{{ s.label }}</span>
                        <span
                            v-if="s.status === 'ready'"
                            class="size-1.5 shrink-0 rounded-full bg-accent-vivid"
                            aria-label="Sezione disponibile"
                        />
                        <span
                            v-else
                            class="tabular text-2xs text-muted-foreground/70"
                            :title="`Coming in Phase ${s.phase}`"
                            >{{ s.phase }}</span
                        >
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Main scrollabile -->
        <main ref="main" class="flex-1 overflow-y-auto">
            <div class="mx-auto w-full max-w-[1080px] px-10 py-10">
                <!-- Hero -->
                <header class="mb-10 border-b border-border pb-8">
                    <span class="kicker">Manifesto</span>
                    <h1
                        class="mt-2 text-3xl font-medium tracking-tight text-foreground"
                    >
                        Design system
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                    >
                        Token, primitive shadcn e pattern di layout dell'app.
                        Riferimento eseguibile: ogni sezione mostra come si usa,
                        con esempi live e regole. Costruito incrementalmente in
                        Phase B.
                    </p>
                </header>

                <!-- ─────────────────────────────────────────────────────────
                     01 Foundations (Phase B.1 — completata)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-foundations" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            01 Foundations
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>

                    <!-- 1.1 PALETTE BASE — colori cromatici "veri" -->
                    <div class="mb-12">
                        <h3 class="kicker mb-3">1.1 Palette base</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            <strong class="font-medium text-foreground"
                                >20 colori</strong
                            >
                            raggruppati per famiglia. Sono i token
                            <code class="kbd">--c-*</code> in
                            <code class="kbd">app.css</code>: i colori veri del
                            DS. Tutti gli altri token semantici (sezione 1.2)
                            puntano qui via <code class="kbd">var(--c-*)</code>.
                        </p>

                        <div
                            v-for="group in palette"
                            :key="group.title"
                            class="mb-8"
                        >
                            <div
                                class="mb-3 flex items-baseline justify-between"
                            >
                                <h4 class="text-sm font-medium text-foreground">
                                    {{ group.title }}
                                </h4>
                                <span
                                    v-if="group.subtitle"
                                    class="text-2xs text-muted-foreground"
                                >
                                    {{ group.subtitle }}
                                </span>
                            </div>
                            <div
                                class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
                            >
                                <div
                                    v-for="sw in group.swatches"
                                    :key="sw.var"
                                    class="flex flex-col gap-2 rounded-md border border-border bg-card p-3"
                                >
                                    <div
                                        class="border-border-soft h-10 w-full rounded border"
                                        :style="{
                                            backgroundColor: `var(${sw.var})`,
                                        }"
                                    />
                                    <div class="min-w-0">
                                        <code
                                            class="font-mono text-2xs text-foreground"
                                            >{{ sw.var }}</code
                                        >
                                        <div
                                            class="tabular mt-0.5 font-mono text-2xs text-muted-foreground"
                                        >
                                            {{ sw.value }}
                                        </div>
                                        <p
                                            v-if="sw.note"
                                            class="mt-1 text-2xs leading-snug text-muted-foreground/80"
                                        >
                                            {{ sw.note }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1.2 TOKEN SEMANTICI — mappatura ai colori base -->
                    <div class="mb-12">
                        <h3 class="kicker mb-3">1.2 Token semantici</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            Ogni token shadcn punta a un colore della palette
                            base.
                            <strong class="font-medium text-foreground"
                                >Sono questi i nomi che usi nei
                                componenti</strong
                            >
                            (<code class="kbd">bg-background</code>,
                            <code class="kbd">text-foreground</code>,
                            <code class="kbd">border-border</code>, ecc.). I
                            token <code class="kbd">--c-*</code>
                            non sono esposti come Tailwind utility — usa solo i
                            token semantici qui sotto.
                        </p>

                        <div
                            v-for="group in semantics"
                            :key="group.title"
                            class="mb-8"
                        >
                            <h4
                                class="mb-3 text-sm font-medium text-foreground"
                            >
                                {{ group.title }}
                            </h4>
                            <div
                                class="overflow-hidden rounded-md border border-border"
                            >
                                <div
                                    class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                                    style="
                                        grid-template-columns:
                                            minmax(220px, 1fr)
                                            auto minmax(180px, 1fr) 2fr;
                                    "
                                >
                                    <span>Token</span>
                                    <span>→</span>
                                    <span>Punta a</span>
                                    <span>Uso</span>
                                </div>
                                <div
                                    v-for="(t, i) in group.tokens"
                                    :key="t.token"
                                    class="grid items-center gap-4 bg-card px-4 py-2.5"
                                    :class="
                                        i < group.tokens.length - 1
                                            ? 'border-border-soft border-b'
                                            : ''
                                    "
                                    style="
                                        grid-template-columns:
                                            minmax(220px, 1fr)
                                            auto minmax(180px, 1fr) 2fr;
                                    "
                                >
                                    <code
                                        class="font-mono text-2xs text-foreground"
                                        >{{ t.token }}</code
                                    >
                                    <span class="text-muted-foreground/50"
                                        >→</span
                                    >
                                    <span
                                        class="flex min-w-0 items-center gap-2"
                                    >
                                        <span
                                            class="border-border-soft size-4 shrink-0 rounded border"
                                            :style="{
                                                backgroundColor: `var(${t.mapsTo})`,
                                            }"
                                        />
                                        <code
                                            class="truncate font-mono text-2xs text-muted-foreground"
                                            >{{ t.mapsTo }}</code
                                        >
                                    </span>
                                    <span
                                        class="text-2xs leading-snug text-muted-foreground/85"
                                        >{{ t.use }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Charts — placeholder, niente dataviz in v0 -->
                        <h4
                            class="mt-8 mb-3 text-sm font-medium text-foreground"
                        >
                            Charts
                        </h4>
                        <p
                            class="mb-3 max-w-2xl text-2xs text-muted-foreground"
                        >
                            5 colori dedicati alle visualizzazioni dati, non
                            derivati dalla palette base.
                            <strong class="font-medium text-foreground"
                                >Non usati in v0</strong
                            >: placeholder per future dashboard analitiche
                            (KPI numerici tabulari sono coperti da Mono +
                            tabular-nums).
                        </p>
                        <div class="flex gap-2">
                            <div
                                v-for="n in 5"
                                :key="n"
                                class="flex flex-1 flex-col items-center gap-1.5 rounded-md border border-border bg-card p-3"
                            >
                                <div
                                    class="h-10 w-full rounded"
                                    :style="{
                                        backgroundColor: `var(--chart-${n})`,
                                    }"
                                />
                                <code
                                    class="font-mono text-2xs text-muted-foreground"
                                    >--chart-{{ n }}</code
                                >
                            </div>
                        </div>

                        <!-- Regola d'uso: Petrol vs Ink -->
                        <div
                            class="mt-8 rounded-md border border-border bg-card p-5"
                        >
                            <h4 class="text-sm font-medium text-foreground">
                                Quando accent, quando ink
                            </h4>
                            <p
                                class="mt-2 max-w-2xl text-13 leading-relaxed text-muted-foreground"
                            >
                                I due colori d'accento del DS hanno ruoli
                                distinti.
                                <strong class="font-medium text-foreground"
                                    >Petrol</strong
                                >
                                = stato attivo / selezione persistente / valore
                                positivo.
                                <strong class="font-medium text-foreground"
                                    >Ink</strong
                                >
                                = brand / contenuto attivo / azione primaria.
                            </p>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div
                                    class="border-border-soft rounded border p-4"
                                >
                                    <p class="kicker mb-2">Petrol</p>
                                    <ul
                                        class="space-y-1 text-13 text-foreground/85"
                                    >
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Voce sidebar attiva (barra 2px
                                                accent + icona fill, no bg)</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Checkbox checked, Radio
                                                selected, Switch on</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Slider track filled +
                                                thumb</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Pill status positive ("Pagato",
                                                "Completato")</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Border focus form
                                                (input/textarea/select)</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                            />
                                            <span
                                                >Wordmark FIN, link inline
                                                (accent-vivid)</span
                                            >
                                        </li>
                                    </ul>
                                </div>
                                <div
                                    class="border-border-soft rounded border p-4"
                                >
                                    <p class="kicker mb-2">Ink (nero)</p>
                                    <ul
                                        class="space-y-1 text-13 text-foreground/85"
                                    >
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-foreground"
                                            />
                                            <span
                                                >Button primary (default —
                                                Salva, Conferma)</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-foreground"
                                            />
                                            <span
                                                >Tab attiva (testo +
                                                underline)</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-foreground"
                                            />
                                            <span>Breadcrumb current item</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-foreground"
                                            />
                                            <span
                                                >Testo (foreground) e tutto ciò
                                                che è
                                                "navigazione/contenuto"</span
                                            >
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span
                                                class="mt-2 size-1 shrink-0 rounded-full bg-foreground"
                                            />
                                            <span
                                                >Badge default (count forte,
                                                etichetta brand)</span
                                            >
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p
                                class="mt-4 text-2xs leading-snug text-muted-foreground/80"
                            >
                                <strong class="font-medium text-foreground"
                                    >Test rapido:</strong
                                >
                                "ho selezionato/abilitato/segnato come positivo
                                qualcosa?" → accent. "sto navigando o premendo
                                l'azione principale?" → ink.
                            </p>
                        </div>
                    </div>

                    <!-- 1.3 Typography -->
                    <div class="mb-12">
                        <h3 class="kicker mb-3">1.3 Typography</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            <strong class="font-medium text-foreground"
                                >Switzer</strong
                            >
                            per il sans (UI + display),
                            <strong class="font-medium text-foreground"
                                >Google Sans Code</strong
                            >
                            per codici, kbd e numeri tabular. Scala completa
                            Tailwind v4 + 2 token custom (<code class="kbd"
                                >text-2xs</code
                            >
                            11px e <code class="kbd">text-13</code> 13px).
                        </p>

                        <!-- Font samples -->
                        <div class="mb-8 grid gap-4 md:grid-cols-2">
                            <div
                                class="rounded-md border border-border bg-card p-5"
                            >
                                <div
                                    class="mb-3 flex items-baseline justify-between"
                                >
                                    <code
                                        class="font-mono text-2xs text-muted-foreground"
                                        >--font-sans</code
                                    >
                                    <span
                                        class="text-2xs text-muted-foreground/70"
                                        >Switzer</span
                                    >
                                </div>
                                <div
                                    class="font-sans text-2xl tracking-tight text-foreground"
                                >
                                    Aa Bb Cc Gg Qq
                                </div>
                                <div
                                    class="mt-2 font-sans text-13 text-foreground/85"
                                >
                                    Le scadenze fiscali si gestiscono qui.
                                </div>
                                <div
                                    class="mt-3 font-sans text-2xs tracking-wider text-muted-foreground uppercase"
                                >
                                    Pesi: 200 · 300 · 400 · 500 · 600 · 700
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-border bg-card p-5"
                            >
                                <div
                                    class="mb-3 flex items-baseline justify-between"
                                >
                                    <code
                                        class="font-mono text-2xs text-muted-foreground"
                                        >--font-mono</code
                                    >
                                    <span
                                        class="text-2xs text-muted-foreground/70"
                                        >Google Sans Code</span
                                    >
                                </div>
                                <div class="font-mono text-2xl text-foreground">
                                    FT 2026-04 · 4.523,00
                                </div>
                                <div
                                    class="tabular mt-2 font-mono text-13 text-foreground/85"
                                >
                                    €1.250,00 · 2026-05-06
                                </div>
                                <div
                                    class="mt-3 font-mono text-2xs tracking-wider text-muted-foreground uppercase"
                                >
                                    Pesi: 400 · 500 · 600
                                </div>
                            </div>
                        </div>

                        <!-- Type scale -->
                        <h4
                            class="mb-3 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                        >
                            Scale
                        </h4>
                        <div class="rounded-md border border-border">
                            <div
                                class="grid items-baseline gap-4 border-b border-border bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                                style="
                                    grid-template-columns: 110px 100px 1fr 1fr;
                                "
                            >
                                <span>Class</span>
                                <span>px / lh</span>
                                <span>Esempio</span>
                                <span>Uso</span>
                            </div>
                            <div
                                v-for="(t, i) in typographyScale"
                                :key="t.class"
                                class="grid items-center gap-4 px-4 py-3"
                                :class="
                                    i < typographyScale.length - 1
                                        ? 'border-border-soft border-b'
                                        : ''
                                "
                                style="
                                    grid-template-columns: 110px 100px 1fr 1fr;
                                "
                            >
                                <code
                                    class="flex items-center gap-1.5 font-mono text-2xs text-foreground"
                                >
                                    {{ t.class }}
                                    <span
                                        v-if="t.custom"
                                        class="rounded bg-accent-vivid/15 px-1 text-2xs text-accent-strong"
                                        title="Token custom (non Tailwind nativo)"
                                        >c</span
                                    >
                                </code>
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >{{ t.px }}</span
                                >
                                <span :class="t.class" class="text-foreground"
                                    >The quick fox</span
                                >
                                <span
                                    class="text-2xs text-muted-foreground/85"
                                    >{{ t.use }}</span
                                >
                            </div>
                        </div>

                        <!-- Font features -->
                        <h4
                            class="mt-8 mb-3 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                        >
                            font-feature-settings
                        </h4>
                        <ul class="grid gap-1.5 text-13 md:grid-cols-2">
                            <li
                                v-for="ff in fontFeatureSettings"
                                :key="ff.tag"
                                class="flex items-baseline gap-2"
                            >
                                <code
                                    class="font-mono text-2xs text-accent-strong"
                                    >{{ ff.tag }}</code
                                >
                                <span class="text-13 text-muted-foreground">
                                    {{ ff.desc }}
                                    <span
                                        v-if="ff.mono"
                                        class="text-2xs text-muted-foreground/60"
                                        >(solo mono)</span
                                    >
                                </span>
                            </li>
                        </ul>
                        <p
                            class="mt-3 max-w-2xl text-2xs leading-snug text-muted-foreground/80"
                        >
                            Inoltre,
                            <code class="kbd">font-size-adjust: 0.58</code> sul
                            body normalizza l'altezza ottica di Switzer
                            (x-height più bassa di Inter/Geist) — senza questo,
                            a parità di <code class="kbd">font-size</code> il
                            testo apparirebbe più piccolo.
                        </p>
                    </div>

                    <!-- 1.4 Spacing -->
                    <div class="mb-12">
                        <h3 class="kicker mb-3">1.4 Spacing</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            Scala Tailwind v4 (4px base). Usa sempre i token
                            Tailwind, niente valori arbitrari
                            <code class="kbd">[Xpx]</code> custom.
                        </p>

                        <div class="rounded-md border border-border">
                            <div
                                class="grid items-baseline gap-4 border-b border-border bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                                style="grid-template-columns: 110px 80px 1fr"
                            >
                                <span>Class</span>
                                <span>px</span>
                                <span>Visual</span>
                            </div>
                            <div
                                v-for="(s, i) in spacingScale"
                                :key="s.class"
                                class="grid items-center gap-4 px-4 py-2.5"
                                :class="
                                    i < spacingScale.length - 1
                                        ? 'border-border-soft border-b'
                                        : ''
                                "
                                style="grid-template-columns: 110px 80px 1fr"
                            >
                                <code
                                    class="font-mono text-2xs text-foreground"
                                    >{{ s.class }}</code
                                >
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >{{ s.px }}px</span
                                >
                                <div class="flex items-center">
                                    <div
                                        class="h-2 rounded-sm bg-accent-vivid"
                                        :style="{ width: `${s.px}px` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1.5 Radius -->
                    <div class="mb-12">
                        <h3 class="kicker mb-3">1.5 Radius</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            Token <code class="kbd">--radius: 4px</code> come
                            default. Studiofinance dichiara 4 step separati
                            (sm 3 · default 4 · md 6 · lg 8), non derivati: la
                            scala è breve, niente
                            <code class="kbd">rounded-2xl</code> / 3xl che
                            farebbero leggere consumer.
                        </p>

                        <div class="grid grid-cols-3 gap-3 md:grid-cols-7">
                            <div
                                v-for="r in radiusScale"
                                :key="r.class"
                                class="flex flex-col items-center gap-2"
                            >
                                <div
                                    class="h-12 w-12 border border-border bg-accent"
                                    :class="r.class"
                                />
                                <code
                                    class="font-mono text-2xs text-foreground"
                                    >{{ r.class }}</code
                                >
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >{{ r.px }}px</span
                                >
                                <span
                                    v-if="r.note"
                                    class="text-2xs text-muted-foreground/70"
                                    >{{ r.note }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 1.6 Utility valide -->
                    <div class="mb-4">
                        <h3 class="kicker mb-3">1.6 Utility CSS</h3>
                        <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                            Utility globali in <code class="kbd">app.css</code>.
                            Sono il sorgente canonico del DS — usabili in
                            qualsiasi pagina.
                        </p>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div
                                class="rounded-md border border-border bg-card p-4"
                            >
                                <code class="font-mono text-2xs text-foreground"
                                    >.kicker</code
                                >
                                <p class="mt-1 text-2xs text-muted-foreground">
                                    Uppercase mono 11px tracking 0.06em. Per
                                    label di sezione e eyebrow.
                                </p>
                                <div class="mt-3 rounded bg-muted px-3 py-2">
                                    <span class="kicker">Esempio kicker</span>
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-border bg-card p-4"
                            >
                                <code class="font-mono text-2xs text-foreground"
                                    >.tabular</code
                                >
                                <p class="mt-1 text-2xs text-muted-foreground">
                                    <code
                                        >font-variant-numeric:
                                        tabular-nums</code
                                    >. Cifre a larghezza fissa.
                                </p>
                                <div
                                    class="mt-3 flex items-center justify-between rounded bg-muted px-3 py-2 text-sm"
                                >
                                    <span class="tabular">€1.250,00</span>
                                    <span class="tabular">€ 85,00</span>
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-border bg-card p-4"
                            >
                                <code class="font-mono text-2xs text-foreground"
                                    >.kbd</code
                                >
                                <p class="mt-1 text-2xs text-muted-foreground">
                                    Capsula tastiera mono, h-18px. Per
                                    scorciatoie inline (⌘K, ⌘B, ecc.).
                                </p>
                                <div
                                    class="mt-3 flex items-center gap-1 rounded bg-muted px-3 py-2"
                                >
                                    <kbd class="kbd">⌘</kbd>
                                    <kbd class="kbd">K</kbd>
                                    <span
                                        class="ml-2 text-13 text-muted-foreground"
                                        >apre la palette</span
                                    >
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-border bg-card p-4"
                            >
                                <code class="font-mono text-2xs text-foreground"
                                    >.status-dot</code
                                >
                                <p class="mt-1 text-2xs text-muted-foreground">
                                    Dot 6px inline. Modificatori
                                    <code>--active</code>, <code>--muted</code>.
                                </p>
                                <div
                                    class="mt-3 flex items-center gap-3 rounded bg-muted px-3 py-2 text-13"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span
                                            class="status-dot status-dot--active"
                                        />
                                        Attivo
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <span
                                            class="status-dot status-dot--muted"
                                        />
                                        Inattivo
                                    </span>
                                </div>
                            </div>

                            <div
                                class="rounded-md border border-border bg-card p-4"
                            >
                                <code class="font-mono text-2xs text-foreground"
                                    >.code-pill</code
                                >
                                <p class="mt-1 text-2xs text-muted-foreground">
                                    Token user-facing in mono compatto: numero
                                    fattura, codice F24, identificatori che
                                    l'utente riconosce e cita. Mai per
                                    system-language interna.
                                </p>
                                <div
                                    class="mt-3 flex flex-wrap items-center gap-2 rounded bg-muted px-3 py-2"
                                >
                                    <span class="code-pill">FT 2026-04</span>
                                    <span class="code-pill">F24-06-2026</span>
                                    <span class="code-pill">CF · 78%</span>
                                </div>
                            </div>

                        </div>

                        <!-- Pattern: opacity-modifier vs hex piatto -->
                        <div
                            class="mt-6 rounded-md border border-border bg-card p-5"
                        >
                            <h4 class="text-sm font-medium text-foreground">
                                Pattern: opacity-modifier al posto di hex piatti
                            </h4>
                            <p
                                class="mt-2 max-w-2xl text-13 leading-relaxed text-muted-foreground"
                            >
                                Studiofinance compone i fill accent soft con
                                <code class="font-mono text-2xs"
                                    >color-mix()</code
                                >
                                sul petrol base, non con hex piatti. Vantaggio:
                                lo stesso bg si adatta in modo organico al
                                canvas sotto (zinc-50 main vs zinc-100 sidebar
                                vs zinc-50 card). In Tailwind v4 l'opacity
                                modifier <code class="kbd">/10</code>,
                                <code class="kbd">/15</code>,
                                <code class="kbd">/30</code> compila esattamente
                                quei mix in CSS.
                            </p>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div
                                    class="border-border-soft rounded border p-3"
                                >
                                    <div
                                        class="h-10 rounded bg-accent-vivid/10"
                                    />
                                    <code
                                        class="mt-2 block font-mono text-2xs text-foreground"
                                    >
                                        bg-accent-vivid/10
                                    </code>
                                    <p
                                        class="mt-1 text-2xs text-muted-foreground/80"
                                    >
                                        Voce sidebar / item attivo, bg secondary
                                        tonale.
                                    </p>
                                </div>
                                <div
                                    class="border-border-soft rounded border p-3"
                                >
                                    <div
                                        class="h-10 rounded bg-accent-vivid/15"
                                    />
                                    <code
                                        class="mt-2 block font-mono text-2xs text-foreground"
                                    >
                                        bg-accent-vivid/15
                                    </code>
                                    <p
                                        class="mt-1 text-2xs text-muted-foreground/80"
                                    >
                                        Hover su tonale soft.
                                    </p>
                                </div>
                                <div
                                    class="border-border-soft rounded border p-3"
                                >
                                    <div
                                        class="h-10 rounded bg-accent-vivid/30"
                                    />
                                    <code
                                        class="mt-2 block font-mono text-2xs text-foreground"
                                    >
                                        bg-accent-vivid/30
                                    </code>
                                    <p
                                        class="mt-1 text-2xs text-muted-foreground/80"
                                    >
                                        Border highlight, focus ring soft.
                                    </p>
                                </div>
                            </div>
                            <p
                                class="mt-4 max-w-2xl text-2xs leading-snug text-muted-foreground/80"
                            >
                                <strong class="font-medium text-foreground"
                                    >Quando usare invece il token
                                    piatto?</strong
                                >
                                Solo dove l'opacity-modifier non è praticabile:
                                utility CSS classiche in
                                <code class="kbd">app.css</code> (es.
                                <code class="kbd">.pill</code>). Per
                                tutto il resto preferisci
                                <code class="kbd">/10</code>,
                                <code class="kbd">/15</code>,
                                <code class="kbd">/30</code>.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     02 Buttons (Phase B.2)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-buttons" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            02 Buttons
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Componente <code class="kbd">&lt;Button&gt;</code> da
                        <code class="kbd">@/components/ui/button</code>.
                        Varianti definite via <code class="kbd">cva</code> in
                        <code class="kbd">button/index.ts</code>. Usa sempre
                        questo componente — niente
                        <code class="kbd">&lt;button&gt;</code> HTML diretto
                        nelle pagine.
                    </p>

                    <!-- 2.1 Varianti — esempi live + tabella uso -->
                    <h3 class="kicker mb-3">2.1 Varianti</h3>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Button variant="default">Salva</Button>
                            <Button variant="destructive">Elimina</Button>
                            <Button variant="outline">Annulla</Button>
                            <Button variant="secondary">
                                <PhCheck />
                                Mostra dettagli
                            </Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="link">Link</Button>
                        </div>
                        <p
                            class="mt-4 text-2xs leading-snug text-muted-foreground"
                        >
                            <strong class="font-medium text-foreground"
                                >Primary unico</strong
                            >: ink (<code class="kbd">default</code>). Il petrol
                            non viene usato come primary filled — entra come
                            tonale soft nella variant
                            <code class="kbd">secondary</code>, per azioni
                            contestuali soft (Mostra dettagli, Vedi storico).
                            Implementazione:
                            <code class="font-mono"
                                >bg-accent-vivid/10 text-accent-strong</code
                            >.
                        </p>
                    </div>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 140px 1fr"
                        >
                            <span>Variant</span>
                            <span>Uso</span>
                        </div>
                        <div
                            v-for="(v, i) in buttonVariantsInfo"
                            :key="v.variant"
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            :class="
                                i < buttonVariantsInfo.length - 1
                                    ? 'border-border-soft border-b'
                                    : ''
                            "
                            style="grid-template-columns: 140px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground">{{
                                v.variant
                            }}</code>
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                                >{{ v.use }}</span
                            >
                        </div>
                    </div>

                    <!-- 2.2 Sizes -->
                    <h3 class="kicker mb-3">2.2 Sizes</h3>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Button size="sm">Small</Button>
                            <Button size="default">Default</Button>
                            <Button size="lg">Large</Button>
                            <Button
                                size="icon-sm"
                                variant="outline"
                                aria-label="Aggiungi"
                            >
                                <PhPlus />
                            </Button>
                            <Button
                                size="icon"
                                variant="outline"
                                aria-label="Aggiungi"
                            >
                                <PhPlus />
                            </Button>
                            <Button
                                size="icon-lg"
                                variant="outline"
                                aria-label="Aggiungi"
                            >
                                <PhPlus />
                            </Button>
                        </div>
                    </div>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 140px 90px 1fr"
                        >
                            <span>Size</span>
                            <span>Altezza</span>
                            <span>Uso</span>
                        </div>
                        <div
                            v-for="(s, i) in buttonSizes"
                            :key="s.size"
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            :class="
                                i < buttonSizes.length - 1
                                    ? 'border-border-soft border-b'
                                    : ''
                            "
                            style="grid-template-columns: 140px 90px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground">{{
                                s.size
                            }}</code>
                            <code
                                class="tabular font-mono text-2xs text-muted-foreground"
                                >{{ s.px }}</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                                >{{ s.use }}</span
                            >
                        </div>
                    </div>

                    <!-- 2.3 Stati: hover, focus, disabled, loading, con icona -->
                    <h3 class="kicker mb-3">2.3 Stati</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">Default + icon leading</p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="default">
                                    <PhPlus />
                                    Nuova fattura
                                </Button>
                                <Button variant="outline">
                                    <PhDownload />
                                    Esporta
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">Icon trailing</p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline">
                                    Continua
                                    <PhArrowRight />
                                </Button>
                                <Button variant="ghost">
                                    Modifica
                                    <PhPencilSimple />
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">Disabled</p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="default" disabled
                                    >Salva</Button
                                >
                                <Button variant="outline" disabled
                                    >Annulla</Button
                                >
                                <Button variant="destructive" disabled
                                    >Elimina</Button
                                >
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">Loading</p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="default" disabled>
                                    <PhSpinnerGap class="animate-spin" />
                                    Salvataggio…
                                </Button>
                                <Button variant="outline" disabled>
                                    <PhSpinnerGap class="animate-spin" />
                                    Caricamento…
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4 md:col-span-2"
                        >
                            <p class="kicker mb-3">
                                Destructive con conferma — pattern tipico modal
                                footer
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline">
                                    <PhX />
                                    Annulla
                                </Button>
                                <Button variant="destructive">
                                    <PhTrash />
                                    Elimina definitivamente
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- 2.4 Shortcut tastiera (Kbd) -->
                    <h3 class="kicker mt-10 mb-3">2.4 Shortcut tastiera</h3>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Aggiungi <code class="kbd">&lt;Kbd&gt;</code> dentro il
                        <code class="kbd">&lt;Button&gt;</code> per shortcut
                        visibili (modali, toolbar, palette comandi). Il Kbd
                        <strong class="font-medium text-foreground"
                            >si auto-adatta al colore del bottone</strong
                        >: tono chiaro automatico dentro variant
                        <code class="kbd">default</code> e
                        <code class="kbd">destructive</code> (sfondi scuri),
                        tono muted dentro <code class="kbd">outline</code> /
                        <code class="kbd">ghost</code> /
                        <code class="kbd">secondary</code>. Niente prop da
                        passare.
                    </p>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">
                                Modal footer — Salva &amp; Annulla
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="ghost">
                                    Annulla
                                    <Kbd>esc</Kbd>
                                </Button>
                                <Button variant="default">
                                    Salva
                                    <Kbd>⌘↵</Kbd>
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">
                                Destructive — conferma rapida
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline">
                                    Annulla
                                    <Kbd>esc</Kbd>
                                </Button>
                                <Button variant="destructive">
                                    Elimina
                                    <Kbd>⌘⌫</Kbd>
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">
                                Toolbar — azioni con shortcut
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm">
                                    Nuovo
                                    <Kbd>N</Kbd>
                                </Button>
                                <Button variant="outline" size="sm">
                                    Cerca
                                    <Kbd>⌘K</Kbd>
                                </Button>
                                <Button variant="ghost" size="sm">
                                    Filtri
                                    <Kbd>F</Kbd>
                                </Button>
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-3">Quando NON usarlo</p>
                            <ul
                                class="space-y-1.5 text-2xs text-muted-foreground"
                            >
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-0.5 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Bottoni cliccabili da non-power-user
                                        (UI mobile, onboarding).</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-0.5 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Quando lo shortcut non è davvero attivo
                                        (mostrarlo è bugiardo).</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-0.5 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Per puro decoro / sembrare "pro".</span
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 2.5 Toggle pattern (aria-pressed sull'outline) -->
                    <h3 class="kicker mt-10 mb-3">2.5 Toggle pattern</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        Per bottoni che togglano uno stato persistente (apertura
                        pannello, view-switch binario, filtro on/off) usa
                        <code class="kbd">variant="outline"</code> con
                        <code class="kbd">:aria-pressed</code>. Lo stato "on"
                        condivide lo stesso look accent tonale del hover —
                        <strong class="font-medium text-foreground"
                            >la continuità tra hover e pressed</strong
                        >
                        rende il toggle leggibile senza bisogno di una variant
                        separata.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                variant="outline"
                                size="sm"
                                :aria-pressed="demoButtonToggle"
                                @click="demoButtonToggle = !demoButtonToggle"
                            >
                                <PhFunnel />
                                Filtri
                            </Button>
                            <span class="text-2xs text-muted-foreground">
                                Stato:
                                <code class="font-mono text-foreground">
                                    {{
                                        demoButtonToggle
                                            ? 'pressed (on)'
                                            : 'rest (off)'
                                    }}
                                </code>
                                · prova anche l'hover per vedere la continuità
                            </span>
                        </div>
                    </div>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Button
  variant="outline"
  :aria-pressed="filtersOpen"
  @click="filtersOpen = !filtersOpen"
&gt;
  &lt;PhFunnel /&gt; Filtri
&lt;/Button&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     03 Badges (Phase B.2)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-badges" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            03 Badges
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Due famiglie:
                        <strong class="font-medium text-foreground"
                            >badge solid</strong
                        >
                        (componente
                        <code class="kbd">&lt;Badge&gt;</code> shadcn — count,
                        etichette forti) e
                        <strong class="font-medium text-foreground"
                            >pill status soft</strong
                        >
                        (utility CSS in <code class="kbd">app.css</code> — stati
                        di record). Nota di coerenza: la variant
                        <code class="kbd">secondary</code> di Badge e Button
                        condivide lo stesso tono accent tonale — il "secondary"
                        del DS è un solo concetto visivo, riusato ovunque.
                    </p>

                    <!-- 3.1 Badge solid (componente shadcn) -->
                    <h3 class="kicker mb-3">3.1 Badge — varianti shadcn</h3>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge variant="default">12</Badge>
                            <Badge variant="secondary">Pagato</Badge>
                            <Badge variant="destructive">Scaduto</Badge>
                            <Badge variant="outline">2026</Badge>
                            <Badge variant="default">
                                <PhCheck />
                                Pagato
                            </Badge>
                        </div>
                    </div>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 160px 1fr"
                        >
                            <span>Variant</span>
                            <span>Uso</span>
                        </div>
                        <div
                            v-for="(v, i) in badgeVariantsInfo"
                            :key="v.variant"
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            :class="
                                i < badgeVariantsInfo.length - 1
                                    ? 'border-border-soft border-b'
                                    : ''
                            "
                            style="grid-template-columns: 160px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground">{{
                                v.variant
                            }}</code>
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                                >{{ v.use }}</span
                            >
                        </div>
                    </div>

                    <!-- 3.2 Pill status soft (utility CSS) -->
                    <h3 class="kicker mb-3">
                        3.2 Pill status soft — utility CSS
                    </h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        Per gli
                        <strong class="font-medium text-foreground"
                            >stati di record</strong
                        >
                        (attivo, sospeso, bloccato, archiviato) usa la utility
                        <code class="kbd">.pill</code> +
                        modificatore di tono. Più morbida del Badge solid,
                        perfetta per liste e detail panel.
                    </p>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                v-for="p in pillStatuses"
                                :key="p.label"
                                :class="p.className"
                                >{{ p.label }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 1fr 130px 2fr"
                        >
                            <span>Class</span>
                            <span>Demo</span>
                            <span>Uso</span>
                        </div>
                        <div
                            v-for="(p, i) in pillStatuses"
                            :key="p.label"
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            :class="
                                i < pillStatuses.length - 1
                                    ? 'border-border-soft border-b'
                                    : ''
                            "
                            style="grid-template-columns: 1fr 130px 2fr"
                        >
                            <code class="font-mono text-2xs text-foreground">{{
                                p.className
                            }}</code>
                            <span
                                ><span :class="p.className">{{
                                    p.label
                                }}</span></span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                                >{{ p.use }}</span
                            >
                        </div>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     04 Form fields (Phase B.2)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-forms" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            04 Form fields
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Tutte primitive shadcn. Niente HTML diretto.
                        <strong class="font-medium text-foreground"
                            >Convenzioni</strong
                        >: testo <code class="kbd">text-13</code>, focus border
                        accent (no ring), margine label
                        <code class="kbd">mb-2</code>, asterisco rosso quando
                        <code class="kbd">required</code>.
                    </p>

                    <!-- 4.1 Input — wrapper FormField -->
                    <h3 class="kicker mb-3">4.1 Input</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Wrap in <code class="kbd">&lt;FormField&gt;</code> con
                        prop <code class="kbd">label</code>,
                        <code class="kbd">required</code>,
                        <code class="kbd">invalid</code>,
                        <code class="kbd">hint</code>. Internamente usa Field +
                        FieldLabel + FieldDescription/FieldError — niente
                        boilerplate.
                    </p>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Email"
                                required
                                hint="Required → asterisco automatico. Focus → border accent."
                            >
                                <Input
                                    v-model="demoText"
                                    type="email"
                                    placeholder="nome@studio.it"
                                />
                            </FormField>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Disabilitato"
                                hint="Stato disabled."
                            >
                                <Input
                                    disabled
                                    placeholder="Non modificabile"
                                />
                            </FormField>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField label="Errore" invalid>
                                <Input value="testo non valido" />
                                <template #error>Email non valida.</template>
                            </FormField>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Numerico"
                                hint="Aggiungi .tabular per cifre allineate."
                            >
                                <Input
                                    type="number"
                                    placeholder="0,00"
                                    class="tabular"
                                />
                            </FormField>
                        </div>
                    </div>

                    <!-- 4.2 Textarea -->
                    <h3 class="kicker mb-3">4.2 Textarea</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField label="Note">
                            <Textarea
                                v-model="demoTextarea"
                                placeholder="Note sulla fattura, riferimenti pagamento, comunicazioni…"
                                rows="3"
                            />
                        </FormField>
                    </div>

                    <!-- 4.3 Select -->
                    <h3 class="kicker mb-3">4.3 Select</h3>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Voce di spesa"
                                required
                                hint="Default state con placeholder."
                            >
                                <Select v-model="demoSelect">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Seleziona…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="t in projectTypes"
                                            :key="t.value"
                                            :value="t.value"
                                            >{{ t.label }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </FormField>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField label="Disabilitato">
                                <Select disabled>
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="Non modificabile"
                                        />
                                    </SelectTrigger>
                                    <SelectContent />
                                </Select>
                            </FormField>
                        </div>
                    </div>

                    <!-- 4.4 Checkbox — gruppo via FieldSet + FieldGroup -->
                    <h3 class="kicker mb-3">4.4 Checkbox</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FieldSet>
                            <FieldLegend>Opzioni</FieldLegend>
                            <FieldDescription
                                >Selezione multipla.</FieldDescription
                            >
                            <FieldGroup data-slot="checkbox-group">
                                <Field orientation="horizontal">
                                    <Checkbox
                                        id="ds-check-1"
                                        v-model="demoCheck1"
                                    />
                                    <FieldLabel for="ds-check-1"
                                        >Spunta attiva</FieldLabel
                                    >
                                </Field>
                                <Field orientation="horizontal">
                                    <Checkbox
                                        id="ds-check-2"
                                        v-model="demoCheck2"
                                    />
                                    <FieldLabel for="ds-check-2"
                                        >Da spuntare</FieldLabel
                                    >
                                </Field>
                                <Field
                                    orientation="horizontal"
                                    data-disabled="true"
                                >
                                    <Checkbox
                                        id="ds-check-3"
                                        v-model="demoCheck3"
                                        disabled
                                    />
                                    <FieldLabel for="ds-check-3"
                                        >Disabilitato</FieldLabel
                                    >
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    </div>

                    <!-- 4.5 Radio (RadioGroup) — gruppo via FieldSet + RadioGroup -->
                    <h3 class="kicker mb-3">4.5 Radio</h3>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FieldSet>
                                <FieldLegend>Voce di spesa</FieldLegend>
                                <FieldDescription
                                    >Selezione singola
                                    obbligatoria.</FieldDescription
                                >
                                <RadioGroup v-model="demoRadio">
                                    <Field
                                        v-for="t in projectTypes.slice(0, 4)"
                                        :key="t.value"
                                        orientation="horizontal"
                                    >
                                        <RadioGroupItem
                                            :id="`ds-radio-${t.value}`"
                                            :value="t.value"
                                        />
                                        <FieldLabel
                                            :for="`ds-radio-${t.value}`"
                                        >
                                            {{ t.label }}
                                        </FieldLabel>
                                    </Field>
                                </RadioGroup>
                            </FieldSet>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FieldSet data-disabled="true">
                                <FieldLegend>Disabilitato</FieldLegend>
                                <RadioGroup default-value="a" disabled>
                                    <Field
                                        orientation="horizontal"
                                        data-disabled="true"
                                    >
                                        <RadioGroupItem
                                            id="ds-radio-d-a"
                                            value="a"
                                        />
                                        <FieldLabel for="ds-radio-d-a"
                                            >Opzione A</FieldLabel
                                        >
                                    </Field>
                                    <Field
                                        orientation="horizontal"
                                        data-disabled="true"
                                    >
                                        <RadioGroupItem
                                            id="ds-radio-d-b"
                                            value="b"
                                        />
                                        <FieldLabel for="ds-radio-d-b"
                                            >Opzione B</FieldLabel
                                        >
                                    </Field>
                                </RadioGroup>
                            </FieldSet>
                        </div>
                    </div>

                    <!-- 4.6 Switch — gruppo via FieldSet + FieldGroup -->
                    <h3 class="kicker mb-3">4.6 Switch</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FieldSet>
                            <FieldLegend>Preferenze</FieldLegend>
                            <FieldDescription
                                >Toggle on/off per ogni
                                preferenza.</FieldDescription
                            >
                            <FieldGroup>
                                <Field orientation="horizontal">
                                    <Switch
                                        id="ds-switch-1"
                                        v-model="demoSwitch1"
                                    />
                                    <FieldLabel for="ds-switch-1"
                                        >Notifiche email</FieldLabel
                                    >
                                </Field>
                                <Field orientation="horizontal">
                                    <Switch
                                        id="ds-switch-2"
                                        v-model="demoSwitch2"
                                    />
                                    <FieldLabel for="ds-switch-2"
                                        >Modalità compatta</FieldLabel
                                    >
                                </Field>
                                <Field
                                    orientation="horizontal"
                                    data-disabled="true"
                                >
                                    <Switch id="ds-switch-3" disabled />
                                    <FieldLabel for="ds-switch-3"
                                        >Disabilitato</FieldLabel
                                    >
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    </div>

                    <!-- 4.7 Combobox (autocomplete) -->
                    <h3 class="kicker mb-3">4.7 Combobox</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Voce di spesa"
                            hint="Autocomplete con ricerca: ideale quando le opzioni sono molte."
                        >
                            <Combobox v-model="demoCombobox" by="value">
                                <ComboboxAnchor class="w-full">
                                    <ComboboxTrigger as-child>
                                        <Button
                                            variant="outline"
                                            type="button"
                                            class="w-full justify-between font-normal"
                                        >
                                            <span
                                                :class="
                                                    !demoCombobox
                                                        ? 'text-muted-foreground/70'
                                                        : ''
                                                "
                                            >
                                                {{
                                                    projectTypes.find(
                                                        (t) =>
                                                            t.value ===
                                                            demoCombobox,
                                                    )?.label ??
                                                    'Cerca tipologia…'
                                                }}
                                            </span>
                                            <PhArrowRight
                                                class="ml-2 size-3.5 rotate-90 opacity-50"
                                            />
                                        </Button>
                                    </ComboboxTrigger>
                                </ComboboxAnchor>
                                <ComboboxList class="w-full">
                                    <div class="relative w-full">
                                        <ComboboxInput placeholder="Cerca…" />
                                    </div>
                                    <ComboboxEmpty
                                        >Nessun risultato.</ComboboxEmpty
                                    >
                                    <ComboboxGroup>
                                        <ComboboxItem
                                            v-for="t in projectTypes"
                                            :key="t.value"
                                            :value="t.value"
                                        >
                                            {{ t.label }}
                                            <ComboboxItemIndicator>
                                                <PhCheck class="size-3.5" />
                                            </ComboboxItemIndicator>
                                        </ComboboxItem>
                                    </ComboboxGroup>
                                </ComboboxList>
                            </Combobox>
                        </FormField>
                    </div>

                    <!-- 4.8 Multiselect (Combobox multiple) -->
                    <h3 class="kicker mb-3">4.8 Multiselect</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Voci di spesa multiple"
                            hint="Stesso primitive Combobox con multiple: i selezionati appaiono come Badge accent tonale."
                        >
                            <Combobox
                                v-model="demoMultiselect"
                                by="value"
                                multiple
                            >
                                <ComboboxAnchor class="w-full">
                                    <ComboboxTrigger as-child>
                                        <Button
                                            variant="outline"
                                            type="button"
                                            class="h-auto w-full justify-between py-1.5 font-normal"
                                        >
                                            <div
                                                class="flex flex-1 flex-wrap items-center gap-1"
                                            >
                                                <template
                                                    v-if="
                                                        demoMultiselect.length
                                                    "
                                                >
                                                    <Badge
                                                        v-for="v in demoMultiselect"
                                                        :key="v"
                                                        variant="secondary"
                                                        class="font-normal"
                                                    >
                                                        {{
                                                            projectTypes.find(
                                                                (t) =>
                                                                    t.value ===
                                                                    v,
                                                            )?.label ?? v
                                                        }}
                                                    </Badge>
                                                </template>
                                                <span
                                                    v-else
                                                    class="text-muted-foreground/70"
                                                >
                                                    Seleziona tipologie…
                                                </span>
                                            </div>
                                            <PhArrowRight
                                                class="ml-2 size-3.5 rotate-90 opacity-50"
                                            />
                                        </Button>
                                    </ComboboxTrigger>
                                </ComboboxAnchor>
                                <ComboboxList class="w-full">
                                    <div class="relative w-full">
                                        <ComboboxInput placeholder="Cerca…" />
                                    </div>
                                    <ComboboxEmpty
                                        >Nessun risultato.</ComboboxEmpty
                                    >
                                    <ComboboxGroup>
                                        <ComboboxItem
                                            v-for="t in projectTypes"
                                            :key="t.value"
                                            :value="t.value"
                                        >
                                            {{ t.label }}
                                            <ComboboxItemIndicator>
                                                <PhCheck class="size-3.5" />
                                            </ComboboxItemIndicator>
                                        </ComboboxItem>
                                    </ComboboxGroup>
                                </ComboboxList>
                            </Combobox>
                        </FormField>
                    </div>

                    <!-- 4.9 Number field -->
                    <h3 class="kicker mb-3">4.9 Number field</h3>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Quantità"
                                hint="Frecce su/giù da tastiera + bottoni +/−."
                            >
                                <NumberField
                                    v-model="demoNumber"
                                    :min="0"
                                    :max="999"
                                    class="w-full"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                            </FormField>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Importo (€)"
                                hint="format-options per valuta / percentuali."
                            >
                                <NumberField
                                    :default-value="1250"
                                    :format-options="{
                                        style: 'currency',
                                        currency: 'EUR',
                                    }"
                                    class="w-full"
                                >
                                    <NumberFieldContent>
                                        <NumberFieldDecrement />
                                        <NumberFieldInput class="tabular" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                            </FormField>
                        </div>
                    </div>

                    <!-- 4.10 Slider -->
                    <h3 class="kicker mb-3">4.10 Slider</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <Field>
                            <div class="flex items-baseline justify-between">
                                <FieldLabel>Aliquota imposta sostitutiva (%)</FieldLabel>
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                >
                                    {{ demoSlider[0] }}%
                                </span>
                            </div>
                            <Slider v-model="demoSlider" :max="100" :step="1" />
                            <FieldDescription>
                                Singolo valore. Per range usa array
                                <code class="kbd">v-model="[min, max]"</code>.
                            </FieldDescription>
                        </Field>
                    </div>

                    <!-- 4.11 Tags input -->
                    <h3 class="kicker mb-3">4.11 Tags input</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Etichette fattura"
                            hint="Enter per aggiungere. Backspace su input vuoto rimuove l'ultimo."
                        >
                            <TagsInput v-model="demoTags">
                                <TagsInputItem
                                    v-for="t in demoTags"
                                    :key="t"
                                    :value="t"
                                >
                                    <TagsInputItemText>{{
                                        t
                                    }}</TagsInputItemText>
                                    <TagsInputItemDelete />
                                </TagsInputItem>
                                <TagsInputInput placeholder="Aggiungi tag…" />
                            </TagsInput>
                        </FormField>
                    </div>

                    <!-- 4.12 Input OTP -->
                    <h3 class="kicker mb-3">4.12 Input OTP</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Codice di verifica (6 cifre)"
                            hint="Per 2FA, conferma email, codici di invito."
                        >
                            <InputOTP v-model="demoOtp" :maxlength="6">
                                <template #default="{ slots }">
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="(slot, idx) in slots.slice(
                                                0,
                                                3,
                                            )"
                                            :key="idx"
                                            :index="idx"
                                        />
                                    </InputOTPGroup>
                                    <InputOTPSeparator />
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="(slot, idx) in slots.slice(
                                                3,
                                                6,
                                            )"
                                            :key="idx + 3"
                                            :index="idx + 3"
                                        />
                                    </InputOTPGroup>
                                </template>
                            </InputOTP>
                        </FormField>
                    </div>

                    <!-- 4.13 Calendar -->
                    <h3 class="kicker mb-3">4.13 Calendar</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Data emissione fattura"
                            hint="Calendario inline. Per overlay usa Date Picker (4.14)."
                        >
                            <Calendar
                                v-model="demoCalendar"
                                weekday-format="short"
                            />
                        </FormField>
                    </div>

                    <!-- 4.14 Date Picker — pattern Calendar + Popover -->
                    <h3 class="kicker mb-3">4.14 Date Picker</h3>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Data emissione"
                                hint="Pattern: Popover + Calendar. Niente primitive separato (shadcn convention)."
                            >
                                <Popover v-model:open="datePickerOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            type="button"
                                            class="w-full justify-start font-normal"
                                        >
                                            <span
                                                :class="
                                                    !demoDatePicker
                                                        ? 'text-muted-foreground/70'
                                                        : ''
                                                "
                                            >
                                                {{
                                                    formatDate(
                                                        demoDatePicker,
                                                    ) || 'Seleziona data…'
                                                }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-auto p-0"
                                        align="start"
                                    >
                                        <Calendar
                                            v-model="demoDatePicker"
                                            weekday-format="short"
                                            @update:model-value="
                                                datePickerOpen = false
                                            "
                                        />
                                    </PopoverContent>
                                </Popover>
                            </FormField>
                        </div>
                    </div>

                    <!-- 4.15 Range Calendar -->
                    <h3 class="kicker mb-3">4.15 Range Calendar</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <FormField
                            label="Periodo fatture"
                            hint="Click start → click end. Ottimo per filtri da/a, periodi fiscali."
                        >
                            <RangeCalendar
                                v-model="demoRange"
                                weekday-format="short"
                            />
                        </FormField>
                    </div>

                    <!-- 4.16 Kbd primitive -->
                    <h3 class="kicker mb-3">4.16 Kbd</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Capsula tasto come
                        <strong class="font-medium text-foreground"
                            >primitive shadcn</strong
                        >, non più solo utility CSS. Usa
                        <code class="kbd">&lt;Kbd&gt;</code> per scorciatoie e
                        shortcut.
                    </p>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3 text-13">
                            <span class="flex items-center gap-1">
                                <Kbd>⌘</Kbd>
                                <Kbd>K</Kbd>
                                <span class="ml-1 text-muted-foreground"
                                    >apre la palette</span
                                >
                            </span>
                            <span class="flex items-center gap-1">
                                <Kbd>esc</Kbd>
                                <span class="ml-1 text-muted-foreground"
                                    >chiude</span
                                >
                            </span>
                            <span class="flex items-center gap-1">
                                <Kbd>⇧</Kbd>
                                <Kbd>↵</Kbd>
                                <span class="ml-1 text-muted-foreground"
                                    >a capo</span
                                >
                            </span>
                        </div>
                    </div>

                    <!-- 4.17 Choice Card — wrapper compositi sopra Field + Radio/Check/Switch -->
                    <h3 class="kicker mb-3">4.17 Choice Card</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        3 wrapper compositi:
                        <code class="kbd">ChoiceCardRadio</code>,
                        <code class="kbd">ChoiceCardCheck</code>,
                        <code class="kbd">ChoiceCardSwitch</code>. Card
                        cliccabile intera con titolo + descrizione, stato
                        selected via <code class="kbd">has-[]</code> (bordo + bg
                        accent tonale).
                    </p>

                    <div class="mb-10 grid gap-4 md:grid-cols-3">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <Label class="mb-2" required
                                >Voce di spesa (radio)</Label
                            >
                            <RadioGroup
                                v-model="demoCardRadio"
                                class="grid gap-2"
                            >
                                <ChoiceCardRadio
                                    v-for="opt in projectTypes.slice(0, 3)"
                                    :key="opt.value"
                                    :value="opt.value"
                                    :title="opt.label"
                                    description="Spesa di questa categoria."
                                />
                            </RadioGroup>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <Label class="mb-2"
                                >Voci attive (check)</Label
                            >
                            <div class="grid gap-2">
                                <ChoiceCardCheck
                                    v-for="opt in projectTypes.slice(0, 3)"
                                    :key="opt.value"
                                    :model-value="
                                        demoCardChecks.includes(opt.value)
                                    "
                                    :title="opt.label"
                                    description="Abilita questa voce."
                                    @update:model-value="
                                        (v) => {
                                            if (v)
                                                demoCardChecks.push(opt.value);
                                            else
                                                demoCardChecks.splice(
                                                    demoCardChecks.indexOf(
                                                        opt.value,
                                                    ),
                                                    1,
                                                );
                                        }
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <Label class="mb-2">Notifiche (switch)</Label>
                            <div class="grid gap-2">
                                <ChoiceCardSwitch
                                    v-model="demoCardSwitches.notif"
                                    title="Notifiche scadenze"
                                    description="Avvisi sulle scadenze imminenti."
                                />
                                <ChoiceCardSwitch
                                    v-model="demoCardSwitches.auto"
                                    title="Pre-popola da template"
                                    description="Crea spese annuali dal catalogo."
                                />
                                <ChoiceCardSwitch
                                    v-model="demoCardSwitches.digest"
                                    title="Promemoria settimanale"
                                    description="Riepilogo scadenze lunedì."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 4.18 Choice Pill — wrapper compositi pillola compatta -->
                    <h3 class="kicker mb-3">4.18 Choice Pill</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        Versione compatta:
                        <code class="kbd">ChoicePillRadio</code>,
                        <code class="kbd">ChoicePillCheck</code>. Pillole
                        cliccabili come gruppo di scelta, stile accent tonale
                        quando selezionate.
                    </p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <Label class="mb-2" required
                                >Voce di spesa (radio)</Label
                            >
                            <RadioGroup
                                v-model="demoPillRadio"
                                class="flex flex-wrap gap-2"
                            >
                                <ChoicePillRadio
                                    v-for="opt in projectTypes"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </ChoicePillRadio>
                            </RadioGroup>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <Label class="mb-2"
                                >Voci attive (check)</Label
                            >
                            <div class="flex flex-wrap gap-2">
                                <ChoicePillCheck
                                    v-for="opt in projectTypes"
                                    :key="opt.value"
                                    :model-value="
                                        demoPillChecks.includes(opt.value)
                                    "
                                    @update:model-value="
                                        togglePillCheck(opt.value)
                                    "
                                >
                                    {{ opt.label }}
                                </ChoicePillCheck>
                            </div>
                        </div>
                    </div>

                    <!-- 4.19 Input Group — input con prefix/suffix -->
                    <h3 class="kicker mt-10 mb-3">4.19 Input Group</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        <code class="kbd">InputGroup</code> wrappa un
                        <code class="kbd">InputGroupInput</code> con
                        <code class="kbd">InputGroupAddon</code> in posizione
                        <code class="kbd">inline-start</code> /
                        <code class="kbd">inline-end</code> per icone, kbd hint,
                        unità di misura, bottoni inline (clear, copy). Mantiene
                        focus ring unico, niente bordi doppi.
                    </p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Cerca fatture"
                                hint="Icona prefix + kbd hint suffix."
                            >
                                <InputGroup>
                                    <InputGroupAddon>
                                        <PhMagnifyingGlass />
                                    </InputGroupAddon>
                                    <InputGroupInput
                                        v-model="demoSearch"
                                        placeholder="Numero, cliente, importo…"
                                    />
                                    <InputGroupAddon align="inline-end">
                                        <Kbd>⌘K</Kbd>
                                    </InputGroupAddon>
                                </InputGroup>
                            </FormField>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Importo previsto"
                                hint="Prefisso valuta."
                            >
                                <InputGroup>
                                    <InputGroupAddon>
                                        <PhCurrencyEur />
                                    </InputGroupAddon>
                                    <InputGroupInput
                                        v-model="demoBudget"
                                        type="number"
                                        placeholder="120.000"
                                        class="tabular"
                                    />
                                    <InputGroupAddon align="inline-end">
                                        <InputGroupText>EUR</InputGroupText>
                                    </InputGroupAddon>
                                </InputGroup>
                            </FormField>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField label="Email referente">
                                <InputGroup>
                                    <InputGroupAddon>
                                        <PhEnvelope />
                                    </InputGroupAddon>
                                    <InputGroupInput
                                        type="email"
                                        placeholder="referente@cliente.it"
                                    />
                                </InputGroup>
                            </FormField>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <FormField
                                label="Codice fattura"
                                hint="Bottone copy inline."
                            >
                                <InputGroup>
                                    <InputGroupInput
                                        default-value="FT 2026-04"
                                        readonly
                                        class="tabular font-mono"
                                    />
                                    <InputGroupAddon align="inline-end">
                                        <InputGroupButton
                                            size="icon-xs"
                                            @click="
                                                toast.success('Codice copiato.')
                                            "
                                        >
                                            <PhCheck />
                                        </InputGroupButton>
                                    </InputGroupAddon>
                                </InputGroup>
                            </FormField>
                        </div>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     05 Tabs (Phase B.3) — segmented + chapters
                     ───────────────────────────────────────────────────────── -->
                <section id="s-tabs" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            05 Tabs
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Controllo segmentato: pillole su
                        <code class="kbd">bg-muted</code>, voce attiva elevata
                        con <code class="kbd">bg-card</code> + leggera shadow.
                        Per switch tra viste in spazi contenuti (vista Anno,
                        dettaglio Spesa annuale, dettaglio Cliente). Stato
                        attivo = <strong class="font-medium text-foreground"
                            >ink</strong
                        >, non petrol: Tabs è navigazione tra viste, non
                        selezione di valore.
                    </p>

                    <!-- 5.1 Esempi live -->
                    <h3 class="kicker mb-3">5.1 Esempio</h3>

                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <Tabs v-model="demoTab" class="w-full">
                            <TabsList>
                                <TabsTrigger value="panoramica"
                                    >Riepilogo</TabsTrigger
                                >
                                <TabsTrigger value="attivita"
                                    >Spese</TabsTrigger
                                >
                                <TabsTrigger value="documenti"
                                    >Pagamenti</TabsTrigger
                                >
                                <TabsTrigger value="finanziario"
                                    >Fatture</TabsTrigger
                                >
                                <TabsTrigger value="contatti" disabled
                                    >Contributi</TabsTrigger
                                >
                            </TabsList>
                            <TabsContent
                                value="panoramica"
                                class="mt-4 text-13 text-muted-foreground"
                            >
                                Riepilogo dell'anno, KPI principali, contributi
                                pagati.
                            </TabsContent>
                            <TabsContent
                                value="attivita"
                                class="mt-4 text-13 text-muted-foreground"
                            >
                                Voci di spesa annuali con previsto, pagato, da
                                pagare.
                            </TabsContent>
                            <TabsContent
                                value="documenti"
                                class="mt-4 text-13 text-muted-foreground"
                            >
                                Pagamenti registrati, F24, avvisi Inarcassa.
                            </TabsContent>
                            <TabsContent
                                value="finanziario"
                                class="mt-4 text-13 text-muted-foreground"
                            >
                                Fatture dell'anno con cliente e ritenute.
                            </TabsContent>
                            <TabsContent
                                value="contatti"
                                class="mt-4 text-13 text-muted-foreground"
                            >
                                Contributi Inarcassa: Soggettivo, Integrativo,
                                Maternità.
                            </TabsContent>
                        </Tabs>
                    </div>

                    <!-- 5.2 Uso -->
                    <h3 class="kicker mb-3">5.2 Quando usarlo</h3>
                    <ul class="space-y-1.5 text-13 text-foreground/85">
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Show entity con sezioni</strong
                                >
                                — es. dettaglio Anno: Riepilogo / Spese /
                                Pagamenti.
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Settings con aree</strong
                                >
                                — es. Profilo professionale, Voci di spesa,
                                Scadenze tipo in tab.
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Switch di vista</strong
                                >
                                su uno stesso dataset (Lista vs Griglia → ma per
                                quello considera il toggle
                                <code class="kbd">#page-topbar-views</code>).
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     06 Dialog (Phase B.3) — 4 sizes, niente Sheet
                     ───────────────────────────────────────────────────────── -->
                <section id="s-dialog" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            06 Dialog
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Modale centrato, blocca, focus totale. 4 size via prop
                        <code class="kbd">size</code> ma
                        <strong class="font-medium text-foreground"
                            >tipografia, padding e border sono uguali per
                            tutti</strong
                        >: niente regole speciali per il mini, niente per il
                        fullscreen. Una grammatica unica.
                    </p>

                    <!-- 6.1 Anatomia & regole canoniche -->
                    <h3 class="kicker mb-3">6.1 Anatomia &amp; regole</h3>
                    <div class="mb-6 grid gap-4 md:grid-cols-2">
                        <!-- Diagramma anatomia -->
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">Composizione canonica</p>
                            <div
                                class="border-border-soft overflow-hidden rounded border bg-card"
                            >
                                <!-- Header: title (left), trailing? + close (right) -->
                                <div
                                    class="border-border-soft border-b px-5 py-4"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-base font-semibold tracking-tight text-foreground"
                                            >
                                                Titolo dialog
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded border border-dashed border-border px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground/70 italic"
                                            >
                                                #trailing?
                                            </span>
                                            <PhX
                                                class="size-3.5 text-muted-foreground"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        class="mt-1.5 text-2xs text-muted-foreground"
                                    >
                                        Descrizione opzionale (text-xs).
                                    </div>
                                </div>
                                <!-- Body -->
                                <div class="px-5 py-4">
                                    <div
                                        class="rounded bg-muted/60 px-3 py-2 text-2xs text-muted-foreground/70"
                                    >
                                        Body — solo contenuto, niente intro
                                    </div>
                                </div>
                                <!-- Footer (bg-muted/50 + border-t) -->
                                <div
                                    class="border-border-soft flex justify-end gap-2 border-t bg-muted/50 px-5 py-3"
                                >
                                    <span
                                        class="rounded bg-transparent px-2 py-0.5 text-2xs text-muted-foreground/70"
                                    >
                                        ghost
                                    </span>
                                    <span
                                        class="rounded bg-foreground px-2 py-0.5 text-2xs text-background"
                                    >
                                        primary
                                    </span>
                                </div>
                            </div>
                            <p
                                class="mt-3 text-2xs leading-snug text-muted-foreground/80"
                            >
                                La close X vive
                                <strong class="font-medium text-foreground"
                                    >dentro il header</strong
                                >, allineata
                                <code class="font-mono">items-center</code> con
                                la riga del title. Niente
                                <code class="font-mono">absolute</code>:
                                trailing slot e close condividono lo stesso
                                flex.
                            </p>
                        </div>

                        <!-- Regole tipografia + spacing -->
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">Regole canoniche</p>
                            <ul
                                class="space-y-2 text-2xs text-muted-foreground"
                            >
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Title</strong
                                        >
                                        — Switzer 16 / 600 / ink (strappo locale
                                        al medium 500 del DS)</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-base</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Description</strong
                                        >
                                        — Switzer 12 / 400 / muted</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-xs</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Body</strong
                                        >
                                        — Switzer 13 / 400 / ink</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-13</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Header / Body</strong
                                        >
                                        — padding</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >px-6 py-5</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Footer</strong
                                        >
                                        — padding + bg</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >px-6 py-4 · bg-muted/50</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Sezioni</strong
                                        >
                                        — divisori</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >border-border-soft</code
                                    >
                                </li>
                            </ul>
                            <p
                                class="mt-3 text-2xs leading-snug text-muted-foreground/80"
                            >
                                Stessa griglia per tutti i size (mini →
                                fullscreen). Niente eccezioni. Quando il body è
                                assente, il
                                <code class="font-mono">border-b</code>
                                dell'header collassa auto via
                                <code class="font-mono">:has()</code>.
                            </p>
                        </div>
                    </div>

                    <!-- 6.2 Componenti compositi — la via consigliata -->
                    <h3 class="kicker mb-3">
                        6.2 Componenti compositi (consigliati)
                    </h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        3 wrapper sopra le primitive shadcn forzano il pattern
                        canonico ed eliminano il boilerplate.
                        <strong class="font-medium text-foreground"
                            >Usa questi nelle pagine reali</strong
                        >; le primitive raw restano disponibili per casi edge.
                    </p>
                    <div
                        class="mb-6 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <span>Componente</span>
                            <span>Props / Slot</span>
                            <span>Cosa fa</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DialogStandardHeader</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >code, title, description?, #trailing</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Header con code-pill (obbligatorio) + title
                                (obbligatorio) su una riga. Description
                                (opzionale) sotto. Slot
                                <code class="font-mono">#trailing</code> per
                                content a destra del title (es. WizardStepper).
                                Close X renderizzata in-header automaticamente:
                                passa
                                <code class="font-mono"
                                    >:show-close-button="false"</code
                                >
                                a
                                <code class="font-mono">DialogContent</code> per
                                evitare il doppio.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DialogBody</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >slot</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Wrapper col padding canonico
                                <code class="font-mono">px-6 py-5</code>
                                (allineato al chrome).
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DialogStandardFooter</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >info?, cancelLabel?, slot</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Annulla ghost auto-DialogClose + slot primary.
                                Prop opzionale
                                <code class="font-mono">info</code> per "Passo 1
                                di 6" stile wizard.
                            </span>
                        </div>
                    </div>
                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Dialog&gt;
  &lt;DialogTrigger as-child&gt;
    &lt;Button&gt;Apri&lt;/Button&gt;
  &lt;/DialogTrigger&gt;
  &lt;DialogContent size="default" :show-close-button="false"&gt;
    &lt;DialogStandardHeader
      title="Nuova voce di spesa"
      description="Crea una voce di spesa riusabile dal template."
    /&gt;
    &lt;DialogBody&gt;
      &lt;FormField label="Nome voce" required&gt;…&lt;/FormField&gt;
    &lt;/DialogBody&gt;
    &lt;DialogStandardFooter&gt;
      &lt;Button&gt;Crea voce&lt;/Button&gt;
    &lt;/DialogStandardFooter&gt;
  &lt;/DialogContent&gt;
&lt;/Dialog&gt;</code></pre>

                    <!-- 6.3 Esempi live: 4 sizes che mostrano combinazioni diverse -->
                    <h3 class="kicker mb-3">6.3 Sizes</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Mini — conferma rapida -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline"
                                        >Mini (~460)</Button
                                    >
                                </DialogTrigger>
                                <DialogContent
                                    size="mini"
                                    :show-close-button="false"
                                >
                                    <DialogStandardHeader
                                        title="Eliminare il cliente?"
                                        description="Tutte le fatture associate verranno mantenute, ma il cliente non sarà più visibile in elenco."
                                    />
                                    <DialogStandardFooter>
                                        <Button variant="destructive"
                                            >Elimina</Button
                                        >
                                    </DialogStandardFooter>
                                </DialogContent>
                            </Dialog>

                            <!-- Default — form rapido -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline"
                                        >Default (~580)</Button
                                    >
                                </DialogTrigger>
                                <DialogContent :show-close-button="false">
                                    <DialogStandardHeader
                                        title="Nuova voce di spesa"
                                        description="Crea una voce di spesa riusabile dal template."
                                    />
                                    <DialogBody>
                                        <FieldGroup>
                                            <FormField
                                                label="Nome voce"
                                                required
                                            >
                                                <Input
                                                    id="ds-dialog-name"
                                                    placeholder="Es. Imposta sostitutiva"
                                                />
                                            </FormField>
                                            <FormField label="Descrizione">
                                                <Textarea
                                                    id="ds-dialog-desc"
                                                    rows="3"
                                                />
                                            </FormField>
                                        </FieldGroup>
                                    </DialogBody>
                                    <DialogStandardFooter>
                                        <Button variant="default"
                                            >Crea voce</Button
                                        >
                                    </DialogStandardFooter>
                                </DialogContent>
                            </Dialog>

                            <!-- Wide — modifica entità -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline">Wide</Button>
                                </DialogTrigger>
                                <DialogContent
                                    size="wide"
                                    :show-close-button="false"
                                >
                                    <DialogStandardHeader
                                        title="Bianchi Studio srl"
                                        description="Aggiorna anagrafica, P.IVA/CF e flag ritenute. Le modifiche si applicano a tutte le fatture collegate."
                                    />
                                    <DialogBody>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="md:col-span-2">
                                                <Label class="mb-2" required
                                                    >Denominazione</Label
                                                >
                                                <Input
                                                    default-value="Bianchi Studio srl"
                                                />
                                            </div>
                                            <div>
                                                <Label class="mb-2"
                                                    >P.IVA</Label
                                                >
                                                <Input
                                                    class="tabular"
                                                    default-value="01234567890"
                                                />
                                            </div>
                                            <div>
                                                <Label class="mb-2"
                                                    >Codice Fiscale</Label
                                                >
                                                <Input class="tabular" />
                                            </div>
                                            <div class="md:col-span-2">
                                                <Label class="mb-2">Note</Label>
                                                <Textarea rows="3" />
                                            </div>
                                        </div>
                                    </DialogBody>
                                    <DialogStandardFooter>
                                        <Button variant="default">Salva</Button>
                                    </DialogStandardFooter>
                                </DialogContent>
                            </Dialog>

                            <!-- Fullscreen — viewer / editor denso (per wizard vedi sezione 12) -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline"
                                        >Fullscreen</Button
                                    >
                                </DialogTrigger>
                                <DialogContent
                                    size="fullscreen"
                                    :show-close-button="false"
                                >
                                    <DialogStandardHeader
                                        title="Vista anno · 2026"
                                        description="Viewer denso — spazio massimo per il content, chrome ridotto al minimo."
                                    />
                                    <DialogBody class="flex-1 overflow-auto">
                                        <p
                                            class="text-13 text-muted-foreground"
                                        >
                                            Contenuto fullscreen — vista anno
                                            aggregato, editor di calcoli, dossier
                                            dettaglio. Per i wizard multi-step
                                            (apertura anno, onboarding) c'è il
                                            pattern dedicato in
                                            <a href="#s-modal" class="underline"
                                                >sezione 12</a
                                            >.
                                        </p>
                                    </DialogBody>
                                    <DialogStandardFooter>
                                        <Button variant="default"
                                            >Chiudi</Button
                                        >
                                    </DialogStandardFooter>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>

                    <!-- 6.4 Tabella sizes -->
                    <h3 class="kicker mb-3">6.4 Quando usare quale size</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 130px 100px 1fr"
                        >
                            <span>Size</span>
                            <span>Larghezza</span>
                            <span>Caso d'uso</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 100px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >mini</code
                            >
                            <span
                                class="tabular font-mono text-2xs text-muted-foreground"
                                >~460</span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Conferme distruttive, alert, scelte rapide
                                (Sì/No).
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 100px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >default</code
                            >
                            <span
                                class="tabular font-mono text-2xs text-muted-foreground"
                                >~580</span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Form rapidi (edit cliente, nuova voce di
                                spesa, conferma pagamento).
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 100px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >wide</code
                            >
                            <span
                                class="tabular font-mono text-2xs text-muted-foreground"
                                >~760</span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Form complessi (apri anno, edit fattura denso,
                                form a 2 colonne).
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 100px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >fullscreen</code
                            >
                            <span
                                class="tabular font-mono text-2xs text-muted-foreground"
                                >100vw</span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Wizard multi-step, editor con preview, viewer
                                dense.
                            </span>
                        </div>
                    </div>

                    <!-- 6.5 Quando usare code-pill -->
                    <h3 class="kicker mb-3">6.5 Quando usare code-pill</h3>
                    <div class="mb-10 rounded-md border border-border bg-card p-5">
                        <p
                            class="mb-3 max-w-2xl text-13 leading-relaxed text-muted-foreground"
                        >
                            Il
                            <code class="kbd">.code-pill</code> è riservato a
                            <strong class="font-medium text-foreground"
                                >token user-facing</strong
                            >
                            che l'utente già riconosce e cita: numero fattura,
                            codice F24, codice tributo, coefficiente
                            forfettario. Niente system-language interna (no
                            <code class="kbd">M.CRE</code> / <code class="kbd">M.EDT</code>),
                            niente identificatori di sezione modale.
                        </p>
                        <ul class="space-y-1.5 text-13 text-foreground/85">
                            <li class="flex items-start gap-2">
                                <span
                                    class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                />
                                <span>
                                    <strong
                                        class="font-medium text-foreground"
                                        >Sì</strong
                                    >
                                    —
                                    <code class="font-mono">FT 2026-04</code>,
                                    <code class="font-mono">F24-06-2026</code>,
                                    <code class="font-mono">CF · 78%</code>.
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <PhX
                                    class="mt-1 size-3 shrink-0 text-destructive"
                                />
                                <span>
                                    <strong
                                        class="font-medium text-foreground"
                                        >No</strong
                                    >
                                    — taxonomie interne dei modali. Se serve
                                    un identificatore per analytics/debug,
                                    usa
                                    <code class="kbd">data-modal-code</code>
                                    sull'elemento, invisibile all'utente.
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- 6.6 Perché niente Sheet -->
                    <div class="rounded-md border border-border bg-card p-5">
                        <h4 class="text-sm font-medium text-foreground">
                            Perché niente Sheet?
                        </h4>
                        <p
                            class="mt-2 max-w-2xl text-13 leading-relaxed text-muted-foreground"
                        >
                            Avere Sheet + Dialog crea un decision-point ambiguo:
                            "questo edit lo apro come Sheet o come Dialog?". Per
                            Qui la separazione è netta:
                        </p>
                        <ul class="mt-3 space-y-1.5 text-13 text-foreground/85">
                            <li class="flex items-start gap-2">
                                <span
                                    class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                />
                                <span>
                                    Azioni
                                    <strong class="font-medium text-foreground"
                                        >focalizzate</strong
                                    >
                                    (l'utente deve completare prima di tornare)
                                    → Dialog
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span
                                    class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                />
                                <span>
                                    Casi
                                    <strong class="font-medium text-foreground"
                                        >keep-context</strong
                                    >
                                    (vedere ancora la lista/canvas sotto) →
                                    pannelli push inline: FilterSidebar a
                                    destra, RightDetailPanel — vivono nel
                                    layout, non come overlay
                                </span>
                            </li>
                        </ul>
                        <p
                            class="mt-3 text-2xs leading-snug text-muted-foreground/80"
                        >
                            La primitive <code class="kbd">Sheet</code> resta
                            installata come dipendenza interna shadcn-vue, ma
                            non è documentata né da usare in nuovo codice.
                        </p>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     07 Tables (Phase B.4)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-tables" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            07 Tables
                        </h2>
                        <span class="font-mono text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Tabelle dense desktop-first. HTML
                        <code class="kbd">&lt;table&gt;</code> semantico via
                        primitive shadcn. Density compact di default (<code
                            class="kbd"
                            >h-9</code
                        >
                        riga), header
                        <code class="kbd">.kicker-style</code> uppercase muted.
                    </p>

                    <!-- 7.1 Anatomia & regole -->
                    <h3 class="kicker mb-3">7.1 Anatomia &amp; regole</h3>
                    <div class="mb-6 grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">Tipografia</p>
                            <ul
                                class="space-y-2 text-2xs text-muted-foreground"
                            >
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Header</strong
                                        >
                                        — Switzer 11px / 500 / muted / uppercase
                                        tracking-wider</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-2xs</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Colonna principale</strong
                                        >
                                        — Switzer 13px / 500 / ink (es.
                                        "Progetto")</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-13</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Colonne secondarie</strong
                                        >
                                        — Switzer 12px / 400 / muted (cliente,
                                        tipologia, importo…)</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >text-xs</code
                                    >
                                </li>
                                <li
                                    class="flex items-baseline justify-between gap-3"
                                >
                                    <span
                                        ><strong
                                            class="font-medium text-foreground"
                                            >Numeri / codici</strong
                                        >
                                        —
                                        <code class="font-mono">.tabular</code>
                                        per allineamento decimale</span
                                    >
                                    <code class="font-mono text-2xs"
                                        >.tabular</code
                                    >
                                </li>
                            </ul>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">Layout — boxed</p>
                            <p
                                class="mb-2 text-2xs leading-snug text-muted-foreground/85"
                            >
                                Tabella dentro un box con border + radius. Il
                                thead resta sticky in alto, le righe scrollano
                                DENTRO il box. La pagination vive sotto, fuori
                                dal box.
                            </p>
                            <ul
                                class="space-y-2 text-2xs text-muted-foreground"
                            >
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Container</strong
                                    >
                                    —
                                    <code class="font-mono"
                                        >border rounded-md overflow-hidden
                                        bg-card</code
                                    >
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Thead</strong
                                    >
                                    —
                                    <code class="font-mono"
                                        >sticky top-0 z-10 bg-muted/95</code
                                    >
                                    (resta in alto durante lo scroll del body)
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Scroll</strong
                                    >
                                    — area interna con
                                    <code class="font-mono">overflow-auto</code>
                                    e <code class="font-mono">max-h</code>
                                    configurabile (prop
                                    <code class="font-mono">maxHeight</code>)
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Pagination</strong
                                    >
                                    — fuori dal box, sotto, separata
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Density</strong
                                    >
                                    — riga <code class="font-mono">h-9</code>,
                                    cell
                                    <code class="font-mono">px-3 py-2</code>
                                </li>
                            </ul>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">Pattern boxato</p>
                            <p
                                class="mb-2 text-2xs leading-snug text-muted-foreground/85"
                            >
                                Tabella semplice: nessuno scroll verticale
                                interno. Le righe crescono naturalmente sotto.
                                Lo scroll orizzontale (su tabelle larghe)
                                avviene dentro il box. Le sticky columns sono
                                opt-in solo dove servono (verranno aggiunte se
                                serve scroll verticale o tabelle wide).
                            </p>
                            <ul
                                class="space-y-2 text-2xs text-muted-foreground"
                            >
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Esempio tipico</strong
                                    >
                                    — lista fatture, lista pagamenti, lista
                                    scadenze di un anno.
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Min-width</strong
                                    >
                                    — passa <code class="font-mono">min-w-[…]</code>
                                    al DataTable per attivare scroll-x sotto
                                    quella soglia.
                                </li>
                            </ul>
                        </div>

                        <div
                            class="rounded-md border border-border bg-card p-5"
                        >
                            <p class="kicker mb-3">
                                Stati riga &amp; tipi cella
                            </p>
                            <ul
                                class="space-y-2 text-2xs text-muted-foreground"
                            >
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Hover</strong
                                    >
                                    —
                                    <code class="font-mono">bg-background</code>
                                    (canvas zinc-50, più morbido del bg-card)
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Selected</strong
                                    >
                                    —
                                    <code class="font-mono"
                                        >bg-accent-vivid/10</code
                                    >
                                    (accent tonale)
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Codice</strong
                                    >
                                    — <code class="font-mono">.code-pill</code>
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Numero</strong
                                    >
                                    —
                                    <code class="font-mono"
                                        >.tabular text-right</code
                                    >
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Stato</strong
                                    >
                                    —
                                    <code class="font-mono"
                                        >.pill</code
                                    >
                                </li>
                                <li>
                                    <strong class="font-medium text-foreground"
                                        >Azione</strong
                                    >
                                    — Button ghost icon-sm
                                    <code class="font-mono"
                                        >PhDotsThreeVertical</code
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 7.2 Esempio live: tabella fatture boxata -->
                    <h3 class="kicker mb-3">7.2 Esempio — lista fatture</h3>
                    <p class="mb-3 max-w-2xl text-2xs text-muted-foreground/85">
                        Tabella dentro un box con border + radius. Header
                        dentro al box (bg-muted), body sotto con divisori tra
                        le righe. La pagination vive fuori dal box.
                    </p>

                    <DataTable class="min-w-[820px]">
                        <colgroup>
                            <col style="width: 36px" />
                            <col style="width: 120px" />
                            <col />
                            <col style="width: 110px" />
                            <col style="width: 130px" />
                            <col style="width: 140px" />
                            <col style="width: 40px" />
                        </colgroup>

                        <DataTableHeader
                            :all-selected="demoAllSelected"
                            @toggle-all="toggleAll"
                        >
                            <TableHead>Codice</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Data</TableHead>
                            <TableHead>Stato</TableHead>
                            <TableHead class="text-right">Importo</TableHead>
                        </DataTableHeader>

                        <DataTableBody>
                            <DataTableRow
                                v-for="p in demoProjects"
                                :key="p.code"
                                :selected="demoSelected.includes(p.code)"
                                @toggle-select="toggleRow(p.code)"
                            >
                                <TableCell>
                                    <span class="code-pill">{{ p.code }}</span>
                                </TableCell>
                                <TableCell class="text-13 text-foreground">
                                    {{ p.client }}
                                </TableCell>
                                <TableCell
                                    class="tabular font-mono text-xs text-muted-foreground"
                                >
                                    {{ p.date }}
                                </TableCell>
                                <TableCell>
                                    <span :class="`pill pill--${p.state}`">
                                        {{ p.stateLabel }}
                                    </span>
                                </TableCell>
                                <TableCell
                                    class="numeric text-right text-13 text-foreground"
                                >
                                    {{ fmtEur(p.importo) }}
                                </TableCell>
                                <template #actions>
                                    <DropdownMenuItem
                                        >Vai al dettaglio</DropdownMenuItem
                                    >
                                    <DropdownMenuItem
                                        >Duplica</DropdownMenuItem
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-destructive">
                                        Archivia
                                    </DropdownMenuItem>
                                </template>
                            </DataTableRow>
                        </DataTableBody>
                    </DataTable>

                    <!-- Pagination FUORI dal box -->
                    <DataTablePagination
                        class="mb-10"
                        :page="demoPage"
                        :per-page="demoPerPage"
                        :total="68"
                        @update:page="(v) => (demoPage = v)"
                    />

                    <!-- 7.3 Empty state -->
                    <h3 class="kicker mb-3">7.3 Empty state</h3>
                    <div class="mb-6">
                        <Table boxed>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Codice</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Data</TableHead>
                                    <TableHead>Stato</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty :colspan="4">
                                    <div
                                        class="flex flex-col items-center gap-3 text-center"
                                    >
                                        <PhReceipt
                                            class="size-8 text-muted-foreground/40"
                                        />
                                        <div>
                                            <p
                                                class="text-13 font-medium text-foreground"
                                            >
                                                Nessuna fattura
                                            </p>
                                            <p
                                                class="mt-1 text-2xs text-muted-foreground"
                                            >
                                                Importa via XML o crea una
                                                fattura manuale per iniziare.
                                            </p>
                                        </div>
                                        <Button variant="default" size="sm">
                                            <PhPlus />
                                            Nuova fattura
                                        </Button>
                                    </div>
                                </TableEmpty>
                            </TableBody>
                        </Table>
                    </div>
                    <p class="mb-10 text-2xs text-muted-foreground/85">
                        <strong class="font-medium text-foreground"
                            >Due varianti:</strong
                        >
                        nessun dato (con CTA crea) e nessun risultato per filtri
                        (con CTA azzera filtri). Il primitive
                        <code class="kbd">&lt;TableEmpty&gt;</code> gestisce
                        automaticamente il <code class="kbd">colspan</code>.
                    </p>

                    <!-- 7.4 Componenti -->
                    <h3 class="kicker mb-3">
                        7.4 Componenti compositi (consigliati)
                    </h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        4 wrapper sopra le primitive shadcn forzano il pattern
                        canonico ed eliminano boilerplate.
                        <strong class="font-medium text-foreground"
                            >Usa questi nelle pagine reali</strong
                        >; le primitive raw restano disponibili per casi edge.
                    </p>
                    <div
                        class="mb-6 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <span>Componente</span>
                            <span>Props / Slot</span>
                            <span>Cosa fa</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DataTable</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >slot</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Wrapper Table base con
                                <code class="font-mono"
                                    >table-fixed border-separate</code
                                >
                                pre-applicati. Slot per colgroup +
                                DataTableHeader + DataTableBody.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DataTableHeader</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >allSelected, selectable?, hasActions?,
                                slot</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Header riga con auto-checkbox col 0 (select-all)
                                + auto-cell vuota in fondo per actions. Slot:
                                TableHead intermedi. Emit
                                <code class="font-mono">toggle-all</code>.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DataTableBody</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >slot</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                TableBody con la "monster-class" del card-look
                                pre-applicata (bordi perimetrali via celle,
                                radius su 4 angoli, hover/selected via td).
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DataTableRow</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >selected, selectable?, slot, slot#actions</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Riga con auto-checkbox col 0 + auto-DropdownMenu
                                kebab in fondo (se slot
                                <code class="font-mono">#actions</code>
                                presente). Slot: TableCell intermedi. Emit
                                <code class="font-mono">toggle-select</code>.
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 240px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >DataTablePagination</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >page, perPage, total</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Strip pagination canonica con numeri pagina
                                cliccabili. Emit
                                <code class="font-mono">update:page</code> per
                                controllo lato server. Niente selector per
                                pagina (valore fisso).
                            </span>
                        </div>
                    </div>

                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;DataTable class="min-w-[820px]"&gt;
  &lt;colgroup&gt;
    &lt;col style="width: 36px;"&gt;    &lt;!-- checkbox --&gt;
    &lt;col style="width: 120px;"&gt;   &lt;!-- codice --&gt;
    &lt;col&gt;                         &lt;!-- cliente flex --&gt;
    ...
    &lt;col style="width: 40px;"&gt;    &lt;!-- actions --&gt;
  &lt;/colgroup&gt;

  &lt;DataTableHeader :all-selected="…" @toggle-all="…"&gt;
    &lt;TableHead&gt;Codice&lt;/TableHead&gt;
    &lt;TableHead&gt;Cliente&lt;/TableHead&gt;
    ...
  &lt;/DataTableHeader&gt;

  &lt;DataTableBody&gt;
    &lt;DataTableRow v-for="p in items" :selected="…" @toggle-select="…"&gt;
      &lt;TableCell&gt;
        &lt;span class="code-pill"&gt;&#123;&#123; p.code &#125;&#125;&lt;/span&gt;
      &lt;/TableCell&gt;
      ...
      &lt;template #actions&gt;
        &lt;DropdownMenuItem&gt;Vai al dettaglio&lt;/DropdownMenuItem&gt;
        &lt;DropdownMenuItem class="text-destructive"&gt;Archivia&lt;/DropdownMenuItem&gt;
      &lt;/template&gt;
    &lt;/DataTableRow&gt;
  &lt;/DataTableBody&gt;
&lt;/DataTable&gt;

&lt;!-- Pagination FUORI dal box --&gt;
&lt;DataTablePagination :page="…" :per-page="…" :total="…" @update:page="…" /&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     08 Topbar
                     ───────────────────────────────────────────────────────── -->
                <section id="s-topbar" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            08 Topbar
                        </h2>
                        <span class="text-2xs text-muted-foreground"
                            >v0</span
                        >
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        2 fasce orizzontali sopra ogni pagina. Top sempre
                        visibile (breadcrumb + status + azioni primarie),
                        bottom condizionale (search testuale, filtri, viste).
                        I 4 mount-point Teleport sono sempre nel DOM, le
                        pagine ci proiettano dentro il loro content.
                    </p>

                    <!-- 8.1 Anatomia -->
                    <h3 class="kicker mb-3">8.1 Anatomia</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border bg-card"
                    >
                        <!-- Diagramma fascia top -->
                        <div
                            class="border-border-soft flex h-12 items-center gap-3 border-b bg-background px-4"
                        >
                            <div
                                class="flex min-w-0 flex-1 items-center gap-1.5"
                            >
                                <span class="text-xs text-muted-foreground"
                                    >StudioFIN</span
                                >
                                <PhCaretRight
                                    :size="10"
                                    weight="bold"
                                    class="text-muted-foreground/40"
                                />
                                <span
                                    class="text-13 font-medium tracking-wide uppercase text-foreground"
                                    >Pagina</span
                                >
                                <span class="pill pill--success ml-2"
                                    >Attiva</span
                                >
                            </div>
                            <div
                                class="border-border-soft rounded border border-dashed bg-muted/40 px-2 py-1 font-mono text-2xs text-muted-foreground/85"
                            >
                                #page-topbar-actions
                            </div>
                        </div>

                        <!-- Diagramma fascia bottom (subbar) -->
                        <div
                            class="border-border-soft flex h-11 items-center gap-3 border-b px-4"
                        >
                            <div
                                class="border-border-soft rounded border border-dashed bg-muted/40 px-2 py-1 font-mono text-2xs text-muted-foreground/85"
                            >
                                #page-topbar-search
                            </div>
                            <div
                                class="border-border-soft rounded border border-dashed bg-muted/40 px-2 py-1 font-mono text-2xs text-muted-foreground/85"
                            >
                                #page-topbar-filters
                            </div>
                            <div
                                class="border-border-soft rounded border border-dashed bg-muted/40 px-2 py-1 font-mono text-2xs text-muted-foreground/85"
                            >
                                #page-topbar-views
                            </div>
                            <div class="flex-1" />
                        </div>

                        <!-- Etichette altezze -->
                        <div
                            class="flex items-center justify-between bg-muted/30 px-4 py-2 text-2xs text-muted-foreground"
                        >
                            <div>
                                <strong class="font-medium text-foreground"
                                    >Top</strong
                                >
                                — h-12, sempre visibile (breadcrumb · status ·
                                azioni)
                            </div>
                            <div>
                                <strong class="font-medium text-foreground"
                                    >Bottom</strong
                                >
                                — h-11,
                                <code class="font-mono">v-show="subbar"</code>
                            </div>
                        </div>
                    </div>

                    <!-- 8.2 setLayoutProps API -->
                    <h3 class="kicker mb-3">8.2 setLayoutProps API</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        La pagina dichiara cosa mostrare in topbar tramite
                        <code class="kbd">setLayoutProps</code> di Inertia v3.
                        Le prop viaggiano fino al layout via injection.
                    </p>
                    <div
                        class="mb-6 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 130px 180px 1fr"
                        >
                            <span>Prop</span>
                            <span>Tipo</span>
                            <span>Cosa fa</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 180px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >pageTitle</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >string?</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Ultimo nodo del breadcrumb se non passi
                                <code class="font-mono">crumbs</code>. Se
                                assente, mostra solo il nome studio.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 180px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >pageCrumbs</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >{label, href?}[]</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Catena di breadcrumb dopo il nome studio
                                (auto-prepended). Item con
                                <code class="font-mono">href</code> = link,
                                senza = current.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 180px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >pageStatus</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >{label, tone?}?</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Pill stato a fianco breadcrumb (es. "In corso",
                                "Bozza"). Tone: positive / negative / neutral /
                                warning.
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 130px 180px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >subbar</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >boolean</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Mostra fascia bottom (default false). Attiva
                                quando la pagina ha filtri / viste / azioni da
                                proiettare nei mount-point.
                            </span>
                        </div>
                    </div>

                    <!-- 8.3 Status tone reference -->
                    <h3 class="kicker mb-3">8.3 Status tone</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 110px 130px 1fr"
                        >
                            <span>Tone</span>
                            <span>Demo</span>
                            <span>Quando usarlo</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 130px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >positive</code
                            >
                            <span
                                ><span
                                    class="pill pill--success"
                                    >In corso</span
                                ></span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Stato attivo / approvato / ok.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 130px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >warning</code
                            >
                            <span
                                ><span
                                    class="pill pill--warning"
                                    >Sospeso</span
                                ></span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Stato in pausa / scadenza imminente /
                                attenzione.
                            </span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 130px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >negative</code
                            >
                            <span
                                ><span
                                    class="pill pill--danger"
                                    >Bloccato</span
                                ></span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Errore / sospensione critica / KO.
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 130px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >neutral</code
                            >
                            <span
                                ><span
                                    class="pill pill--neutral"
                                    >Bozza</span
                                ></span
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Stato non conclusivo / archivio / chiuso.
                            </span>
                        </div>
                    </div>

                    <!-- 8.4 Mount-point Teleport — esempio canonico -->
                    <h3 class="kicker mb-3">8.4 Mount-point Teleport</h3>
                    <p class="mb-4 max-w-2xl text-13 text-muted-foreground">
                        I 4 mount-point sono sempre presenti nel DOM (anche con
                        <code class="kbd">subbar: false</code> — ma
                        <code class="kbd">display:none</code>). Le pagine usano
                        <code class="kbd">&lt;Teleport :defer&gt;</code>
                        per proiettare contenuti dentro la subbar senza ricreare
                        la struttura ad ogni navigazione.
                    </p>

                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>// Pagina (es. clients/Index.vue)
import { setLayoutProps } from '@inertiajs/vue3'

setLayoutProps(&#123;
  pageTitle: 'Clienti',
  pageCrumbs: [&#123; label: 'Clienti' &#125;],
  pageStatus: &#123; label: 'In corso', tone: 'positive' &#125;,
  subbar: true,
&#125;)

// Template
&lt;template&gt;
  &lt;Teleport to="#page-topbar-search" defer&gt;
    &lt;Input v-model="search" placeholder="Cerca cliente…" /&gt;
  &lt;/Teleport&gt;

  &lt;Teleport to="#page-topbar-filters" defer&gt;
    &lt;Button variant="outline" @click="filtersOpen = !filtersOpen"&gt;
      &lt;PhFunnel /&gt; Filtri
    &lt;/Button&gt;
  &lt;/Teleport&gt;

  &lt;Teleport to="#page-topbar-actions" defer&gt;
    &lt;Button&gt;&lt;PhPlus /&gt; Nuovo cliente&lt;/Button&gt;
  &lt;/Teleport&gt;

  &lt;!-- Resto della pagina --&gt;
&lt;/template&gt;</code></pre>

                    <!-- 8.5 Quando attivare subbar -->
                    <h3 class="kicker mb-3">8.5 Quando attivare subbar</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-2">Sì</p>
                            <ul class="space-y-1.5 text-13 text-foreground/85">
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                    />
                                    <span
                                        >Pagine Index con search, filtri o
                                        azione "Nuovo X"</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                    />
                                    <span
                                        >Show con toggle vista
                                        (Lista/Griglia/Timeline)</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                    />
                                    <span
                                        >Form con bottoni Salva sticky in topbar
                                        (alternativa al footbar)</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-4"
                        >
                            <p class="kicker mb-2">No</p>
                            <ul
                                class="space-y-1.5 text-13 text-muted-foreground"
                            >
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-1 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Dashboard / Home (niente filtri, niente
                                        azioni primarie globali)</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-1 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Pagine settings semplici (Profile,
                                        Studio info)</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <PhX
                                        class="mt-1 size-3 shrink-0 text-destructive"
                                    />
                                    <span
                                        >Per riempire vuoto — meglio pagina
                                        respiri</span
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     09 Sidebar nav
                     ───────────────────────────────────────────────────────── -->
                <section id="s-sidebar" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            09 Sidebar nav
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Sidebar collapsible (expanded 13rem / rail 3rem).
                        Header con logo, due sezioni — "Lavoro" e "Sistema" —
                        con kicker label uppercase muted, voci flat con icona
                        Phosphor (regular default, fill quando attiva o on
                        hover), barretta accent w-[2px] come indicatore.
                        Footer con avatar utente + dropdown menu. Bottone
                        collapse pillola a cavallo del bordo destro.
                    </p>

                    <!-- 9.1 Anatomia: expanded vs rail -->
                    <h3 class="kicker mb-3">9.1 Anatomia — expanded vs rail</h3>
                    <div class="mb-10 grid gap-4 md:grid-cols-2">
                        <!-- Mock sidebar expanded -->
                        <div
                            class="rounded-md border border-border bg-card p-3"
                        >
                            <p class="kicker mb-2 px-1">Expanded — 13rem</p>
                            <div
                                class="relative flex w-[208px] flex-col gap-3 rounded border border-sidebar-border bg-sidebar py-2"
                            >
                                <div class="h-14 px-3 pt-1">
                                    <div
                                        class="flex h-7 items-center px-2 text-13 font-semibold text-foreground"
                                    >
                                        StudioFIN
                                    </div>
                                </div>
                                <div class="px-0">
                                    <div class="kicker px-4">Lavoro</div>
                                    <div class="mt-1 flex flex-col gap-0.5">
                                        <div
                                            class="relative flex items-center gap-2 px-4 py-1.75 text-13 font-medium text-foreground"
                                        >
                                            <span
                                                class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-accent-vivid"
                                            />
                                            <PhHouse
                                                weight="fill"
                                                class="size-4 shrink-0"
                                            />
                                            Dashboard
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhReceipt
                                                class="size-4 shrink-0"
                                            />
                                            Fatture
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhUsers
                                                class="size-4 shrink-0"
                                            />
                                            Clienti
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhBell
                                                class="size-4 shrink-0"
                                            />
                                            Scadenze
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhCalendarDots
                                                class="size-4 shrink-0"
                                            />
                                            Anni
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="kicker px-4">Sistema</div>
                                    <div class="mt-1 flex flex-col gap-0.5">
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhGearSix
                                                class="size-4 shrink-0"
                                            />
                                            Impostazioni
                                        </div>
                                        <div
                                            class="flex items-center gap-2 px-4 py-1.75 text-13 text-foreground/75"
                                        >
                                            <PhBookOpen
                                                class="size-4 shrink-0"
                                            />
                                            Design system
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mock sidebar rail (collapsed) -->
                        <div
                            class="rounded-md border border-border bg-card p-3"
                        >
                            <p class="kicker mb-2 px-1">Rail — 3rem</p>
                            <div class="flex items-start gap-2">
                                <div
                                    class="relative flex w-[48px] flex-col gap-3 rounded border border-sidebar-border bg-sidebar py-2"
                                >
                                    <!-- Header: logo compatto centrato -->
                                    <div
                                        class="flex h-14 items-center justify-center"
                                    >
                                        <div
                                            class="size-6 rounded-sm bg-foreground/10"
                                        />
                                    </div>
                                    <!-- Group: hairline divider centrata al posto del kicker -->
                                    <div
                                        class="relative h-6"
                                    >
                                        <span
                                            class="absolute top-1/2 left-1/2 h-px w-5 -translate-x-1/2 -translate-y-1/2 bg-border"
                                        />
                                    </div>
                                    <!-- Voci compatte centrate -->
                                    <div class="flex flex-col gap-0.5">
                                        <div
                                            class="relative flex items-center justify-center py-1.5 text-foreground"
                                        >
                                            <span
                                                class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-accent-vivid"
                                            />
                                            <PhHouse
                                                weight="fill"
                                                class="size-4"
                                            />
                                        </div>
                                        <div
                                            class="flex items-center justify-center py-1.5 text-foreground/75"
                                        >
                                            <PhReceipt class="size-4" />
                                        </div>
                                        <div
                                            class="flex items-center justify-center py-1.5 text-foreground/75"
                                        >
                                            <PhUsers class="size-4" />
                                        </div>
                                        <div
                                            class="flex items-center justify-center py-1.5 text-foreground/75"
                                        >
                                            <PhBell class="size-4" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Collapse toggle outline a cavallo del bordo,
                                     radius default (4px) coerente col system -->
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="-ml-5 mt-3 size-6"
                                    aria-label="Espandi sidebar"
                                >
                                    <PhCaretRight :size="11" weight="bold" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- 9.2 Stati voce -->
                    <h3 class="kicker mb-3">9.2 Stati voce</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 110px 1fr 1fr"
                        >
                            <span>Stato</span>
                            <span>Look</span>
                            <span>Regole</span>
                        </div>
                        <!-- Default -->
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 1fr 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >default</code
                            >
                            <div
                                class="border-border-soft inline-flex max-w-[200px] items-center gap-2 rounded border bg-sidebar px-4 py-1.5 text-13 text-foreground/75"
                            >
                                <PhReceipt class="size-4 shrink-0" />
                                Fatture
                            </div>
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                <code class="font-mono"
                                    >text-foreground/75</code
                                >, icona regular
                            </span>
                        </div>
                        <!-- Hover -->
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 1fr 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >hover</code
                            >
                            <div
                                class="border-border-soft relative inline-flex max-w-[200px] items-center gap-2 rounded border bg-sidebar px-4 py-1.5 text-13 text-foreground"
                            >
                                <span
                                    class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-muted-foreground/40"
                                />
                                <PhReceipt weight="fill" class="size-4 shrink-0" />
                                Fatture
                            </div>
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Barretta ghost
                                <code class="font-mono"
                                    >bg-muted-foreground/40</code
                                >
                                fade-in, icona crossfade regular → fill, testo
                                <code class="font-mono"
                                    >text-foreground</code
                                >
                            </span>
                        </div>
                        <!-- Active -->
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 110px 1fr 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >active</code
                            >
                            <div
                                class="border-border-soft relative inline-flex max-w-[200px] items-center gap-2 rounded border bg-sidebar px-4 py-1.5 text-13 font-medium text-foreground"
                            >
                                <span
                                    class="absolute top-1/2 left-0 h-5 w-[2px] -translate-y-1/2 rounded-r-[2px] bg-accent-vivid"
                                />
                                <PhReceipt weight="fill" class="size-4 shrink-0" />
                                Fatture
                            </div>
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Barretta accent
                                <code class="font-mono"
                                    >bg-accent-vivid w-[2px]</code
                                >, icona
                                <code class="font-mono">weight="fill"</code>,
                                testo
                                <code class="font-mono">font-medium</code>.
                                Niente bg sulla voce — l'identità visiva è la
                                barretta.
                            </span>
                        </div>
                    </div>

                    <!-- 9.3 Rail mode -->
                    <h3 class="kicker mb-3">9.3 Modalità rail (collapsed)</h3>
                    <div
                        class="mb-10 rounded-md border border-border bg-card p-5"
                    >
                        <ul class="space-y-2 text-2xs text-muted-foreground">
                            <li>
                                <strong class="font-medium text-foreground"
                                    >Width</strong
                                >
                                — 3rem (48px). Espansa a 13rem (208px) on toggle.
                            </li>
                            <li>
                                <strong class="font-medium text-foreground"
                                    >Header h-14</strong
                                >
                                — altezza fissa identica in entrambe le modalità
                                per non far ballare il contenuto sotto.
                            </li>
                            <li>
                                <strong class="font-medium text-foreground"
                                    >Group label</strong
                                >
                                — il testo del kicker diventa
                                <code class="font-mono">text-transparent</code>
                                ma il box mantiene
                                <code class="font-mono">h-6</code> per
                                preservare lo spacing fra gruppi. Una hairline
                                <code class="font-mono"
                                    >::before h-px w-5 bg-border</code
                                >
                                centrata fa da divider implicito.
                            </li>
                            <li>
                                <strong class="font-medium text-foreground"
                                    >Voci</strong
                                >
                                — solo icona, label nascoste via
                                <code class="font-mono"
                                    >group-data-[collapsible=icon]:hidden</code
                                >. La barretta accent resta al bordo sinistro.
                            </li>
                            <li>
                                <strong class="font-medium text-foreground"
                                    >NavUser</strong
                                >
                                — in rail mode il
                                <code class="font-mono">SidebarMenuButton</code>
                                viene forzato a
                                <code class="font-mono">size-8</code> con
                                avatar
                                <code class="font-mono">size-5</code> centrato
                                (override del
                                <code class="font-mono">p-2!</code> del
                                variant).
                            </li>
                            <li>
                                <strong class="font-medium text-foreground"
                                    >Collapse toggle</strong
                                >
                                — Button outline
                                <code class="font-mono">size-6</code> (radius
                                4px default, coerente col system), fixed
                                <code class="font-mono"
                                    >left: var(--sidebar-width) - 12px</code
                                >
                                (= half size). Icona
                                <code class="font-mono">PhCaretLeft</code>
                                expanded ↔
                                <code class="font-mono">PhCaretRight</code>
                                collapsed.
                            </li>
                        </ul>
                    </div>

                    <!-- 9.4 Aggiungere voci -->
                    <h3 class="kicker mb-3">9.4 Aggiungere voci</h3>
                    <div class="rounded-md border border-border bg-card p-5">
                        <p class="mb-3 text-13 text-muted-foreground">
                            La sidebar è hardcoded — niente prop dinamiche. Per
                            aggiungere una voce: edita
                            <code class="kbd">AppSidebar.vue</code>, inserisci
                            nel array <code class="kbd">sections</code>. Il
                            target rotta deve essere una funzione Wayfinder.
                        </p>
                        <pre
                            class="border-border-soft overflow-x-auto rounded border bg-muted/30 p-3 font-mono text-2xs text-foreground/85"
                        ><code>const sections: NavSection[] = [
  &#123;
    label: 'Lavoro',
    items: [
      &#123; label: 'Dashboard', icon: PhHouse, href: dashboard() &#125;,
      &#123; label: 'Fatture', icon: PhReceipt, href: fatturesIndex() &#125;,
      &#123; label: 'Clienti', icon: PhUsers, href: clientiIndex() &#125;,
      &#123; label: 'Scadenze', icon: PhBell, href: scadenzeIndex() &#125;,
      &#123; label: 'Anni', icon: PhCalendarDots, href: anniIndex() &#125;,
    ],
  &#125;,
  &#123;
    label: 'Sistema',
    items: [
      &#123; label: 'Impostazioni', icon: PhGearSix, href: profileEdit() &#125;,
      &#123; label: 'Design system', icon: PhBookOpen, href: designSystem() &#125;,
    ],
  &#125;,
]</code></pre>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     10 Side panels
                     ───────────────────────────────────────────────────────── -->
                <section id="s-side-panels" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            10 Side panels
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Pannelli laterali inline-push (non overlay): vivono come
                        fratelli del
                        <code class="kbd">&lt;main&gt;</code> dentro la pagina.
                        Drop di Sheet — i pannelli laterali sono sempre push,
                        non si sovrappongono al content. 2 wrapper compositi:
                        <code class="kbd">FilterSidebar</code> (filtri di lista)
                        e <code class="kbd">RightDetailPanel</code> (dettaglio
                        record nelle Show).
                    </p>

                    <!-- 10.1 Anatomia -->
                    <h3 class="kicker mb-3">10.1 Anatomia</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border bg-card"
                    >
                        <div class="flex h-[280px]">
                            <!-- Mock main -->
                            <div
                                class="flex-1 overflow-hidden bg-background p-5"
                            >
                                <p class="kicker mb-2">main</p>
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-3/4 rounded bg-muted/60"
                                    />
                                    <div
                                        class="h-4 w-2/3 rounded bg-muted/60"
                                    />
                                    <div
                                        class="h-4 w-1/2 rounded bg-muted/60"
                                    />
                                </div>
                            </div>
                            <!-- Mock side panel -->
                            <aside
                                class="w-[260px] shrink-0 overflow-y-auto border-l border-border bg-card p-5"
                            >
                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <span
                                        class="text-13 font-medium text-foreground"
                                        >Filtri</span
                                    >
                                    <button
                                        type="button"
                                        class="text-2xs text-accent-vivid hover:underline"
                                    >
                                        Pulisci
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <div class="kicker mb-1.5">Stato</div>
                                        <div class="space-y-1.5">
                                            <div
                                                class="flex items-center gap-2 text-13"
                                            >
                                                <Checkbox model-value />
                                                <span>Pagate</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 text-13"
                                            >
                                                <Checkbox />
                                                <span>In arrivo</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 text-13"
                                            >
                                                <Checkbox />
                                                <span>Scadute</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <!-- 10.2 Componenti API -->
                    <h3 class="kicker mb-3">10.2 Componenti</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <span>Componente</span>
                            <span>Props / Slot / Emit</span>
                            <span>Cosa fa</span>
                        </div>
                        <div
                            class="border-border-soft grid items-center gap-4 border-b bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >FilterSidebar</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >open?, activeCount?, slot, slot#title,
                                slot#actions, @clear</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Pannello filtri di lista (w-260, border-l,
                                overflow-y-auto). Header con title (default
                                "Filtri") + bottone "Pulisci" visibile se
                                <code class="font-mono">activeCount &gt; 0</code
                                >. Slot per checkbox/radio gruppi.
                            </span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 200px 220px 1fr"
                        >
                            <code class="font-mono text-2xs text-foreground"
                                >RightDetailPanel</code
                            >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >open?, slot, slot#title, slot#actions</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Pannello dettaglio per pagine Show (w-280,
                                border-l). Header con title (default "Dettagli")
                                + slot actions. Slot default per blocchi info /
                                metadata / link rapidi.
                            </span>
                        </div>
                    </div>

                    <!-- 10.3 Pattern d'uso -->
                    <h3 class="kicker mb-3">10.3 Pattern d'uso</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        I pannelli vivono
                        <strong class="font-medium text-foreground"
                            >dentro la pagina</strong
                        >
                        come fratelli del <code class="kbd">&lt;main&gt;</code>.
                        Il toggle del pannello è state locale della pagina, e il
                        bottone "Filtri" / "Dettagli" va in topbar via Teleport
                        (vedi sezione 08).
                    </p>
                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;script setup&gt;
const filtersOpen = ref(false)
setLayoutProps(&#123; pageTitle: 'Clienti', subbar: true &#125;)
&lt;/script&gt;

&lt;template&gt;
  &lt;Teleport to="#page-topbar-filters" defer&gt;
    &lt;Button variant="outline" @click="filtersOpen = !filtersOpen"&gt;
      &lt;PhFunnel /&gt; Filtri
    &lt;/Button&gt;
  &lt;/Teleport&gt;

  &lt;div class="flex h-full"&gt;
    &lt;main class="flex-1 overflow-y-auto px-6 py-10"&gt;
      …lista…
    &lt;/main&gt;
    &lt;FilterSidebar
      :open="filtersOpen"
      :active-count="activeFilters.length"
      @clear="clearFilters()"
    &gt;
      …checkbox per stato/tipologia/anno…
    &lt;/FilterSidebar&gt;
  &lt;/div&gt;
&lt;/template&gt;</code></pre>

                    <!-- 10.4 Quando push vs Sheet -->
                    <h3 class="kicker mb-3">10.4 Push vs Sheet</h3>
                    <div class="rounded-md border border-border bg-card p-5">
                        <p
                            class="mb-3 text-13 leading-relaxed text-muted-foreground"
                        >
                            La regola: i pannelli laterali vivono
                            <strong class="font-medium text-foreground"
                                >sempre come push inline</strong
                            >, non come overlay. Il content principale resta
                            sempre visibile, si comprime per fare spazio al
                            pannello.
                        </p>
                        <ul class="space-y-2 text-13 text-foreground/85">
                            <li class="flex items-start gap-2">
                                <span
                                    class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                />
                                <span>
                                    <strong class="font-medium text-foreground"
                                        >Filtri</strong
                                    >
                                    → FilterSidebar push (mai Sheet overlay)
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span
                                    class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                                />
                                <span>
                                    <strong class="font-medium text-foreground"
                                        >Dettaglio record secondario</strong
                                    >
                                    → RightDetailPanel push (mai Sheet overlay)
                                </span>
                            </li>
                            <li class="flex items-start gap-2">
                                <PhX
                                    class="mt-1 size-3 shrink-0 text-destructive"
                                />
                                <span>
                                    <strong class="font-medium text-foreground"
                                        >Edit modale focalizzata</strong
                                    >
                                    → Dialog (vedi sezione 06), non panel
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     11 Form layout
                     ───────────────────────────────────────────────────────── -->
                <section id="s-form-layout" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            11 Form layout
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Solo <code class="kbd">FormSection</code> per
                        raggruppare campi. I singoli campi usano
                        <code class="kbd">FormField</code>
                        (documentato in sezione 04). Niente footbar dedicata: i
                        bottoni Salva / Ripristina vivono nella topbar via
                        mount-point
                        <code class="kbd">#page-topbar-actions</code>.
                    </p>

                    <!-- 11.1 FormSection API -->
                    <h3 class="kicker mb-3">11.1 FormSection</h3>
                    <div
                        class="mb-10 overflow-hidden rounded-md border border-border"
                    >
                        <div
                            class="grid items-center gap-4 bg-muted/40 px-4 py-2 text-2xs font-medium tracking-wider text-muted-foreground uppercase"
                            style="grid-template-columns: 220px 1fr"
                        >
                            <span>Props / Slot</span>
                            <span>Cosa fa</span>
                        </div>
                        <div
                            class="grid items-center gap-4 bg-card px-4 py-2.5"
                            style="grid-template-columns: 220px 1fr"
                        >
                            <code
                                class="font-mono text-2xs text-muted-foreground"
                                >title?, first?, last?, slot, slot#actions</code
                            >
                            <span
                                class="text-2xs leading-snug text-muted-foreground/85"
                            >
                                Sezione di form. Header con title (2xs semibold)
                                + slot default per FormField. Slot named
                                "actions" opzionale a destra. Prop
                                <code class="font-mono">first</code> /
                                <code class="font-mono">last</code> per drop
                                padding top/bottom.
                            </span>
                        </div>
                    </div>

                    <!-- 11.2 Esempio live: Modifica cliente -->
                    <h3 class="kicker mb-3">11.2 Esempio — modifica cliente</h3>
                    <p class="mb-3 max-w-2xl text-2xs text-muted-foreground">
                        2 sezioni con campi tipici. Niente sticky footbar: in
                        pagina reale i bottoni Salva / Ripristina vanno nei
                        mount-point Teleport della topbar (vedi sezione 08).
                    </p>
                    <div
                        class="mb-10 rounded-md border border-border bg-card px-6"
                    >
                        <FormSection first title="Anagrafica">
                            <FormField label="Ragione sociale" required>
                                <Input v-model="demoForm.name" />
                            </FormField>
                            <FormField
                                label="P.IVA"
                                hint="11 cifre, italiana o intra-UE."
                            >
                                <Input
                                    v-model="demoForm.piva"
                                    class="tabular"
                                />
                            </FormField>
                            <FormField label="Città">
                                <Input v-model="demoForm.city" />
                            </FormField>
                        </FormSection>

                        <FormSection last title="Note">
                            <FormField label="Note">
                                <Textarea v-model="demoForm.notes" rows="3" />
                            </FormField>
                        </FormSection>
                    </div>

                    <!-- 11.3 Pattern canonico (page-topbar-actions per Salva/Ripristina) -->
                    <h3 class="kicker mb-3">11.3 Pattern canonico</h3>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;!-- Bottoni Salva / Ripristina nella topbar (subbar) --&gt;
&lt;Teleport to="#page-topbar-actions" defer&gt;
  &lt;Button
    variant="ghost"
    :disabled="!form.isDirty || form.processing"
    @click="form.reset()"
  &gt;
    &lt;PhArrowCounterClockwise /&gt; Ripristina
  &lt;/Button&gt;
  &lt;Button
    :disabled="!form.isDirty || form.processing"
    @click="form.save()"
  &gt;
    &lt;PhFloppyDisk /&gt; Salva &lt;Kbd&gt;⌘S&lt;/Kbd&gt;
  &lt;/Button&gt;
&lt;/Teleport&gt;

&lt;!-- Sezioni form nel body --&gt;
&lt;FormSection first title="Anagrafica"&gt;
  &lt;FormField label="Ragione sociale" required&gt;
    &lt;Input v-model="form.name" /&gt;
  &lt;/FormField&gt;
  &lt;FormField label="P.IVA" hint="11 cifre."&gt;
    &lt;Input v-model="form.piva" class="tabular" /&gt;
  &lt;/FormField&gt;
&lt;/FormSection&gt;

&lt;FormSection last title="Note"&gt;
  &lt;FormField label="Note"&gt;
    &lt;Textarea v-model="form.notes" rows="3" /&gt;
  &lt;/FormField&gt;
&lt;/FormSection&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     12 Modal patterns
                     ───────────────────────────────────────────────────────── -->
                <section id="s-modals" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            12 Modal patterns
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        3 pattern d'uso ricorrenti dei Dialog (anatomia in
                        <a href="#s-dialog" class="underline">sezione 06</a>):
                        <strong class="font-medium text-foreground"
                            >Confirm</strong
                        >
                        (delete / archive),
                        <strong class="font-medium text-foreground"
                            >Form</strong
                        >
                        (create / edit entity),
                        <strong class="font-medium text-foreground"
                            >Wizard</strong
                        >
                        (multi-step). Per Confirm c'è il wrapper pre-cucinato
                        <code class="kbd">&lt;ConfirmDialog&gt;</code>.
                    </p>

                    <!-- 12.1 Confirm Dialog -->
                    <h3 class="kicker mb-3">12.1 Confirm Dialog</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Per delete / archive / azioni rapide. Wrapper composto
                        su Mini Dialog + DialogStandardHeader +
                        DialogStandardFooter.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <Button
                            variant="outline"
                            @click="demoConfirmOpen = true"
                        >
                            <PhTrash />
                            Apri ConfirmDialog
                        </Button>
                    </div>
                    <ConfirmDialog
                        v-model:open="demoConfirmOpen"
                        title="Eliminare il cliente?"
                        description="Le fatture e le scadenze già collegate al cliente vengono mantenute, ma il cliente non sarà più selezionabile per nuovi documenti."
                        confirm-label="Elimina"
                        destructive
                        @confirm="demoConfirm"
                    />
                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;ConfirmDialog
  v-model:open="deleteOpen"
  title="Eliminare il cliente?"
  description="Le fatture e le scadenze già collegate vengono mantenute…"
  confirm-label="Elimina"
  destructive
  @confirm="performDelete"
/&gt;</code></pre>

                    <!-- 12.2 Form Dialog -->
                    <h3 class="kicker mb-3">12.2 Form Dialog</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Per create / edit entity. Niente wrapper dedicato:
                        compongi
                        <code class="kbd">DialogContent</code> +
                        <code class="kbd">DialogStandardHeader</code> +
                        <code class="kbd">DialogBody</code> contenente
                        <code class="kbd">FormField</code> +
                        <code class="kbd">DialogStandardFooter</code> con il
                        bottone primario custom. Size
                        <code class="kbd">default</code> per form rapidi (1
                        colonna), <code class="kbd">wide</code> per 2 colonne.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="outline">
                                    <PhPlus />
                                    Apri Form Dialog
                                </Button>
                            </DialogTrigger>
                            <DialogContent :show-close-button="false">
                                <DialogStandardHeader
                                    title="Nuova voce di spesa"
                                    description="Aggiungi una voce di spesa ricorrente da usare in scadenze e bilancio annuale."
                                />
                                <DialogBody>
                                    <FormField label="Nome voce" required>
                                        <Input placeholder="Es. Bolli f24" />
                                    </FormField>
                                    <FormField
                                        label="Note"
                                        hint="Visibile nel dettaglio voce."
                                    >
                                        <Textarea rows="3" />
                                    </FormField>
                                </DialogBody>
                                <DialogStandardFooter>
                                    <Button>Crea voce</Button>
                                </DialogStandardFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Dialog v-model:open="formOpen"&gt;
  &lt;DialogContent&gt;
    &lt;DialogStandardHeader
      title="Nuova voce di spesa"
      description="…"
    /&gt;
    &lt;DialogBody&gt;
      &lt;FormField label="Nome" required&gt;
        &lt;Input v-model="form.name" /&gt;
      &lt;/FormField&gt;
      &lt;FormField label="Note"&gt;
        &lt;Textarea v-model="form.notes" rows="3" /&gt;
      &lt;/FormField&gt;
    &lt;/DialogBody&gt;
    &lt;DialogStandardFooter&gt;
      &lt;Button @click="save()"&gt;Crea voce&lt;/Button&gt;
    &lt;/DialogStandardFooter&gt;
  &lt;/DialogContent&gt;
&lt;/Dialog&gt;</code></pre>

                    <!-- 12.3 Wizard Dialog -->
                    <h3 class="kicker mb-3">12.3 Wizard Dialog</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Per flussi multi-step. Size
                        <code class="kbd">fullscreen</code>, progresso con
                        <code class="kbd">&lt;WizardStepper&gt;</code>
                        (mini-pills accent) nel
                        <code class="kbd">#trailing</code> del header,
                        descrizione dinamica per nominare lo step corrente (es.
                        "Step 1 di 4 · Cliente"). Bottone primary diventa
                        "Avanti" / "Crea" sull'ultimo step.
                    </p>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Le
                        <strong class="font-medium text-foreground"
                            >mini-pills</strong
                        >
                        sono il pattern unico Studiofinance: visivamente discrete,
                        lasciano respirare il content e funzionano bene da 3 a 6
                        step. Il nome dello step è già nella description, non
                        serve la mappa completa.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <Button
                            variant="outline"
                            @click="demoWizardOpen = true"
                        >
                            <PhPlus />
                            Apri Wizard
                        </Button>
                    </div>
                    <Dialog v-model:open="demoWizardOpen">
                        <DialogContent
                            size="fullscreen"
                            :show-close-button="false"
                        >
                            <DialogStandardHeader
                                title="Nuova fattura"
                                :description="`Step ${demoWizardStep} di 4 · ${['Cliente', 'Voci', 'Riepilogo', 'Conferma'][demoWizardStep - 1]}`"
                            >
                                <template #trailing>
                                    <WizardStepper
                                        :current="demoWizardStep"
                                        :total="4"
                                    />
                                </template>
                            </DialogStandardHeader>
                            <DialogBody class="flex-1 overflow-auto">
                                <p class="text-13 text-muted-foreground">
                                    Contenuto step
                                    <strong
                                        class="font-medium text-foreground"
                                        >{{ demoWizardStep }}</strong
                                    >: qui tipicamente vivono i campi specifici
                                    dello step (selezione cliente, righe
                                    fattura, riepilogo importi, conferma).
                                </p>
                            </DialogBody>
                            <DialogStandardFooter cancel-label="Annulla">
                                <Button
                                    v-if="demoWizardStep > 1"
                                    variant="outline"
                                    @click="demoWizardStep--"
                                >
                                    Indietro
                                </Button>
                                <Button
                                    v-if="demoWizardStep < 4"
                                    @click="demoWizardStep++"
                                >
                                    Avanti
                                </Button>
                                <Button
                                    v-else
                                    @click="
                                        demoWizardOpen = false;
                                        demoWizardStep = 1;
                                        toast.success('Fattura creata.');
                                    "
                                >
                                    Crea fattura
                                </Button>
                            </DialogStandardFooter>
                        </DialogContent>
                    </Dialog>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Dialog v-model:open="wizardOpen"&gt;
  &lt;DialogContent size="fullscreen"&gt;
    &lt;DialogStandardHeader
      title="Nuova fattura"
      :description="`Step $&#123;step&#125; di 4 · $&#123;stepLabels[step - 1]&#125;`"
    &gt;
      &lt;template #trailing&gt;
        &lt;WizardStepper :current="step" :total="4" /&gt;
      &lt;/template&gt;
    &lt;/DialogStandardHeader&gt;
    &lt;DialogBody class="flex-1 overflow-auto"&gt;
      &lt;component :is="stepComponents[step]" /&gt;
    &lt;/DialogBody&gt;
    &lt;DialogStandardFooter&gt;
      &lt;Button v-if="step &gt; 1" variant="outline" @click="step--"&gt;Indietro&lt;/Button&gt;
      &lt;Button v-if="step &lt; 4" @click="step++"&gt;Avanti&lt;/Button&gt;
      &lt;Button v-else @click="finish()"&gt;Crea fattura&lt;/Button&gt;
    &lt;/DialogStandardFooter&gt;
  &lt;/DialogContent&gt;
&lt;/Dialog&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     13 Pagination                     ───────────────────────────────────────────────────────── -->
                <section id="s-pagination" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            13 Pagination
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Strip canonica per liste paginated server-side. Layout
                        fisso:
                        <code class="kbd">range "1–25 di 124"</code> a sinistra,
                        <code class="kbd">« ‹ 1 … 5 › »</code> numerico a
                        destra. Niente selector "righe per pagina" — quel valore
                        lo decide il backend per la pagina/ruolo.
                    </p>

                    <h3 class="kicker mb-3">13.1 Componente canonico</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        <code class="kbd">DataTablePagination</code> (in
                        <code class="kbd">@/components/ui/table</code>) è il
                        wrapper d'uso. Riceve <code class="kbd">page</code> /
                        <code class="kbd">perPage</code> /
                        <code class="kbd">total</code>, emette
                        <code class="kbd">update:page</code>. Usalo sotto ogni
                        tabella (è già il pattern di sezione 07).
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <DataTablePagination
                            :page="demoPagPage"
                            :per-page="25"
                            :total="247"
                            @update:page="(v) => (demoPagPage = v)"
                        />
                    </div>
                    <pre
                        class="mb-10 overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;DataTablePagination
  :page="page"
  :per-page="25"
  :total="total"
  @update:page="(v) =&gt; load(v)"
/&gt;</code></pre>

                    <h3 class="kicker mb-3">
                        13.2 Quando usare la primitive raw
                    </h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Le primitive shadcn
                        <code class="kbd">Pagination*</code> (<code class="kbd"
                            >@/components/ui/pagination</code
                        >) restano disponibili per casi atipici: paginazione di
                        card-grid, di lista no-table, o layout custom. Per le
                        tabelle
                        <strong class="font-medium text-foreground"
                            >usa sempre il wrapper</strong
                        >, non comporre raw.
                    </p>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     14 Progress                     ───────────────────────────────────────────────────────── -->
                <section id="s-progress" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            14 Progress
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Barra orizzontale fine per stati determinati:
                        percentuale completamento anno fiscale, upload XML,
                        calcoli backend.
                        <strong class="font-medium text-foreground">Non</strong>
                        per loading generico (usa Spinner) né per stati
                        indeterminati.
                    </p>

                    <h3 class="kicker mb-3">14.1 Esempi</h3>
                    <div
                        class="mb-4 space-y-5 rounded-md border border-border bg-card p-5"
                    >
                        <div>
                            <div
                                class="mb-2 flex items-baseline justify-between text-13"
                            >
                                <span class="text-foreground"
                                    >Fase concept</span
                                >
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >100%</span
                                >
                            </div>
                            <Progress :model-value="100" />
                        </div>
                        <div>
                            <div
                                class="mb-2 flex items-baseline justify-between text-13"
                            >
                                <span class="text-foreground"
                                    >Fase definitivo</span
                                >
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >{{ demoProgress }}%</span
                                >
                            </div>
                            <Progress :model-value="demoProgress" />
                            <div class="mt-3 flex items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        demoProgress = Math.max(
                                            0,
                                            demoProgress - 10,
                                        )
                                    "
                                    >−10</Button
                                >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        demoProgress = Math.min(
                                            100,
                                            demoProgress + 10,
                                        )
                                    "
                                    >+10</Button
                                >
                            </div>
                        </div>
                        <div>
                            <div
                                class="mb-2 flex items-baseline justify-between text-13"
                            >
                                <span class="text-foreground"
                                    >Fase esecutivo</span
                                >
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >12%</span
                                >
                            </div>
                            <Progress :model-value="12" />
                        </div>
                    </div>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Progress :model-value="percent" /&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     15 Empty state                     ───────────────────────────────────────────────────────── -->
                <section id="s-empty" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            15 Empty state
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Pattern per assenza di content: tabella vuota, ricerca
                        senza risultati, pagina appena creata, prima entità da
                        seedare. Composto da
                        <code class="kbd">Empty</code> +
                        <code class="kbd">EmptyHeader</code> (icona + title +
                        description) +
                        <code class="kbd">EmptyContent</code> (CTA opzionale).
                    </p>

                    <h3 class="kicker mb-3">15.1 First-run (zero state)</h3>
                    <div class="mb-6 rounded-md border border-border bg-card">
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <PhReceipt />
                                </EmptyMedia>
                                <EmptyTitle>Nessuna fattura ancora</EmptyTitle>
                                <EmptyDescription>
                                    Importa il primo file XML o crea una
                                    fattura manualmente. Numerazione e totali
                                    vengono calcolati automaticamente.
                                </EmptyDescription>
                            </EmptyHeader>
                            <EmptyContent>
                                <Button>
                                    <PhPlus />
                                    Nuova fattura
                                </Button>
                            </EmptyContent>
                        </Empty>
                    </div>

                    <h3 class="kicker mb-3">15.2 Ricerca senza risultati</h3>
                    <div class="mb-6 rounded-md border border-border bg-card">
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <PhMagnifyingGlass />
                                </EmptyMedia>
                                <EmptyTitle>Nessun risultato</EmptyTitle>
                                <EmptyDescription>
                                    Nessuna fattura corrisponde a "Studio
                                    Bianchi". Prova con meno parole o rimuovi
                                    i filtri attivi.
                                </EmptyDescription>
                            </EmptyHeader>
                            <EmptyContent>
                                <Button variant="outline"
                                    >Pulisci filtri</Button
                                >
                            </EmptyContent>
                        </Empty>
                    </div>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;Empty&gt;
  &lt;EmptyHeader&gt;
    &lt;EmptyMedia variant="icon"&gt;&lt;PhReceipt /&gt;&lt;/EmptyMedia&gt;
    &lt;EmptyTitle&gt;Nessuna fattura ancora&lt;/EmptyTitle&gt;
    &lt;EmptyDescription&gt;Importa il primo file XML o crea una fattura…&lt;/EmptyDescription&gt;
  &lt;/EmptyHeader&gt;
  &lt;EmptyContent&gt;
    &lt;Button&gt;Nuova fattura&lt;/Button&gt;
  &lt;/EmptyContent&gt;
&lt;/Empty&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     16 Toggle group                     ───────────────────────────────────────────────────────── -->
                <section id="s-toggle" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            16 Toggle group
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Segmented control per
                        <strong class="font-medium text-foreground"
                            >selezionare un valore</strong
                        >, non per cambiare pannello (per quello c'è Tabs in
                        <a href="#s-tabs" class="underline">sezione 05</a>). Tre
                        forme: single (radio segmentato), multi (multi-select),
                        e <code class="kbd">Toggle</code> standalone (boolean
                        on/off).
                    </p>

                    <h3 class="kicker mb-3">
                        16.1 ToggleGroup single (view-toggle, variant boxed)
                    </h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Pattern
                        <code class="kbd">#page-topbar-views</code> Lista /
                        Griglia. Stesso content, presentazione diversa. Variant
                        <code class="kbd">boxed</code> = stessa grammatica
                        visiva di <a href="#s-tabs" class="underline">Tabs</a>:
                        container muted, item attivo elevato a card bianca con
                        shadow.
                    </p>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <ToggleGroup
                            v-model="demoView"
                            type="single"
                            variant="boxed"
                        >
                            <ToggleGroupItem value="list" aria-label="Lista">
                                <PhRows />
                                Lista
                            </ToggleGroupItem>
                            <ToggleGroupItem value="grid" aria-label="Griglia">
                                <PhSquaresFour />
                                Griglia
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <p class="mt-3 text-2xs text-muted-foreground">
                            Vista corrente:
                            <code class="font-mono text-foreground">{{
                                demoView
                            }}</code>
                        </p>
                    </div>

                    <h3 class="kicker mb-3">
                        16.2 ToggleGroup multi (formattazione)
                    </h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Editor di testo, filtri non-esclusivi, opzioni multi.
                    </p>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <ToggleGroup
                            v-model="demoFormat"
                            type="multiple"
                            variant="outline"
                        >
                            <ToggleGroupItem
                                value="bold"
                                aria-label="Grassetto"
                            >
                                <PhTextB />
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="italic"
                                aria-label="Corsivo"
                            >
                                <PhTextItalic />
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="underline"
                                aria-label="Sottolineato"
                            >
                                <PhTextUnderline />
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <p class="mt-3 text-2xs text-muted-foreground">
                            Attivi:
                            <code class="font-mono text-foreground">{{
                                demoFormat.join(', ') || '—'
                            }}</code>
                        </p>
                    </div>

                    <h3 class="kicker mb-3">16.3 Toggle standalone</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Single boolean. Differenza con
                        <code class="kbd">Switch</code>: il Toggle è un
                        <em>bottone</em> con stato pressed (semantica: aziona
                        qualcosa); Switch è un <em>setting</em> che cambia
                        valore. Quando in dubbio, usa Switch nei form.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <Toggle
                            v-model="demoBoldOn"
                            variant="outline"
                            aria-label="Grassetto"
                        >
                            <PhTextB />
                            Grassetto
                        </Toggle>
                        <p class="mt-3 text-2xs text-muted-foreground">
                            Stato:
                            <code class="font-mono text-foreground">{{
                                demoBoldOn ? 'on' : 'off'
                            }}</code>
                        </p>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     17 Aspect ratio                     ───────────────────────────────────────────────────────── -->
                <section id="s-aspect-ratio" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            17 Aspect ratio
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Wrapper utility per fissare il rapporto
                        larghezza/altezza di un elemento. Per anteprime XML,
                        miniature documenti, video embed o screenshot. Funziona
                        con qualunque content interno (img, div, video).
                    </p>

                    <h3 class="kicker mb-3">17.1 Anteprima documento 3:2</h3>
                    <div class="mb-6 grid gap-4 md:grid-cols-3">
                        <div
                            class="rounded-md border border-border bg-card p-3"
                        >
                            <AspectRatio
                                :ratio="3 / 2"
                                class="overflow-hidden rounded"
                            >
                                <div
                                    class="flex size-full items-center justify-center bg-muted text-muted-foreground"
                                >
                                    <PhFolder class="size-8" />
                                </div>
                            </AspectRatio>
                            <p class="mt-2 text-xs text-muted-foreground">
                                3:2 (cover)
                            </p>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-3"
                        >
                            <AspectRatio
                                :ratio="16 / 9"
                                class="overflow-hidden rounded"
                            >
                                <div
                                    class="flex size-full items-center justify-center bg-muted text-muted-foreground"
                                >
                                    <PhFolder class="size-8" />
                                </div>
                            </AspectRatio>
                            <p class="mt-2 text-xs text-muted-foreground">
                                16:9 (video / wide)
                            </p>
                        </div>
                        <div
                            class="rounded-md border border-border bg-card p-3"
                        >
                            <AspectRatio
                                :ratio="1"
                                class="overflow-hidden rounded"
                            >
                                <div
                                    class="flex size-full items-center justify-center bg-muted text-muted-foreground"
                                >
                                    <PhFolder class="size-8" />
                                </div>
                            </AspectRatio>
                            <p class="mt-2 text-xs text-muted-foreground">
                                1:1 (avatar / quadro)
                            </p>
                        </div>
                    </div>
                    <pre
                        class="overflow-x-auto rounded-md border border-border bg-muted/30 p-4 font-mono text-2xs text-foreground/85"
                    ><code>&lt;AspectRatio :ratio="3 / 2" class="overflow-hidden rounded"&gt;
  &lt;img src="…" class="size-full object-cover" /&gt;
&lt;/AspectRatio&gt;</code></pre>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     18 Hover card                     ───────────────────────────────────────────────────────── -->
                <section id="s-hover-card" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            18 Hover card
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Anteprima rich on-hover: link cliente, fattura,
                        scadenza. Solo per content non-essenziale (l'utente
                        può navigarci sopra senza). Per info breve usa
                        <code class="kbd">Tooltip</code>; per dettaglio
                        cliccabile usa <code class="kbd">Popover</code>.
                    </p>

                    <h3 class="kicker mb-3">18.1 Anteprima cliente</h3>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <p class="text-13 text-muted-foreground">
                            Fattura intestata a
                            <HoverCard>
                                <HoverCardTrigger as-child>
                                    <a
                                        href="#"
                                        class="font-medium text-foreground underline-offset-2 hover:underline"
                                    >
                                        Acme Architettura srl
                                    </a>
                                </HoverCardTrigger>
                                <HoverCardContent class="w-80">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-accent text-accent-strong"
                                        >
                                            <PhBuildings />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-13 font-medium text-foreground"
                                            >
                                                Acme Architettura srl
                                            </p>
                                            <p
                                                class="text-2xs text-muted-foreground"
                                            >
                                                Cliente · Milano · 12 fatture
                                                nel 2026
                                            </p>
                                            <p
                                                class="mt-2 text-13 leading-relaxed text-muted-foreground"
                                            >
                                                P.IVA 01234567890 — referente
                                                Mario Rossi (mario@acme.it).
                                            </p>
                                        </div>
                                    </div>
                                </HoverCardContent>
                            </HoverCard>
                            con scadenza il 30 novembre 2026.
                        </p>
                    </div>

                    <h3 class="kicker mb-3">18.2 Anteprima utente</h3>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <p class="text-13 text-muted-foreground">
                            Ultima modifica di
                            <HoverCard>
                                <HoverCardTrigger as-child>
                                    <a
                                        href="#"
                                        class="font-medium text-foreground underline-offset-2 hover:underline"
                                    >
                                        @luisa
                                    </a>
                                </HoverCardTrigger>
                                <HoverCardContent class="w-72">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted font-medium text-foreground"
                                        >
                                            LB
                                        </div>
                                        <div>
                                            <p
                                                class="text-13 font-medium text-foreground"
                                            >
                                                Luisa Bianchi
                                            </p>
                                            <p
                                                class="text-2xs text-muted-foreground"
                                            >
                                                Architetto Senior · Studio
                                            </p>
                                            <p
                                                class="mt-2 text-2xs text-muted-foreground"
                                            >
                                                Attiva da gennaio 2024 · 38
                                                fatture emesse
                                            </p>
                                        </div>
                                    </div>
                                </HoverCardContent>
                            </HoverCard>
                            ieri alle 17:42.
                        </p>
                    </div>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     19 Loading                     ───────────────────────────────────────────────────────── -->
                <section id="s-loading" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            19 Loading
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Due primitive complementari:
                        <code class="kbd">Skeleton</code> per placeholder che
                        mimano il content in arrivo (liste, tabelle, card),
                        <code class="kbd">Spinner</code> per stati indeterminati
                        inline (bottone async, polling, refresh).
                    </p>

                    <h3 class="kicker mb-3">
                        19.1 Skeleton (lista in caricamento)
                    </h3>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-13 text-muted-foreground">
                                Stato:
                                {{
                                    demoSkeletonLoaded
                                        ? 'caricato'
                                        : 'in caricamento'
                                }}
                            </span>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="
                                    demoSkeletonLoaded = !demoSkeletonLoaded
                                "
                            >
                                Toggle
                            </Button>
                        </div>
                        <div v-if="!demoSkeletonLoaded" class="space-y-3">
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <Skeleton class="size-9 rounded-full" />
                                <div class="flex-1 space-y-2">
                                    <Skeleton class="h-3 w-1/3" />
                                    <Skeleton class="h-3 w-2/3" />
                                </div>
                                <Skeleton class="h-3 w-16" />
                            </div>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(p, i) in [
                                    {
                                        name: 'Villa Bianchi',
                                        meta: 'Acme Architettura · 25-A-001',
                                    },
                                    {
                                        name: 'Restauro Brera',
                                        meta: 'Studio Rossi · 25-A-002',
                                    },
                                    {
                                        name: 'Uffici Tortona',
                                        meta: 'Comune di Milano · 25-A-003',
                                    },
                                ]"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent text-accent-strong"
                                >
                                    <PhFolder />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-13 font-medium text-foreground"
                                    >
                                        {{ p.name }}
                                    </p>
                                    <p class="text-2xs text-muted-foreground">
                                        {{ p.meta }}
                                    </p>
                                </div>
                                <span
                                    class="tabular font-mono text-2xs text-muted-foreground"
                                    >attivo</span
                                >
                            </div>
                        </div>
                    </div>

                    <h3 class="kicker mb-3">
                        19.2 Spinner (inline / bottone async)
                    </h3>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-4">
                            <Spinner />
                            <Button disabled>
                                <Spinner />
                                Caricamento…
                            </Button>
                            <span
                                class="flex items-center gap-2 text-13 text-muted-foreground"
                            >
                                <Spinner class="size-3.5" />
                                Import fatture XML
                            </span>
                        </div>
                    </div>

                    <h3 class="kicker mb-3">19.3 Quando usare quale</h3>
                    <ul class="space-y-1.5 text-13 text-foreground/85">
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Skeleton</strong
                                >
                                quando la struttura del content è nota e il
                                caricamento dura &gt; 200ms (lista, tabella,
                                card).
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Spinner</strong
                                >
                                per azioni inline (bottone in submit, polling
                                refresh, indicatori di stato).
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <PhX
                                class="mt-1 size-3 shrink-0 text-destructive"
                            />
                            <span>
                                Mai full-page spinner (l'utente perde contesto).
                                Sempre Skeleton al posto del content reale.
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     20 Tooltip + Popover                     ───────────────────────────────────────────────────────── -->
                <section id="s-tooltip" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            20 Tooltip + Popover
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Due popup di scala diversa.
                        <strong class="font-medium text-foreground"
                            >Tooltip</strong
                        >
                        = micro helper testuale (label di un'icona, kbd hint,
                        descrizione breve) non interattivo.
                        <strong class="font-medium text-foreground"
                            >Popover</strong
                        >
                        = riquadro interattivo con form, lista, controlli. Per
                        anteprima rich non interattiva c'è
                        <a href="#s-hover-card" class="underline">HoverCard</a>.
                    </p>

                    <h3 class="kicker mb-3">20.1 Tooltip</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Avvolgi tutto in
                        <code class="kbd">TooltipProvider</code>
                        (è già nel layout). Mai per testo già visibile, mai per
                        content che richiede tap su mobile.
                    </p>
                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <TooltipProvider>
                            <div class="flex flex-wrap items-center gap-3">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="outline" size="icon">
                                            <PhPencilSimple />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Modifica fattura</TooltipContent
                                    >
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="outline" size="icon">
                                            <PhTrash />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Sposta nel cestino</TooltipContent
                                    >
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button variant="outline">
                                            <PhInfo />
                                            Helper text
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Spiega un dettaglio non
                                        ovvio.</TooltipContent
                                    >
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </div>

                    <h3 class="kicker mb-3">20.2 Popover</h3>
                    <p class="mb-3 max-w-2xl text-13 text-muted-foreground">
                        Container per content interattivo: form rapidi, picker,
                        mini-menu custom. Si chiude con click-outside o ESC.
                    </p>
                    <div
                        class="mb-4 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Popover v-model:open="demoPopoverOpen">
                                <PopoverTrigger as-child>
                                    <Button variant="outline">
                                        <PhUser />
                                        Profilo rapido
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-80">
                                    <div class="space-y-3">
                                        <div>
                                            <p
                                                class="text-13 font-medium text-foreground"
                                            >
                                                Modifica nome visualizzato
                                            </p>
                                            <p
                                                class="text-2xs text-muted-foreground"
                                            >
                                                Apparirà nei commenti e nelle
                                                attività.
                                            </p>
                                        </div>
                                        <FormField label="Nome">
                                            <Input
                                                default-value="Luisa Bianchi"
                                            />
                                        </FormField>
                                        <div
                                            class="flex justify-end gap-2 pt-1"
                                        >
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="demoPopoverOpen = false"
                                                >Annulla</Button
                                            >
                                            <Button
                                                size="sm"
                                                @click="
                                                    demoPopoverOpen = false;
                                                    toast.success(
                                                        'Profilo aggiornato.',
                                                    );
                                                "
                                                >Salva</Button
                                            >
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <h3 class="kicker mb-3">20.3 Quale scegliere</h3>
                    <ul class="space-y-1.5 text-13 text-foreground/85">
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Tooltip</strong
                                >
                                — testo breve, non cliccabile, scompare al
                                mouse-leave.
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >HoverCard</strong
                                >
                                — anteprima rich (avatar + meta), interattiva ma
                                non required (utente può by-passare).
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Popover</strong
                                >
                                — UI interattiva (form, picker, controlli).
                                Trigger su click, non hover.
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Dialog</strong
                                >
                                — quando l'azione richiede focus totale (vedi
                                <a href="#s-modal" class="underline"
                                    >sezione 12</a
                                >).
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     21 Toast (Sonner)
                     ───────────────────────────────────────────────────────── -->
                <section id="s-toast" class="mb-16 scroll-mt-6">
                    <div
                        class="mb-6 flex items-baseline justify-between border-b border-border pb-3"
                    >
                        <h2 class="text-xl font-medium text-foreground">
                            21 Toast (Sonner)
                        </h2>
                        <span class="text-2xs text-muted-foreground">v0</span>
                    </div>
                    <p class="mb-6 max-w-2xl text-13 text-muted-foreground">
                        Per
                        <strong class="font-medium text-foreground"
                            >conferme di salvataggio</strong
                        >
                        e azioni avvenute usiamo
                        <code class="kbd">vue-sonner</code>. Il
                        <code class="kbd">&lt;Toaster /&gt;</code> è già montato
                        nel layout. Importa <code class="kbd">toast</code> da
                        <code class="kbd">vue-sonner</code>
                        e chiamalo dopo l'azione.
                    </p>

                    <div
                        class="mb-6 rounded-md border border-border bg-card p-5"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                variant="outline"
                                @click="toast('Cliente salvato.')"
                            >
                                Default
                            </Button>
                            <Button
                                variant="secondary"
                                @click="
                                    toast.success('Progetto creato.', {
                                        description:
                                            'Codice 25-A-001 assegnato.',
                                    })
                                "
                            >
                                Success
                            </Button>
                            <Button
                                variant="outline"
                                @click="
                                    toast.error('Errore di salvataggio.', {
                                        description:
                                            'Verifica i campi obbligatori.',
                                    })
                                "
                            >
                                Error
                            </Button>
                            <Button
                                variant="outline"
                                @click="
                                    toast.info('Bozza salvata automaticamente.')
                                "
                            >
                                Info
                            </Button>
                            <Button
                                variant="outline"
                                @click="
                                    toast('Progetto eliminato.', {
                                        action: {
                                            label: 'Annulla',
                                            onClick: () =>
                                                toast.success('Ripristinato.'),
                                        },
                                    })
                                "
                            >
                                Con azione
                            </Button>
                        </div>
                    </div>

                    <h3 class="kicker mb-3">Quando usarlo</h3>
                    <ul class="mb-6 space-y-1.5 text-13 text-foreground/85">
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Conferma salvataggio</strong
                                >
                                — dopo PUT/POST riusciti (Cliente salvato,
                                Progetto creato).
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Errori non bloccanti</strong
                                >
                                — connessione persa, retry automatico,
                                salvataggio bozza fallito.
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span
                                class="mt-2 size-1 shrink-0 rounded-full bg-accent-vivid"
                            />
                            <span>
                                <strong class="font-medium text-foreground"
                                    >Azioni undoable</strong
                                >
                                — eliminazione + bottone "Annulla" inline.
                            </span>
                        </li>
                    </ul>

                    <h3 class="kicker mb-3">Quando NON usarlo</h3>
                    <ul class="space-y-1.5 text-13 text-muted-foreground">
                        <li class="flex items-start gap-2">
                            <PhX
                                class="mt-1 size-3 shrink-0 text-destructive"
                            />
                            <span
                                >Errori di validazione form → usa
                                <code class="kbd">aria-invalid</code> sul field
                                + helper text.</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <PhX
                                class="mt-1 size-3 shrink-0 text-destructive"
                            />
                            <span
                                >Conferme distruttive → usa Dialog modale, non
                                toast.</span
                            >
                        </li>
                        <li class="flex items-start gap-2">
                            <PhX
                                class="mt-1 size-3 shrink-0 text-destructive"
                            />
                            <span
                                >Stati persistenti → quelli vivono nella UI
                                (badge, banner), non in toast effimero.</span
                            >
                        </li>
                    </ul>
                </section>

                <!-- ─────────────────────────────────────────────────────────
                     Sezioni placeholder (Phase B.3 → B.10 in arrivo)
                     ───────────────────────────────────────────────────────── -->
                <section
                    v-for="s in sections.filter((x) => x.status === 'pending')"
                    :id="`s-${s.id}`"
                    :key="s.id"
                    class="mb-16 scroll-mt-6"
                >
                    <div
                        class="border-border-soft mb-6 flex items-baseline justify-between border-b pb-3"
                    >
                        <h2 class="text-xl font-medium text-muted-foreground">
                            {{ s.label }}
                        </h2>
                        <span class="text-2xs text-muted-foreground">{{
                            s.phase
                        }}</span>
                    </div>
                    <div
                        class="rounded-md border border-dashed border-border px-6 py-8"
                    >
                        <p class="text-13 text-muted-foreground">
                            Sezione in arrivo nella fase
                            <code class="kbd">{{ s.phase }}</code>
                            del DS rebuild.
                        </p>
                        <div v-if="s.topics?.length" class="mt-4">
                            <p class="kicker mb-2">Cosa coprirà</p>
                            <ul class="space-y-1.5 text-13 text-foreground/85">
                                <li
                                    v-for="t in s.topics"
                                    :key="t"
                                    class="flex items-start gap-2"
                                >
                                    <span
                                        class="mt-2 size-1 shrink-0 rounded-full bg-muted-foreground/50"
                                        aria-hidden="true"
                                    />
                                    <span>{{ t }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
