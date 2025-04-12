<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  createColumnHelper,
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { h } from 'vue'
import { format } from 'date-fns'
import {
  Clock,
  Download,
  Edit,
  Eye,
  Search,
  Users,
  AlertTriangle,
  LogOut,
  CheckCircle, Trash2
} from 'lucide-vue-next';
import { toast } from 'vue-sonner'

interface User {
  id: number
  name: string
  email: string
}

interface AttendanceRecord {
  id: number
  user: User
  date: string
  check_in_time: string
  check_out_time: string | null
  late_minutes: number
  early_departure_minutes: number
  status: 'present' | 'absent' | 'late' | 'early_departure'
  notes: string | null
}

interface Stats {
  total_employees: number
  present: number
  absent: number
  late: number
  early_departure: number
}

interface Props {
  attendance: {
    data: AttendanceRecord[]
    links: any[]
  }
  filters: {
    search?: string
    date?: string
    status?: string
  }
  stats: Stats
}

const props = defineProps<Props>()

// Filters
const filters = ref({
  search: props.filters.search || '',
  date: props.filters.date ? new Date(props.filters.date) : null,
  status: props.filters.status || null,
})

const statusOptions = [
  { value: null, label: 'All Status' },
  { value: 'present', label: 'Present' },
  { value: 'absent', label: 'Absent' },
  { value: 'late', label: 'Late' },
  { value: 'early_departure', label: 'Early Departure' },
]

// Update filters
const updateFilters = () => {
  router.get(
    route('admin.attendance.index'),
    {
      search: filters.value.search,
      date: filters.value.date ? format(filters.value.date, 'yyyy-MM-dd') : null,
      status: filters.value.status,
    },
    {
      preserveState: true,
      preserveScroll: true,
    }
  )
}

// Reset filters
const resetFilters = () => {
  filters.value = {
    search: '',
    date: null,
    status: null,
  }
  updateFilters()
}

// Export attendance
const exportAttendance = () => {
  const params = new URLSearchParams({
    search: filters.value.search || '',
    date: filters.value.date ? format(filters.value.date, 'yyyy-MM-dd') : '',
    status: filters.value.status || '',
  })

  window.location.href = `${route('admin.reports.export', 'attendance')}?${params.toString()}`
}

// Delete attendance record
const deleteAttendance = (id: number) => {
  if (confirm('Are you sure you want to delete this record?')) {
    router.delete(route('admin.attendance.destroy', id), {
      onSuccess: () => {
        toast({
          title: 'Success',
          description: 'Attendance record deleted successfully.',
        })
      },
    })
  }
}

// Status badge styling
const getStatusBadgeClasses = (status: string) => {
  const classes = {
    present: 'bg-green-100 text-green-800',
    absent: 'bg-red-100 text-red-800',
    late: 'bg-yellow-100 text-yellow-800',
    early_departure: 'bg-orange-100 text-orange-800',
  }
  return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${classes[status] || ''}`
}

// Table columns
const columnHelper = createColumnHelper<AttendanceRecord>()

const columns = [
  columnHelper.accessor('user.name', {
    header: 'Employee',
    cell: ({ row }) => {
      return h('div', [
        h('div', { class: 'font-medium' }, row.original.user.name),
        h('div', { class: 'text-sm text-muted-foreground' }, row.original.user.email)
      ])
    },
  }),
  columnHelper.accessor('date', {
    header: 'Date'
  }),
  columnHelper.accessor('check_in_time', {
    header: 'Check In'
  }),
  columnHelper.accessor('check_out_time', {
    header: 'Check Out',
    cell: ({ row }) => h('div', {}, row.original.check_out_time || '-')
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: ({ row }) => {
      const status = row.original.status
      return h('span', {
        class: getStatusBadgeClasses(status)
      }, status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1))
    }
  }),
  columnHelper.display({
    id: 'timeIssues',
    header: 'Time Issues',
    cell: ({ row }) => {
      const record = row.original
      if (!record.late_minutes && !record.early_departure_minutes)
        return h('div', {}, '-')

      return h('div', { class: 'text-sm' }, [
        record.late_minutes > 0 && h('div', { class: 'text-yellow-600' }, `Late: ${record.late_minutes} mins`),
        record.early_departure_minutes > 0 && h('div', { class: 'text-orange-600' }, `Early: ${record.early_departure_minutes} mins`)
      ].filter(Boolean))
    }
  }),
  columnHelper.display({
    id: 'actions',
    cell: ({ row }) => {
      return h('div', { class: 'flex items-center gap-2' }, [
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          as: 'a',
          href: route('admin.attendance.show', row.original.id)
        }, () => h(Eye, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          as: 'a',
          href: route('admin.attendance.edit', row.original.id)
        }, () => h(Edit, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          onClick: () => deleteAttendance(row.original.id)
        }, () => h(Trash2, { class: 'h-4 w-4' }))
      ])
    }
  })
]

const table = useVueTable({
  data: props.attendance.data,
  columns,
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
})
</script>

<template>
  <AppLayout>
    <Head title="Attendance Management" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">
          Attendance Management
        </h2>

        <p class="text-muted-foreground">
          View and manage employee attendance records
        </p>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Employees</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>

          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_employees }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present Today</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-500" />
          </CardHeader>

          <CardContent>
            <div class="text-2xl font-bold">{{ stats.present }}</div>
            <p class="text-xs text-muted-foreground">
              {{ Math.round((stats.present / stats.total_employees) * 100) }}% attendance rate
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Late Arrivals</CardTitle>
            <AlertTriangle class="h-4 w-4 text-yellow-500" />
          </CardHeader>

          <CardContent>
            <div class="text-2xl font-bold">{{ stats.late }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Early Departures</CardTitle>
            <LogOut class="h-4 w-4 text-orange-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.early_departure }}</div>
          </CardContent>
        </Card>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
              <div class="relative">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="filters.search"
                  placeholder="Search employees..."
                  class="pl-8"
                  @keyup.enter="updateFilters"
                />
              </div>
            </div>

            <!-- Date Filter -->
            <div class="w-[250px]">
              <VDatePicker
                v-model="filters.date"
                @update:model-value="updateFilters"
                :model-config="{
                  type: 'string',
                  format: 'yyyy-MM-dd'
                }">
                <template #default="{ inputValue, inputEvents }">
                  <Input
                    placeholder="Pick a date"
                    :value="inputValue"
                    v-on="inputEvents"
                    readonly
                  />
                </template>
              </VDatePicker>
            </div>

            <!-- Status Filter -->
            <div class="w-[200px]">
              <Select v-model="filters.status" @update:model-value="updateFilters">
                <SelectTrigger>
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value">
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Reset Filters -->
            <Button variant="outline" @click="resetFilters">
              Reset Filters
            </Button>

            <!-- Export -->
            <Button variant="outline" @click="exportAttendance">
              <Download class="mr-2 h-4 w-4" />
              Export
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Table -->
      <!-- Attendance Table -->
      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
              <TableHead v-for="header in headerGroup.headers" :key="header.id">
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="table.getRowModel().rows?.length">
              <TableRow
                v-for="row in table.getRowModel().rows"
                :key="row.id">
                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                  />
                </TableCell>
              </TableRow>
            </template>
            <TableRow v-else>
              <TableCell :colspan="columns.length" class="h-24 text-center">
                No results.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
