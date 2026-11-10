<x-khung-cong-khai :title="'Báo trả phòng ' . $hopDong->phong->ma_phong">
    <a href="{{ route('hop-dong.show', $hopDong) }}" class="text-sm text-gray-600 underline">&larr; Hợp đồng phòng {{ $hopDong->phong->ma_phong }}</a>

    <div class="mx-auto mt-4 max-w-xl space-y-4">
        <form method="POST" action="{{ route('hop-dong.bao-tra.store', $hopDong) }}" class="rounded-lg bg-white p-6 shadow-sm">
            @csrf
            <h1 class="text-xl font-bold">Báo trả phòng {{ $hopDong->phong->ma_phong }}</h1>
            <p class="mt-1 text-sm text-gray-600">Hợp đồng kết thúc ngày <strong>{{ $hopDong->ngay_ket_thuc->format('d/m/Y') }}</strong>.</p>

            <div class="mt-4">
                <label for="ngay_tra" class="block text-sm font-medium text-gray-700">Ngày trả phòng dự kiến</label>
                <input id="ngay_tra" type="date" name="ngay_tra" required
                       value="{{ old('ngay_tra') }}" min="{{ now()->format('Y-m-d') }}" max="{{ $hopDong->ngay_ket_thuc->format('Y-m-d') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                <p class="mt-1 text-xs text-gray-500">
                    Ngày trả không được sau ngày kết thúc hợp đồng. Muốn ở thêm, hãy nhờ chủ trọ gia hạn hợp đồng trước.
                </p>
                <x-input-error :messages="$errors->get('ngay_tra')" class="mt-2" />
            </div>

            <p class="mt-4 rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                Lưu ý: nếu trả phòng giữa chu kỳ thu tiền, tiền phòng đã trả trước cho những ngày không ở <strong>không được hoàn</strong>.
                Điện, nước và dịch vụ vẫn tính theo thực tế đến ngày trả.
            </p>

            @if (session('canh_bao'))
                <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Cảnh báo mất cọc</p>
                    <p class="mt-1">
                        Bạn trả phòng sớm hơn ngày kết thúc hợp đồng mà báo trước chưa đủ 1 tháng.
                        Khi thanh lý, tiền cọc sẽ dùng trả nợ và khoản hư hỏng trước, phần còn lại sẽ <strong>bị mất, không hoàn</strong>.
                    </p>
                </div>
                <input type="hidden" name="xac_nhan" value="1">
                <button class="mt-4 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Tôi hiểu, vẫn báo trả phòng</button>
            @else
                <button class="mt-4 rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Gửi báo trả phòng</button>
            @endif
        </form>
    </div>
</x-khung-cong-khai>
