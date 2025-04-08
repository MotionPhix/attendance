<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  ArrowLeft,
  Calendar,
  Check,
  Clock,
  DollarSign,
  Edit2,
  FileText,
  UserX,
  X
} from 'lucide-vue-next'
import dayjs from 'dayjs'
import LocalizedFormat from 'dayjs/plugin/localizedFormat'
import { useStorage } from '@vueuse/core';
import { toast } from 'vue-sonner';
import { BreadcrumbItem } from '@/types';
import MainAppLayout from '@/layouts/MainAppLayout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

dayjs.extend(LocalizedFormat)

interface Department {
  id: number
  name: string
}

interface Attendance {
  id: number
  check_in_time: string
  check_out_time: string | null
  status: 'on_time' | 'late' | 'early_departure'
  notes?: string
}

interface LeaveRequest {
  id: number
  type: string
  start_date: string
  end_date: string
  status: 'pending' | 'approved' | 'rejected'
  reason: string
}

interface SalaryRecord {
  id: number
  month: number
  year: number
  base_salary: number
  hourly_rate: number | null
  total_hours: number
  overtime_hours: number
  overtime_pay: number
  deductions: number
  net_salary: number
  status: 'pending' | 'processed' | 'paid'
  paid_at: string | null
}

interface Employee {
  id: number
  user: {
    id: number
    name: string
    email: string
    avatar?: string
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
  attendanceRecords: {
    data: Attendance[]
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
  leaveRequests: {
    data: LeaveRequest[]
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
  salaryRecords: {
    data: SalaryRecord[]
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

const props = defineProps<Props>()

const showEditDialog = ref(false)
const showDeactivateDialog = ref(false)
const processing = ref(false)
const activeTab = useStorage('employee_tabs', 'overview', sessionStorage)

// Format helpers
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}

const formatTime = (time: string | null) => {
  return time ? dayjs(time).format('LT') : '—'
}

const formatDate = (date: string) => {
  return dayjs(date).format('ll')
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    case 'on_leave':
      return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
    case 'suspended':
      return 'text-orange-500 bg-orange-50 dark:bg-orange-900/20'
    case 'terminated':
      return 'text-red-500 bg-red-50 dark:bg-red-900/20'
    // Attendance status colors
    case 'on_time':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    case 'late':
      return 'text-red-500 bg-red-50 dark:bg-red-900/20'
    case 'early_departure':
      return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
    // Leave request status colors
    case 'pending':
      return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
    case 'approved':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    case 'rejected':
      return 'text-red-500 bg-red-50 dark:bg-red-900/20'
    // Salary status colors
    case 'processed':
      return 'text-blue-500 bg-blue-50 dark:bg-blue-900/20'
    case 'paid':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    default:
      return 'text-gray-500 bg-gray-50 dark:bg-gray-900/20'
  }
}

// Form state
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

const handleUpdateEmployee = () => {
  processing.value = true

  router.put(route('admin.employees.update', props.employee.id), form.value, {
    onSuccess: () => {
      showEditDialog.value = false
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

const handleDeactivateEmployee = () => {
  processing.value = true

  router.delete(route('admin.employees.destroy', props.employee.id), {
    onSuccess: () => {
      router.visit(route('admin.employees.index'))
      toast({
        title: 'Success',
        description: 'Employee has been deactivated.',
      })
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

// Pagination handlers
const loadAttendancePage = (page: number) => {
  router.get(
    route('admin.employees.show', props.employee.id),
    { attendance_page: page },
    { preserveState: true }
  )
}

const loadLeavePage = (page: number) => {
  router.get(
    route('admin.employees.show', props.employee.id),
    { leave_page: page },
    { preserveState: true }
  )
}

const loadSalaryPage = (page: number) => {
  router.get(
    route('admin.employees.show', props.employee.id),
    { salary_page: page },
    { preserveState: true }
  )
}

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs([
  {
    label: 'Dashboard',
    href: route('admin.dashboard')
  },
  {
    label: 'Employees',
    href: route('admin.employees.index')
  },
  {
    label: props.employee.user.name
  }
]);

onMounted(() => {
  activeTab.value = 'overview'
})

onBeforeUnmount(() => {
  activeTab.value = null
})
</script>

<template>
  <MainAppLayout>
    <Head :title="employee.user.name" />

    <div class="container py-6">
      <!-- Employee header -->
      <div class="mb-6 flex items-center gap-4">
        <img
          :src="employee.user.avatar || `/avatars/default.png`"
          :alt="employee.user.name"
          class="h-20 w-20 rounded-full object-cover">

        <div>
          <h1 class="text-2xl font-semibold">
            {{ employee.user.name }}
          </h1>

          <p class="text-muted-foreground">
            {{ employee.position }} at {{ employee.department.name }}
          </p>
        </div>

        <div class="mb-6 flex-1 flex items-center justify-end">
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              @click="showEditDialog = true">
              <Edit2 class="mr-2 h-4 w-4" />
              Edit Details
            </Button>

            <Button
              v-if="employee.status !== 'terminated'"
              variant="destructive"
              @click="showDeactivateDialog = true">
              <UserX class="mr-2 h-4 w-4" />
              Terminate
            </Button>
          </div>
        </div>

      </div>

      <Tabs class="space-y-6" v-model="activeTab">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="leave">Leave</TabsTrigger>
          <TabsTrigger value="salary">Salary</TabsTrigger>
        </TabsList>

        <!-- Overview Tab -->
        <TabsContent value="overview">
          <div class="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Basic Information</CardTitle>
              </CardHeader>

              <CardContent>
                <dl class="grid gap-y-4 divide-y">
                  <div class="grid gap-y-2">
                    <dt class="font-medium text-muted-foreground">Status</dt>

                    <dd>
                      <span
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                        :class="getStatusColor(employee.status)">
                        {{ employee.status.replace('_', ' ') }}
                      </span>
                    </dd>
                  </div>

                  <div class="grid gap-y-2 pt-2">
                    <dt class="font-medium text-muted-foreground">Department</dt>
                    <dd>{{ employee.department.name }}</dd>
                  </div>

                  <div class="grid gap-y-2 pt-2">
                    <dt class="font-medium text-muted-foreground">Position</dt>
                    <dd>{{ employee.position }}</dd>
                  </div>

                  <div class="grid gap-y-2 pt-2">
                    <dt class="font-medium text-muted-foreground">Hire Date</dt>
                    <dd>{{ formatDate(employee.hire_date) }}</dd>
                  </div>

                  <div class="grid gap-y-2 pt-2">
                    <dt class="font-medium text-muted-foreground">Email</dt>
                    <dd>{{ employee.user.email }}</dd>
                  </div>

                  <div class="grid gap-y-2 pt-2">
                    <dt class="font-medium text-muted-foreground">Base Salary</dt>
                    <dd>{{ formatCurrency(employee.base_salary) }}</dd>
                  </div>

                  <div class="flex justify-between pt-2">
                    <dt class="font-medium text-muted-foreground">Hourly Rate</dt>
                    <dd>{{ employee.hourly_rate ? formatCurrency(employee.hourly_rate) : '—' }}</dd>
                  </div>
                </dl>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Attendance Tab -->
        <TabsContent value="attendance">
          <Card>
            <CardHeader>
              <CardTitle>Attendance History</CardTitle>
              <CardDescription>
                View attendance records and patterns for this employee.
              </CardDescription>
            </CardHeader>

            <CardContent>
              <div class="rounded-md border">
                <table class="w-full">
                  <thead>
                  <tr class="border-b bg-muted/50">
                    <th class="p-3 text-left font-medium">Date</th>
                    <th class="p-3 text-left font-medium">Check In</th>
                    <th class="p-3 text-left font-medium">Check Out</th>
                    <th class="p-3 text-left font-medium">Status</th>
                    <th class="p-3 text-left font-medium">Notes</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr
                    v-for="record in attendanceRecords.data"
                    :key="record.id"
                    class="border-b"
                  >
                    <td class="p-3">{{ formatDate(record.check_in_time) }}</td>
                    <td class="p-3">{{ formatTime(record.check_in_time) }}</td>
                    <td class="p-3">{{ formatTime(record.check_out_time) }}</td>
                    <td class="p-3">
                        <span
                          class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                          :class="getStatusColor(record.status)"
                        >
                          {{ record.status.replace('_', ' ') }}
                        </span>
                    </td>
                    <td class="p-3">{{ record.notes || '—' }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>

              <!-- Attendance Pagination -->
              <div
                v-if="attendanceRecords.last_page > 1"
                class="mt-4 flex items-center justify-between"
              >
                <p class="text-sm text-muted-foreground">
                  Showing {{ attendanceRecords.per_page }} of {{ attendanceRecords.total }} records
                </p>

                <div class="flex items-center gap-2">
                  <Button
                    :disabled="attendanceRecords.current_page === 1"
                    variant="outline"
                    size="sm"
                    @click="loadAttendancePage(attendanceRecords.current_page - 1)">
                    Previous
                  </Button>

                  <Button
                    :disabled="attendanceRecords.current_page === attendanceRecords.last_page"
                    variant="outline"
                    size="sm"
                    @click="loadAttendancePage(attendanceRecords.current_page + 1)">
                    Next
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Leave Tab -->
        <TabsContent value="leave">
          <Card>
            <CardHeader>
              <CardTitle>Leave Requests</CardTitle>
              <CardDescription>
                View leave requests and their status.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="rounded-md border">
                <table class="w-full">
                  <thead>
                  <tr class="border-b bg-muted/50">
                    <th class="p-3 text-left font-medium">Type</th>
                    <th class="p-3 text-left font-medium">Start Date</th>
                    <th class="p-3 text-left font-medium">End Date</th>
                    <th class="p-3 text-left font-medium">Status</th>
                    <th class="p-3 text-left font-medium">Reason</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr
                    v-for="request in leaveRequests.data"
                    :key="request.id"
                    class="border-b">
                    <td class="p-3">{{ request.type }}</td>
                    <td class="p-3">{{ formatDate(request.start_date) }}</td>
                    <td class="p-3">{{ formatDate(request.end_date) }}</td>
                    <td class="p-3">
                        <span
                          class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                          :class="getStatusColor(request.status)">
                          {{ request.status }}
                        </span>
                    </td>
                    <td class="p-3">{{ request.reason }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>

              <!-- Leave Pagination -->
              <div
                v-if="leaveRequests.last_page > 1"
                class="mt-4 flex items-center justify-between"
              >
                <p class="text-sm text-muted-foreground">
                  Showing {{ leaveRequests.per_page }} of {{ leaveRequests.total }} requests
                </p>

                <div class="flex items-center gap-2">
                  <Button
                    :disabled="leaveRequests.current_page === 1"
                    variant="outline"
                    size="sm"
                    @click="loadLeavePage(leaveRequests.current_page - 1)"
                  >
                    Previous
                  </Button>
                  <Button
                    :disabled="leaveRequests.current_page === leaveRequests.last_page"
                    variant="outline"
                    size="sm"
                    @click="loadLeavePage(leaveRequests.current_page + 1)"
                  >
                    Next
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Salary Tab -->
        <TabsContent value="salary">
          <Card>
            <CardHeader>
              <CardTitle>Salary History</CardTitle>
              <CardDescription>
                View salary records and payment history.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="rounded-md border">
                <table class="w-full">
                  <thead>
                  <tr class="border-b bg-muted/50">
                    <th class="p-3 text-left font-medium">Period</th>
                    <th class="p-3 text-left font-medium">Base</th>
                    <th class="p-3 text-left font-medium">Hours</th>
                    <th class="p-3 text-left font-medium">Overtime</th>
                    <th class="p-3 text-left font-medium">Deductions</th>
                    <th class="p-3 text-left font-medium">Net</th>
                    <th class="p-3 text-left font-medium">Status</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr
                    v-for="record in salaryRecords.data"
                    :key="record.id"
                    class="border-b"
                  >
                    <td class="p-3">{{ record.month }}/{{ record.year }}</td>
                    <td class="p-3">{{ formatCurrency(record.base_salary) }}</td>
                    <td class="p-3">{{ record.total_hours }}</td>
                    <td class="p-3 text-green-600">
                      +{{ formatCurrency(record.overtime_pay) }}
                      <span class="text-xs text-muted-foreground">
                          ({{ record.overtime_hours }}h)
                        </span>
                    </td>
                    <td class="p-3 text-red-600">
                      -{{ formatCurrency(record.deductions) }}
                    </td>
                    <td class="p-3 font-medium">
                      {{ formatCurrency(record.net_salary) }}
                    </td>
                    <td class="p-3">
                        <span
                          class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                          :class="getStatusColor(record.status)"
                        >
                          {{ record.status }}
                        </span>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>

              <!-- Salary Pagination -->
              <div
                v-if="salaryRecords.last_page > 1"
                class="mt-4 flex items-center justify-between"
              >
                <p class="text-sm text-muted-foreground">
                  Showing {{ salaryRecords.per_page }} of {{ salaryRecords.total }} records
                </p>

                <div class="flex items-center gap-2">
                  <Button
                    :disabled="salaryRecords.current_page === 1"
                    variant="outline"
                    size="sm"
                    @click="loadSalaryPage(salaryRecords.current_page - 1)"
                  >
                    Previous
                  </Button>
                  <Button
                    :disabled="salaryRecords.current_page === salaryRecords.last_page"
                    variant="outline"
                    size="sm"
                    @click="loadSalaryPage(salaryRecords.current_page + 1)"
                  >
                    Next
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <!-- Edit Employee Dialog -->
      <Dialog v-model:open="showEditDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Employee Details</DialogTitle>
            <DialogDescription>
              Update the employee's information. These changes will be reflected immediately.
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
                name="status"
              >
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
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showEditDialog = false"
            >
              Cancel
            </Button>
            <Button
              :disabled="processing"
              @click="handleUpdateEmployee"
            >
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Terminate Employee Dialog -->
      <Dialog v-model:open="showDeactivateDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Terminate Employee</DialogTitle>
            <DialogDescription>
              Are you sure you want to terminate this employee? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showDeactivateDialog = false"
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              :disabled="processing"
              @click="handleDeactivateEmployee"
            >
              Terminate
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </MainAppLayout>
</template>
