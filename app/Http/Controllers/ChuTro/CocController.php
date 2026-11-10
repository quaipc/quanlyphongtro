<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\HopDong;
use App\Models\Phong;
use App\Models\YeuCauGiuCho;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CocController extends Controller
{
    public function index()
    {
        $choXuLy = YeuCauGiuCho::with(['phong', 'khach', 'thanhToan', 'hopDong'])
            ->where('xu_ly_coc', 'cho_xu_ly')
            ->orderByDesc('id')
            ->get();

        $daXuLy = YeuCauGiuCho::with(['phong', 'khach'])
            ->whereIn('xu_ly_coc', ['mat_coc', 'da_hoan'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('chu-tro.coc.index', compact('choXuLy', 'daXuLy'));
    }

    // Chủ trọ quyết định khoản cọc đang chờ xử lý: giữ lại (mất cọc) hoặc đã hoàn cho khách.
    // Việc chuyển khoản hoàn tiền do chủ trọ làm thủ công, hệ thống chỉ ghi nhận kết quả.
    public function xuLy(Request $request, YeuCauGiuCho $yeuCau): RedirectResponse
    {
        $quyetDinh = $request->validate([
            'quyet_dinh' => ['required', 'in:mat_coc,da_hoan'],
        ])['quyet_dinh'];

        try {
            DB::transaction(function () use ($yeuCau, $quyetDinh) {
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($yeuCau->id);

                if ($y->xu_ly_coc !== 'cho_xu_ly') {
                    throw new \RuntimeException('Khoản cọc này không còn ở trạng thái chờ xử lý.');
                }

                YeuCauGiuCho::whereKey($y->id)->update(['xu_ly_coc' => $quyetDinh]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return back()->with('status', $quyetDinh === 'mat_coc'
            ? 'Đã ghi nhận: giữ lại khoản cọc (khách mất cọc).'
            : 'Đã ghi nhận: hoàn cọc cho khách.');
    }

    // Chủ trọ không thể thực hiện hợp đồng (chưa lập được hoặc đã ký nhưng chưa bàn giao): hủy và hoàn cọc cho khách.
    // Khoản cọc chuyển sang "chờ xử lý"; chủ trọ chuyển khoản hoàn rồi bấm "Đã hoàn cọc".
    public function huyHoanCoc(YeuCauGiuCho $yeuCau): RedirectResponse
    {
        try {
            DB::transaction(function () use ($yeuCau) {
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($yeuCau->id);
                $phong = Phong::lockForUpdate()->findOrFail($y->phong_id);

                if (!in_array($y->trang_thai, ['da_coc', 'da_ky'], true)) {
                    throw new \RuntimeException('Chỉ hủy và hoàn cọc với yêu cầu đã cọc hoặc đã ký hợp đồng.');
                }
                if ($phong->trang_thai !== 'da_giu_cho') {
                    throw new \RuntimeException('Khách đã nhận phòng nên không hủy theo cách này được. Hãy thanh lý hợp đồng.');
                }

                YeuCauGiuCho::whereKey($y->id)->update(['trang_thai' => 'da_huy', 'xu_ly_coc' => 'cho_xu_ly']);
                HopDong::where('yeu_cau_id', $y->id)->whereIn('trang_thai', ['cho_ky', 'hieu_luc'])->update(['trang_thai' => 'da_huy']);
                $phong->update(['trang_thai' => 'trong']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return redirect()->route('chutro.coc.index')
            ->with('status', 'Đã hủy, phòng được mở lại. Hãy chuyển khoản hoàn cọc cho khách rồi bấm "Đã hoàn cọc".');
    }
}
