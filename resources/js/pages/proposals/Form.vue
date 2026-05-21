<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';

type ServiceTypeValue = 'hourly' | 'fixed';
type ProposalStatusValue =
    | 'draft'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'declined';

type ClientOption = {
    id: number;
    name: string;
};

type ServiceOption = {
    id: number;
    name: string;
    price: {
        cents: number;
        formatted: string;
    };
    type: {
        value: ServiceTypeValue;
        label: string;
    };
};

type ProposalItemRecord = {
    id: number;
    name: string;
    price: {
        cents: number;
        formatted: string;
    };
    type: {
        value: ServiceTypeValue;
        label: string;
    };
    quantity: number;
};

type ProposalRecord = {
    id: number;
    title: string;
    client_id: number;
    status: {
        value: ProposalStatusValue;
        label: string;
    };
    valid_until: {
        value: string | null;
        formatted: string | null;
    };
    items: ProposalItemRecord[];
};

type ProposalFormItem = {
    name: string;
    price: number | null;
    type: ServiceTypeValue;
    typeLabel: string;
    quantity: number | null;
};

type ProposalForm = {
    title: string;
    client_id: number | null;
    valid_until: string | null;
    items: ProposalFormItem[];
};

type Props = {
    proposal: {
        data: ProposalRecord;
    } | null;
    clients: {
        data: ClientOption[];
    };
    services: {
        data: ServiceOption[];
    };
};

const props = defineProps<Props>();

const { t } = useTranslations();
const proposalsUrl = '/proposals';
const selectedServiceId = ref<number | null>(null);

const proposal = computed(() => props.proposal?.data ?? null);
const isEditing = computed(() => proposal.value !== null);
const isLocked = computed(
    () => isEditing.value && proposal.value?.status.value !== 'draft',
);

const form = useForm<ProposalForm>({
    title: proposal.value?.title ?? '',
    client_id: proposal.value?.client_id ?? null,
    valid_until: proposal.value?.valid_until.value ?? null,
    items: proposal.value?.items.map(mapProposalItemToForm) ?? [],
});

const pageTitle = computed(() =>
    isEditing.value
        ? t('ui.proposals.form.edit_head')
        : t('ui.proposals.form.create_head'),
);

const totalAmount = computed(() =>
    form.items.reduce((total, item) => total + itemSubtotal(item), 0),
);

const totalFormatted = computed(() => formatCurrency(totalAmount.value));

const validUntilDate = computed<Date | null>({
    get: () => parseDateString(form.valid_until),
    set: (date) => {
        form.valid_until = formatDateForBackend(date);
    },
});

watch(selectedServiceId, (serviceId) => {
    if (serviceId === null) {
        return;
    }

    const service = props.services.data.find(
        (option) => option.id === serviceId,
    );

    if (!service) {
        selectedServiceId.value = null;

        return;
    }

    form.items.push({
        name: service.name,
        price: centsToAmount(service.price.cents),
        type: service.type.value,
        typeLabel: service.type.label,
        quantity: 1,
    });
    selectedServiceId.value = null;
});

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.proposals.index.head'),
                href: proposalsUrl,
            },
            {
                title: pageTitle.value,
                href:
                    isEditing.value && proposal.value
                        ? `${proposalsUrl}/${proposal.value.id}/edit`
                        : `${proposalsUrl}/create`,
            },
        ],
    });
});

function mapProposalItemToForm(item: ProposalItemRecord): ProposalFormItem {
    return {
        name: item.name,
        price: centsToAmount(item.price.cents),
        type: item.type.value,
        typeLabel: item.type.label,
        quantity: item.quantity,
    };
}

function centsToAmount(cents: number): number {
    return cents / 100;
}

function amountToCents(amount: number | null): number {
    return Math.round((amount ?? 0) * 100);
}

function itemSubtotal(item: ProposalFormItem): number {
    return (item.price ?? 0) * (item.quantity ?? 0);
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function parseDateString(value: string | null): Date | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day);
}

function formatDateForBackend(date: Date | null): string | null {
    if (!date) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function itemError(
    index: number,
    field: keyof Pick<ProposalFormItem, 'name' | 'price' | 'type' | 'quantity'>,
): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `items.${index}.${field}`
    ];
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function submit(): void {
    if (isLocked.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            name: item.name,
            price: amountToCents(item.price),
            type: item.type,
            quantity: item.quantity ?? 1,
        })),
    }));

    if (isEditing.value && proposal.value) {
        form.put(`${proposalsUrl}/${proposal.value.id}`);

        return;
    }

    form.post(proposalsUrl);
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex max-w-6xl flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="
                    isEditing
                        ? t('ui.proposals.form.edit_title')
                        : t('ui.proposals.form.create_title')
                "
                :description="
                    isEditing
                        ? t('ui.proposals.form.edit_description')
                        : t('ui.proposals.form.create_description')
                "
            />

            <Link :href="proposalsUrl" class="shrink-0">
                <Button severity="secondary" variant="text">
                    <ArrowLeft class="size-4" />
                    <span>{{ t('ui.actions.back') }}</span>
                </Button>
            </Link>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="space-y-6">
                    <div class="grid gap-2">
                        <label for="title" class="text-sm font-medium">
                            {{ t('ui.proposals.fields.title') }}
                        </label>
                        <InputText
                            id="title"
                            v-model="form.title"
                            :invalid="Boolean(form.errors.title)"
                            :placeholder="t('ui.proposals.placeholders.title')"
                            :disabled="isLocked"
                            fluid
                            required
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <label for="client_id" class="text-sm font-medium">
                                {{ t('ui.proposals.fields.client') }}
                            </label>
                            <Select
                                id="client_id"
                                v-model="form.client_id"
                                :options="props.clients.data"
                                option-label="name"
                                option-value="id"
                                :invalid="Boolean(form.errors.client_id)"
                                :placeholder="
                                    t('ui.proposals.placeholders.client')
                                "
                                :disabled="isLocked"
                                filter
                                fluid
                            />
                            <InputError :message="form.errors.client_id" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="valid_until"
                                class="text-sm font-medium"
                            >
                                {{ t('ui.proposals.fields.valid_until') }}
                            </label>
                            <DatePicker
                                id="valid_until"
                                v-model="validUntilDate"
                                date-format="yy-mm-dd"
                                :invalid="Boolean(form.errors.valid_until)"
                                :disabled="isLocked"
                                show-icon
                                show-button-bar
                                fluid
                            />
                            <InputError :message="form.errors.valid_until" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-md border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900"
                >
                    <p
                        class="text-sm font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{ t('ui.proposals.total.title') }}
                    </p>
                    <p class="mt-2 text-3xl font-semibold text-green-300">
                        {{ totalFormatted }}
                    </p>
                    <p
                        class="mt-2 text-sm text-surface-500 dark:text-surface-400"
                    >
                        {{ t('ui.proposals.total.description') }}
                    </p>
                </div>
            </div>

            <section class="space-y-4">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-semibold">
                            {{ t('ui.proposals.items.title') }}
                        </h2>
                        <p
                            class="text-sm text-surface-500 dark:text-surface-400"
                        >
                            {{ t('ui.proposals.items.description') }}
                        </p>
                    </div>

                    <div class="grid gap-2 sm:w-80">
                        <label for="service_id" class="text-sm font-medium">
                            {{ t('ui.proposals.fields.catalog_service') }}
                        </label>
                        <Select
                            id="service_id"
                            v-model="selectedServiceId"
                            :options="props.services.data"
                            option-label="name"
                            option-value="id"
                            :placeholder="
                                t('ui.proposals.placeholders.catalog_service')
                            "
                            :disabled="isLocked"
                            filter
                            fluid
                        >
                            <template
                                #option="{ option }: { option: ServiceOption }"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ option.name }}
                                    </p>
                                    <p
                                        class="text-xs text-surface-500 dark:text-surface-400"
                                    >
                                        {{ option.price.formatted }} ·
                                        {{ option.type.label }}
                                    </p>
                                </div>
                            </template>
                        </Select>
                    </div>
                </div>

                <InputError :message="form.errors.items" />

                <div
                    class="overflow-hidden rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
                >
                    <div
                        class="hidden grid-cols-[minmax(14rem,1fr)_7rem_12rem_9rem_10rem_4rem] gap-4 border-b border-surface-200 px-4 py-3 text-xs font-medium text-surface-500 uppercase lg:grid dark:border-surface-700 dark:text-surface-400"
                    >
                        <span>{{ t('ui.proposals.items.columns.name') }}</span>
                        <span>{{ t('ui.proposals.items.columns.type') }}</span>
                        <span>{{ t('ui.proposals.items.columns.price') }}</span>
                        <span>{{
                            t('ui.proposals.items.columns.quantity')
                        }}</span>
                        <span>{{
                            t('ui.proposals.items.columns.subtotal')
                        }}</span>
                        <span class="sr-only">
                            {{ t('ui.proposals.items.columns.actions') }}
                        </span>
                    </div>

                    <div
                        v-if="form.items.length === 0"
                        class="py-10 text-center text-sm text-surface-500 dark:text-surface-400"
                    >
                        {{ t('ui.proposals.items.empty') }}
                    </div>

                    <div
                        v-for="(item, index) in form.items"
                        :key="`${item.name}-${index}`"
                        class="grid gap-4 border-b border-surface-200 p-4 last:border-b-0 lg:grid-cols-[minmax(14rem,1fr)_7rem_12rem_9rem_10rem_4rem] lg:items-start dark:border-surface-700"
                    >
                        <div class="grid gap-2">
                            <label
                                :for="`items_${index}_name`"
                                class="text-xs font-medium text-surface-500 uppercase lg:hidden dark:text-surface-400"
                            >
                                {{ t('ui.proposals.items.columns.name') }}
                            </label>
                            <InputText
                                :id="`items_${index}_name`"
                                v-model="item.name"
                                :invalid="Boolean(itemError(index, 'name'))"
                                :disabled="isLocked"
                                fluid
                            />
                            <InputError :message="itemError(index, 'name')" />
                        </div>

                        <div class="grid gap-2">
                            <span
                                class="text-xs font-medium text-surface-500 uppercase lg:hidden dark:text-surface-400"
                            >
                                {{ t('ui.proposals.items.columns.type') }}
                            </span>
                            <Tag
                                :value="item.typeLabel"
                                :severity="
                                    item.type === 'fixed' ? 'info' : 'secondary'
                                "
                                rounded
                            />
                            <InputError :message="itemError(index, 'type')" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                :for="`items_${index}_price`"
                                class="text-xs font-medium text-surface-500 uppercase lg:hidden dark:text-surface-400"
                            >
                                {{ t('ui.proposals.items.columns.price') }}
                            </label>
                            <InputNumber
                                :id="`items_${index}_price`"
                                v-model="item.price"
                                :invalid="Boolean(itemError(index, 'price'))"
                                mode="currency"
                                currency="USD"
                                locale="en-US"
                                :min="0"
                                :min-fraction-digits="2"
                                :max-fraction-digits="2"
                                :disabled="isLocked"
                                fluid
                            />
                            <InputError :message="itemError(index, 'price')" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                :for="`items_${index}_quantity`"
                                class="text-xs font-medium text-surface-500 uppercase lg:hidden dark:text-surface-400"
                            >
                                {{ t('ui.proposals.items.columns.quantity') }}
                            </label>
                            <InputNumber
                                :id="`items_${index}_quantity`"
                                v-model="item.quantity"
                                :invalid="Boolean(itemError(index, 'quantity'))"
                                :min="1"
                                :disabled="isLocked"
                                show-buttons
                                fluid
                            />
                            <InputError
                                :message="itemError(index, 'quantity')"
                            />
                        </div>

                        <div class="grid gap-2">
                            <span
                                class="text-xs font-medium text-surface-500 uppercase lg:hidden dark:text-surface-400"
                            >
                                {{ t('ui.proposals.items.columns.subtotal') }}
                            </span>
                            <span
                                class="font-medium text-surface-950 dark:text-surface-0"
                            >
                                {{ formatCurrency(itemSubtotal(item)) }}
                            </span>
                        </div>

                        <div class="flex justify-end">
                            <Button
                                type="button"
                                severity="danger"
                                variant="text"
                                :aria-label="t('ui.proposals.items.remove')"
                                :disabled="isLocked"
                                @click="removeItem(index)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <div
                class="flex flex-col gap-4 border-t border-surface-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-surface-700"
            >
                <div>
                    <p class="text-sm text-surface-500 dark:text-surface-400">
                        {{ t('ui.proposals.total.title') }}
                    </p>
                    <p class="text-2xl font-semibold text-green-300">
                        {{ totalFormatted }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <Link :href="proposalsUrl">
                        <Button severity="secondary" variant="text">
                            {{ t('ui.actions.cancel') }}
                        </Button>
                    </Link>
                    <Button
                        type="submit"
                        :disabled="form.processing || isLocked"
                    >
                        <Save class="size-4" />
                        <span>{{ t('ui.proposals.actions.save') }}</span>
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
