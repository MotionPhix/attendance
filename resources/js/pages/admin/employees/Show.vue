<script setup lang="ts" xmlns="http://www.w3.org/1999/html">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/MainAppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Edit2, UserX } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface Department {
  id: number;
  name: string;
}

interface Employee {
  id: number;
  user: {
    id: number
    name: string
    email: string
    avatar?: string
  };
  department: Department;
  position: string;
  employee_id: string;
  join_date: string;
  status: 'active' | 'inactive' | 'on_leave';
  phone?: string;
  address?: string;
  emergency_contact?: {
    name: string
    relationship: string
    phone: string
  };
}

interface Props {
  employee: Employee;
  departments: Department[];
}

const props = defineProps<Props>();

const showEditDialog = ref(false);
const showDeactivateDialog = ref(false);
const processing = ref(false);

const form = ref({
  name: props.employee.user.name,
  email: props.employee.user.email,
  department_id: props.employee.department.id.toString(),
  position: props.employee.position,
  employee_id: props.employee.employee_id,
  status: props.employee.status,
  phone: props.employee.phone || '',
  address: props.employee.address || '',
  emergency_contact: props.employee.emergency_contact || {
    name: '',
    relationship: '',
    phone: ''
  }
});

const handleUpdateEmployee = () => {
  processing.value = true;

  router.put(route('admin.employees.update', props.employee.id), form.value, {
    onSuccess: () => {
      showEditDialog.value = false;
      toast({
        title: 'Success',
        description: 'Employee details have been updated.'
      });
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const handleDeactivateEmployee = () => {
  processing.value = true;

  router.delete(route('admin.employees.destroy', props.employee.id), {
    onSuccess: () => {
      router.visit(route('admin.employees.index'));
      toast({
        title: 'Success',
        description: 'Employee has been deactivated.'
      });
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active':
      return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    case 'on_leave':
      return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
    case 'suspended':
      return 'text-orange-500 bg-orange-50 dark:bg-orange-900/20'
    case 'terminated':
      return 'text-red-500 bg-red-50 dark:bg-red-900/20'
    default:
      return 'text-gray-500 bg-gray-50 dark:bg-gray-900/20'
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="employee.user.name" />

    <div class="container py-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <img
            :src="employee.user.avatar || `/avatars/default.png`"
            :alt="employee.user.name"
            class="h-16 w-16 rounded-full">

          <div>
            <h2 class="text-2xl font-semibold tracking-tight">
              {{ employee.user.name }}
            </h2>

            <p class="text-muted-foreground">
              {{ employee.position }} at {{ employee.department.name }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            @click="showEditDialog = true">
            <Edit2 class="mr-2 h-4 w-4" />
            Edit Details
          </Button>

          <Button
            variant="destructive"
            @click="showDeactivateDialog = true">
            <UserX class="mr-2 h-4 w-4" />
            Deactivate
          </Button>
        </div>
      </div>

      <Tabs class="mt-6" default-value="overview">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="leave">Leave</TabsTrigger>
          <TabsTrigger value="salary">Salary</TabsTrigger>
        </TabsList>

        <TabsContent value="overview">
          <div class="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Basic Information</CardTitle>
              </CardHeader>
              <CardContent>
                <dl class="space-y-4">
                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Employee ID</dt>
                    <dd>{{ employee.employee_id }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Join Date</dt>
                    <dd>{{ new Date(employee.join_date).toLocaleDateString() }}</dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Status</dt>
                    <dd>
                      <span
                        class="inline-flex capitalize items-center rounded-full px-2 py-1 text-xs font-medium"
                        :class="getStatusColor(employee.status)">
                        {{ employee.status.replace('_', ' ') }}
                      </span>
                    </dd>
                  </div>

                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Email</dt>
                    <dd>{{ employee.user.email }}</dd>
                  </div>

                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Phone</dt>
                    <dd>{{ employee.phone || 'Not provided' }}</dd>
                  </div>

                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Address</dt>
                    <dd>{{ employee.address || 'Not provided' }}</dd>
                  </div>
                </dl>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Emergency Contact</CardTitle>
              </CardHeader>
              <CardContent>
                <dl class="space-y-4">
                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Name</dt>
                    <dd>{{ employee.emergency_contact?.name || 'Not provided' }}</dd>
                  </div>

                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Relationship</dt>
                    <dd>{{ employee.emergency_contact?.relationship || 'Not provided' }}</dd>
                  </div>

                  <div class="flex justify-between">
                    <dt class="font-medium text-muted-foreground">Phone</dt>
                    <dd>{{ employee.emergency_contact?.phone || 'Not provided' }}</dd>
                  </div>
                </dl>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="attendance">
          <!-- Attendance history will be implemented later -->
          <Card>
            <CardHeader>
              <CardTitle>Attendance History</CardTitle>
              <CardDescription>
                View the employee's attendance records and patterns.
              </CardDescription>
            </CardHeader>

            <CardContent>
              Coming soon...
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="leave">
          <!-- Leave history will be implemented later -->
          <Card>
            <CardHeader>
              <CardTitle>Leave History</CardTitle>
              <CardDescription>
                View the employee's leave requests and balances.
              </CardDescription>
            </CardHeader>
            <CardContent>
              Coming soon...
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="salary">
          <!-- Salary history will be implemented later -->
          <Card>
            <CardHeader>
              <CardTitle>Salary History</CardTitle>
              <CardDescription>
                View the employee's salary records and adjustments.
              </CardDescription>
            </CardHeader>
            <CardContent>
              Coming soon...
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <!-- Edit Employee Dialog -->
      <Dialog v-model:open="showEditDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Employee Details</DialogTitle>
            <DialogDescription>
              Update the employee's information. These changes will be reflected immediately.
            </DialogDescription>
          </DialogHeader>

          <div class="grid gap-4 grid-cols-2">
            <section>
              <div class="grid gap-2">
                <label for="name">Full Name</label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="John Doe"
                />
              </div>

              <div class="grid gap-2">
                <label for="email">Email Address</label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="john@example.com"
                />
              </div>

              <div class="grid gap-2">
                <label for="department">Department</label>
                <Select
                  v-model="form.department_id"
                  name="department">
                  <SelectTrigger>
                    <SelectValue placeholder="Select department" />
                  </SelectTrigger>

                  <SelectContent>
                    <SelectItem
                      v-for="department in departments"
                      :key="department.id"
                      :value="department.id.toString()">
                      {{ department.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="grid gap-2">
                <label for="position">Position</label>
                <Input
                  id="position"
                  v-model="form.position"
                  placeholder="Software Engineer"
                />
              </div>

              <div class="grid gap-2">
                <label for="status">Status</label>
                <Select
                  v-model="form.status"
                  name="status">
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>

                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                    <SelectItem value="on_leave">On Leave</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="grid gap-2">
                <label for="phone">Phone Number</label>
                <Input
                  id="phone"
                  v-model="form.phone"
                  placeholder="+1234567890"
                />
              </div>

              <div class="grid gap-2">
                <label for="address">Address</label>
                <Input
                  id="address"
                  v-model="form.address"
                  placeholder="123 Main St, City, Country"
                />
              </div>

            </section>

            <div class="space-y-4">
              <h4 class="font-medium">Emergency Contact</h4>

              <div class="grid gap-2">
                <label for="emergency_name">Contact Name</label>
                <Input
                  id="emergency_name"
                  v-model="form.emergency_contact.name"
                  placeholder="Jane Doe"
                />
              </div>

              <div class="grid gap-2">
                <label for="emergency_relationship">Relationship</label>
                <Input
                  id="emergency_relationship"
                  v-model="form.emergency_contact.relationship"
                  placeholder="Spouse"
                />
              </div>

              <div class="grid gap-2">
                <label for="emergency_phone">Contact Phone</label>
                <Input
                  id="emergency_phone"
                  v-model="form.emergency_contact.phone"
                  placeholder="+1234567890"
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showEditDialog = false">
              Cancel
            </Button>

            <Button
              :disabled="processing"
              @click="handleUpdateEmployee">
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Deactivate Employee Dialog -->
      <Dialog v-model:open="showDeactivateDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Deactivate Employee</DialogTitle>
            <DialogDescription>
              Are you sure you want to deactivate this employee? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button
              variant="outline"
              @click="showDeactivateDialog = false">
              Cancel
            </Button>

            <Button
              variant="destructive"
              :disabled="processing"
              @click="handleDeactivateEmployee">
              Deactivate
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
