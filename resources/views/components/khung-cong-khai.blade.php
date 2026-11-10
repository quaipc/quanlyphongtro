@props(['title' => null])
@php
    $ten = config('phongtro.ten');
    $nguoiDung = auth()->user();
    $lienKetKhach = [
        ['lich-xem.index', 'Lịch xem của tôi'],
        ['giu-cho.index', 'Yêu cầu giữ chỗ'],
        ['hop-dong.index', 'Hợp đồng của tôi'],
        ['hoa-don.index', 'Hóa đơn của tôi'],
        ['su-co.index', 'Sự cố của tôi'],
        ['profile.edit', 'Hồ sơ cá nhân'],
    ];
    $thongBaoNoiBo = ['profile-updated', 'password-updated', 'verification-link-sent'];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' | ' : '' }}{{ $ten }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans text-gray-900 antialiased">

    {{-- Thanh menu --}}
    <header x-data="{ moMenu: false, moTaiKhoan: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
            <a href="{{ route('trang-chu') }}" class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/></svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight">{{ $ten }}</span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium text-gray-600 md:flex">
                <a href="{{ route('trang-chu') }}" class="hover:text-indigo-600">Trang chủ</a>
                <a href="{{ route('trang-chu') }}#phong-trong" class="hover:text-indigo-600">Phòng trống</a>
                <a href="{{ route('trang-chu') }}#cach-thue" class="hover:text-indigo-600">Cách thuê</a>
                <a href="{{ route('trang-chu') }}#lien-he" class="hover:text-indigo-600">Liên hệ</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                @auth
                    @if ($nguoiDung->laChuTro())
                        <a href="{{ route('chutro.bao-cao.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Khu quản lý</a>
                    @else
                        <div class="relative" @click.outside="moTaiKhoan = false">
                            <button type="button" @click="moTaiKhoan = !moTaiKhoan"
                                    class="flex items-center gap-2 rounded-full border border-gray-200 py-1 pl-1 pr-3 text-sm hover:bg-gray-50">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($nguoiDung->ho_ten, 0, 1)) }}</span>
                                <span class="max-w-[8rem] truncate font-medium">{{ $nguoiDung->ho_ten }}</span>
                                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div x-show="moTaiKhoan" x-cloak x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-sm shadow-lg">
                                @foreach ($lienKetKhach as [$route, $nhan])
                                    <a href="{{ route($route) }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">{{ $nhan }}</a>
                                @endforeach
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                                    @csrf
                                    <button class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-50">Đăng xuất</button>
                                </form>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Đăng ký</a>
                @endauth
            </div>

            <button type="button" @click="moMenu = !moMenu" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Mở menu">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        {{-- Menu trên điện thoại --}}
        <div x-show="moMenu" x-cloak class="border-t border-gray-100 bg-white px-4 py-3 text-sm md:hidden">
            <a href="{{ route('trang-chu') }}" class="block py-2 font-medium">Trang chủ</a>
            <a href="{{ route('trang-chu') }}#phong-trong" class="block py-2 font-medium">Phòng trống</a>
            <a href="{{ route('trang-chu') }}#cach-thue" class="block py-2 font-medium">Cách thuê</a>
            <a href="{{ route('trang-chu') }}#lien-he" class="block py-2 font-medium">Liên hệ</a>
            <div class="mt-2 border-t border-gray-100 pt-2">
                @auth
                    @if ($nguoiDung->laChuTro())
                        <a href="{{ route('chutro.bao-cao.index') }}" class="block py-2 font-semibold text-indigo-600">Khu quản lý</a>
                    @else
                        @foreach ($lienKetKhach as [$route, $nhan])
                            <a href="{{ route($route) }}" class="block py-2 text-gray-700">{{ $nhan }}</a>
                        @endforeach
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="block w-full py-2 text-left text-red-600">Đăng xuất</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block py-2 font-medium">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="block py-2 font-semibold text-indigo-600">Đăng ký</a>
                @endauth
            </div>
        </div>
    </header>

    @isset($hero)
        {{ $hero }}
    @endisset

    <main class="flex-1">
        <div class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status') && !in_array(session('status'), $thongBaoNoiBo, true))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            {{ $slot }}
        </div>
    </main>

    {{-- Chân trang --}}
    <footer class="mt-8 bg-slate-900 text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
            <div>
                <p class="flex items-center gap-2 text-lg font-extrabold text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/></svg>
                    </span>
                    {{ $ten }}
                </p>
                <p class="mt-3 text-sm text-slate-400">{{ config('phongtro.khau_hieu') }}</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Liên kết nhanh</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('trang-chu') }}#phong-trong" class="hover:text-white">Phòng đang trống</a></li>
                    <li><a href="{{ route('trang-chu') }}#cach-thue" class="hover:text-white">Cách thuê phòng</a></li>
                    @guest
                        <li><a href="{{ route('login') }}" class="hover:text-white">Đăng nhập</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Đăng ký tài khoản</a></li>
                    @endguest
                </ul>
            </div>
            <div id="lien-he-chan-trang">
                <p class="text-sm font-semibold text-white">Liên hệ</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @if (config('phongtro.dia_chi'))<li>📍 {{ config('phongtro.dia_chi') }}</li>@endif
                    @if (config('phongtro.sdt'))<li>📞 {{ config('phongtro.sdt') }}</li>@endif
                    @if (config('phongtro.email'))<li>✉️ {{ config('phongtro.email') }}</li>@endif
                    @if (config('phongtro.gio_lam_viec'))<li>🕒 {{ config('phongtro.gio_lam_viec') }}</li>@endif
                    @unless (config('phongtro.dia_chi') || config('phongtro.sdt') || config('phongtro.email'))
                        <li class="text-slate-500">Đăng ký tài khoản để gửi yêu cầu cho chủ trọ.</li>
                    @endunless
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
            © {{ date('Y') }} {{ $ten }}. Đồ án tốt nghiệp: website quản lý phòng cho thuê.
        </div>
    </footer>
</body>
</html>
