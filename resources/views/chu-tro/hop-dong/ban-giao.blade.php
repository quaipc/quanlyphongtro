<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bàn giao phòng {{ $hopDong->phong->ma_phong }} – HĐ{{ $hopDong->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <form method="POST" action="{{ route('chutro.hop-dong.ban-giao.store', $hopDong) }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm"
                  onsubmit="return confirm('Xác nhận bàn giao phòng và đã thu tiền phòng chu kỳ đầu?')">
                @csrf

                <div class="rounded-md bg-gray-50 p-4 text-sm text-gray-700 space-y-1">
                    <p>Khách: <strong>{{ $hopDong->khach->ho_ten }}</strong></p>
                    <p>Ngày bàn giao (trở thành ngày bắt đầu hợp đồng): <strong>{{ $homNay->format('d/m/Y') }}</strong></p>
                    <p>Hợp đồng đến hết ngày: <strong>{{ $hopDong->ngay_ket_thuc->format('d/m/Y') }}</strong> – thu tiền phòng {{ mb_strtolower($hopDong->nhanChuKy()) }}</p>
                </div>

                <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                    <p class="font-semibold">Hóa đơn bàn giao: tiền phòng chu kỳ đầu</p>
                    <p>Từ {{ $tu->format('d/m/Y') }} đến {{ $den->format('d/m/Y') }}</p>
                    <p class="mt-1 text-lg font-bold">{{ number_format($soTien, 0, ',', '.') }} đ</p>
                    @if ($biCat)
                        <p class="mt-1 text-xs">Hợp đồng kết thúc giữa chu kỳ nên tiền phòng tính theo số ngày ở.</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="chi_so_dien" value="Chỉ số điện đầu kỳ (kWh)" />
                        <x-text-input id="chi_so_dien" name="chi_so_dien" type="number" min="0" step="1" class="mt-1 block w-full"
                                      :value="old('chi_so_dien')" required autofocus />
                        <x-input-error :messages="$errors->get('chi_so_dien')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chi_so_nuoc" value="Chỉ số nước đầu kỳ (m³)" />
                        <x-text-input id="chi_so_nuoc" name="chi_so_nuoc" type="number" min="0" step="1" class="mt-1 block w-full"
                                      :value="old('chi_so_nuoc')" required />
                        <x-input-error :messages="$errors->get('chi_so_nuoc')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="hinh_thuc" value="Khách đã thanh toán tiền phòng bằng" />
                    <select id="hinh_thuc" name="hinh_thuc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="tien_mat" @selected(old('hinh_thuc', 'tien_mat') === 'tien_mat')>Tiền mặt</option>
                        <option value="chuyen_khoan" @selected(old('hinh_thuc') === 'chuyen_khoan')>Chuyển khoản</option>
                    </select>
                    <x-input-error :messages="$errors->get('hinh_thuc')" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Xác nhận bàn giao</x-primary-button>
                    <a href="{{ route('chutro.hop-dong.show', $hopDong) }}" class="text-sm text-gray-600 underline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
