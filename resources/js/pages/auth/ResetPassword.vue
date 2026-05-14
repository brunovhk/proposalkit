<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { ref, watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();

const { t } = useTranslations();
const inputEmail = ref(props.email);

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.reset_password.title'),
        description: t('ui.auth.reset_password.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.reset_password.head')" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="space-y-5"
    >
        <div class="space-y-5">
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium">
                    {{ t('ui.fields.email') }}
                </label>
                <InputText
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    fluid
                    readonly
                    :invalid="Boolean(errors.email)"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium">
                    {{ t('ui.fields.password') }}
                </label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    :placeholder="t('ui.fields.password')"
                    :invalid="Boolean(errors.password)"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium">
                    {{ t('ui.fields.confirm_password') }}
                </label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    :placeholder="t('ui.fields.confirm_password')"
                    :invalid="Boolean(errors.password_confirmation)"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                fluid
                :disabled="processing"
                :loading="processing"
                data-test="reset-password-button"
            >
                {{ t('ui.auth.reset_password.submit') }}
            </Button>
        </div>
    </Form>
</template>
