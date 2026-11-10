<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sửa phòng {{ $phong->ma_phong }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('chutro.phong.update', $phong) }}" enctype="multipart/form-data"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
                @csrf @method('PUT')
                @include('phong._form')
                <div class="flex items-center gap-4">
                    <x-primary-button>Lưu thay đổi</x-primary-button>
                    <a href="{{ route('chutro.phong.index') }}" class="text-sm text-gray-600 underline">Quay lại</a>
                </div>
            </form>

            {{-- Ảnh hiện có: để ngoài form chính vì HTML không cho lồng form trong form --}}
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-3 font-medium text-gray-900">Ảnh hiện có ({{ $phong->anhPhong->count() }})</h3>
                @if ($phong->anhPhong->isEmpty())
                    <p class="text-sm text-gray-500">Chưa có ảnh nào.</p>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        @foreach ($phong->anhPhong as $anh)
                            <div>
                                <img src="{{ asset('storage/' . $anh->duong_dan) }}" class="h-28 w-full rounded object-cover" alt="">
                                <form method="POST" action="{{ route('chutro.phong.xoa-anh', $anh) }}"
                                      onsubmit="return confirm('Xóa ảnh này?')">
                                    @csrf @method('DELETE')
                                    <button class="mt-1 text-xs text-red-600 hover:underline">Xóa ảnh</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
