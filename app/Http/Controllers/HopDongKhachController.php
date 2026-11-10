<?php

namespace App\Http\Controllers;

use App\Models\HopDong;
use Illuminate\Http\Request;

class HopDongKhachController extends Controller
{
    public function index(Request $request)
    {
        $hopDong = HopDong::with('phong')
            ->where('khach_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10);

        return view('khach.hop-dong.index', compact('hopDong'));
    }

    public function show(Request $request, HopDong $hopDong)
    {
        abort_unless($hopDong->khach_id === $request->user()->id, 403);
        $hopDong->load(['phong', 'nguoiO']);

        return view('khach.hop-dong.show', compact('hopDong'));
    }
}
