<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { User, Lock, Palette } from 'lucide-vue-next';

interface SettingsNavItem extends NavItem {
  title: string;
  href: string;
  icon: any;
  description: string;
  routeName: string;
}

const sidebarNavItems: SettingsNavItem[] = [
  {
    title: 'Profile',
    href: route('profile.edit'),
    icon: User,
    routeName: 'profile.edit',
    description: 'Manage your account profile and preferences'
  },
  {
    title: 'Password',
    href: route('password.edit'),
    icon: Lock,
    routeName: 'password.edit',
    description: 'Update your password and security settings'
  },
  {
    title: 'Appearance',
    href: route('appearance'),
    icon: Palette,
    routeName: 'appearance',
    description: 'Customize your interface theme preferences'
  }
];

const page = usePage();

// Computed property for current route name
const currentRouteName = computed(() => route().current());

// Check if a route is active
const isRouteActive = (routeName: string) => {
  const current = currentRouteName.value;
  
  if (!current) return false;
  
  // Handle specific route matches
  switch (routeName) {
    case 'profile.edit':
      return current.startsWith('profile.');
    case 'password.edit':
      return current.startsWith('password.');
    case 'appearance':
      return current === 'appearance';
    default:
      return false;
  }
};

// Computed property for current section
const currentSection = computed(() => 
  sidebarNavItems.find(item => isRouteActive(item.routeName))
);
</script>

<template>
  <div class="space-y-6 p-6 pb-16">
    <Heading 
      :title="currentSection?.title || 'Settings'" 
      :description="currentSection?.description || 'Manage your account settings and preferences'"
    />

    <Separator class="my-6" />

    <div class="flex flex-col space-y-8 lg:flex-row lg:space-x-12 lg:space-y-0">
      <aside class="-mx-4 lg:w-1/5">
        <nav 
          class="flex space-x-2 lg:flex-col lg:space-x-0 lg:space-y-1"
          aria-label="Settings sections">
          <Button
            v-for="item in sidebarNavItems"
            :key="item.href"
            variant="ghost"
            :class="cn(
              'w-full justify-start',
              isRouteActive(item.routeName) && 'bg-muted font-medium'
            )"
            as-child>
            <Link 
              :href="item.href"
              :class="cn(
                'flex w-full items-center gap-3 p-2',
                isRouteActive(item.routeName) ? 'text-foreground' : 'text-muted-foreground'
              )">
              <component
                :is="item.icon"
                class="h-4 w-4"
              />
              <span>{{ item.title }}</span>
            </Link>
          </Button>
        </nav>
      </aside>

      <Separator class="my-6 lg:hidden" />

      <div class="flex-1 lg:max-w-2xl">
        <section 
          class="space-y-6"
          :aria-label="currentSection?.title">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>