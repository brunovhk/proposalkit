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
                    class="rounded-lg border border-border bg-card shadow-sm"
                >
                    <header class="px-10 pt-8 pb-0 text-center">
                        <h1 class="text-xl font-semibold text-foreground">
                            {{ title }}
                        </h1>
                        <p class="mt-2 text-sm text-muted-foreground">
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
