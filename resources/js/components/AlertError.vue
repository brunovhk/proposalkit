<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type Props = {
    errors: string[];
    title?: string;
};

const props = defineProps<Props>();

const { t } = useTranslations();
const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
const title = computed(() => props.title ?? t('ui.messages.error_title'));
</script>

<template>
    <Message severity="error" :closable="false">
        <div class="space-y-2">
            <p class="font-medium">{{ title }}</p>
            <ul class="list-inside list-disc text-sm">
                <li v-for="(error, index) in uniqueErrors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </div>
    </Message>
</template>
