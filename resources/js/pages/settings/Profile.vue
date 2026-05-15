<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useTranslations();

type ProfileForm = {
    name: string;
};

const profileForm = useForm<ProfileForm>({
    name: user.value.name,
});

const submitProfile = (): void => {
    profileForm.patch(ProfileController.update.url(), {
        preserveScroll: true,
    });
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.settings.profile.breadcrumb'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('ui.settings.profile.head')" />

    <h1 class="sr-only">{{ t('ui.settings.profile.head') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('ui.settings.profile.title')"
            :description="t('ui.settings.profile.description')"
        />

        <form class="space-y-6" @submit.prevent="submitProfile">
            <div class="grid gap-2">
                <label for="name" class="text-sm font-medium">
                    {{ t('ui.fields.name') }}
                </label>
                <InputText
                    id="name"
                    v-model="profileForm.name"
                    class="mt-1 block w-full"
                    name="name"
                    fluid
                    required
                    autocomplete="name"
                    :placeholder="t('ui.fields.name')"
                />
                <Message
                    v-if="profileForm.errors.name"
                    severity="error"
                    :closable="false"
                    class="mt-2"
                >
                    {{ profileForm.errors.name }}
                </Message>
            </div>

            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium">
                    {{ t('ui.fields.email_address') }}
                </label>
                <InputText
                    id="email"
                    :model-value="user.email"
                    type="email"
                    class="mt-1 block w-full"
                    fluid
                    disabled
                    autocomplete="username"
                    :placeholder="t('ui.fields.email_address')"
                />
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    {{ t('ui.settings.profile.email_locked') }}
                </p>
            </div>

            <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-surface-500 dark:text-surface-400">
                    {{ t('ui.settings.profile.email_unverified') }}
                    <Link
                        :href="send()"
                        as="button"
                        class="text-surface-900 underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:text-surface-0 dark:decoration-neutral-500"
                    >
                        {{ t('ui.settings.profile.resend_verification') }}
                    </Link>
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    {{ t('ui.settings.profile.sent') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="profileForm.processing"
                    :loading="profileForm.processing"
                    data-test="update-profile-button"
                >
                    {{ t('ui.actions.save') }}
                </Button>
            </div>
        </form>
    </div>

    <DeleteUser />
</template>
