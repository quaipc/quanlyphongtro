<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quản lý phòng</h2>
            <a href="{{ route('chutro.phong.create') }}"
               class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                + Thêm phòng
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <form method="GET" class="flex flex-wrap gap-3 rounded-lg bg-white p-4 shadow-sm">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm theo mã phòng..."
                       class="rounded-md border-gray-300 text-sm shadow-sm">
                <select name="trang_thai" class="rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach (\App\Models\Phong::NHAN_TRANG_THAI as $ma => $nhan)
                        <option value="{{ $ma }}" @selected(request('trang_thai') === $ma)>{{ $nhan }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Lọc</button>
                @if (request()->hasAny(['q', 'trang_thai']))
                    <a href="{{ route('chutro.phong.index') }}" class="self-center text-sm text-gray-600 underline">Xóa lọc</a>
                @endif
            </form>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Ảnh</th>
                            <th class="px-4 py-3">Mã phòng</th>
                            <th class="px-4 py-3">Diện tích</th>
                            <th class="px-4 py-3">Giá thuê / tháng</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($phongs as $phong)
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($phong->anhPhong->isNotEmpty())
                                        <img src="{{ asset('storage/' . $phong->anhPhong->first()->duong_dan) }}"
                                             class="h-12 w-16 rounded object-cover" alt="">
                                    @else
                                        <div class="flex h-12 w-16 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">Chưa có</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ rtrim(rtrim($phong->dien_tich, '0'), '.') }} m²</td>
                                <td class="px-4 py-3">{{ number_format($phong->gia_thue, 0, ',', '.') }} đ</td>
                                <td class="px-4 py-3">@include('phong._badge', ['trangThai' => $phong->trang_thai])</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('chutro.phong.edit', $phong) }}" class="text-indigo-600 hover:underline">Sửa</a>
                                    <form method="POST" action="{{ route('chutro.phong.destroy', $phong) }}" class="inline"
                                          onsubmit="return confirm('Xóa phòng {{ $phong->ma_phong }}?')">
                                        @csrf @method('DELETE')
                                        <button class="ml-3 text-red-600 hover:underline">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có phòng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $phongs->links() }}
        </div>
    </div>
</x-app-layout>
