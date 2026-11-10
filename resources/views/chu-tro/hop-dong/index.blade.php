<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hợp đồng</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Mã</th>
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Thời hạn</th>
                            <th class="px-4 py-3">Giá thuê</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($hopDong as $hd)
                            <tr>
                                <td class="px-4 py-3">HĐ{{ $hd->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $hd->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $hd->khach->ho_ten }}</td>
                                <td class="px-4 py-3">{{ $hd->ngay_bat_dau->format('d/m/Y') }} – {{ $hd->ngay_ket_thuc->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ number_format($hd->gia_thue, 0, ',', '.') }} đ</td>
                                <td class="px-4 py-3">@include('partials.badge', ['tt' => $hd->trang_thai, 'nhan' => $hd->nhan()])</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('chutro.hop-dong.show', $hd) }}" class="text-indigo-600 hover:underline">Xem</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có hợp đồng nào. Lập hợp đồng từ yêu cầu giữ chỗ đã cọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $hopDong->links() }}
        </div>
    </div>
</x-app-layout>
