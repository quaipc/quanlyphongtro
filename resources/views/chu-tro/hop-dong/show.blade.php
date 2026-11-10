@php
    $daBanGiao = $hopDong->hoaDon->where('loai', 'ban_giao')->isNotEmpty();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hợp đồng HĐ{{ $hopDong->id }} – phòng {{ $hopDong->phong->ma_phong }}</h2>
            @include('partials.badge', ['tt' => $hopDong->trang_thai, 'nhan' => $hopDong->nhan()])
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
                @include('partials.hop-dong-chi-tiet', ['hopDong' => $hopDong])

                @if ($hopDong->trang_thai === 'cho_ky')
                    <p class="mt-4 text-sm text-gray-600">
                        Giữ chỗ còn đến hết ngày <strong>{{ $hopDong->yeuCau->han_giu->format('d/m/Y') }}</strong>.
                        Quá ngày này mà chưa ký, hợp đồng bị hủy và phòng được mở lại.
                    </p>

                    <form method="POST" action="{{ route('chutro.hop-dong.ky', $hopDong) }}" class="mt-4 flex flex-wrap items-end gap-4"
                          onsubmit="return confirm('Xác nhận hai bên đã ký hợp đồng vào ngày đã chọn?')">
                        @csrf
                        <div>
                            <label for="ngay_ky" class="block text-sm font-medium text-gray-700">Ngày hai bên ký</label>
                            <input id="ngay_ky" type="date" name="ngay_ky" required
                                   value="{{ old('ngay_ky', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}"
                                   class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <x-primary-button>Xác nhận hai bên đã ký</x-primary-button>
                    </form>

                    <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4">
                        <a href="{{ route('chutro.hop-dong.edit', $hopDong) }}" class="text-sm text-indigo-600 underline">Sửa hợp đồng</a>
                        <form method="POST" action="{{ route('chutro.hop-dong.khach-tu-choi-ky', $hopDong) }}"
                              onsubmit="return confirm('Khách từ chối ký: tính là khách tự hủy và MẤT CỌC. Hợp đồng bị hủy và phòng được mở lại. Tiếp tục?')">
                            @csrf
                            <button class="text-sm text-red-600 underline">Khách từ chối ký</button>
                        </form>
                    </div>
                @endif

                @if ($hopDong->trang_thai === 'hieu_luc' && !$daBanGiao && $hopDong->phong->trang_thai === 'da_giu_cho')
                    <div class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-900">
                        Khách chưa nhận phòng. Phải bàn giao trước hết ngày
                        <strong>{{ $hopDong->ngay_bat_dau->format('d/m/Y') }}</strong>, nếu không hợp đồng sẽ bị hủy và phòng được mở lại.
                        <div class="mt-3">
                            <a href="{{ route('chutro.hop-dong.ban-giao', $hopDong) }}"
                               class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                Bàn giao phòng
                            </a>
                        </div>
                    </div>
                @endif

                @if ($daBanGiao && in_array($hopDong->trang_thai, ['hieu_luc', 'cho_thanh_ly']))
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ route('chutro.hoa-don.create', $hopDong) }}"
                           class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-500">
                            Ghi chỉ số và lập hóa đơn định kỳ
                        </a>
                        <a href="{{ route('chutro.hop-dong.thanh-ly', $hopDong) }}"
                           class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500">
                            Thanh lý hợp đồng
                        </a>
                    </div>

                    @if ($hopDong->trang_thai === 'cho_thanh_ly')
                        <p class="mt-3 rounded-md bg-orange-50 p-3 text-sm text-orange-900">
                            Khách đã báo trả phòng, dự kiến trả ngày <strong>{{ $hopDong->ngay_tra_phong->format('d/m/Y') }}</strong>.
                        </p>
                    @endif

                    @if ($hopDong->trang_thai === 'hieu_luc' && !$hopDong->ngay_tra_phong)
                        <form method="POST" action="{{ route('chutro.hop-dong.gia-han', $hopDong) }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4"
                              onsubmit="return confirm('Gia hạn hợp đồng đến ngày đã chọn (hai bên đã ký phụ lục)?')">
                            @csrf
                            <div>
                                <label for="ngay_ket_thuc_moi" class="block text-sm font-medium text-gray-700">Gia hạn đến ngày</label>
                                <input id="ngay_ket_thuc_moi" type="date" name="ngay_ket_thuc_moi" required
                                       min="{{ $hopDong->ngay_ket_thuc->copy()->addDay()->format('Y-m-d') }}"
                                       class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <button class="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Gia hạn</button>
                        </form>
                    @endif
                @endif

                @if (in_array($hopDong->trang_thai, ['cho_ky', 'hieu_luc']) && !$daBanGiao && $hopDong->phong->trang_thai === 'da_giu_cho')
                    <form method="POST" action="{{ route('chutro.yeu-cau.huy-hoan-coc', $hopDong->yeu_cau_id) }}" class="mt-4 border-t border-gray-100 pt-4"
                          onsubmit="return confirm('Chủ trọ hủy hợp đồng và hoàn cọc cho khách? Phòng sẽ được mở lại.')">
                        @csrf
                        <button class="text-sm text-red-600 underline">Chủ trọ hủy hợp đồng, hoàn cọc cho khách</button>
                    </form>
                @endif
            </div>

            @if ($hopDong->hoaDon->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-900">Hóa đơn</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="py-2">Loại</th>
                                <th class="py-2">Kỳ</th>
                                <th class="py-2">Tổng tiền</th>
                                <th class="py-2">Trạng thái</th>
                                <th class="py-2 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($hopDong->hoaDon->sortBy('id') as $hd)
                                <tr>
                                    <td class="py-2">{{ $hd->nhanLoai() }}</td>
                                    <td class="py-2">{{ $hd->tu_ngay->format('d/m/Y') }} – {{ $hd->den_ngay->format('d/m/Y') }}</td>
                                    <td class="py-2">{{ number_format($hd->tong_tien, 0, ',', '.') }} đ</td>
                                    <td class="py-2">@include('partials.badge', ['tt' => $hd->trang_thai, 'nhan' => $hd->nhan()])</td>
                                    <td class="py-2 text-right"><a href="{{ route('chutro.hoa-don.show', $hd) }}" class="text-indigo-600 hover:underline">Xem</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <a href="{{ route('chutro.hop-dong.index') }}" class="text-sm text-gray-600 underline">&larr; Danh sách hợp đồng</a>
        </div>
    </div>
</x-app-layout>
