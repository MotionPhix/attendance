<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import MainAppLayout from '@/layouts/MainAppLayout.vue';
import { Pagination } from '@/components/ui/pagination';
import {
  ArrowUpDown,
  ChevronDown,
  Check,
  X,
  DollarSign,
  FileText,
  Eye,
  RefreshCw,
  Download,
  Filter,
  Calendar,
  Users
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import debounce from 'lodash/debounce';

const props = defineProps({
  salaries: Object,
  departments: Array,
  summary: Object,
  filters: Object,
  statuses: Object,
  currentPeriod: String
});

const selectedSalaries = ref([]);
const showConfirmation = ref(false);
const search = ref(props.filters.search || '');
const selectedDepartment = ref(props.filters.department || '');
const selectedStatus = ref(props.filters.status || '');
const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);
const sortField = ref(props.filters.sort_field || 'net_amount');
const sortDirection = ref(props.filters.sort_direction || 'desc');

// Generate months array
const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' }
];

// Generate years array (current year and 5 years back)
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 6 }, (_, i) => currentYear - i);

// Forms
const markAsPaidForm = useForm({
  salary_ids: []
});

const recalculateForm = useForm({});

const markSinglePaidForm = useForm({});

// Computed
const isAllSelected = computed(() => {
  return props.salaries.data.length > 0 && selectedSalaries.value.length === props.salaries.data.length;
});

// Methods
const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedSalaries.value = [];
  } else {
    selectedSalaries.value = props.salaries.data.map((salary) => salary.id);
  }
};

const confirmMarkAsPaid = () => {
  showConfirmation.value = true;
};

const processMarkAsPaid = () => {
  markAsPaidForm.salary_ids = selectedSalaries.value;
  markAsPaidForm.post(route('admin.salaries.mark-as-paid'), {
    onSuccess: () => {
      selectedSalaries.value = [];
      showConfirmation.value = false;
    }
  });
};

const markSingleAsPaid = (id) => {
  markSinglePaidForm.post(route('admin.salaries.mark-single-as-paid', id));
};

const recalculateSalary = (id) => {
  recalculateForm.post(route('admin.salaries.recalculate', id));
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(value);
};

const getStatusBadgeVariant = (status) => {
  switch (status) {
    case 'pending':
      return 'warning';
    case 'processed':
      return 'secondary';
    case 'paid':
      return 'success';
    default:
      return 'outline';
  }
};

const updateSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  applyFilters();
};

const applyFilters = () => {
  window.location.href = route('admin.salaries.index', {
    search: search.value,
    department: selectedDepartment.value,
    status: selectedStatus.value,
    month: selectedMonth.value,
    year: selectedYear.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  });
};

const debouncedSearch = debounce(() => {
  applyFilters();
}, 500);

// Watch for changes in selected salaries
watch(selectedSalaries, (newVal) => {
  console.log('Selected salaries:', newVal);
});

// Sort indicator component
const SortIndicator = ({ field, currentSort, direction }) => {
  if (field !== currentSort) {
    return h(ArrowUpDown, { class: 'ml-1 h-4 w-4 inline' });
  }
  return direction === 'asc'
    ? h('span', { class: 'ml-1 inline-block' }, '↑')
    : h('span', { class: 'ml-1 inline-block' }, '↓');
};
</script>

<template>
  <MainAppLayout>
    <div class="container py-10">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Salary Records - {{ currentPeriod }}</h1>
        <div class="flex space-x-2">
          <Link :href="route('admin.salaries.generate')">
            <Button>
              <DollarSign class="mr-2 h-4 w-4" />
              Generate Salaries
            </Button>
          </Link>
          <Link :href="route('admin.salaries.department-stats', { month: selectedMonth, year: selectedYear })">
            <Button variant="outline">
              <Users class="mr-2 h-4 w-4" />
              Department Stats
            </Button>
          </Link>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-card p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Employees</h3>
            <Users class="h-4 w-4 text-muted-foreground" />
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <div class="text-3xl font-bold">{{ summary.processed_employees }}</div>
            <div class="text-sm text-muted-foreground">of {{ summary.total_employees }}</div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Total Salary</h3>
            <DollarSign class="h-4 w-4 text-muted-foreground" />
          </div>
          <div class="mt-3">
            <div class="text-3xl font-bold">{{ formatCurrency(summary.total_net_amount) }}</div>
            <div class="mt-1 text-sm text-muted-foreground">Base: {{ formatCurrency(summary.total_base_salary) }}</div>
          </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-muted-foreground">Adjustments</h3>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2">
            <div>
              <div class="text-xs text-muted-foreground">Deductions</div>
              <div class="text-sm font-semibold text-destructive">-{{ formatCurrency(summary.total_deductions) }}</div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Bonuses</div>
              <div class="text-sm font-semibold text-green-600">+{{ formatCurrency(summary.total_bonuses) }}</div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Overtime</div>
              <div class="text-sm font-semibold text-blue-600">+{{ formatCurrency(summary.total_overtime_pay) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="mb-6 rounded-lg border bg-card shadow-sm">
        <div class="p-6">
          <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
            <!-- Search -->
            <div class="w-full md:w-1/3">
              <Input
                v-model="search"
                type="text"
                placeholder="Search employees..."
                @input="debouncedSearch"
              />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Department Filter -->
              <Select v-model="selectedDepartment" @update:modelValue="applyFilters">
                <SelectTrigger class="w-[180px]">
                  <SelectValue placeholder="All Departments" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">All Departments</SelectItem>
                  <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <!-- Status Filter -->
              <Select v-model="selectedStatus" @update:modelValue="applyFilters">
                <SelectTrigger class="w-[150px]">
                  <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">All Statuses</SelectItem>
                  <SelectItem v-for="(label, value) in statuses" :key="value" :value="value">
                    {{ label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <!-- Month Filter -->
              <Select v-model="selectedMonth" @update:modelValue="applyFilters">
                <SelectTrigger class="w-[150px]">
                  <SelectValue placeholder="Select Month" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="month in months" :key="month.value" :value="month.value">
                    {{ month.label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <!-- Year Filter -->
              <Select v-model="selectedYear" @update:modelValue="applyFilters">
                <SelectTrigger class="w-[120px]">
                  <SelectValue placeholder="Select Year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="year in years" :key="year" :value="year">
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div v-if="selectedSalaries.length > 0" class="mt-4 flex items-center space-x-2">
            <Button variant="default" @click="confirmMarkAsPaid">
              <Check class="mr-2 h-4 w-4" />
              Mark {{ selectedSalaries.length }} as Paid
            </Button>
            <Button variant="outline" @click="selectedSalaries = []">
              <X class="mr-2 h-4 w-4" />
              Clear Selection
            </Button>
          </div>
        </div>
      </div>

      <!-- Salary Records Table -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
            <tr class="border-b bg-muted/50 text-sm">
              <th class="w-[40px] px-4 py-3 text-left">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th
                class="px-4 py-3 text-left font-medium text-muted-foreground"
                @click="updateSort('employee')"
              >
                <div class="flex cursor-pointer items-center">
                  Employee
                  <ArrowUpDown v-if="sortField !== 'employee'" class="ml-1 h-4 w-4" />
                  <span v-else class="ml-1">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th
                class="px-4 py-3 text-left font-medium text-muted-foreground"
                @click="updateSort('department')"
              >
                <div class="flex cursor-pointer items-center">
                  Department
                  <ArrowUpDown v-if="sortField !== 'department'" class="ml-1 h-4 w-4" />
                  <span v-else class="ml-1">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th
                class="px-4 py-3 text-left font-medium text-muted-foreground"
                @click="updateSort('base_amount')"
              >
                <div class="flex cursor-pointer items-center">
                  Base Salary
                  <ArrowUpDown v-if="sortField !== 'base_amount'" class="ml-1 h-4 w-4" />
                  <span v-else class="ml-1">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th
                class="px-4 py-3 text-left font-medium text-muted-foreground"
                @click="updateSort('net_amount')"
              >
                <div class="flex cursor-pointer items-center">
                  Net Salary
                  <ArrowUpDown v-if="sortField !== 'net_amount'" class="ml-1 h-4 w-4" />
                  <span v-else class="ml-1">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th
                class="px-4 py-3 text-left font-medium text-muted-foreground"
                @click="updateSort('status')"
              >
                <div class="flex cursor-pointer items-center">
                  Status
                  <ArrowUpDown v-if="sortField !== 'status'" class="ml-1 h-4 w-4" />
                  <span v-else class="ml-1">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="salary in salaries.data" :key="salary.id" class="border-b hover:bg-muted/50" :class="{ 'bg-muted/30': selectedSalaries.includes(salary.id) }">
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                  :value="salary.id"
                  v-model="selectedSalaries"
                />
              </td>
              <td class="px-4 py-3">
                <div>
                  <div class="font-medium">{{ salary.user.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ salary.user.email }}</div>
                </div>
              </td>
              <td class="px-4 py-3">
                <div>
                  <div>{{ salary.user.employee_profile?.department?.name || 'N/A' }}</div>
                  <div class="text-xs text-muted-foreground">{{ salary.user.employee_profile?.position || 'N/A' }}</div>
                </div>
              </td>
              <td class="px-4 py-3">
                <div>
                  <div>{{ formatCurrency(salary.base_amount) }}</div>
                  <div class="flex text-xs">
                    <span class="text-destructive">-{{ formatCurrency(salary.deductions) }}</span>
                    <span class="mx-1">|</span>
                    <span class="text-green-600">+{{ formatCurrency(salary.bonuses) }}</span>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 font-medium">
                {{ formatCurrency(salary.net_amount) }}
              </td>
              <td class="px-4 py-3">
                <Badge :variant="getStatusBadgeVariant(salary.status)">
                  {{ statuses[salary.status] }}
                </Badge>
              </td>
              <td class="px-4 py-3 text-right">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                      <span class="sr-only">Open menu</span>
                      <ChevronDown class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem asChild>
                      <Link :href="route('admin.salaries.show', salary.id)">
                        <Eye class="mr-2 h-4 w-4" />
                        View Details
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="salary.status !== 'paid'" @click="markSingleAsPaid(salary.id)">
                      <Check class="mr-2 h-4 w-4" />
                      Mark as Paid
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="recalculateSalary(salary.id)">
                      <RefreshCw class="mr-2 h-4 w-4" />
                      Recalculate
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <a :href="route('admin.salaries.payslip', salary.id)" target="_blank">
                        <FileText class="mr-2 h-4 w-4" />
                        View Payslip
                      </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <a :href="route('admin.salaries.download', salary.id)">
                        <Download class="mr-2 h-4 w-4" />
                        Download PDF
                      </a>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </td>
            </tr>
            <tr v-if="salaries.data.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                No salary records found for the selected period.
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="border-t p-4">
          <Pagination v-if="salaries.links.length > 3" :links="salaries.links" />
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <Dialog :open="showConfirmation" @update:open="showConfirmation = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Mark Salaries as Paid</DialogTitle>
          <DialogDescription>
            Are you sure you want to mark {{ selectedSalaries.length }} salary records as paid?
            This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showConfirmation = false">Cancel</Button>
          <Button @click="processMarkAsPaid">Confirm</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </MainAppLayout>
</template>
