export type ProposalStatusValue =
    | 'draft'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'declined';

export type ProposalStatusSeverity =
    | 'secondary'
    | 'info'
    | 'success'
    | 'warn'
    | 'danger';

export type ProposalRecord = {
    id: number;
    uuid: string;
    title: string;
    client: {
        id: number;
        name: string;
        email: string;
    };
    status: {
        value: ProposalStatusValue;
        label: string;
    };
    valid_until: {
        value: string | null;
        formatted: string | null;
    };
    total: {
        cents: number;
        formatted: string;
    };
    finalized_metadata: {
        accepted_by_name: string | null;
        refusal_reason: string | null;
    };
    urls: {
        edit: string;
        destroy: string;
        send: string;
        public: string;
    };
};
