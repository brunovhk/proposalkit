<script setup lang="ts">
import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const page = usePage();
const { t } = useTranslations();
const showRegistrationLink = computed(
    () => props.canRegister && page.props.live_demo_enabled !== true,
);

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.login.title'),
        description: t('ui.auth.login.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.login.head')" />

    <Teleport to="body">
        <Link
            :href="home()"
            class="group absolute top-6 left-6 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900 md:top-8 md:left-8 dark:text-slate-400 dark:hover:text-slate-100"
        >
            <ArrowLeft
                class="size-4 transition-transform group-hover:-translate-x-0.5"
                aria-hidden="true"
            />
            <span>{{ t('ui.landing.actions.back_to_home') }}</span>
        </Link>
    </Teleport>

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
        class="space-y-6"
    >
        <div class="space-y-5">
            <div class="space-y-2">
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
                    :invalid="Boolean(errors.email)"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="space-y-2">
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
                <Password
                    input-id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('ui.fields.password')"
                    :invalid="Boolean(errors.password)"
                    :feedback="false"
                    toggle-mask
                    fluid
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <label
                    for="remember"
                    class="inline-flex items-center gap-3 text-sm leading-none"
                >
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
                fluid
                :tabindex="4"
                :disabled="processing"
                :loading="processing"
                data-test="login-button"
            >
                {{ t('ui.auth.login.submit') }}
            </Button>
        </div>

        <div
            class="text-center text-sm text-surface-500 dark:text-surface-400"
            v-if="showRegistrationLink"
        >
            {{ t('ui.auth.login.no_account') }}
            <TextLink :href="register()" :tabindex="5">
                {{ t('ui.auth.register.head') }}
            </TextLink>
        </div>
    </Form>
</template>
