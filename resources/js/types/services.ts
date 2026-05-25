export type ServiceRecord = {
    id: number;
    name: string;
    description: string | null;
    price: {
        cents: number;
        formatted: string;
    };
    type: {
        value: 'hourly' | 'fixed';
        label: string;
    };
    urls: {
        edit: string;
    };
};
