<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink, Eye, Pencil, Send, Trash2 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type {
    ProposalRecord,
    ProposalStatusSeverity,
    ProposalStatusValue,
} from '@/types/proposals';

type Props = {
    proposals: ProposalRecord[];
};

defineProps<Props>();

const emit = defineEmits<{
    (e: 'request-delete', proposal: ProposalRecord): void;
    (e: 'request-detail', proposal: ProposalRecord): void;
    (e: 'send', proposal: ProposalRecord): void;
}>();

const { t } = useTranslations();

const statusSeverity: Record<ProposalStatusValue, ProposalStatusSeverity> = {
    draft: 'secondary',
    sent: 'info',
    viewed: 'warn',
    accepted: 'success',
    declined: 'danger',
};

function canViewPublicLink(status: ProposalStatusValue): boolean {
    return ['sent', 'viewed'].includes(status);
}

function canViewInternalDetails(status: ProposalStatusValue): boolean {
    return ['accepted', 'declined'].includes(status);
}

function publicProposalUrl(proposal: ProposalRecord): string {
    return proposal.urls.public;
}
</script>

<template>
    <div
        class="rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
    >
        <DataTable
            :value="proposals"
            data-key="id"
            showGridlines
            stripedRows
            removableSort
            :paginator="proposals.length > 10"
            :rows="10"
            table-style="min-width: 58rem"
        >
            <Column
                field="title"
                :header="t('ui.proposals.table.title')"
                class="max-w-48 md:max-w-64"
            >
                <template #body="{ data }: { data: ProposalRecord }">
                    <span
                        v-tooltip.top="data.title"
                        class="block cursor-pointer truncate font-medium text-surface-950 hover:underline dark:text-surface-0"
                        @click="emit('request-detail', data)"
                    >
                        {{ data.title }}
                    </span>
                </template>
            </Column>

            <Column
                field="client.name"
                :header="t('ui.proposals.table.client')"
                class="max-w-42"
                sortable
            >
                <template #body="{ data }: { data: ProposalRecord }">
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
                </template>
            </Column>

            <Column
                field="status.label"
                :header="t('ui.proposals.table.status')"
                class="w-12 whitespace-nowrap"
                sortable
            >
                <template #body="{ data }: { data: ProposalRecord }">
                    <div class="flex items-center justify-center">
                        <Tag
                            :value="data.status.label"
                            :severity="statusSeverity[data.status.value]"
                            rounded
                        />
                    </div>
                </template>
            </Column>

            <Column
                field="total.cents"
                :header="t('ui.proposals.table.total')"
                class="whitespace-nowrap"
                sortable
            >
                <template #body="{ data }: { data: ProposalRecord }">
                    <span
                        class="font-medium text-green-500 dark:text-green-300"
                    >
                        {{ data.total.formatted }}
                    </span>
                </template>
            </Column>

            <Column
                field="valid_until.value"
                :header="t('ui.proposals.table.valid_until')"
                class="max-w-28 whitespace-nowrap"
                sortable
            >
                <template #body="{ data }: { data: ProposalRecord }">
                    <span
                        class="text-sm text-surface-600 dark:text-surface-300"
                    >
                        {{ data.valid_until.formatted ?? '' }}
                    </span>
                </template>
            </Column>

            <Column
                :header="t('ui.proposals.table.actions')"
                body-class="text-right"
                class="w-42"
            >
                <template #body="{ data }: { data: ProposalRecord }">
                    <div class="flex items-center justify-end gap-1">
                        <Button
                            v-if="data.status.value === 'draft'"
                            severity="success"
                            variant="text"
                            :aria-label="t('ui.proposals.actions.send')"
                            @click="emit('send', data)"
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
                            @click="emit('request-delete', data)"
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
                            :aria-label="t('ui.proposals.actions.view_details')"
                            @click="emit('request-detail', data)"
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
</template>
