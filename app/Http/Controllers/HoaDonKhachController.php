<?php

namespace App\Http\Controllers;

use App\Models\HoaDon;
use App\Models\ThanhToan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoaDonKhachController extends Controller
{
    public function index(Request $request)
    {
        $hoaDon = HoaDon::with('hopDong.phong')
            ->whereHas('hopDong', fn ($q) => $q->where('khach_id', $request->user()->id))
            ->when($request->thang, fn ($q, $v) => $q->where('thang', $v))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('khach.hoa-don.index', compact('hoaDon'));
    }

    public function show(Request $request, HoaDon $hoaDon)
    {
        $hoaDon->load(['hopDong.phong', 'chiTiet', 'thanhToan']);
        abort_unless($hoaDon->hopDong->khach_id === $request->user()->id, 403);

        return view('khach.hoa-don.show', compact('hoaDon'));
    }

    // Khách chuyển khoản rồi bấm "Tôi đã chuyển" và tải biên lai: tạo giao dịch chờ xác nhận
    public function thanhToan(Request $request, HoaDon $hoaDon): RedirectResponse
    {
        $hoaDon->load('hopDong.phong');
        abort_unless($hoaDon->hopDong->khach_id === $request->user()->id, 403);

        $request->validate([
            'anh_bien_lai' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [], ['anh_bien_lai' => 'ảnh biên lai']);

        try {
            DB::transaction(function () use ($request, $hoaDon) {
                $hd = HoaDon::lockForUpdate()->findOrFail($hoaDon->id);

                if (!$hd->choPhepThanhToan()) {
                    throw new \RuntimeException('Hóa đơn này không còn ở trạng thái cần thanh toán.');
                }
                $conLai = $hd->conLai();
                if ($conLai <= 0) {
                    throw new \RuntimeException('Hóa đơn này đã được thanh toán đủ.');
                }
                $dangCho = ThanhToan::where('hoa_don_id', $hd->id)->where('loai', 'hoa_don')->where('trang_thai', 'cho_xac_nhan')->exists();
                if ($dangCho) {
                    throw new \RuntimeException('Bạn đã gửi biên lai và đang chờ chủ trọ xác nhận.');
                }

                ThanhToan::create([
                    'hoa_don_id'   => $hd->id,
                    'loai'         => 'hoa_don',
                    'so_tien'      => $conLai,
                    'hinh_thuc'    => 'chuyen_khoan',
                    'noi_dung_ck'  => 'TT ' . $hoaDon->hopDong->phong->ma_phong . ' HD' . $hd->id,
                    'anh_bien_lai' => $request->file('anh_bien_lai')->store('bien-lai', 'local'),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('loi', $e->getMessage());
        }

        return back()->with('status', 'Đã gửi biên lai. Vui lòng chờ chủ trọ xác nhận.');
    }
}
