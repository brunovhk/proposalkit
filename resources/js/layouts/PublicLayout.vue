<script setup lang="ts">
import { useFlashToasts } from '@/composables/useFlashToasts';

useFlashToasts();
</script>

<template>
    <Toast />
    <main class="min-h-screen bg-surface-50 px-4 py-8 dark:bg-surface-950">
        <div class="mx-auto max-w-5xl">
            <slot />
        </div>
    </main>
</template>
