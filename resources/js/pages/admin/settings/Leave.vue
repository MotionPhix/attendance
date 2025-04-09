<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Building2, Clock, DollarSign, UserRound } from 'lucide-vue-next'
import { toast } from 'vue-sonner';
import SettingsNav from '@/pages/admin/settings/partials/SettingsNav.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

interface Settings {
  annual_leave_days: number
  sick_leave_days: number
  personal_leave_days: number
  require_approval_for_leave: boolean
  min_days_before_leave_request: number
  allow_half_day_leave: boolean
  leave_accrual_method: 'annual' | 'monthly' | 'bi-monthly'
  leave_carryover_limit: number
  leave_carryover_expiry_months: number
}

interface Props {
  settings: Settings
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { label: 'Dashboard', href: route('admin.dashboard') },
  { label: 'Settings', href: route('admin.settings.index') },
  { label: 'Leave' }
]

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs(breadcrumbs);

// Form state
const form = ref({
  ...props.settings
})

const processing = ref(false)

const accrualMethods = {
  annual: 'Annual (All days credited at start of year)',
  monthly: 'Monthly (Days credited each month)',
  'bi-monthly': 'Bi-Monthly (Days credited every two months)'
}

// Form submission
const handleSubmit = () => {
  processing.value = true

  router.post(route('admin.settings.leave.update'), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Leave settings have been updated successfully.'
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
    <Head title="Leave Settings" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">Leave Settings</h2>
        <p class="text-muted-foreground">
          Configure leave policies and allowances.
        </p>
      </div>

      <!-- Settings Navigation -->
      <SettingsNav current-path="admin.settings.leave" />

      <!-- Settings Form -->
      <form @submit.prevent="handleSubmit">
        <div class="grid gap-6 grid-cols-3">
          <!-- Leave Allowances -->
          <Card class="col-span-2">
            <CardHeader>
              <CardTitle>Leave Allowances</CardTitle>
              <CardDescription>
                Configure the number of days allowed for different types of leave.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Annual Leave -->
              <div class="grid gap-2">
                <Label for="annual_leave_days">Annual Leave Days</Label>
                <Input
                  id="annual_leave_days"
                  v-model="form.annual_leave_days"
                  type="number"
                  min="0"
                  max="100"
                />
                <p class="text-sm text-muted-foreground">
                  Number of paid vacation days per year.
                </p>
              </div>

              <!-- Sick Leave -->
              <div class="grid gap-2">
                <Label for="sick_leave_days">Sick Leave Days</Label>
                <Input
                  id="sick_leave_days"
                  v-model="form.sick_leave_days"
                  type="number"
                  min="0"
                  max="100"
                />
                <p class="text-sm text-muted-foreground">
                  Number of paid sick days per year.
                </p>
              </div>

              <!-- Personal Leave -->
              <div class="grid gap-2">
                <Label for="personal_leave_days">Personal Leave Days</Label>
                <Input
                  id="personal_leave_days"
                  v-model="form.personal_leave_days"
                  type="number"
                  min="0"
                  max="100"
                />
                <p class="text-sm text-muted-foreground">
                  Number of personal days per year.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Leave Policies -->
          <Card class="col-span-2">
            <CardHeader>
              <CardTitle>Leave Policies</CardTitle>
              <CardDescription>
                Configure leave request and approval policies.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Approval Required -->
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.require_approval_for_leave" />
                  Require Approval for Leave Requests
                </Label>
                <p class="text-sm text-muted-foreground">
                  If enabled, leave requests must be approved by a supervisor.
                </p>
              </div>

              <!-- Minimum Notice -->
              <div class="grid gap-2">
                <Label for="min_days_before_leave_request">Minimum Notice Days</Label>
                <Input
                  id="min_days_before_leave_request"
                  v-model="form.min_days_before_leave_request"
                  type="number"
                  min="0"
                  max="30"
                />
                <p class="text-sm text-muted-foreground">
                  Minimum number of days before the leave date that a request must be submitted.
                </p>
              </div>

              <!-- Half Day Leave -->
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.allow_half_day_leave" />
                  Allow Half-Day Leave
                </Label>
                <p class="text-sm text-muted-foreground">
                  If enabled, employees can request leave for half a day.
                </p>
              </div>

              <!-- Accrual Method -->
              <div class="grid gap-2">
                <Label for="leave_accrual_method">Leave Accrual Method</Label>
                <Select v-model="form.leave_accrual_method">
                  <SelectTrigger>
                    <SelectValue placeholder="Select accrual method" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(label, value) in accrualMethods"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-sm text-muted-foreground">
                  How leave days are credited to employees.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Leave Carryover -->
          <Card class="col-span-2">
            <CardHeader>
              <CardTitle>Leave Carryover</CardTitle>
              <CardDescription>
                Configure leave carryover policies.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Carryover Limit -->
              <div class="grid gap-2">
                <Label for="leave_carryover_limit">Carryover Limit</Label>
                <Input
                  id="leave_carryover_limit"
                  v-model="form.leave_carryover_limit"
                  type="number"
                  min="0"
                  max="100"
                />
                <p class="text-sm text-muted-foreground">
                  Maximum number of leave days that can be carried over to the next year.
                </p>
              </div>

              <!-- Carryover Expiry -->
              <div class="grid gap-2">
                <Label for="leave_carryover_expiry_months">Carryover Expiry (Months)</Label>
                <Input
                  id="leave_carryover_expiry_months"
                  v-model="form.leave_carryover_expiry_months"
                  type="number"
                  min="0"
                  max="12"
                />
                <p class="text-sm text-muted-foreground">
                  Number of months into the new year before carried over leave expires.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 grid gap-6 grid-cols-3">
          <div class="col-span-2 justify-end flex">
            <Button
              type="submit"
              :disabled="processing">
              Save Changes
            </Button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
