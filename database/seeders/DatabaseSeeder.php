<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\TableSeat;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@beanflow.local'],
            [
                'name' => 'Admin BeanFlow',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'kasir@beanflow.local'],
            [
                'name' => 'Kasir BeanFlow',
                'role' => 'cashier',
                'password' => Hash::make('password'),
            ]
        );

        $menus = [
            ['name' => 'Espresso', 'price' => 18000, 'stock' => 30],
            ['name' => 'Cappuccino', 'price' => 28000, 'stock' => 24],
            ['name' => 'Caramel Latte', 'price' => 32000, 'stock' => 20],
            ['name' => 'Croissant Butter', 'price' => 22000, 'stock' => 15],
        ];

        foreach ($menus as $menu) {
            MenuItem::query()->updateOrCreate(
                ['slug' => Str::slug($menu['name'])],
                [
                    'name' => $menu['name'],
                    'description' => "Menu favorit untuk {$menu['name']}.",
                    'price' => $menu['price'],
                    'stock' => $menu['stock'],
                    'is_active' => true,
                ]
            );
        }

        foreach (['A1', 'A2', 'B1', 'B2'] as $code) {
            TableSeat::query()->firstOrCreate(
                ['code' => $code],
                ['is_active' => true]
            );
        }
    }
}
