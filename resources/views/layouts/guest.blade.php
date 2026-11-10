@php $ten = config('phongtro.ten'); @endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $ten }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Cột giới thiệu --}}
        <div class="hidden flex-col justify-between bg-gradient-to-br from-indigo-700 via-indigo-600 to-sky-500 p-12 text-white lg:flex">
            <a href="{{ route('trang-chu') }}" class="flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/></svg>
                </span>
                <span class="text-xl font-extrabold tracking-tight">{{ $ten }}</span>
            </a>
            <div>
                <h2 class="text-4xl font-extrabold leading-tight">{{ config('phongtro.khau_hieu') }}</h2>
                <ul class="mt-8 space-y-3 text-indigo-100">
                    <li>✔ Xem phòng trống, ảnh và giá rõ ràng</li>
                    <li>✔ Đặt lịch xem và giữ chỗ trực tuyến</li>
                    <li>✔ Theo dõi hợp đồng, hóa đơn và sự cố tại một nơi</li>
                </ul>
            </div>
            <p class="text-sm text-indigo-200">© {{ date('Y') }} {{ $ten }}</p>
        </div>

        {{-- Cột biểu mẫu --}}
        <div class="flex items-center justify-center bg-gray-50 px-6 py-12">
            <div class="w-full max-w-md">
                <a href="{{ route('trang-chu') }}" class="mb-6 flex items-center justify-center gap-2 lg:hidden">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/></svg>
                    </span>
                    <span class="text-lg font-extrabold">{{ $ten }}</span>
                </a>

                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-sm text-gray-500">
                    <a href="{{ route('trang-chu') }}" class="hover:text-indigo-600">&larr; Về trang chủ</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
