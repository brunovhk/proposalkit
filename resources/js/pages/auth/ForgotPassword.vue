<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.forgot_password.title'),
        description: t('ui.auth.forgot_password.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.forgot_password.head')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium">
                    {{ t('ui.fields.email_address') }}
                </label>
                <InputText
                    id="email"
                    type="email"
                    name="email"
                    fluid
                    autocomplete="off"
                    autofocus
                    :placeholder="t('ui.fields.email_placeholder')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <ProgressSpinner
                        v-if="processing"
                        class="size-4"
                        stroke-width="4"
                    />
                    {{ t('ui.auth.forgot_password.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('ui.auth.forgot_password.return') }}</span>
            <TextLink :href="login()">{{ t('ui.auth.login.submit') }}</TextLink>
        </div>
    </div>
</template>
