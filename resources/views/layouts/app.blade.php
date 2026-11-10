@php
    $nguoiDung = auth()->user();
@endphp
@if ($nguoiDung && $nguoiDung->laChuTro())
    @php
        $ten = config('phongtro.ten');
        $soChoDuyet = \App\Models\YeuCauGiuCho::whereIn('trang_thai', ['cho_duyet', 'cho_xac_nhan'])->count();
        $soLichCho = \App\Models\LichXem::where('trang_thai', 'cho_xac_nhan')->count();
        $soQuaHan = \App\Models\HoaDon::where('trang_thai', 'qua_han')->count();
        $soCocCho = \App\Models\YeuCauGiuCho::where('xu_ly_coc', 'cho_xu_ly')->count();
        $soSuCoMoi = \App\Models\SuCo::where('trang_thai', 'moi')->count();

        // [nhóm => [[tên route, nhãn, mẫu route để tô sáng, biểu tượng, số thông báo, màu số]]]
        $menu = [
            'Tổng quan' => [
                ['chutro.bao-cao.index', 'Báo cáo', 'chutro.bao-cao.*', '📊', 0, 'bg-indigo-500'],
            ],
            'Vận hành' => [
                ['chutro.yeu-cau.index', 'Yêu cầu giữ chỗ', 'chutro.yeu-cau.*', '📝', $soChoDuyet, 'bg-amber-500'],
                ['chutro.lich-xem.index', 'Lịch xem phòng', 'chutro.lich-xem.*', '📅', $soLichCho, 'bg-amber-500'],
                ['chutro.hop-dong.index', 'Hợp đồng', 'chutro.hop-dong.*', '📄', 0, 'bg-indigo-500'],
                ['chutro.hoa-don.index', 'Hóa đơn', 'chutro.hoa-don.*', '🧾', $soQuaHan, 'bg-red-500'],
                ['chutro.coc.index', 'Cọc chờ xử lý', 'chutro.coc.*', '💰', $soCocCho, 'bg-amber-500'],
                ['chutro.su-co.index', 'Sự cố', 'chutro.su-co.*', '🔧', $soSuCoMoi, 'bg-red-500'],
            ],
            'Danh mục' => [
                ['chutro.phong.index', 'Quản lý phòng', 'chutro.phong.*', '🏠', 0, 'bg-indigo-500'],
                ['chutro.dich-vu.index', 'Điện, nước, dịch vụ', 'chutro.dich-vu.*', '⚙️', 0, 'bg-indigo-500'],
            ],
        ];
    @endphp
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Quản lý | {{ $ten }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        <style>[x-cloak] { display: none !important; }</style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 font-sans text-gray-900 antialiased">
        <div x-data="{ moMenu: false }" class="min-h-screen lg:flex">

            <div x-show="moMenu" x-cloak @click="moMenu = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

            {{-- Thanh menu bên trái --}}
            <aside :class="moMenu ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
                <a href="{{ route('chutro.bao-cao.index') }}" class="flex h-16 shrink-0 items-center gap-2 border-b border-slate-800 px-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/></svg>
                    </span>
                    <span class="font-extrabold tracking-tight text-white">{{ $ten }}</span>
                </a>

                <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
                    @foreach ($menu as $nhom => $muc)
                        <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 first:mt-0">{{ $nhom }}</p>
                        @foreach ($muc as [$route, $nhan, $mau, $bieuTuong, $so, $mauSo])
                            @php $dangChon = request()->routeIs($mau); @endphp
                            <a href="{{ route($route) }}"
                               class="mb-0.5 flex items-center justify-between rounded-lg px-3 py-2 font-medium transition {{ $dangChon ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                                <span class="flex items-center gap-3"><span class="w-5 text-center">{{ $bieuTuong }}</span>{{ $nhan }}</span>
                                @if ($so > 0)
                                    <span class="rounded-full {{ $mauSo }} px-2 py-0.5 text-xs font-semibold text-white">{{ $so }}</span>
                                @endif
                            </a>
                        @endforeach
                    @endforeach
                </nav>

                <div class="shrink-0 border-t border-slate-800 p-4 text-sm">
                    <p class="truncate font-semibold text-white">{{ $nguoiDung->ho_ten }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $nguoiDung->email }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                        <a href="{{ route('trang-chu') }}" class="hover:text-white">Xem trang khách</a>
                        <a href="{{ route('profile.edit') }}" class="hover:text-white">Hồ sơ</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button class="text-red-400 hover:text-red-300">Đăng xuất</button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Nội dung --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:hidden">
                    <button type="button" @click="moMenu = true" class="rounded-md p-2 text-gray-600 hover:bg-gray-100" aria-label="Mở menu">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="font-extrabold">{{ $ten }}</span>
                </div>

                @isset($header)
                    <header class="border-b border-gray-200 bg-white">
                        <div class="px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
                    </header>
                @endisset

                <main class="flex-1">{{ $slot }}</main>
            </div>
        </div>
    </body>
    </html>
@else
    {{-- Khách và các tài khoản khác dùng khung chung của website --}}
    <x-khung-cong-khai>
        @isset($header)
            <div class="mb-6">{{ $header }}</div>
        @endisset
        {{ $slot }}
    </x-khung-cong-khai>
@endif
