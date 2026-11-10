<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhToan extends Model
{
    protected $table = 'thanh_toan';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ngay_tt' => 'datetime',
        ];
    }

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class);
    }

    public function yeuCau()
    {
        return $this->belongsTo(YeuCauGiuCho::class, 'yeu_cau_id');
    }

    public function nguoiXacNhan()
    {
        return $this->belongsTo(User::class, 'nguoi_xac_nhan_id');
    }
}
