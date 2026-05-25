<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Check, Copy, ScanLine } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();
const { t } = useTranslations();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: t('ui.two_factor.setup.enabled_title'),
            description: t('ui.two_factor.setup.enabled_description'),
            buttonText: t('ui.actions.close'),
        };
    }

    if (showVerificationStep.value) {
        return {
            title: t('ui.two_factor.setup.verify_title'),
            description: t('ui.two_factor.setup.verify_description'),
            buttonText: t('ui.actions.continue'),
        };
    }

    return {
        title: t('ui.two_factor.setup.start_title'),
        description: t('ui.two_factor.setup.start_description'),
        buttonText: t('ui.actions.continue'),
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();

            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog
        v-model:visible="isOpen"
        :dismissableMask="true"
        modal
        :draggable="false"
        class="w-[min(32rem,calc(100vw-2rem))]"
    >
        <template #header>
            <div
                class="flex w-full flex-col items-center justify-center gap-2 text-center"
            >
                <div
                    class="mb-3 w-auto rounded-full border border-surface-200 bg-surface-0 p-0.5 shadow-sm dark:border-surface-700 dark:bg-surface-900"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-surface-200 bg-surface-100 p-2.5 dark:border-surface-700 dark:bg-surface-800"
                    >
                        <div
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-r border-surface-200 last:border-r-0 dark:border-surface-700"
                            />
                        </div>
                        <div
                            class="absolute inset-0 grid grid-rows-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`row-${i}`"
                                class="border-b border-surface-200 last:border-b-0 dark:border-surface-700"
                            />
                        </div>
                        <ScanLine
                            class="relative z-20 size-6 text-surface-900 dark:text-surface-0"
                        />
                    </div>
                </div>
                <h2 class="text-lg font-semibold">{{ modalConfig.title }}</h2>
                <p
                    class="max-w-sm text-sm text-surface-500 dark:text-surface-400"
                >
                    {{ modalConfig.description }}
                </p>
            </div>
        </template>

        <div
            class="relative flex w-auto flex-col items-center justify-center space-y-5"
        >
            <template v-if="!showVerificationStep">
                <Message
                    v-if="errors?.length"
                    severity="error"
                    :closable="false"
                >
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
                <template v-else>
                    <div
                        class="relative mx-auto flex max-w-md items-center overflow-hidden"
                    >
                        <div
                            class="relative mx-auto aspect-square w-64 overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700"
                        >
                            <div
                                v-if="!qrCodeSvg"
                                class="absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center bg-surface-0 dark:bg-surface-900"
                            >
                                <ProgressSpinner
                                    class="size-6"
                                    stroke-width="4"
                                />
                            </div>
                            <div
                                v-else
                                class="relative z-10 overflow-hidden border p-5"
                            >
                                <div
                                    v-html="qrCodeSvg"
                                    class="flex aspect-square size-full items-center justify-center"
                                    :style="{
                                        filter:
                                            resolvedAppearance === 'dark'
                                                ? 'invert(1) brightness(1.5)'
                                                : undefined,
                                    }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full items-center">
                        <Button fluid @click="handleModalNextStep">
                            {{ modalConfig.buttonText }}
                        </Button>
                    </div>

                    <div
                        class="relative flex w-full items-center justify-center"
                    >
                        <div
                            class="absolute inset-0 top-1/2 h-px w-full bg-border"
                        />
                        <span
                            class="relative bg-surface-0 px-2 py-1 dark:bg-surface-900"
                        >
                            {{ t('ui.two_factor.setup.manual_separator') }}
                        </span>
                    </div>

                    <div
                        class="flex w-full items-center justify-center space-x-2"
                    >
                        <div
                            class="flex w-full items-stretch overflow-hidden rounded-xl border border-surface-200 dark:border-surface-700"
                        >
                            <div
                                v-if="!manualSetupKey"
                                class="flex h-full w-full items-center justify-center bg-surface-100 p-3 dark:bg-surface-800"
                            >
                                <ProgressSpinner
                                    class="size-5"
                                    stroke-width="4"
                                />
                            </div>
                            <template v-else>
                                <InputText
                                    readonly
                                    :value="manualSetupKey"
                                    fluid
                                />
                                <Button
                                    type="button"
                                    text
                                    :aria-label="
                                        t('ui.two_factor.setup.copy_key')
                                    "
                                    @click="copy(manualSetupKey || '')"
                                    class="relative block h-auto rounded-none border-l border-surface-200 px-3 dark:border-surface-700"
                                >
                                    <Check
                                        v-if="copied"
                                        class="w-4 text-green-500"
                                    />
                                    <Copy v-else class="w-4" />
                                </Button>
                            </template>
                        </div>
                    </div>
                </template>
            </template>

            <template v-else>
                <Form
                    v-bind="confirm.form()"
                    error-bag="confirmTwoFactorAuthentication"
                    reset-on-error
                    @finish="code = ''"
                    @success="isOpen = false"
                    v-slot="{ errors, processing }"
                >
                    <input type="hidden" name="code" :value="code" />
                    <div
                        ref="pinInputContainerRef"
                        class="relative w-full space-y-3"
                    >
                        <div
                            class="flex w-full flex-col items-center justify-center space-y-3 py-2"
                        >
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :length="6"
                                integer-only
                                :disabled="processing"
                                :invalid="Boolean(errors?.code)"
                                autofocus
                            />
                            <InputError :message="errors?.code" />
                        </div>

                        <div class="flex w-full items-center space-x-5">
                            <Button
                                type="button"
                                outlined
                                class="flex-1"
                                @click="showVerificationStep = false"
                                :disabled="processing"
                            >
                                {{ t('ui.actions.back') }}
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1"
                                :disabled="processing || code.length < 6"
                                :loading="processing"
                            >
                                {{ t('ui.actions.confirm') }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </template>
        </div>
    </Dialog>
</template>
