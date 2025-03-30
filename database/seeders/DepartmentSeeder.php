<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $departments = [
      [
        'name' => 'Human Resources',
        'description' => 'Responsible for recruiting, onboarding, training, and administering employee benefit programs.',
      ],
      [
        'name' => 'Information Technology',
        'description' => 'Responsible for maintaining and implementing technology systems and infrastructure.',
      ],
      [
        'name' => 'Finance',
        'description' => 'Responsible for financial planning, management of financial risks, and financial reporting.',
      ],
      [
        'name' => 'Marketing',
        'description' => 'Responsible for creating and implementing marketing strategies to promote products or services.',
      ],
      [
        'name' => 'Sales',
        'description' => 'Responsible for selling products or services to customers and meeting sales targets.',
      ],
      [
        'name' => 'Operations',
        'description' => 'Responsible for overseeing the production and distribution of products or services.',
      ],
      [
        'name' => 'Customer Service',
        'description' => 'Responsible for providing assistance and support to customers.',
      ],
      [
        'name' => 'Research and Development',
        'description' => 'Responsible for innovation and the development of new products or services.',
      ],
    ];

    foreach ($departments as $department) {
      Department::create($department);
    }
  }
}
