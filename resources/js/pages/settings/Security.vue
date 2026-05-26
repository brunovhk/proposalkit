<script setup lang="ts">
import { Form, Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref, watchEffect } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const { t } = useTranslations();
const showSetupModal = ref<boolean>(false);

type PasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

const passwordForm = useForm<PasswordForm>({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = (): void => {
    passwordForm.put(SecurityController.update.url(), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () =>
            passwordForm.reset(
                'current_password',
                'password',
                'password_confirmation',
            ),
    });
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.settings.security.breadcrumb'),
                href: edit(),
            },
        ],
    });
});

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <Head :title="t('ui.settings.security.breadcrumb')" />

    <h1 class="sr-only">{{ t('ui.settings.security.breadcrumb') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('ui.settings.security.password_title')"
            :description="t('ui.settings.security.password_description')"
        />

        <form class="space-y-6" @submit.prevent="updatePassword">
            <div class="grid gap-2">
                <label for="current_password" class="text-sm font-medium">
                    {{ t('ui.fields.current_password') }}
                </label>
                <Password
                    input-id="current_password"
                    v-model="passwordForm.current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    :placeholder="t('ui.fields.current_password')"
                    :feedback="false"
                    toggle-mask
                    fluid
                />
                <Message
                    v-if="passwordForm.errors.current_password"
                    severity="error"
                    :closable="false"
                >
                    {{ passwordForm.errors.current_password }}
                </Message>
            </div>

            <div class="grid gap-2">
                <label for="password" class="text-sm font-medium">
                    {{ t('ui.fields.new_password') }}
                </label>
                <Password
                    input-id="password"
                    v-model="passwordForm.password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('ui.fields.new_password')"
                    :feedback="false"
                    toggle-mask
                    fluid
                />
                <Message
                    v-if="passwordForm.errors.password"
                    severity="error"
                    :closable="false"
                >
                    {{ passwordForm.errors.password }}
                </Message>
            </div>

            <div class="grid gap-2">
                <label for="password_confirmation" class="text-sm font-medium">
                    {{ t('ui.fields.confirm_password') }}
                </label>
                <Password
                    input-id="password_confirmation"
                    v-model="passwordForm.password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('ui.fields.confirm_password')"
                    :feedback="false"
                    toggle-mask
                    fluid
                />
                <Message
                    v-if="passwordForm.errors.password_confirmation"
                    severity="error"
                    :closable="false"
                >
                    {{ passwordForm.errors.password_confirmation }}
                </Message>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="passwordForm.processing"
                    :loading="passwordForm.processing"
                    data-test="update-password-button"
                >
                    {{ t('ui.settings.security.save_password') }}
                </Button>
            </div>
        </form>
    </div>

    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            :title="t('ui.settings.security.two_factor_title')"
            :description="t('ui.settings.security.two_factor_description')"
        />

        <div
            v-if="!twoFactorEnabled"
            class="flex flex-col items-start justify-start space-y-4"
        >
            <p class="text-sm text-surface-500 dark:text-surface-400">
                {{ t('ui.settings.security.two_factor_disabled_body') }}
            </p>

            <div>
                <Button v-if="hasSetupData" @click="showSetupModal = true">
                    <ShieldCheck class="size-4" />
                    {{ t('ui.settings.security.setup_continue') }}
                </Button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        {{ t('ui.settings.security.enable_2fa') }}
                    </Button>
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-surface-500 dark:text-surface-400">
                {{ t('ui.settings.security.two_factor_enabled_body') }}
            </p>

            <div class="relative inline">
                <Form v-bind="disable.form()" #default="{ processing }">
                    <Button
                        severity="danger"
                        type="submit"
                        :disabled="processing"
                    >
                        {{ t('ui.settings.security.disable_2fa') }}
                    </Button>
                </Form>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
