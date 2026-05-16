<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type ProposalStatusValue = 'draft' | 'sent' | 'accepted' | 'declined';
type ProposalStatusSeverity =
    | 'secondary'
    | 'info'
    | 'success'
    | 'warn'
    | 'danger';

type ProposalItemRecord = {
    id: number;
    name: string;
    price: {
        cents: number;
        formatted: string;
    };
    type: {
        value: 'hourly' | 'fixed';
        label: string;
    };
    quantity: number;
    line_total: {
        cents: number;
        formatted: string;
    };
};

type ProposalRecord = {
    uuid: string;
    title: string;
    client: {
        id: number;
        name: string;
        email: string;
        tax_id: string | null;
        address: string | null;
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
    items: ProposalItemRecord[];
};

type Props = {
    proposal: {
        data: ProposalRecord;
    };
    transitionUrl: string;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const proposal = computed(() => props.proposal.data);
const canRespond = computed(() => proposal.value.status.value === 'sent');

const statusSeverity: Record<ProposalStatusValue, ProposalStatusSeverity> = {
    draft: 'secondary',
    sent: 'info',
    accepted: 'success',
    declined: 'danger',
};

function transition(action: 'accepted' | 'declined'): void {
    router.post(
        props.transitionUrl,
        { action },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="proposal.title" />

    <article
        class="overflow-hidden rounded-md border border-surface-200 bg-surface-0 shadow-sm dark:border-surface-700 dark:bg-surface-900"
    >
        <header class="border-b border-surface-200 p-6 dark:border-surface-700">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <p
                        class="text-sm font-medium text-surface-500 dark:text-surface-400"
                    >
                        {{ t('ui.proposals.public.eyebrow') }}
                    </p>
                    <h1
                        class="mt-2 text-3xl font-semibold text-surface-950 dark:text-surface-0"
                    >
                        {{ proposal.title }}
                    </h1>
                </div>

                <Tag
                    :value="proposal.status.label"
                    :severity="statusSeverity[proposal.status.value]"
                    rounded
                />
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <p
                        class="text-xs font-medium text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.public.client') }}
                    </p>
                    <p
                        class="mt-1 font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{ proposal.client.name }}
                    </p>
                    <p class="text-sm text-surface-600 dark:text-surface-300">
                        {{ proposal.client.email }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-xs font-medium text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.fields.valid_until') }}
                    </p>
                    <p
                        class="mt-1 font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{
                            proposal.valid_until.formatted ??
                            t('ui.proposals.public.no_expiration')
                        }}
                    </p>
                </div>
            </div>
        </header>

        <section class="p-6">
            <DataTable
                :value="proposal.items"
                data-key="id"
                table-style="min-width: 48rem"
            >
                <Column
                    field="name"
                    :header="t('ui.proposals.items.columns.name')"
                >
                    <template #body="{ data }: { data: ProposalItemRecord }">
                        <span class="font-medium">{{ data.name }}</span>
                    </template>
                </Column>

                <Column :header="t('ui.proposals.items.columns.type')">
                    <template #body="{ data }: { data: ProposalItemRecord }">
                        <Tag
                            :value="data.type.label"
                            :severity="
                                data.type.value === 'fixed'
                                    ? 'info'
                                    : 'secondary'
                            "
                            rounded
                        />
                    </template>
                </Column>

                <Column :header="t('ui.proposals.items.columns.price')">
                    <template #body="{ data }: { data: ProposalItemRecord }">
                        {{ data.price.formatted }}
                    </template>
                </Column>

                <Column :header="t('ui.proposals.items.columns.quantity')">
                    <template #body="{ data }: { data: ProposalItemRecord }">
                        {{ data.quantity }}
                    </template>
                </Column>

                <Column :header="t('ui.proposals.items.columns.subtotal')">
                    <template #body="{ data }: { data: ProposalItemRecord }">
                        <span class="font-medium">
                            {{ data.line_total.formatted }}
                        </span>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-10 text-center text-sm text-surface-500">
                        {{ t('ui.proposals.items.empty') }}
                    </div>
                </template>
            </DataTable>
        </section>

        <footer
            class="border-t border-surface-200 bg-surface-50 p-6 dark:border-surface-700 dark:bg-surface-950"
        >
            <div
                class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-surface-500 dark:text-surface-400">
                        {{ t('ui.proposals.total.title') }}
                    </p>
                    <p class="text-4xl font-semibold text-green-300">
                        {{ proposal.total.formatted }}
                    </p>
                </div>

                <div v-if="canRespond" class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        severity="success"
                        size="large"
                        @click="transition('accepted')"
                    >
                        <Check class="size-5" />
                        <span>{{ t('ui.proposals.public.accept') }}</span>
                    </Button>
                    <Button
                        severity="danger"
                        size="large"
                        variant="outlined"
                        @click="transition('declined')"
                    >
                        <X class="size-5" />
                        <span>{{ t('ui.proposals.public.decline') }}</span>
                    </Button>
                </div>

                <div v-else class="max-w-sm">
                    <p class="text-sm text-surface-500 dark:text-surface-400">
                        {{ t('ui.proposals.public.final_status') }}
                    </p>
                    <Tag
                        class="mt-2"
                        :value="proposal.status.label"
                        :severity="statusSeverity[proposal.status.value]"
                        rounded
                    />
                </div>
            </div>
        </footer>
    </article>
</template>
