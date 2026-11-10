<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DichVu extends Model
{
    protected $table = 'dich_vu';
    public $timestamps = false;
    protected $guarded = [];

    // Điện và Nước là dịch vụ hệ thống, được nhận diện theo tên (QT22): chỉ sửa đơn giá, không đổi tên, không xóa
    public const TEN_HE_THONG = ['điện', 'nước'];

    public function laHeThong(): bool
    {
        return in_array(mb_strtolower($this->ten), self::TEN_HE_THONG, true);
    }

    public static function tenLaHeThong(string $ten): bool
    {
        return in_array(mb_strtolower(trim($ten)), self::TEN_HE_THONG, true);
    }

    public function chiTietHoaDon() { return $this->hasMany(ChiTietHoaDon::class); }
}
