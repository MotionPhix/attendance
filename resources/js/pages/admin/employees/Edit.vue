<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'
import GlobalModal from '@/components/GlobalModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import { Edit2 } from 'lucide-vue-next'
import AvatarUpload from '@/components/AvatarUpload.vue';
import { Department, Employee } from '@/types';

interface Props {
  employee: Employee
  departments: Department[]
}

const props = defineProps<Props>()
const processing = ref(false)
const editEmployeeRef = ref()
const avatarFile = ref<File | null>(null)
const uploadingAvatar = ref(false)

const form = ref({
  name: props.employee.user.name,
  email: props.employee.user.email,
  department_id: props.employee.department.id.toString(),
  position: props.employee.position,
  hire_date: props.employee.hire_date,
  base_salary: props.employee.base_salary.toString(),
  hourly_rate: props.employee.hourly_rate?.toString() ?? '',
  status: props.employee.status,
})

const handleSubmit = () => {
  processing.value = true

  router.put(route('admin.employees.update', props.employee.id), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Employee details have been updated.',
      })
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

const handleAvatarUpload = () => {
  if (!avatarFile.value) return

  const formData = new FormData()
  formData.append('avatar', avatarFile.value)

  uploadingAvatar.value = true

  router.post(route('admin.employees.update-avatar', props.employee.id), formData, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Avatar has been updated.',
      })
    },
    onError: (errors) => {
      toast({
        title: 'Error',
        description: errors.avatar || 'Failed to update avatar',
        variant: 'destructive'
      })
    },
    onFinish: () => {
      uploadingAvatar.value = false
    }
  })
}

watch(avatarFile, (newFile) => {
  if (newFile) {
    handleAvatarUpload()
  }
})
</script>

<template>
  <GlobalModal
    ref="editEmployeeRef"
    modalTitle="Edit Employee Details"
    description="Update the employee's information. These changes will be reflected immediately."
    :icon="Edit2"
    maxWidth="xl">
    <form class="space-y-4">
      <div class="flex justify-center">
        <AvatarUpload
          v-model="avatarFile"
          :current-avatar="employee.user.avatar"
          :uploading="uploadingAvatar"
          size="lg"
        />
      </div>

      <div class="grid gap-2">
        <label for="name">Full Name</label>
        <Input
          id="name"
          v-model="form.name"
          placeholder="John Doe"
        />
      </div>

      <div class="grid gap-2">
        <label for="email">Email Address</label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          placeholder="john@example.com"
        />
      </div>

      <div class="grid gap-2">
        <label for="department">Department</label>

        <Select
          v-model="form.department_id"
          name="department">
          <SelectTrigger>
            <SelectValue placeholder="Select department" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem
              v-for="department in departments"
              :key="department.id"
              :value="department.id.toString()">
              {{ department.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="grid gap-2">
        <label for="position">Position</label>

        <Input
          id="position"
          v-model="form.position"
          placeholder="Software Engineer"
        />
      </div>

      <div class="grid gap-2">
        <label for="hire_date">Hire Date</label>

        <Input
          id="hire_date"
          v-model="form.hire_date"
          type="date"
        />
      </div>

      <div class="grid gap-2">
        <label for="base_salary">Base Salary</label>

        <Input
          id="base_salary"
          v-model="form.base_salary"
          type="number"
          step="0.01"
          min="0"
        />
      </div>

      <div class="grid gap-2">
        <label for="hourly_rate">Hourly Rate (Optional)</label>

        <Input
          id="hourly_rate"
          v-model="form.hourly_rate"
          type="number"
          step="0.01"
          min="0"
        />
      </div>

      <div class="grid gap-2">
        <label for="status">Status</label>
        <Select
          v-model="form.status"
          name="status">
          <SelectTrigger>
            <SelectValue placeholder="Select status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="on_leave">On Leave</SelectItem>
            <SelectItem value="suspended">Suspended</SelectItem>
            <SelectItem value="terminated">Terminated</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </form>

    <template #footer>
      <Button
        type="button"
        variant="outline"
        @click="editEmployeeRef.close()">
        Cancel
      </Button>

      <Button
        type="submit"
        @click="handleSubmit"
        :disabled="processing">
        Save Changes
      </Button>
    </template>
  </GlobalModal>
</template>
