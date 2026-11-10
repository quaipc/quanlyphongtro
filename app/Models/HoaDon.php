<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    protected $table = 'hoa_don';
    public $timestamps = false;
    protected $guarded = [];

    public const NHAN_LOAI = [
        'ban_giao' => 'Bàn giao',
        'dinh_ky'  => 'Định kỳ',
        'thanh_ly' => 'Thanh lý',
    ];

    public const NHAN_TRANG_THAI = [
        'chua_thanh_toan' => 'Chưa thanh toán',
        'da_thanh_toan'   => 'Đã thanh toán',
        'qua_han'         => 'Quá hạn',
        'da_chuyen_no'    => 'Đã chuyển nợ',
    ];

    protected function casts(): array
    {
        return [
            'tu_ngay'        => 'date',
            'den_ngay'       => 'date',
            'han_thanh_toan' => 'date',
        ];
    }

    public function nhanLoai(): string
    {
        return self::NHAN_LOAI[$this->loai] ?? $this->loai;
    }

    public function nhan(): string
    {
        return self::NHAN_TRANG_THAI[$this->trang_thai] ?? $this->trang_thai;
    }

    // Tổng tiền các khoản thanh toán chủ trọ đã xác nhận
    public function daThanhToan(): int
    {
        return (int) $this->thanhToan()
            ->where('loai', 'hoa_don')
            ->where('trang_thai', 'da_xac_nhan')
            ->sum('so_tien');
    }

    public function conLai(): int
    {
        return max(0, (int) $this->tong_tien - $this->daThanhToan());
    }

    // Số ngày đã quá hạn (0 nếu chưa quá hạn hoặc không ở trạng thái quá hạn)
    public function soNgayQuaHan(): int
    {
        if ($this->trang_thai !== 'qua_han') {
            return 0;
        }

        return max(0, (int) $this->han_thanh_toan->diffInDays(now()->startOfDay(), false));
    }

    // QT14: quá hạn từ 4 ngày trở lên thì chủ trọ bắt đầu nhắc nợ
    public function canNhacNo(): bool
    {
        return $this->soNgayQuaHan() >= 4;
    }

    public function choPhepThanhToan(): bool
    {
        return in_array($this->trang_thai, ['chua_thanh_toan', 'qua_han'], true);
    }

    public function hopDong()  { return $this->belongsTo(HopDong::class); }
    public function chiTiet()  { return $this->hasMany(ChiTietHoaDon::class); }
    public function thanhToan(){ return $this->hasMany(ThanhToan::class); }
}
