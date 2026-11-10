<?php

namespace App\Http\Controllers;

use App\Models\Phong;
use App\Models\ThanhToan;
use App\Models\YeuCauGiuCho;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YeuCauKhachController extends Controller
{
    public function index(Request $request)
    {
        $yeuCau = YeuCauGiuCho::with('phong')
            ->where('khach_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('khach.giu-cho', compact('yeuCau'));
    }

    public function store(Request $request, Phong $phong): RedirectResponse
    {
        if ($phong->trang_thai !== 'trong') {
            return redirect()->route('trang-chu')->with('loi', 'Phòng này hiện không còn trống.');
        }

        $daCo = YeuCauGiuCho::where('phong_id', $phong->id)
            ->where('khach_id', $request->user()->id)
            ->whereIn('trang_thai', YeuCauGiuCho::DANG_XU_LY)
            ->exists();

        if ($daCo) {
            return back()->with('loi', 'Bạn đã gửi yêu cầu giữ chỗ cho phòng này rồi.');
        }

        // Tiền cọc sẽ được chốt khi chủ trọ duyệt
        YeuCauGiuCho::create([
            'phong_id' => $phong->id,
            'khach_id' => $request->user()->id,
        ]);

        return redirect()->route('giu-cho.index')
            ->with('status', "Đã gửi yêu cầu giữ chỗ phòng {$phong->ma_phong}. Vui lòng chờ chủ trọ duyệt.");
    }

    // Dùng thủ tục sp_khach_huy_giu_cho của CSDL để đúng quy tắc mất cọc / hoàn cọc
    public function huy(Request $request, YeuCauGiuCho $yeuCau): RedirectResponse
    {
        abort_unless($yeuCau->khach_id === $request->user()->id, 403);

        try {
            DB::statement('CALL sp_khach_huy_giu_cho(?)', [$yeuCau->id]);
        } catch (QueryException $e) {
            $info = $e->errorInfo ?? [];

            return back()->with('loi', (($info[0] ?? null) === '45000' && !empty($info[2]))
                ? $info[2]
                : 'Không thể hủy yêu cầu ở trạng thái hiện tại.');
        }

        return back()->with('status', 'Đã hủy yêu cầu giữ chỗ.');
    }

    // ---------- Chuyển cọc ----------

    public function formChuyenCoc(Request $request, YeuCauGiuCho $yeuCau)
    {
        abort_unless($yeuCau->khach_id === $request->user()->id, 403);

        if ($loi = $this->kiemTraChuyenCoc($yeuCau)) {
            return redirect()->route('giu-cho.index')->with('loi', $loi);
        }

        $conThieu = $yeuCau->tienCocConThieu();
        if ($conThieu <= 0) {
            return redirect()->route('giu-cho.index')->with('loi', 'Khoản cọc đã được xác nhận đủ.');
        }

        $yeuCau->load('phong');
        $noiDung = $this->noiDungChuyenKhoan($yeuCau);

        return view('khach.chuyen-coc', compact('yeuCau', 'noiDung', 'conThieu'));
    }

    // Khách bấm "Tôi đã chuyển": gửi ảnh biên lai, yêu cầu -> chờ xác nhận cọc.
    // Hạn 24 giờ được kiểm tra bằng đồng hồ MySQL (NOW()) ngay lúc bấm, không chờ thủ tục hết hạn chạy.
    public function chuyenCoc(Request $request, YeuCauGiuCho $yeuCau): RedirectResponse
    {
        abort_unless($yeuCau->khach_id === $request->user()->id, 403);

        $request->validate([
            'anh_bien_lai' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [], ['anh_bien_lai' => 'ảnh biên lai']);

        try {
            DB::transaction(function () use ($request, $yeuCau) {
                $y = YeuCauGiuCho::whereKey($yeuCau->id)->lockForUpdate()->firstOrFail();

                if ($loi = $this->kiemTraChuyenCoc($y)) {
                    throw new \RuntimeException($loi);
                }

                $conThieu = $y->tienCocConThieu();
                if ($conThieu <= 0) {
                    throw new \RuntimeException('Khoản cọc đã được xác nhận đủ.');
                }

                ThanhToan::create([
                    'yeu_cau_id'   => $y->id,
                    'loai'         => 'coc',
                    'so_tien'      => $conThieu,
                    'hinh_thuc'    => 'chuyen_khoan',
                    'noi_dung_ck'  => $this->noiDungChuyenKhoan($y),
                    'anh_bien_lai' => $request->file('anh_bien_lai')->store('bien-lai', 'local'),
                ]);

                YeuCauGiuCho::whereKey($y->id)->update(['trang_thai' => 'cho_xac_nhan']);
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('giu-cho.index')->with('loi', $e->getMessage());
        }

        return redirect()->route('giu-cho.index')
            ->with('status', 'Đã gửi biên lai. Vui lòng chờ chủ trọ xác nhận đã nhận cọc.');
    }

    private function kiemTraChuyenCoc(YeuCauGiuCho $yeuCau): ?string
    {
        if ($yeuCau->trang_thai !== 'cho_coc') {
            return 'Yêu cầu này không ở trạng thái chờ chuyển cọc.';
        }

        $conHan = YeuCauGiuCho::whereKey($yeuCau->id)->whereRaw('han_chuyen_coc >= NOW()')->exists();

        return $conHan ? null : 'Đã quá hạn chuyển cọc nên không thể gửi thông báo chuyển tiền nữa.';
    }

    private function noiDungChuyenKhoan(YeuCauGiuCho $yeuCau): string
    {
        $yeuCau->loadMissing('phong');

        return 'COC ' . $yeuCau->phong->ma_phong . ' ' . $yeuCau->id;
    }
}
