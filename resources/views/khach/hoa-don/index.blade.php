<x-khung-cong-khai title="Hóa đơn của tôi">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-bold">Hóa đơn của tôi</h1>
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="thang" value="{{ request('thang') }}" class="rounded-md border-gray-300 text-sm shadow-sm">
            <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Xem tháng</button>
            @if (request('thang'))
                <a href="{{ route('hoa-don.index') }}" class="text-sm text-gray-600 underline">Xóa lọc</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Phòng</th>
                    <th class="px-4 py-3">Loại</th>
                    <th class="px-4 py-3">Kỳ</th>
                    <th class="px-4 py-3">Tổng tiền</th>
                    <th class="px-4 py-3">Hạn thanh toán</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($hoaDon as $hd)
                    <tr>
                        <td class="px-4 py-3 font-medium">Phòng {{ $hd->hopDong->phong->ma_phong }}</td>
                        <td class="px-4 py-3">{{ $hd->nhanLoai() }}</td>
                        <td class="px-4 py-3">{{ $hd->tu_ngay->format('d/m/Y') }} – {{ $hd->den_ngay->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ number_format($hd->tong_tien, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">{{ $hd->han_thanh_toan->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">@include('partials.badge', ['tt' => $hd->trang_thai, 'nhan' => $hd->nhan()])</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('hoa-don.show', $hd) }}" class="text-indigo-600 hover:underline">Xem chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có hóa đơn nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $hoaDon->links() }}</div>
</x-khung-cong-khai>
