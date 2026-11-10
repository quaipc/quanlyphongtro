<?php

namespace App\Http\Controllers;

use App\Models\Phong;
use App\Models\YeuCauGiuCho;
use Illuminate\Http\Request;

class PhongCongKhaiController extends Controller
{
    // Các cách sắp xếp
    public const SAP_XEP = [
        'gia_tang' => ['gia_thue', 'asc', 'Giá thấp đến cao'],
        'gia_giam' => ['gia_thue', 'desc', 'Giá cao đến thấp'],
        'dt_tang'  => ['dien_tich', 'asc', 'Diện tích nhỏ đến lớn'],
        'dt_giam'  => ['dien_tich', 'desc', 'Diện tích lớn đến nhỏ'],
    ];

    // Trang chủ: danh sách phòng trống, có tìm kiếm, lọc theo giá và diện tích, sắp xếp
    public function index(Request $request)
    {
        $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'gia_tu'   => ['nullable', 'integer', 'min:0'],
            'gia_den'  => ['nullable', 'integer', 'min:0'],
            'dt_tu'    => ['nullable', 'numeric', 'min:0'],
            'dt_den'   => ['nullable', 'numeric', 'min:0'],
            'sap_xep'  => ['nullable', 'in:' . implode(',', array_keys(self::SAP_XEP))],
        ], [], [
            'gia_tu' => 'giá tối thiểu', 'gia_den' => 'giá tối đa',
            'dt_tu' => 'diện tích tối thiểu', 'dt_den' => 'diện tích tối đa',
        ]);

        // Nhập ngược khoảng (từ lớn hơn đến) thì tự hoán đổi cho hợp lý
        foreach ([['gia_tu', 'gia_den'], ['dt_tu', 'dt_den']] as [$tu, $den]) {
            if ($request->filled($tu) && $request->filled($den) && (float) $request->input($den) < (float) $request->input($tu)) {
                $tam = $request->input($tu);
                $request->merge([$tu => $request->input($den), $den => $tam]);
            }
        }

        [$cot, $huong] = self::SAP_XEP[$request->input('sap_xep', 'gia_tang')];

        $phongs = $this->loc($request)
            ->with('anhPhong')
            ->orderBy($cot, $huong)
            ->orderBy('ma_phong')
            ->paginate(9)
            ->withQueryString();

        $coLoc = $request->hasAny(['q', 'gia_tu', 'gia_den', 'dt_tu', 'dt_den']) && $this->coGiaTriLoc($request);
        $tongTrong = Phong::where('trang_thai', 'trong')->count();

        // Không có kết quả: gợi ý nới bộ lọc bằng cách bỏ từng nhóm lọc xem có phòng nào không
        $goiY = [];
        if ($phongs->total() === 0 && $coLoc) {
            $nhom = [
                'tu_khoa'  => ['Bỏ từ khóa', ['q']],
                'gia'      => ['Bỏ lọc giá', ['gia_tu', 'gia_den']],
                'dien_tich' => ['Bỏ lọc diện tích', ['dt_tu', 'dt_den']],
            ];
            foreach ($nhom as $ma => [$nhan, $bo]) {
                if ($request->hasAny($bo) && collect($bo)->contains(fn ($k) => $request->filled($k))) {
                    $so = $this->loc($request, [$ma])->count();
                    if ($so > 0) {
                        $goiY[] = ['nhan' => $nhan, 'so' => $so, 'bo' => $bo];
                    }
                }
            }
        }

        return view('cong-khai.index', compact('phongs', 'coLoc', 'tongTrong', 'goiY'));
    }

    // Chi tiết phòng. Phòng không còn trống thì vẫn xem được nhưng ẩn nút đặt lịch và giữ chỗ (UC02).
    public function show(Request $request, Phong $phong)
    {
        $phong->load('anhPhong');

        $yeuCauCuaToi = null;
        if ($request->user() && !$request->user()->laChuTro()) {
            $yeuCauCuaToi = YeuCauGiuCho::where('phong_id', $phong->id)
                ->where('khach_id', $request->user()->id)
                ->whereIn('trang_thai', YeuCauGiuCho::DANG_XU_LY)
                ->first();
        }

        return view('cong-khai.show', compact('phong', 'yeuCauCuaToi'));
    }

    // Truy vấn phòng trống theo bộ lọc; $bo là các nhóm lọc bỏ qua ('tu_khoa', 'gia', 'dien_tich')
    private function loc(Request $request, array $bo = [])
    {
        $query = Phong::where('trang_thai', 'trong');

        if (!in_array('tu_khoa', $bo, true) && $request->filled('q')) {
            $tuKhoa = '%' . addcslashes(trim($request->q), '%_\\') . '%';
            $query->where(fn ($w) => $w->where('ma_phong', 'like', $tuKhoa)->orWhere('mo_ta', 'like', $tuKhoa));
        }
        if (!in_array('gia', $bo, true)) {
            $query->when($request->filled('gia_tu'), fn ($q) => $q->where('gia_thue', '>=', (int) $request->gia_tu))
                  ->when($request->filled('gia_den'), fn ($q) => $q->where('gia_thue', '<=', (int) $request->gia_den));
        }
        if (!in_array('dien_tich', $bo, true)) {
            $query->when($request->filled('dt_tu'), fn ($q) => $q->where('dien_tich', '>=', (float) $request->dt_tu))
                  ->when($request->filled('dt_den'), fn ($q) => $q->where('dien_tich', '<=', (float) $request->dt_den));
        }

        return $query;
    }

    private function coGiaTriLoc(Request $request): bool
    {
        return collect(['q', 'gia_tu', 'gia_den', 'dt_tu', 'dt_den'])->contains(fn ($k) => $request->filled($k));
    }
}
