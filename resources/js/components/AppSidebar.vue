<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, Clock, DollarSign, Folder, LayoutGrid, MessageSquare, Settings, Smile, Star, Target, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const isAdmin = computed(() => usePage().props.auth.user?.isAdmin ?? false);

const adminNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/admin/dashboard',
    icon: LayoutGrid,
  },
  {
    title: 'Employees',
    href: '/admin/employees',
    icon: Users,
  },
  {
    title: 'Attendance',
    href: '/admin/attendance',
    icon: Clock,
  },
  {
    title: 'Leave Requests',
    href: '/admin/leave-requests',
    icon: Calendar,
  },
  {
    title: 'Salaries',
    href: '/admin/salaries',
    icon: DollarSign,
  },
  {
    title: 'Achievements',
    href: '/admin/achievements',
    icon: Star,
  },
  {
    title: 'Feedback',
    href: '/admin/feedback',
    icon: MessageSquare,
  },
  {
    title: 'Settings',
    href: '/admin/settings',
    icon: Settings,
  },
];

const employeeNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },
  {
    title: 'Attendance',
    href: '/attendance/daily',
    icon: Clock,
  },
  {
    title: 'Leave Requests',
    href: '/leave-requests',
    icon: Calendar,
  },
  {
    title: 'Salary',
    href: '/salary',
    icon: DollarSign,
  },
  {
    title: 'Goals',
    href: '/goals',
    icon: Target,
  },
  {
    title: 'Feedback',
    href: '/feedback',
    icon: MessageSquare,
  },
  {
    title: 'Mood Tracker',
    href: '/mood-logs',
    icon: Smile,
  },
  {
    title: 'Achievements',
    href: '/achievements',
    icon: Star,
  },
];

const mainNavItems = isAdmin.value ? adminNavItems : employeeNavItems;

const footerNavItems: NavItem[] = [
  {
    title: 'Reports',
    href: isAdmin.value ? route('admin.reports.attendance.daily') : route('reports.attendance.daily'),
    icon: Folder,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link 
              :href="isAdmin ? '/admin/dashboard' : '/dashboard'"
              class="font-bold text-xl text-muted-">
              {{ $page.props.name }}
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
