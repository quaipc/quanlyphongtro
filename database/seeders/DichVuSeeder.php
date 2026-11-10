<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DichVuSeeder extends Seeder
{
    public function run(): void
    {
        // Đơn giá chỉ là ví dụ, sửa theo thực tế
        DB::table('dich_vu')->insertOrIgnore([
            ['ten' => 'Điện',     'don_gia' => 3500,   'don_vi' => 'kWh'],
            ['ten' => 'Nước',     'don_gia' => 20000,  'don_vi' => 'm3'],
            ['ten' => 'Rác',      'don_gia' => 20000,  'don_vi' => 'tháng'],
            ['ten' => 'Internet', 'don_gia' => 100000, 'don_vi' => 'tháng'],
        ]);
    }
}
