<script setup lang="ts">
import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import type { BreadcrumbItem } from '@/types'

interface Props {
  breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [{
    label: 'Dashboard',
    href: route('admin.dashboard')
  }]
})
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />

    <AppContent variant="sidebar">
      <AppSidebarHeader>
        <Breadcrumbs v-if="breadcrumbs" :breadcrumbs="breadcrumbs" />
      </AppSidebarHeader>

      <div class="p-4">
        <slot />
      </div>
    </AppContent>
  </AppShell>
</template>
