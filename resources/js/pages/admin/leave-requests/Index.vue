<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Check, Clock, X } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

interface LeaveRequest {
  id: number
  user: {
    id: number
    name: string
    position: string
    avatar: string
  }
  leave_type: {
    id: number
    name: string
    description: string
    color: string
  }
  start_date: string
  end_date: string
  total_days: number
  reason: string
  status: 'pending' | 'approved' | 'rejected'
  submitted_at: string
  reviewed_by?: {
    id: number
    name: string
  }
  review_notes?: string
  attachments?: Array<{
    id: number
    name: string
    path: string
  }>
}

interface Props {
  requests: {
    data: LeaveRequest[]
    meta: {
      current_page: number
      from: number
      last_page: number
      per_page: number
      to: number
      total: number
    }
  }
  filters: {
    status: string | null
    date_range: {
      from: string | null
      to: string | null
    }
  }
}

const props = defineProps<Props>()

const showDialog = ref(false)
const selectedRequest = ref<LeaveRequest | null>(null)
const reviewStatus = ref<'approved' | 'rejected'>('approved')
const reviewNotes = ref('')
const processing = ref(false)

const status = ref(props.filters.status)
const dateRange = ref(props.filters.date_range)

// Filter handlers
const updateFilters = () => {
  router.get(route('admin.leave-requests.index'), {
    status: status.value,
    from: dateRange.value.from,
    to: dateRange.value.to
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

// Review handlers
const openReviewDialog = (request: LeaveRequest) => {
  selectedRequest.value = request
  reviewStatus.value = 'approved'
  reviewNotes.value = ''
  showDialog.value = true
}

const handleReview = () => {
  if (!selectedRequest.value || processing.value) return

  processing.value = true
  router.post(route('admin.leave-requests.review', selectedRequest.value.id), {
    status: reviewStatus.value,
    notes: reviewNotes.value
  }, {
    onSuccess: () => {
      showDialog.value = false
      toast({
        title: 'Success',
        description: `Leave request has been ${reviewStatus.value}`
      })
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'approved':
      return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400'
    case 'rejected':
      return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'
    default:
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400'
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Leave Requests" />

    <div class="container py-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold tracking-tight">
          Leave Requests
        </h2>

        <!-- Filters -->
        <div class="flex items-center gap-4">
          <Select v-model="status">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="Filter by status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">All Status</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="approved">Approved</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
            </SelectContent>
          </Select>

          <Calendar
            v-model="dateRange"
            mode="range"
            class="rounded-md border"
          />

          <Button
            variant="outline"
            @click="updateFilters"
          >
            Apply Filters
          </Button>
        </div>
      </div>

      <!-- Requests Grid -->
      <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="request in requests.data"
          :key="request.id"
          class="rounded-lg border bg-card p-6 text-card-foreground shadow-sm dark:border-gray-800"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <img
                :src="request.user.avatar"
                :alt="request.user.name"
                class="h-10 w-10 rounded-full"
              >
              <div>
                <h3 class="font-medium">{{ request.user.name }}</h3>
                <p class="text-sm text-muted-foreground">
                  {{ request.user.position }}
                </p>
              </div>
            </div>

            <span
              class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              :class="getStatusColor(request.status)"
            >
              {{ request.status }}
            </span>
          </div>

          <div class="mt-4 space-y-2">
            <div class="flex items-center gap-2">
              <span
                class="inline-flex h-2 w-2 rounded-full"
                :style="{ backgroundColor: request.leave_type.color }"
              />
              <span class="text-sm font-medium">{{ request.leave_type.name }}</span>
            </div>

            <div class="space-y-1 text-sm text-muted-foreground">
              <p>{{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}</p>
              <p>{{ request.total_days }} days</p>
            </div>

            <p class="text-sm">
              {{ request.reason }}
            </p>

            <div
              v-if="request.attachments?.length"
              class="flex gap-2"
            >
              <a
                v-for="attachment in request.attachments"
                :key="attachment.id"
                :href="attachment.path"
                target="_blank"
                class="text-sm text-blue-600 hover:underline dark:text-blue-400"
              >
                {{ attachment.name }}
              </a>
            </div>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <Button
              v-if="request.status === 'pending'"
              variant="outline"
              size="sm"
              class="w-full"
              @click="openReviewDialog(request)"
            >
              Review Request
            </Button>
            <Button
              v-else
              variant="ghost"
              size="sm"
              class="w-full"
              @click="openReviewDialog(request)"
            >
              View Details
            </Button>
          </div>
        </div>
      </div>

      <!-- Review Dialog -->
      <Dialog v-model:open="showDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Review Leave Request</DialogTitle>
          </DialogHeader>

          <div
            v-if="selectedRequest"
            class="space-y-4"
          >
            <div class="rounded-lg border p-4 dark:border-gray-800">
              <h4 class="font-medium">
                {{ selectedRequest.user.name }}
              </h4>
              <p class="mt-1 text-sm text-muted-foreground">
                {{ selectedRequest.leave_type.name }} Leave Request
              </p>
              <p class="mt-2 text-sm">
                {{ formatDate(selectedRequest.start_date) }} - {{ formatDate(selectedRequest.end_date) }}
                ({{ selectedRequest.total_days }} days)
              </p>
              <p class="mt-2 text-sm">
                {{ selectedRequest.reason }}
              </p>
            </div>

            <div v-if="selectedRequest.status === 'pending'">
              <div class="mb-4 flex gap-4">
                <Button
                  :variant="reviewStatus === 'approved' ? 'default' : 'outline'"
                  class="w-full"
                  @click="reviewStatus = 'approved'"
                >
                  <Check class="mr-2 h-4 w-4" />
                  Approve
                </Button>

                <Button
                  :variant="reviewStatus === 'rejected' ? 'destructive' : 'outline'"
                  class="w-full"
                  @click="reviewStatus = 'rejected'"
                >
                  <X class="mr-2 h-4 w-4" />
                  Reject
                </Button>
              </div>

              <Textarea
                v-model="reviewNotes"
                :placeholder="reviewStatus === 'approved' ? 'Add any notes (optional)...' : 'Provide a reason for rejection...'"
                rows="3"
              />
            </div>

            <div
              v-else
              class="rounded-lg border p-4 dark:border-gray-800"
            >
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">Status:</span>
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                  :class="getStatusColor(selectedRequest.status)"
                >
                  {{ selectedRequest.status }}
                </span>
              </div>

              <div v-if="selectedRequest.reviewed_by" class="mt-2">
                <p class="text-sm text-muted-foreground">
                  Reviewed by {{ selectedRequest.reviewed_by.name }}
                </p>
                <p v-if="selectedRequest.review_notes" class="mt-1 text-sm">
                  {{ selectedRequest.review_notes }}
                </p>
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showDialog = false"
            >
              {{ selectedRequest?.status === 'pending' ? 'Cancel' : 'Close' }}
            </Button>
            <Button
              v-if="selectedRequest?.status === 'pending'"
              :variant="reviewStatus === 'rejected' ? 'destructive' : 'default'"
              :disabled="processing"
              @click="handleReview"
            >
              Confirm
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
