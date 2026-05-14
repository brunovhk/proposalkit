<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.login.title'),
        description: t('ui.auth.login.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.login.head')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium">
                    {{ t('ui.fields.email_address') }}
                </label>
                <InputText
                    id="email"
                    type="email"
                    name="email"
                    fluid
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    :placeholder="t('ui.fields.email_placeholder')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <label for="password" class="text-sm font-medium">
                        {{ t('ui.fields.password') }}
                    </label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        {{ t('ui.auth.login.forgot_password') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('ui.fields.password')"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember" class="flex items-center gap-3 text-sm">
                    <Checkbox
                        input-id="remember"
                        name="remember"
                        binary
                        :tabindex="3"
                    />
                    <span>{{ t('ui.auth.login.remember') }}</span>
                </label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <ProgressSpinner
                    v-if="processing"
                    class="size-4"
                    stroke-width="4"
                />
                {{ t('ui.auth.login.submit') }}
            </Button>
        </div>

        <div
            class="text-center text-sm text-muted-foreground"
            v-if="canRegister"
        >
            {{ t('ui.auth.login.no_account') }}
            <TextLink :href="register()" :tabindex="5">
                {{ t('ui.auth.register.head') }}
            </TextLink>
        </div>
    </Form>
</template>
