<script setup lang="ts">
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type ServiceRecord = {
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
};

type Props = {
    service: ServiceRecord | null;
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
        modal
        :header="t('ui.services.detail.title')"
        class="mx-4 w-full max-w-lg"
    >
        <div v-if="service" class="grid gap-5">
            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.services.fields.name') }}
                </p>
                <h2
                    class="text-xl font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ service.name }}
                </h2>
            </section>

            <section class="grid gap-2">
                <p
                    class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                >
                    {{ t('ui.services.fields.description') }}
                </p>
                <p
                    class="text-sm leading-6 whitespace-pre-line text-surface-700 dark:text-surface-200"
                >
                    {{ service.description ?? '' }}
                </p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.services.fields.price') }}
                    </p>
                    <p class="text-sm font-semibold text-green-300">
                        {{ service.price.formatted }}
                    </p>
                </section>

                <section class="grid gap-2">
                    <p
                        class="text-xs font-medium tracking-wide text-surface-500 uppercase dark:text-surface-400"
                    >
                        {{ t('ui.services.fields.type') }}
                    </p>
                    <div>
                        <Tag
                            :value="service.type.label"
                            :severity="
                                service.type.value === 'fixed'
                                    ? 'info'
                                    : 'secondary'
                            "
                            rounded
                        />
                    </div>
                </section>
            </div>
        </div>
    </Dialog>
</template>
