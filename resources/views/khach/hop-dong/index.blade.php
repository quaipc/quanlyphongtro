<x-khung-cong-khai title="Hợp đồng của tôi">
    <h1 class="mb-4 text-2xl font-bold">Hợp đồng của tôi</h1>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Phòng</th>
                    <th class="px-4 py-3">Thời hạn</th>
                    <th class="px-4 py-3">Giá thuê</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($hopDong as $hd)
                    <tr>
                        <td class="px-4 py-3 font-medium">Phòng {{ $hd->phong->ma_phong }}</td>
                        <td class="px-4 py-3">{{ $hd->ngay_bat_dau->format('d/m/Y') }} – {{ $hd->ngay_ket_thuc->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ number_format($hd->gia_thue, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">@include('partials.badge', ['tt' => $hd->trang_thai, 'nhan' => $hd->nhan()])</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('hop-dong.show', $hd) }}" class="text-indigo-600 hover:underline">Xem chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Bạn chưa có hợp đồng nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $hopDong->links() }}</div>
</x-khung-cong-khai>
