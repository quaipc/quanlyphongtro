<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Giữ tên lớp User để Breeze và cấu hình auth mặc định hoạt động, nhưng trỏ tới bảng nguoi_dung.
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'nguoi_dung';

    const UPDATED_AT = null;          // ERD chỉ có created_at

    protected $rememberTokenName = ''; // tắt "ghi nhớ đăng nhập" (ERD không có remember_token)

    protected $fillable = ['ho_ten', 'email', 'sdt', 'cccd', 'mat_khau', 'vai_tro'];

    protected $hidden = ['mat_khau'];

    protected function casts(): array
    {
        return [
            'mat_khau'   => 'hashed',
            'created_at' => 'datetime',
        ];
    }

    // Cột mật khẩu tên mat_khau thay vì password
    public function getAuthPasswordName(): string
    {
        return 'mat_khau';
    }

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    public function laChuTro(): bool
    {
        return $this->vai_tro === 'chu_tro';
    }

    public function hopDong()
    {
        return $this->hasMany(HopDong::class, 'khach_id');
    }

    public function yeuCauGiuCho()
    {
        return $this->hasMany(YeuCauGiuCho::class, 'khach_id');
    }

    public function lichXem()
    {
        return $this->hasMany(LichXem::class, 'khach_id');
    }

    public function suCo()
    {
        return $this->hasMany(SuCo::class, 'khach_id');
    }
}
