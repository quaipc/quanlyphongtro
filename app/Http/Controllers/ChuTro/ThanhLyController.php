<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\ChiSoDienNuoc;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\Phong;
use App\Models\ThanhToan;
use App\Services\HoaDonService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ThanhLyController extends Controller
{
    public function form(HopDong $hopDong, HoaDonService $dichVu)
    {
        $hopDong->load(['phong', 'khach']);
        $homNay = $dichVu->homNay();

        [$tra, $loi] = $this->xacDinhNgayTra($hopDong, $homNay, true);
        if ($loi) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $loi);
        }

        $truoc = $dichVu->chiSoTruoc($hopDong);
        $hetHan = $homNay->gte($hopDong->ngay_ket_thuc);
        $soNo = HoaDon::where('hop_dong_id', $hopDong->id)
            ->whereIn('trang_thai', ['chua_thanh_toan', 'qua_han'])
            ->get()
            ->sum(fn ($h) => $h->conLai());

        return view('chu-tro.hop-dong.thanh-ly', compact('hopDong', 'tra', 'truoc', 'hetHan', 'soNo'));
    }

    // Toàn bộ luồng thanh lý nằm trong MỘT giao dịch: lỗi ở bước nào thì hoàn tác cả luồng.
    // Chỉ sp_tinh_tat_toan được gọi bên trong giao dịch (nó không tự mở giao dịch); các thủ tục khác tự mở giao dịch riêng.
    public function store(Request $request, HopDong $hopDong, HoaDonService $dichVu): RedirectResponse
    {
        $data = $request->validate([
            'chi_so_dien'      => ['required', 'integer', 'min:0', 'max:99999999'],
            'chi_so_nuoc'      => ['required', 'integer', 'min:0', 'max:99999999'],
            'tien_tru_hu_hong' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ], [], [
            'chi_so_dien'      => 'chỉ số điện',
            'chi_so_nuoc'      => 'chỉ số nước',
            'tien_tru_hu_hong' => 'khoản hư hỏng',
        ]);

        $mienPhat = $request->boolean('mien_phat');
        $chuyenBaoTri = $request->boolean('chuyen_bao_tri');
        $homNay = $dichVu->homNay();

        try {
            DB::transaction(function () use ($hopDong, $data, $mienPhat, $chuyenBaoTri, $homNay, $dichVu) {
                // ----- Bước 1: ghi nhận ngày trả, chỉ số, hư hỏng và lập hóa đơn thanh lý -----
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);
                Phong::lockForUpdate()->findOrFail($hd->phong_id);
                $hd->load('phong');

                if (HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'thanh_ly')->exists()) {
                    throw new \RuntimeException('Hợp đồng này đã có hóa đơn thanh lý.');
                }
                if (!$hd->hoaDon()->where('loai', 'ban_giao')->exists()) {
                    throw new \RuntimeException('Hợp đồng chưa bàn giao phòng.');
                }

                [$tra, $loi] = $this->xacDinhNgayTra($hd, $homNay, $mienPhat);
                if ($loi) {
                    throw new \RuntimeException($loi);
                }

                // Hóa đơn cũ còn biên lai chờ xác nhận thì phải xử lý trước khi tất toán
                $cu = HoaDon::where('hop_dong_id', $hd->id)->whereIn('trang_thai', ['chua_thanh_toan', 'qua_han'])->get();
                foreach ($cu as $h) {
                    $cho = ThanhToan::where('hoa_don_id', $h->id)->where('loai', 'hoa_don')->where('trang_thai', 'cho_xac_nhan')->exists();
                    if ($cho) {
                        throw new \RuntimeException("Hóa đơn #{$h->id} còn biên lai chờ xác nhận. Hãy xử lý biên lai đó trước khi thanh lý.");
                    }
                }

                $hd->update([
                    'ngay_tra_phong'   => $tra->toDateString(),
                    'mien_phat'        => $mienPhat || $hd->mien_phat,
                    'tien_tru_hu_hong' => $data['tien_tru_hu_hong'],
                ]);
                $hd->refresh();

                $kq = $dichVu->tinhThanhLy($hd, $tra, (int) $data['chi_so_dien'], (int) $data['chi_so_nuoc']);
                $dong = $kq['dong'];
                $tong = $kq['tong_duong'];

                // Sự cố nặng: hoàn tiền phòng những ngày không ở, cấn trừ vào hóa đơn thanh lý trước (QT13)
                if ($hd->mien_phat) {
                    $hoan = $dichVu->hoanTienPhong($hd, $tra);
                    $giam = min($hoan, $tong);
                    if ($giam > 0) {
                        $dong[] = ['mo_ta' => 'Hoàn tiền phòng những ngày không ở (sự cố nặng)', 'thanh_tien' => -$giam, 'dich_vu_id' => null, 'su_co_id' => null];
                        $tong -= $giam;
                    }
                }

                ChiSoDienNuoc::create([
                    'hop_dong_id' => $hd->id,
                    'thang'       => $tra->format('Y-m'),
                    'loai'        => 'tra_phong',
                    'chi_so_dien' => $data['chi_so_dien'],
                    'chi_so_nuoc' => $data['chi_so_nuoc'],
                ]);

                $hoaDon = HoaDon::create([
                    'hop_dong_id'    => $hd->id,
                    'thang'          => $kq['thang'],
                    'loai'           => 'thanh_ly',
                    'tu_ngay'        => $kq['tu']->toDateString(),
                    'den_ngay'       => $kq['den']->toDateString(),
                    'no_cu'          => 0,
                    'tong_tien'      => $tong,
                    'han_thanh_toan' => $homNay->toDateString(),
                    'trang_thai'     => 'chua_thanh_toan',
                ]);

                foreach ($dong as $d) {
                    ChiTietHoaDon::create($d + ['hoa_don_id' => $hoaDon->id]);
                }

                // ----- Bước 2: thủ tục tất toán tính hoàn cọc, mất cọc, nợ còn lại -----
                DB::statement('CALL sp_tinh_tat_toan(?)', [$hd->id]);

                // ----- Bước 3: trừ cọc vào hóa đơn chưa trả, đóng hợp đồng, trả phòng -----
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);   // đọc lại kết quả thủ tục vừa ghi
                $phong = Phong::lockForUpdate()->findOrFail($hd->phong_id);

                // Cọc dùng trả hóa đơn chưa trả, sau khi trừ khoản hư hỏng
                $conDung = max(0, (int) $hd->tien_coc - (int) $hd->tien_tru_hu_hong);

                $mo = HoaDon::where('hop_dong_id', $hd->id)
                    ->whereIn('trang_thai', ['chua_thanh_toan', 'qua_han'])
                    ->orderBy('id')
                    ->get();

                foreach ($mo as $h) {
                    $tru = min($h->conLai(), $conDung);
                    if ($tru > 0) {
                        ThanhToan::create([
                            'hoa_don_id'        => $h->id,
                            'loai'              => 'hoa_don',
                            'so_tien'           => $tru,
                            'hinh_thuc'         => 'tru_coc',
                            'trang_thai'        => 'da_xac_nhan',
                            'nguoi_xac_nhan_id' => auth()->id(),
                        ]);
                        $conDung -= $tru;
                    }
                    // Trả đủ thì đã thanh toán; cọc không đủ thì phần nợ được ghi vào "nợ còn lại" của hợp đồng
                    HoaDon::whereKey($h->id)->update([
                        'trang_thai' => $h->conLai() <= 0 ? 'da_thanh_toan' : 'da_chuyen_no',
                    ]);
                }

                $hd->update(['trang_thai' => 'da_thanh_ly']);
                $phong->update(['trang_thai' => $chuyenBaoTri ? 'bao_tri' : 'trong']);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            $info = $e->errorInfo ?? [];
            $thongBao = (($info[0] ?? null) === '45000' && !empty($info[2]))
                ? $info[2]
                : 'Không thanh lý được, mọi thay đổi đã được hoàn tác.';

            return back()->withInput()->with('loi', $thongBao);
        }

        $hd = HopDong::findOrFail($hopDong->id);
        $thanhLy = HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'thanh_ly')->first();
        $giam = $thanhLy ? abs((int) ChiTietHoaDon::where('hoa_don_id', $thanhLy->id)->where('thanh_tien', '<', 0)->sum('thanh_tien')) : 0;
        $duTienPhong = max(0, (int) $hd->tien_hoan_tien_phong - $giam);

        $ketQua = 'Đã thanh lý hợp đồng. Hoàn cọc: ' . number_format($hd->tien_hoan_coc, 0, ',', '.') . ' đ';
        if ((int) $hd->tien_mat_coc > 0) {
            $ketQua .= ', mất cọc: ' . number_format($hd->tien_mat_coc, 0, ',', '.') . ' đ';
        }
        if ((int) $hd->no_con_lai > 0) {
            $ketQua .= ', khách còn nợ: ' . number_format($hd->no_con_lai, 0, ',', '.') . ' đ';
        }
        if ($duTienPhong > 0) {
            $ketQua .= ', hoàn thêm tiền phòng: ' . number_format($duTienPhong, 0, ',', '.') . ' đ';
        }
        $ketQua .= '. Chủ trọ chuyển khoản hoàn cho khách theo số tiền trên.';

        return redirect()->route('chutro.hop-dong.show', $hopDong)->with('status', $ketQua);
    }

    // Gia hạn: chỉ khi hợp đồng còn hiệu lực (chưa chuyển sang chờ thanh lý). Hai bên ký phụ lục, hệ thống cập nhật ngày kết thúc.
    public function giaHan(Request $request, HopDong $hopDong): RedirectResponse
    {
        if ($hopDong->trang_thai !== 'hieu_luc') {
            return back()->with('loi', 'Chỉ gia hạn được hợp đồng đang hiệu lực, không gia hạn khi đã chờ thanh lý.');
        }
        if (!$hopDong->hoaDon()->where('loai', 'ban_giao')->exists()) {
            return back()->with('loi', 'Hợp đồng chưa bàn giao phòng nên chưa gia hạn được.');
        }

        $data = $request->validate([
            'ngay_ket_thuc_moi' => ['required', 'date', 'after:' . $hopDong->ngay_ket_thuc->toDateString()],
        ], [
            'ngay_ket_thuc_moi.after' => 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.',
        ], ['ngay_ket_thuc_moi' => 'ngày kết thúc mới']);

        $hopDong->update(['ngay_ket_thuc' => $data['ngay_ket_thuc_moi']]);

        return back()->with('status', 'Đã gia hạn hợp đồng đến hết ngày ' . Carbon::parse($data['ngay_ket_thuc_moi'])->format('d/m/Y') . '.');
    }

    // Xác định ngày trả phòng dùng để thanh lý. Trả về [ngày trả, lý do không thanh lý được].
    // Thanh lý chỉ khi: khách đã báo trả, hoặc hợp đồng đã hết hạn, hoặc chủ trọ đánh dấu sự cố nặng (UC17).
    private function xacDinhNgayTra(HopDong $hd, Carbon $homNay, bool $mienPhat): array
    {
        if (!in_array($hd->phong->trang_thai, ['dang_thue', 'cho_tra_phong'], true)) {
            return [null, 'Phòng chưa được bàn giao hoặc đã trả nên không thanh lý được.'];
        }

        // Khách đã báo trả: thanh lý vào hoặc sau ngày trả dự kiến
        if ($hd->trang_thai === 'cho_thanh_ly' && $hd->ngay_tra_phong) {
            if ($homNay->lt($hd->ngay_tra_phong)) {
                return [null, 'Khách dự kiến trả phòng ngày ' . $hd->ngay_tra_phong->format('d/m/Y') . ', chưa tới ngày thanh lý.'];
            }

            return [$hd->ngay_tra_phong->copy(), null];
        }

        if ($hd->trang_thai === 'hieu_luc') {
            // Hết hạn hợp đồng bình thường: không phạt
            if ($homNay->gte($hd->ngay_ket_thuc)) {
                return [$hd->ngay_ket_thuc->copy(), null];
            }
            // Thanh lý sớm chỉ khi sự cố nặng buộc khách rời đi
            if ($mienPhat || $hd->mien_phat) {
                return [$homNay->copy(), null];
            }

            return [null, 'Hợp đồng chưa hết hạn và khách chưa báo trả. Chỉ thanh lý sớm khi có sự cố nặng buộc khách rời đi.'];
        }

        return [null, 'Hợp đồng không ở trạng thái có thể thanh lý.'];
    }
}
