<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';

type ClientOption = {
    id: number;
    name: string;
};

type ProposalRecord = {
    id: number;
    title: string;
    client_id: number;
    valid_until: {
        value: string | null;
        formatted: string | null;
    };
};

type ProposalForm = {
    title: string;
    client_id: number | null;
    valid_until: string | null;
};

type Props = {
    proposal: {
        data: ProposalRecord;
    } | null;
    clients: {
        data: ClientOption[];
    };
};

const props = defineProps<Props>();

const { t } = useTranslations();
const proposalsUrl = '/proposals';

const proposal = computed(() => props.proposal?.data ?? null);
const isEditing = computed(() => proposal.value !== null);

const form = useForm<ProposalForm>({
    title: proposal.value?.title ?? '',
    client_id: proposal.value?.client_id ?? null,
    valid_until: proposal.value?.valid_until.value ?? null,
});

const pageTitle = computed(() =>
    isEditing.value
        ? t('ui.proposals.form.edit_head')
        : t('ui.proposals.form.create_head'),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.proposals.index.head'),
                href: proposalsUrl,
            },
            {
                title: pageTitle.value,
                href:
                    isEditing.value && proposal.value
                        ? `${proposalsUrl}/${proposal.value.id}/edit`
                        : `${proposalsUrl}/create`,
            },
        ],
    });
});

function submit(): void {
    if (isEditing.value && proposal.value) {
        form.put(`${proposalsUrl}/${proposal.value.id}`);

        return;
    }

    form.post(proposalsUrl);
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
                        ? t('ui.proposals.form.edit_title')
                        : t('ui.proposals.form.create_title')
                "
                :description="
                    isEditing
                        ? t('ui.proposals.form.edit_description')
                        : t('ui.proposals.form.create_description')
                "
            />

            <Link :href="proposalsUrl" class="shrink-0">
                <Button severity="secondary" variant="text">
                    <ArrowLeft class="size-4" />
                    <span>{{ t('ui.actions.back') }}</span>
                </Button>
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <label for="title" class="text-sm font-medium">
                    {{ t('ui.proposals.fields.title') }}
                </label>
                <InputText
                    id="title"
                    v-model="form.title"
                    :invalid="Boolean(form.errors.title)"
                    :placeholder="t('ui.proposals.placeholders.title')"
                    fluid
                    required
                    autocomplete="off"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="client_id" class="text-sm font-medium">
                        {{ t('ui.proposals.fields.client') }}
                    </label>
                    <Select
                        id="client_id"
                        v-model="form.client_id"
                        :options="props.clients.data"
                        option-label="name"
                        option-value="id"
                        :invalid="Boolean(form.errors.client_id)"
                        :placeholder="t('ui.proposals.placeholders.client')"
                        fluid
                    />
                    <InputError :message="form.errors.client_id" />
                </div>

                <div class="grid gap-2">
                    <label for="valid_until" class="text-sm font-medium">
                        {{ t('ui.proposals.fields.valid_until') }}
                    </label>
                    <InputText
                        id="valid_until"
                        v-model="form.valid_until"
                        type="date"
                        :invalid="Boolean(form.errors.valid_until)"
                        fluid
                    />
                    <InputError :message="form.errors.valid_until" />
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" />
                    <span>{{ t('ui.proposals.actions.save') }}</span>
                </Button>
            </div>
        </form>
    </div>
</template>
