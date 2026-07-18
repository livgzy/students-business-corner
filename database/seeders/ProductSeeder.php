<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            Product::factory(2)->create([
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
