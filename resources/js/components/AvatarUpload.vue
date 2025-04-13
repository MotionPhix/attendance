<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Camera, Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { User } from '@/types';

interface Props {
  modelValue?: File | null
  user?: User
  size?: 'sm' | 'md' | 'lg'
  shape?: 'square' | 'circle'
  uploading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  shape: 'circle',
  uploading: false,
  user: undefined
})

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)

const containerSize = computed(() => {
  switch (props.size) {
    case 'sm': return 'h-20 w-20'
    case 'lg': return 'h-32 w-32'
    default: return 'h-24 w-24'
  }
})

// Get the current avatar URL from either the uploaded media or the default avatar system
const getCurrentAvatarUrl = computed(() => {
  if (props.user) {
    return props.user.avatar_url
  }
  return '/avatars/default.png'
})

const previewImage = ref<string>(getCurrentAvatarUrl.value)

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = false

  if (e.dataTransfer?.files.length) {
    handleFileChange(e.dataTransfer.files[0])
  }
}

const handleFileChange = (file: File | undefined) => {
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast({
      title: 'Invalid file type',
      description: 'Please upload an image file',
      variant: 'destructive'
    })
    return
  }

  // Check file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    toast({
      title: 'File too large',
      description: 'Image must be less than 2MB',
      variant: 'destructive'
    })
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    previewImage.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
  emit('update:modelValue', file)
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

// Reset preview image when user prop changes
watch(() => props.user, (newUser) => {
  if (newUser) {
    previewImage.value = getCurrentAvatarUrl.value
  }
})

// Initialize with current avatar on mount
onMounted(() => {
  previewImage.value = getCurrentAvatarUrl.value
})
</script>

<template>
  <div
    :class="[
      'relative group cursor-pointer border-2 border-dashed border-muted-foreground/25 rounded-lg overflow-hidden',
      containerSize,
      { 'border-primary': isDragging }
    ]"
    @click="triggerFileInput"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop="handleDrop"
  >
    <input
      ref="fileInput"
      type="file"
      class="hidden"
      accept="image/*"
      @change="handleFileChange($event.target.files?.[0])"
    >

    <img
      :src="previewImage"
      :alt="user?.name || 'Avatar'"
      :class="[
        'w-full h-full object-cover transition-opacity duration-300 bg-muted',
        { 'opacity-50': uploading }
      ]"
    >

    <div
      class="absolute inset-0 flex items-center justify-center bg-background/80 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
    >
      <div class="text-center p-2">
        <Camera class="h-6 w-6 mx-auto mb-2" />
        <span class="text-xs">
          {{ uploading ? 'Uploading...' : 'Change Avatar' }}
        </span>
      </div>
    </div>

    <div
      v-if="uploading"
      class="absolute inset-0 flex items-center justify-center bg-background/50"
    >
      <Loader2 class="h-6 w-6 animate-spin" />
    </div>
  </div>
</template>
