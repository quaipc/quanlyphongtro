<?php

namespace App\Http\Controllers;

use App\Models\AnhPhong;
use App\Models\Phong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PhongController extends Controller
{
    public function index(Request $request)
    {
        $phongs = Phong::with('anhPhong')
            ->when($request->q, fn ($q, $v) => $q->where('ma_phong', 'like', "%{$v}%"))
            ->when($request->trang_thai, fn ($q, $v) => $q->where('trang_thai', $v))
            ->orderBy('ma_phong')
            ->paginate(10)
            ->withQueryString();

        return view('phong.index', compact('phongs'));
    }

    public function create()
    {
        return view('phong.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), [], $this->tenTruong());

        $phong = Phong::create([
            'ma_phong'   => $data['ma_phong'],
            'dien_tich'  => $data['dien_tich'],
            'gia_thue'   => $data['gia_thue'],
            'mo_ta'      => $data['mo_ta'] ?? null,
            'trang_thai' => $data['trang_thai'] ?? 'trong',
        ]);

        $this->luuAnh($phong, $request);

        return redirect()->route('chutro.phong.index')
            ->with('status', "Đã thêm phòng {$phong->ma_phong}.");
    }

    public function edit(Phong $phong)
    {
        $phong->load('anhPhong');
        return view('phong.edit', compact('phong'));
    }

    public function update(Request $request, Phong $phong): RedirectResponse
    {
        $data = $request->validate($this->rules($phong), [], $this->tenTruong());

        $capNhat = Arr::only($data, ['ma_phong', 'dien_tich', 'gia_thue', 'mo_ta']);

        // Phòng đang trong quy trình giữ chỗ/thuê thì không cho đổi trạng thái bằng tay
        if (!$phong->dangTrongQuyTrinh() && isset($data['trang_thai'])) {
            $capNhat['trang_thai'] = $data['trang_thai'];
        }

        $phong->update($capNhat);
        $this->luuAnh($phong, $request);

        return redirect()->route('chutro.phong.index')
            ->with('status', "Đã cập nhật phòng {$phong->ma_phong}.");
    }

    public function destroy(Phong $phong): RedirectResponse
    {
        if ($phong->dangTrongQuyTrinh()) {
            return back()->with('loi', "Phòng {$phong->ma_phong} đang được giữ chỗ hoặc đang thuê, không thể xóa.");
        }

        $coLichSu = $phong->hopDong()->exists()
            || $phong->yeuCauGiuCho()->exists()
            || $phong->lichXem()->exists()
            || $phong->suCo()->exists();

        // Phòng đã có lịch sử thì không xóa, chỉ chuyển sang Bảo trì
        if ($coLichSu) {
            $phong->update(['trang_thai' => 'bao_tri']);
            return back()->with('status', "Phòng {$phong->ma_phong} đã có lịch sử nên không xóa được, đã chuyển sang Bảo trì.");
        }

        foreach ($phong->anhPhong as $anh) {
            Storage::disk('public')->delete($anh->duong_dan);
        }
        $ma = $phong->ma_phong;
        $phong->delete(); // bảng anh_phong tự xóa theo (ON DELETE CASCADE)

        return back()->with('status', "Đã xóa phòng {$ma}.");
    }

    public function xoaAnh(AnhPhong $anhPhong): RedirectResponse
    {
        Storage::disk('public')->delete($anhPhong->duong_dan);
        $anhPhong->delete();

        return back()->with('status', 'Đã xóa ảnh.');
    }

    private function luuAnh(Phong $phong, Request $request): void
    {
        foreach ($request->file('anh', []) as $file) {
            $phong->anhPhong()->create([
                'duong_dan' => $file->store('phong', 'public'),
            ]);
        }
    }

    private function rules(?Phong $phong = null): array
    {
        return [
            'ma_phong'   => ['required', 'string', 'max:20', Rule::unique('phong', 'ma_phong')->ignore($phong?->id)],
            'dien_tich'  => ['required', 'numeric', 'gt:0', 'max:9999.99'],
            'gia_thue'   => ['required', 'integer', 'min:0', 'max:999999999999'],
            'mo_ta'      => ['nullable', 'string', 'max:5000'],
            'trang_thai' => ['nullable', Rule::in(['trong', 'bao_tri'])],
            'anh'        => ['nullable', 'array', 'max:10'],
            'anh.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function tenTruong(): array
    {
        return [
            'ma_phong'  => 'mã phòng',
            'dien_tich' => 'diện tích',
            'gia_thue'  => 'giá thuê',
            'mo_ta'     => 'mô tả',
            'trang_thai' => 'trạng thái',
            'anh'       => 'ảnh',
            'anh.*'     => 'ảnh',
        ];
    }
}
