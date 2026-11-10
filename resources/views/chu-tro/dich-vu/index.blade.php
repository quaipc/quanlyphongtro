<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Giá điện, nước và dịch vụ</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <p class="text-sm text-gray-600">
                Điện và Nước tính theo chỉ số, chỉ sửa được đơn giá. Các dịch vụ khác (rác, internet...) tính theo tháng cho mọi hợp đồng.
                Đơn giá mới chỉ áp dụng cho hóa đơn lập sau.
            </p>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Tên dịch vụ</th>
                            <th class="px-4 py-3">Đơn vị</th>
                            <th class="px-4 py-3">Đơn giá (đồng)</th>
                            <th class="px-4 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($dichVu as $dv)
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($dv->laHeThong())
                                        <span class="font-medium">{{ $dv->ten }}</span>
                                        <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">Hệ thống</span>
                                    @else
                                        <input form="sua-{{ $dv->id }}" type="text" name="ten" value="{{ $dv->ten }}" maxlength="100" required
                                               class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $dv->don_vi }}</td>
                                <td class="px-4 py-3">
                                    <input form="sua-{{ $dv->id }}" type="number" name="don_gia" value="{{ (int) $dv->don_gia }}" min="0" step="1" required
                                           class="w-40 rounded-md border-gray-300 text-sm shadow-sm">
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button form="sua-{{ $dv->id }}" class="text-indigo-600 hover:underline">Lưu</button>
                                    @unless ($dv->laHeThong())
                                        <form method="POST" action="{{ route('chutro.dich-vu.destroy', $dv) }}" class="ml-3 inline"
                                              onsubmit="return confirm('Xóa dịch vụ {{ $dv->ten }}?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Xóa</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Form sửa của từng dòng đặt ngoài bảng (HTML không cho form nằm trực tiếp trong tr); ô nhập liên kết qua thuộc tính form --}}
            @foreach ($dichVu as $dv)
                <form method="POST" action="{{ route('chutro.dich-vu.update', $dv) }}" id="sua-{{ $dv->id }}">
                    @csrf @method('PUT')
                </form>
            @endforeach

            <form method="POST" action="{{ route('chutro.dich-vu.store') }}" class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
                @csrf
                <div>
                    <label for="ten" class="block text-sm font-medium text-gray-700">Thêm dịch vụ (tính theo tháng)</label>
                    <input id="ten" type="text" name="ten" value="{{ old('ten') }}" maxlength="100" required placeholder="Tên dịch vụ"
                           class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="don_gia" class="block text-sm font-medium text-gray-700">Đơn giá / tháng</label>
                    <input id="don_gia" type="number" name="don_gia" value="{{ old('don_gia') }}" min="0" step="1" required
                           class="mt-1 w-40 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Thêm</button>
            </form>
        </div>
    </div>
</x-app-layout>
