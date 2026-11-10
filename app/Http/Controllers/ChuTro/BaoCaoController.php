<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\HopDong;
use App\Models\Phong;
use App\Models\ThanhToan;
use App\Models\YeuCauGiuCho;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BaoCaoController extends Controller
{
    private const TOI_DA_THANG = 24;

    public function index(Request $request)
    {
        $request->validate([
            'tu'  => ['nullable', 'date'],
            'den' => ['nullable', 'date', 'after_or_equal:tu'],
        ], [
            'den.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ], ['tu' => 'từ ngày', 'den' => 'đến ngày']);

        $homNay = now()->startOfDay();
        $den = $request->filled('den') ? Carbon::parse($request->den)->startOfDay() : $homNay->copy();
        $tu = $request->filled('tu') ? Carbon::parse($request->tu)->startOfDay() : $den->copy()->subMonthsNoOverflow(5)->startOfMonth();

        // Danh sách các tháng trong khoảng (kể cả tháng không có doanh thu)
        $dsThang = [];
        for ($d = $tu->copy()->startOfMonth(); $d->lte($den); $d->addMonthsNoOverflow(1)) {
            $dsThang[] = $d->format('Y-m');
        }
        if (count($dsThang) > self::TOI_DA_THANG) {
            return back()->withInput()->withErrors(['den' => 'Chỉ xem được tối đa ' . self::TOI_DA_THANG . ' tháng mỗi lần.']);
        }

        // ----- Doanh thu: tiền thực thu từ hóa đơn (chuyển khoản, tiền mặt, trừ cọc), theo tháng thu tiền -----
        $thu = ThanhToan::where('loai', 'hoa_don')
            ->where('trang_thai', 'da_xac_nhan')
            ->whereBetween('ngay_tt', [$tu->copy()->startOfDay(), $den->copy()->endOfDay()])
            ->selectRaw("DATE_FORMAT(ngay_tt, '%Y-%m') AS thang, hinh_thuc, SUM(so_tien) AS tong")
            ->groupBy('thang', 'hinh_thuc')
            ->get();

        $theoThang = [];
        foreach ($dsThang as $t) {
            $theoThang[$t] = ['chuyen_khoan' => 0, 'tien_mat' => 0, 'tru_coc' => 0, 'tong' => 0];
        }
        foreach ($thu as $row) {
            if (isset($theoThang[$row->thang])) {
                $theoThang[$row->thang][$row->hinh_thuc] += (int) $row->tong;
                $theoThang[$row->thang]['tong'] += (int) $row->tong;
            }
        }
        $tongDoanhThu = collect($theoThang)->sum('tong');
        $maxThang = max(1, collect($theoThang)->max('tong'));

        // ----- Công nợ (tính đến hôm nay): hóa đơn chưa trả hết, không tính hóa đơn đã chuyển nợ (tránh đếm hai lần) -----
        $hoaDonNo = HoaDon::with(['hopDong.phong', 'hopDong.khach', 'thanhToan'])
            ->whereIn('trang_thai', ['chua_thanh_toan', 'qua_han'])
            ->get()
            ->each(function ($h) {
                $da = (int) $h->thanhToan->where('loai', 'hoa_don')->where('trang_thai', 'da_xac_nhan')->sum('so_tien');
                $h->con_no = max(0, (int) $h->tong_tien - $da);
            });

        $noHoaDon = (int) $hoaDonNo->sum('con_no');
        // Nợ còn lại sau thanh lý (cọc không đủ trả) được ghi trên hợp đồng
        $noSauThanhLy = (int) HopDong::where('trang_thai', 'da_thanh_ly')->where('no_con_lai', '>', 0)->sum('no_con_lai');
        $tongCongNo = $noHoaDon + $noSauThanhLy;

        $quaHan = $hoaDonNo->where('trang_thai', 'qua_han')->sortBy('han_thanh_toan')->values();

        // ----- Phòng -----
        $demPhong = Phong::selectRaw('trang_thai, COUNT(*) AS so')->groupBy('trang_thai')->pluck('so', 'trang_thai');
        $tongPhong = (int) $demPhong->sum();
        $trong = (int) ($demPhong['trong'] ?? 0);
        $dangThue = (int) ($demPhong['dang_thue'] ?? 0) + (int) ($demPhong['cho_tra_phong'] ?? 0);
        $giuCho = (int) ($demPhong['da_giu_cho'] ?? 0);
        $baoTri = (int) ($demPhong['bao_tri'] ?? 0);
        $phongKhaDung = max(1, $tongPhong - $baoTri);
        $tyLeLapDay = $tongPhong > 0 ? round($dangThue * 100 / $phongKhaDung) : 0;

        // ----- Cọc chờ xử lý -----
        $cocCho = YeuCauGiuCho::where('xu_ly_coc', 'cho_xu_ly')->pluck('id');
        $soCocCho = $cocCho->count();
        $tienCocCho = $soCocCho > 0
            ? (int) ThanhToan::whereIn('yeu_cau_id', $cocCho)->where('loai', 'coc')->where('trang_thai', 'da_xac_nhan')->sum('so_tien')
            : 0;

        return view('chu-tro.bao-cao.index', compact(
            'tu', 'den', 'theoThang', 'tongDoanhThu', 'maxThang',
            'hoaDonNo', 'noHoaDon', 'noSauThanhLy', 'tongCongNo', 'quaHan',
            'tongPhong', 'trong', 'dangThue', 'giuCho', 'baoTri', 'tyLeLapDay',
            'soCocCho', 'tienCocCho'
        ));
    }
}
