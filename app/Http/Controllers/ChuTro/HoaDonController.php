<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\ChiSoDienNuoc;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\ThanhToan;
use App\Services\HoaDonService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoaDonController extends Controller
{
    public function index(Request $request)
    {
        $hoaDon = HoaDon::with(['hopDong.phong', 'hopDong.khach'])
            ->when($request->trang_thai, fn ($q, $v) => $q->where('trang_thai', $v))
            ->orderByRaw("FIELD(trang_thai, 'qua_han', 'chua_thanh_toan', 'da_thanh_toan', 'da_chuyen_no')")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('chu-tro.hoa-don.index', compact('hoaDon'));
    }

    public function show(HoaDon $hoaDon)
    {
        $hoaDon->load(['hopDong.phong', 'hopDong.khach', 'chiTiet', 'thanhToan']);

        return view('chu-tro.hoa-don.show', compact('hoaDon'));
    }

    // ---------- Ghi chỉ số cuối chu kỳ và lập hóa đơn định kỳ (UC19) ----------

    public function create(HopDong $hopDong, HoaDonService $dichVu)
    {
        [$k, $loi] = $dichVu->kiemTraKyTiepTheo($hopDong, $dichVu->homNay());
        if ($loi) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $loi);
        }

        $truoc = $dichVu->chiSoTruoc($hopDong);

        try {
            // Tính thử với chỉ số bằng kỳ trước (tiêu thụ 0) để lấy tiền phòng và nợ cũ, đồng thời bắt lỗi sớm
            $xem = $dichVu->tinh($hopDong, $k, (int) $truoc->chi_so_dien, (int) $truoc->chi_so_nuoc);
        } catch (\RuntimeException $e) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $e->getMessage());
        }

        $hopDong->load(['phong', 'khach']);
        [$tuKyVuaHet, $denKyVuaHet] = $hopDong->chuKyThu($k - 1);

        return view('chu-tro.hoa-don.create', compact('hopDong', 'truoc', 'xem', 'tuKyVuaHet', 'denKyVuaHet'));
    }

    public function store(Request $request, HopDong $hopDong, HoaDonService $dichVu): RedirectResponse
    {
        $data = $request->validate([
            'chi_so_dien' => ['required', 'integer', 'min:0', 'max:99999999'],
            'chi_so_nuoc' => ['required', 'integer', 'min:0', 'max:99999999'],
        ], [], ['chi_so_dien' => 'chỉ số điện', 'chi_so_nuoc' => 'chỉ số nước']);

        try {
            $hoaDon = DB::transaction(function () use ($hopDong, $data, $dichVu) {
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);
                $homNay = $dichVu->homNay();

                [$k, $loi] = $dichVu->kiemTraKyTiepTheo($hd, $homNay);
                if ($loi) {
                    throw new \RuntimeException($loi);
                }

                $kq = $dichVu->tinh($hd, $k, (int) $data['chi_so_dien'], (int) $data['chi_so_nuoc']);

                ChiSoDienNuoc::create([
                    'hop_dong_id' => $hd->id,
                    'thang'       => $kq['thang'],
                    'loai'        => 'hang_thang',
                    'chi_so_dien' => $data['chi_so_dien'],
                    'chi_so_nuoc' => $data['chi_so_nuoc'],
                ]);

                $hoaDon = HoaDon::create([
                    'hop_dong_id'    => $hd->id,
                    'thang'          => $kq['thang'],
                    'loai'           => 'dinh_ky',
                    'tu_ngay'        => $kq['tu']->toDateString(),
                    'den_ngay'       => $kq['den']->toDateString(),
                    'no_cu'          => $kq['no_cu'],
                    'tong_tien'      => $kq['tong'],
                    'han_thanh_toan' => $homNay->copy()->addDays((int) config('phongtro.han_thanh_toan_ngay', 5))->toDateString(),
                    'trang_thai'     => 'chua_thanh_toan',
                ]);

                foreach ($kq['dong'] as $d) {
                    ChiTietHoaDon::create($d + ['hoa_don_id' => $hoaDon->id]);
                }

                if ($kq['hoa_don_cu_ids']) {
                    HoaDon::whereIn('id', $kq['hoa_don_cu_ids'])->update(['trang_thai' => 'da_chuyen_no']);
                }

                return $hoaDon;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            return back()->withInput()->with('loi', 'Hóa đơn hoặc chỉ số cho chu kỳ này đã tồn tại.');
        }

        return redirect()->route('chutro.hoa-don.show', $hoaDon)
            ->with('status', 'Đã lập hóa đơn định kỳ.');
    }

    // ---------- Xác nhận thu tiền (UC20) ----------

    // Xác nhận biên lai khách gửi, với số tiền thực nhận
    public function xacNhanThanhToan(Request $request, ThanhToan $thanhToan): RedirectResponse
    {
        $data = $request->validate([
            'so_tien' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ], [], ['so_tien' => 'số tiền đã nhận']);

        try {
            DB::transaction(function () use ($thanhToan, $data) {
                $tt = ThanhToan::lockForUpdate()->findOrFail($thanhToan->id);
                $hoaDon = HoaDon::lockForUpdate()->findOrFail($tt->hoa_don_id);

                if ($tt->loai !== 'hoa_don' || $tt->trang_thai !== 'cho_xac_nhan') {
                    throw new \RuntimeException('Biên lai này không còn ở trạng thái chờ xác nhận.');
                }
                if (!$hoaDon->choPhepThanhToan()) {
                    throw new \RuntimeException('Hóa đơn này không còn ở trạng thái cần thanh toán.');
                }
                if ((int) $data['so_tien'] > $hoaDon->conLai()) {
                    throw new \RuntimeException('Số tiền nhận vượt quá số còn phải trả (' . number_format($hoaDon->conLai(), 0, ',', '.') . ' đ).');
                }

                $tt->update([
                    'so_tien'           => $data['so_tien'],
                    'trang_thai'        => 'da_xac_nhan',
                    'nguoi_xac_nhan_id' => auth()->id(),
                ]);

                $this->capNhatTrangThaiHoaDon($hoaDon);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return back()->with('status', 'Đã xác nhận khoản thanh toán.');
    }

    public function tuChoiThanhToan(ThanhToan $thanhToan): RedirectResponse
    {
        if ($thanhToan->loai !== 'hoa_don' || $thanhToan->trang_thai !== 'cho_xac_nhan') {
            return back()->with('loi', 'Biên lai này không còn ở trạng thái chờ xác nhận.');
        }

        $thanhToan->update([
            'trang_thai'        => 'tu_choi',
            'nguoi_xac_nhan_id' => auth()->id(),
        ]);

        return back()->with('status', 'Đã từ chối biên lai. Khách có thể tải biên lai khác.');
    }

    // Khách trả tiền mặt: chủ trọ nhập trực tiếp
    public function thuTienMat(Request $request, HoaDon $hoaDon): RedirectResponse
    {
        $data = $request->validate([
            'so_tien' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ], [], ['so_tien' => 'số tiền thu']);

        try {
            DB::transaction(function () use ($hoaDon, $data) {
                $hd = HoaDon::lockForUpdate()->findOrFail($hoaDon->id);

                if (!$hd->choPhepThanhToan()) {
                    throw new \RuntimeException('Hóa đơn này không còn ở trạng thái cần thanh toán.');
                }
                if ((int) $data['so_tien'] > $hd->conLai()) {
                    throw new \RuntimeException('Số tiền thu vượt quá số còn phải trả (' . number_format($hd->conLai(), 0, ',', '.') . ' đ).');
                }

                ThanhToan::create([
                    'hoa_don_id'        => $hd->id,
                    'loai'              => 'hoa_don',
                    'so_tien'           => $data['so_tien'],
                    'hinh_thuc'         => 'tien_mat',
                    'trang_thai'        => 'da_xac_nhan',
                    'nguoi_xac_nhan_id' => auth()->id(),
                ]);

                $this->capNhatTrangThaiHoaDon($hd);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return back()->with('status', 'Đã ghi nhận khoản thu tiền mặt.');
    }

    // Tổng tiền đã xác nhận đủ thì hóa đơn chuyển sang đã thanh toán
    private function capNhatTrangThaiHoaDon(HoaDon $hoaDon): void
    {
        if ($hoaDon->conLai() <= 0) {
            HoaDon::whereKey($hoaDon->id)->update(['trang_thai' => 'da_thanh_toan']);
        }
    }
}
