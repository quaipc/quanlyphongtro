<?php

namespace App\Http\Controllers;

use App\Models\Phong;
use App\Models\SuCo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuCoKhachController extends Controller
{
    public function index(Request $request)
    {
        $suCo = SuCo::with('phong')
            ->where('khach_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10);

        $phongs = $this->phongBaoDuoc($request);

        return view('khach.su-co.index', compact('suCo', 'phongs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $phongs = $this->phongBaoDuoc($request);

        if ($phongs->isEmpty()) {
            return back()->with('loi', 'Bạn chỉ báo được sự cố khi có hợp đồng đang hiệu lực và đã nhận phòng.');
        }

        $data = $request->validate([
            'phong_id' => ['required', Rule::in($phongs->pluck('id')->all())],
            'tieu_de'  => ['required', 'string', 'max:150'],
            'mo_ta'    => ['nullable', 'string', 'max:2000'],
        ], [], ['phong_id' => 'phòng', 'tieu_de' => 'tiêu đề', 'mo_ta' => 'mô tả']);

        SuCo::create([
            'phong_id' => $data['phong_id'],
            'khach_id' => $request->user()->id,
            'tieu_de'  => $data['tieu_de'],
            'mo_ta'    => $data['mo_ta'] ?? null,
        ]);

        return back()->with('status', 'Đã gửi báo sự cố. Chủ trọ sẽ tiếp nhận và xử lý.');
    }

    // Chủ trọ báo đã sửa xong, khách xác nhận để hoàn tất
    public function xacNhan(Request $request, SuCo $suCo): RedirectResponse
    {
        abort_unless($suCo->khach_id === $request->user()->id, 403);

        if ($suCo->trang_thai !== 'cho_khach_xac_nhan') {
            return back()->with('loi', 'Sự cố này không ở trạng thái chờ bạn xác nhận.');
        }

        $suCo->update(['trang_thai' => 'hoan_tat']);

        return back()->with('status', 'Đã xác nhận sửa xong. Sự cố hoàn tất.');
    }

    // Khách chưa hài lòng: sự cố quay lại đang xử lý
    public function chuaHaiLong(Request $request, SuCo $suCo): RedirectResponse
    {
        abort_unless($suCo->khach_id === $request->user()->id, 403);

        if ($suCo->trang_thai !== 'cho_khach_xac_nhan') {
            return back()->with('loi', 'Sự cố này không ở trạng thái chờ bạn xác nhận.');
        }

        $suCo->update(['trang_thai' => 'dang_xu_ly']);

        return back()->with('status', 'Đã chuyển sự cố về trạng thái đang xử lý để chủ trọ sửa lại.');
    }

    // Các phòng khách đang thuê (hợp đồng đang hiệu lực, đã nhận phòng)
    private function phongBaoDuoc(Request $request)
    {
        return Phong::whereIn('trang_thai', ['dang_thue', 'cho_tra_phong'])
            ->whereHas('hopDong', fn ($q) => $q
                ->where('khach_id', $request->user()->id)
                ->whereIn('trang_thai', ['hieu_luc', 'cho_thanh_ly']))
            ->orderBy('ma_phong')
            ->get();
    }
}
