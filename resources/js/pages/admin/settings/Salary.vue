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
import { Building2, Clock, DollarSign, UserRound, Plus, Trash2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

interface TaxBracket {
  from: number
  to: number | null
  rate: number
}

interface Settings {
  salary_payment_date: number
  overtime_rate: number
  weekend_overtime_rate: number
  holiday_overtime_rate: number
  late_deduction_method: 'per_minute' | 'fixed' | 'hourly'
  late_deduction_amount: number
  early_departure_deduction_method: 'per_minute' | 'fixed' | 'hourly'
  early_departure_deduction_amount: number
  tax_calculation_method: 'progressive' | 'flat'
  tax_brackets: TaxBracket[]
  flat_tax_rate: number
  enable_bonuses: boolean
  enable_deductions: boolean
}

interface Props {
  settings: Settings
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { label: 'Dashboard', href: route('admin.dashboard') },
  { label: 'Settings', href: route('admin.settings.index') },
  { label: 'Salary' }
]

// Form state
const form = ref({
  ...props.settings,
  tax_brackets: [...props.settings.tax_brackets] // Create a new array to avoid mutation
})

const processing = ref(false)

const deductionMethods = {
  per_minute: 'Per Minute',
  fixed: 'Fixed Amount',
  hourly: 'Per Hour'
}

// Add new tax bracket
const addTaxBracket = () => {
  const lastBracket = form.value.tax_brackets[form.value.tax_brackets.length - 1]
  const newFrom = lastBracket ? (lastBracket.to || 0) + 1 : 0

  form.value.tax_brackets.push({
    from: newFrom,
    to: null,
    rate: 0
  })
}

// Remove tax bracket
const removeTaxBracket = (index: number) => {
  form.value.tax_brackets.splice(index, 1)
}

// Validate tax brackets
const validateTaxBrackets = (): boolean => {
  if (form.value.tax_calculation_method !== 'progressive') return true

  for (let i = 0; i < form.value.tax_brackets.length; i++) {
    const current = form.value.tax_brackets[i]
    const next = form.value.tax_brackets[i + 1]

    // Check for valid rate
    if (current.rate < 0 || current.rate > 100) {
      toast({
        title: 'Invalid Tax Rate',
        description: 'Tax rates must be between 0% and 100%.',
        variant: 'destructive'
      })
      return false
    }

    // Check for valid range
    if (current.to !== null && current.from >= current.to) {
      toast({
        title: 'Invalid Tax Bracket',
        description: 'The "from" amount must be less than the "to" amount.',
        variant: 'destructive'
      })
      return false
    }

    // Check for gaps between brackets
    if (next && current.to !== next.from - 1) {
      toast({
        title: 'Invalid Tax Brackets',
        description: 'Tax brackets must be continuous without gaps.',
        variant: 'destructive'
      })
      return false
    }
  }

  return true
}

// Form submission
const handleSubmit = () => {
  if (!validateTaxBrackets()) return

  processing.value = true

  router.post(route('admin.settings.salary.update'), form.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Salary settings have been updated successfully.'
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
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Salary Settings" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">Salary Settings</h2>
        <p class="text-muted-foreground">
          Configure salary calculation and payment policies.
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
          :class="{ 'bg-muted': true }"
          :href="route('admin.settings.salary')"
        >
          <DollarSign class="mr-2 h-4 w-4" />
          Salary
        </Button>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="handleSubmit">
        <div class="grid gap-6">
          <!-- Basic Salary Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Basic Settings</CardTitle>
              <CardDescription>
                Configure basic salary payment settings.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Payment Date -->
              <div class="grid gap-2">
                <Label for="salary_payment_date">Salary Payment Date</Label>
                <Input
                  id="salary_payment_date"
                  v-model="form.salary_payment_date"
                  type="number"
                  min="1"
                  max="31"
                />
                <p class="text-sm text-muted-foreground">
                  Day of the month when salaries are paid.
                </p>
              </div>

              <!-- Additional Components -->
              <div class="grid gap-2">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.enable_bonuses" />
                  Enable Bonus Payments
                </Label>
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.enable_deductions" />
                  Enable Salary Deductions
                </Label>
              </div>
            </CardContent>
          </Card>

          <!-- Overtime Rates -->
          <Card>
            <CardHeader>
              <CardTitle>Overtime Rates</CardTitle>
              <CardDescription>
                Configure overtime payment multipliers.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Regular Overtime -->
              <div class="grid gap-2">
                <Label for="overtime_rate">Regular Overtime Rate</Label>
                <Input
                  id="overtime_rate"
                  v-model="form.overtime_rate"
                  type="number"
                  min="1"
                  max="5"
                  step="0.1"
                />
                <p class="text-sm text-muted-foreground">
                  Multiplier for regular overtime hours (e.g., 1.5 = 150% of regular rate).
                </p>
              </div>

              <!-- Weekend Overtime -->
              <div class="grid gap-2">
                <Label for="weekend_overtime_rate">Weekend Overtime Rate</Label>
                <Input
                  id="weekend_overtime_rate"
                  v-model="form.weekend_overtime_rate"
                  type="number"
                  min="1"
                  max="5"
                  step="0.1"
                />
                <p class="text-sm text-muted-foreground">
                  Multiplier for weekend overtime hours.
                </p>
              </div>

              <!-- Holiday Overtime -->
              <div class="grid gap-2">
                <Label for="holiday_overtime_rate">Holiday Overtime Rate</Label>
                <Input
                  id="holiday_overtime_rate"
                  v-model="form.holiday_overtime_rate"
                  type="number"
                  min="1"
                  max="5"
                  step="0.1"
                />
                <p class="text-sm text-muted-foreground">
                  Multiplier for holiday overtime hours.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Deduction Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Deduction Settings</CardTitle>
              <CardDescription>
                Configure automatic deduction rules.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Late Deductions -->
              <div class="space-y-4">
                <Label>Late Arrival Deductions</Label>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="grid gap-2">
                    <Label for="late_deduction_method">Calculation Method</Label>
                    <Select v-model="form.late_deduction_method">
                      <SelectTrigger>
                        <SelectValue placeholder="Select method" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="(label, value) in deductionMethods"
                          :key="value"
                          :value="value"
                        >
                          {{ label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="grid gap-2">
                    <Label for="late_deduction_amount">Amount</Label>
                    <Input
                      id="late_deduction_amount"
                      v-model="form.late_deduction_amount"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </div>
                </div>
              </div>

              <!-- Early Departure Deductions -->
              <div class="space-y-4">
                <Label>Early Departure Deductions</Label>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="grid gap-2">
                    <Label for="early_departure_deduction_method">Calculation Method</Label>
                    <Select v-model="form.early_departure_deduction_method">
                      <SelectTrigger>
                        <SelectValue placeholder="Select method" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="(label, value) in deductionMethods"
                          :key="value"
                          :value="value"
                        >
                          {{ label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="grid gap-2">
                    <Label for="early_departure_deduction_amount">Amount</Label>
                    <Input
                      id="early_departure_deduction_amount"
                      v-model="form.early_departure_deduction_amount"
                      type="number"
                      min="0"
                      step="0.01"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Tax Settings -->
          <Card>
            <CardHeader>
              <CardTitle>Tax Settings</CardTitle>
              <CardDescription>
                Configure tax calculation methods and brackets.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Tax Calculation Method -->
              <div class="grid gap-2">
                <Label for="tax_calculation_method">Tax Calculation Method</Label>
                <Select v-model="form.tax_calculation_method">
                  <SelectTrigger>
                    <SelectValue placeholder="Select method" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="progressive">Progressive Tax</SelectItem>
                    <SelectItem value="flat">Flat Rate Tax</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Flat Tax Rate -->
              <div
                v-if="form.tax_calculation_method === 'flat'"
                class="grid gap-2"
              >
                <Label for="flat_tax_rate">Flat Tax Rate (%)</Label>
                <Input
                  id="flat_tax_rate"
                  v-model="form.flat_tax_rate"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                />
              </div>

              <!-- Progressive Tax Brackets -->
              <div v-else>
                <div class="mb-4 flex items-center justify-between">
                  <Label>Tax Brackets</Label>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addTaxBracket"
                  >
                    <Plus class="mr-2 h-4 w-4" />
                    Add Bracket
                  </Button>
                </div>

                <div class="space-y-4">
                  <div
                    v-for="(bracket, index) in form.tax_brackets"
                    :key="index"
                    class="flex items-start gap-4"
                  >
                    <div class="grid flex-1 gap-4 sm:grid-cols-3">
                      <div class="grid gap-2">
                        <Label :for="`bracket-${index}-from`">From</Label>
                        <Input
                          :id="`bracket-${index}-from`"
                          v-model="bracket.from"
                          type="number"
                          min="0"
                          step="0.01"
                          :disabled="index > 0"
                        />
                      </div>
                      <div class="grid gap-2">
                        <Label :for="`bracket-${index}-to`">To</Label>
                        <Input
                          :id="`bracket-${index}-to`"
                          v-model="bracket.to"
                          type="number"
                          min="0"
                          step="0.01"
                          :disabled="index === form.tax_brackets.length - 1"
                          :placeholder="index === form.tax_brackets.length - 1 ? 'No limit' : ''"
                        />
                      </div>
                      <div class="grid gap-2">
                        <Label :for="`bracket-${index}-rate`">Rate (%)</Label>
                        <Input
                          :id="`bracket-${index}-rate`"
                          v-model="bracket.rate"
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                        />
                      </div>
                    </div>
                    <Button
                      v-if="form.tax_brackets.length > 1"
                      type="button"
                      variant="outline"
                      size="icon"
                      @click="removeTaxBracket(index)"
                    >
                      <span class="sr-only">Remove bracket</span>
                      <Trash2 class="h-4 w-4" />
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
