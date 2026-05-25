<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watchEffect } from 'vue';
import ClientDeleteDialog from '@/components/clients/ClientDeleteDialog.vue';
import ClientDetailDialog from '@/components/clients/ClientDetailDialog.vue';
import ClientTable from '@/components/clients/ClientTable.vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ClientRecord } from '@/types/clients';

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

        <ClientTable
            :clients="props.clients.data"
            @view="viewClient"
            @request-delete="requestDelete"
        />
    </div>

    <ClientDetailDialog
        v-model:visible="detailDialogVisible"
        :client="selectedClient"
    />

    <ClientDeleteDialog
        v-model:visible="deleteDialogVisible"
        :client="clientPendingDelete"
        @confirm="destroyClient"
    />
</template>
