<script setup lang="ts">
import { type Component } from 'vue'

interface Props {
  title: string
  description?: string
  icon?: Component
}

defineProps<Props>()
</script>

<template>
  <div class="flex min-h-[400px] flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center animate-in fade-in-50">
    <div class="mx-auto flex max-w-[420px] flex-col items-center justify-center text-center">
      <div class="flex h-20 w-20 items-center justify-center rounded-full bg-muted">
        <component
          :is="icon"
          class="h-10 w-10 text-muted-foreground"
          v-if="icon"
        />
      </div>

      <h3 class="mt-4 text-lg font-semibold">
        {{ title }}
      </h3>

      <p
        class="mt-2 text-sm text-muted-foreground"
        v-if="description"
      >
        {{ description }}
      </p>

      <slot name="actions" />
    </div>
  </div>
</template>
