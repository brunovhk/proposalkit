<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref, watchEffect } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <label for="current_password" class="text-sm font-medium">
                    {{ t('ui.fields.current_password') }}
                </label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    :placeholder="t('ui.fields.current_password')"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <label for="password" class="text-sm font-medium">
                    {{ t('ui.fields.new_password') }}
                </label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('ui.fields.new_password')"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <label for="password_confirmation" class="text-sm font-medium">
                    {{ t('ui.fields.confirm_password') }}
                </label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('ui.fields.confirm_password')"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    {{ t('ui.settings.security.save_password') }}
                </Button>
            </div>
        </Form>
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
            <p class="text-sm text-muted-foreground">
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
            <p class="text-sm text-muted-foreground">
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
