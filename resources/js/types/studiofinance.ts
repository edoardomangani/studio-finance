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

export type DueYearOffset = 'current' | 'next';
export type ExpenseYearOffset = 'current' | 'next';

export type ExpenseItem = {
    id: number;
    name: string;
    calculation_type: ExpenseCalculationType;
    calculation_type_label: string;
    default_rate: string | number | null;
    default_minimum: string | number | null;
    default_maximum: string | number | null;
    default_amount: string | number | null;
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
    active: boolean;
};

export type EnumOption = { value: string; label: string };
