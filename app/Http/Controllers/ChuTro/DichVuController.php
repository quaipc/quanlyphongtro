<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\DichVu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DichVuController extends Controller
{
    public function index()
    {
        $dichVu = DichVu::orderBy('id')->get();

        return view('chu-tro.dich-vu.index', compact('dichVu'));
    }

    // Dịch vụ thêm mới luôn có đơn vị tính là "tháng" (QT22)
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ten'    => ['required', 'string', 'max:100', 'unique:dich_vu,ten'],
            'don_gia' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ], [], $this->tenTruong());

        if (DichVu::tenLaHeThong($data['ten'])) {
            return back()->withInput()->withErrors(['ten' => 'Tên này trùng với dịch vụ hệ thống (Điện, Nước).']);
        }

        DichVu::create([
            'ten'     => trim($data['ten']),
            'don_gia' => $data['don_gia'],
            'don_vi'  => 'tháng',
        ]);

        return back()->with('status', 'Đã thêm dịch vụ.');
    }

    public function update(Request $request, DichVu $dichVu): RedirectResponse
    {
        // Điện và Nước: chỉ sửa đơn giá
        if ($dichVu->laHeThong()) {
            $data = $request->validate([
                'don_gia' => ['required', 'integer', 'min:0', 'max:999999999999'],
            ], [], $this->tenTruong());

            $dichVu->update(['don_gia' => $data['don_gia']]);

            return back()->with('status', "Đã cập nhật đơn giá {$dichVu->ten}. Đơn giá mới chỉ áp dụng cho hóa đơn lập sau.");
        }

        $data = $request->validate([
            'ten'     => ['required', 'string', 'max:100', Rule::unique('dich_vu', 'ten')->ignore($dichVu->id)],
            'don_gia' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ], [], $this->tenTruong());

        if (DichVu::tenLaHeThong($data['ten'])) {
            return back()->withErrors(['ten' => 'Tên này trùng với dịch vụ hệ thống (Điện, Nước).']);
        }

        $dichVu->update(['ten' => trim($data['ten']), 'don_gia' => $data['don_gia']]);

        return back()->with('status', 'Đã cập nhật dịch vụ. Đơn giá mới chỉ áp dụng cho hóa đơn lập sau.');
    }

    public function destroy(DichVu $dichVu): RedirectResponse
    {
        if ($dichVu->laHeThong()) {
            return back()->with('loi', 'Điện và Nước là dịch vụ hệ thống, không xóa được.');
        }
        if ($dichVu->chiTietHoaDon()->exists()) {
            return back()->with('loi', "Dịch vụ {$dichVu->ten} đã có trong hóa đơn nên không xóa được.");
        }

        $dichVu->delete();

        return back()->with('status', 'Đã xóa dịch vụ.');
    }

    private function tenTruong(): array
    {
        return ['ten' => 'tên dịch vụ', 'don_gia' => 'đơn giá'];
    }
}
