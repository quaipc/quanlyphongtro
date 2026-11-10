<?php

namespace App\Http\Controllers;

use App\Models\HopDong;
use App\Services\HoaDonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BaoTraPhongController extends Controller
{
    public function form(Request $request, HopDong $hopDong)
    {
        abort_unless($hopDong->khach_id === $request->user()->id, 403);
        $hopDong->load('phong');

        if ($loi = $this->kiemTra($hopDong)) {
            return redirect()->route('hop-dong.show', $hopDong)->with('loi', $loi);
        }

        return view('khach.hop-dong.bao-tra', compact('hopDong'));
    }

    public function store(Request $request, HopDong $hopDong, HoaDonService $dichVu): RedirectResponse
    {
        abort_unless($hopDong->khach_id === $request->user()->id, 403);
        $hopDong->load('phong');

        if ($loi = $this->kiemTra($hopDong)) {
            return redirect()->route('hop-dong.show', $hopDong)->with('loi', $loi);
        }

        // QT23: ngày trả không được sau ngày kết thúc hợp đồng; muốn ở thêm thì chủ trọ gia hạn trước
        $ketThuc = $hopDong->ngay_ket_thuc;
        $data = $request->validate([
            'ngay_tra' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $ketThuc->toDateString()],
        ], [
            'ngay_tra.after_or_equal'  => 'Ngày trả phòng phải từ hôm nay trở đi.',
            'ngay_tra.before_or_equal' => 'Ngày trả phòng không được sau ngày kết thúc hợp đồng (' . $ketThuc->format('d/m/Y') . '). Muốn ở thêm, hãy nhờ chủ trọ gia hạn hợp đồng trước.',
        ], ['ngay_tra' => 'ngày trả phòng']);

        $homNay = $dichVu->homNay();
        $tra = Carbon::parse($data['ngay_tra'])->startOfDay();

        // Trả sớm hơn ngày kết thúc mà báo trước chưa đủ 1 tháng thì mất cọc (QT11), trừ khi được miễn phạt (QT13)
        $baoTre = !$hopDong->mien_phat
            && $tra->lt($hopDong->ngay_ket_thuc)
            && $tra->lt($homNay->copy()->addMonthsNoOverflow(1));

        if ($baoTre && !$request->boolean('xac_nhan')) {
            return back()->withInput()->with('canh_bao', true);
        }

        try {
            // Thủ tục trả về một dòng kết quả nên đọc và đóng bằng PDO để không làm kẹt kết nối
            $stmt = DB::connection()->getPdo()->prepare('CALL sp_bao_tra_phong(?, ?)');
            $stmt->execute([$hopDong->id, $tra->toDateString()]);
            $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($stmt->nextRowset()) {
                // đọc hết các tập kết quả còn lại
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            return back()->withInput()->with('loi', $this->loiThuTuc($e));
        }

        return redirect()->route('hop-dong.show', $hopDong)
            ->with('status', 'Đã gửi báo trả phòng. Chủ trọ sẽ kiểm tra phòng và thanh lý hợp đồng vào ngày trả.');
    }

    private function kiemTra(HopDong $hopDong): ?string
    {
        if ($hopDong->trang_thai !== 'hieu_luc' || $hopDong->phong->trang_thai !== 'dang_thue') {
            return 'Chỉ báo trả phòng khi hợp đồng đang hiệu lực và bạn đã nhận phòng.';
        }

        // Quá ngày kết thúc thì không còn ngày trả hợp lệ: chủ trọ thanh lý theo ngày kết thúc (QT23)
        if ($hopDong->ngay_ket_thuc->lt(app(HoaDonService::class)->homNay())) {
            return 'Hợp đồng đã hết hạn nên không cần báo trả phòng. Chủ trọ sẽ thanh lý theo ngày kết thúc hợp đồng.';
        }

        return null;
    }

    // Lấy thông báo lỗi do thủ tục SIGNAL ra
    private function loiThuTuc(\PDOException $e): string
    {
        $info = $e->errorInfo ?? [];

        return (($info[0] ?? null) === '45000' && !empty($info[2]))
            ? $info[2]
            : 'Không gửi được báo trả phòng. Vui lòng thử lại.';
    }
}
