<script setup lang="ts">
import AppLayout from '@/layouts/MainAppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  Alert,
  AlertDescription,
  AlertTitle
} from '@/components/ui/alert';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import {
  ArrowLeft,
  Calendar,
  Check,
  Clock,
  DollarSign,
  Download,
  FileText,
  Info,
  Loader2,
  Printer,
  Trash2,
  User
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import dayjs from 'dayjs';

// Set breadcrumbs for this page
const { setPageBreadcrumbs } = useBreadcrumbs();
setPageBreadcrumbs([
  { label: 'Home', href: '/' },
  { label: 'Salaries', href: '/admin/salaries' },
  { label: 'Salary Details' }
]);

const props = defineProps({
  salary: Object,
  attendanceSummary: Object,
  leaveSummary: Object,
  period: String,
});

// Format helpers
const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (dateString) => {
  return dateString ? dayjs(dateString).format('MMM D, YYYY') : 'N/A';
};

// Status badge color
const statusColor = computed(() => {
  switch (props.salary.status) {
    case 'pending':
      return 'bg-amber-500/10 text-amber-500';
    case 'processed':
      return 'bg-blue-500/10 text-blue-500';
    case 'paid':
      return 'bg-green-500/10 text-green-500';
    default:
      return 'bg-muted text-muted-foreground';
  }
});

// Update salary status form
const updateForm = useForm({
  status: props.salary.status,
  paid_at: props.salary.paid_at || '',
  payment_reference: props.salary.payment_reference || '',
});

// Delete confirmation
const showDeleteDialog = ref(false);
const deleteForm = useForm({});

// Update salary status
const updateSalaryStatus = () => {
  updateForm.put(route('admin.salaries.update', props.salary.id));
};

// Delete salary record
const deleteSalary = () => {
  deleteForm.delete(route('admin.salaries.destroy', props.salary.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};

// Print salary slip
const printSalarySlip = () => {
  window.print();
};

// Download salary slip
const downloadSalarySlip = () => {
  window.open(route('admin.salaries.download', props.salary.id), '_blank');
};
</script>

<template>
  <AppLayout :title="`Salary Details - ${salary.user.name}`">
    <Head :title="`Salary Details - ${salary.user.name}`" />

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-foreground">Salary Details</h2>
            <p class="mt-1 text-sm text-muted-foreground">
              Detailed salary information for {{ period }}.
            </p>
          </div>
          <div class="flex space-x-2">
            <Link :href="route('admin.salaries.index')" class="btn-secondary">
              <ArrowLeft class="mr-1 h-4 w-4" />
              Back to Salaries
            </Link>
            <Button variant="outline" @click="printSalarySlip">
              <Printer class="mr-1 h-4 w-4" />
              Print
            </Button>
            <Button variant="outline" @click="downloadSalarySlip">
              <Download class="mr-1 h-4 w-4" />
              Download
            </Button>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Left Column: Employee Info and Salary Summary -->
          <div class="space-y-6">
            <!-- Employee Information -->
            <Card>
              <CardHeader>
                <CardTitle>Employee Information</CardTitle>
                <CardDescription>
                  Basic details about the employee.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-4">
                  <div class="flex items-center space-x-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                      <User class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h3 class="font-medium text-card-foreground">{{ salary.user.name }}</h3>
                      <p class="text-sm text-muted-foreground">{{ salary.user.employeeProfile.position }}</p>
                    </div>
                  </div>

                  <div class="space-y-2 pt-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Department:</span>
                      <span class="font-medium">{{ salary.user.employeeProfile.department.name }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Employee ID:</span>
                      <span class="font-medium">{{ salary.user.id }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Email:</span>
                      <span class="font-medium">{{ salary.user.email }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Hire Date:</span>
                      <span class="font-medium">{{ formatDate(salary.user.employeeProfile.hire_date) }}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Salary Summary -->
            <Card>
              <CardHeader>
                <CardTitle>Salary Summary</CardTitle>
                <CardDescription>
                  Overview of salary components.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Status:</span>
                    <span :class="`rounded-full px-2 py-1 text-xs font-medium ${statusColor}`">
                      {{ salary.status.charAt(0).toUpperCase() + salary.status.slice(1) }}
                    </span>
                  </div>

                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Base Salary:</span>
                      <span class="font-medium">{{ formatCurrency(salary.base_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Overtime Pay:</span>
                      <span class="font-medium text-green-600">{{ formatCurrency(salary.overtime_pay) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Bonuses:</span>
                      <span class="font-medium text-green-600">{{ formatCurrency(salary.bonuses) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Deductions:</span>
                      <span class="font-medium text-destructive">{{ formatCurrency(salary.deductions) }}</span>
                    </div>
                    <div class="border-t border-border pt-2">
                      <div class="flex justify-between font-medium">
                        <span>Net Salary:</span>
                        <span>{{ formatCurrency(salary.net_amount) }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-2 pt-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Processed Date:</span>
                      <span class="font-medium">{{ formatDate(salary.processed_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Payment Date:</span>
                      <span class="font-medium">{{ formatDate(salary.paid_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Reference:</span>
                      <span class="font-medium">{{ salary.payment_reference || 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Update Status -->
            <Card v-if="salary.status !== 'paid'">
              <CardHeader>
                <CardTitle>Update Status</CardTitle>
                <CardDescription>
                  Change the status of this salary record.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <form @submit.prevent="updateSalaryStatus" class="space-y-4">
                  <div class="space-y-2">
                    <Label for="status">Status</Label>
                    <Select v-model="updateForm.status">
                      <SelectTrigger id="status">
                        <SelectValue placeholder="Select status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="processed">Processed</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div v-if="updateForm.status === 'paid'" class="space-y-2">
                    <Label for="paid_at">Payment Date</Label>
                    <Input
                      id="paid_at"
                      v-model="updateForm.paid_at"
                      type="date"
                      :max="new Date().toISOString().split('T')[0]"
                    />
                  </div>

                  <div v-if="updateForm.status === 'paid'" class="space-y-2">
                    <Label for="payment_reference">Payment Reference</Label>
                    <Input
                      id="payment_reference"
                      v-model="updateForm.payment_reference"
                      placeholder="e.g., Transaction ID, Check Number"
                    />
                  </div>
                </form>
              </CardContent>
              <CardFooter>
                <Button
                  type="submit"
                  @click="updateSalaryStatus"
                  :disabled="updateForm.processing"
                  class="w-full"
                >
                  <Loader2 v-if="updateForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                  <span v-else>Update Status</span>
                </Button>
              </CardFooter>
            </Card>

            <!-- Delete Salary -->
            <Card v-if="salary.status === 'pending'" class="border-destructive/20">
              <CardHeader>
                <CardTitle class="text-destructive">Delete Salary Record</CardTitle>
                <CardDescription>
                  This action cannot be undone.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p class="text-sm text-muted-foreground">
                  Only pending salary records can be deleted. Once a salary is marked as processed or paid,
                  it cannot be deleted.
                </p>
              </CardContent>
              <CardFooter>
                <Dialog v-model:open="showDeleteDialog">
                  <DialogTrigger asChild>
                    <Button variant="destructive" class="w-full">
                      <Trash2 class="mr-2 h-4 w-4" />
                      Delete Salary Record
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Are you sure?</DialogTitle>
                      <DialogDescription>
                        This will permanently delete the salary record for {{ salary.user.name }}
                        for {{ period }}. This action cannot be undone.
                      </DialogDescription>
                    </DialogHeader>
                    <div class="py-4">
                      <p class="text-sm text-muted-foreground">
                        If you need to make changes to this salary, consider updating it instead of deleting it.
                      </p>
                    </div>
                    <DialogFooter>
                      <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                      <Button
                        variant="destructive"
                        @click="deleteSalary"
                        :disabled="deleteForm.processing"
                      >
                        <Loader2 v-if="deleteForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span v-else>Delete</span>
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </CardFooter>
            </Card>
          </div>

          <!-- Right Column: Salary Details -->
          <div class="lg:col-span-2">
            <!-- Salary Breakdown -->
            <Card class="mb-6">
              <CardHeader>
                <CardTitle>Salary Breakdown</CardTitle>
                <CardDescription>
                  Detailed breakdown of salary components for {{ period }}.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-6">
                  <!-- Earnings -->
                  <div>
                    <h3 class="mb-3 font-medium text-card-foreground">Earnings</h3>
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Description</TableHead>
                          <TableHead class="text-right">Amount</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow>
                          <TableCell>Base Salary</TableCell>
                          <TableCell class="text-right">{{ formatCurrency(salary.base_amount) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="salary.overtime_pay > 0">
                          <TableCell>Overtime Pay</TableCell>
                          <TableCell class="text-right text-green-600">{{ formatCurrency(salary.overtime_pay) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="salary.bonuses > 0">
                          <TableCell>Performance Bonus</TableCell>
                          <TableCell class="text-right text-green-600">{{ formatCurrency(salary.bonuses) }}</TableCell>
                        </TableRow>
                        <TableRow class="font-medium">
                          <TableCell>Total Earnings</TableCell>
                          <TableCell class="text-right">{{ formatCurrency(salary.base_amount + salary.overtime_pay + salary.bonuses) }}</TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>

                  <!-- Deductions -->
                  <div>
                    <h3 class="mb-3 font-medium text-card-foreground">Deductions</h3>
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Description</TableHead>
                          <TableHead class="text-right">Amount</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow>
                          <TableCell>Tax Deduction</TableCell>
                          <TableCell class="text-right text-destructive">{{ formatCurrency(salary.deductions * 0.7) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="attendanceSummary.total_late_minutes > 0">
                          <TableCell>Late Arrival Deduction ({{ attendanceSummary.total_late_minutes }} minutes)</TableCell>
                          <TableCell class="text-right text-destructive">{{ formatCurrency(salary.deductions * 0.15) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="attendanceSummary.total_early_departure_minutes > 0">
                          <TableCell>Early Departure Deduction ({{ attendanceSummary.total_early_departure_minutes }} minutes)</TableCell>
                          <TableCell class="text-right text-destructive">{{ formatCurrency(salary.deductions * 0.1) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="leaveSummary.unpaid_leave_days > 0">
                          <TableCell>Unpaid Leave ({{ leaveSummary.unpaid_leave_days }} days)</TableCell>
                          <TableCell class="text-right text-destructive">{{ formatCurrency(salary.deductions * 0.05) }}</TableCell>
                        </TableRow>
                        <TableRow class="font-medium">
                          <TableCell>Total Deductions</TableCell>
                          <TableCell class="text-right text-destructive">{{ formatCurrency(salary.deductions) }}</TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>

                  <!-- Net Salary -->
                  <div class="rounded-md border border-border p-4">
                    <div class="flex items-center justify-between">
                      <span class="text-lg font-medium">Net Salary:</span>
                      <span class="text-lg font-bold">{{ formatCurrency(salary.net_amount) }}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Attendance Summary -->
            <Card class="mb-6">
              <CardHeader>
                <CardTitle>Attendance Summary</CardTitle>
                <CardDescription>
                  Attendance statistics for {{ period }}.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                  <div class="rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <Clock class="h-5 w-5 text-primary" />
                        <span class="text-sm font-medium">Present Days</span>
                      </div>
                      <span class="text-xl font-bold">{{ attendanceSummary.present_days }}</span>
                    </div>
                  </div>

                  <div class="rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <Calendar class="h-5 w-5 text-amber-500" />
                        <span class="text-sm font-medium">Late Arrivals</span>
                      </div>
                      <span class="text-xl font-bold">{{ attendanceSummary.late_days }}</span>
                    </div>
                  </div>

                  <div class="rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <Clock class="h-5 w-5 text-destructive" />
                        <span class="text-sm font-medium">Early Departures</span>
                      </div>
                      <span class="text-xl font-bold">{{ attendanceSummary.early_departure_days }}</span>
                    </div>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div class="rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <FileText class="h-5 w-5 text-blue-500" />
                        <span class="text-sm font-medium">Paid Leave</span>
                      </div>
                      <span class="text-xl font-bold">{{ leaveSummary.paid_leave_days }}</span>
                    </div>
                  </div>

                  <div class="rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <FileText class="h-5 w-5 text-destructive" />
                        <span class="text-sm font-medium">Unpaid Leave</span>
                      </div>
                      <span class="text-xl font-bold">{{ leaveSummary.unpaid_leave_days }}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Notes and Information -->
            <Card>
              <CardHeader>
                <CardTitle>Notes and Information</CardTitle>
                <CardDescription>
                  Additional information about this salary record.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Alert>
                  <Info class="h-4 w-4" />
                  <AlertTitle>Payment Information</AlertTitle>
                  <AlertDescription>
                    <p>
                      This salary record was generated on {{ formatDate(salary.processed_at) }}.
                      <span v-if="salary.status === 'paid'">
                        Payment was processed on {{ formatDate(salary.paid_at) }}
                        <span v-if="salary.payment_reference">
                          with reference number: {{ salary.payment_reference }}.
                        </span>
                      </span>
                      <span v-else>
                        The current status is <strong>{{ salary.status }}</strong>.
                      </span>
                    </p>
                  </AlertDescription>
                </Alert>

                <div class="mt-4">
                  <p class="text-sm text-muted-foreground">
                    Salary calculations are based on the employee's base salary, attendance records,
                    overtime hours, and applicable deductions according to company policies.
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.btn-secondary {
  @apply inline-flex items-center rounded-md border border-border bg-secondary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-secondary-foreground shadow-sm transition duration-150 ease-in-out hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2;
}

@media print {
  .btn-secondary,
  button {
    display: none !important;
  }
}
</style>
