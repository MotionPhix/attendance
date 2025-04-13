<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink } from '@/components/ui/breadcrumb'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import AvatarUpload from '@/components/AvatarUpload.vue'
import {
  ClipboardList,
  Clock,
  Calendar,
  UserRound,
  AlertTriangle,
  LogOut,
  FileEdit,
  Trash2
} from 'lucide-vue-next'
import { ref } from 'vue';
import { toast } from 'vue-sonner';

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
const deleteDialog = ref(false)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (time: string | null) => {
  if (!time) return '—'
  const [hours, minutes] = time.split(':')
  return new Date(2025, 0, 1, parseInt(hours), parseInt(minutes))
    .toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
}

const getStatusDetails = (status: string) => {
  const details = {
    present: {
      color: 'text-green-500 dark:text-green-400',
      bgColor: 'bg-green-50 dark:bg-green-900/20',
      icon: Clock,
      description: 'Employee arrived and departed on time'
    },
    absent: {
      color: 'text-red-500 dark:text-red-400',
      bgColor: 'bg-red-50 dark:bg-red-900/20',
      icon: AlertTriangle,
      description: 'Employee did not report to work'
    },
    late: {
      color: 'text-yellow-500 dark:text-yellow-400',
      bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
      icon: AlertTriangle,
      description: 'Employee arrived later than scheduled'
    },
    early_departure: {
      color: 'text-orange-500 dark:text-orange-400',
      bgColor: 'bg-orange-50 dark:bg-orange-900/20',
      icon: LogOut,
      description: 'Employee left earlier than scheduled'
    }
  }

  return details[status] || details.present
}

const deleteAttendance = () => {
  router.delete(route('admin.attendance.destroy', props.attendance.id), {
    onSuccess: () => {
      toast.success('Attendance record deleted successfully')
    }
  })

  deleteDialog.value = false
}
</script>

<template>
  <AppLayout>
    <Head :title="`Attendance Record - ${attendance.user.name}`" />

    <ScrollArea class="h-full">
      <div class="container max-w-6xl py-6">
        <!-- Breadcrumbs -->
        <Breadcrumb class="mb-6">
          <BreadcrumbItem>
            <BreadcrumbLink :href="route('admin.dashboard')">Dashboard</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbItem>
            <BreadcrumbLink :href="route('admin.attendance.index')">Attendance</BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbItem>
            {{ attendance.user.name }}
          </BreadcrumbItem>
        </Breadcrumb>

        <!-- Header with Back Button and Actions -->
        <div class="mb-6">
          <Button
            variant="ghost"
            size="sm"
            :href="route('admin.attendance.index')"
            class="mb-2"
          >
            <ArrowLeft class="mr-2 h-4 w-4" />
            Back to List
          </Button>
        </div>

        <!-- Main Content Grid -->
        <div class="grid gap-6 lg:grid-cols-12">
          <!-- Left Column - Avatar and User Info -->
          <div class="lg:col-span-4 space-y-6">
            <!-- User Profile Card -->
            <Card>
              <CardContent class="pt-6">
                <div class="flex flex-col items-center space-y-4">
                  <!-- Avatar Upload Component -->
                  <div class="w-32 h-32">
                    <AvatarUpload
                      :user="attendance.user"
                      size="lg"
                      readonly
                    />
                  </div>

                  <div class="text-center">
                    <h3 class="text-lg font-semibold">
                      {{ attendance.user.name }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                      {{ attendance.user.email }}
                    </p>
                  </div>

                  <!-- Status Badge -->
                  <div :class="[
                    'w-full p-4 rounded-lg border',
                    getStatusDetails(attendance.status).bgColor,
                    getStatusDetails(attendance.status).border
                  ]">
                    <div class="flex items-center space-x-3">
                      <component
                        :is="getStatusDetails(attendance.status).icon"
                        :class="[
                          'h-5 w-5',
                          getStatusDetails(attendance.status).color
                        ]"
                      />
                      <div>
                        <p :class="[
                          'font-medium',
                          getStatusDetails(attendance.status).color
                        ]">
                          {{ attendance.status.replace('_', ' ').charAt(0).toUpperCase() + attendance.status.replace('_', ' ').slice(1) }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                          {{ getStatusDetails(attendance.status).description }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2">
              <Button
                variant="outline"
                class="w-full"
                :href="route('admin.attendance.edit', attendance.id)"
              >
                <FileEdit class="mr-2 h-4 w-4" />
                Edit Record
              </Button>
              <Button
                variant="destructive"
                class="w-full"
                @click="deleteDialog = true"
              >
                <Trash2 class="mr-2 h-4 w-4" />
                Delete Record
              </Button>
            </div>
          </div>

          <!-- Right Column - Attendance Details -->
          <div class="lg:col-span-8 space-y-6">
            <!-- Date and Time Card -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center">
                  <Calendar class="mr-2 h-5 w-5" />
                  Attendance Details
                </CardTitle>
                <CardDescription>
                  {{ formatDate(attendance.date) }}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="grid gap-6 sm:grid-cols-2">
                  <div class="space-y-2">
                    <div class="text-sm font-medium text-muted-foreground">Check In Time</div>
                    <div class="text-2xl">{{ formatTime(attendance.check_in_time) }}</div>
                  </div>
                  <div class="space-y-2">
                    <div class="text-sm font-medium text-muted-foreground">Check Out Time</div>
                    <div class="text-2xl">{{ formatTime(attendance.check_out_time) }}</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Time Issues Card -->
            <Card v-if="attendance.late_minutes > 0 || attendance.early_departure_minutes > 0">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <AlertTriangle class="mr-2 h-5 w-5" />
                  Time Issues
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="space-y-4">
                  <div v-if="attendance.late_minutes > 0" 
                       class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="flex items-center text-sm font-medium text-yellow-600 dark:text-yellow-400">
                      <AlertTriangle class="mr-2 h-4 w-4" />
                      Late Arrival
                    </div>
                    <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                      {{ attendance.late_minutes }} minutes after scheduled time
                    </div>
                  </div>

                  <div v-if="attendance.early_departure_minutes > 0"
                       class="rounded-lg bg-orange-50 dark:bg-orange-900/20 p-4">
                    <div class="flex items-center text-sm font-medium text-orange-600 dark:text-orange-400">
                      <LogOut class="mr-2 h-4 w-4" />
                      Early Departure
                    </div>
                    <div class="mt-1 text-sm text-orange-700 dark:text-orange-300">
                      Left {{ attendance.early_departure_minutes }} minutes before scheduled time
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Notes Card -->
            <Card v-if="attendance.notes">
              <CardHeader>
                <CardTitle class="flex items-center">
                  <ClipboardList class="mr-2 h-5 w-5" />
                  Notes
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p class="whitespace-pre-wrap text-muted-foreground">{{ attendance.notes }}</p>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </ScrollArea>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="deleteDialog" @update:open="deleteDialog = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Attendance Record</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this attendance record? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button
            variant="outline"
            @click="deleteDialog = false"
          >
            Cancel
          </Button>
          <Button
            variant="destructive"
            @click="deleteAttendance"
          >
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
