<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/app/AdminLayout.vue';
import EmployeeLayout from '@/layouts/app/EmployeeLayout.vue';
import { Toaster } from 'vue-sonner';
import { useDark } from '@vueuse/core';

const page = usePage();
const user = computed(() => page.props.auth.user);

const isDark = useDark()
</script>

<template>
  <Toaster rich-colors :expand="true" :theme="isDark" />

  <AdminLayout v-if="user.isAdmin">
    <slot />
  </AdminLayout>

  <EmployeeLayout v-else>
    <slot />
  </EmployeeLayout>
</template>
