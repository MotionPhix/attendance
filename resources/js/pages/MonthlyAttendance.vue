<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Calendar, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import AttendanceChart from '@/Components/Attendance/AttendanceChart.vue';
import dayjs from 'dayjs';

const props = defineProps({
  stats: Object,
  dailyLogs: Array,
  trends: Array,
  currentMonth: Object
});

// Computed properties
const isCurrentMonth = computed(() => {
  const now = dayjs();
  return now.month() + 1 === props.currentMonth.month && now.year() === props.currentMonth.year;
});

const firstDayOfMonth = computed(() => {
  const firstDay = dayjs(`${props.currentMonth.year}-${props.currentMonth.month}-01`).day();
  return firstDay; // 0 for Sunday, 1 for Monday, etc.
});

// Methods
const changeMonth = (delta) => {
  const currentDate = dayjs(`${props.currentMonth.year}-${props.currentMonth.month}-01`);
  const newDate = delta > 0 ? currentDate.add(delta, 'month') : currentDate.subtract(Math.abs(delta), 'month');

  router.get(route('attendance.monthly'), {
    month: newDate.month() + 1, // dayjs months are 0-indexed
    year: newDate.year()
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['stats', 'dailyLogs', 'currentMonth']
  });
};

const goToCurrentMonth = () => {
  const now = dayjs();

  router.get(route('attendance.monthly'), {
    month: now.month() + 1,
    year: now.year()
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['stats', 'dailyLogs', 'currentMonth']
  });
};

const isPastDay = (dateString) => {
  const date = dayjs(dateString);
  const today = dayjs();
  return date.isBefore(today, 'day');
};
</script>

<template>
  <AppLayout title="Monthly Attendance">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Monthly Attendance
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Month Selector -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6 flex flex-wrap items-center justify-between">
            <div class="flex items-center space-x-4">
              <button
                @click="changeMonth(-1)"
                class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <ChevronLeft class="h-5 w-5 text-gray-600 dark:text-gray-400" />
              </button>

              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ currentMonth.name }}
              </h3>

              <button
                @click="changeMonth(1)"
                class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <ChevronRight class="h-5 w-5 text-gray-600 dark:text-gray-400" />
              </button>
            </div>

            <button
              v-if="!isCurrentMonth"
              @click="goToCurrentMonth"
              class="flex items-center px-3 py-1.5 text-sm bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-md"
            >
              <Calendar class="h-4 w-4 mr-1" />
              Current Month
            </button>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
              Present
            </h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.present_days }}/{{ stats.total_working_days }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Days</p>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
              Attendance
            </h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.attendance_rate }}%
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rate</p>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
              Late
            </h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.late_arrivals }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Times</p>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
              Hours
            </h4>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ stats.total_hours_worked }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Worked</p>
          </div>
        </div>

        <!-- Attendance Trends Chart -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Attendance Trends
            </h3>
            <AttendanceChart :trends="trends" type="area" :height="350" />
          </div>
        </div>

        <!-- Monthly Calendar -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
              Monthly Calendar
            </h3>

            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 gap-1 mb-2">
              <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day"
                   class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">
                {{ day }}
              </div>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1">
              <!-- Empty cells for days before the first of the month -->
              <div v-for="_ in firstDayOfMonth" :key="'empty-' + _"
                   class="h-24 sm:h-32 bg-gray-50 dark:bg-gray-900 rounded-md"></div>

              <!-- Actual days -->
              <div v-for="log in dailyLogs" :key="log.date"
                   :class="[
                     'h-24 sm:h-32 p-2 rounded-md border',
                     log.is_weekend ? 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                     log.is_today ? 'ring-2 ring-blue-500 dark:ring-blue-400' : ''
                   ]">
                <!-- Day number -->
                <div class="flex justify-between items-start">
                  <span :class="[
                    'inline-flex h-6 w-6 items-center justify-center rounded-full text-sm',
                    log.is_today ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300'
                  ]">
                    {{ log.day }}
                  </span>

                  <!-- Status indicator -->
                  <div v-if="log.attendance" class="flex items-center">
                    <span v-if="log.attendance.status === 'on_time'"
                          class="h-2 w-2 rounded-full bg-green-500"></span>
                    <span v-else-if="log.attendance.status === 'late'"
                          class="h-2 w-2 rounded-full bg-amber-500"></span>
                    <span v-else-if="log.attendance.status === 'early_departure'"
                          class="h-2 w-2 rounded-full bg-orange-500"></span>
                    <span v-else-if="log.attendance.status === 'late_and_early_departure'"
                          class="h-2 w-2 rounded-full bg-red-500"></span>
                  </div>
                </div>

                <!-- Attendance details -->
                <div v-if="log.attendance" class="mt-2 text-xs">
                  <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>In:</span>
                    <span>{{ log.attendance.check_in }}</span>
                  </div>
                  <div v-if="log.attendance.check_out" class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>Out:</span>
                    <span>{{ log.attendance.check_out }}</span>
                  </div>
                  <div v-if="log.attendance.late_minutes > 0" class="text-amber-600 dark:text-amber-400 mt-1">
                    Late: {{ log.attendance.late_minutes }}m
                  </div>
                  <div v-if="log.attendance.early_departure_minutes > 0" class="text-orange-600 dark:text-orange-400 mt-1">
                    Early: {{ log.attendance.early_departure_minutes }}m
                  </div>
                </div>

                <!-- No attendance indicator for past days -->
                <div v-else-if="isPastDay(log.date) && !log.is_weekend" class="mt-4 flex justify-center">
                  <span class="text-xs text-red-500 dark:text-red-400">Absent</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
