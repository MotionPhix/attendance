<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/app/AdminLayout.vue';
import EmployeeLayout from '@/layouts/app/EmployeeLayout.vue';

const page = usePage();

const user = computed(() => page.props.auth.user);
</script>

<template>
  <AdminLayout v-if="user.isAdmin">
    <slot />
  </AdminLayout>

  <EmployeeLayout v-else>
    <slot />
  </EmployeeLayout>
</template>
