`<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/stores/breadcrumbStore';

interface Props {
  breadcrumbs: BreadcrumbItem[];
}

defineProps<Props>();
</script>

<template>
  <nav aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 text-sm">
      <li v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="flex items-center">
        <template v-if="index > 0">
          <ChevronRight class="mx-1 h-4 w-4 text-muted-foreground" />
        </template>

        <Link
          v-if="breadcrumb.href && index < breadcrumbs.length - 1"
          :href="breadcrumb.href"
          class="text-muted-foreground hover:text-foreground">
          {{ breadcrumb.label }}
        </Link>

        <span v-else :class="{ 'font-medium text-foreground': index === breadcrumbs.length - 1 }">
          {{ breadcrumb.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>`
