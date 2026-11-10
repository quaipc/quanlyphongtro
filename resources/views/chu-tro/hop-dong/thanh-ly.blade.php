<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Thanh lý hợp đồng HĐ{{ $hopDong->id }} – phòng {{ $hopDong->phong->ma_phong }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('chutro.hop-dong.thanh-ly.store', $hopDong) }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm"
                  onsubmit="return confirm('Thanh lý hợp đồng? Thao tác này tất toán cọc và trả phòng, không hoàn tác được.')">
                @csrf

                <div class="space-y-1 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                    <p>Khách: <strong>{{ $hopDong->khach->ho_ten }}</strong></p>
                    <p>Ngày trả phòng: <strong>{{ $tra->format('d/m/Y') }}</strong></p>
                    <p>Tiền cọc: <strong>{{ number_format($hopDong->tien_coc, 0, ',', '.') }} đ</strong></p>
                    <p>Hóa đơn chưa trả (sẽ trừ vào cọc): <strong>{{ number_format($soNo, 0, ',', '.') }} đ</strong></p>
                    @if ($hopDong->ngay_bao_tra)
                        <p>Khách báo trả ngày {{ $hopDong->ngay_bao_tra->format('d/m/Y') }}.</p>
                    @endif
                </div>

                    <p class="text-sm text-gray-600">
                        Chỉ số kỳ trước: điện <strong>{{ $truoc->chi_so_dien }}</strong>, nước <strong>{{ $truoc->chi_so_nuoc }}</strong>.
                        Hóa đơn thanh lý gồm điện nước và dịch vụ của chu kỳ cuối; tiền phòng đã trả trước không hoàn.
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="chi_so_dien" value="Chỉ số điện lúc trả (kWh)" />
                            <x-text-input id="chi_so_dien" name="chi_so_dien" type="number" min="{{ $truoc->chi_so_dien }}" step="1" class="mt-1 block w-full"
                                          :value="old('chi_so_dien')" required autofocus />
                            <x-input-error :messages="$errors->get('chi_so_dien')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="chi_so_nuoc" value="Chỉ số nước lúc trả (m³)" />
                            <x-text-input id="chi_so_nuoc" name="chi_so_nuoc" type="number" min="{{ $truoc->chi_so_nuoc }}" step="1" class="mt-1 block w-full"
                                          :value="old('chi_so_nuoc')" required />
                            <x-input-error :messages="$errors->get('chi_so_nuoc')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="tien_tru_hu_hong" value="Khoản hư hỏng trừ vào cọc (đồng, nhập 0 nếu không có)" />
                        <x-text-input id="tien_tru_hu_hong" name="tien_tru_hu_hong" type="number" min="0" step="1" class="mt-1 block w-full"
                                      :value="old('tien_tru_hu_hong', 0)" required />
                        <x-input-error :messages="$errors->get('tien_tru_hu_hong')" class="mt-2" />
                    </div>

                    @if ($hopDong->trang_thai === 'hieu_luc' && !$hetHan && !$hopDong->mien_phat)
                        <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                            <p>Hợp đồng chưa hết hạn và khách chưa báo trả, nên chỉ thanh lý sớm được khi <strong>sự cố nặng buộc khách rời đi</strong>.</p>
                            <label class="mt-2 flex items-center gap-2">
                                <input type="checkbox" name="mien_phat" value="1" required class="rounded border-gray-300">
                                <span>Sự cố nặng buộc khách rời đi: miễn báo trước 1 tháng, không mất cọc, hoàn tiền phòng những ngày không ở</span>
                            </label>
                        </div>
                    @elseif (!$hopDong->mien_phat)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="mien_phat" value="1" @checked(old('mien_phat')) class="rounded border-gray-300">
                            <span>Sự cố nặng buộc khách rời đi: miễn báo trước 1 tháng, không mất cọc, hoàn tiền phòng những ngày không ở</span>
                        </label>
                    @else
                        <input type="hidden" name="mien_phat" value="1">
                        <p class="text-sm text-gray-600">Hợp đồng đã được đánh dấu miễn phạt do sự cố nặng.</p>
                    @endif

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="chuyen_bao_tri" value="1" @checked(old('chuyen_bao_tri')) class="rounded border-gray-300">
                        <span>Hư hỏng nặng: chuyển phòng sang Bảo trì sau khi thanh lý</span>
                    </label>

                    <x-primary-button>Thanh lý hợp đồng</x-primary-button>
                <a href="{{ route('chutro.hop-dong.show', $hopDong) }}" class="ml-4 text-sm text-gray-600 underline">Hủy</a>
            </form>
        </div>
    </div>
</x-app-layout>
