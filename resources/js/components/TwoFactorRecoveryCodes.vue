<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from '@lucide/vue';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const { t } = useTranslations();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <section
        class="w-full rounded-lg border border-surface-200 p-5 dark:border-surface-700"
    >
        <header class="space-y-2">
            <h2 class="flex items-center gap-3 font-semibold">
                <LockKeyhole class="size-4" />
                {{ t('ui.two_factor.recovery_codes.title') }}
            </h2>
            <p class="text-sm text-surface-500 dark:text-surface-400">
                {{ t('ui.two_factor.recovery_codes.body') }}
            </p>
        </header>
        <div class="mt-5">
            <div
                class="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between"
            >
                <Button @click="toggleRecoveryCodesVisibility">
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{
                        isRecoveryCodesVisible
                            ? t('ui.two_factor.recovery_codes.hide')
                            : t('ui.two_factor.recovery_codes.view')
                    }}
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        severity="secondary"
                        type="submit"
                        :disabled="processing"
                    >
                        <RefreshCw class="size-4" />
                        {{ t('ui.two_factor.recovery_codes.regenerate') }}
                    </Button>
                </Form>
            </div>
            <div
                :class="[
                    'relative overflow-hidden transition-all duration-300',
                    isRecoveryCodesVisible
                        ? 'h-auto opacity-100'
                        : 'h-0 opacity-0',
                ]"
            >
                <div v-if="errors?.length" class="mt-6">
                    <Message severity="error" :closable="false">
                        <div class="space-y-2">
                            <p class="font-medium">
                                {{ t('ui.messages.error_title') }}
                            </p>
                            <ul class="list-inside list-disc text-sm">
                                <li
                                    v-for="(error, index) in Array.from(
                                        new Set(errors),
                                    )"
                                    :key="index"
                                >
                                    {{ error }}
                                </li>
                            </ul>
                        </div>
                    </Message>
                </div>
                <div v-else class="mt-3 space-y-3">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="grid gap-1 rounded-lg bg-surface-100 p-4 font-mono text-sm dark:bg-surface-800"
                    >
                        <div v-if="!recoveryCodesList.length" class="space-y-2">
                            <div
                                v-for="n in 8"
                                :key="n"
                                class="h-4 animate-pulse rounded bg-surface-500/20 dark:bg-surface-400/20"
                            ></div>
                        </div>
                        <div
                            v-else
                            v-for="(code, index) in recoveryCodesList"
                            :key="index"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <p
                        class="text-xs text-surface-500 select-none dark:text-surface-400"
                    >
                        {{ t('ui.two_factor.recovery_codes.hint') }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
