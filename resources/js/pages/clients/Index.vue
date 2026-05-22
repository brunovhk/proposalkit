<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import ClientDetailDialog from '@/components/clients/ClientDetailDialog.vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';

type ClientRecord = {
    id: number;
    name: string;
    email: string;
    tax_id: string | null;
    address: string | null;
    urls: {
        edit: string;
    };
};

type Props = {
    clients: {
        data: ClientRecord[];
    };
};

const props = defineProps<Props>();

const { t } = useTranslations();
const selectedClient = ref<ClientRecord | null>(null);
const clientPendingDelete = ref<ClientRecord | null>(null);

const clientsUrl = '/clients';
const createClientUrl = '/clients/create';

const detailDialogVisible = computed({
    get: () => selectedClient.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            selectedClient.value = null;
        }
    },
});

const deleteDialogVisible = computed({
    get: () => clientPendingDelete.value !== null,
    set: (visible: boolean) => {
        if (!visible) {
            clientPendingDelete.value = null;
        }
    },
});

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.clients.index.head'),
                href: clientsUrl,
            },
        ],
    });
});

function viewClient(client: ClientRecord): void {
    selectedClient.value = client;
}

function requestDelete(client: ClientRecord): void {
    clientPendingDelete.value = client;
}

function destroyClient(): void {
    if (!clientPendingDelete.value) {
        return;
    }

    router.delete(`${clientsUrl}/${clientPendingDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            clientPendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('ui.clients.index.head')" />

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="t('ui.clients.index.title')"
                :description="t('ui.clients.index.description')"
            />

            <Link :href="createClientUrl" class="shrink-0">
                <Button>
                    <Plus class="size-4" />
                    <span>{{ t('ui.clients.actions.create') }}</span>
                </Button>
            </Link>
        </div>

        <div
            class="overflow-hidden rounded-md border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
        >
            <DataTable
                :value="props.clients.data"
                data-key="id"
                :paginator="props.clients.data.length > 10"
                :rows="10"
                table-style="min-width: 52rem"
            >
                <Column field="name" :header="t('ui.clients.table.name')">
                    <template #body="{ data }: { data: ClientRecord }">
                        <p
                            class="truncate font-medium text-surface-950 dark:text-surface-0"
                        >
                            {{ data.name }}
                        </p>
                    </template>
                </Column>

                <Column field="email" :header="t('ui.clients.table.email')">
                    <template #body="{ data }: { data: ClientRecord }">
                        <a
                            :href="`mailto:${data.email}`"
                            class="text-sm break-words text-surface-600 transition-colors hover:text-surface-950 dark:text-surface-300 dark:hover:text-surface-0"
                        >
                            {{ data.email }}
                        </a>
                    </template>
                </Column>

                <Column :header="t('ui.clients.table.tax_id')">
                    <template #body="{ data }: { data: ClientRecord }">
                        <span
                            class="text-sm text-surface-600 dark:text-surface-300"
                        >
                            {{ data.tax_id ?? '' }}
                        </span>
                    </template>
                </Column>

                <Column :header="t('ui.clients.table.address')">
                    <template #body="{ data }: { data: ClientRecord }">
                        <button
                            type="button"
                            class="line-clamp-2 max-w-xl text-left text-sm text-surface-600 transition-colors hover:text-surface-950 dark:text-surface-300 dark:hover:text-surface-0"
                            @click="viewClient(data)"
                        >
                            {{ data.address ?? '' }}
                        </button>
                    </template>
                </Column>

                <Column :header="t('ui.clients.table.actions')">
                    <template #body="{ data }: { data: ClientRecord }">
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                severity="secondary"
                                variant="text"
                                :aria-label="t('ui.clients.actions.view')"
                                @click="viewClient(data)"
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
                        {{ t('ui.clients.empty') }}
                    </div>
                </template>
            </DataTable>
        </div>
    </div>

    <ClientDetailDialog
        v-model:visible="detailDialogVisible"
        :client="selectedClient"
    />

    <Dialog
        v-model:visible="deleteDialogVisible"
        modal
        :header="
            t('ui.clients.confirm_delete.title', {
                name: clientPendingDelete?.name ?? '',
            })
        "
        class="mx-4 w-full max-w-md"
    >
        <p class="text-sm text-surface-600 dark:text-surface-300">
            {{ t('ui.clients.confirm_delete.body') }}
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
                <Button severity="danger" @click="destroyClient">
                    <Trash2 class="size-4" />
                    <span>{{ t('ui.actions.delete') }}</span>
                </Button>
            </div>
        </template>
    </Dialog>
</template>
