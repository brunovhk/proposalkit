<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { useTranslations } from '@/composables/useTranslations';

const passwordInput = useTemplateRef('passwordInput');
const visible = ref(false);
const { t } = useTranslations();
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('ui.settings.delete_user.title')"
            :description="t('ui.settings.delete_user.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">
                    {{ t('ui.settings.delete_user.warning_title') }}
                </p>
                <p class="text-sm">
                    {{ t('ui.settings.delete_user.warning_body') }}
                </p>
            </div>
            <Button
                severity="danger"
                data-test="delete-user-button"
                @click="visible = true"
            >
                {{ t('ui.settings.delete_user.submit') }}
            </Button>
            <Dialog
                v-model:visible="visible"
                modal
                :header="t('ui.settings.delete_user.confirm_title')"
                :draggable="false"
                class="w-[min(32rem,calc(100vw-2rem))]"
            >
                <Form
                    v-bind="ProfileController.destroy.form()"
                    reset-on-success
                    @error="() => passwordInput?.focus()"
                    @success="visible = false"
                    :options="{
                        preserveScroll: true,
                    }"
                    class="space-y-6"
                    v-slot="{ errors, processing, reset, clearErrors }"
                >
                    <p class="text-sm text-surface-500 dark:text-surface-400">
                        {{ t('ui.settings.delete_user.confirm_description') }}
                    </p>

                    <div class="grid gap-2">
                        <label for="password" class="sr-only">
                            {{ t('ui.fields.password') }}
                        </label>
                        <PasswordInput
                            id="password"
                            name="password"
                            ref="passwordInput"
                            :placeholder="t('ui.fields.password')"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            severity="secondary"
                            @click="
                                () => {
                                    clearErrors();
                                    reset();
                                    visible = false;
                                }
                            "
                        >
                            {{ t('ui.actions.cancel') }}
                        </Button>

                        <Button
                            type="submit"
                            severity="danger"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            {{ t('ui.settings.delete_user.submit') }}
                        </Button>
                    </div>
                </Form>
            </Dialog>
        </div>
    </div>
</template>
