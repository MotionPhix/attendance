<script setup lang="ts">
import {Modal} from '@inertiaui/modal-vue'
import {type Component, ref} from "vue";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

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
    manualClose: true
  }
)

function close() {
  modalRef.value.close()
}

defineExpose({
  close,
});
</script>

<template>
  <Modal
    ref="modalRef"
    :max-width="maxWidth"
    :position="placement"
    :paddingClasses="padding"
    :close-explicitly="manualClose"
    :close-button="hasCloseButton"
    panel-classes="dark:text-muted-foreground max-h-[80svh] overflow-y-auto scrollbar-none scroll-smooth">
    <Card>

      <CardHeader
        v-if="modalTitle"
        class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-800">
        <CardTitle class="flex items-center gap-2">
          <component :is="icon"/>
          {{ modalTitle }}
        </CardTitle>

        <CardDescription v-if="description">
          {{ description }}
        </CardDescription>
      </CardHeader>

     <CardContent>
       <slot/>
     </CardContent>

      <CardFooter class="justify-end gap-x-2" v-if="$slots.footer">
        <slot name="footer" />
      </CardFooter>
    </Card>
  </Modal>
</template>
