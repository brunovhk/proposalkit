<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import ProposalDeleteDialog from '@/components/proposals/ProposalDeleteDialog.vue';
import ProposalDetailDialog from '@/components/proposals/ProposalDetailDialog.vue';
import ProposalTable from '@/components/proposals/ProposalTable.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ProposalRecord } from '@/types/proposals';

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

function sendProposal(proposal: ProposalRecord): void {
    router.post(proposal.urls.send, undefined, {
        preserveScroll: true,
    });
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

        <ProposalTable
            :proposals="props.proposals.data"
            @send="sendProposal"
            @request-delete="requestDelete"
            @request-detail="requestDetail"
        />
    </div>

    <ProposalDeleteDialog
        v-model:visible="deleteDialogVisible"
        :proposal="proposalPendingDelete"
        @confirm="destroyProposal"
    />

    <ProposalDetailDialog
        v-model:visible="detailDialogVisible"
        :proposal="proposalPendingDetail"
    />
</template>
