<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Thêm phòng</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('chutro.phong.store') }}" enctype="multipart/form-data"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @include('phong._form')
                <div class="flex items-center gap-4">
                    <x-primary-button>Lưu phòng</x-primary-button>
                    <a href="{{ route('chutro.phong.index') }}" class="text-sm text-gray-600 underline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
