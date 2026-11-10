<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phong extends Model
{
    protected $table = 'phong';
    public $timestamps = false;
    protected $guarded = [];

    // Nhãn tiếng Việt của trạng thái phòng
    public const NHAN_TRANG_THAI = [
        'trong'         => 'Trống',
        'da_giu_cho'    => 'Đã giữ chỗ',
        'dang_thue'     => 'Đang thuê',
        'cho_tra_phong' => 'Chờ trả phòng',
        'bao_tri'       => 'Bảo trì',
    ];

    // Các trạng thái do quy trình giữ chỗ/hợp đồng điều khiển, chủ trọ không đổi tay
    public const TRANG_THAI_DO_QUY_TRINH = ['da_giu_cho', 'dang_thue', 'cho_tra_phong'];

    protected function casts(): array
    {
        return [
            'dien_tich' => 'decimal:2',
        ];
    }

    public function dangTrongQuyTrinh(): bool
    {
        return in_array($this->trang_thai, self::TRANG_THAI_DO_QUY_TRINH, true);
    }

    public function anhPhong()      { return $this->hasMany(AnhPhong::class); }
    public function lichXem()       { return $this->hasMany(LichXem::class); }
    public function yeuCauGiuCho()  { return $this->hasMany(YeuCauGiuCho::class); }
    public function hopDong()       { return $this->hasMany(HopDong::class); }
    public function suCo()          { return $this->hasMany(SuCo::class); }
}
