<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\ThanhToan;
use App\Models\YeuCauGiuCho;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YeuCauGiuChoController extends Controller
{
    public function index()
    {
        $yeuCau = YeuCauGiuCho::with(['phong', 'khach', 'thanhToan', 'hopDong'])
            ->orderByRaw("FIELD(trang_thai, 'cho_xac_nhan', 'cho_duyet') = 0, FIELD(trang_thai, 'cho_xac_nhan', 'cho_duyet')")
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('chu-tro.yeu-cau', compact('yeuCau'));
    }

    // Duyệt: yêu cầu -> chờ chuyển cọc (24 giờ), phòng -> đã giữ chỗ, cọc = 1 tháng tiền phòng.
    // Các yêu cầu khác của cùng phòng tự động bị từ chối (QT6).
    public function duyet(YeuCauGiuCho $yeuCau): RedirectResponse
    {
        try {
            DB::transaction(function () use ($yeuCau) {
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($yeuCau->id);
                $phong = Phong::lockForUpdate()->findOrFail($y->phong_id);

                if ($y->trang_thai !== 'cho_duyet') {
                    throw new \RuntimeException('Yêu cầu này đã được xử lý rồi.');
                }
                if ($phong->trang_thai !== 'trong') {
                    throw new \RuntimeException("Phòng {$phong->ma_phong} hiện không còn trống nên không thể duyệt.");
                }

                YeuCauGiuCho::whereKey($y->id)->update([
                    'trang_thai'     => 'cho_coc',
                    'tien_coc'       => $phong->gia_thue,
                    'han_chuyen_coc' => DB::raw('DATE_ADD(NOW(), INTERVAL 24 HOUR)'),
                ]);
                $phong->update(['trang_thai' => 'da_giu_cho']);

                YeuCauGiuCho::where('phong_id', $y->phong_id)
                    ->where('id', '<>', $y->id)
                    ->where('trang_thai', 'cho_duyet')
                    ->update(['trang_thai' => 'bi_tu_choi']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            return back()->with('loi', 'Phòng này đã có yêu cầu khác đang giữ chỗ.');
        }

        return back()->with('status', 'Đã duyệt. Khách có 24 giờ để chuyển cọc; các yêu cầu khác của phòng này đã bị từ chối.');
    }

    public function tuChoi(YeuCauGiuCho $yeuCau): RedirectResponse
    {
        if ($yeuCau->trang_thai !== 'cho_duyet') {
            return back()->with('loi', 'Yêu cầu này đã được xử lý rồi.');
        }

        $yeuCau->update(['trang_thai' => 'bi_tu_choi']);

        return back()->with('status', 'Đã từ chối yêu cầu giữ chỗ.');
    }

    // Xác nhận một biên lai cọc với số tiền thực nhận (có thể thiếu so với số khách khai).
    // Đủ tiền cọc -> đã cọc, giữ 7 ngày. Chưa đủ -> quay về chờ cọc, đặt lại hạn 24 giờ (QT20).
    public function xacNhanBienLai(Request $request, ThanhToan $thanhToan): RedirectResponse
    {
        $data = $request->validate([
            'so_tien' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ], [], ['so_tien' => 'số tiền đã nhận']);

        $ketQua = '';

        try {
            DB::transaction(function () use ($thanhToan, $data, &$ketQua) {
                $tt = ThanhToan::lockForUpdate()->findOrFail($thanhToan->id);
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($tt->yeu_cau_id);

                if ($tt->loai !== 'coc' || $tt->trang_thai !== 'cho_xac_nhan' || $y->trang_thai !== 'cho_xac_nhan') {
                    throw new \RuntimeException('Biên lai này không còn ở trạng thái chờ xác nhận.');
                }

                $tt->update([
                    'so_tien'           => $data['so_tien'],
                    'trang_thai'        => 'da_xac_nhan',
                    'nguoi_xac_nhan_id' => auth()->id(),
                ]);

                if ($y->tongCocDaXacNhan() >= (int) $y->tien_coc) {
                    YeuCauGiuCho::whereKey($y->id)->update([
                        'trang_thai' => 'da_coc',
                        'han_giu'    => DB::raw('DATE_ADD(CURDATE(), INTERVAL 7 DAY)'),
                    ]);
                    $ketQua = 'du';
                    return;
                }

                $conBienLaiKhac = ThanhToan::where('yeu_cau_id', $y->id)
                    ->where('loai', 'coc')
                    ->where('trang_thai', 'cho_xac_nhan')
                    ->exists();

                if ($conBienLaiKhac) {
                    $ketQua = 'thieu_con_cho';
                } else {
                    YeuCauGiuCho::whereKey($y->id)->update([
                        'trang_thai'     => 'cho_coc',
                        'han_chuyen_coc' => DB::raw('DATE_ADD(NOW(), INTERVAL 24 HOUR)'),
                    ]);
                    $ketQua = 'thieu';
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return back()->with('status', match ($ketQua) {
            'du'    => 'Đã nhận đủ tiền cọc. Phòng được giữ 7 ngày để ký hợp đồng.',
            'thieu' => 'Đã ghi nhận khoản cọc. Cọc chưa đủ nên khách cần chuyển thêm, hạn chuyển được đặt lại 24 giờ.',
            default => 'Đã ghi nhận khoản cọc. Còn biên lai khác đang chờ xác nhận.',
        });
    }

    // Từ chối biên lai: dùng thủ tục sp_tu_choi_bien_lai (đặt lại hạn chuyển cọc 24 giờ)
    public function tuChoiBienLai(ThanhToan $thanhToan): RedirectResponse
    {
        try {
            DB::statement('CALL sp_tu_choi_bien_lai(?, ?)', [$thanhToan->id, auth()->id()]);
        } catch (QueryException $e) {
            return back()->with('loi', 'Chỉ từ chối được biên lai cọc đang chờ xác nhận.');
        }

        $conCho = YeuCauGiuCho::whereKey($thanhToan->yeu_cau_id)->where('trang_thai', 'cho_coc')->exists();

        return back()->with('status', $conCho
            ? 'Đã từ chối biên lai. Hạn chuyển cọc của khách được đặt lại 24 giờ kể từ bây giờ.'
            : 'Đã từ chối biên lai. Yêu cầu vẫn còn biên lai khác đang chờ xác nhận.');
    }
}
