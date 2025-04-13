<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Plus, Search, UserPlus, XCircle } from 'lucide-vue-next'
import { debounce } from 'lodash'
import { toast } from 'vue-sonner';
import EmptyState from '@/components/EmptyState.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Department, Employee } from '@/types';

interface Props {
  employees: {
    data: Employee[]
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  departments: Department[]
  filters: {
    search: string
    department: string
    status: string
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')
const selectedDepartment = ref(props.filters.department || '')
const selectedStatus = ref(props.filters.status || '')

const hasEmployees = computed(() => props.employees.data.length > 0)
const hasActiveFilters = computed(() => search.value || selectedDepartment.value || selectedStatus.value)

// Add Employee Dialog
const showAddDialog = ref(false)
const processing = ref(false)
const form = ref({
  name: '',
  email: '',
  department_id: '',
  position: '',
  employee_id: '',
  join_date: '',
})

const updateFilters = debounce(() => {
  router.get(route('admin.employees.index'), {
    search: search.value,
    department: selectedDepartment.value,
    status: selectedStatus.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

const emptyStateProps = computed(() => {
  if (!hasEmployees.value && !hasActiveFilters.value) {
    return {
      title: 'No employees yet',
      description: 'Get started by adding your first employee.',
      icon: Users2,
      action: {
        label: 'Add Employee',
        onClick: () => showAddDialog.value = true
      }
    }
  }

  return {
    title: 'No matching employees',
    description: 'Try adjusting your search or filter criteria.',
    icon: Filter,
    action: {
      label: 'Clear Filters',
      onClick: clearFilters
    }
  }
})

const handleAddEmployee = () => {
  processing.value = true

  router.post(route('admin.employees.store'), form.value, {
    onSuccess: () => {
      showAddDialog.value = false
      form.value = {
        name: '',
        email: '',
        department_id: '',
        position: '',
        employee_id: '',
        join_date: '',
      }
      toast({
        title: 'Success',
        description: 'Employee has been added successfully.',
      })
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    case 'inactive':
      return 'text-red-500 bg-red-50 dark:bg-red-900/20'
    default:
      return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
  }
}

const clearFilters = () => {
  search.value = ''
  selectedDepartment.value = ''
  selectedStatus.value = ''
  updateFilters()
}

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs([
  { label: 'Home', href: route('admin.dashboard') },
  { label: 'Employees', href: route('admin.employees.index') },
]);
</script>

<template>
  <AppLayout>
    <Head title="Employees" />

    <div class="container py-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold tracking-tight">
          Employees
        </h2>

        <Button @click="showAddDialog = true">
          <UserPlus class="mr-2 h-4 w-4" />
          Add Employee
        </Button>
      </div>

      <!-- Filters -->
      <div class="mt-6 flex items-center gap-4">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            v-model="search"
            class="pl-9"
            placeholder="Search employees..."
            @input="updateFilters"
          />
        </div>

        <Select
          v-model="selectedDepartment"
          @update:model-value="updateFilters"
        >
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="All Departments" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">All Departments</SelectItem>
            <SelectItem
              v-for="department in departments"
              :key="department.id"
              :value="department.id.toString()">
              {{ department.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select
          v-model="selectedStatus"
          @update:model-value="updateFilters">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="All Status" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem :value="null">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
            <SelectItem value="on_leave">On Leave</SelectItem>
          </SelectContent>
        </Select>

        <!-- Clear Filters Button - Only show when filters are active -->
        <Button
          v-if="hasActiveFilters"
          variant="ghost"
          size="sm"
          @click="clearFilters">
          <XCircle class="mr-2 h-4 w-4" />
          Clear filters
        </Button>
      </div>

      <!-- Employees Table -->
      <div v-if="hasEmployees" class="mt-6 rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Employee</TableHead>
              <TableHead>Department</TableHead>
              <TableHead>Position</TableHead>
              <TableHead>Employee Id</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="w-[100px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="employee in employees.data"
              :key="employee.id">
              <TableCell>
                <div class="flex items-center gap-3">
                  <img
                    :src="employee.user.avatar_url"
                    :alt="employee.user?.name"
                    class="h-20 w-16 object-cover rounded-lg shrink-0">

                  <div>
                    <div class="font-medium">
                      {{ employee.user?.name }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                      {{ employee.user?.email }}
                    </div>
                  </div>
                </div>
              </TableCell>
              <TableCell>{{ employee.department?.name }}</TableCell>
              <TableCell>{{ employee.position }}</TableCell>
              <TableCell>{{ employee.employee_id }}</TableCell>
              <TableCell>
                <span
                  class="inline-flex capitalize items-center rounded-full px-2 py-1 text-xs font-medium"
                  :class="getStatusColor(employee.status)">
                  {{ employee.status?.replace('_', ' ') }}
                </span>
              </TableCell>
              <TableCell>
                <Button
                  variant="ghost"
                  size="sm"
                  :as="Link"
                  :href="route('admin.employees.show', employee.id)">
                  View
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <EmptyState
        v-else
        :title="emptyStateProps.title"
        :description="emptyStateProps.description"
        :icon="emptyStateProps.icon">
        <template #actions>
          <Button @click="emptyStateProps.action.onClick">
            {{ emptyStateProps.action.label }}
          </Button>
        </template>
      </EmptyState>

      <!-- Add Employee Dialog -->
      <Dialog v-model:open="showAddDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add New Employee</DialogTitle>
            <DialogDescription>
              Create a new employee account. They will receive an email to set up their password.
            </DialogDescription>
          </DialogHeader>

          <div class="grid gap-4">
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
                name="department"
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select department" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="department in departments"
                    :key="department.id"
                    :value="department.id.toString()"
                  >
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
              <label for="employee_id">Employee ID</label>
              <Input
                id="employee_id"
                v-model="form.employee_id"
                placeholder="EMP001"
              />
            </div>

            <div class="grid gap-2">
              <label for="join_date">Join Date</label>
              <Input
                id="join_date"
                v-model="form.join_date"
                type="date"
              />
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showAddDialog = false"
            >
              Cancel
            </Button>
            <Button
              :disabled="processing"
              @click="handleAddEmployee"
            >
              Add Employee
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
