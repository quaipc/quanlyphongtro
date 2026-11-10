<x-khung-cong-khai title="Yêu cầu giữ chỗ">
    <h1 class="mb-4 text-2xl font-bold">Yêu cầu giữ chỗ của tôi</h1>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Phòng</th>
                    <th class="px-4 py-3">Ngày gửi</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Ghi chú</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($yeuCau as $yc)
                    @php $conHan = $yc->trang_thai === 'cho_coc' && $yc->han_chuyen_coc && $yc->han_chuyen_coc->isFuture(); @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">Phòng {{ $yc->phong->ma_phong }}</td>
                        <td class="px-4 py-3">{{ $yc->created_at->format('H:i d/m/Y') }}</td>
                        <td class="px-4 py-3">@include('partials.badge', ['tt' => $yc->trang_thai, 'nhan' => $yc->nhan()])</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if ($yc->trang_thai === 'cho_coc')
                                Tiền cọc <strong>{{ number_format($yc->tien_coc, 0, ',', '.') }} đ</strong>.
                                @if ($yc->tienCocConThieu() < (int) $yc->tien_coc)
                                    Đã xác nhận {{ number_format($yc->tien_coc - $yc->tienCocConThieu(), 0, ',', '.') }} đ,
                                    còn thiếu <strong>{{ number_format($yc->tienCocConThieu(), 0, ',', '.') }} đ</strong>.
                                @endif
                                Hạn chuyển cọc: <strong>{{ $yc->han_chuyen_coc->format('H:i d/m/Y') }}</strong>.
                                @unless ($conHan)
                                    <span class="text-red-700">Đã quá hạn, phòng sẽ được mở lại.</span>
                                @endunless
                            @elseif ($yc->trang_thai === 'cho_xac_nhan')
                                Đã gửi biên lai, chờ chủ trọ xác nhận đã nhận cọc.
                            @elseif ($yc->trang_thai === 'da_coc')
                                Đã nhận cọc. Phòng được giữ đến hết ngày <strong>{{ $yc->han_giu->format('d/m/Y') }}</strong> để ký hợp đồng.
                            @elseif ($yc->trang_thai === 'het_han')
                                Quá hạn, phòng đã được mở lại.
                            @elseif ($yc->trang_thai === 'bi_tu_choi')
                                Chủ trọ đã từ chối yêu cầu này.
                            @endif

                            @if ($yc->xu_ly_coc === 'cho_xu_ly')
                                <p class="mt-1 text-xs text-yellow-700">Khoản cọc đang chờ chủ trọ xử lý (giữ lại hoặc hoàn cho bạn).</p>
                            @elseif ($yc->xu_ly_coc === 'mat_coc')
                                <p class="mt-1 text-xs text-red-700">Khoản cọc không được hoàn.</p>
                            @elseif ($yc->xu_ly_coc === 'da_hoan')
                                <p class="mt-1 text-xs text-green-700">Chủ trọ đã hoàn cọc cho bạn.</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($conHan)
                                <a href="{{ route('giu-cho.chuyen-coc', $yc) }}" class="mr-3 font-medium text-indigo-600 hover:underline">Tôi đã chuyển</a>
                            @endif
                            @php
                                // Hủy được khi chưa nhận phòng; hủy sau khi cọc đã được xác nhận thì mất cọc (QT3)
                                $huyDuoc = in_array($yc->trang_thai, ['cho_duyet', 'cho_coc', 'cho_xac_nhan', 'da_coc'], true)
                                    || ($yc->trang_thai === 'da_ky' && $yc->phong->trang_thai === 'da_giu_cho');
                                $canhBao = match ($yc->trang_thai) {
                                    'cho_xac_nhan' => 'Bạn đã báo chuyển cọc. Nếu hủy bây giờ, chủ trọ sẽ xử lý hoàn lại tiền cho bạn. Tiếp tục?',
                                    'da_coc', 'da_ky' => 'Cọc đã được xác nhận: nếu hủy bạn sẽ MẤT CỌC. Tiếp tục?',
                                    default => 'Hủy yêu cầu giữ chỗ này?',
                                };
                            @endphp
                            @if ($huyDuoc)
                                <form method="POST" action="{{ route('giu-cho.huy', $yc) }}" class="inline"
                                      onsubmit="return confirm('{{ $canhBao }}')">
                                    @csrf @method('PATCH')
                                    <button class="text-red-600 hover:underline">Hủy</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Bạn chưa gửi yêu cầu giữ chỗ nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $yeuCau->links() }}</div>
</x-khung-cong-khai>
