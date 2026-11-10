<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietHoaDon extends Model
{
    protected $table = 'chi_tiet_hoa_don';
    public $timestamps = false;
    protected $guarded = [];

    public function hoaDon()
    {
        return $this->belongsTo(HoaDon::class);
    }

    public function dichVu()
    {
        return $this->belongsTo(DichVu::class);
    }

    public function suCo()
    {
        return $this->belongsTo(SuCo::class);
    }
}
