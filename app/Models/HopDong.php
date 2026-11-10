<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HopDong extends Model
{
    protected $table = 'hop_dong';
    public $timestamps = false;
    protected $guarded = [];

    public const NHAN = [
        'cho_ky'       => 'Chờ ký',
        'hieu_luc'     => 'Đang hiệu lực',
        'cho_thanh_ly' => 'Chờ thanh lý',
        'da_thanh_ly'  => 'Đã thanh lý',
        'da_huy'       => 'Đã hủy',
    ];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau'   => 'date',
            'ngay_ket_thuc'  => 'date',
            'ngay_ky'        => 'date',
            'ngay_bao_tra'   => 'date',
            'ngay_tra_phong' => 'date',
            'mien_phat'      => 'boolean',
        ];
    }

    public function nhan(): string
    {
        return self::NHAN[$this->trang_thai] ?? $this->trang_thai;
    }

    public function nhanChuKy(): string
    {
        return (int) $this->chu_ky_thu === 3 ? 'Mỗi quý (3 tháng)' : 'Mỗi tháng';
    }

    // Chu kỳ thu thứ $k (bắt đầu từ 0), tính từ ngày bắt đầu hợp đồng.
    // Ví dụ nhận phòng ngày 21: kỳ 1 tháng đầu là 21 đến 20 tháng sau (QT12).
    public function chuKyThu(int $k): array
    {
        $batDau = $this->ngay_bat_dau;
        $tu = $batDau->copy()->addMonthsNoOverflow($k * (int) $this->chu_ky_thu);
        $kyKeTiep = $batDau->copy()->addMonthsNoOverflow(($k + 1) * (int) $this->chu_ky_thu);

        return [$tu, $kyKeTiep->copy()->subDay()];
    }

    // Tiền phòng của chu kỳ thu thứ $k. Kỳ đủ thì bằng giá thuê nhân số tháng của kỳ;
    // kỳ cuối bị ngày kết thúc cắt giữa chừng thì tính theo ngày: tiền kỳ nhân số ngày ở chia số ngày của kỳ.
    // Trả về [từ ngày, đến ngày thực tế, số tiền, có bị cắt giữa kỳ không].
    public function tienPhongChuKy(int $k): array
    {
        [$tu, $den] = $this->chuKyThu($k);

        $tongNgay = (int) abs($tu->diffInDays($den)) + 1;
        $denThucTe = $this->ngay_ket_thuc->lt($den) ? $this->ngay_ket_thuc->copy() : $den;
        $soNgay = (int) abs($tu->diffInDays($denThucTe)) + 1;

        $tienKy = (int) $this->gia_thue * (int) $this->chu_ky_thu;
        $biCat = $soNgay < $tongNgay;
        $soTien = $biCat ? (int) round($tienKy * $soNgay / $tongNgay) : $tienKy;

        return [$tu, $denThucTe, $soTien, $biCat];
    }

    public function yeuCau()        { return $this->belongsTo(YeuCauGiuCho::class, 'yeu_cau_id'); }
    public function phong()         { return $this->belongsTo(Phong::class); }
    public function khach()         { return $this->belongsTo(User::class, 'khach_id'); }
    public function nguoiO()        { return $this->hasMany(NguoiO::class); }
    public function chiSoDienNuoc() { return $this->hasMany(ChiSoDienNuoc::class); }
    public function hoaDon()        { return $this->hasMany(HoaDon::class); }
}
