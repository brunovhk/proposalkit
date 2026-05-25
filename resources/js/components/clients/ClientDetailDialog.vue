<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type ClientRecord = {
    id: number;
    name: string;
    email: string;
    tax_id: string | null;
    address: string | null;
};

type Props = {
    client: ClientRecord | null;
    visible: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:visible': [visible: boolean];
}>();

const { t } = useTranslations();

const visibleModel = computed({
    get: () => props.visible,
    set: (visible: boolean) => {
        emit('update:visible', visible);
    },
});
</script>

<template>
    <Dialog
        v-model:visible="visibleModel"
        :dismissableMask="true"
        modal
        :draggable="false"
        :header="t('ui.clients.detail.title')"
        class="mx-4 w-full max-w-lg"
    >
        <div v-if="client" class="grid gap-5">
            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.clients.fields.name') }}
                </p>
                <h2
                    class="text-xl font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ client.name }}
                </h2>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.clients.fields.email') }}
                    </p>
                    <p
                        class="text-sm font-medium break-all text-surface-950 dark:text-surface-0"
                    >
                        {{ client.email }}
                    </p>
                </section>

                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.clients.fields.tax_id') }}
                    </p>
                    <p
                        class="text-sm font-medium text-surface-950 dark:text-surface-0"
                    >
                        {{ client.tax_id ?? '' }}
                    </p>
                </section>
            </div>

            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.clients.fields.address') }}
                </p>
                <p
                    class="text-sm leading-6 whitespace-pre-line text-surface-700 dark:text-surface-200"
                >
                    {{ client.address ?? '' }}
                </p>
            </section>
        </div>
    </Dialog>
</template>
