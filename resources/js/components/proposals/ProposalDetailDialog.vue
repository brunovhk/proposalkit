<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type {
    ProposalRecord,
    ProposalStatusSeverity,
    ProposalStatusValue,
} from '@/types/proposals';

type Props = {
    visible: boolean;
    proposal: ProposalRecord | null;
};

defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:visible', visible: boolean): void;
}>();

const { t } = useTranslations();

const statusSeverity: Record<ProposalStatusValue, ProposalStatusSeverity> = {
    draft: 'secondary',
    sent: 'info',
    viewed: 'warn',
    accepted: 'success',
    declined: 'danger',
};
</script>

<template>
    <Dialog
        :visible="visible"
        :dismissableMask="true"
        modal
        :draggable="false"
        :header="t('ui.proposals.detail.title')"
        class="mx-4 w-full max-w-lg"
        @update:visible="emit('update:visible', $event)"
    >
        <div v-if="proposal" class="grid gap-5">
            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.proposals.fields.title') }}
                </p>
                <h2
                    class="text-xl font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ proposal.title }}
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
                        class="text-sm font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{ proposal.client.name }}
                    </p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">
                        {{ proposal.client.email }}
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
                            :value="proposal.status.label"
                            :severity="statusSeverity[proposal.status.value]"
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
                    <p
                        class="text-sm font-semibold text-green-500 dark:text-green-300"
                    >
                        {{ proposal.total.formatted }}
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
                        {{ proposal.valid_until.formatted ?? '' }}
                    </p>
                </section>
            </div>

            <section
                v-if="proposal.status.value === 'accepted'"
                class="grid gap-2"
            >
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.proposals.detail.signatory_name') }}
                </p>
                <p
                    class="text-sm font-medium text-surface-950 dark:text-surface-0"
                >
                    {{ proposal.finalized_metadata.accepted_by_name ?? '' }}
                </p>
            </section>

            <section
                v-if="proposal.status.value === 'declined'"
                class="grid gap-2"
            >
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.proposals.detail.refusal_reason') }}
                </p>
                <p
                    class="text-sm leading-tight text-surface-700 dark:text-surface-200"
                >
                    {{ proposal.finalized_metadata.refusal_reason ?? '' }}
                </p>
            </section>
        </div>
    </Dialog>
</template>
