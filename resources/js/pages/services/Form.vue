<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';

type ServiceTypeValue = 'hourly' | 'fixed';

type ServiceTypeOption = {
    label: string;
    value: ServiceTypeValue;
};

type ServiceRecord = {
    id: number;
    name: string;
    description: string | null;
    price: {
        cents: number;
        formatted: string;
    };
    type: {
        value: ServiceTypeValue;
        label: string;
    };
};

type ServiceForm = {
    name: string;
    description: string;
    price: number | null;
    type: ServiceTypeValue | null;
};

type Props = {
    service: {
        data: ServiceRecord;
    } | null;
    serviceTypes: ServiceTypeOption[];
};

const props = defineProps<Props>();

const { t } = useTranslations();
const servicesUrl = '/services';

const service = computed(() => props.service?.data ?? null);
const isEditing = computed(() => service.value !== null);

const form = useForm<ServiceForm>({
    name: service.value?.name ?? '',
    description: service.value?.description ?? '',
    price: centsToAmount(service.value?.price.cents ?? 0),
    type: service.value?.type.value ?? 'fixed',
});

const pageTitle = computed(() =>
    isEditing.value
        ? t('ui.services.form.edit_head')
        : t('ui.services.form.create_head'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.services.index.head'),
                href: servicesUrl,
            },
            {
                title: pageTitle.value,
                href:
                    isEditing.value && service.value
                        ? `${servicesUrl}/${service.value.id}/edit`
                        : `${servicesUrl}/create`,
            },
        ],
    });
});

function centsToAmount(cents: number): number {
    return cents / 100;
}

function amountToCents(amount: number | null): number {
    return Math.round((amount ?? 0) * 100);
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        price: amountToCents(data.price),
    }));

    if (isEditing.value && service.value) {
        form.put(`${servicesUrl}/${service.value.id}`);

        return;
    }

    form.post(servicesUrl);
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
                        ? t('ui.services.form.edit_title')
                        : t('ui.services.form.create_title')
                "
                :description="
                    isEditing
                        ? t('ui.services.form.edit_description')
                        : t('ui.services.form.create_description')
                "
            />

            <Link :href="servicesUrl" class="shrink-0">
                <Button severity="secondary" variant="text">
                    <ArrowLeft class="size-4" />
                    <span>{{ t('ui.actions.back') }}</span>
                </Button>
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <label for="name" class="text-sm font-medium">
                    {{ t('ui.services.fields.name') }}
                </label>
                <InputText
                    id="name"
                    v-model="form.name"
                    :invalid="Boolean(form.errors.name)"
                    :placeholder="t('ui.services.placeholders.name')"
                    fluid
                    required
                    autocomplete="off"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <label for="description" class="text-sm font-medium">
                    {{ t('ui.services.fields.description') }}
                </label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    :invalid="Boolean(form.errors.description)"
                    :placeholder="t('ui.services.placeholders.description')"
                    rows="5"
                    auto-resize
                    fluid
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="price" class="text-sm font-medium">
                        {{ t('ui.services.fields.price') }}
                    </label>
                    <InputNumber
                        id="price"
                        v-model="form.price"
                        :invalid="Boolean(form.errors.price)"
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        :min="0"
                        :min-fraction-digits="2"
                        :max-fraction-digits="2"
                        fluid
                    />
                    <InputError :message="form.errors.price" />
                </div>

                <div class="grid gap-2">
                    <label for="type" class="text-sm font-medium">
                        {{ t('ui.services.fields.type') }}
                    </label>
                    <Select
                        id="type"
                        v-model="form.type"
                        :options="serviceTypes"
                        option-label="label"
                        option-value="value"
                        :invalid="Boolean(form.errors.type)"
                        :placeholder="t('ui.services.placeholders.type')"
                        fluid
                    />
                    <InputError :message="form.errors.type" />
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" />
                    <span>{{ t('ui.services.actions.save') }}</span>
                </Button>
            </div>
        </form>
    </div>
</template>
