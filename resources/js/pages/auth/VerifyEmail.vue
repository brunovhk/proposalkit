<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        title: t('ui.auth.verify_email.title'),
        description: t('ui.auth.verify_email.description'),
    });
});
</script>

<template>
    <Head :title="t('ui.auth.verify_email.head')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ t('ui.auth.verify_email.sent') }}
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button
            fluid
            :disabled="processing"
            :loading="processing"
            severity="secondary"
        >
            {{ t('ui.auth.verify_email.submit') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ t('ui.user_menu.logout') }}
        </TextLink>
    </Form>
</template>
