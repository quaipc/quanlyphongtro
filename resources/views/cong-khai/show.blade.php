@php
    $anh = $phong->anhPhong->map(fn ($a) => asset('storage/' . $a->duong_dan))->values();
    $conTrong = $phong->trang_thai === 'trong';
    $thongBaoKhongTrong = match ($phong->trang_thai) {
        'da_giu_cho' => 'Phòng này đã có người giữ chỗ.',
        'dang_thue', 'cho_tra_phong' => 'Phòng này đang có người thuê.',
        default => 'Phòng này đang bảo trì, tạm thời chưa cho thuê.',
    };
@endphp
<x-khung-cong-khai :title="'Phòng ' . $phong->ma_phong">
    <nav class="text-sm text-gray-500">
        <a href="{{ route('trang-chu') }}" class="hover:text-indigo-600">Trang chủ</a>
        <span class="mx-1">/</span>
        <a href="{{ route('trang-chu') }}#phong-trong" class="hover:text-indigo-600">Phòng trống</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800">Phòng {{ $phong->ma_phong }}</span>
    </nav>

    <div class="mt-4 grid gap-8 lg:grid-cols-3">
        {{-- Ảnh và mô tả --}}
        <div class="space-y-6 lg:col-span-2">
            @if ($anh->isNotEmpty())
                <div x-data="{ hien: '{{ $anh->first() }}' }" class="space-y-3">
                    <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-gray-100 shadow-sm">
                        <img :src="hien" alt="Phòng {{ $phong->ma_phong }}" class="h-full w-full object-cover">
                    </div>
                    @if ($anh->count() > 1)
                        <div class="grid grid-cols-4 gap-3 sm:grid-cols-5">
                            @foreach ($anh as $url)
                                <button type="button" @click="hien = '{{ $url }}'"
                                        :class="hien === '{{ $url }}' ? 'ring-2 ring-indigo-600' : 'opacity-80 hover:opacity-100'"
                                        class="aspect-square overflow-hidden rounded-xl bg-gray-100">
                                    <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex aspect-[16/10] items-center justify-center rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 text-6xl">🏠</div>
            @endif

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold">Mô tả</h2>
                <p class="mt-2 whitespace-pre-line text-gray-700">{{ $phong->mo_ta ?: 'Chủ trọ chưa có mô tả.' }}</p>
            </div>
        </div>

        {{-- Thông tin và hành động --}}
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-2xl font-extrabold">Phòng {{ $phong->ma_phong }}</h1>
                    @if ($conTrong)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">Còn trống</span>
                    @else
                        <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700">Không còn trống</span>
                    @endif
                </div>
                <p class="mt-3 text-3xl font-extrabold text-indigo-700">
                    {{ number_format($phong->gia_thue, 0, ',', '.') }} đ<span class="text-sm font-medium text-gray-500"> / tháng</span>
                </p>
                <dl class="mt-4 divide-y divide-gray-100 text-sm">
                    <div class="flex justify-between py-2"><dt class="text-gray-500">Diện tích</dt><dd class="font-semibold">{{ rtrim(rtrim($phong->dien_tich, '0'), '.') }} m²</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-gray-500">Tiền cọc</dt><dd class="font-semibold">{{ number_format($phong->gia_thue, 0, ',', '.') }} đ (1 tháng)</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-gray-500">Hạn chuyển cọc</dt><dd class="font-semibold">24 giờ sau khi được duyệt</dd></div>
                </dl>
            </div>

            @if (!$conTrong)
                <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-900">
                    {{ $thongBaoKhongTrong }}
                    <a href="{{ route('trang-chu') }}#phong-trong" class="ml-1 font-medium underline">Xem các phòng trống khác</a>
                </div>
            @else
                @guest
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 text-sm shadow-sm">
                        <p class="mb-4 text-gray-700">Đăng nhập để đặt lịch xem phòng hoặc giữ chỗ.</p>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('login') }}" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-500">Đăng nhập</a>
                            <a href="{{ route('register') }}" class="font-medium text-indigo-600 underline">Tạo tài khoản</a>
                        </div>
                    </div>
                @else
                    @if (auth()->user()->laChuTro())
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                            Bạn đang đăng nhập bằng tài khoản chủ trọ nên không đặt lịch hoặc giữ chỗ được.
                            <a href="{{ route('chutro.phong.edit', $phong) }}" class="font-medium text-indigo-600 underline">Sửa phòng này</a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('lich-xem.store', $phong) }}" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            @csrf
                            <h2 class="font-bold">📅 Đặt lịch xem phòng</h2>
                            <input type="datetime-local" name="thoi_gian" required
                                   min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                                   value="{{ old('thoi_gian') }}"
                                   class="mt-3 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <x-input-error :messages="$errors->get('thoi_gian')" class="mt-2" />
                            <button class="mt-3 w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">Gửi lịch xem</button>
                        </form>

                        @if ($yeuCauCuaToi)
                            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-sm shadow-sm">
                                <p class="text-gray-700">Bạn đã gửi yêu cầu giữ chỗ phòng này ({{ $yeuCauCuaToi->nhan() }}).</p>
                                <a href="{{ route('giu-cho.index') }}" class="mt-2 inline-block font-medium text-indigo-600 underline">Xem yêu cầu giữ chỗ của tôi</a>
                            </div>
                        @else
                            <form method="POST" action="{{ route('giu-cho.store', $phong) }}" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6"
                                  onsubmit="return confirm('Gửi yêu cầu giữ chỗ phòng {{ $phong->ma_phong }}?')">
                                @csrf
                                <h2 class="font-bold text-indigo-900">🔒 Giữ chỗ</h2>
                                <p class="mt-1 text-xs text-indigo-800">Chủ trọ sẽ duyệt yêu cầu. Nếu bạn tự hủy sau khi cọc đã được xác nhận thì mất cọc.</p>
                                <button class="mt-3 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Gửi yêu cầu giữ chỗ</button>
                            </form>
                        @endif
                    @endif
                @endguest
            @endif
        </aside>
    </div>
</x-khung-cong-khai>
