<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\HopDong;
use App\Models\SuCo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SuCoController extends Controller
{
    public function index(Request $request)
    {
        $suCo = SuCo::with(['phong', 'khach'])
            ->when($request->trang_thai, fn ($q, $v) => $q->where('trang_thai', $v))
            ->orderByRaw("FIELD(trang_thai, 'moi', 'dang_xu_ly', 'cho_khach_xac_nhan', 'hoan_tat')")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('chu-tro.su-co.index', compact('suCo'));
    }

    public function show(SuCo $suCo)
    {
        $suCo->load(['phong', 'khach']);
        $hopDong = $this->hopDongCuaSuCo($suCo);

        return view('chu-tro.su-co.show', compact('suCo', 'hopDong'));
    }

    // Tiếp nhận: mới -> đang xử lý
    public function tiepNhan(SuCo $suCo): RedirectResponse
    {
        if ($suCo->trang_thai !== 'moi') {
            return back()->with('loi', 'Sự cố này đã được tiếp nhận rồi.');
        }

        $suCo->update(['trang_thai' => 'dang_xu_ly']);

        return back()->with('status', 'Đã tiếp nhận sự cố.');
    }

    // Sau khi sửa: ghi chi phí, đánh dấu lỗi do khách hay không, chuyển sang chờ khách xác nhận
    public function baoSuaXong(Request $request, SuCo $suCo): RedirectResponse
    {
        if ($suCo->trang_thai !== 'dang_xu_ly') {
            return back()->with('loi', 'Chỉ báo sửa xong với sự cố đang xử lý.');
        }

        $data = $request->validate([
            'chi_phi' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ], [], ['chi_phi' => 'chi phí']);

        $suCo->update([
            'chi_phi'      => $data['chi_phi'],
            'loi_do_khach' => $request->boolean('loi_do_khach'),
            'trang_thai'   => 'cho_khach_xac_nhan',
        ]);

        return back()->with('status', 'Đã báo sửa xong. Chờ khách xác nhận.');
    }

    // Sự cố nặng buộc khách rời đi: đánh dấu hợp đồng được miễn phạt (QT13).
    // Sau đó chủ trọ thanh lý hợp đồng (UC17): không cần báo trước, không mất cọc, hoàn tiền phòng những ngày không ở.
    public function suCoNang(SuCo $suCo): RedirectResponse
    {
        $hopDong = $this->hopDongCuaSuCo($suCo);

        if (!$hopDong) {
            return back()->with('loi', 'Không tìm thấy hợp đồng đang hiệu lực của khách ở phòng này.');
        }
        if ($hopDong->mien_phat) {
            return back()->with('loi', 'Hợp đồng này đã được đánh dấu miễn phạt rồi.');
        }

        $hopDong->update(['mien_phat' => true]);

        return redirect()->route('chutro.hop-dong.show', $hopDong)
            ->with('status', 'Đã đánh dấu sự cố nặng: hợp đồng được miễn phạt. Hãy thanh lý hợp đồng khi khách rời đi.');
    }

    // Hợp đồng đang hiệu lực (hoặc chờ thanh lý) của đúng khách ở phòng có sự cố
    private function hopDongCuaSuCo(SuCo $suCo): ?HopDong
    {
        return HopDong::where('phong_id', $suCo->phong_id)
            ->where('khach_id', $suCo->khach_id)
            ->whereIn('trang_thai', ['hieu_luc', 'cho_thanh_ly'])
            ->latest('id')
            ->first();
    }
}
