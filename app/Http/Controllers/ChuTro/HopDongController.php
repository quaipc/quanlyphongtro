<?php

namespace App\Http\Controllers\ChuTro;

use App\Http\Controllers\Controller;
use App\Models\HopDong;
use App\Models\User;
use App\Models\YeuCauGiuCho;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HopDongController extends Controller
{
    public function index()
    {
        $hopDong = HopDong::with(['phong', 'khach'])
            ->orderByRaw("FIELD(trang_thai, 'cho_ky', 'hieu_luc', 'cho_thanh_ly', 'da_thanh_ly', 'da_huy')")
            ->orderByDesc('id')
            ->paginate(15);

        return view('chu-tro.hop-dong.index', compact('hopDong'));
    }

    public function show(HopDong $hopDong)
    {
        $hopDong->load(['phong', 'khach', 'nguoiO', 'yeuCau', 'hoaDon']);

        return view('chu-tro.hop-dong.show', compact('hopDong'));
    }

    // ---------- Lập hợp đồng từ yêu cầu đã cọc ----------

    public function create(YeuCauGiuCho $yeuCau)
    {
        if ($hd = HopDong::where('yeu_cau_id', $yeuCau->id)->first()) {
            return redirect()->route('chutro.hop-dong.show', $hd);
        }
        if ($loi = $this->kiemTraLap($yeuCau)) {
            return redirect()->route('chutro.yeu-cau.index')->with('loi', $loi);
        }

        $yeuCau->load(['phong', 'khach']);

        return view('chu-tro.hop-dong.form', [
            'yeuCau'  => $yeuCau,
            'hopDong' => null,
            'nguoiO'  => collect(),
        ]);
    }

    public function store(Request $request, YeuCauGiuCho $yeuCau): RedirectResponse
    {
        $yeuCau->load('khach');
        $data = $request->validate($this->rules($yeuCau->khach), [], $this->tenTruong());

        try {
            $hopDong = DB::transaction(function () use ($yeuCau, $data) {
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($yeuCau->id);

                if (HopDong::where('yeu_cau_id', $y->id)->exists()) {
                    throw new \RuntimeException('Yêu cầu này đã có hợp đồng.');
                }
                if ($loi = $this->kiemTraLap($y)) {
                    throw new \RuntimeException($loi);
                }

                $this->capNhatCccdKhach($yeuCau->khach, $data);

                $hd = HopDong::create([
                    'yeu_cau_id'    => $y->id,
                    'phong_id'      => $y->phong_id,
                    'khach_id'      => $y->khach_id,
                    'ngay_bat_dau'  => $data['ngay_bat_dau'],
                    'ngay_ket_thuc' => $data['ngay_ket_thuc'],
                    'gia_thue'      => $data['gia_thue'],
                    'chu_ky_thu'    => $data['chu_ky_thu'],
                    'tien_coc'      => $y->tongCocDaXacNhan(),
                    'trang_thai'    => 'cho_ky',
                ]);

                $this->luuNguoiO($hd, $data['nguoi_o'] ?? []);

                return $hd;
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('chutro.yeu-cau.index')->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            return back()->withInput()->with('loi', 'Không lập được hợp đồng: phòng này đã có hợp đồng khác còn hiệu lực.');
        }

        return redirect()->route('chutro.hop-dong.show', $hopDong)
            ->with('status', 'Đã lập hợp đồng. Khi hai bên đã ký, bấm "Xác nhận hai bên đã ký".');
    }

    // ---------- Sửa hợp đồng khi còn chờ ký ----------

    public function edit(HopDong $hopDong)
    {
        if ($hopDong->trang_thai !== 'cho_ky') {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', 'Chỉ sửa được hợp đồng đang chờ ký.');
        }

        $hopDong->load(['nguoiO', 'khach', 'phong']);
        $yeuCau = $hopDong->yeuCau()->with(['phong', 'khach'])->first();

        return view('chu-tro.hop-dong.form', [
            'yeuCau'  => $yeuCau,
            'hopDong' => $hopDong,
            'nguoiO'  => $hopDong->nguoiO->values(),
        ]);
    }

    public function update(Request $request, HopDong $hopDong): RedirectResponse
    {
        $hopDong->load('khach');
        $data = $request->validate($this->rules($hopDong->khach), [], $this->tenTruong());

        try {
            DB::transaction(function () use ($hopDong, $data) {
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);

                if ($hd->trang_thai !== 'cho_ky') {
                    throw new \RuntimeException('Chỉ sửa được hợp đồng đang chờ ký.');
                }

                $this->capNhatCccdKhach($hopDong->khach, $data);

                $hd->update([
                    'ngay_bat_dau'  => $data['ngay_bat_dau'],
                    'ngay_ket_thuc' => $data['ngay_ket_thuc'],
                    'gia_thue'      => $data['gia_thue'],
                    'chu_ky_thu'    => $data['chu_ky_thu'],
                ]);

                $hd->nguoiO()->delete();
                $this->luuNguoiO($hd, $data['nguoi_o'] ?? []);
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('chutro.hop-dong.show', $hopDong)->with('loi', $e->getMessage());
        }

        return redirect()->route('chutro.hop-dong.show', $hopDong)->with('status', 'Đã cập nhật hợp đồng.');
    }

    // ---------- Xác nhận hai bên đã ký ----------
    // Hai bên ký giấy trực tiếp, chủ trọ nhập ngày ký và xác nhận trên hệ thống.
    // Hợp đồng -> hiệu lực, yêu cầu -> đã ký. Phòng vẫn "Đã giữ chỗ" cho đến khi bàn giao.
    public function ky(Request $request, HopDong $hopDong): RedirectResponse
    {
        $data = $request->validate([
            'ngay_ky' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'ngay_ky.before_or_equal' => 'Ngày ký không được ở tương lai.',
        ], ['ngay_ky' => 'ngày ký']);

        try {
            DB::transaction(function () use ($hopDong, $data) {
                $hd = HopDong::lockForUpdate()->findOrFail($hopDong->id);
                $y = YeuCauGiuCho::lockForUpdate()->findOrFail($hd->yeu_cau_id);

                if ($hd->trang_thai !== 'cho_ky') {
                    throw new \RuntimeException('Hợp đồng này không ở trạng thái chờ ký.');
                }

                $conHan = YeuCauGiuCho::whereKey($y->id)->whereRaw('han_giu >= CURDATE()')->exists();
                if ($y->trang_thai !== 'da_coc' || !$conHan) {
                    throw new \RuntimeException('Đã quá hạn giữ chỗ (7 ngày) nên không thể ký hợp đồng này.');
                }

                if (!HopDong::whereKey($hd->id)->whereRaw('ngay_bat_dau >= CURDATE()')->exists()) {
                    throw new \RuntimeException('Ngày bắt đầu hợp đồng đã qua. Hãy sửa lại ngày bắt đầu rồi ký.');
                }

                $ngayKy = Carbon::parse($data['ngay_ky'])->startOfDay();

                // Hạn giữ chỗ = ngày xác nhận cọc + 7 ngày, nên ngày xác nhận cọc = hạn giữ chỗ - 7 ngày
                $ngayXacNhanCoc = $y->han_giu->copy()->subDays(7);
                if ($ngayKy->lt($ngayXacNhanCoc)) {
                    throw new \RuntimeException('Ngày ký không được trước ngày xác nhận cọc (' . $ngayXacNhanCoc->format('d/m/Y') . ').');
                }
                if ($ngayKy->copy()->addDays(14)->lt($hd->ngay_bat_dau)) {
                    throw new \RuntimeException('Ngày bắt đầu hợp đồng phải không muộn hơn 14 ngày kể từ ngày ký.');
                }

                $hd->update([
                    'trang_thai' => 'hieu_luc',
                    'ngay_ky'    => $ngayKy->toDateString(),
                ]);
                YeuCauGiuCho::whereKey($y->id)->update(['trang_thai' => 'da_ky']);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('loi', $e->getMessage());
        } catch (QueryException $e) {
            return back()->withInput()->with('loi', 'Không thể ký: ngày bắt đầu phải không muộn hơn 14 ngày kể từ ngày ký.');
        }

        return back()->with('status', 'Hợp đồng đã có hiệu lực.');
    }

    // Khách từ chối ký tại chỗ: coi là khách tự hủy, mất cọc (QT3).
    // Dùng thủ tục sp_khach_huy_giu_cho: hủy yêu cầu và hợp đồng, ghi mất cọc, phòng về Trống.
    // Không bọc trong transaction của Laravel vì thủ tục tự mở transaction riêng.
    public function khachTuChoiKy(HopDong $hopDong): RedirectResponse
    {
        if ($hopDong->trang_thai !== 'cho_ky') {
            return back()->with('loi', 'Chỉ ghi nhận khách từ chối ký với hợp đồng đang chờ ký.');
        }

        try {
            DB::statement('CALL sp_khach_huy_giu_cho(?)', [$hopDong->yeu_cau_id]);
        } catch (QueryException $e) {
            return back()->with('loi', 'Không thể hủy ở trạng thái hiện tại.');
        }

        return redirect()->route('chutro.hop-dong.index')
            ->with('status', 'Đã ghi nhận khách từ chối ký: hợp đồng bị hủy, khách mất cọc và phòng được mở lại.');
    }

    // ---------- Hỗ trợ ----------

    private function kiemTraLap(YeuCauGiuCho $yeuCau): ?string
    {
        if ($yeuCau->trang_thai !== 'da_coc') {
            return 'Chỉ lập hợp đồng cho yêu cầu đã cọc.';
        }

        $conHan = YeuCauGiuCho::whereKey($yeuCau->id)->whereRaw('han_giu >= CURDATE()')->exists();

        return $conHan ? null : 'Đã quá hạn giữ chỗ (7 ngày) nên không thể lập hợp đồng.';
    }

    private function rules(User $khach): array
    {
        return [
            'ngay_bat_dau'  => ['required', 'date', 'after_or_equal:today', 'before_or_equal:+14 days'],
            'ngay_ket_thuc' => ['required', 'date', 'after:ngay_bat_dau'],
            'gia_thue'      => ['required', 'integer', 'min:0', 'max:999999999999'],
            'chu_ky_thu'    => ['required', 'in:1,3'],
            'cccd_khach'    => [
                Rule::requiredIf(blank($khach->cccd)),
                'nullable',
                'regex:/^(\d{9}|\d{12})$/',
                Rule::unique('nguoi_dung', 'cccd')->ignore($khach->id),
            ],
            'nguoi_o'            => ['nullable', 'array', 'max:5'],
            'nguoi_o.*.ho_ten'   => ['nullable', 'string', 'max:100'],
            'nguoi_o.*.cccd'     => ['nullable', 'string', 'max:20'],
        ];
    }

    private function tenTruong(): array
    {
        return [
            'ngay_bat_dau'     => 'ngày bắt đầu',
            'ngay_ket_thuc'    => 'ngày kết thúc',
            'gia_thue'         => 'giá thuê',
            'chu_ky_thu'       => 'chu kỳ thu tiền phòng',
            'cccd_khach'       => 'CCCD của khách',
            'nguoi_o.*.ho_ten' => 'họ tên người ở cùng',
            'nguoi_o.*.cccd'   => 'CCCD người ở cùng',
        ];
    }

    // Chủ trọ bổ sung CCCD cho khách nếu hồ sơ khách chưa có
    private function capNhatCccdKhach(User $khach, array $data): void
    {
        if (blank($khach->cccd) && filled($data['cccd_khach'] ?? null)) {
            $khach->update(['cccd' => $data['cccd_khach']]);
        }
    }

    private function luuNguoiO(HopDong $hopDong, array $dong): void
    {
        foreach ($dong as $r) {
            if (filled($r['ho_ten'] ?? null)) {
                $hopDong->nguoiO()->create([
                    'ho_ten' => $r['ho_ten'],
                    'cccd'   => filled($r['cccd'] ?? null) ? $r['cccd'] : null,
                ]);
            }
        }
    }
}
