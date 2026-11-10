<?php

namespace App\Services;

use App\Models\ChiSoDienNuoc;
use App\Models\ChiTietHoaDon;
use App\Models\DichVu;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\SuCo;
use App\Models\ThanhToan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HoaDonService
{
    // Ngày hôm nay theo đồng hồ MySQL, khớp với các kiểm tra hạn bằng CURDATE()
    public function homNay(): Carbon
    {
        return Carbon::parse(DB::selectOne('SELECT CURDATE() AS d')->d)->startOfDay();
    }

    // Kiểm tra có lập được hóa đơn định kỳ kế tiếp không.
    // Trả về [chỉ số chu kỳ sẽ thu tiền phòng, lý do không lập được hoặc null].
    // Hóa đơn bàn giao thu chu kỳ 0, nên hóa đơn định kỳ đầu tiên thu chu kỳ 1 (QT16).
    public function kiemTraKyTiepTheo(HopDong $hd, Carbon $homNay): array
    {
        if (!in_array($hd->trang_thai, ['hieu_luc', 'cho_thanh_ly'], true)) {
            return [null, 'Hợp đồng không còn hiệu lực nên không lập hóa đơn định kỳ.'];
        }
        if (!HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'ban_giao')->exists()) {
            return [null, 'Hợp đồng chưa bàn giao phòng nên chưa có chu kỳ thu.'];
        }

        $k = HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'dinh_ky')->count() + 1;
        [$tuMoi] = $hd->chuKyThu($k);
        [, $denHienTai] = $hd->chuKyThu($k - 1);

        if ($tuMoi->gt($hd->ngay_ket_thuc)) {
            return [null, 'Hợp đồng kết thúc trong chu kỳ hiện tại nên không còn chu kỳ kế tiếp để thu. Hóa đơn cuối sẽ được lập khi thanh lý.'];
        }
        // QT19: đã báo trả mà ngày trả nằm trong chu kỳ hiện tại thì chuyển thẳng sang hóa đơn thanh lý
        if ($hd->ngay_tra_phong && $hd->ngay_tra_phong->lt($tuMoi)) {
            return [null, 'Khách trả phòng trong chu kỳ hiện tại nên không lập hóa đơn định kỳ, chuyển thẳng sang hóa đơn thanh lý.'];
        }
        if ($homNay->lt($denHienTai)) {
            return [null, 'Chu kỳ hiện tại kết thúc ngày ' . $denHienTai->format('d/m/Y') . '. Chỉ lập hóa đơn từ ngày đó.'];
        }

        return [$k, null];
    }

    // Chỉ số điện nước gần nhất đã ghi của hợp đồng
    public function chiSoTruoc(HopDong $hd): ?ChiSoDienNuoc
    {
        return ChiSoDienNuoc::where('hop_dong_id', $hd->id)->orderByDesc('id')->first();
    }

    // Tính hóa đơn định kỳ cho chu kỳ $k với chỉ số điện nước mới. Báo lỗi bằng RuntimeException.
    public function tinh(HopDong $hd, int $k, int $dien, int $nuoc): array
    {
        [$tu, $den, $tienPhong] = $hd->tienPhongChuKy($k);

        $truoc = $this->chiSoTruoc($hd);
        if (!$truoc) {
            throw new \RuntimeException('Chưa có chỉ số điện nước đầu kỳ (lúc bàn giao).');
        }
        if ($dien < $truoc->chi_so_dien) {
            throw new \RuntimeException("Chỉ số điện mới nhỏ hơn chỉ số kỳ trước ({$truoc->chi_so_dien}).");
        }
        if ($nuoc < $truoc->chi_so_nuoc) {
            throw new \RuntimeException("Chỉ số nước mới nhỏ hơn chỉ số kỳ trước ({$truoc->chi_so_nuoc}).");
        }

        $dichVu = DichVu::orderBy('id')->get();
        $dvDien = $dichVu->first(fn ($d) => mb_strtolower($d->ten) === 'điện');
        $dvNuoc = $dichVu->first(fn ($d) => mb_strtolower($d->ten) === 'nước');
        if (!$dvDien || !$dvNuoc) {
            throw new \RuntimeException('Chưa thiết lập dịch vụ Điện hoặc Nước (tên phải đúng là "Điện" và "Nước").');
        }

        $dong = [];

        // Tiền phòng của chu kỳ kế tiếp (thu trước)
        $dong[] = [
            'mo_ta'      => 'Tiền phòng từ ' . $tu->format('d/m/Y') . ' đến ' . $den->format('d/m/Y'),
            'thanh_tien' => $tienPhong,
            'dich_vu_id' => null,
            'su_co_id'   => null,
        ];

        // Sau gia hạn: nếu chu kỳ trước đã lập hóa đơn theo ngày (hợp đồng từng kết thúc giữa chu kỳ) thì bù phần chênh lệch
        if ($bu = $this->chenhLechTienPhongKyTruoc($hd, $k)) {
            $dong[] = $bu;
        }

        // Điện, nước của chu kỳ vừa hết
        foreach ([[$dvDien, $dien - $truoc->chi_so_dien], [$dvNuoc, $nuoc - $truoc->chi_so_nuoc]] as [$dv, $soLuong]) {
            $dong[] = [
                'mo_ta'      => $dv->ten . ': ' . $soLuong . ' ' . $dv->don_vi . ' × ' . number_format($dv->don_gia, 0, ',', '.') . ' đ',
                'thanh_tien' => $soLuong * (int) $dv->don_gia,
                'dich_vu_id' => $dv->id,
                'su_co_id'   => null,
            ];
        }

        // Các dịch vụ còn lại (rác, internet...) tính theo tháng nhân số tháng của chu kỳ vừa hết
        $soThang = (int) $hd->chu_ky_thu;
        foreach ($dichVu as $dv) {
            if ($dv->id === $dvDien->id || $dv->id === $dvNuoc->id) {
                continue;
            }
            $dong[] = [
                'mo_ta'      => $dv->ten . ': ' . $soThang . ' tháng × ' . number_format($dv->don_gia, 0, ',', '.') . ' đ',
                'thanh_tien' => $soThang * (int) $dv->don_gia,
                'dich_vu_id' => $dv->id,
                'su_co_id'   => null,
            ];
        }

        // Phí sự cố do khách chưa tính vào hóa đơn nào
        $daTinh = ChiTietHoaDon::whereNotNull('su_co_id')->select('su_co_id');
        $suCo = SuCo::where('phong_id', $hd->phong_id)
            ->where('khach_id', $hd->khach_id)
            ->where('loi_do_khach', true)
            ->where('trang_thai', 'hoan_tat')
            ->where('chi_phi', '>', 0)
            ->whereNotIn('id', $daTinh)
            ->get();
        foreach ($suCo as $s) {
            $dong[] = [
                'mo_ta'      => 'Phí sự cố: ' . $s->tieu_de,
                'thanh_tien' => (int) $s->chi_phi,
                'dich_vu_id' => null,
                'su_co_id'   => $s->id,
            ];
        }

        // Nợ cũ: các hóa đơn chưa trả hết được cộng dồn, hóa đơn cũ chuyển sang "đã chuyển nợ" (QT10)
        $noCu = 0;
        $idCu = [];
        $hoaDonCu = HoaDon::where('hop_dong_id', $hd->id)->whereIn('trang_thai', ['chua_thanh_toan', 'qua_han'])->get();
        foreach ($hoaDonCu as $cu) {
            $choXacNhan = ThanhToan::where('hoa_don_id', $cu->id)->where('loai', 'hoa_don')->where('trang_thai', 'cho_xac_nhan')->exists();
            if ($choXacNhan) {
                throw new \RuntimeException("Hóa đơn #{$cu->id} còn biên lai chờ xác nhận. Hãy xử lý biên lai đó trước khi lập hóa đơn mới.");
            }
            $noCu += $cu->conLai();
            $idCu[] = $cu->id;
        }

        $tong = (int) collect($dong)->sum('thanh_tien') + $noCu;

        return [
            'k'              => $k,
            'thang'          => $tu->format('Y-m'),
            'tu'             => $tu,
            'den'            => $den,
            'dong'           => $dong,
            'no_cu'          => $noCu,
            'tong'           => $tong,
            'hoa_don_cu_ids' => $idCu,
            'chi_so_truoc'   => $truoc,
        ];
    }

    // Hoàn tiền phòng đã trả trước cho những ngày không ở khi sự cố nặng (QT13).
    // Dùng đúng công thức của thủ tục sp_tinh_tat_toan để hai bên cho cùng kết quả.
    public function hoanTienPhong(HopDong $hd, Carbon $tra): int
    {
        $r = DB::selectOne(
            "SELECT ROUND(? * ? * DATEDIFF(h.den_ngay, ?) / DATEDIFF(DATE_ADD(h.tu_ngay, INTERVAL ? MONTH), h.tu_ngay), 0) AS hoan
             FROM hoa_don h
             WHERE h.hop_dong_id = ? AND h.loai IN ('ban_giao', 'dinh_ky') AND ? >= h.tu_ngay AND ? < h.den_ngay
             ORDER BY h.tu_ngay DESC LIMIT 1",
            [(int) $hd->gia_thue, (int) $hd->chu_ky_thu, $tra->toDateString(), (int) $hd->chu_ky_thu, $hd->id, $tra->toDateString(), $tra->toDateString()]
        );

        return $r ? (int) $r->hoan : 0;
    }

    // Tính các dòng của hóa đơn thanh lý: điện nước và dịch vụ của chu kỳ cuối đến ngày trả (QT16, QT17, QT22).
    // Không có tiền phòng: tiền phòng chu kỳ cuối đã thu trước và không hoàn (trừ sự cố nặng).
    public function tinhThanhLy(HopDong $hd, Carbon $tra, int $dien, int $nuoc): array
    {
        $truoc = $this->chiSoTruoc($hd);
        if (!$truoc) {
            throw new \RuntimeException('Chưa có chỉ số điện nước đầu kỳ (lúc bàn giao).');
        }
        if ($dien < $truoc->chi_so_dien) {
            throw new \RuntimeException("Chỉ số điện lúc trả nhỏ hơn chỉ số kỳ trước ({$truoc->chi_so_dien}).");
        }
        if ($nuoc < $truoc->chi_so_nuoc) {
            throw new \RuntimeException("Chỉ số nước lúc trả nhỏ hơn chỉ số kỳ trước ({$truoc->chi_so_nuoc}).");
        }

        // Chu kỳ cuối = chu kỳ đã thu tiền phòng nhưng chưa tính điện nước, dịch vụ
        $m = HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'dinh_ky')->count();
        [$tu, $den] = $hd->chuKyThu($m);
        if ($tra->gt($den)) {
            throw new \RuntimeException('Còn chu kỳ chưa lập hóa đơn định kỳ (từ ' . $den->copy()->addDay()->format('d/m/Y') . '). Hãy lập hóa đơn định kỳ trước khi thanh lý.');
        }

        $dichVu = DichVu::orderBy('id')->get();
        $dvDien = $dichVu->first(fn ($d) => mb_strtolower($d->ten) === 'điện');
        $dvNuoc = $dichVu->first(fn ($d) => mb_strtolower($d->ten) === 'nước');
        if (!$dvDien || !$dvNuoc) {
            throw new \RuntimeException('Chưa thiết lập dịch vụ Điện hoặc Nước.');
        }

        $dong = [];
        foreach ([[$dvDien, $dien - $truoc->chi_so_dien], [$dvNuoc, $nuoc - $truoc->chi_so_nuoc]] as [$dv, $soLuong]) {
            $dong[] = [
                'mo_ta'      => $dv->ten . ': ' . $soLuong . ' ' . $dv->don_vi . ' × ' . number_format($dv->don_gia, 0, ',', '.') . ' đ',
                'thanh_tien' => $soLuong * (int) $dv->don_gia,
                'dich_vu_id' => $dv->id,
                'su_co_id'   => null,
            ];
        }

        // Dịch vụ cố định: đủ chu kỳ thì nhân số tháng, chu kỳ cuối ngắn hơn thì tính theo ngày đến ngày trả
        $tongNgay = (int) abs($tu->diffInDays($den)) + 1;
        $soNgay = $tra->lt($tu) ? 0 : min($tongNgay, (int) abs($tu->diffInDays($tra)) + 1);
        $soThang = (int) $hd->chu_ky_thu;
        foreach ($dichVu as $dv) {
            if ($dv->id === $dvDien->id || $dv->id === $dvNuoc->id) {
                continue;
            }
            if ($soNgay >= $tongNgay) {
                $moTa = $dv->ten . ': ' . $soThang . ' tháng × ' . number_format($dv->don_gia, 0, ',', '.') . ' đ';
                $tien = $soThang * (int) $dv->don_gia;
            } else {
                $moTa = $dv->ten . ': ' . $soNgay . '/' . $tongNgay . ' ngày × ' . number_format($dv->don_gia * $soThang, 0, ',', '.') . ' đ';
                $tien = (int) round($dv->don_gia * $soThang * $soNgay / $tongNgay);
            }
            $dong[] = ['mo_ta' => $moTa, 'thanh_tien' => $tien, 'dich_vu_id' => $dv->id, 'su_co_id' => null];
        }

        // Phí sự cố do khách chưa tính vào hóa đơn nào
        $daTinh = ChiTietHoaDon::whereNotNull('su_co_id')->select('su_co_id');
        $suCo = SuCo::where('phong_id', $hd->phong_id)
            ->where('khach_id', $hd->khach_id)
            ->where('loi_do_khach', true)
            ->where('trang_thai', 'hoan_tat')
            ->where('chi_phi', '>', 0)
            ->whereNotIn('id', $daTinh)
            ->get();
        foreach ($suCo as $s) {
            $dong[] = ['mo_ta' => 'Phí sự cố: ' . $s->tieu_de, 'thanh_tien' => (int) $s->chi_phi, 'dich_vu_id' => null, 'su_co_id' => $s->id];
        }

        return [
            'thang'      => $tra->format('Y-m'),
            'tu'         => $tu,
            'den'        => $tra->lt($tu) ? $tu->copy() : $tra->copy(),
            'dong'       => $dong,
            'tong_duong' => (int) collect($dong)->sum('thanh_tien'),
        ];
    }

    // Hợp đồng từng kết thúc giữa một chu kỳ nên hóa đơn chu kỳ đó tính theo ngày; sau khi gia hạn,
    // phần tiền phòng chênh lệch so với cả chu kỳ được tính vào hóa đơn kế tiếp.
    private function chenhLechTienPhongKyTruoc(HopDong $hd, int $k): ?array
    {
        $kTruoc = $k - 1;
        [$tuTruoc, $denDayDu] = $hd->chuKyThu($kTruoc);

        $hoaDonTruoc = $kTruoc === 0
            ? HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'ban_giao')->first()
            : HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'dinh_ky')->whereDate('tu_ngay', $tuTruoc->toDateString())->first();

        if (!$hoaDonTruoc || $hoaDonTruoc->den_ngay->gte($denDayDu)) {
            return null;
        }

        $daThu = (int) ChiTietHoaDon::where('hoa_don_id', $hoaDonTruoc->id)
            ->whereNull('dich_vu_id')
            ->whereNull('su_co_id')
            ->where('mo_ta', 'like', 'Tiền phòng từ%')
            ->sum('thanh_tien');
        $chenh = (int) $hd->gia_thue * (int) $hd->chu_ky_thu - $daThu;

        if ($chenh <= 0) {
            return null;
        }

        return [
            'mo_ta'      => 'Tiền phòng bù chu kỳ ' . $tuTruoc->format('d/m/Y') . ' – ' . $denDayDu->format('d/m/Y') . ' (sau gia hạn)',
            'thanh_tien' => $chenh,
            'dich_vu_id' => null,
            'su_co_id'   => null,
        ];
    }
}
