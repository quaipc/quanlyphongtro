<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lịch xem phòng</h2>
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
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Liên hệ</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($lichXem as $lx)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $lx->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $lx->khach->ho_ten }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $lx->khach->sdt }}</td>
                                <td class="px-4 py-3">{{ $lx->thoi_gian->format('H:i d/m/Y') }}</td>
                                <td class="px-4 py-3">@include('partials.badge', ['tt' => $lx->trang_thai, 'nhan' => $lx->nhan()])</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($lx->trang_thai === 'cho_xac_nhan')
                                        <form method="POST" action="{{ route('chutro.lich-xem.cap-nhat', $lx) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="da_xac_nhan">
                                            <button class="text-indigo-600 hover:underline">Xác nhận</button>
                                        </form>
                                        <form method="POST" action="{{ route('chutro.lich-xem.cap-nhat', $lx) }}" class="ml-3 inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="tu_choi">
                                            <button class="text-red-600 hover:underline">Từ chối</button>
                                        </form>
                                    @elseif ($lx->trang_thai === 'da_xac_nhan')
                                        <form method="POST" action="{{ route('chutro.lich-xem.cap-nhat', $lx) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="da_xem">
                                            <button class="text-indigo-600 hover:underline">Đã xem phòng</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có lịch xem phòng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lichXem->links() }}
        </div>
    </div>
</x-app-layout>
