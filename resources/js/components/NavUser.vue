<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useTranslations();
const userMenu = ref<InstanceType<typeof UserMenuContent> | null>(null);

const toggleUserMenu = (event: Event): void => {
    userMenu.value?.toggle(event);
};
</script>

<template>
    <div>
        <Button
            type="button"
            text
            class="w-full justify-between"
            :aria-label="t('ui.user_menu.open')"
            aria-haspopup="true"
            aria-controls="sidebar_user_menu"
            data-test="sidebar-menu-button"
            @click="toggleUserMenu"
        >
            <UserInfo :user="user" />
            <ChevronsUpDown class="ml-auto size-4" />
        </Button>
        <UserMenuContent
            id="sidebar_user_menu"
            ref="userMenu"
            :user="user"
            popup
        />
    </div>
</template>
