<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Resource;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, ResourceSeeder::class]);
        DB::transaction(function () {
            $roles = Role::pluck('id', 'name');
            $resources = Resource::pluck('id', 'name');
            foreach (config('permissions') as $role => $names) {
                foreach ($names as $name) {
                    Permission::query()->insertOrIgnore(['role_id' => $roles[$role], 'resource_id' => $resources[$name]]);
                }
            }
        });
    }
}
