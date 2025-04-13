<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import AvatarUpload from '@/components/AvatarUpload.vue';
import AppLayout from '@/layouts/MainAppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type SharedData, type User } from '@/types';
import { toast } from 'vue-sonner';
import { ref } from 'vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs([
    { label: user.isAdmin ? 'Dashboard' : 'Home', href: user.isAdmin ? route('admin.dashboard') : route('dashboard') },
    { label: 'Password Reset' },
]);

// Extended form with additional fields
const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null as File | null,
    bio: user.bio || '',
    phone: user.phone || '',
    job_title: user.job_title || '',
    department: user.department || '',
    location: user.location || '',
    timezone: user.timezone || 'UTC',
    date_format: user.date_format || 'YYYY-MM-DD',
    time_format: user.time_format || '24',
    language: user.language || 'en',
    notifications_enabled: user.notifications_enabled ?? true,
    weekly_digest: user.weekly_digest ?? false,
});

const timezones = [
    { value: 'UTC', label: 'UTC' },
    { value: 'America/New_York', label: 'Eastern Time (ET)' },
    { value: 'America/Chicago', label: 'Central Time (CT)' },
    { value: 'America/Denver', label: 'Mountain Time (MT)' },
    { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
    // Add more timezones as needed
];

const languages = [
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Español' },
    { value: 'fr', label: 'Français' },
    { value: 'de', label: 'Deutsch' },
];

const dateFormats = [
    { value: 'YYYY-MM-DD', label: '2025-04-13' },
    { value: 'MM/DD/YYYY', label: '04/13/2025' },
    { value: 'DD/MM/YYYY', label: '13/04/2025' },
    { value: 'DD.MM.YYYY', label: '13.04.2025' },
];

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile updated successfully');
        },
    });
};
</script>

<template>
    <AppLayout>

        <Head title="Profile settings" />

        <SettingsLayout>

            <HeadingSmall 
                title="Update profile"
                description="Ensure your profile information" 
            />

            <div class="space-y-8">
                <!-- Avatar Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Profile Picture</CardTitle>
                        <CardDescription>
                            Your profile picture will be shown on your profile and in comments
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-6">
                            <AvatarUpload :user="user" size="lg" @update:avatar="form.avatar = $event" />
                            <div class="flex flex-col gap-1">
                                <h4 class="text-sm font-medium">Upload a new photo</h4>
                                <p class="text-sm text-muted-foreground">
                                    JPG, GIF or PNG. Max size of 2MB.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>
                            Your personal information that will be displayed on your profile
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Name and Email -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="name">Full Name</Label>
                                    <Input id="name" v-model="form.name" required autocomplete="name" />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email">Email Address</Label>
                                    <Input id="email" type="email" v-model="form.email" required autocomplete="email" />
                                    <InputError :message="form.errors.email" />
                                </div>
                            </div>

                            <!-- Bio -->
                            <div class="grid gap-2">
                                <Label for="bio">Bio</Label>
                                <Textarea id="bio" v-model="form.bio" placeholder="Write a short bio about yourself..."
                                    rows="4" />
                                <InputError :message="form.errors.bio" />
                            </div>

                            <!-- Work Information -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="job_title">Job Title</Label>
                                    <Input id="job_title" v-model="form.job_title"
                                        placeholder="e.g. Senior Developer" />
                                    <InputError :message="form.errors.job_title" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="department">Department</Label>
                                    <Input id="department" v-model="form.department" placeholder="e.g. Engineering" />
                                    <InputError :message="form.errors.department" />
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="phone">Phone Number</Label>
                                    <Input id="phone" type="tel" v-model="form.phone" placeholder="+1 (555) 000-0000" />
                                    <InputError :message="form.errors.phone" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="location">Location</Label>
                                    <Input id="location" v-model="form.location" placeholder="e.g. New York, USA" />
                                    <InputError :message="form.errors.location" />
                                </div>
                            </div>

                            <Separator />

                            <!-- Preferences -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-medium">Preferences</h4>

                                <!-- Language and Timezone -->
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="language">Language</Label>
                                        <Select v-model="form.language">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select language" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="lang in languages" :key="lang.value"
                                                    :value="lang.value">
                                                    {{ lang.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.language" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="timezone">Timezone</Label>
                                        <Select v-model="form.timezone">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select timezone" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="tz in timezones" :key="tz.value" :value="tz.value">
                                                    {{ tz.label }}
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
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select date format" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="format in dateFormats" :key="format.value"
                                                    :value="format.value">
                                                    {{ format.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.date_format" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label>Time Format</Label>
                                        <RadioGroup v-model="form.time_format" class="flex gap-4">
                                            <div class="flex items-center space-x-2">
                                                <RadioGroupItem value="12" id="12h" />
                                                <Label for="12h">12-hour</Label>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <RadioGroupItem value="24" id="24h" />
                                                <Label for="24h">24-hour</Label>
                                            </div>
                                        </RadioGroup>
                                        <InputError :message="form.errors.time_format" />
                                    </div>
                                </div>

                                <!-- Notification Preferences -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-medium">Notifications</h4>
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label>Enable Notifications</Label>
                                            <p class="text-sm text-muted-foreground">
                                                Receive notifications about important updates
                                            </p>
                                        </div>
                                        <Switch v-model="form.notifications_enabled"
                                            :aria-label="'Enable notifications'" />
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label>Weekly Digest</Label>
                                            <p class="text-sm text-muted-foreground">
                                                Get a weekly summary of your activities
                                            </p>
                                        </div>
                                        <Switch v-model="form.weekly_digest" :aria-label="'Enable weekly digest'" />
                                    </div>
                                </div>
                            </div>

                            <!-- Email Verification Notice -->
                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="space-y-2">
                                <p class="text-sm text-amber-600 dark:text-amber-400">
                                    Your email address is unverified.
                                    <Link :href="route('verification.send')" method="post" as="button"
                                        class="font-medium underline hover:text-amber-700 dark:hover:text-amber-300">
                                    Click here to resend the verification email.
                                    </Link>
                                </p>

                                <p v-if="status === 'verification-link-sent'"
                                    class="text-sm font-medium text-green-600 dark:text-green-400">
                                    A new verification link has been sent to your email address.
                                </p>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center gap-4">
                                <Button type="submit" :disabled="form.processing">
                                    Save Changes
                                </Button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Delete Account Section -->
                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>