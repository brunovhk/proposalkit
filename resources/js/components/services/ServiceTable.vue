<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ServiceRecord } from '@/types/services';

type Props = {
    services: ServiceRecord[];
};

defineProps<Props>();

const emit = defineEmits<{
    (e: 'view', service: ServiceRecord): void;
    (e: 'request-delete', service: ServiceRecord): void;
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        class="rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
    >
        <DataTable
            :value="services"
            data-key="id"
            stripedRows
            removableSort
            :paginator="services.length > 10"
            :rows="10"
            table-style="min-width: 48rem"
        >
            <Column
                field="name"
                :header="t('ui.services.table.name')"
                class="max-w-48 whitespace-nowrap md:max-w-64"
                sortable
            >
                <template #body="{ data }: { data: ServiceRecord }">
                    <span
                        v-tooltip.top="data.name"
                        class="block cursor-pointer truncate font-medium text-surface-950 hover:underline dark:text-surface-0"
                        @click="emit('view', data)"
                    >
                        {{ data.name }}
                    </span>
                </template>
            </Column>

            <Column
                :header="t('ui.services.table.description')"
                class="max-w-72 whitespace-nowrap md:max-w-96"
            >
                <template #body="{ data }: { data: ServiceRecord }">
                    <p
                        class="truncate text-sm text-surface-600 dark:text-surface-300"
                    >
                        {{ data.description ?? '' }}
                    </p>
                </template>
            </Column>

            <Column
                field="price.cents"
                :header="t('ui.services.table.price')"
                class="whitespace-nowrap"
                sortable
            >
                <template #body="{ data }: { data: ServiceRecord }">
                    <span
                        class="font-medium text-green-500 dark:text-green-300"
                    >
                        {{ data.price.formatted }}
                    </span>
                </template>
            </Column>

            <Column
                field="type.label"
                :header="t('ui.services.table.type')"
                class="whitespace-nowrap"
                sortable
            >
                <template #body="{ data }: { data: ServiceRecord }">
                    <Tag
                        :value="data.type.label"
                        :severity="
                            data.type.value === 'fixed' ? 'info' : 'secondary'
                        "
                        rounded
                    />
                </template>
            </Column>

            <Column
                :header="t('ui.services.table.actions')"
                body-class="text-right"
            >
                <template #body="{ data }: { data: ServiceRecord }">
                    <div class="flex items-center justify-end gap-2">
                        <Button
                            severity="secondary"
                            variant="text"
                            :aria-label="t('ui.services.actions.view')"
                            @click="emit('view', data)"
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
                            @click="emit('request-delete', data)"
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
</template>
