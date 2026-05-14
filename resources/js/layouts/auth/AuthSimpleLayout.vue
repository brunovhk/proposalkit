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
        class="flex min-h-svh flex-col items-center justify-center bg-background px-4 py-8 sm:px-6 lg:px-8"
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
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold text-foreground">
                            {{ title }}
                        </h1>
                        <p class="text-sm leading-6 text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <Fluid>
                    <section
                        class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8"
                    >
                        <slot />
                    </section>
                </Fluid>
            </div>
        </div>
    </div>
</template>
