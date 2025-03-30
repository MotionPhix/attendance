<script setup lang="ts">
import AppLayout from '@/Layouts/MainAppLayout.vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Calculator, Calendar, ClipboardCheck, DollarSign, FileText, LogIn, LogOut, Users } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { useAppearance } from '@/composables/useAppearance';

// Set breadcrumbs for this page
const { setPageBreadcrumbs } = useBreadcrumbs();
setPageBreadcrumbs([
  { label: 'Home', href: '/' },
  { label: 'Dashboard' }
]);

const props = defineProps({
  employeeStats: Object,
  attendanceStats: Object,
  salaryStats: Object,
  recentActivities: Array,
  pendingLeaveRequests: Array,
  topPerformers: Array,
  currentDate: String,
  currentMonth: String,
});

// Get current theme
const { appearance } = useAppearance();
const isDarkMode = computed(() => appearance.value === 'dark');

// Format helpers
const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (dateString) => {
  return dayjs(dateString).format('MMM D, YYYY');
};

const formatDateTime = (dateTimeString) => {
  return dayjs(dateTimeString).format('MMM D, YYYY h:mm A');
};

// Chart colors based on theme
const getChartColors = () => {
  // Using more vibrant and distinct colors
  return isDarkMode.value
    ? ['#3b82f6', '#f97316', '#ef4444', '#8b5cf6', '#ec4899']  // Blue, Orange, Red, Purple, Pink
    : ['#2563eb', '#ea580c', '#dc2626', '#7c3aed', '#db2777'];  // Darker versions for light mode
};

// Chart data for attendance trends
const attendanceChartSeries = computed(() => [
  {
    name: 'Attendance Rate',
    type: 'line',
    data: props.attendanceStats.monthly_trends.map((item) => item.attendance_rate),
  },
  {
    name: 'Late Arrivals',
    type: 'column',
    data: props.attendanceStats.monthly_trends.map((item) => item.late_arrivals),
  },
  {
    name: 'Early Departures',
    type: 'column',
    data: props.attendanceStats.monthly_trends.map((item) => item.early_departures),
  },
]);

const attendanceChartOptions = computed(() => ({
  chart: {
    height: 350,
    type: 'line',
    stacked: false,
    toolbar: {
      show: false,
    },
    fontFamily: 'var(--font-sans)',
    background: 'transparent',
    foreColor: isDarkMode.value ? 'var(--foreground)' : 'var(--foreground)',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  stroke: {
    width: [4, 0, 0],
    curve: 'smooth',
    lineCap: 'round'
  },
  plotOptions: {
    bar: {
      columnWidth: '50%',
      borderRadius: 0,
      dataLabels: {
        position: 'top',
      },
    },
  },
  colors: getChartColors(),
  fill: {
    type: ['gradient', 'solid', 'solid'],
    gradient: {
      shade: 'light',
      type: 'vertical',
      shadeIntensity: 0.5,
      gradientToColors: undefined,
      inverseColors: false,
      opacityFrom: 0.8,
      opacityTo: 0.5,
      stops: [0, 100]
    },
    opacity: [1, 0.7, 0.7], // Slightly reduced opacity for better visibility
  },
  labels: props.attendanceStats.monthly_trends.map((item) => item.month),
  markers: {
    size: 5,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  grid: {
    borderColor: isDarkMode.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
    row: {
      colors: [isDarkMode.value ? 'transparent' : 'transparent'],
      opacity: 0.5
    },
    padding: {
      bottom: 0
    }
  },
  xaxis: {
    type: 'category',
    labels: {
      style: {
        colors: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
        fontSize: '12px',
        fontFamily: 'var(--font-sans)',
      }
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: [
    {
      title: {
        text: 'Attendance Rate (%)',
        style: {
          color: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
          fontSize: '12px',
          fontFamily: 'var(--font-sans)',
        }
      },
      min: 0,
      max: 100,
      labels: {
        style: {
          colors: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
          fontSize: '12px',
          fontFamily: 'var(--font-sans)',
        },
        formatter: (value) => `${value.toFixed(0)}%`
      }
    },
    {
      opposite: true,
      title: {
        text: 'Count',
        style: {
          color: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
          fontSize: '12px',
          fontFamily: 'var(--font-sans)',
        }
      },
      min: 0,
      labels: {
        style: {
          colors: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
          fontSize: '12px',
          fontFamily: 'var(--font-sans)',
        }
      }
    },
  ],
  tooltip: {
    shared: true,
    intersect: false,
    theme: isDarkMode.value ? 'dark' : 'light',
    y: {
      formatter: function (value, { seriesIndex }) {
        if (seriesIndex === 0) {
          return value.toFixed(1) + '%';
        }
        return value;
      },
    },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    offsetY: -10,
    labels: {
      colors: isDarkMode.value ? 'var(--foreground)' : 'var(--foreground)',
    },
    markers: {
      width: 12,
      height: 12,
      strokeWidth: 0,
      radius: 12,
      offsetX: -3
    },
    itemMargin: {
      horizontal: 10,
      vertical: 0
    },
    onItemClick: {
      toggleDataSeries: true
    },
    onItemHover: {
      highlightDataSeries: true
    },
    formatter: function(seriesName, opts) {
      // Add a colored dot before each legend item
      return ['<span class="legend-item">', seriesName, '</span>'].join(' ');
    },
    fontFamily: 'var(--font-sans)',
    fontSize: '13px',
    fontWeight: 500,
  },
  states: {
    hover: {
      filter: {
        type: 'lighten',
        value: 0.05,
      }
    },
    active: {
      allowMultipleDataPointsSelection: false,
      filter: {
        type: 'darken',
        value: 0.1,
      }
    }
  }
}));

// Department chart colors - using a different color palette for better distinction
const getDepartmentChartColors = () => {
  // Using a different color palette for the department chart
  return isDarkMode.value
    ? ['#3b82f6', '#f97316', '#10b981', '#8b5cf6', '#ec4899', '#f59e0b', '#14b8a6', '#ef4444', '#6366f1', '#84cc16']
    : ['#2563eb', '#ea580c', '#059669', '#7c3aed', '#db2777', '#d97706', '#0d9488', '#dc2626', '#4f46e5', '#65a30d'];
};

// Chart data for department distribution
const departmentChartSeries = computed(() => props.employeeStats.by_department.map((dept) => dept.count));

const departmentChartOptions = computed(() => ({
  chart: {
    type: 'donut',
    fontFamily: 'var(--font-sans)',
    background: 'transparent',
    foreColor: isDarkMode.value ? 'var(--foreground)' : 'var(--foreground)',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      }
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  labels: props.employeeStats.by_department.map((dept) => dept.department),
  colors: getDepartmentChartColors(),
  legend: {
    position: 'bottom',
    horizontalAlign: 'center',
    labels: {
      colors: isDarkMode.value ? 'var(--foreground)' : 'var(--foreground)',
    },
    markers: {
      width: 12,
      height: 12,
      strokeWidth: 0,
      radius: 12,
      offsetX: -3
    },
    itemMargin: {
      horizontal: 10,
      vertical: 5
    },
    formatter: function(seriesName, opts) {
      // Add count to each legend item
      return [
        '<span class="legend-item">',
        seriesName,
        ': ',
        '<strong>',
        props.employeeStats.by_department[opts.seriesIndex].count,
        '</strong>',
        '</span>'
      ].join('');
    },
    fontFamily: 'var(--font-sans)',
    fontSize: '13px',
    fontWeight: 500,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '55%',
        background: 'transparent',
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: '22px',
            fontFamily: 'var(--font-sans)',
            color: isDarkMode.value ? 'var(--foreground)' : 'var(--foreground)',
            offsetY: -10
          },
          value: {
            show: true,
            fontSize: '16px',
            fontFamily: 'var(--font-sans)',
            color: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
            offsetY: 5,
            formatter: function (val) {
              return val;
            }
          },
          total: {
            show: true,
            showAlways: true,
            label: 'Total',
            fontSize: '16px',
            fontFamily: 'var(--font-sans)',
            color: isDarkMode.value ? 'var(--muted-foreground)' : 'var(--muted-foreground)',
            formatter: function (w) {
              return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
            }
          }
        }
      }
    }
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          width: 300
        },
        legend: {
          position: 'bottom'
        }
      }
    }
  ],
  dataLabels: {
    enabled: false
  },
  tooltip: {
    theme: isDarkMode.value ? 'dark' : 'light',
    y: {
      formatter: function (val) {
        return val + ' employees';
      }
    }
  },
  stroke: {
    width: 2,
    colors: isDarkMode.value ? ['var(--background)'] : ['var(--background)']
  }
}));

// Watch for theme changes and update charts
const updateChartsOnThemeChange = () => {
  // Force chart updates when theme changes
  attendanceChartOptions.value = { ...attendanceChartOptions.value };
  departmentChartOptions.value = { ...departmentChartOptions.value };
};

// Set up a watcher for theme changes
watch(() => isDarkMode.value, () => {
  updateChartsOnThemeChange();
}, { immediate: true });
</script>

<template>
  <AppLayout title="Admin Dashboard">
    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Date and Quick Stats -->
        <div class="mb-6">
          <div class="mb-4 flex flex-wrap items-center justify-between">
            <h3 class="text-lg font-medium text-foreground">
              {{ currentDate }}
            </h3>
            <div class="flex space-x-2">
              <Link :href="route('admin.employees.index')" class="btn-secondary">
                <Users class="mr-1 h-4 w-4" />
                Manage Employees
              </Link>
              <Link :href="route('admin.salaries.generate')" class="btn-primary">
                <Calculator class="mr-1 h-4 w-4" />
                Generate Salaries
              </Link>
            </div>
          </div>

          <!-- Quick Stats Cards -->
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Total Employees -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="flex items-center">
                <div class="rounded-full bg-primary/10 p-3">
                  <Users class="h-6 w-6 text-primary" />
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-muted-foreground">Total Employees</p>
                  <p class="text-2xl font-semibold text-card-foreground">{{ employeeStats.total }}</p>
                  <p class="text-sm text-muted-foreground">{{ employeeStats.active }} active, {{ employeeStats.on_leave }} on leave</p>
                </div>
              </div>
            </div>

            <!-- Today's Attendance -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="flex items-center">
                <div class="rounded-full bg-green-500/10 p-3">
                  <ClipboardCheck class="h-6 w-6 text-green-500" />
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-muted-foreground">Today's Attendance</p>
                  <p class="text-2xl font-semibold text-card-foreground">{{ attendanceStats.today.present }} / {{ employeeStats.active }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ attendanceStats.today.late }} late, {{ attendanceStats.today.absent }} absent
                  </p>
                </div>
              </div>
            </div>

            <!-- Monthly Salary -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="flex items-center">
                <div class="rounded-full bg-amber-500/10 p-3">
                  <DollarSign class="h-6 w-6 text-amber-500" />
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-muted-foreground">Monthly Salary</p>
                  <p class="text-2xl font-semibold text-card-foreground">
                    {{ formatCurrency(salaryStats.total_net_amount) }}
                  </p>
                  <p class="text-sm text-muted-foreground">
                    {{ salaryStats.processed_count }} processed, {{ salaryStats.paid_count }} paid
                  </p>
                </div>
              </div>
            </div>

            <!-- Pending Leaves -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="flex items-center">
                <div class="rounded-full bg-purple-500/10 p-3">
                  <Calendar class="h-6 w-6 text-purple-500" />
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-muted-foreground">Pending Leaves</p>
                  <p class="text-2xl font-semibold text-card-foreground">
                    {{ pendingLeaveRequests.length }}
                  </p>
                  <p class="text-sm text-muted-foreground">Requests awaiting approval</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Left Column -->
          <div class="space-y-6 lg:col-span-2">
            <!-- Attendance Trends Chart -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <h3 class="mb-4 text-lg font-medium text-card-foreground">Monthly Attendance Trends</h3>
              <div class="h-80">
                <apexchart type="line" height="100%" :options="attendanceChartOptions" :series="attendanceChartSeries"></apexchart>
              </div>
            </div>

            <!-- Department Distribution -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-medium text-card-foreground">Department Distribution</h3>
                <Link :href="route('admin.departments.index')" class="text-sm text-primary hover:underline"> View All </Link>
              </div>
              <div class="h-80">
                <apexchart type="donut" height="100%" :options="departmentChartOptions" :series="departmentChartSeries"></apexchart>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-6">
            <!-- Recent Activities -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <h3 class="mb-4 text-lg font-medium text-card-foreground">Recent Activities</h3>
              <div class="space-y-4">
                <div v-for="(activity, index) in recentActivities" :key="index" class="flex items-start">
                  <div class="flex-shrink-0">
                    <div v-if="activity.type === 'check_in'" class="rounded-full bg-green-500/10 p-2">
                      <LogIn class="h-4 w-4 text-green-500" />
                    </div>
                    <div v-else-if="activity.type === 'check_out'" class="rounded-full bg-blue-500/10 p-2">
                      <LogOut class="h-4 w-4 text-blue-500" />
                    </div>
                    <div v-else-if="activity.type === 'leave_request'" class="rounded-full bg-amber-500/10 p-2">
                      <FileText class="h-4 w-4 text-amber-500" />
                    </div>
                  </div>
                  <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-card-foreground">{{ activity.user }}</p>
                    <p class="text-sm text-muted-foreground">{{ activity.details }}</p>
                    <p class="text-xs text-muted-foreground/70">{{ formatDateTime(activity.time) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Top Performers -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <h3 class="mb-4 text-lg font-medium text-card-foreground">Top Performers</h3>
              <div class="space-y-4">
                <div v-for="(performer, index) in topPerformers" :key="performer.user_id" class="flex items-center">
                  <div
                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary"
                  >
                    {{ index + 1 }}
                  </div>
                  <div class="ml-3 flex-1">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-card-foreground">{{ performer.name }}</p>
                      <p class="text-sm font-semibold text-primary">{{ performer.attendance_rate }}%</p>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-secondary">
                      <div class="h-2 rounded-full bg-primary" :style="{ width: `${performer.attendance_rate}%` }"></div>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                      {{ performer.present_days }}/{{ performer.present_days + performer.absent_days }} days, {{ performer.late_arrivals }} late
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pending Leave Requests -->
            <div class="overflow-hidden rounded-lg border border-border bg-card p-6 shadow-sm">
              <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-medium text-card-foreground">Pending Leave Requests</h3>
                <Link :href="route('admin.leave-requests.index')" class="text-sm text-primary hover:underline"> View All </Link>
              </div>
              <div v-if="pendingLeaveRequests.length > 0" class="space-y-4">
                <div
                  v-for="request in pendingLeaveRequests"
                  :key="request.id"
                  class="border-b border-border pb-3 last:border-0 last:pb-0"
                >
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-card-foreground">{{ request.user.name }}</p>
                    <span class="rounded-full bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-500"> Pending </span>
                  </div>
                  <p class="mt-1 text-sm text-muted-foreground">{{ request.duration_days }} days {{ request.leave_type }} leave</p>
                  <p class="mt-1 text-xs text-muted-foreground/70">
                    {{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}
                  </p>
                </div>
              </div>
              <div v-else class="py-4 text-center">
                <Calendar class="mx-auto h-8 w-8 text-muted-foreground/50" />
                <p class="mt-2 text-sm text-muted-foreground">No pending leave requests</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.btn-primary {
  @apply inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-foreground transition duration-150 ease-in-out hover:bg-primary/90 focus:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 active:bg-primary/80;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-border bg-secondary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-secondary-foreground shadow-sm transition duration-150 ease-in-out hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2;
}

/* Custom styles for chart legends */
:deep(.apexcharts-legend) {
  padding: 0 10px !important;
}

:deep(.legend-item) {
  display: inline-flex;
  align-items: center;
  font-weight: 500;
}

:deep(.apexcharts-legend-marker) {
  margin-right: 5px !important;
}
</style>
