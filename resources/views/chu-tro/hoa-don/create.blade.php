<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lập hóa đơn định kỳ – phòng {{ $hopDong->phong->ma_phong }} (HĐ{{ $hopDong->id }})</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <form method="POST" action="{{ route('chutro.hoa-don.store', $hopDong) }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm"
                  onsubmit="return confirm('Lập hóa đơn với các chỉ số đã nhập?')">
                @csrf

                <div class="rounded-md bg-gray-50 p-4 text-sm text-gray-700 space-y-1">
                    <p>Khách: <strong>{{ $hopDong->khach->ho_ten }}</strong></p>
                    <p>Chu kỳ vừa hết: <strong>{{ $tuKyVuaHet->format('d/m/Y') }} – {{ $denKyVuaHet->format('d/m/Y') }}</strong> (tính điện nước, dịch vụ)</p>
                    <p>Chu kỳ kế tiếp: <strong>{{ $xem['tu']->format('d/m/Y') }} – {{ $xem['den']->format('d/m/Y') }}</strong> (thu tiền phòng trước)</p>
                </div>

                <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-900 space-y-1">
                    <p>Tiền phòng chu kỳ kế tiếp: <strong>{{ number_format($xem['dong'][0]['thanh_tien'], 0, ',', '.') }} đ</strong></p>
                    <p>Nợ cũ cộng dồn: <strong>{{ number_format($xem['no_cu'], 0, ',', '.') }} đ</strong></p>
                    <p class="text-xs text-indigo-700">Điện, nước, dịch vụ và phí sự cố (nếu có) được tính sau khi bạn nhập chỉ số.</p>
                </div>

                <p class="text-sm text-gray-600">
                    Chỉ số kỳ trước: điện <strong>{{ $truoc->chi_so_dien }}</strong>, nước <strong>{{ $truoc->chi_so_nuoc }}</strong>.
                    Chỉ số mới không được nhỏ hơn các số này.
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="chi_so_dien" value="Chỉ số điện mới (kWh)" />
                        <x-text-input id="chi_so_dien" name="chi_so_dien" type="number" min="{{ $truoc->chi_so_dien }}" step="1" class="mt-1 block w-full"
                                      :value="old('chi_so_dien')" required autofocus />
                        <x-input-error :messages="$errors->get('chi_so_dien')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chi_so_nuoc" value="Chỉ số nước mới (m³)" />
                        <x-text-input id="chi_so_nuoc" name="chi_so_nuoc" type="number" min="{{ $truoc->chi_so_nuoc }}" step="1" class="mt-1 block w-full"
                                      :value="old('chi_so_nuoc')" required />
                        <x-input-error :messages="$errors->get('chi_so_nuoc')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Lập hóa đơn</x-primary-button>
                    <a href="{{ route('chutro.hop-dong.show', $hopDong) }}" class="text-sm text-gray-600 underline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
