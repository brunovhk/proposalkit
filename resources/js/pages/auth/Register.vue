<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.register.title'),
        description: t('ui.auth.register.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.register.head')" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <label for="name" class="text-sm font-medium">
                    {{ t('ui.fields.name') }}
                </label>
                <InputText
                    id="name"
                    type="text"
                    fluid
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    :placeholder="t('ui.fields.name')"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <label for="email" class="text-sm font-medium">
                    {{ t('ui.fields.email_address') }}
                </label>
                <InputText
                    id="email"
                    type="email"
                    fluid
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    :placeholder="t('ui.fields.email_placeholder')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <label for="password" class="text-sm font-medium">
                    {{ t('ui.fields.password') }}
                </label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('ui.fields.password')"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <label for="password_confirmation" class="text-sm font-medium">
                    {{ t('ui.fields.confirm_password') }}
                </label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('ui.fields.confirm_password')"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <ProgressSpinner
                    v-if="processing"
                    class="size-4"
                    stroke-width="4"
                />
                {{ t('ui.auth.register.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('ui.auth.register.already_registered') }}
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
            >
                {{ t('ui.auth.login.submit') }}
            </TextLink>
        </div>
    </Form>
</template>
