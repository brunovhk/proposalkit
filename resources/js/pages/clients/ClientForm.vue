<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

type ClientFormData = {
    name: string;
    email: string;
    tax_id: string;
    address: string;
};

type Props = {
    client: {
        data: ClientRecord;
    } | null;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const clientsUrl = '/clients';
const client = computed(() => props.client?.data ?? null);
const isEditing = computed(() => client.value !== null);

const form = useForm<ClientFormData>({
    name: client.value?.name ?? '',
    email: client.value?.email ?? '',
    tax_id: client.value?.tax_id ?? '',
    address: client.value?.address ?? '',
});

const pageTitle = computed(() =>
    isEditing.value
        ? t('ui.clients.form.edit_head')
        : t('ui.clients.form.create_head'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.clients.index.head'),
                href: clientsUrl,
            },
            {
                title: pageTitle.value,
                href:
                    isEditing.value && client.value
                        ? client.value.urls.edit
                        : `${clientsUrl}/create`,
            },
        ],
    });
});

function submit(): void {
    if (isEditing.value && client.value) {
        form.put(`${clientsUrl}/${client.value.id}`);

        return;
    }

    form.post(clientsUrl);
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                :title="
                    isEditing
                        ? t('ui.clients.form.edit_title')
                        : t('ui.clients.form.create_title')
                "
                :description="
                    isEditing
                        ? t('ui.clients.form.edit_description')
                        : t('ui.clients.form.create_description')
                "
            />

            <Link :href="clientsUrl" class="shrink-0">
                <Button severity="secondary" variant="text">
                    <ArrowLeft class="size-4" />
                    <span>{{ t('ui.actions.back') }}</span>
                </Button>
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <label for="client-name" class="text-sm font-medium">
                    {{ t('ui.clients.fields.name') }}
                </label>
                <InputText
                    id="client-name"
                    v-model="form.name"
                    :invalid="Boolean(form.errors.name)"
                    :placeholder="t('ui.clients.placeholders.name')"
                    fluid
                    required
                    autocomplete="organization"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <label for="client-email" class="text-sm font-medium">
                    {{ t('ui.clients.fields.email') }}
                </label>
                <InputText
                    id="client-email"
                    v-model="form.email"
                    type="email"
                    :invalid="Boolean(form.errors.email)"
                    :placeholder="t('ui.clients.placeholders.email')"
                    fluid
                    required
                    autocomplete="email"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <label for="client-tax-id" class="text-sm font-medium">
                    {{ t('ui.clients.fields.tax_id') }}
                </label>
                <InputText
                    id="client-tax-id"
                    v-model="form.tax_id"
                    :invalid="Boolean(form.errors.tax_id)"
                    :placeholder="t('ui.clients.placeholders.tax_id')"
                    fluid
                    autocomplete="off"
                />
                <InputError :message="form.errors.tax_id" />
            </div>

            <div class="grid gap-2">
                <label for="client-address" class="text-sm font-medium">
                    {{ t('ui.clients.fields.address') }}
                </label>
                <Textarea
                    id="client-address"
                    v-model="form.address"
                    :invalid="Boolean(form.errors.address)"
                    :placeholder="t('ui.clients.placeholders.address')"
                    rows="5"
                    auto-resize
                    fluid
                />
                <InputError :message="form.errors.address" />
            </div>

            <div class="flex justify-end gap-2">
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" />
                    <span>{{ t('ui.clients.actions.save') }}</span>
                </Button>
            </div>
        </form>
    </div>
</template>
