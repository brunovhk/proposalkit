<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { ExternalLink, Eye, Pencil, Plus, Send, Trash2 } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';

type ProposalStatusValue =
    | 'draft'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'declined';
type ProposalStatusSeverity =
    | 'secondary'
    | 'info'
    | 'success'
    | 'warn'
    | 'danger';

type ProposalRecord = {
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
    urls: {
        edit: string;
        destroy: string;
        send: string;
        public: string;
    };
};

type Props = {
    proposals: {
        data: ProposalRecord[];
    };
};

const props = defineProps<Props>();

const { t } = useTranslations();
const proposalPendingDelete = ref<ProposalRecord | null>(null);
const proposalPendingDetail = ref<ProposalRecord | null>(null);

const proposalsUrl = '/proposals';
const createProposalUrl = '/proposals/create';

const deleteDialogVisible = computed({
    get: () => proposalPendingDelete.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            proposalPendingDelete.value = null;
        }
    },
});

const detailDialogVisible = computed({
    get: () => proposalPendingDetail.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            proposalPendingDetail.value = null;
        }
    },
});

const statusSeverity: Record<ProposalStatusValue, ProposalStatusSeverity> = {
    draft: 'secondary',
    sent: 'info',
    viewed: 'warn',
    accepted: 'success',
    declined: 'danger',
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.proposals.index.head'),
                href: proposalsUrl,
            },
        ],
    });
});

function requestDelete(proposal: ProposalRecord): void {
    proposalPendingDelete.value = proposal;
}

function requestDetail(proposal: ProposalRecord): void {
    proposalPendingDetail.value = proposal;
}

function canViewPublicLink(status: ProposalStatusValue): boolean {
    return ['sent', 'viewed'].includes(status);
}

function canViewInternalDetails(status: ProposalStatusValue): boolean {
    return ['accepted', 'declined'].includes(status);
}

function sendProposal(proposal: ProposalRecord): void {
    router.post(proposal.urls.send, undefined, {
        preserveScroll: true,
    });
}

function publicProposalUrl(proposal: ProposalRecord): string {
    return proposal.urls.public;
}

function destroyProposal(): void {
    if (!proposalPendingDelete.value) {
        return;
    }

    router.delete(proposalPendingDelete.value.urls.destroy, {
        preserveScroll: true,
        onFinish: () => {
            proposalPendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.proposals.index.head')" />

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('ui.proposals.index.title')"
                :description="t('ui.proposals.index.description')"
            />

            <Link :href="createProposalUrl" class="shrink-0">
                <Button>
                    <Plus class="size-4" />
                    <span>{{ t('ui.proposals.actions.create') }}</span>
                </Button>
            </Link>
        </div>

        <div
            class="overflow-hidden rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
        >
            <DataTable
                :value="props.proposals.data"
                data-key="id"
                :paginator="props.proposals.data.length > 10"
                :rows="10"
                table-style="min-width: 58rem"
            >
                <Column field="title" :header="t('ui.proposals.table.title')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <p
                            class="truncate font-medium text-surface-950 dark:text-surface-0"
                        >
                            {{ data.title }}
                        </p>
                    </template>
                </Column>

                <Column :header="t('ui.proposals.table.client')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-surface-800 dark:text-surface-100"
                            >
                                {{ data.client.name }}
                            </p>
                            <p
                                class="truncate text-xs text-surface-500 dark:text-surface-400"
                            >
                                {{ data.client.email }}
                            </p>
                        </div>
                    </template>
                </Column>

                <Column :header="t('ui.proposals.table.status')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <Tag
                            :value="data.status.label"
                            :severity="statusSeverity[data.status.value]"
                            rounded
                        />
                    </template>
                </Column>

                <Column :header="t('ui.proposals.table.total')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <span class="font-medium text-green-300">
                            {{ data.total.formatted }}
                        </span>
                    </template>
                </Column>

                <Column :header="t('ui.proposals.table.valid_until')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <span
                            class="text-sm text-surface-600 dark:text-surface-300"
                        >
                            {{ data.valid_until.formatted ?? '' }}
                        </span>
                    </template>
                </Column>

                <Column :header="t('ui.proposals.table.actions')">
                    <template #body="{ data }: { data: ProposalRecord }">
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="data.status.value === 'draft'"
                                severity="success"
                                variant="text"
                                :aria-label="t('ui.proposals.actions.send')"
                                @click="sendProposal(data)"
                            >
                                <Send class="size-4" />
                            </Button>
                            <Link
                                v-if="data.status.value === 'draft'"
                                :href="data.urls.edit"
                            >
                                <Button
                                    severity="secondary"
                                    variant="text"
                                    :aria-label="t('ui.actions.edit')"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                            </Link>
                            <Button
                                v-if="data.status.value === 'draft'"
                                severity="danger"
                                variant="text"
                                :aria-label="t('ui.proposals.actions.delete')"
                                @click="requestDelete(data)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                            <a
                                v-if="canViewPublicLink(data.status.value)"
                                :href="publicProposalUrl(data)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Button
                                    severity="secondary"
                                    variant="text"
                                    :aria-label="
                                        t('ui.proposals.actions.view_public')
                                    "
                                >
                                    <ExternalLink class="size-4" />
                                </Button>
                            </a>
                            <Button
                                v-if="canViewInternalDetails(data.status.value)"
                                severity="secondary"
                                variant="text"
                                :aria-label="
                                    t('ui.proposals.actions.view_details')
                                "
                                @click="requestDetail(data)"
                            >
                                <Eye class="size-4" />
                            </Button>
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div
                        class="py-10 text-center text-sm text-surface-500 dark:text-surface-400"
                    >
                        {{ t('ui.proposals.empty') }}
                    </div>
                </template>
            </DataTable>
        </div>
    </div>

    <Dialog
        v-model:visible="deleteDialogVisible"
        modal
        :header="
            t('ui.proposals.confirm_delete.title', {
                title: proposalPendingDelete?.title ?? '',
            })
        "
        class="mx-4 w-full max-w-md"
    >
        <p class="text-sm text-surface-600 dark:text-surface-300">
            {{ t('ui.proposals.confirm_delete.body') }}
        </p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    severity="secondary"
                    variant="text"
                    @click="deleteDialogVisible = false"
                >
                    {{ t('ui.actions.cancel') }}
                </Button>
                <Button severity="danger" @click="destroyProposal">
                    <Trash2 class="size-4" />
                    <span>{{ t('ui.actions.delete') }}</span>
                </Button>
            </div>
        </template>
    </Dialog>

    <Dialog
        v-model:visible="detailDialogVisible"
        modal
        :header="t('ui.proposals.detail.title')"
        class="mx-4 w-full max-w-lg"
    >
        <div v-if="proposalPendingDetail" class="grid gap-5">
            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.proposals.fields.title') }}
                </p>
                <h2
                    class="text-xl font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ proposalPendingDetail.title }}
                </h2>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.fields.client') }}
                    </p>
                    <p
                        class="text-sm font-medium break-words text-surface-950 dark:text-surface-0"
                    >
                        {{ proposalPendingDetail.client.name }}
                    </p>
                    <p
                        class="text-xs break-words text-surface-500 dark:text-surface-400"
                    >
                        {{ proposalPendingDetail.client.email }}
                    </p>
                </section>

                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.table.status') }}
                    </p>
                    <div>
                        <Tag
                            :value="proposalPendingDetail.status.label"
                            :severity="
                                statusSeverity[
                                    proposalPendingDetail.status.value
                                ]
                            "
                            rounded
                        />
                    </div>
                </section>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.table.total') }}
                    </p>
                    <p class="text-sm font-semibold text-green-300">
                        {{ proposalPendingDetail.total.formatted }}
                    </p>
                </section>

                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.proposals.table.valid_until') }}
                    </p>
                    <p
                        class="text-sm font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{ proposalPendingDetail.valid_until.formatted ?? '' }}
                    </p>
                </section>
            </div>
        </div>
    </Dialog>
</template>
