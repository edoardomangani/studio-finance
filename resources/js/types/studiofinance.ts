/**
 * Tipi dominio Studiofinance (entità fiscali). Una sola fonte di verità,
 * importati dove servono (Onboarding, Settings, ecc.).
 */

export type ProfessionalProfile = {
    coefficiente_redditivita: string | number;
    anno_inizio_attivita: number;
};

export type TipoCalcoloVoceSpesa =
    | 'perc_reddito_irpef'
    | 'perc_volume_affari_iva'
    | 'fissa_annuale'
    | 'somma_bolli';

export type TipoScadenza = 'pagamento' | 'adempimento';

export type AnnoRiferimentoSpesa = 'corrente' | 'successivo';

export type VoceSpesa = {
    id: number;
    nome: string;
    tipo_calcolo: TipoCalcoloVoceSpesa;
    tipo_calcolo_label: string;
    aliquota_default: string | number | null;
    minimale_default: string | number | null;
    massimale_default: string | number | null;
    quota_default: string | number | null;
    attiva: boolean;
    ordine: number;
};

export type ScadenzaTipo = {
    id: number;
    nome: string;
    giorno: number;
    mese: number;
    tipo: TipoScadenza;
    tipo_label: string;
    voce_spesa_id: number | null;
    voce_spesa_nome: string | null;
    anno_data_scadenza: AnnoDataScadenza;
    anno_data_label: string;
    anno_riferimento_spesa: AnnoRiferimentoSpesa;
    anno_riferimento_label: string;
    attiva: boolean;
};

export type AnnoDataScadenza = 'corrente' | 'successivo';

export type EnumOption = { value: string; label: string };
