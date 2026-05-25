export type ClientRecord = {
    id: number;
    name: string;
    email: string;
    tax_id: string | null;
    address: string | null;
    urls: {
        edit: string;
    };
};
