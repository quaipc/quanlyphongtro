<x-khung-cong-khai title="Lịch xem của tôi">
    <h1 class="mb-4 text-2xl font-bold">Lịch xem phòng của tôi</h1>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Phòng</th>
                    <th class="px-4 py-3">Thời gian</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($lichXem as $lx)
                    <tr>
                        <td class="px-4 py-3 font-medium">Phòng {{ $lx->phong->ma_phong }}</td>
                        <td class="px-4 py-3">{{ $lx->thoi_gian->format('H:i d/m/Y') }}</td>
                        <td class="px-4 py-3">@include('partials.badge', ['tt' => $lx->trang_thai, 'nhan' => $lx->nhan()])</td>
                        <td class="px-4 py-3 text-right">
                            @if (in_array($lx->trang_thai, ['cho_xac_nhan', 'da_xac_nhan']) && $lx->thoi_gian->isFuture())
                                <form method="POST" action="{{ route('lich-xem.huy', $lx) }}" class="inline"
                                      onsubmit="return confirm('Hủy lịch xem phòng này?')">
                                    @csrf @method('PATCH')
                                    <button class="text-red-600 hover:underline">Hủy lịch</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Bạn chưa đặt lịch xem phòng nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $lichXem->links() }}</div>
</x-khung-cong-khai>
