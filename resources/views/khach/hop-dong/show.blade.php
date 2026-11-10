<x-khung-cong-khai :title="'Hợp đồng phòng ' . $hopDong->phong->ma_phong">
    <a href="{{ route('hop-dong.index') }}" class="text-sm text-gray-600 underline">&larr; Hợp đồng của tôi</a>

    <div class="mx-auto mt-4 max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-bold">Hợp đồng phòng {{ $hopDong->phong->ma_phong }}</h1>
            @include('partials.badge', ['tt' => $hopDong->trang_thai, 'nhan' => $hopDong->nhan()])
        </div>

        @include('partials.hop-dong-chi-tiet', ['hopDong' => $hopDong])

        @if ($hopDong->trang_thai === 'cho_ky')
            <p class="mt-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                Hợp đồng đang chờ ký. Vui lòng gặp chủ trọ để hai bên ký trước khi hết hạn giữ chỗ.
            </p>
        @endif

        @if ($hopDong->trang_thai === 'hieu_luc' && $hopDong->phong->trang_thai === 'dang_thue')
            <div class="mt-5 border-t border-gray-100 pt-4">
                <a href="{{ route('hop-dong.bao-tra', $hopDong) }}" class="text-sm font-medium text-red-600 hover:underline">Báo trả phòng</a>
            </div>
        @endif

        @if ($hopDong->trang_thai === 'cho_thanh_ly')
            <p class="mt-4 rounded-md bg-orange-50 p-3 text-sm text-orange-900">
                Bạn đã báo trả phòng. Chủ trọ sẽ kiểm tra phòng và thanh lý hợp đồng vào ngày trả
                ({{ $hopDong->ngay_tra_phong->format('d/m/Y') }}).
            </p>
        @endif
    </div>
</x-khung-cong-khai>
