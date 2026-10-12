<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M20 8h19l11 11v31a6 6 0 0 1-6 6H20a6 6 0 0 1-6-6V14a6 6 0 0 1 6-6Zm3.6 28.4a2.25 2.25 0 0 0 0 3.2l6 6a2.25 2.25 0 0 0 3.3-.1l11-12a2.25 2.25 0 1 0-3.3-3L31.3 40.7l-4.5-4.3a2.25 2.25 0 0 0-3.2 0Z"
        />
    </svg>
</template>
