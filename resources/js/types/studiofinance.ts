/**
 * Tipi dominio Studiofinance (entità fiscali). Una sola fonte di verità,
 * importati dove servono (Onboarding, Settings, ecc.).
 */

export type ProfessionalProfile = {
    profitability_coefficient: string | number;
    business_start_year: number;
};

export type ExpenseCalculationType =
    | 'percentage_of_irpef_income'
    | 'percentage_of_iva_revenue'
    | 'fixed_annual'
    | 'sum_of_bolli';

export type DeadlineKind = 'payment' | 'fulfillment';
export type DeadlineStatus = 'open' | 'completed' | 'not_due';
export type PaymentStatus = 'planned' | 'paid' | 'not_due';
export type QuotaType =
    | 'tax_advance'
    | 'tax_balance'
    | 'contribution_minimum'
    | 'contribution_adjustment'
    | 'full_amount';

export type DueYearOffset = 'current' | 'next';
export type ExpenseYearOffset = 'current' | 'next';

export type ExpenseItem = {
    id: number;
    name: string;
    calculation_type: ExpenseCalculationType;
    calculation_type_label: string;
    default_rate: number | null;
    default_minimum: number | null;
    default_maximum: number | null;
    default_amount: number | null;
    active: boolean;
    position: number;
};

export type RecurringDeadline = {
    id: number;
    name: string;
    day: number;
    month: number;
    kind: DeadlineKind;
    kind_label: string;
    expense_item_id: number | null;
    expense_item_name: string | null;
    due_year_offset: DueYearOffset;
    due_year_offset_label: string;
    expense_year_offset: ExpenseYearOffset;
    expense_year_offset_label: string;
    quota_type: QuotaType | null;
    quota_type_label: string | null;
    active: boolean;
};

export type EnumOption = { value: string; label: string };

export type Client = {
    id: number;
    name: string;
    vat_number: string | null;
    tax_code: string | null;
    bank_withholding: boolean;
    notes: string | null;
    created_at_diff?: string | null;
};

/** Cliente shape compatta per il ClientPicker (no notes). */
export type ClientForPicker = {
    id: number;
    name: string;
    vat_number: string | null;
    tax_code: string | null;
    bank_withholding: boolean;
};

/** Riga della lista fatture (Index.vue). */
export type InvoiceListItem = {
    id: number;
    number: string;
    issued_at: string | null;
    amount: number;
    inarcassa_amount: number;
    stamp_amount: number;
    art_15_amount: number;
    total: number;
    bank_withholding: boolean;
    client: {
        id: number;
        name: string;
    };
};

/**
 * Divergenza tra valore estratto da XML e valore atteso dal calcolatore
 * standard (regime Inarcassa 4%, bollo €2 sopra €77,47). Segnala probabile
 * errore del gestionale o regime non standard. Mostrata inline accanto
 * al campo nella card anteprima.
 */
export type ImportDiscrepancy = {
    xml: number;
    expected: number;
    delta: number;
};

/**
 * Anteprima di una fattura XML parsata server-side, restituita dal
 * controller di import. Quando `parsed=false`, solo `filename` + `error`
 * sono valorizzati; il frontend mostra la card come scartata.
 */
export type ImportPreviewServer = {
    filename: string;
    parsed: boolean;
    error?: string;
    number?: string;
    issued_at?: string;
    amount?: number;
    inarcassa_amount?: number;
    stamp_amount?: number;
    art_15_amount?: number;
    bank_withholding?: boolean;
    client_from_xml?: {
        name: string;
        vat_number: string | null;
        tax_code: string | null;
    };
    matched_client_id?: number | null;
    /** Mappa per campo → divergenza. Solo i campi con `delta > 0.01`. */
    discrepancies?: Partial<Record<'stamp_amount' | 'inarcassa_amount', ImportDiscrepancy>>;
};

/** Payload upload XML batch → POST /invoices/import/parse. */
export type ImportXmlUploadPayload = { files: File[] };

/** Singola fattura nel payload di conferma import. */
export type ImportInvoiceSubmit = {
    number: string;
    issued_at: string;
    amount: string;
    inarcassa_amount: string;
    stamp_amount: string;
    art_15_amount: string;
    bank_withholding: boolean;
    client_mode: 'existing' | 'new';
    existing_client_id: number | null;
    new_client: {
        name: string;
        vat_number: string | null;
        tax_code: string | null;
        bank_withholding: boolean;
    } | null;
};

/** Payload conferma import batch → POST /invoices/import. */
export type ImportInvoicesSubmitPayload = { previews: ImportInvoiceSubmit[] };

/** Fattura completa per Show / Edit. */
export type Invoice = {
    id: number;
    number: string;
    issued_at: string | null;
    amount: number;
    inarcassa_amount: number;
    stamp_amount: number;
    art_15_amount: number;
    bank_withholding: boolean;
    total: number;
    withholding_amount: number;
    client: ClientForPicker;
};

/** Riga della lista anni (years/Index.vue). */
export type YearListItem = {
    id: number;
    year: number;
    profitability_coefficient: number;
    pre_opened: boolean;
    expenses_count: number;
    deadlines_count: number;
};

/** Voce di spesa nel piano di apertura anno (copia editabile del template). */
export type YearPlanExpense = {
    expense_item_id: number | null;
    name: string;
    calculation_type: ExpenseCalculationType;
    rate: number | null;
    minimum: number | null;
    maximum: number | null;
    amount: number | null;
    previous_year_credit: number | null;
};

/**
 * Riga spesa editabile nel wizard apertura anno: come YearPlanExpense ma con
 * i campi numerici grezzi (`number | string`, vuoto = '') e il flag di
 * inclusione. Condivisa tra OpenYearDialog e WizardExpensesStep.
 */
export type YearWizardExpense = {
    expense_item_id: number | null;
    name: string;
    calculation_type: ExpenseCalculationType;
    rate: number | string;
    minimum: number | string;
    maximum: number | string;
    amount: number | string;
    included: boolean;
};

/** Scadenza nel piano di apertura anno (con data calcolata). */
export type YearPlanDeadline = {
    recurring_deadline_id: number | null;
    name: string;
    due_at: string;
    kind: DeadlineKind;
    expense_item_id: number | null;
    expense_year_offset: ExpenseYearOffset;
};

/**
 * Piano editabile prodotto da YearOpeningPlanner e consumato dal wizard
 * (years/OpenWizard.vue). `cross_year_deadlines` elenca le scadenze che
 * referenziano l'anno N+1; `next_year_needs_preopen` è true quando N+1 va
 * pre-aperto.
 */
export type YearPlan = {
    year: number;
    profitability_coefficient: number;
    note: string | null;
    expenses: YearPlanExpense[];
    deadlines: YearPlanDeadline[];
    cross_year_deadlines: string[];
    next_year: number;
    next_year_exists: boolean;
    next_year_needs_preopen: boolean;
};

/** Spesa annuale nella vista anno (years/Show.vue). */
export type YearShowExpense = {
    id: number;
    name: string;
    calculation_type: ExpenseCalculationType;
    calculation_type_label: string;
    rate: number | null;
    minimum: number | null;
    maximum: number | null;
    amount: number | null;
};

/** Vista anno (placeholder Fase 6, KPI fiscali in Fase 9). */
export type YearShow = {
    id: number;
    year: number;
    profitability_coefficient: number;
    pre_opened: boolean;
    note: string | null;
    deadlines_count: number;
    expenses: YearShowExpense[];
};

/**
 * Shape della paginazione Laravel `->paginate()` (default, senza API
 * Resources). Generic su T per riusare su Invoices, Payments, ecc.
 */
export type PaginatedList<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    links: { url: string | null; label: string; active: boolean }[];
};

/** Pagamento collegato a una scadenza (riga lista scadenze). */
export type DeadlinePayment = {
    id: number;
    status: PaymentStatus;
    status_label: string;
    amount: number | null;
    paid_at: string | null;
};

/** Riga della lista scadenze (vista cronologica pluriennale). */
export type DeadlineListItem = {
    id: number;
    name: string;
    due_at: string;
    kind: DeadlineKind;
    kind_label: string;
    quota_type: QuotaType | null;
    quota_type_label: string | null;
    status: DeadlineStatus;
    status_label: string;
    year: number;
    annual_expense_id: number | null;
    annual_expense_name: string | null;
    /** Importo previsto (suggerimento, RB8); null se non calcolabile o adempimento. */
    expected_amount: number | null;
    payment: DeadlinePayment | null;
};

/**
 * Filtri del pannello scadenze (faceted multi-select: array vuoto = nessun
 * filtro). Lo stato vive nel toggle segmentato dell'index.
 */
export type DeadlineFilterState = {
    kind: DeadlineKind[];
    /** Anni di riferimento (spesa). */
    year: number[];
    /** Anni in cui cade la scadenza (due_at). */
    dueYear: number[];
    expenseItemId: number[];
};

/** Toggle stato: open = da fare, closed = completate + non dovute. */
export type DeadlineStateFilter = 'open' | 'closed';

