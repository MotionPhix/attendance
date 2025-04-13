import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  isActive?: boolean;
}

export interface SharedData extends PageProps {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
}

interface Media {
  id: number
  model_type: string
  model_id: number
  uuid: string
  collection_name: string
  name: string
  file_name: string
  mime_type: string
  disk: string
  conversions_disk: string
  size: number
  manipulations: any[]
  custom_properties: any[]
  generated_conversions: any[]
  responsive_images: any[]
  order_column: number
  created_at: string
  updated_at: string
  original_url: string
  preview_url: string
}

interface Department {
  id: number
  name: string
  description: string
  manager_id: number | null
  created_at: string
  updated_at: string
}

interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
  avatar_url: string
  media: Media[]
}

interface Employee {
  id: number
  user_id: number
  department_id: number
  position: string
  hire_date: string
  base_salary: string
  hourly_rate: string | null
  status: 'active' | 'inactive' | 'on_leave' | 'suspended' | 'terminated'
  created_at: string
  updated_at: string
  user: User
  department: Department
}

export type BreadcrumbItemType = BreadcrumbItem;
