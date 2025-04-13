<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import GlobalModal from '@/components/GlobalModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import { Edit2 } from 'lucide-vue-next';
import AvatarUpload from '@/components/AvatarUpload.vue';
import { Department, Employee } from '@/types';
import { Label } from '@/components/ui/label';

interface Props {
  employee: Employee;
  departments: Department[];
}

const props = defineProps<Props>();
const processing = ref(false);
const editEmployeeRef = ref();
const avatarFile = ref<File | null>(null);
const uploadingAvatar = ref(false);

const form = ref({
  name: props.employee.user.name,
  email: props.employee.user.email,
  department_id: props.employee.department.id.toString(),
  position: props.employee.position,
  hire_date: props.employee.hire_date,
  base_salary: props.employee.base_salary.toString(),
  hourly_rate: props.employee.hourly_rate?.toString() ?? '',
  status: props.employee.status
});

const handleSubmit = () => {
  processing.value = true;

  router.put(route('admin.employees.update', props.employee.id), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Employee details have been updated.'
      });
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const handleAvatarUpload = () => {
  if (!avatarFile.value) return;

  const formData = new FormData();
  formData.append('avatar', avatarFile.value);

  uploadingAvatar.value = true;

  router.post(route('admin.employees.update-avatar', props.employee.id), formData, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Success', {
        description: 'Avatar has been updated.'
      });
    },
    onError: (errors) => {
      toast.error('Error', {
        description: errors.avatar || 'Failed to update avatar',
      });
    },
    onFinish: () => {
      uploadingAvatar.value = false;
    }
  });
};

watch(avatarFile, (newFile) => {
  if (newFile) {
    handleAvatarUpload();
  }
});
</script>

<template>
  <GlobalModal
    ref="editEmployeeRef"
    modalTitle="Edit Employee Details"
    description="Update the employee's information. These changes will be reflected immediately."
    :icon="Edit2"
    maxWidth="xl">
    <form class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5">
      <div class="sticky top-24">
        <AvatarUpload
          v-model="avatarFile"
          :user="employee.user"
          :uploading="uploadingAvatar"
          size="lg"
        />
      </div>

      <section class="sm:col-span-2 space-y-4">

        <div class="grid gap-2">
          <Label for="name">Full Name</Label>
          <Input
            id="name"
            v-model="form.name"
            placeholder="John Doe"
          />
        </div>

        <div class="grid gap-2">
          <Label for="email">Email Address</Label>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="john@example.com"
          />
        </div>

        <div class="grid gap-2">
          <Label for="department">Department</Label>

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
          <Label for="position">Position</Label>

          <Input
            id="position"
            v-model="form.position"
            placeholder="Software Engineer"
          />
        </div>

        <div class="grid gap-2">
          <Label for="hire_date">Hire Date</Label>

          <Input
            id="hire_date"
            v-model="form.hire_date"
            type="date"
          />
        </div>

        <div class="grid gap-2">
          <Label for="base_salary">Base Salary</Label>

          <Input
            id="base_salary"
            v-model="form.base_salary"
            type="number"
            step="0.01"
            min="0"
          />
        </div>

        <div class="grid gap-2">
          <Label for="hourly_rate">Hourly Rate (Optional)</Label>

          <Input
            id="hourly_rate"
            v-model="form.hourly_rate"
            type="number"
            step="0.01"
            min="0"
          />
        </div>

        <div class="grid gap-2">
          <Label for="status">Status</Label>

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

      </section>
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
