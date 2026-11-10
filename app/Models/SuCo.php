<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuCo extends Model
{
    protected $table = 'su_co';
    public $timestamps = false;
    protected $guarded = [];

    public const NHAN = [
        'moi'                => 'Mới báo',
        'dang_xu_ly'         => 'Đang xử lý',
        'cho_khach_xac_nhan' => 'Chờ khách xác nhận',
        'hoan_tat'           => 'Hoàn tất',
    ];

    protected function casts(): array
    {
        return [
            'loi_do_khach' => 'boolean',
            'ngay_bao'     => 'datetime',
        ];
    }

    public function nhan(): string
    {
        return self::NHAN[$this->trang_thai] ?? $this->trang_thai;
    }

    // Phí sự cố do khách đã được tính vào một hóa đơn chưa
    public function daTinhVaoHoaDon(): bool
    {
        return $this->chiTietHoaDon()->exists();
    }

    public function phong()         { return $this->belongsTo(Phong::class); }
    public function khach()         { return $this->belongsTo(User::class, 'khach_id'); }
    public function chiTietHoaDon() { return $this->hasMany(ChiTietHoaDon::class); }
}
