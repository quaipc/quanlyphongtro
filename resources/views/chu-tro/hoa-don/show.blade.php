@php
    $nhanTt = ['cho_xac_nhan' => 'Chờ xác nhận', 'da_xac_nhan' => 'Đã xác nhận', 'tu_choi' => 'Bị từ chối'];
    $nhanHt = ['chuyen_khoan' => 'Chuyển khoản', 'tien_mat' => 'Tiền mặt', 'tru_coc' => 'Trừ cọc'];
    $conLai = $hoaDon->conLai();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hóa đơn #{{ $hoaDon->id }} ({{ $hoaDon->nhanLoai() }}) – phòng {{ $hoaDon->hopDong->phong->ma_phong }}</h2>
            @include('partials.badge', ['tt' => $hoaDon->trang_thai, 'nhan' => $hoaDon->nhan()])
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <p class="text-sm text-gray-600">
                    Khách: <strong>{{ $hoaDon->hopDong->khach->ho_ten }}</strong> ·
                    Kỳ {{ $hoaDon->tu_ngay->format('d/m/Y') }} – {{ $hoaDon->den_ngay->format('d/m/Y') }} ·
                    Hạn thanh toán <strong>{{ $hoaDon->han_thanh_toan->format('d/m/Y') }}</strong>
                </p>
                @if ($hoaDon->canNhacNo())
                    <p class="mt-2 rounded-md bg-red-50 p-3 text-sm text-red-800">
                        Hóa đơn quá hạn {{ $hoaDon->soNgayQuaHan() }} ngày: đến lúc nhắc nợ khách (liên hệ {{ $hoaDon->hopDong->khach->sdt }}).
                    </p>
                @endif

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

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-semibold text-gray-900">Thanh toán</h3>
                @forelse ($hoaDon->thanhToan->where('loai', 'hoa_don')->sortBy('id') as $tt)
                    <div class="mb-3 rounded-md border border-gray-200 p-3 text-sm">
                        <p>
                            {{ number_format($tt->so_tien, 0, ',', '.') }} đ · {{ $nhanHt[$tt->hinh_thuc] ?? $tt->hinh_thuc }} ·
                            {{ $tt->ngay_tt->format('H:i d/m/Y') }}
                            @include('partials.badge', ['tt' => $tt->trang_thai, 'nhan' => $nhanTt[$tt->trang_thai] ?? $tt->trang_thai])
                            @if ($tt->anh_bien_lai)
                                <a href="{{ route('bien-lai.show', $tt) }}" target="_blank" class="ml-2 text-indigo-600 underline">Xem biên lai</a>
                            @endif
                        </p>
                        @if ($tt->noi_dung_ck)
                            <p class="mt-1 text-gray-500">Nội dung chuyển khoản: <span class="font-mono">{{ $tt->noi_dung_ck }}</span></p>
                        @endif
                        @if ($tt->trang_thai === 'cho_xac_nhan')
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('chutro.thanh-toan.xac-nhan', $tt) }}" class="flex items-center gap-2"
                                      onsubmit="return confirm('Xác nhận số tiền thực nhận đúng với sao kê ngân hàng?')">
                                    @csrf
                                    <input type="number" name="so_tien" min="1" step="1" value="{{ min((int) $tt->so_tien, $conLai) }}"
                                           class="w-36 rounded-md border-gray-300 text-sm shadow-sm" title="Số tiền thực nhận">
                                    <button class="text-indigo-600 hover:underline">Xác nhận đã nhận</button>
                                </form>
                                <form method="POST" action="{{ route('chutro.thanh-toan.tu-choi', $tt) }}"
                                      onsubmit="return confirm('Từ chối biên lai này?')">
                                    @csrf
                                    <button class="text-red-600 hover:underline">Từ chối biên lai</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có khoản thanh toán nào.</p>
                @endforelse

                @if ($hoaDon->choPhepThanhToan() && $conLai > 0)
                    <form method="POST" action="{{ route('chutro.hoa-don.thu-tien-mat', $hoaDon) }}" class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4"
                          onsubmit="return confirm('Ghi nhận đã thu tiền mặt?')">
                        @csrf
                        <label class="text-sm text-gray-700" for="so_tien_mat">Khách trả tiền mặt:</label>
                        <input id="so_tien_mat" type="number" name="so_tien" min="1" max="{{ $conLai }}" step="1" value="{{ $conLai }}"
                               class="w-40 rounded-md border-gray-300 text-sm shadow-sm">
                        <button class="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Ghi nhận thu</button>
                    </form>
                @endif
            </div>

            <div class="flex gap-4 text-sm">
                <a href="{{ route('chutro.hoa-don.index') }}" class="text-gray-600 underline">&larr; Danh sách hóa đơn</a>
                <a href="{{ route('chutro.hop-dong.show', $hoaDon->hopDong) }}" class="text-gray-600 underline">Hợp đồng HĐ{{ $hoaDon->hop_dong_id }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
