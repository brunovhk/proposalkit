<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import ServiceDetailDialog from '@/components/services/ServiceDetailDialog.vue';
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
    urls: {
        edit: string;
    };
};

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

        <div
            class="overflow-hidden rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
        >
            <DataTable
                :value="props.services.data"
                data-key="id"
                :paginator="props.services.data.length > 10"
                :rows="10"
                table-style="min-width: 48rem"
            >
                <Column field="name" :header="t('ui.services.table.name')">
                    <template #body="{ data }: { data: ServiceRecord }">
                        <div class="min-w-0">
                            <p
                                class="truncate font-medium text-surface-950 dark:text-surface-0"
                            >
                                {{ data.name }}
                            </p>
                        </div>
                    </template>
                </Column>

                <Column :header="t('ui.services.table.description')">
                    <template #body="{ data }: { data: ServiceRecord }">
                        <button
                            type="button"
                            class="line-clamp-2 max-w-xl text-left text-sm text-surface-600 transition-colors hover:text-surface-950 dark:text-surface-300 dark:hover:text-surface-0"
                            @click="viewService(data)"
                        >
                            {{ data.description ?? '' }}
                        </button>
                    </template>
                </Column>

                <Column :header="t('ui.services.table.price')">
                    <template #body="{ data }: { data: ServiceRecord }">
                        <span class="font-medium text-green-300">{{
                            data.price.formatted
                        }}</span>
                    </template>
                </Column>

                <Column :header="t('ui.services.table.type')">
                    <template #body="{ data }: { data: ServiceRecord }">
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

                <Column :header="t('ui.services.table.actions')">
                    <template #body="{ data }: { data: ServiceRecord }">
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                severity="secondary"
                                variant="text"
                                :aria-label="t('ui.services.actions.view')"
                                @click="viewService(data)"
                            >
                                <Eye class="size-4" />
                            </Button>
                            <Link :href="data.urls.edit">
                                <Button
                                    severity="secondary"
                                    variant="text"
                                    :aria-label="t('ui.actions.edit')"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                            </Link>
                            <Button
                                severity="danger"
                                variant="text"
                                :aria-label="t('ui.services.actions.delete')"
                                @click="requestDelete(data)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div
                        class="py-10 text-center text-sm text-surface-500 dark:text-surface-400"
                    >
                        {{ t('ui.services.empty') }}
                    </div>
                </template>
            </DataTable>
        </div>
    </div>

    <ServiceDetailDialog
        v-model:visible="detailDialogVisible"
        :service="selectedService"
    />

    <Dialog
        v-model:visible="deleteDialogVisible"
        modal
        :header="
            t('ui.services.confirm_delete.title', {
                name: servicePendingDelete?.name ?? '',
            })
        "
        class="mx-4 w-full max-w-md"
    >
        <p class="text-sm text-surface-600 dark:text-surface-300">
            {{ t('ui.services.confirm_delete.body') }}
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
                <Button severity="danger" @click="destroyService">
                    <Trash2 class="size-4" />
                    <span>{{ t('ui.actions.delete') }}</span>
                </Button>
            </div>
        </template>
    </Dialog>
</template>
