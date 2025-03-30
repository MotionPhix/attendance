`<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar :is-admin="true" />

    <AppContent variant="sidebar">
      <AppSidebarHeader />

      <div class="p-4">
        <slot />
      </div>
    </AppContent>
  </AppShell>
</template>`
