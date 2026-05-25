<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2 } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ClientRecord } from '@/types/clients';

type Props = {
    clients: ClientRecord[];
};

defineProps<Props>();

const emit = defineEmits<{
    (e: 'view', client: ClientRecord): void;
    (e: 'request-delete', client: ClientRecord): void;
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        class="rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
    >
        <DataTable
            :value="clients"
            data-key="id"
            stripedRows
            removableSort
            :paginator="clients.length > 10"
            :rows="10"
            table-style="min-width: 52rem"
        >
            <Column
                field="name"
                :header="t('ui.clients.table.name')"
                sortable
                class="max-w-50"
            >
                <template #body="{ data }: { data: ClientRecord }">
                    <span
                        v-tooltip.top="data.name"
                        class="block cursor-pointer truncate font-medium text-surface-950 hover:underline md:max-w-64 dark:text-surface-0"
                        @click="emit('view', data)"
                    >
                        {{ data.name }}
                    </span>
                </template>
            </Column>

            <Column
                field="email"
                :header="t('ui.clients.table.email')"
                class="max-w-50"
                sortable
            >
                <template #body="{ data }: { data: ClientRecord }">
                    <a
                        :href="`mailto:${data.email}`"
                        class="wrap-break-words block truncate text-sm text-surface-600 transition-colors hover:text-surface-950 dark:text-surface-300 dark:hover:text-surface-0"
                        v-tooltip.top="data.email"
                    >
                        {{ data.email }}
                    </a>
                </template>
            </Column>

            <Column
                :header="t('ui.clients.table.tax_id')"
                class="whitespace-nowrap"
            >
                <template #body="{ data }: { data: ClientRecord }">
                    <span
                        class="text-sm text-surface-600 dark:text-surface-300"
                    >
                        {{ data.tax_id ?? '' }}
                    </span>
                </template>
            </Column>

            <Column
                :header="t('ui.clients.table.address')"
                class="max-w-48! md:max-w-64!"
            >
                <template #body="{ data }: { data: ClientRecord }">
                    <p
                        v-tooltip.top="data.address"
                        class="truncate text-sm text-surface-600 dark:text-surface-300"
                    >
                        {{ data.address ?? '' }}
                    </p>
                </template>
            </Column>

            <Column
                :header="t('ui.clients.table.actions')"
                body-class="text-right"
                class="w-42!"
            >
                <template #body="{ data }: { data: ClientRecord }">
                    <div class="flex items-center justify-end gap-2">
                        <Button
                            severity="secondary"
                            variant="text"
                            :aria-label="t('ui.clients.actions.view')"
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
                            :aria-label="t('ui.clients.actions.delete')"
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
                    {{ t('ui.clients.empty') }}
                </div>
            </template>
        </DataTable>
    </div>
</template>
