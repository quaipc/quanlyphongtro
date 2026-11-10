<dl class="divide-y divide-gray-100 text-sm">
    <div class="flex justify-between py-2"><dt class="text-gray-500">Phòng</dt><dd class="font-medium">{{ $hopDong->phong->ma_phong }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Khách thuê</dt><dd class="font-medium">{{ $hopDong->khach->ho_ten ?? auth()->user()->ho_ten }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Ngày bắt đầu (nhận phòng)</dt><dd class="font-medium">{{ $hopDong->ngay_bat_dau->format('d/m/Y') }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Ngày kết thúc</dt><dd class="font-medium">{{ $hopDong->ngay_ket_thuc->format('d/m/Y') }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Ngày ký</dt><dd class="font-medium">{{ $hopDong->ngay_ky ? $hopDong->ngay_ky->format('d/m/Y') : 'Chưa ký' }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Giá thuê / tháng</dt><dd class="font-medium">{{ number_format($hopDong->gia_thue, 0, ',', '.') }} đ</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Chu kỳ thu tiền phòng</dt><dd class="font-medium">{{ $hopDong->nhanChuKy() }}</dd></div>
    <div class="flex justify-between py-2"><dt class="text-gray-500">Tiền cọc</dt><dd class="font-medium">{{ number_format($hopDong->tien_coc, 0, ',', '.') }} đ</dd></div>

    @if ($hopDong->ngay_tra_phong)
        <div class="flex justify-between py-2"><dt class="text-gray-500">Ngày báo trả</dt><dd class="font-medium">{{ $hopDong->ngay_bao_tra ? $hopDong->ngay_bao_tra->format('d/m/Y') : '—' }}</dd></div>
        <div class="flex justify-between py-2"><dt class="text-gray-500">Ngày trả phòng</dt><dd class="font-medium">{{ $hopDong->ngay_tra_phong->format('d/m/Y') }}</dd></div>
    @endif
    @if ($hopDong->mien_phat)
        <div class="flex justify-between py-2"><dt class="text-gray-500">Miễn phạt (sự cố nặng)</dt><dd class="font-medium">Có</dd></div>
    @endif

    @if ($hopDong->trang_thai === 'da_thanh_ly')
        <div class="flex justify-between py-2"><dt class="text-gray-500">Khoản hư hỏng trừ vào cọc</dt><dd class="font-medium">{{ number_format($hopDong->tien_tru_hu_hong, 0, ',', '.') }} đ</dd></div>
        <div class="flex justify-between py-2"><dt class="text-gray-500">Hoàn cọc cho khách</dt><dd class="font-medium text-green-700">{{ number_format($hopDong->tien_hoan_coc, 0, ',', '.') }} đ</dd></div>
        @if ((int) $hopDong->tien_mat_coc > 0)
            <div class="flex justify-between py-2"><dt class="text-gray-500">Mất cọc (báo trả trễ)</dt><dd class="font-medium text-red-700">{{ number_format($hopDong->tien_mat_coc, 0, ',', '.') }} đ</dd></div>
        @endif
        @if ((int) $hopDong->no_con_lai > 0)
            <div class="flex justify-between py-2"><dt class="text-gray-500">Khách còn nợ</dt><dd class="font-medium text-red-700">{{ number_format($hopDong->no_con_lai, 0, ',', '.') }} đ</dd></div>
        @endif
        @if ((int) $hopDong->tien_hoan_tien_phong > 0)
            <div class="flex justify-between py-2"><dt class="text-gray-500">Hoàn tiền phòng (sự cố nặng)</dt><dd class="font-medium">{{ number_format($hopDong->tien_hoan_tien_phong, 0, ',', '.') }} đ</dd></div>
        @endif
    @endif
</dl>

<h3 class="mt-5 mb-2 text-sm font-semibold text-gray-800">Người ở cùng</h3>
@if ($hopDong->nguoiO->isEmpty())
    <p class="text-sm text-gray-500">Không có.</p>
@else
    <ul class="list-disc pl-5 text-sm text-gray-700">
        @foreach ($hopDong->nguoiO as $n)
            <li>{{ $n->ho_ten }}@if ($n->cccd) – CCCD {{ $n->cccd }}@endif</li>
        @endforeach
    </ul>
@endif
