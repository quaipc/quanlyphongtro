<?php

use App\Http\Controllers\BienLaiController;
use App\Http\Controllers\ChuTro\BanGiaoController as ChuTroBanGiaoController;
use App\Http\Controllers\ChuTro\BaoCaoController as ChuTroBaoCaoController;
use App\Http\Controllers\ChuTro\CocController as ChuTroCocController;
use App\Http\Controllers\ChuTro\DichVuController as ChuTroDichVuController;
use App\Http\Controllers\ChuTro\HoaDonController as ChuTroHoaDonController;
use App\Http\Controllers\ChuTro\HopDongController as ChuTroHopDongController;
use App\Http\Controllers\ChuTro\LichXemController as ChuTroLichXemController;
use App\Http\Controllers\ChuTro\SuCoController as ChuTroSuCoController;
use App\Http\Controllers\ChuTro\ThanhLyController as ChuTroThanhLyController;
use App\Http\Controllers\ChuTro\YeuCauGiuChoController as ChuTroYeuCauController;
use App\Http\Controllers\BaoTraPhongController;
use App\Http\Controllers\HoaDonKhachController;
use App\Http\Controllers\HopDongKhachController;
use App\Http\Controllers\LichXemKhachController;
use App\Http\Controllers\PhongController;
use App\Http\Controllers\PhongCongKhaiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuCoKhachController;
use App\Http\Controllers\YeuCauKhachController;
use Illuminate\Support\Facades\Route;

// ---------- Công khai: ai cũng xem được ----------
Route::get('/', [PhongCongKhaiController::class, 'index'])->name('trang-chu');
Route::get('/phong-trong/{phong}', [PhongCongKhaiController::class, 'show'])->name('phong.chi-tiet');

// Sau khi đăng nhập Breeze chuyển về /dashboard: đưa về đúng nơi theo vai trò
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->laChuTro() ? 'chutro.bao-cao.index' : 'trang-chu');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Ảnh biên lai (riêng tư): chủ trọ hoặc đúng khách của yêu cầu mới xem được
    Route::get('/bien-lai/{thanhToan}', [BienLaiController::class, 'show'])->name('bien-lai.show');
});

// ---------- Khách thuê ----------
Route::middleware(['auth', 'role:khach'])->group(function () {
    Route::post('/phong-trong/{phong}/xem', [LichXemKhachController::class, 'store'])->name('lich-xem.store');
    Route::get('/lich-xem-cua-toi', [LichXemKhachController::class, 'index'])->name('lich-xem.index');
    Route::patch('/lich-xem-cua-toi/{lichXem}/huy', [LichXemKhachController::class, 'huy'])->name('lich-xem.huy');

    Route::post('/phong-trong/{phong}/giu-cho', [YeuCauKhachController::class, 'store'])->name('giu-cho.store');
    Route::get('/yeu-cau-giu-cho', [YeuCauKhachController::class, 'index'])->name('giu-cho.index');
    Route::patch('/yeu-cau-giu-cho/{yeuCau}/huy', [YeuCauKhachController::class, 'huy'])->name('giu-cho.huy');

    Route::get('/yeu-cau-giu-cho/{yeuCau}/chuyen-coc', [YeuCauKhachController::class, 'formChuyenCoc'])->name('giu-cho.chuyen-coc');
    Route::post('/yeu-cau-giu-cho/{yeuCau}/chuyen-coc', [YeuCauKhachController::class, 'chuyenCoc'])->name('giu-cho.chuyen-coc.store');

    Route::get('/hop-dong-cua-toi', [HopDongKhachController::class, 'index'])->name('hop-dong.index');
    Route::get('/hop-dong-cua-toi/{hopDong}', [HopDongKhachController::class, 'show'])->name('hop-dong.show');
    Route::get('/hop-dong-cua-toi/{hopDong}/bao-tra', [BaoTraPhongController::class, 'form'])->name('hop-dong.bao-tra');
    Route::post('/hop-dong-cua-toi/{hopDong}/bao-tra', [BaoTraPhongController::class, 'store'])->name('hop-dong.bao-tra.store');

    Route::get('/hoa-don-cua-toi', [HoaDonKhachController::class, 'index'])->name('hoa-don.index');
    Route::get('/hoa-don-cua-toi/{hoaDon}', [HoaDonKhachController::class, 'show'])->name('hoa-don.show');
    Route::post('/hoa-don-cua-toi/{hoaDon}/thanh-toan', [HoaDonKhachController::class, 'thanhToan'])->name('hoa-don.thanh-toan');

    Route::get('/su-co-cua-toi', [SuCoKhachController::class, 'index'])->name('su-co.index');
    Route::post('/su-co-cua-toi', [SuCoKhachController::class, 'store'])->name('su-co.store');
    Route::post('/su-co-cua-toi/{suCo}/xac-nhan', [SuCoKhachController::class, 'xacNhan'])->name('su-co.xac-nhan');
    Route::post('/su-co-cua-toi/{suCo}/chua-hai-long', [SuCoKhachController::class, 'chuaHaiLong'])->name('su-co.chua-hai-long');
});

// ---------- Chủ trọ ----------
Route::middleware(['auth', 'role:chu_tro'])
    ->prefix('chu-tro')
    ->name('chutro.')
    ->group(function () {
        Route::resource('phong', PhongController::class)->except('show');
        Route::delete('anh-phong/{anhPhong}', [PhongController::class, 'xoaAnh'])->name('phong.xoa-anh');

        Route::get('lich-xem', [ChuTroLichXemController::class, 'index'])->name('lich-xem.index');
        Route::patch('lich-xem/{lichXem}', [ChuTroLichXemController::class, 'capNhat'])->name('lich-xem.cap-nhat');

        Route::get('yeu-cau', [ChuTroYeuCauController::class, 'index'])->name('yeu-cau.index');
        Route::post('yeu-cau/{yeuCau}/duyet', [ChuTroYeuCauController::class, 'duyet'])->name('yeu-cau.duyet');
        Route::post('yeu-cau/{yeuCau}/tu-choi', [ChuTroYeuCauController::class, 'tuChoi'])->name('yeu-cau.tu-choi');
        Route::post('bien-lai/{thanhToan}/xac-nhan', [ChuTroYeuCauController::class, 'xacNhanBienLai'])->name('bien-lai.xac-nhan');
        Route::post('bien-lai/{thanhToan}/tu-choi', [ChuTroYeuCauController::class, 'tuChoiBienLai'])->name('bien-lai.tu-choi');

        Route::get('hop-dong', [ChuTroHopDongController::class, 'index'])->name('hop-dong.index');
        Route::get('yeu-cau/{yeuCau}/hop-dong/tao', [ChuTroHopDongController::class, 'create'])->name('hop-dong.create');
        Route::post('yeu-cau/{yeuCau}/hop-dong', [ChuTroHopDongController::class, 'store'])->name('hop-dong.store');
        Route::get('hop-dong/{hopDong}', [ChuTroHopDongController::class, 'show'])->name('hop-dong.show');
        Route::get('hop-dong/{hopDong}/sua', [ChuTroHopDongController::class, 'edit'])->name('hop-dong.edit');
        Route::put('hop-dong/{hopDong}', [ChuTroHopDongController::class, 'update'])->name('hop-dong.update');
        Route::post('hop-dong/{hopDong}/ky', [ChuTroHopDongController::class, 'ky'])->name('hop-dong.ky');
        Route::get('hop-dong/{hopDong}/ban-giao', [ChuTroBanGiaoController::class, 'form'])->name('hop-dong.ban-giao');
        Route::post('hop-dong/{hopDong}/ban-giao', [ChuTroBanGiaoController::class, 'store'])->name('hop-dong.ban-giao.store');
        Route::post('hop-dong/{hopDong}/khach-tu-choi-ky', [ChuTroHopDongController::class, 'khachTuChoiKy'])->name('hop-dong.khach-tu-choi-ky');
        Route::get('hop-dong/{hopDong}/thanh-ly', [ChuTroThanhLyController::class, 'form'])->name('hop-dong.thanh-ly');
        Route::post('hop-dong/{hopDong}/thanh-ly', [ChuTroThanhLyController::class, 'store'])->name('hop-dong.thanh-ly.store');
        Route::post('hop-dong/{hopDong}/gia-han', [ChuTroThanhLyController::class, 'giaHan'])->name('hop-dong.gia-han');

        Route::get('dich-vu', [ChuTroDichVuController::class, 'index'])->name('dich-vu.index');
        Route::post('dich-vu', [ChuTroDichVuController::class, 'store'])->name('dich-vu.store');
        Route::put('dich-vu/{dichVu}', [ChuTroDichVuController::class, 'update'])->name('dich-vu.update');
        Route::delete('dich-vu/{dichVu}', [ChuTroDichVuController::class, 'destroy'])->name('dich-vu.destroy');

        Route::get('bao-cao', [ChuTroBaoCaoController::class, 'index'])->name('bao-cao.index');
        Route::get('coc', [ChuTroCocController::class, 'index'])->name('coc.index');
        Route::post('yeu-cau/{yeuCau}/xu-ly-coc', [ChuTroCocController::class, 'xuLy'])->name('coc.xu-ly');
        Route::post('yeu-cau/{yeuCau}/huy-hoan-coc', [ChuTroCocController::class, 'huyHoanCoc'])->name('yeu-cau.huy-hoan-coc');

        Route::get('su-co', [ChuTroSuCoController::class, 'index'])->name('su-co.index');
        Route::get('su-co/{suCo}', [ChuTroSuCoController::class, 'show'])->name('su-co.show');
        Route::post('su-co/{suCo}/tiep-nhan', [ChuTroSuCoController::class, 'tiepNhan'])->name('su-co.tiep-nhan');
        Route::post('su-co/{suCo}/bao-sua-xong', [ChuTroSuCoController::class, 'baoSuaXong'])->name('su-co.bao-sua-xong');
        Route::post('su-co/{suCo}/su-co-nang', [ChuTroSuCoController::class, 'suCoNang'])->name('su-co.nang');

        Route::get('hoa-don', [ChuTroHoaDonController::class, 'index'])->name('hoa-don.index');
        Route::get('hop-dong/{hopDong}/hoa-don/tao', [ChuTroHoaDonController::class, 'create'])->name('hoa-don.create');
        Route::post('hop-dong/{hopDong}/hoa-don', [ChuTroHoaDonController::class, 'store'])->name('hoa-don.store');
        Route::get('hoa-don/{hoaDon}', [ChuTroHoaDonController::class, 'show'])->name('hoa-don.show');
        Route::post('hoa-don/{hoaDon}/thu-tien-mat', [ChuTroHoaDonController::class, 'thuTienMat'])->name('hoa-don.thu-tien-mat');
        Route::post('thanh-toan/{thanhToan}/xac-nhan', [ChuTroHoaDonController::class, 'xacNhanThanhToan'])->name('thanh-toan.xac-nhan');
        Route::post('thanh-toan/{thanhToan}/tu-choi', [ChuTroHoaDonController::class, 'tuChoiThanhToan'])->name('thanh-toan.tu-choi');
    });

require __DIR__.'/auth.php';
