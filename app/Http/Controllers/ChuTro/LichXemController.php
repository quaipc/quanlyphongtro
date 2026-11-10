<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\LichXem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LichXemController extends Controller
{
    // Từ trạng thái nào được chuyển sang trạng thái nào
    private const CHUYEN = [
        'cho_xac_nhan' => ['da_xac_nhan', 'tu_choi'],
        'da_xac_nhan'  => ['da_xem', 'tu_choi'],
    ];

    public function index()
    {
        $lichXem = LichXem::with(['phong', 'khach'])
            ->orderByRaw("trang_thai = 'cho_xac_nhan' DESC")
            ->orderByDesc('thoi_gian')
            ->paginate(15);

        return view('chu-tro.lich-xem', compact('lichXem'));
    }

    public function capNhat(Request $request, LichXem $lichXem): RedirectResponse
    {
        $moi = $request->validate([
            'trang_thai' => ['required', 'in:da_xac_nhan,tu_choi,da_xem'],
        ])['trang_thai'];

        if (!in_array($moi, self::CHUYEN[$lichXem->trang_thai] ?? [], true)) {
            return back()->with('loi', 'Không thể chuyển lịch này sang trạng thái đó.');
        }

        $lichXem->update(['trang_thai' => $moi]);

        return back()->with('status', 'Đã cập nhật lịch xem phòng.');
    }
}
