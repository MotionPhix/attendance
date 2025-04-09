<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { ArrowLeft, Clock, Calendar, UserRound } from 'lucide-vue-next'
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
  notes: string | null
}

interface Props {
  attendance: AttendanceRecord
}

const props = defineProps<Props>()

// Form state
const form = ref({
  check_in_time: props.attendance.check_in_time,
  check_out_time: props.attendance.check_out_time || '',
  notes: props.attendance.notes || '',
})

const isSubmitting = ref(false)

const handleSubmit = () => {
  isSubmitting.value = true

  router.put(route('admin.attendance.update', props.attendance.id), form.value, {
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Attendance record updated successfully.',
      })
    },
    onError: () => {
      toast({
        title: 'Error',
        description: 'Failed to update attendance record.',
        variant: 'destructive',
      })
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Edit Attendance Record" />

    <div class="container max-w-4xl py-6">
      <!-- Header -->
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
        <h2 class="text-2xl font-semibold tracking-tight">
          Edit Attendance Record
        </h2>
        <p class="text-muted-foreground">
          Update attendance record for {{ attendance.user.name }}
        </p>
      </div>

      <form @submit.prevent="handleSubmit">
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
                <Label>Name</Label>
                <p class="text-lg">{{ attendance.user.name }}</p>
              </div>
              <div>
                <Label>Email</Label>
                <p class="text-lg">{{ attendance.user.email }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Date -->
        <Card class="mb-6">
          <CardHeader>
            <CardTitle class="flex items-center">
              <Calendar class="mr-2 h-5 w-5" />
              Date Information
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div>
              <Label>Date</Label>
              <p class="text-lg">{{ attendance.date }}</p>
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
          <CardContent class="space-y-6">
            <!-- Check In Time -->
            <div class="grid gap-2">
              <Label for="check_in_time">Check In Time</Label>
              <Input
                id="check_in_time"
                v-model="form.check_in_time"
                type="time"
                step="1"
              />
            </div>

            <!-- Check Out Time -->
            <div class="grid gap-2">
              <Label for="check_out_time">Check Out Time</Label>
              <Input
                id="check_out_time"
                v-model="form.check_out_time"
                type="time"
                step="1"
              />
            </div>

            <!-- Notes -->
            <div class="grid gap-2">
              <Label for="notes">Notes</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                placeholder="Add any notes about this attendance record..."
                rows="4"
              />
            </div>
          </CardContent>
        </Card>

        <!-- Form Actions -->
        <div class="mt-6 flex items-center justify-end gap-4">
          <Button
            type="button"
            variant="outline"
            :href="route('admin.attendance.index')"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="isSubmitting"
          >
            Update Record
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
