<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 4 dịch vụ mẫu: điện, nước, rác, internet
        $this->call(DichVuSeeder::class);

        // Tài khoản chủ trọ để đăng nhập thử
        User::create([
            'ho_ten' => 'Chủ trọ',
            'email' => 'chutro@example.com',
            'sdt' => '0900000000',
            'mat_khau' => '12345678',
            'vai_tro' => 'chu_tro',
        ]);
    }
}
