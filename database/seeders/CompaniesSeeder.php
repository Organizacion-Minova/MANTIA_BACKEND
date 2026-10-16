<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::firstOrCreate(
            ['nit' => 123456789],
            [
                'name' => 'Minova',
                'address' => 'Calle 123, Ciudad, País',
                'phone' => '+1 234 567 890',
                'email' => 'matiadso@gmail.com',
            ]
        );
    }
}
