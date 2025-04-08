<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, Clock, DollarSign, UserRound } from 'lucide-vue-next'
import { Button } from '@/components/ui/button';

defineProps<{
  currentPath: string
}>()
</script>

<template>
  <div class="mb-6 flex flex-wrap">
    <Button
      :as="Link"
      variant="outline"
      class="border-e-0 rounded-e-none"
      :class="{ 'bg-muted': currentPath === 'admin.settings.index' }"
      :href="route('admin.settings.index')">
      <Building2 class="mr-2 h-4 w-4" />
      General
    </Button>

    <Button
      :as="Link"
      variant="outline"
      :class="{ 'bg-muted': currentPath === 'admin.settings.attendance' }"
      class="rounded-s-none rounded-e-none"
      :href="route('admin.settings.attendance')">
      <Clock class="mr-2 h-4 w-4" />
      Attendance
    </Button>

    <Button
      :as="Link"
      variant="outline"
      class="rounded-s-none rounded-e-none"
      :class="{ 'bg-muted': currentPath === 'admin.settings.leave' }"
      :href="route('admin.settings.leave')">
      <UserRound class="mr-2 h-4 w-4" />
      Leave
    </Button>

    <Button
      :as="Link"
      variant="outline"
      class="border-s-0 rounded-s-none"
      :class="{ 'bg-muted': currentPath === 'admin.settings.salary' }"
      :href="route('admin.settings.salary')">
      <DollarSign class="mr-2 h-4 w-4" />
      Salary
    </Button>
  </div>
</template>

<style scoped>

</style>
