<script setup lang="ts">
import { ref } from 'vue'
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

interface Department {
  id: number
  name: string
}

interface Employee {
  id: number
  user: {
    name: string
    email: string
  }
  department: Department
  position: string
  hire_date: string
  base_salary: number
  hourly_rate: number | null
  status: 'active' | 'on_leave' | 'suspended' | 'terminated'
}

interface Props {
  employee: Employee
  departments: Department[]
}

const props = defineProps<Props>()
const processing = ref(false)
const editEmployeeRef = ref()

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
</script>

<template>
  <GlobalModal
    ref="editEmployeeRef"
    modalTitle="Edit Employee Details"
    description="Update the employee's information. These changes will be reflected immediately."
    :icon="Edit2"
    maxWidth="xl">
    <form class="space-y-4">
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
