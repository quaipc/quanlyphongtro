<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguoiO extends Model
{
    protected $table = 'nguoi_o';
    public $timestamps = false;
    protected $guarded = [];

    public function hopDong()
    {
        return $this->belongsTo(HopDong::class);
    }
}
