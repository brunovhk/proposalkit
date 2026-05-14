<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import { store } from '@/routes/password/confirm';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.confirm_password.title'),
        description: t('ui.auth.confirm_password.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.confirm_password.head')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <label for="password" class="text-sm font-medium">
                    {{ t('ui.fields.password') }}
                </label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <ProgressSpinner
                        v-if="processing"
                        class="size-4"
                        stroke-width="4"
                    />
                    {{ t('ui.auth.confirm_password.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
