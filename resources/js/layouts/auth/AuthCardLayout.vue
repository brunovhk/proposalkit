<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-surface-0 px-4 py-8 sm:px-6 lg:px-8 dark:bg-surface-900"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon
                        class="size-9 fill-current text-black dark:text-white"
                    />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <section
                    class="rounded-lg border border-surface-200 bg-surface-0 shadow-sm dark:border-surface-700 dark:bg-surface-900"
                >
                    <header class="px-10 pt-8 pb-0 text-center">
                        <h1
                            class="text-xl font-semibold text-surface-900 dark:text-surface-0"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="mt-2 text-sm text-surface-500 dark:text-surface-400"
                        >
                            {{ description }}
                        </p>
                    </header>
                    <Fluid class="px-6 py-8 sm:px-10">
                        <slot />
                    </Fluid>
                </section>
            </div>
        </div>
    </div>
</template>
