<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sự cố</h2>
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
                    @foreach (\App\Models\SuCo::NHAN as $ma => $nhan)
                        <option value="{{ $ma }}" @selected(request('trang_thai') === $ma)>{{ $nhan }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Lọc</button>
                @if (request('trang_thai'))
                    <a href="{{ route('chutro.su-co.index') }}" class="self-center text-sm text-gray-600 underline">Xóa lọc</a>
                @endif
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Sự cố</th>
                            <th class="px-4 py-3">Ngày báo</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($suCo as $s)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $s->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $s->khach->ho_ten }}</td>
                                <td class="px-4 py-3">{{ $s->tieu_de }}</td>
                                <td class="px-4 py-3">{{ $s->ngay_bao->format('H:i d/m/Y') }}</td>
                                <td class="px-4 py-3">@include('partials.badge', ['tt' => $s->trang_thai, 'nhan' => $s->nhan()])</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('chutro.su-co.show', $s) }}" class="text-indigo-600 hover:underline">Xử lý</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có sự cố nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $suCo->links() }}
        </div>
    </div>
</x-app-layout>
