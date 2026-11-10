<?php

namespace App\Http\Controllers;

use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BienLaiController extends Controller
{
    public function show(Request $request, ThanhToan $thanhToan)
    {
        $user = $request->user();
        $thanhToan->load(['yeuCau', 'hoaDon.hopDong']);

        // Chủ trọ, hoặc đúng khách có yêu cầu giữ chỗ / hóa đơn chứa biên lai này
        $duocXem = $user->laChuTro()
            || ($thanhToan->yeuCau && $thanhToan->yeuCau->khach_id === $user->id)
            || ($thanhToan->hoaDon && $thanhToan->hoaDon->hopDong && $thanhToan->hoaDon->hopDong->khach_id === $user->id);
        abort_unless($duocXem, 403);

        abort_unless(
            $thanhToan->anh_bien_lai && Storage::disk('local')->exists($thanhToan->anh_bien_lai),
            404
        );

        return response()->file(Storage::disk('local')->path($thanhToan->anh_bien_lai));
    }
}
