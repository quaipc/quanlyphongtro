<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mỗi giờ tự xử lý: hết hạn chuyển cọc, hết hạn giữ chỗ, hóa đơn quá hạn
Schedule::call(fn() => DB::statement('CALL sp_xu_ly_het_han()'))
    ->hourly()
    ->name('xu-ly-het-han');