<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hóa đơn</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <form method="GET" class="flex flex-wrap gap-3 rounded-lg bg-white p-4 shadow-sm">
                <select name="trang_thai" class="rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach (\App\Models\HoaDon::NHAN_TRANG_THAI as $ma => $nhan)
                        <option value="{{ $ma }}" @selected(request('trang_thai') === $ma)>{{ $nhan }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Lọc</button>
                @if (request('trang_thai'))
                    <a href="{{ route('chutro.hoa-don.index') }}" class="self-center text-sm text-gray-600 underline">Xóa lọc</a>
                @endif
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Mã</th>
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Loại</th>
                            <th class="px-4 py-3">Kỳ</th>
                            <th class="px-4 py-3">Tổng tiền</th>
                            <th class="px-4 py-3">Hạn</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($hoaDon as $hd)
                            <tr>
                                <td class="px-4 py-3">#{{ $hd->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $hd->hopDong->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $hd->hopDong->khach->ho_ten }}</td>
                                <td class="px-4 py-3">{{ $hd->nhanLoai() }}</td>
                                <td class="px-4 py-3">{{ $hd->tu_ngay->format('d/m/Y') }} – {{ $hd->den_ngay->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ number_format($hd->tong_tien, 0, ',', '.') }} đ</td>
                                <td class="px-4 py-3">{{ $hd->han_thanh_toan->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    @include('partials.badge', ['tt' => $hd->trang_thai, 'nhan' => $hd->nhan()])
                                    @if ($hd->canNhacNo())
                                        <span class="ml-1 text-xs font-medium text-red-700">Cần nhắc nợ ({{ $hd->soNgayQuaHan() }} ngày)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('chutro.hoa-don.show', $hd) }}" class="text-indigo-600 hover:underline">Xem</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Chưa có hóa đơn nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $hoaDon->links() }}
        </div>
    </div>
</x-app-layout>
