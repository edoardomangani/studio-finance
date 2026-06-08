/**
 * Tipi dominio Studiofinance (entità fiscali). Una sola fonte di verità,
 * importati dove servono (Onboarding, Settings, ecc.).
 */

/** Riga uniforme della vista Archivio (qualsiasi entità soft-deletata). */
export type ArchiveRow = {
    id: number;
    name: string;
    detail: string | null;
    amount: number | null;
    archived_at: string | null;
};

/** Tipo (segmento URL) di un'entità archiviabile. */
export type ArchiveType =
    | 'clients'
    | 'invoices'
    | 'expense-items'
    | 'recurring-deadlines'
    | 'payments';

/** Payload della pagina Archivio: righe cestinate per tipo di entità. */
export type ArchiveData = Record<ArchiveType, ArchiveRow[]>;

export type ProfessionalProfile = {
    profitability_coefficient: string | number;
    business_start_year: number;
};

export type ExpenseCalculationType =
    | 'percentage_of_irpef_income'
    | 'percentage_of_iva_revenue'
    | 'fixed_annual'
    | 'sum_of_bolli';

/** Tipo fiscale ("famiglia") di una voce di spesa. */
export type ExpenseKind = 'tax' | 'pension' | 'stamp_duty' | 'fixed';

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
    kind: ExpenseKind;
    family_name: string;
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
    stamp_charged_to_client: boolean;
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
    discrepancies?: Partial<
        Record<'stamp_amount' | 'inarcassa_amount', ImportDiscrepancy>
    >;
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
    stamp_charged_to_client: boolean;
    art_15_amount: number;
    bank_withholding: boolean;
    total: number;
    withholding_amount: number;
    client: ClientForPicker;
};

/** Riga della tabella confronto anni (years/Compare.vue), KPI a due focus. */
export type YearListItem = {
    id: number;
    year: number;
    profitability_coefficient: number;
    pre_opened: boolean;
    /** past = chiuso, current = in corso (importi stima), future = pre-aperto. */
    time_state: 'past' | 'current' | 'future';
    expenses_count: number;
    deadlines_count: number;
    // Focus Personale.
    invoice_total: number;
    expenses: number;
    net: number;
    paid: number;
    due: number;
    // Focus UNICO (dichiarazione).
    vat_turnover: number;
    irpef_income_net: number;
    pension_contributions_paid: number;
    imposta_sostitutiva: number;
    withholdings: number;
    previous_year_credit: number;
    bank_income: number;
    bank_income_monthly: number;
};

/** Voce di spesa nel piano di apertura anno (copia editabile del template). */
export type YearPlanExpense = {
    expense_item_id: number | null;
    name: string;
    calculation_type: ExpenseCalculationType;
    kind: ExpenseKind;
    rate: number | null;
    minimum: number | null;
    maximum: number | null;
    amount: number | null;
    previous_year_credit: number | null;
};

/**
 * Riga spesa editabile nel wizard apertura anno: come YearPlanExpense ma con
 * i campi numerici grezzi (`number | string`, vuoto = '') e il flag di
 * inclusione. Condivisa tra la pagina di apertura anno e WizardExpensesStep.
 */
export type YearWizardExpense = {
    expense_item_id: number | null;
    name: string;
    calculation_type: ExpenseCalculationType;
    kind: ExpenseKind;
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

/**
 * FormulaBlock (9.a): dati strutturati della derivazione di una spesa. La
 * frase leggibile la compone il frontend; `base`/`rate`/`fixed_amount` sono
 * null secondo il tipo di calcolo.
 */
export type YearExpenseFormula = {
    calculation_type: ExpenseCalculationType;
    base_label: string | null;
    base: number | null;
    rate: number | null;
    minimum: number | null;
    maximum: number | null;
    capped_base: number | null;
    fixed_amount: number | null;
    calculated: number;
    deductions: number;
    manual: number | null;
    definitive: number;
};

/** Spesa annuale nella vista anno (tab Spese), con famiglia importi e formula. */
export type YearShowExpense = {
    id: number;
    name: string;
    calculation_type: ExpenseCalculationType;
    calculation_type_label: string;
    rate: number | null;
    minimum: number | null;
    maximum: number | null;
    amount: number | null;
    kind: ExpenseKind;
    family_name: string;
    is_imposta_sostitutiva: boolean;
    previous_year_credit: number | null;
    /** una-tantum (creata a mano, senza template): eliminabile. */
    is_custom: boolean;
    // Famiglia importi (RB12).
    expected: number;
    calculated: number;
    deductions: number;
    manual: number | null;
    definitive: number;
    amount_to_date: number;
    paid: number;
    due: number;
    due_to_date: number;
    formula: YearExpenseFormula;
};

/** Spesa col previsto del mese (riga mensile). */
export type YearMonthExpense = { id: number; name: string; expected: number };

/** Riga mensile della panoramica (12 mesi). */
export type YearMonth = {
    month: number;
    taxable_amount: number;
    stamp_duty: number;
    vat_turnover: number;
    irpef_income: number;
    invoice_total: number;
    total_expenses: number;
    net: number;
    net_to_gross_ratio: number;
    invoices: InvoiceListItem[];
    expenses: YearMonthExpense[];
};

/** Totali d'anno (KPI + righe spesa sommate). */
export type YearTotals = {
    taxable_amount: number;
    stamp_duty: number;
    vat_turnover: number;
    irpef_income_gross: number;
    irpef_income_net: number;
    pension_contributions_paid: number;
    withholdings: number;
    imposta_sostitutiva: number;
    previous_year_credit: number;
    invoice_total: number;
    expenses_expected: number;
    expenses_definitive: number;
    expenses_paid: number;
    expenses_due: number;
    /** "A oggi": maturato e scoperto attuale (= maturato − pagato). */
    expenses_amount_to_date: number;
    expenses_due_to_date: number;
    net: number;
    /** Netto a oggi (anno in corso): fatturato − maturato a oggi. */
    net_to_date: number;
    bank_income: number;
};

/** Stato dell'anno N+1 per l'azione "Apri prossimo anno" + stato temporale. */
export type YearMeta = {
    next_year: number;
    can_open_next: boolean;
    /** past = chiuso, current = in corso, future = aperto in anticipo. */
    time_state: 'past' | 'current' | 'future';
};

/** Voce dello switcher anni. */
export type YearNavItem = { year: number; pre_opened: boolean };

/** Vista anno (cockpit, Fase 9): meta, switcher, mensile, totali, entità. */
export type YearShow = {
    id: number;
    year: number;
    profitability_coefficient: number;
    pre_opened: boolean;
    note: string | null;
    deadlines_count: number;
    invoices: InvoiceListItem[];
    months: YearMonth[];
    totals: YearTotals;
    expenses: YearShowExpense[];
    deadlines: DeadlineListItem[];
    payments: PaymentListItem[];
    meta: YearMeta;
    years_nav: YearNavItem[];
    /** Mappa kind → nome famiglia (rinominabili), per i picker. */
    families: Record<ExpenseKind, string>;
};

/* ── Dashboard generale (Fase 9.b) ─────────────────────────────────────── */

/** Mese in corso: stipendio (netto), fatturato, spese, delta vs anno prima. */
export type DashboardThisMonth = {
    month: number;
    net: number;
    invoice_total: number;
    expenses: number;
    yoy_percent: number | null;
};

/** I due numeri cross-anno di "Da coprire": competenza vs cassa. */
export type DashboardToCover = {
    expenses_due_to_date: number;
    paid_to_date: number;
    deadlines_due: number;
    open_deadlines_count: number;
};

/** Pannello anno: cumulato, mesi trascorsi (progress), proiezione, netto bancario. */
export type DashboardYear = {
    year: number;
    invoice_total: number;
    months_elapsed: number;
    projection: number;
    bank_income: number;
};

/** Una voce di spesa del mese (accantonamento del mese, per singola spesa). */
export type DashboardMonthExpense = { kind: ExpenseKind; label: string; amount: number };

/** Scadenza aperta nella lista dashboard (prossime per data). */
export type DashboardDeadline = {
    id: number;
    name: string;
    due_at: string;
    year: number | null;
    kind_label: string;
    expected_amount: number | null;
};

export type DashboardRecentInvoice = {
    id: number;
    number: string;
    issued_at: string;
    total: number;
    client_name: string | null;
};

export type DashboardRecentPayment = {
    id: number;
    description: string | null;
    annual_expense_name: string | null;
    amount: number;
    paid_at: string | null;
};

/** Payload dashboard: empty state (`has_data: false`) o pieno. */
export type DashboardData =
    | { has_data: false }
    | {
          has_data: true;
          calendar_year: number;
          calendar_month: number;
          current_year: number;
          this_month: DashboardThisMonth | null;
          to_cover: DashboardToCover;
          year: DashboardYear;
          month_expenses: DashboardMonthExpense[];
          open_deadlines: DashboardDeadline[];
          recent_invoices: DashboardRecentInvoice[];
          recent_payments: DashboardRecentPayment[];
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

/** Riga del registro pagamenti (solo `paid`: fatti di cassa, RB9). */
export type PaymentListItem = {
    id: number;
    description: string | null;
    annual_expense_id: number;
    annual_expense_name: string | null;
    expense_year: number | null;
    amount: number;
    paid_at: string | null;
    /** deadline_id null = pagamento manuale extra-scadenza (F8). */
    is_manual: boolean;
};

/** Spesa annuale compatta per l'autocomplete (pagamento manuale, scadenza ad-hoc). */
export type AnnualExpenseForPicker = {
    id: number;
    name: string;
    year: number;
};

/** Anno come opzione {id, year} per le select (scadenza ad-hoc di adempimento). */
export type YearOption = {
    id: number;
    year: number;
};

/**
 * Filtri del pannello registro pagamenti (faceted multi-select: array vuoto =
 * nessun filtro). I due "anni rilevanti" di RB9.
 */
export type PaymentFilterState = {
    /** Anni di riferimento (spesa). */
    expenseYear: number[];
    /** Anni della data di cassa (paid_at). */
    paidYear: number[];
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
    /** recurring_deadline_id null = scadenza ad-hoc: editabile + archiviabile. */
    is_custom: boolean;
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
