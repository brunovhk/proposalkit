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
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-7">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-10 w-10 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-surface-900 dark:text-surface-0"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1
                            class="text-xl font-semibold text-surface-900 dark:text-surface-0"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="text-sm leading-6 text-surface-500 dark:text-surface-400"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <Fluid>
                    <section
                        class="rounded-lg border border-surface-200 bg-surface-0 p-6 shadow-sm sm:p-8 dark:border-surface-700 dark:bg-surface-900"
                    >
                        <slot />
                    </section>
                </Fluid>
            </div>
        </div>
    </div>
</template>
