<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ProposalRecord } from '@/types/proposals';

type Props = {
    visible: boolean;
    proposal: ProposalRecord | null;
};

defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:visible', visible: boolean): void;
    (e: 'confirm'): void;
}>();

const { t } = useTranslations();
</script>

<template>
    <Dialog
        :visible="visible"
        :dismissableMask="true"
        modal
        :draggable="false"
        :header="
            t('ui.proposals.confirm_delete.title', {
                title: proposal?.title ?? '',
            })
        "
        class="mx-4 w-full max-w-md"
        @update:visible="emit('update:visible', $event)"
    >
        <p class="text-sm text-surface-600 dark:text-surface-300">
            {{ t('ui.proposals.confirm_delete.body') }}
        </p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    severity="secondary"
                    variant="text"
                    @click="emit('update:visible', false)"
                >
                    {{ t('ui.actions.cancel') }}
                </Button>
                <Button severity="danger" @click="emit('confirm')">
                    <Trash2 class="size-4" />
                    <span>{{ t('ui.actions.delete') }}</span>
                </Button>
            </div>
        </template>
    </Dialog>
</template>
