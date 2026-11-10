<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiSoDienNuoc extends Model
{
    protected $table = 'chi_so_dien_nuoc';
    public $timestamps = false;
    protected $guarded = [];

    public function hopDong()
    {
        return $this->belongsTo(HopDong::class);
    }
}
