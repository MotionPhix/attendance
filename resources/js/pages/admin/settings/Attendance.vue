<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Building2, Clock, DollarSign, UserRound } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

interface Settings {
  check_in_tolerance_minutes: number
  auto_checkout_enabled: boolean
  auto_checkout_time: string
  weekend_days: number[]
  allow_ip_restriction: boolean
  allowed_ip_addresses: string[]
  allow_location_restriction: boolean
  office_latitude: string
  office_longitude: string
  location_radius_meters: number
}

interface Props {
  settings: Settings
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { label: 'Dashboard', href: route('admin.dashboard') },
  { label: 'Settings', href: route('admin.settings.index') },
  { label: 'Attendance' }
]

// Form state
const form = ref({
  ...props.settings
})

const processing = ref(false)

// Weekend days options
const weekendOptions = [
  { value: 0, label: 'Sunday' },
  { value: 1, label: 'Monday' },
  { value: 2, label: 'Tuesday' },
  { value: 3, label: 'Wednesday' },
  { value: 4, label: 'Thursday' },
  { value: 5, label: 'Friday' },
  { value: 6, label: 'Saturday' }
]

// Form submission
const handleSubmit = () => {
  processing.value = true

  router.post(route('admin.settings.attendance.update'), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Attendance settings have been updated successfully.'
      })
    },
    onError: (errors) => {
      toast({
        title: 'Error',
        description: 'There was an error updating settings. Please check the form and try again.',
        variant: 'destructive'
      })
    },
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Attendance Settings" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">Attendance Settings</h2>
        <p class="text-muted-foreground">
          Configure attendance rules and restrictions.
        </p>
      </div>

      <!-- Settings Navigation -->
      <div class="mb-6 flex flex-wrap gap-4">
        <Button
          variant="outline"
          :href="route('admin.settings.index')"
        >
          <Building2 class="mr-2 h-4 w-4" />
          General
        </Button>

        <Button
          variant="outline"
          :class="{ 'bg-muted': true }"
          :href="route('admin.settings.attendance')"
        >
          <Clock class="mr-2 h-4 w-4" />
          Attendance
        </Button>

        <Button
          variant="outline"
          :href="route('admin.settings.leave')"
        >
          <UserRound class="mr-2 h-4 w-4" />
          Leave
        </Button>

        <Button
          variant="outline"
          :href="route('admin.settings.salary')"
        >
          <DollarSign class="mr-2 h-4 w-4" />
          Salary
        </Button>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="handleSubmit">
        <div class="grid gap-6">
          <!-- Basic Attendance Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Basic Settings</CardTitle>
              <CardDescription>
                Configure basic attendance rules and timing.
              </CardDescription>
            </CardHeader>

            <CardContent class="space-y-6">
              <!-- Check-in Tolerance -->
              <div class="grid gap-2">
                <Label for="check_in_tolerance_minutes">Check-in Tolerance (minutes)</Label>
                <Input
                  id="check_in_tolerance_minutes"
                  v-model="form.check_in_tolerance_minutes"
                  type="number"
                  min="0"
                  max="60"
                />
                <p class="text-sm text-muted-foreground">
                  Number of minutes after the scheduled start time before marking attendance as late.
                </p>
              </div>

              <!-- Auto Checkout -->
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.auto_checkout_enabled" />
                  Enable Automatic Checkout
                </Label>
                <div class="mt-2">
                  <Input
                    v-model="form.auto_checkout_time"
                    type="time"
                    :disabled="!form.auto_checkout_enabled"
                  />
                </div>
                <p class="text-sm text-muted-foreground">
                  Automatically check out employees who haven't checked out by this time.
                </p>
              </div>

              <!-- Weekend Days -->
              <div class="grid gap-2">
                <Label>Weekend Days</Label>
<!--                <MultiSelect-->
<!--                  v-model="form.weekend_days"-->
<!--                  :options="weekendOptions"-->
<!--                />-->
                <p class="text-sm text-muted-foreground">
                  Select which days are considered weekends.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Location Restrictions -->
          <Card>
            <CardHeader>
              <CardTitle>Location Restrictions</CardTitle>
              <CardDescription>
                Configure location-based attendance restrictions.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.allow_location_restriction" />
                  Enable Location Restrictions
                </Label>

                <div class="mt-4 space-y-4">
                  <div class="grid gap-2">
                    <Label>Office Location</Label>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <Input
                          v-model="form.office_latitude"
                          type="text"
                          placeholder="Latitude"
                          :disabled="!form.allow_location_restriction"
                        />
                      </div>
                      <div>
                        <Input
                          v-model="form.office_longitude"
                          type="text"
                          placeholder="Longitude"
                          :disabled="!form.allow_location_restriction"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="grid gap-2">
                    <Label>Allowed Radius (meters)</Label>
                    <Input
                      v-model="form.location_radius_meters"
                      type="number"
                      min="10"
                      max="1000"
                      :disabled="!form.allow_location_restriction"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- IP Restrictions -->
          <Card>
            <CardHeader>
              <CardTitle>IP Restrictions</CardTitle>
              <CardDescription>
                Configure IP-based attendance restrictions.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.allow_ip_restriction" />
                  Enable IP Restrictions
                </Label>

                <div
                  v-if="form.allow_ip_restriction"
                  class="mt-4"
                >
                  <Label>Allowed IP Addresses</Label>
                  <div class="mt-2 space-y-2">
                    <div
                      v-for="(ip, index) in form.allowed_ip_addresses"
                      :key="index"
                      class="flex items-center gap-2"
                    >
                      <Input
                        v-model="form.allowed_ip_addresses[index]"
                        type="text"
                        placeholder="Enter IP address"
                      />
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        @click="form.allowed_ip_addresses.splice(index, 1)"
                      >
                        <span class="sr-only">Remove IP</span>
                        <XIcon class="h-4 w-4" />
                      </Button>
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      @click="form.allowed_ip_addresses.push('')"
                    >
                      Add IP Address
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 flex justify-end">
          <Button
            type="submit"
            :disabled="processing"
          >
            Save Changes
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
