<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yêu cầu giữ chỗ</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Liên hệ</th>
                            <th class="px-4 py-3">Ngày gửi</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Ghi chú / Biên lai</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($yeuCau as $yc)
                            @php
                                $dsBienLai = $yc->trang_thai === 'cho_xac_nhan'
                                    ? $yc->thanhToan->where('loai', 'coc')->where('trang_thai', 'cho_xac_nhan')->sortBy('id')
                                    : collect();
                                $daNhan = in_array($yc->trang_thai, ['cho_coc', 'cho_xac_nhan']) ? $yc->tongCocDaXacNhan() : 0;
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 font-medium">{{ $yc->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $yc->khach->ho_ten }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $yc->khach->sdt }}</td>
                                <td class="px-4 py-3">{{ $yc->created_at->format('H:i d/m/Y') }}</td>
                                <td class="px-4 py-3">@include('partials.badge', ['tt' => $yc->trang_thai, 'nhan' => $yc->nhan()])</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($yc->trang_thai === 'cho_coc')
                                        Cọc {{ number_format($yc->tien_coc, 0, ',', '.') }} đ, hạn chuyển {{ $yc->han_chuyen_coc->format('H:i d/m/Y') }}.
                                        @if ($daNhan > 0)
                                            Đã nhận {{ number_format($daNhan, 0, ',', '.') }} đ.
                                        @endif
                                    @elseif ($dsBienLai->isNotEmpty())
                                        <p class="mb-2">Cọc yêu cầu {{ number_format($yc->tien_coc, 0, ',', '.') }} đ, đã nhận {{ number_format($daNhan, 0, ',', '.') }} đ.</p>
                                        @foreach ($dsBienLai as $bl)
                                            <div class="mb-3 rounded-md border border-gray-200 p-3">
                                                <p>
                                                    Khách báo chuyển <strong>{{ number_format($bl->so_tien, 0, ',', '.') }} đ</strong>
                                                    lúc {{ $bl->ngay_tt->format('H:i d/m/Y') }},
                                                    nội dung <span class="font-mono">{{ $bl->noi_dung_ck }}</span>.
                                                    <a href="{{ route('bien-lai.show', $bl) }}" target="_blank" class="text-indigo-600 underline">Xem biên lai</a>
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                                    <form method="POST" action="{{ route('chutro.bien-lai.xac-nhan', $bl) }}" class="flex items-center gap-2"
                                                          onsubmit="return confirm('Xác nhận số tiền thực nhận đúng với sao kê ngân hàng?')">
                                                        @csrf
                                                        <input type="number" name="so_tien" min="1" step="1" value="{{ (int) $bl->so_tien }}"
                                                               class="w-36 rounded-md border-gray-300 text-sm shadow-sm" title="Số tiền thực nhận">
                                                        <button class="text-indigo-600 hover:underline">Xác nhận đã nhận</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('chutro.bien-lai.tu-choi', $bl) }}"
                                                          onsubmit="return confirm('Từ chối biên lai này? Hạn chuyển cọc của khách sẽ được đặt lại 24 giờ.')">
                                                        @csrf
                                                        <button class="text-red-600 hover:underline">Từ chối biên lai</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    @elseif ($yc->trang_thai === 'da_coc')
                                        Đã cọc, giữ phòng đến hết ngày {{ $yc->han_giu->format('d/m/Y') }}
                                    @elseif ($yc->trang_thai === 'cho_duyet' && $yc->phong->trang_thai !== 'trong')
                                        Phòng đang không trống
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($yc->trang_thai === 'cho_duyet')
                                        @if ($yc->phong->trang_thai === 'trong')
                                            <form method="POST" action="{{ route('chutro.yeu-cau.duyet', $yc) }}" class="inline"
                                                  onsubmit="return confirm('Duyệt yêu cầu? Phòng sẽ chuyển sang Đã giữ chỗ, các yêu cầu khác của phòng bị từ chối và khách có 24 giờ để chuyển cọc.')">
                                                @csrf
                                                <button class="text-indigo-600 hover:underline">Duyệt</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('chutro.yeu-cau.tu-choi', $yc) }}" class="ml-3 inline"
                                              onsubmit="return confirm('Từ chối yêu cầu này?')">
                                            @csrf
                                            <button class="text-red-600 hover:underline">Từ chối</button>
                                        </form>
                                    @endif
                                    @if (in_array($yc->trang_thai, ['da_coc', 'da_ky']))
                                        @if ($yc->hopDong)
                                            <a href="{{ route('chutro.hop-dong.show', $yc->hopDong) }}" class="text-indigo-600 hover:underline">Xem hợp đồng</a>
                                        @elseif ($yc->trang_thai === 'da_coc')
                                            <a href="{{ route('chutro.hop-dong.create', $yc) }}" class="font-medium text-indigo-600 hover:underline">Lập hợp đồng</a>
                                        @endif
                                    @endif
                                    @if (in_array($yc->trang_thai, ['da_coc', 'da_ky']) && $yc->phong->trang_thai === 'da_giu_cho')
                                        <form method="POST" action="{{ route('chutro.yeu-cau.huy-hoan-coc', $yc) }}" class="ml-3 inline"
                                              onsubmit="return confirm('Chủ trọ không thể thực hiện hợp đồng: hủy và hoàn cọc cho khách? Phòng sẽ được mở lại.')">
                                            @csrf
                                            <button class="text-red-600 hover:underline">Hủy, hoàn cọc</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có yêu cầu giữ chỗ nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $yeuCau->links() }}
        </div>
    </div>
</x-app-layout>
