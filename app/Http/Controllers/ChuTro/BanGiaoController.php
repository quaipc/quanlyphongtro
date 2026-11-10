<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\ChiSoDienNuoc;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\Phong;
use App\Models\ThanhToan;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BanGiaoController extends Controller
{
    public function form(HopDong $hopDong)
    {
        if ($loi = $this->kiemTra($hopDong)) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $loi);
        }

        $hopDong->load(['phong', 'khach']);
        $homNay = $this->homNay();

        // Xem trước tiền phòng chu kỳ đầu nếu bàn giao hôm nay
        $tam = $hopDong->replicate();
        $tam->ngay_bat_dau = $homNay;
        [$tu, $den, $soTien, $biCat] = $tam->tienPhongChuKy(0);

        return view('chu-tro.hop-dong.ban-giao', compact('hopDong', 'homNay', 'tu', 'den', 'soTien', 'biCat'));
    }

    // Bàn giao: ngày hôm nay thành ngày bắt đầu hợp đồng và chu kỳ thu đầu; ghi chỉ số điện nước đầu kỳ;
    // tạo hóa đơn bàn giao (tiền phòng chu kỳ đầu) ghi nhận đã thanh toán; phòng chuyển sang Đang thuê (QT15, QT16).
    public function store(Request $request, HopDong $hopDong): RedirectResponse
    {
        $data = $request->validate([
            'chi_so_dien' => ['required', 'integer', 'min:0', 'max:99999999'],
            'chi_so_nuoc' => ['required', 'integer', 'min:0', 'max:99999999'],
            'hinh_thuc'   => ['required', 'in:tien_mat,chuyen_khoan'],
        ], [], [
            'chi_so_dien' => 'chỉ số điện',
            'chi_so_nuoc' => 'chỉ số nước',
            'hinh_thuc'   => 'hình thức thanh toán',
        ]);

        try {
            DB::transaction(function () use ($hopDong, $data) {
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);
                $phong = Phong::lockForUpdate()->findOrFail($hd->phong_id);

                if ($loi = $this->kiemTra($hd, $phong)) {
                    throw new \RuntimeException($loi);
                }

                $homNay = $this->homNay();

                $hd->update(['ngay_bat_dau' => $homNay->toDateString()]);
                [$tu, $den, $soTien] = $hd->tienPhongChuKy(0);

                ChiSoDienNuoc::create([
                    'hop_dong_id' => $hd->id,
                    'thang'       => $homNay->format('Y-m'),
                    'loai'        => 'ban_giao',
                    'chi_so_dien' => $data['chi_so_dien'],
                    'chi_so_nuoc' => $data['chi_so_nuoc'],
                ]);

                $hoaDon = HoaDon::create([
                    'hop_dong_id'    => $hd->id,
                    'thang'          => $tu->format('Y-m'),
                    'loai'           => 'ban_giao',
                    'tu_ngay'        => $tu->toDateString(),
                    'den_ngay'       => $den->toDateString(),
                    'no_cu'          => 0,
                    'tong_tien'      => $soTien,
                    'han_thanh_toan' => $homNay->toDateString(),
                    'trang_thai'     => 'da_thanh_toan',
                ]);

                ChiTietHoaDon::create([
                    'hoa_don_id' => $hoaDon->id,
                    'mo_ta'      => 'Tiền phòng từ ' . $tu->format('d/m/Y') . ' đến ' . $den->format('d/m/Y'),
                    'thanh_tien' => $soTien,
                ]);

                if ($soTien > 0) {
                    ThanhToan::create([
                        'hoa_don_id'        => $hoaDon->id,
                        'loai'              => 'hoa_don',
                        'so_tien'           => $soTien,
                        'hinh_thuc'         => $data['hinh_thuc'],
                        'trang_thai'        => 'da_xac_nhan',
                        'nguoi_xac_nhan_id' => auth()->id(),
                    ]);
                }

                $phong->update(['trang_thai' => 'dang_thue']);
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            return back()->withInput()->with('loi', 'Không bàn giao được: hợp đồng này đã có dữ liệu bàn giao.');
        }

        return redirect()->route('chutro.hop-dong.show', $hopDong)
            ->with('status', 'Đã bàn giao phòng. Phòng chuyển sang Đang thuê và hóa đơn bàn giao được ghi nhận đã thanh toán.');
    }

    private function kiemTra(HopDong $hd, ?Phong $phong = null): ?string
    {
        $phong ??= $hd->phong;

        if ($hd->trang_thai !== 'hieu_luc') {
            return 'Chỉ bàn giao được hợp đồng đang hiệu lực (đã ký).';
        }
        if ($phong->trang_thai !== 'da_giu_cho') {
            return 'Phòng này không ở trạng thái Đã giữ chỗ nên không thể bàn giao.';
        }
        if (HoaDon::where('hop_dong_id', $hd->id)->where('loai', 'ban_giao')->exists()) {
            return 'Hợp đồng này đã được bàn giao.';
        }

        // Quá ngày bắt đầu dự kiến mà chưa nhận phòng thì hợp đồng bị hủy (QT18)
        $conHan = HopDong::whereKey($hd->id)->whereRaw('ngay_bat_dau >= CURDATE()')->exists();

        return $conHan ? null : 'Đã quá ngày bắt đầu dự kiến của hợp đồng nên không thể bàn giao.';
    }

    // Ngày hôm nay theo đồng hồ MySQL, khớp với các kiểm tra hạn bằng CURDATE()
    private function homNay(): Carbon
    {
        return Carbon::parse(DB::selectOne('SELECT CURDATE() AS d')->d)->startOfDay();
    }
}
