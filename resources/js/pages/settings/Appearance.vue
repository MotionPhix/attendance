<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/MainAppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

const user = usePage().props.auth.user;

const { setPageBreadcrumbs } = useBreadcrumbs();

setPageBreadcrumbs([
  { label: user.isAdmin ? 'Dashboard' : 'Home', href: user.isAdmin ? route('admin.dashboard') : route('dashboard') },
  { label: 'Appearance Settings' },
]);

</script>

<template>
    <AppLayout>

        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
