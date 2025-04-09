<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ArrowLeft,
  Clock,
  Calendar,
  UserRound,
  AlertTriangle,
  LogOut,
  FileEdit,
  Trash2
} from 'lucide-vue-next'

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
  notes: string | null
  status: 'present' | 'absent' | 'late' | 'early_departure'
}

interface Props {
  attendance: AttendanceRecord
}

const props = defineProps<Props>()

const getStatusColor = (status: string) => {
  switch (status) {
    case 'present':
      return 'text-green-500'
    case 'absent':
      return 'text-red-500'
    case 'late':
      return 'text-yellow-500'
    case 'early_departure':
      return 'text-orange-500'
    default:
      return 'text-gray-500'
  }
}

const deleteAttendance = () => {
  if (confirm('Are you sure you want to delete this attendance record?')) {
    router.delete(route('admin.attendance.destroy', props.attendance.id))
  }
}
</script>

<template>
  <AppLayout>
    <Head title="View Attendance Record" />

    <div class="container max-w-4xl py-6">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <Button
              variant="ghost"
              size="sm"
              :href="route('admin.attendance.index')"
              class="mb-2"
            >
              <ArrowLeft class="mr-2 h-4 w-4" />
              Back to List
            </Button>
            <h2 class="text-2xl font-semibold tracking-tight">
              Attendance Record Details
            </h2>
            <p class="text-muted-foreground">
              View detailed attendance information for {{ attendance.user.name }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              :href="route('admin.attendance.edit', attendance.id)"
            >
              <FileEdit class="mr-2 h-4 w-4" />
              Edit Record
            </Button>
            <Button
              variant="destructive"
              @click="deleteAttendance"
            >
              <Trash2 class="mr-2 h-4 w-4" />
              Delete Record
            </Button>
          </div>
        </div>
      </div>

      <!-- Employee Details -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="flex items-center">
            <UserRound class="mr-2 h-5 w-5" />
            Employee Information
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Name</div>
              <div class="text-lg">{{ attendance.user.name }}</div>
            </div>
            <div>
              <div class="text-sm font-medium text-muted-foreground">Email</div>
              <div class="text-lg">{{ attendance.user.email }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Details -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="flex items-center">
            <Calendar class="mr-2 h-5 w-5" />
            Attendance Details
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-6 md:grid-cols-2">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Date</div>
              <div class="text-lg">{{ attendance.date }}</div>
            </div>
            <div>
              <div class="text-sm font-medium text-muted-foreground">Status</div>
              <div :class="['text-lg font-medium', getStatusColor(attendance.status)]">
                {{ attendance.status.replace('_', ' ').charAt(0).toUpperCase() + attendance.status.replace('_', ' ').slice(1) }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Time Details -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="flex items-center">
            <Clock class="mr-2 h-5 w-5" />
            Time Details
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-6 md:grid-cols-2">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Check In Time</div>
              <div class="text-lg">{{ attendance.check_in_time || 'Not checked in' }}</div>
            </div>
            <div>
              <div class="text-sm font-medium text-muted-foreground">Check Out Time</div>
              <div class="text-lg">{{ attendance.check_out_time || 'Not checked out' }}</div>
            </div>

            <!-- Time Issues -->
            <template v-if="attendance.late_minutes > 0 || attendance.early_departure_minutes > 0">
              <div v-if="attendance.late_minutes > 0">
                <div class="flex items-center text-sm font-medium text-yellow-600">
                  <AlertTriangle class="mr-2 h-4 w-4" />
                  Late Arrival
                </div>
                <div class="text-lg">{{ attendance.late_minutes }} minutes</div>
              </div>
              <div v-if="attendance.early_departure_minutes > 0">
                <div class="flex items-center text-sm font-medium text-orange-600">
                  <LogOut class="mr-2 h-4 w-4" />
                  Early Departure
                </div>
                <div class="text-lg">{{ attendance.early_departure_minutes }} minutes</div>
              </div>
            </template>
          </div>
        </CardContent>
      </Card>

      <!-- Notes -->
      <Card v-if="attendance.notes">
        <CardHeader>
          <CardTitle>Notes</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="whitespace-pre-wrap">{{ attendance.notes }}</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
