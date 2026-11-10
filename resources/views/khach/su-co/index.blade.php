<x-khung-cong-khai title="Sự cố của tôi">
    <h1 class="mb-4 text-2xl font-bold">Sự cố của tôi</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    @if ($phongs->isNotEmpty())
        <form method="POST" action="{{ route('su-co.store') }}" class="mb-6 space-y-4 rounded-lg bg-white p-6 shadow-sm">
            @csrf
            <h2 class="font-semibold">Báo sự cố mới</h2>

            @if ($phongs->count() === 1)
                <input type="hidden" name="phong_id" value="{{ $phongs->first()->id }}">
                <p class="text-sm text-gray-600">Phòng <strong>{{ $phongs->first()->ma_phong }}</strong></p>
            @else
                <div>
                    <label for="phong_id" class="block text-sm font-medium text-gray-700">Phòng</label>
                    <select id="phong_id" name="phong_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        @foreach ($phongs as $p)
                            <option value="{{ $p->id }}" @selected((int) old('phong_id') === $p->id)>Phòng {{ $p->ma_phong }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="tieu_de" class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                <input id="tieu_de" type="text" name="tieu_de" value="{{ old('tieu_de') }}" maxlength="150" required
                       placeholder="Ví dụ: Vòi nước trong nhà tắm bị rò"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
            </div>
            <div>
                <label for="mo_ta" class="block text-sm font-medium text-gray-700">Mô tả chi tiết</label>
                <textarea id="mo_ta" name="mo_ta" rows="4" maxlength="2000"
                          class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">{{ old('mo_ta') }}</textarea>
            </div>
            <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Gửi báo sự cố</button>
        </form>
    @else
        <div class="mb-6 rounded-lg bg-white p-4 text-sm text-gray-600 shadow-sm">
            Bạn chỉ báo được sự cố khi có hợp đồng đang hiệu lực và đã nhận phòng.
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Phòng</th>
                    <th class="px-4 py-3">Sự cố</th>
                    <th class="px-4 py-3">Ngày báo</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($suCo as $s)
                    <tr class="align-top">
                        <td class="px-4 py-3 font-medium">{{ $s->phong->ma_phong }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $s->tieu_de }}</p>
                            @if ($s->mo_ta)<p class="text-gray-500">{{ $s->mo_ta }}</p>@endif
                            @if ($s->trang_thai === 'cho_khach_xac_nhan' || $s->trang_thai === 'hoan_tat')
                                @if ((int) $s->chi_phi > 0 && $s->loi_do_khach)
                                    <p class="mt-1 text-xs text-red-700">
                                        Chủ trọ ghi nhận lỗi do khách, phí sửa {{ number_format($s->chi_phi, 0, ',', '.') }} đ
                                        sẽ được tính vào hóa đơn kỳ sau khi sự cố hoàn tất.
                                    </p>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $s->ngay_bao->format('H:i d/m/Y') }}</td>
                        <td class="px-4 py-3">@include('partials.badge', ['tt' => $s->trang_thai, 'nhan' => $s->nhan()])</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($s->trang_thai === 'cho_khach_xac_nhan')
                                <form method="POST" action="{{ route('su-co.xac-nhan', $s) }}" class="inline"
                                      onsubmit="return confirm('Xác nhận sự cố đã được sửa xong?')">
                                    @csrf
                                    <button class="text-green-700 hover:underline">Đã sửa xong</button>
                                </form>
                                <form method="POST" action="{{ route('su-co.chua-hai-long', $s) }}" class="ml-3 inline">
                                    @csrf
                                    <button class="text-red-600 hover:underline">Chưa hài lòng</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Bạn chưa báo sự cố nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $suCo->links() }}</div>
</x-khung-cong-khai>
