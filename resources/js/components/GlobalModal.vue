<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue'
import { type Component, ref } from "vue"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const modalRef = ref()

withDefaults(
  defineProps<{
    manualClose?: boolean
    placement?: 'center' | 'top' | 'bottom'
    hasCloseButton?: boolean
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    padding?: string
    modalTitle?: string
    description?: string
    icon?: Component
  }>(), {
    maxWidth: 'md',
    placement: 'center',
    manualClose: true,
    hasCloseButton: false,
    padding: 'p-0'
  }
)

function close() {
  modalRef.value.close()
}

defineExpose({
  close,
})
</script>

<template>
  <Modal
    ref="modalRef"
    :max-width="maxWidth"
    :position="placement"
    :paddingClasses="padding"
    :close-explicitly="manualClose"
    :close-button="hasCloseButton"
    panel-classes="relative dark:text-muted-foreground max-h-[80vh] overflow-y-auto scrollbar-none scroll-smooth bg-background dark:bg-background rounded-lg shadow-lg"
  >
    <Card class="border-0 shadow-none">
      <CardHeader
        v-if="modalTitle"
        class="sticky top-0 z-10 space-y-1.5 pb-6 bg-gray-100 dark:bg-gray-800 dark:text-gray-400"
      >
        <CardTitle class="flex items-center gap-2 text-lg font-semibold leading-none tracking-tight">
          <component :is="icon" v-if="icon" class="h-5 w-5" />
          {{ modalTitle }}
        </CardTitle>

        <CardDescription v-if="description" class="text-sm text-muted-foreground">
          {{ description }}
        </CardDescription>
      </CardHeader>

      <CardContent>
        <slot />
      </CardContent>

      <CardFooter
        v-if="$slots.footer"
        class="flex justify-end space-x-2 pt-6"
      >
        <slot name="footer" />
      </CardFooter>
    </Card>
  </Modal>
</template>

<style>
/* Add smooth scrollbar styling */
.scrollbar-none {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.scrollbar-none::-webkit-scrollbar {
  display: none;
}

/* Add transitions for the modal */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
