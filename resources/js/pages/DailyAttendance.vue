<template>
  <AppLayout title="Daily Attendance">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Daily Attendance
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Clock In/Out Section -->
          <div class="md:col-span-1">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance Check</h3>
                <ClockInOutButton
                  :can-check-in="canCheckIn"
                  :can-check-out="canCheckOut"
                  :active-session="activeSession"
                  @checked-in="refreshPage"
                  @checked-out="refreshPage"
                />
              </div>
            </div>
          </div>

          <!-- Daily Stats Section -->
          <div class="md:col-span-2">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Today's Summary</h3>

                <div v-if="activeSession || stats.present_days > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Status Card -->
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Status</h4>
                    <div class="flex items-center">
                      <div v-if="activeSession && !activeSession.check_out_time"
                           class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                      <div v-else-if="activeSession && activeSession.check_out_time"
                           class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                      <div v-else class="w-3 h-3 bg-gray-500 rounded-full mr-2"></div>

                      <span v-if="activeSession && !activeSession.check_out_time" class="font-medium text-green-700">Currently Working</span>
                      <span v-else-if="activeSession && activeSession.check_out_time" class="font-medium text-blue-700">Completed</span>
                      <span v-else class="font-medium text-gray-700">Not Started</span>
                    </div>
                  </div>

                  <!-- Check-in Time -->
                  <div v-if="activeSession" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Check-in Time</h4>
                    <p class="text-lg font-semibold">{{ formatDateTime(activeSession.check_in_time) }}</p>
                    <p v-if="activeSession.late_minutes > 0" class="text-sm text-amber-600 mt-1">
                      Late by {{ activeSession.late_minutes }} minutes
                    </p>
                    <p v-else class="text-sm text-green-600 mt-1">On time</p>
                  </div>

                  <!-- Check-out Time -->
                  <div v-if="activeSession && activeSession.check_out_time" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Check-out Time</h4>
                    <p class="text-lg font-semibold">{{ formatDateTime(activeSession.check_out_time) }}</p>
                    <p v-if="activeSession.early_departure_minutes > 0" class="text-sm text-amber-600 mt-1">
                      Left early by {{ activeSession.early_departure_minutes }} minutes
                    </p>
                    <p v-else class="text-sm text-green-600 mt-1">Completed full day</p>
                  </div>

                  <!-- Hours Worked -->
                  <div v-if="activeSession && activeSession.check_out_time" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Hours Worked</h4>
                    <p class="text-lg font-semibold">{{ calculateHoursWorked(activeSession) }}</p>
                  </div>
                </div>

                <div v-else class="text-center py-8">
                  <DocumentIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <h3 class="text-lg font-medium text-gray-900">No attendance recorded today</h3>
                  <p class="text-gray-500 mt-1">Use the clock-in button to start your workday.</p>
                </div>

                <!-- Notes Section -->
                <div v-if="activeSession && activeSession.notes" class="mt-6">
                  <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Notes</h4>
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-line">{{ activeSession.notes }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">This Week</h4>
                <p class="text-2xl font-bold">{{ stats.present_days }}/{{ stats.total_working_days }}</p>
                <p class="text-sm text-gray-500">Days Present</p>
              </div>

              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Attendance</h4>
                <p class="text-2xl font-bold">{{ stats.attendance_rate }}%</p>
                <p class="text-sm text-gray-500">Rate</p>
              </div>

              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Late</h4>
                <p class="text-2xl font-bold">{{ stats.late_arrivals }}</p>
                <p class="text-sm text-gray-500">Times</p>
              </div>

              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Hours</h4>
                <p class="text-2xl font-bold">{{ stats.total_hours_worked }}</p>
                <p class="text-sm text-gray-500">Worked</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { DocumentIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClockInOutButton from '@/Components/Attendance/ClockInOutButton.vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';

interface AttendanceLog {
  id: number;
  user_id: number;
  check_in_time: string;
  check_out_time: string | null;
  late_minutes: number;
  early_departure_minutes: number;
  status: string;
  notes: string | null;
}

interface Stats {
  period: {
    start_date: string;
    end_date: string;
  };
  total_working_days: number;
  present_days: number;
  absent_days: number;
  late_arrivals: number;
  early_departures: number;
  attendance_rate: number;
  total_hours_worked: number;
}

interface Props {
  stats: Stats;
  activeSession: AttendanceLog | null;
  canCheckIn: boolean;
  canCheckOut: boolean;
}

const props = defineProps<Props>();

const formatDateTime = (dateTimeString: string) => {
  return dayjs(dateTimeString).format('HH:mm:ss');
};

const calculateHoursWorked = (session: AttendanceLog) => {
  if (!session.check_in_time || !session.check_out_time) {
    return '0h 0m';
  }

  const checkIn = dayjs(session.check_in_time);
  const checkOut = dayjs(session.check_out_time);
  const diffMinutes = checkOut.diff(checkIn, 'minute');

  const hours = Math.floor(diffMinutes / 60);
  const minutes = diffMinutes % 60;

  return `${hours}h ${minutes}m`;
};

const refreshPage = () => {
  router.reload();
};
</script>
