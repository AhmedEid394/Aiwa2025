<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PermissionType;

class PermissionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        PermissionType::create([
            'name' => 'access_location',
            'description' => 'Permission to access user location data.'
        ]);

        PermissionType::create([
            'name' => 'access_files',
            'description' => 'Permission to access user files.'
        ]);

        PermissionType::create([
            'name' => 'access_camera',
            'description' => 'Permission to access the device camera.'
        ]);

        PermissionType::create([
            'name' => 'access_contacts',
            'description' => 'Permission to access user contacts.'
        ]);

        PermissionType::create([
            'name' => 'access_calendar',
            'description' => 'Permission to access user calendar.'
        ]);

        PermissionType::create([
            'name' => 'access_settings',
            'description' => 'Permission to access user settings.'
        ]);

    }
}
