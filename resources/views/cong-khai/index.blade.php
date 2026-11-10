@php
    $mucGia = [2000000 => 'Dưới 2 triệu', 3000000 => 'Dưới 3 triệu', 4000000 => 'Dưới 4 triệu', 5000000 => 'Dưới 5 triệu', 7000000 => 'Dưới 7 triệu'];
    $mucDienTich = [15 => 'Từ 15 m²', 20 => 'Từ 20 m²', 25 => 'Từ 25 m²', 30 => 'Từ 30 m²'];
    $buoc = [
        ['1', 'Tìm và xem phòng', 'Lọc theo giá, diện tích, xem ảnh và mô tả chi tiết từng phòng.'],
        ['2', 'Đặt lịch hoặc giữ chỗ', 'Đăng ký tài khoản, hẹn giờ xem phòng hoặc gửi yêu cầu giữ chỗ.'],
        ['3', 'Chuyển cọc, ký hợp đồng', 'Chủ trọ duyệt, bạn chuyển cọc trong 24 giờ rồi ký hợp đồng.'],
        ['4', 'Nhận phòng và thanh toán', 'Theo dõi hóa đơn hằng tháng, báo sự cố và trả phòng ngay trên website.'],
    ];
@endphp
<x-khung-cong-khai title="Phòng trống">
    <x-slot name="hero">
        <section class="bg-gradient-to-br from-indigo-700 via-indigo-600 to-sky-500 text-white">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:py-20">
                <p class="text-sm font-semibold uppercase tracking-widest text-indigo-200">{{ config('phongtro.ten') }}</p>
                <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight sm:text-5xl">Tìm phòng trọ ưng ý, thuê nhanh và minh bạch</h1>
                <p class="mt-4 max-w-xl text-indigo-100">Xem phòng đang trống, đặt lịch xem, giữ chỗ và quản lý hợp đồng, hóa đơn trực tuyến.</p>

                <form method="GET" action="{{ route('trang-chu') }}#phong-trong"
                      class="mt-8 grid gap-3 rounded-2xl bg-white p-3 text-gray-900 shadow-xl sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_auto]">
                    <div class="flex items-center gap-2 rounded-xl border border-gray-200 px-3">
                        <svg class="h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                        <input type="text" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Mã phòng hoặc từ khóa (ban công, gác lửng...)"
                               class="w-full border-0 bg-transparent py-3 text-sm focus:ring-0">
                    </div>
                    <select name="gia_den" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Mọi mức giá</option>
                        @foreach ($mucGia as $gia => $nhan)
                            <option value="{{ $gia }}" @selected((int) request('gia_den') === $gia)>{{ $nhan }}</option>
                        @endforeach
                    </select>
                    <select name="dt_tu" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Mọi diện tích</option>
                        @foreach ($mucDienTich as $dt => $nhan)
                            <option value="{{ $dt }}" @selected((int) request('dt_tu') === $dt)>{{ $nhan }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Tìm phòng</button>
                </form>

                <p class="mt-4 text-sm text-indigo-100">
                    <span class="font-semibold text-white">{{ $tongTrong }}</span> phòng đang trống
                </p>
            </div>
        </section>
    </x-slot>

    {{-- Danh sách phòng --}}
    <section id="phong-trong" class="scroll-mt-20">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold">Phòng đang trống</h2>
                <p class="text-sm text-gray-500">
                    @if ($phongs->total() > 0) Tìm thấy {{ $phongs->total() }} phòng. @else Chọn phòng để xem chi tiết, đặt lịch xem hoặc giữ chỗ. @endif
                </p>
            </div>
        </div>

        <details class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm" @if ($coLoc || request()->filled('sap_xep')) open @endif>
            <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-gray-700">Bộ lọc nâng cao và sắp xếp</summary>
            <form method="GET" action="{{ route('trang-chu') }}#phong-trong" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label for="q" class="block text-xs font-medium text-gray-600">Từ khóa</label>
                    <input id="q" type="text" name="q" value="{{ request('q') }}" maxlength="100" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="gia_tu" class="block text-xs font-medium text-gray-600">Giá từ (đồng)</label>
                    <input id="gia_tu" type="number" name="gia_tu" min="0" step="100000" value="{{ request('gia_tu') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="gia_den2" class="block text-xs font-medium text-gray-600">Giá đến (đồng)</label>
                    <input id="gia_den2" type="number" name="gia_den" min="0" step="100000" value="{{ request('gia_den') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="dt_tu2" class="block text-xs font-medium text-gray-600">Diện tích từ (m²)</label>
                    <input id="dt_tu2" type="number" name="dt_tu" min="0" step="1" value="{{ request('dt_tu') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="dt_den" class="block text-xs font-medium text-gray-600">Diện tích đến (m²)</label>
                    <input id="dt_den" type="number" name="dt_den" min="0" step="1" value="{{ request('dt_den') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div class="lg:col-span-2">
                    <label for="sap_xep" class="block text-xs font-medium text-gray-600">Sắp xếp</label>
                    <select id="sap_xep" name="sap_xep" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        @foreach (\App\Http\Controllers\PhongCongKhaiController::SAP_XEP as $ma => [, , $nhan])
                            <option value="{{ $ma }}" @selected(request('sap_xep', 'gia_tang') === $ma)>{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3 lg:col-span-4">
                    <button class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Áp dụng</button>
                    @if ($coLoc || request()->filled('sap_xep'))
                        <a href="{{ route('trang-chu') }}#phong-trong" class="text-sm text-gray-600 underline">Xóa bộ lọc</a>
                    @endif
                </div>
            </form>
        </details>

        @if ($errors->any())
            <p class="mb-4 text-sm text-red-700">{{ $errors->first() }}</p>
        @endif

        @if ($phongs->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                @if ($coLoc)
                    <p class="font-medium text-gray-800">Không có phòng trống nào phù hợp với bộ lọc của bạn.</p>
                    @if ($goiY)
                        <p class="mt-3 text-sm text-gray-600">Bạn có thể thử nới bộ lọc:</p>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            @foreach ($goiY as $g)
                                <a href="{{ route('trang-chu', collect(request()->query())->except(array_merge($g['bo'], ['page']))->all()) }}#phong-trong"
                                   class="rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                                    {{ $g['nhan'] }} ({{ $g['so'] }} phòng)
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if ($tongTrong > 0)
                        <p class="mt-4 text-sm text-gray-500">Hiện có {{ $tongTrong }} phòng trống,
                            <a href="{{ route('trang-chu') }}#phong-trong" class="text-indigo-600 underline">xem tất cả</a>.</p>
                    @endif
                @else
                    <p class="text-gray-500">Hiện chưa có phòng trống. Vui lòng quay lại sau.</p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($phongs as $phong)
                    <a href="{{ route('phong.chi-tiet', $phong) }}"
                       class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                            @if ($phong->anhPhong->isNotEmpty())
                                <img src="{{ asset('storage/' . $phong->anhPhong->first()->duong_dan) }}" alt="Phòng {{ $phong->ma_phong }}"
                                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-4xl">🏠</div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-full bg-green-600 px-2.5 py-1 text-xs font-semibold text-white shadow">Còn trống</span>
                            <span class="absolute bottom-3 right-3 rounded-lg bg-white/95 px-3 py-1 text-sm font-bold text-indigo-700 shadow">
                                {{ number_format($phong->gia_thue, 0, ',', '.') }} đ<span class="text-xs font-medium text-gray-500"> / tháng</span>
                            </span>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold group-hover:text-indigo-600">Phòng {{ $phong->ma_phong }}</h3>
                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ rtrim(rtrim($phong->dien_tich, '0'), '.') }} m²</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($phong->mo_ta ?: 'Chưa có mô tả.', 80) }}</p>
                            <p class="mt-3 text-sm font-semibold text-indigo-600">Xem chi tiết &rarr;</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $phongs->links() }}</div>
        @endif
    </section>

    {{-- Cách thuê phòng --}}
    <section id="cach-thue" class="mt-16 scroll-mt-20">
        <h2 class="text-2xl font-bold">Cách thuê phòng</h2>
        <p class="text-sm text-gray-500">Bốn bước đơn giản, theo dõi mọi thứ trên website.</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($buoc as [$so, $tieuDe, $moTa])
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">{{ $so }}</span>
                    <h3 class="mt-3 font-semibold">{{ $tieuDe }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $moTa }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Liên hệ --}}
    <section id="lien-he" class="mt-16 scroll-mt-20 rounded-2xl bg-indigo-50 p-8">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-bold text-indigo-900">Cần hỗ trợ thêm?</h2>
                <ul class="mt-3 space-y-1 text-sm text-indigo-900">
                    @if (config('phongtro.dia_chi'))<li>📍 {{ config('phongtro.dia_chi') }}</li>@endif
                    @if (config('phongtro.sdt'))<li>📞 {{ config('phongtro.sdt') }}</li>@endif
                    @if (config('phongtro.email'))<li>✉️ {{ config('phongtro.email') }}</li>@endif
                    @if (config('phongtro.gio_lam_viec'))<li>🕒 {{ config('phongtro.gio_lam_viec') }}</li>@endif
                    @unless (config('phongtro.dia_chi') || config('phongtro.sdt') || config('phongtro.email'))
                        <li>Đăng ký tài khoản để đặt lịch xem phòng và gửi yêu cầu trực tiếp cho chủ trọ.</li>
                    @endunless
                </ul>
            </div>
            @guest
                <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Tạo tài khoản miễn phí</a>
            @endguest
        </div>
    </section>
</x-khung-cong-khai>
