<?php

namespace App\Http\Controllers;

use App\Models\LichXem;
use App\Models\Phong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LichXemKhachController extends Controller
{
    public function index(Request $request)
    {
        $lichXem = LichXem::with('phong')
            ->where('khach_id', $request->user()->id)
            ->orderByDesc('thoi_gian')
            ->paginate(10);

        return view('khach.lich-xem', compact('lichXem'));
    }

    public function store(Request $request, Phong $phong): RedirectResponse
    {
        if ($phong->trang_thai !== 'trong') {
            return redirect()->route('trang-chu')->with('loi', 'Phòng này hiện không còn trống.');
        }

        $data = $request->validate([
            'thoi_gian' => ['required', 'date', 'after:now', 'before:+30 days'],
        ], [
            'thoi_gian.after'  => 'Thời gian xem phòng phải ở tương lai.',
            'thoi_gian.before' => 'Chỉ đặt lịch xem trong vòng 30 ngày tới.',
        ], ['thoi_gian' => 'thời gian xem phòng']);

        $daCo = LichXem::where('phong_id', $phong->id)
            ->where('khach_id', $request->user()->id)
            ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan'])
            ->exists();

        if ($daCo) {
            return back()->with('loi', 'Bạn đã có lịch xem phòng này đang chờ. Hãy hủy lịch cũ trước nếu muốn đổi giờ.');
        }

        // Hai lịch của cùng một phòng cách nhau dưới 1 giờ thì coi là trùng giờ
        $gio = Carbon::parse($data['thoi_gian']);
        $trung = LichXem::where('phong_id', $phong->id)
            ->whereIn('trang_thai', ['cho_xac_nhan', 'da_xac_nhan'])
            ->whereBetween('thoi_gian', [$gio->copy()->subMinutes(59), $gio->copy()->addMinutes(59)])
            ->exists();

        if ($trung) {
            return back()->withInput()->with('loi', 'Khung giờ này đã có lịch xem khác của phòng, vui lòng chọn giờ khác.');
        }

        LichXem::create([
            'phong_id'  => $phong->id,
            'khach_id'  => $request->user()->id,
            'thoi_gian' => $data['thoi_gian'],
        ]);

        return redirect()->route('lich-xem.index')->with('status', 'Đã gửi lịch xem phòng. Vui lòng chờ chủ trọ xác nhận.');
    }

    public function huy(Request $request, LichXem $lichXem): RedirectResponse
    {
        abort_unless($lichXem->khach_id === $request->user()->id, 403);

        if (!in_array($lichXem->trang_thai, ['cho_xac_nhan', 'da_xac_nhan'], true)) {
            return back()->with('loi', 'Lịch này không thể hủy nữa.');
        }

        if ($lichXem->thoi_gian->isPast()) {
            return back()->with('loi', 'Đã quá giờ xem phòng nên không thể hủy lịch này.');
        }

        $lichXem->update(['trang_thai' => 'da_huy']);

        return back()->with('status', 'Đã hủy lịch xem phòng.');
    }
}
