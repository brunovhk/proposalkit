<script setup lang="ts">
import { computed, useAttrs, useTemplateRef } from 'vue';

type PasswordInputClass = string | Record<string, boolean>;

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: PasswordInputClass;
}>();

const attrs = useAttrs();
const passwordRef = useTemplateRef('passwordRef');
const inputId = computed(() =>
    typeof attrs.id === 'string' ? attrs.id : undefined,
);

defineExpose({
    $el: passwordRef,
    focus: () => {
        const element = (passwordRef.value as { $el?: HTMLElement } | null)
            ?.$el;

        element?.querySelector<HTMLInputElement>('input')?.focus();
    },
});
</script>

<template>
    <Password
        ref="passwordRef"
        :input-id="inputId"
        :input-class="props.class"
        :feedback="false"
        toggle-mask
        fluid
        v-bind="$attrs"
    />
</template>
