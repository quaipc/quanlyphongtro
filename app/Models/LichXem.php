<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichXem extends Model
{
    protected $table = 'lich_xem';
    public $timestamps = false;
    protected $guarded = [];

    public const NHAN = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan'  => 'Đã xác nhận',
        'tu_choi'      => 'Bị từ chối',
        'da_xem'       => 'Đã xem phòng',
        'da_huy'       => 'Đã hủy',
    ];

    protected function casts(): array
    {
        return ['thoi_gian' => 'datetime'];
    }

    public function nhan(): string
    {
        return self::NHAN[$this->trang_thai] ?? $this->trang_thai;
    }

    public function phong() { return $this->belongsTo(Phong::class); }
    public function khach() { return $this->belongsTo(User::class, 'khach_id'); }
}
