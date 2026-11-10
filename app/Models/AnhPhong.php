<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnhPhong extends Model
{
    protected $table = 'anh_phong';
    public $timestamps = false;
    protected $guarded = [];

    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }
}
