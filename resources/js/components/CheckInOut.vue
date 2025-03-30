<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ClockIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import InputLabel from '@/components/InputLabel.vue';
import TextArea from '@/components/TextArea.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(duration);
dayjs.extend(relativeTime);

interface Props {
  canCheckIn: boolean;
  canCheckOut: boolean;
  activeSession: {
    id: number;
    check_in_time: string;
    late_minutes: number;
  } | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['checked-in', 'checked-out']);

// State
const currentTimeRef = ref(dayjs().format('HH:mm:ss'));
const currentDate = ref(dayjs().format('dddd, MMMM D, YYYY'));
const showCheckInModal = ref(false);
const showCheckOutModal = ref(false);
const notes = ref('');
const processing = ref(false);
const timerInterval = ref<number | null>(null);
const sessionDuration = ref('');

// Computed
const currentTime = computed(() => {
  return currentTimeRef.value;
});

// Methods
const updateTime = () => {
  currentTimeRef.value = dayjs().format('HH:mm:ss');
  currentDate.value = dayjs().format('dddd, MMMM D, YYYY');

  // Update session duration if active
  if (props.activeSession) {
    const checkInTime = dayjs(props.activeSession.check_in_time);
    const now = dayjs();
    const diff = now.diff(checkInTime, 'second');

    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    const seconds = diff % 60;

    sessionDuration.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }
};

const formatTime = (dateTimeString: string) => {
  return dayjs(dateTimeString).format('HH:mm:ss');
};

const checkInForm = useForm({
  notes: '',
});

const checkOutForm = useForm({
  notes: '',
});

const submitCheckIn = () => {
  processing.value = true;
  checkInForm.notes = notes.value;

  checkInForm.post(route('attendance.check-in'), {
    preserveScroll: true,
    onSuccess: () => {
      showCheckInModal.value = false;
      notes.value = '';
      emit('checked-in');
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const submitCheckOut = () => {
  processing.value = true;
  checkOutForm.notes = notes.value;

  checkOutForm.post(route('attendance.check-out'), {
    preserveScroll: true,
    onSuccess: () => {
      showCheckOutModal.value = false;
      notes.value = '';
      emit('checked-out');
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

// Lifecycle hooks
onMounted(() => {
  updateTime();
  timerInterval.value = window.setInterval(updateTime, 1000);
});

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value);
  }
});
</script>

<template>
  <div class="clock-in-out-container">
    <!-- Current time display -->
    <div class="text-center mb-4">
      <h2 class="text-3xl font-bold">{{ currentTime }}</h2>
      <p class="text-gray-500">{{ currentDate }}</p>
    </div>

    <!-- Status display -->
    <div v-if="activeSession" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="font-semibold text-blue-800">Current Session</h3>
          <p class="text-sm text-gray-600">Checked in at: {{ formatTime(activeSession.check_in_time) }}</p>
          <p v-if="activeSession.late_minutes > 0" class="text-sm text-amber-600">
            Late by {{ activeSession.late_minutes }} minutes
          </p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600">Duration</p>
          <p class="font-medium">{{ sessionDuration }}</p>
        </div>
      </div>
    </div>

    <!-- Clock In/Out buttons -->
    <div class="flex flex-col space-y-4">
      <button
        v-if="canCheckIn"
        @click="showCheckInModal = true"
        class="btn-clock-in"
        :disabled="processing"
      >
        <ClockIcon class="w-5 h-5 mr-2" />
        Clock In
      </button>

      <button
        v-if="canCheckOut"
        @click="showCheckOutModal = true"
        class="btn-clock-out"
        :disabled="processing"
      >
        <ArrowRightOnRectangleIcon class="w-5 h-5 mr-2" />
        Clock Out
      </button>
    </div>

    <!-- Check In Modal -->
    <Modal :show="showCheckInModal" @close="showCheckInModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Clock In</h2>
        <p class="mt-1 text-sm text-gray-600">
          You are about to clock in at {{ currentTime }}. Add any notes if needed.
        </p>

        <div class="mt-4">
          <InputLabel for="check-in-notes" value="Notes (optional)" />
          <TextArea
            id="check-in-notes"
            v-model="notes"
            class="mt-1 block w-full"
            placeholder="Any notes about your check-in..."
          />
        </div>

        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="showCheckInModal = false" class="mr-3">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="submitCheckIn" :disabled="processing">
            <span v-if="processing">Processing...</span>
            <span v-else>Confirm Check In</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Check Out Modal -->
    <Modal :show="showCheckOutModal" @close="showCheckOutModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Clock Out</h2>
        <p class="mt-1 text-sm text-gray-600">
          You are about to clock out at {{ currentTime }}. Add any notes if needed.
        </p>

        <div class="mt-4">
          <InputLabel for="check-out-notes" value="Notes (optional)" />
          <TextArea
            id="check-out-notes"
            v-model="notes"
            class="mt-1 block w-full"
            placeholder="Any notes about your check-out..."
          />
        </div>

        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="showCheckOutModal = false" class="mr-3">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="submitCheckOut" :disabled="processing">
            <span v-if="processing">Processing...</span>
            <span v-else>Confirm Check Out</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
.clock-in-out-container {
  @apply max-w-md mx-auto p-6 bg-white rounded-xl shadow-md;
}

.btn-clock-in {
  @apply flex justify-center items-center w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition duration-150 ease-in-out;
}

.btn-clock-out {
  @apply flex justify-center items-center w-full py-3 px-4 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition duration-150 ease-in-out;
}

.btn-clock-in:disabled, .btn-clock-out:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>
