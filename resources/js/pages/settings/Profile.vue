<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import AvatarUpload from '@/components/AvatarUpload.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  RadioGroup,
  RadioGroupItem,
} from '@/components/ui/radio-group';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { toast } from 'vue-sonner';

interface Props {
  mustVerifyEmail: boolean;
  status?: string;
  media: {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    original_url: string;
    preview_url: string;
    created_at: string;
    updated_at: string;
  } | null;
  avatar: {
    url: string;
    permissions: {
      canUpload: boolean;
      maxSize: number;
      acceptedTypes: string[];
    };
  };
  preferences: {
    date_format: string;
    time_format: string;
    timezone: string;
    language: string;
  };
  availableOptions: {
    timezones: { value: string; label: string; }[];
    languages: { value: string; label: string; }[];
    dateFormats: { value: string; label: string; }[];
    timeFormats: { value: string; label: string; }[];
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Profile settings',
    href: '/settings/profile',
  },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
  name: user.name,
  email: user.email,
  avatar: null as File | null,
  ...props.preferences,
});

const submit = () => {
  form.post(route('profile.update'), {
    preserveScroll: true,
    preserveState: true,
    forceFormData: true,
    onSuccess: () => {
      toast.success('Profile updated successfully');
    },
    onError: () => {
      toast.error('Failed to update profile');
    },
  });
};

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Profile settings" />

    <SettingsLayout>
      <div class="space-y-8">
        <!-- Avatar Section -->
        <Card>
          <CardHeader>
            <CardTitle>Profile Picture</CardTitle>
            <CardDescription>
              Your profile picture will be visible across the application
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-6">
              <AvatarUpload
                :media="props.media"
                :avatar-url="props.avatar.url"
                :max-size="props.avatar.permissions.maxSize"
                :accepted-types="props.avatar.permissions.acceptedTypes"
                @update:avatar="form.avatar = $event"
              />
              <div class="flex flex-col gap-1">
                <h4 class="text-sm font-medium">Upload a new photo</h4>
                <p class="text-sm text-muted-foreground">
                  {{ props.avatar.permissions.acceptedTypes.join(', ').toUpperCase() }} formats.
                  Max size of {{ formatFileSize(props.avatar.permissions.maxSize) }}.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Profile Information -->
        <Card>
          <CardHeader>
            <CardTitle>Profile Information</CardTitle>
            <CardDescription>
              Update your account's profile information and email address
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Name and Email -->
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                  <Label for="name">Name</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autocomplete="name"
                  />
                  <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                  <Label for="email">Email</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                  />
                  <InputError :message="form.errors.email" />
                </div>
              </div>

              <!-- Email Verification Notice -->
              <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm">
                <p class="text-amber-600">
                  Your email address is unverified.
                  <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="underline hover:text-amber-700"
                  >
                    Click here to re-send the verification email.
                  </Link>
                </p>

                <p
                  v-if="status === 'verification-link-sent'"
                  class="mt-2 text-green-600"
                >
                  A new verification link has been sent to your email address.
                </p>
              </div>

              <Separator />

              <!-- Preferences -->
              <div class="space-y-6">
                <h4 class="font-medium">Preferences</h4>

                <!-- Language and Timezone -->
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="grid gap-2">
                    <Label for="language">Language</Label>
                    <Select v-model="form.language">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select language" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="language in props.availableOptions.languages"
                          :key="language.value"
                          :value="language.value"
                        >
                          {{ language.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError :message="form.errors.language" />
                  </div>

                  <div class="grid gap-2">
                    <Label for="timezone">Timezone</Label>
                    <Select v-model="form.timezone">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select timezone" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="timezone in props.availableOptions.timezones"
                          :key="timezone.value"
                          :value="timezone.value"
                        >
                          {{ timezone.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError :message="form.errors.timezone" />
                  </div>
                </div>

                <!-- Date and Time Format -->
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="grid gap-2">
                    <Label for="date_format">Date Format</Label>
                    <Select v-model="form.date_format">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select date format" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="format in props.availableOptions.dateFormats"
                          :key="format.value"
                          :value="format.value"
                        >
                          {{ format.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError :message="form.errors.date_format" />
                  </div>

                  <div class="grid gap-2">
                    <Label>Time Format</Label>
                    <RadioGroup
                      v-model="form.time_format"
                      class="grid grid-cols-2 gap-4"
                    >
                      <div
                        v-for="format in props.availableOptions.timeFormats"
                        :key="format.value"
                        class="flex items-center space-x-2"
                      >
                        <RadioGroupItem :value="format.value" :id="format.value" />
                        <Label :for="format.value">{{ format.label }}</Label>
                      </div>
                    </RadioGroup>
                    <InputError :message="form.errors.time_format" />
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex items-center gap-4">
                <Button
                  type="submit"
                  :disabled="form.processing"
                >
                  Save changes
                </Button>

                <Transition
                  enter-active-class="transition ease-in-out"
                  enter-from-class="opacity-0"
                  leave-active-class="transition ease-in-out"
                  leave-to-class="opacity-0"
                >
                  <p
                    v-show="form.recentlySuccessful"
                    class="text-sm text-muted-foreground"
                  >
                    Saved.
                  </p>
                </Transition>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Delete Account -->
        <DeleteUser />
      </div>
    </SettingsLayout>
  </AppLayout>
</template>