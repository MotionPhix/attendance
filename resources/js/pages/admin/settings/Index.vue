<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainAppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { toast } from 'vue-sonner';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

interface Setting {
  id: number
  key: string
  value: string
  group: string
  type: 'text' | 'number' | 'boolean' | 'select' | 'json' | 'array'
  description: string
  options: Record<string, string> | null
  is_public: boolean
}

interface Props {
  settings: Setting[]
}

const props = defineProps<Props>()

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs([
  { label: 'Dashboard', href: route('admin.dashboard') },
  { label: 'Settings' },
]);

// Group settings by their group
const groupedSettings = computed(() => {
  const groups: Record<string, Setting[]> = {}

  props.settings.forEach(setting => {
    if (!groups[setting.group]) {
      groups[setting.group] = []
    }
    groups[setting.group].push(setting)
  })

  return groups
})

// Form state
const form = ref(
  props.settings.reduce((acc, setting) => {
    acc[setting.key] = setting.value
    return acc
  }, {} as Record<string, string>)
)

const processing = ref(false)

const handleSubmit = () => {
  processing.value = true

  router.post(route('admin.settings.update'), form.value, {
    onSuccess: () => {
      toast({
        title: 'Success',
        description: 'Settings have been updated successfully.',
      })
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

// Group titles and descriptions
const groupInfo = {
  general: {
    title: 'General Settings',
    description: 'Configure basic application settings.',
  },
  regional: {
    title: 'Regional Settings',
    description: 'Set your timezone, date, and time preferences.',
  },
  currency: {
    title: 'Currency Settings',
    description: 'Configure currency and formatting options.',
  },
  attendance: {
    title: 'Attendance Settings',
    description: 'Configure work hours and attendance rules.',
  },
  leave: {
    title: 'Leave Settings',
    description: 'Set up leave policies and allowances.',
  },
  notifications: {
    title: 'Notification Settings',
    description: 'Configure email and notification preferences.',
  },
}
</script>

<template>
  <AppLayout>
    <Head title="Settings" />

    <div class="container py-6">
      <div class="mb-6">
        <h2 class="text-2xl font-semibold tracking-tight">
          Settings
        </h2>

        <p class="text-muted-foreground">
          Configure your application settings and preferences.
        </p>
      </div>

      <form @submit.prevent="handleSubmit">
        <Tabs defaultValue="general" class="space-y-6">
          <TabsList>
            <TabsTrigger value="general">General</TabsTrigger>
            <TabsTrigger value="regional">Regional</TabsTrigger>
            <TabsTrigger value="currency">Currency</TabsTrigger>
            <TabsTrigger value="attendance">Attendance</TabsTrigger>
            <TabsTrigger value="leave">Leave</TabsTrigger>
            <TabsTrigger value="notifications">Notifications</TabsTrigger>
          </TabsList>

          <TabsContent
            v-for="(settings, group) in groupedSettings"
            :key="group"
            :value="group"
          >
            <Card>
              <CardHeader>
                <CardTitle>{{ groupInfo[group].title }}</CardTitle>
                <CardDescription>{{ groupInfo[group].description }}</CardDescription>
              </CardHeader>
              <CardContent class="space-y-6">
                <div
                  v-for="setting in settings"
                  :key="setting.id"
                  class="grid gap-2"
                >
                  <Label :for="setting.key">{{ setting.description }}</Label>

                  <!-- Text Input -->
                  <Input
                    v-if="setting.type === 'text'"
                    :id="setting.key"
                    v-model="form[setting.key]"
                    type="text"
                  />

                  <!-- Number Input -->
                  <Input
                    v-else-if="setting.type === 'number'"
                    :id="setting.key"
                    v-model="form[setting.key]"
                    type="number"
                  />

                  <!-- Boolean Switch -->
                  <Switch
                    v-else-if="setting.type === 'boolean'"
                    :id="setting.key"
                    v-model="form[setting.key]"
                    class="mt-2"
                  />

                  <!-- Select Input -->
                  <Select
                    v-else-if="setting.type === 'select'"
                    v-model="form[setting.key]"
                  >
                    <SelectTrigger>
                      <SelectValue :placeholder="`Select ${setting.description.toLowerCase()}`" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in setting.options"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

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
