<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Building2, Clock, DollarSign, UserRound } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

interface Settings {
  company_name: string
  company_email: string
  company_phone: string
  company_address: string
  company_logo: string
  timezone: string
  date_format: string
  time_format: string
}

interface Props {
  settings: Settings
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { label: 'Dashboard', href: route('admin.dashboard') },
  { label: 'Settings' }
]

// Form state
const form = ref({
  ...props.settings,
  company_logo: null as File | null
})

const processing = ref(false)

// Available formats and timezones
const dateFormats = {
  'Y-m-d': '2025-04-08',
  'm/d/Y': '04/08/2025',
  'd/m/Y': '08/04/2025',
  'M j, Y': 'Apr 8, 2025'
}

const timeFormats = {
  'H:i': '14:30',
  'h:i A': '02:30 PM'
}

// Get all timezones
const timezones = Object.fromEntries(
  Intl.supportedValuesOf('timeZone').map(tz => [tz, tz])
)

// Handle logo preview
const logoPreview = ref<string>(props.settings.company_logo)
const logoInput = ref<HTMLInputElement | null>(null)

const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files?.length) {
    const file = target.files[0]
    form.value.company_logo = file
    logoPreview.value = URL.createObjectURL(file)
  }
}

// Form submission
const handleSubmit = () => {
  processing.value = true

  const formData = new FormData()
  Object.entries(form.value).forEach(([key, value]) => {
    if (value !== null) {
      formData.append(key, value)
    }
  })

  router.post(route('admin.settings.update'), formData, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Settings have been updated successfully.'
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
    <Head title="Settings" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">Settings</h2>
        <p class="text-muted-foreground">
          Configure your application settings and preferences.
        </p>
      </div>

      <!-- Settings Navigation -->
      <div class="mb-6 flex flex-wrap gap-4">
        <Button
          variant="outline"
          :class="{ 'bg-muted': $page.url.endsWith('settings') }"
          :href="route('admin.settings.index')">
          <Building2 class="mr-2 h-4 w-4" />
          General
        </Button>

        <Button
          variant="outline"
          :href="route('admin.settings.attendance')">
          <Clock class="mr-2 h-4 w-4" />
          Attendance
        </Button>

        <Button
          variant="outline"
          :href="route('admin.settings.leave')">
          <UserRound class="mr-2 h-4 w-4" />
          Leave
        </Button>

        <Button
          variant="outline"
          :href="route('admin.settings.salary')">
          <DollarSign class="mr-2 h-4 w-4" />
          Salary
        </Button>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="handleSubmit">
        <div class="grid gap-6">
          <!-- Company Information -->
          <Card>
            <CardHeader>
              <CardTitle>Company Information</CardTitle>
              <CardDescription>
                Configure your company details and branding.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Company Logo -->
              <div class="grid gap-2">
                <Label>Company Logo</Label>
                <div class="flex items-center gap-4">
                  <img
                    v-if="logoPreview"
                    :src="logoPreview"
                    alt="Company Logo"
                    class="h-16 w-16 rounded-lg object-contain border bg-muted"
                  >
                  <Button
                    type="button"
                    variant="outline"
                    @click="logoInput?.click()"
                  >
                    Change Logo
                  </Button>
                  <input
                    ref="logoInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleLogoChange"
                  >
                </div>
              </div>

              <!-- Company Name -->
              <div class="grid gap-2">
                <Label for="company_name">Company Name</Label>
                <Input
                  id="company_name"
                  v-model="form.company_name"
                  type="text"
                />
              </div>

              <!-- Company Email -->
              <div class="grid gap-2">
                <Label for="company_email">Company Email</Label>
                <Input
                  id="company_email"
                  v-model="form.company_email"
                  type="email"
                />
              </div>

              <!-- Company Phone -->
              <div class="grid gap-2">
                <Label for="company_phone">Company Phone</Label>
                <Input
                  id="company_phone"
                  v-model="form.company_phone"
                  type="tel"
                />
              </div>

              <!-- Company Address -->
              <div class="grid gap-2">
                <Label for="company_address">Company Address</Label>
                <Input
                  id="company_address"
                  v-model="form.company_address"
                  type="text"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Regional Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Regional Settings</CardTitle>
              <CardDescription>
                Configure timezone and date/time formats.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Timezone -->
              <div class="grid gap-2">
                <Label for="timezone">Timezone</Label>
                <Select v-model="form.timezone">
                  <SelectTrigger>
                    <SelectValue placeholder="Select timezone" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(label, value) in timezones"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Date Format -->
              <div class="grid gap-2">
                <Label for="date_format">Date Format</Label>
                <Select v-model="form.date_format">
                  <SelectTrigger>
                    <SelectValue placeholder="Select date format" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(example, format) in dateFormats"
                      :key="format"
                      :value="format"
                    >
                      {{ example }} ({{ format }})
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Time Format -->
              <div class="grid gap-2">
                <Label for="time_format">Time Format</Label>
                <Select v-model="form.time_format">
                  <SelectTrigger>
                    <SelectValue placeholder="Select time format" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(example, format) in timeFormats"
                      :key="format"
                      :value="format"
                    >
                      {{ example }} ({{ format }})
                    </SelectItem>
                  </SelectContent>
                </Select>
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
