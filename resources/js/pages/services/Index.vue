<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import ServiceDeleteDialog from '@/components/services/ServiceDeleteDialog.vue';
import ServiceDetailDialog from '@/components/services/ServiceDetailDialog.vue';
import ServiceTable from '@/components/services/ServiceTable.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ServiceRecord } from '@/types/services';

type Props = {
    services: {
        data: ServiceRecord[];
    };
};

const props = defineProps<Props>();

const { t } = useTranslations();
const selectedService = ref<ServiceRecord | null>(null);
const servicePendingDelete = ref<ServiceRecord | null>(null);

const servicesUrl = '/services';
const createServiceUrl = '/services/create';

const detailDialogVisible = computed({
    get: () => selectedService.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            selectedService.value = null;
        }
    },
});

const deleteDialogVisible = computed({
    get: () => servicePendingDelete.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            servicePendingDelete.value = null;
        }
    },
});

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.services.index.head'),
                href: servicesUrl,
            },
        ],
    });
});

function viewService(service: ServiceRecord): void {
    selectedService.value = service;
}

function requestDelete(service: ServiceRecord): void {
    servicePendingDelete.value = service;
}

function destroyService(): void {
    if (!servicePendingDelete.value) {
        return;
    }

    router.delete(`${servicesUrl}/${servicePendingDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            servicePendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.services.index.head')" />

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('ui.services.index.title')"
                :description="t('ui.services.index.description')"
            />

            <Link :href="createServiceUrl" class="shrink-0">
                <Button>
                    <Plus class="size-4" />
                    <span>{{ t('ui.services.actions.create') }}</span>
                </Button>
            </Link>
        </div>

        <ServiceTable
            :services="props.services.data"
            @view="viewService"
            @request-delete="requestDelete"
        />
    </div>

    <ServiceDetailDialog
        v-model:visible="detailDialogVisible"
        :service="selectedService"
    />

    <ServiceDeleteDialog
        v-model:visible="deleteDialogVisible"
        :service="servicePendingDelete"
        @confirm="destroyService"
    />
</template>
