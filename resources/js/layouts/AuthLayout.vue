<script setup lang="ts">
import { useFlashToasts } from '@/composables/useFlashToasts';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

useFlashToasts();

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Toast />
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
