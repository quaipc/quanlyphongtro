<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YeuCauGiuCho extends Model
{
    protected $table = 'yeu_cau_giu_cho';
    public $timestamps = false;
    protected $guarded = [];

    public const NHAN = [
        'cho_duyet'    => 'Chờ chủ trọ duyệt',
        'cho_coc'      => 'Chờ chuyển cọc',
        'cho_xac_nhan' => 'Chờ xác nhận cọc',
        'da_coc'       => 'Đã cọc',
        'da_ky'        => 'Đã ký hợp đồng',
        'bi_tu_choi'   => 'Bị từ chối',
        'het_han'      => 'Hết hạn',
        'da_huy'       => 'Đã hủy',
    ];

    // Các trạng thái được tính là "đang giữ chỗ / đang xử lý"
    public const DANG_XU_LY = ['cho_duyet', 'cho_coc', 'cho_xac_nhan', 'da_coc', 'da_ky'];

    protected function casts(): array
    {
        return [
            'han_chuyen_coc' => 'datetime',
            'han_giu'        => 'date',
            'created_at'     => 'datetime',
        ];
    }

    public function nhan(): string
    {
        return self::NHAN[$this->trang_thai] ?? $this->trang_thai;
    }

    // Tổng tiền cọc chủ trọ đã xác nhận nhận được
    public function tongCocDaXacNhan(): int
    {
        return (int) $this->thanhToan()
            ->where('loai', 'coc')
            ->where('trang_thai', 'da_xac_nhan')
            ->sum('so_tien');
    }

    // Số tiền cọc khách còn phải chuyển (tiền cọc trừ phần đã xác nhận)
    public function tienCocConThieu(): int
    {
        return max(0, (int) $this->tien_coc - $this->tongCocDaXacNhan());
    }

    public function phong()     { return $this->belongsTo(Phong::class); }
    public function khach()     { return $this->belongsTo(User::class, 'khach_id'); }
    public function hopDong()   { return $this->hasOne(HopDong::class, 'yeu_cau_id'); }
    public function thanhToan() { return $this->hasMany(ThanhToan::class, 'yeu_cau_id'); }
}
