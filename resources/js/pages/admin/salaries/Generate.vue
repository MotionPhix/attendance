<script setup lang="ts">
import AppLayout from '@/layouts/MainAppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
  AlertCircle,
  ArrowLeft,
  Calculator,
  Calendar,
  Check,
  ChevronDown,
  Clock,
  DollarSign,
  Eye,
  FileText,
  Filter,
  Info,
  Loader2,
  MoreHorizontal,
  Printer,
  RefreshCw,
  Search,
  Settings,
  X
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import dayjs from 'dayjs';
import axios from 'axios';

// Set breadcrumbs for this page
const { setPageBreadcrumbs } = useBreadcrumbs();
setPageBreadcrumbs([
  { label: 'Home', href: '/' },
  { label: 'Salaries', href: route('admin.salaries.index') },
  { label: 'Generate Salaries' }
]);

const props = defineProps({
  departments: Array,
  months: Array,
  years: Array,
  currentMonth: Number,
  currentYear: Number,
  prevMonth: Number,
  prevYear: Number,
  salarySettings: Object,
  employeeCount: Number,
  pendingSalaries: Array,
  currentPeriod: String,
  previousPeriod: String,
});

// Form state
const month = ref(props.currentMonth);
const year = ref(props.currentYear);
const departmentId = ref('');
const includeInactive = ref(false);
const recalculateExisting = ref(false);

// Preview state
const isLoading = ref(false);
const previewData = ref(null);
const selectedEmployees = ref([]);
const showConfirmDialog = ref(false);

// Format helpers
const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

// Get month name
const getMonthName = (monthNum) => {
  return dayjs().month(monthNum - 1).format('MMMM');
};

// Preview salaries
const previewSalaries = async () => {
  isLoading.value = true;
  selectedEmployees.value = [];

  try {
    const response = await axios.post(route('admin.salaries.preview'), {
      month: month.value,
      year: year.value,
      department_id: departmentId.value || null,
      include_inactive: includeInactive.value,
      recalculate_existing: recalculateExisting.value,
    });

    previewData.value = response.data;

    // Auto-select all employees
    if (previewData.value.employees.length > 0) {
      selectedEmployees.value = previewData.value.employees.map(emp => emp.id);
    }
  } catch (error) {
    console.error('Error fetching preview data:', error);
  } finally {
    isLoading.value = false;
  }
};

// Generate salaries
const generateSalaries = () => {
  if (selectedEmployees.value.length === 0) return;

  router.post(route('admin.salaries.process-generation'), {
    month: month.value,
    year: year.value,
    department_id: departmentId.value || null,
    include_inactive: includeInactive.value,
    recalculate_existing: recalculateExisting.value,
    employee_ids: selectedEmployees.value,
  });
};

// Toggle all employees selection
const toggleAllEmployees = (checked) => {
  if (!previewData.value) return;

  if (checked) {
    selectedEmployees.value = previewData.value.employees.map(emp => emp.id);
  } else {
    selectedEmployees.value = [];
  }
};

// Check if all employees are selected
const allEmployeesSelected = computed(() => {
  if (!previewData.value || previewData.value.employees.length === 0) return false;
  return selectedEmployees.value.length === previewData.value.employees.length;
});

// Calculate total amount for selected employees
const totalSelectedAmount = computed(() => {
  if (!previewData.value) return 0;

  return previewData.value.employees
    .filter(emp => selectedEmployees.value.includes(emp.id))
    .reduce((sum, emp) => sum + emp.estimated_net_salary, 0);
});

// Period display
const selectedPeriod = computed(() => {
  return `${getMonthName(month.value)} ${year.value}`;
});

// Watch for department changes to reset preview
watch([month, year, departmentId, includeInactive, recalculateExisting], () => {
  previewData.value = null;
  selectedEmployees.value = [];
});
</script>

<template>
  <AppLayout title="Generate Salaries">
    <Head title="Generate Salaries" />

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-x-2">
              <Link class="flex gap-x-2 items-center" :href="route('admin.salaries.index')">
                <ArrowLeft class="mr-1 h-4 w-4" />
                Back to Salaries
              </Link>

              |

              <h2 class="text-xl font-semibold text-foreground">Generate Salaries</h2>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
              Preview and generate salary records for employees.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Left Column -->
          <div class="space-y-6">
            <!-- Period Selection -->
            <Card>
              <CardHeader>
                <CardTitle>Salary Period</CardTitle>
                <CardDescription>
                  Select the month and year for salary generation.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="grid gap-4">
                  <div class="grid gap-2">
                    <Label for="month">Month</Label>
                    <Select v-model="month">
                      <SelectTrigger id="month">
                        <SelectValue placeholder="Select month" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                          {{ m.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="grid gap-2">
                    <Label for="year">Year</Label>
                    <Select v-model="year">
                      <SelectTrigger id="year">
                        <SelectValue placeholder="Select year" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="y in years" :key="y.value" :value="y.value">
                          {{ y.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <div class="flex items-center text-sm text-muted-foreground">
                  <Calendar class="mr-2 h-4 w-4" />
                  Selected period: <span class="ml-1 font-medium text-foreground">{{ selectedPeriod }}</span>
                </div>
              </CardFooter>
            </Card>

            <!-- Filters -->
            <Card>
              <CardHeader>
                <CardTitle>Filters</CardTitle>
                <CardDescription>
                  Filter employees for salary generation.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="grid gap-4">
                  <div class="grid gap-2">
                    <Label for="department">Department</Label>
                    <Select v-model="departmentId">
                      <SelectTrigger id="department">
                        <SelectValue placeholder="All departments" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem :value="null">All Departments</SelectItem>
                        <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                          {{ dept.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Checkbox id="include-inactive" v-model:checked="includeInactive" />
                    <Label for="include-inactive" class="text-sm font-normal">
                      Include inactive employees
                    </Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Checkbox id="recalculate" v-model:checked="recalculateExisting" />
                    <Label for="recalculate" class="text-sm font-normal">
                      Recalculate existing salary records
                    </Label>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <Button class="w-full" @click="previewSalaries" :disabled="isLoading">
                  <RefreshCw v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                  <Calculator v-else class="mr-2 h-4 w-4" />
                  {{ isLoading ? 'Calculating...' : 'Preview Salaries' }}
                </Button>
              </CardFooter>
            </Card>

            <!-- Salary Settings -->
            <Card>
              <CardHeader>
                <CardTitle>Salary Settings</CardTitle>
                <CardDescription>
                  Current settings for salary calculations.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Overtime Rate:</span>
                    <span class="font-medium">{{ salarySettings?.overtime_rate || 1.5 }}x</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Weekend Overtime Rate:</span>
                    <span class="font-medium">{{ salarySettings?.weekend_overtime_rate || 2.0 }}x</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Late Deduction Method:</span>
                    <span class="font-medium capitalize">{{ (salarySettings?.late_deduction_method || 'per_minute').replace('_', ' ') }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-muted-foreground">Tax Calculation Method:</span>
                    <span class="font-medium capitalize">{{ (salarySettings?.tax_calculation_method || 'progressive').replace('_', ' ') }}</span>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <Link :href="route('admin.settings.salary')" class="flex w-full items-center justify-center text-sm text-muted-foreground hover:text-foreground">
                  <Settings class="mr-1 h-4 w-4" />
                  Manage Salary Settings
                </Link>
              </CardFooter>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="lg:col-span-2">
            <!-- Preview Results -->
            <Card>
              <CardHeader>
                <div class="flex items-center justify-between">
                  <div>
                    <CardTitle>Salary Preview</CardTitle>
                    <CardDescription>
                      Review and select employees for salary generation.
                    </CardDescription>
                  </div>
                  <Button
                    v-if="previewData && previewData.employees.length > 0"
                    variant="outline"
                    size="sm"
                    @click="previewSalaries"
                    :disabled="isLoading"
                  >
                    <RefreshCw v-if="isLoading" class="mr-1 h-4 w-4 animate-spin" />
                    <RefreshCw v-else class="mr-1 h-4 w-4" />
                    Refresh
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <!-- Loading State -->
                <div v-if="isLoading" class="flex h-60 flex-col items-center justify-center">
                  <Loader2 class="h-8 w-8 animate-spin text-primary" />
                  <p class="mt-2 text-sm text-muted-foreground">Calculating salaries...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="!previewData" class="flex h-60 flex-col items-center justify-center">
                  <Calculator class="h-8 w-8 text-muted-foreground" />
                  <h3 class="mt-2 text-lg font-medium">No preview available</h3>
                  <p class="text-sm text-muted-foreground">
                    Select a period and click "Preview Salaries" to see the results.
                  </p>
                </div>

                <!-- No Results -->
                <div v-else-if="previewData.employees.length === 0" class="flex h-60 flex-col items-center justify-center">
                  <AlertCircle class="h-8 w-8 text-muted-foreground" />
                  <h3 class="mt-2 text-lg font-medium">No employees found</h3>
                  <p class="text-sm text-muted-foreground">
                    No employees match your criteria or all salaries have already been generated.
                  </p>
                  <div class="mt-4 flex gap-2">
                    <Button variant="outline" @click="recalculateExisting = true; previewSalaries()">
                      Include Existing Records
                    </Button>
                    <Button variant="outline" @click="includeInactive = true; previewSalaries()">
                      Include Inactive Employees
                    </Button>
                  </div>
                </div>

                <!-- Results Table -->
                <div v-else>
                  <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <Checkbox
                        id="select-all"
                        :checked="allEmployeesSelected"
                        @change="e => toggleAllEmployees(e.target.checked)"
                      />
                      <Label for="select-all" class="text-sm font-normal">
                        Select All ({{ previewData.employees.length }} employees)
                      </Label>
                    </div>
                    <div class="text-sm">
                      <span class="font-medium">{{ selectedEmployees.length }}</span> selected
                    </div>
                  </div>

                  <div class="rounded-md border">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead class="w-12"></TableHead>
                          <TableHead>Employee</TableHead>
                          <TableHead>Department</TableHead>
                          <TableHead>Base Salary</TableHead>
                          <TableHead>Deductions</TableHead>
                          <TableHead>Net Salary</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow v-for="employee in previewData.employees" :key="employee.id" class="hover:bg-muted/50">
                          <TableCell>
                            <Checkbox
                              :checked="selectedEmployees.includes(employee.id)"
                              @change="e => {
                                if (e.target.checked) {
                                  selectedEmployees.push(employee.id);
                                } else {
                                  selectedEmployees = selectedEmployees.filter(id => id !== employee.id);
                                }
                              }"
                            />
                          </TableCell>
                          <TableCell>
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ employee.position }}</div>
                          </TableCell>
                          <TableCell>{{ employee.department }}</TableCell>
                          <TableCell>{{ formatCurrency(employee.base_salary) }}</TableCell>
                          <TableCell>
                            <span class="text-destructive">-{{ formatCurrency(employee.estimated_deductions) }}</span>
                          </TableCell>
                          <TableCell class="font-medium">{{ formatCurrency(employee.estimated_net_salary) }}</TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>

                  <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                      <span class="font-medium">{{ previewData.period.month_name }} {{ previewData.period.year }}</span> salary period
                    </div>
                    <div class="text-sm">
                      Total: <span class="font-medium">{{ formatCurrency(totalSelectedAmount) }}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
              <CardFooter v-if="previewData && previewData.employees.length > 0" class="flex justify-between">
                <div class="flex items-center text-sm text-muted-foreground">
                  <Info class="mr-1 h-4 w-4" />
                  This is an estimate. Actual amounts may vary.
                </div>
                <Button
                  @click="showConfirmDialog = true"
                  :disabled="selectedEmployees.length === 0"
                >
                  Generate Salaries ({{ selectedEmployees.length }})
                </Button>
              </CardFooter>
            </Card>

            <!-- Pending Salaries -->
            <Card v-if="pendingSalaries && pendingSalaries.length > 0" class="mt-6">
              <CardHeader>
                <CardTitle>Pending Salary Records</CardTitle>
                <CardDescription>
                  Salary records that have been generated but not yet processed.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="rounded-md border">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Employee</TableHead>
                        <TableHead>Period</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead class="w-12"></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="salary in pendingSalaries" :key="salary.id" class="hover:bg-muted/50">
                        <TableCell>{{ salary.employee_name }}</TableCell>
                        <TableCell>{{ getMonthName(salary.month) }} {{ salary.year }}</TableCell>
                        <TableCell>{{ salary.created_at }}</TableCell>
                        <TableCell>
                          <Link :href="route('admin.salaries.show', salary.id)" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-muted">
                            <Eye class="h-4 w-4" />
                          </Link>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
              <CardFooter>
                <Link :href="route('admin.salaries.index', { status: 'pending' })" class="flex w-full items-center justify-center text-sm text-muted-foreground hover:text-foreground">
                  View All Pending Salaries
                </Link>
              </CardFooter>
            </Card>
          </div>
        </div>

        <!-- Confirmation Dialog -->
        <Dialog v-model:open="showConfirmDialog">
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Generate Salary Records</DialogTitle>
              <DialogDescription>
                This will generate salary records for {{ selectedEmployees.length }} employees for {{ selectedPeriod }}.
              </DialogDescription>
            </DialogHeader>
            <div class="py-4">
              <div class="rounded-md border bg-muted/50 p-4">
                <div class="flex items-center">
                  <Info class="mr-2 h-5 w-5 text-muted-foreground" />
                  <div>
                    <h4 class="text-sm font-medium">Important Information</h4>
                    <p class="text-sm text-muted-foreground">
                      Generated salaries will be in "Pending" status and can be reviewed before processing payments.
                    </p>
                  </div>
                </div>
              </div>
              <div class="mt-4 space-y-3">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Period:</span>
                  <span class="font-medium">{{ selectedPeriod }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Employees:</span>
                  <span class="font-medium">{{ selectedEmployees.length }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Total Amount:</span>
                  <span class="font-medium">{{ formatCurrency(totalSelectedAmount) }}</span>
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" @click="showConfirmDialog = false">
                Cancel
              </Button>
              <Button @click="generateSalaries">
                Generate Salaries
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.btn-ghost {
  @apply inline-flex items-center rounded-md border border-transparent bg-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-foreground transition duration-150 ease-in-out hover:bg-secondary focus:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 active:bg-secondary/80;
}
</style>
