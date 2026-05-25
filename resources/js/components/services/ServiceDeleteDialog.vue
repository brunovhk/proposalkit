<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ServiceRecord } from '@/types/services';

type Props = {
    visible: boolean;
    service: ServiceRecord | null;
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
            t('ui.services.confirm_delete.title', {
                name: service?.name ?? '',
            })
        "
        class="mx-4 w-full max-w-md"
        @update:visible="emit('update:visible', $event)"
    >
        <p class="text-sm text-surface-600 dark:text-surface-300">
            {{ t('ui.services.confirm_delete.body') }}
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
