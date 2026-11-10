<x-khung-cong-khai title="Chuyển cọc">
    <a href="{{ route('giu-cho.index') }}" class="text-sm text-gray-600 underline">&larr; Quay lại yêu cầu giữ chỗ</a>

    <div class="mx-auto mt-4 max-w-2xl space-y-6">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold">Chuyển cọc phòng {{ $yeuCau->phong->ma_phong }}</h1>
            <p class="mt-1 text-sm text-red-700">
                Hạn chuyển cọc: <strong>{{ $yeuCau->han_chuyen_coc->format('H:i d/m/Y') }}</strong>.
                Sau hạn này hệ thống sẽ không nhận thông báo chuyển tiền và phòng sẽ được mở lại.
            </p>

            @if ($conThieu < (int) $yeuCau->tien_coc)
                <p class="mt-3 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                    Tổng tiền cọc là {{ number_format($yeuCau->tien_coc, 0, ',', '.') }} đ, chủ trọ đã xác nhận
                    {{ number_format($yeuCau->tien_coc - $conThieu, 0, ',', '.') }} đ. Bạn chỉ cần chuyển phần còn thiếu.
                </p>
            @endif

            <dl class="mt-5 divide-y divide-gray-100 text-sm">
                <div class="flex justify-between py-2"><dt class="text-gray-500">Ngân hàng</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.ngan_hang') }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Số tài khoản</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.so_tai_khoan') }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Chủ tài khoản</dt><dd class="font-medium">{{ config('phongtro.chuyen_khoan.chu_tai_khoan') }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Số tiền cần chuyển</dt><dd class="font-bold text-indigo-700">{{ number_format($conThieu, 0, ',', '.') }} đ</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Nội dung chuyển khoản</dt><dd class="font-mono font-medium">{{ $noiDung }}</dd></div>
            </dl>
        </div>

        <form method="POST" action="{{ route('giu-cho.chuyen-coc.store', $yeuCau) }}" enctype="multipart/form-data"
              class="rounded-lg bg-white p-6 shadow-sm"
              onsubmit="return confirm('Xác nhận bạn đã chuyển khoản {{ number_format($conThieu, 0, ',', '.') }} đ?')">
            @csrf
            <h2 class="mb-1 font-semibold">Tôi đã chuyển</h2>
            <p class="mb-3 text-sm text-gray-600">Chụp ảnh biên lai chuyển khoản rồi gửi lên để chủ trọ đối chiếu.</p>

            <input type="file" name="anh_bien_lai" required accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-700">
            <x-input-error :messages="$errors->get('anh_bien_lai')" class="mt-2" />

            <button class="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Tôi đã chuyển</button>
        </form>
    </div>
</x-khung-cong-khai>
