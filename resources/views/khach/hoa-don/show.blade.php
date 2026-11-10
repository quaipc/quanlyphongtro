@php
    $nhanTt = ['cho_xac_nhan' => 'Chờ xác nhận', 'da_xac_nhan' => 'Đã xác nhận', 'tu_choi' => 'Bị từ chối'];
    $nhanHt = ['chuyen_khoan' => 'Chuyển khoản', 'tien_mat' => 'Tiền mặt', 'tru_coc' => 'Trừ cọc'];
    $conLai = $hoaDon->conLai();
    $dsThanhToan = $hoaDon->thanhToan->where('loai', 'hoa_don')->sortBy('id');
    $dangCho = $dsThanhToan->where('trang_thai', 'cho_xac_nhan')->isNotEmpty();
@endphp
<x-khung-cong-khai :title="'Hóa đơn #' . $hoaDon->id">
    <a href="{{ route('hoa-don.index') }}" class="text-sm text-gray-600 underline">&larr; Hóa đơn của tôi</a>

    <div class="mx-auto mt-4 max-w-2xl space-y-6">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-1 flex items-center justify-between">
                <h1 class="text-xl font-bold">Hóa đơn {{ mb_strtolower($hoaDon->nhanLoai()) }} – phòng {{ $hoaDon->hopDong->phong->ma_phong }}</h1>
                @include('partials.badge', ['tt' => $hoaDon->trang_thai, 'nhan' => $hoaDon->nhan()])
            </div>
            <p class="text-sm text-gray-600">
                Kỳ {{ $hoaDon->tu_ngay->format('d/m/Y') }} – {{ $hoaDon->den_ngay->format('d/m/Y') }} ·
                Hạn thanh toán <strong>{{ $hoaDon->han_thanh_toan->format('d/m/Y') }}</strong>
            </p>

            <table class="mt-4 min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($hoaDon->chiTiet as $ct)
                        <tr>
                            <td class="py-2">{{ $ct->mo_ta }}</td>
                            <td class="py-2 text-right">{{ number_format($ct->thanh_tien, 0, ',', '.') }} đ</td>
                        </tr>
                    @endforeach
                    @if ((int) $hoaDon->no_cu > 0)
                        <tr>
                            <td class="py-2">Nợ cũ cộng dồn</td>
                            <td class="py-2 text-right">{{ number_format($hoaDon->no_cu, 0, ',', '.') }} đ</td>
                        </tr>
                    @endif
                    <tr class="font-semibold">
                        <td class="py-2">Tổng tiền</td>
                        <td class="py-2 text-right">{{ number_format($hoaDon->tong_tien, 0, ',', '.') }} đ</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-500">Đã thanh toán</td>
                        <td class="py-2 text-right">{{ number_format($hoaDon->tong_tien - $conLai, 0, ',', '.') }} đ</td>
                    </tr>
                    <tr class="font-semibold text-indigo-700">
                        <td class="py-2">Còn phải trả</td>
                        <td class="py-2 text-right">{{ number_format($conLai, 0, ',', '.') }} đ</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($dsThanhToan->isNotEmpty())
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold">Các lần thanh toán</h2>
                @foreach ($dsThanhToan as $tt)
                    <p class="mb-2 text-sm">
                        {{ number_format($tt->so_tien, 0, ',', '.') }} đ · {{ $nhanHt[$tt->hinh_thuc] ?? $tt->hinh_thuc }} · {{ $tt->ngay_tt->format('H:i d/m/Y') }}
                        @include('partials.badge', ['tt' => $tt->trang_thai, 'nhan' => $nhanTt[$tt->trang_thai] ?? $tt->trang_thai])
                    </p>
                @endforeach
            </div>
        @endif

        @if ($hoaDon->choPhepThanhToan() && $conLai > 0)
            @if ($dangCho)
                <div class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-900">Bạn đã gửi biên lai, đang chờ chủ trọ xác nhận.</div>
            @else
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-3 font-semibold">Thanh toán</h2>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Ngân hàng</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.ngan_hang') }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Số tài khoản</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.so_tai_khoan') }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Chủ tài khoản</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.chu_tai_khoan') }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Số tiền cần chuyển</dt><dd class="font-bold text-indigo-700">{{ number_format($conLai, 0, ',', '.') }} đ</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Nội dung chuyển khoản</dt><dd class="font-mono font-medium">TT {{ $hoaDon->hopDong->phong->ma_phong }} HD{{ $hoaDon->id }}</dd></div>
                    </dl>

                    <form method="POST" action="{{ route('hoa-don.thanh-toan', $hoaDon) }}" enctype="multipart/form-data" class="mt-4"
                          onsubmit="return confirm('Xác nhận bạn đã chuyển khoản {{ number_format($conLai, 0, ',', '.') }} đ?')">
                        @csrf
                        <p class="mb-2 text-sm text-gray-600">Chuyển khoản xong, chụp ảnh biên lai và bấm "Tôi đã chuyển". Nếu trả tiền mặt, hãy đưa trực tiếp cho chủ trọ.</p>
                        <input type="file" name="anh_bien_lai" required accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-700">
                        <x-input-error :messages="$errors->get('anh_bien_lai')" class="mt-2" />
                        <button class="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Tôi đã chuyển</button>
                    </form>
                </div>
            @endif
        @endif
    </div>
</x-khung-cong-khai>
