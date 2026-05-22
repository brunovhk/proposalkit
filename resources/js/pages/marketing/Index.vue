<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Workflow } from '@lucide/vue';
import { siLaravel, siPhp, siRedis, siVuedotjs } from 'simple-icons';
import type { SimpleIcon } from 'simple-icons';
import type { Component } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';

type PillarCard = {
    key: string;
    titleKey: string;
    descriptionKey: string;
    accentClass: string;
};

type SimpleIconStackItem = {
    key: string;
    labelKey: string;
    iconType: 'simple';
    icon: SimpleIcon;
};

type ComponentStackItem = {
    key: string;
    labelKey: string;
    iconType: 'component';
    icon: Component;
};

type StackItem = SimpleIconStackItem | ComponentStackItem;

const simpleIconColor = (icon: SimpleIcon): string => {
    return `#${icon.hex}`;
};

const { t } = useTranslations();

const stackItems: StackItem[] = [
    {
        key: 'php',
        labelKey: 'ui.landing.stack.php',
        iconType: 'simple',
        icon: siPhp,
    },
    {
        key: 'laravel',
        labelKey: 'ui.landing.stack.laravel',
        iconType: 'simple',
        icon: siLaravel,
    },
    {
        key: 'vue',
        labelKey: 'ui.landing.stack.vue',
        iconType: 'simple',
        icon: siVuedotjs,
    },
    {
        key: 'redis',
        labelKey: 'ui.landing.stack.redis',
        iconType: 'simple',
        icon: siRedis,
    },
    {
        key: 'state_machine',
        labelKey: 'ui.landing.stack.state_machine',
        iconType: 'component',
        icon: Workflow,
    },
];

const pillarCards: PillarCard[] = [
    {
        key: 'tenancy',
        titleKey: 'ui.landing.pillars.tenancy.title',
        descriptionKey: 'ui.landing.pillars.tenancy.description',
        accentClass:
            'from-cyan-500 to-emerald-400 dark:from-cyan-400 dark:to-emerald-300',
    },
    {
        key: 'lifecycle',
        titleKey: 'ui.landing.pillars.lifecycle.title',
        descriptionKey: 'ui.landing.pillars.lifecycle.description',
        accentClass:
            'from-indigo-500 to-cyan-500 dark:from-indigo-400 dark:to-cyan-400',
    },
    {
        key: 'access',
        titleKey: 'ui.landing.pillars.access.title',
        descriptionKey: 'ui.landing.pillars.access.description',
        accentClass:
            'from-amber-400 to-rose-400 dark:from-amber-300 dark:to-rose-300',
    },
];
</script>

<template>
    <Head :title="t('ui.landing.head')" />

    <main
        class="min-h-screen overflow-hidden bg-slate-50 text-slate-900 transition-colors duration-300 selection:bg-cyan-300 selection:text-slate-950 dark:bg-slate-950 dark:text-slate-100"
    >
        <section
            class="relative isolate mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 sm:px-8 lg:px-10"
        >
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-cyan-500/40 to-transparent dark:via-cyan-300/60"
            />
            <div
                class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(120deg,rgba(248,250,252,0)_0%,rgba(14,165,233,0.10)_45%,rgba(99,102,241,0.12)_100%)] dark:bg-[linear-gradient(120deg,rgba(15,23,42,0)_0%,rgba(34,211,238,0.08)_45%,rgba(129,140,248,0.10)_100%)]"
            />

            <header class="flex items-center justify-between py-2">
                <p
                    class="text-sm font-semibold tracking-tight text-slate-950 transition-colors dark:text-slate-100"
                >
                    {{ t('ui.landing.brand') }}
                </p>

                <Link
                    :href="login()"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition duration-200 hover:border-slate-400 hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 focus-visible:outline-none dark:border-slate-700 dark:bg-transparent dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-100 dark:focus-visible:ring-cyan-300 dark:focus-visible:ring-offset-slate-950"
                >
                    {{ t('ui.landing.actions.sign_in') }}
                </Link>
            </header>

            <div
                class="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(340px,440px)] lg:py-20"
            >
                <div class="max-w-4xl space-y-9">
                    <div class="space-y-6">
                        <p
                            class="inline-flex rounded-md border border-cyan-500/20 bg-cyan-500/10 px-3 py-1 text-xs font-semibold tracking-normal text-cyan-700 transition-colors dark:border-cyan-300/20 dark:bg-cyan-300/10 dark:text-cyan-200"
                        >
                            {{ t('ui.landing.eyebrow') }}
                        </p>

                        <h1
                            class="max-w-4xl text-5xl leading-tight font-extrabold tracking-tight text-balance md:text-6xl"
                        >
                            <span
                                class="bg-linear-to-r from-indigo-600 to-cyan-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-cyan-400"
                            >
                                {{ t('ui.landing.title') }}
                            </span>
                        </h1>

                        <p
                            class="max-w-2xl text-lg leading-8 text-slate-600 transition-colors sm:text-xl dark:text-slate-400"
                        >
                            {{ t('ui.landing.subtitle') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            class="inline-flex h-12 items-center justify-center rounded-md bg-cyan-600 px-6 text-sm font-bold text-white shadow-lg shadow-cyan-900/15 transition duration-200 hover:scale-[1.02] hover:bg-cyan-500 focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 focus-visible:outline-none active:scale-[0.99] dark:bg-cyan-300 dark:text-slate-950 dark:shadow-cyan-950/40 dark:hover:bg-cyan-200 dark:focus-visible:ring-cyan-300 dark:focus-visible:ring-offset-slate-950"
                        >
                            {{ t('ui.landing.actions.live_demo') }}
                        </button>
                        <Link
                            :href="login()"
                            class="inline-flex h-12 items-center justify-center rounded-md border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 transition duration-200 hover:border-slate-400 hover:bg-slate-100 hover:text-slate-950 focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 focus-visible:outline-none dark:border-slate-700 dark:bg-transparent dark:text-slate-400 dark:hover:border-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-100 dark:focus-visible:ring-cyan-300 dark:focus-visible:ring-offset-slate-950"
                        >
                            {{ t('ui.landing.actions.sign_in') }}
                        </Link>
                    </div>
                </div>

                <aside
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-200/70 backdrop-blur transition-colors duration-300 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-slate-950/60"
                    :aria-label="t('ui.landing.stack.label')"
                >
                    <div class="space-y-4">
                        <p
                            class="text-sm font-semibold text-slate-800 transition-colors dark:text-slate-200"
                        >
                            {{ t('ui.landing.stack.label') }}
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="item in stackItems"
                                :key="item.key"
                                class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 transition-colors dark:border-slate-800 dark:bg-slate-950/70"
                            >
                                <div class="flex items-center gap-2.5">
                                    <svg
                                        v-if="item.iconType === 'simple'"
                                        class="size-5 shrink-0"
                                        :style="{
                                            color: simpleIconColor(item.icon),
                                        }"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            :d="item.icon.path"
                                            fill="currentColor"
                                        />
                                    </svg>
                                    <component
                                        :is="item.icon"
                                        v-else
                                        class="size-5 shrink-0 text-cyan-500 transition-colors dark:text-cyan-400"
                                        aria-hidden="true"
                                    />
                                    <p
                                        class="text-sm font-medium text-slate-700 transition-colors dark:text-slate-300"
                                    >
                                        {{ t(item.labelKey) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="pb-8" :aria-label="t('ui.landing.pillars.label')">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <article
                        v-for="pillar in pillarCards"
                        :key="pillar.key"
                        class="group rounded-lg border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-slate-950/30 dark:hover:border-slate-600 dark:hover:bg-slate-900"
                    >
                        <div
                            :class="[
                                'mb-5 h-1.5 w-16 rounded-full bg-linear-to-r',
                                pillar.accentClass,
                            ]"
                        />
                        <h2
                            class="text-lg font-bold tracking-tight text-slate-950 transition-colors dark:text-white"
                        >
                            {{ t(pillar.titleKey) }}
                        </h2>
                        <p
                            class="mt-3 text-sm leading-6 text-slate-600 transition-colors dark:text-slate-400"
                        >
                            {{ t(pillar.descriptionKey) }}
                        </p>
                    </article>
                </div>
            </section>
        </section>
    </main>
</template>
