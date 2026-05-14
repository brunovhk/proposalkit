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
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
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
                <section class="rounded-lg border border-border bg-card">
                    <header class="px-10 pt-8 pb-0 text-center">
                        <h1 class="text-xl font-semibold">{{ title }}</h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </header>
                    <div class="px-10 py-8">
                        <slot />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
