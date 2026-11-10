@php
    $sua = (bool) $hopDong;
    $khach = $yeuCau->khach;
    $phong = $yeuCau->phong;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $sua ? 'Sửa hợp đồng HĐ' . $hopDong->id : 'Lập hợp đồng' }} – phòng {{ $phong->ma_phong }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif

            <form method="POST"
                  action="{{ $sua ? route('chutro.hop-dong.update', $hopDong) : route('chutro.hop-dong.store', $yeuCau) }}"
                  class="space-y-5 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @if ($sua) @method('PUT') @endif

                <div class="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                    <p>Khách: <strong>{{ $khach->ho_ten }}</strong> – {{ $khach->sdt }}</p>
                    <p>Tiền cọc (tổng cọc đã xác nhận): <strong>{{ number_format($yeuCau->tongCocDaXacNhan(), 0, ',', '.') }} đ</strong></p>
                    <p>Giá phòng hiện tại: {{ number_format($phong->gia_thue, 0, ',', '.') }} đ / tháng</p>
                </div>

                @if (blank($khach->cccd))
                    <div>
                        <x-input-label for="cccd_khach" value="CCCD của khách (9 hoặc 12 chữ số) – hồ sơ khách chưa có" />
                        <x-text-input id="cccd_khach" name="cccd_khach" type="text" class="mt-1 block w-full"
                                      :value="old('cccd_khach')" required />
                        <x-input-error :messages="$errors->get('cccd_khach')" class="mt-2" />
                    </div>
                @else
                    <p class="text-sm text-gray-600">CCCD của khách: <strong>{{ $khach->cccd }}</strong></p>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="ngay_bat_dau" value="Ngày bắt đầu (ngày nhận phòng)" />
                        <x-text-input id="ngay_bat_dau" name="ngay_bat_dau" type="date" class="mt-1 block w-full"
                                      :value="old('ngay_bat_dau', $sua ? $hopDong->ngay_bat_dau->format('Y-m-d') : '')"
                                      min="{{ now()->format('Y-m-d') }}" max="{{ now()->addDays(14)->format('Y-m-d') }}" required />
                        <p class="mt-1 text-xs text-gray-500">Không muộn hơn 14 ngày kể từ hôm nay.</p>
                        <x-input-error :messages="$errors->get('ngay_bat_dau')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="ngay_ket_thuc" value="Ngày kết thúc" />
                        <x-text-input id="ngay_ket_thuc" name="ngay_ket_thuc" type="date" class="mt-1 block w-full"
                                      :value="old('ngay_ket_thuc', $sua ? $hopDong->ngay_ket_thuc->format('Y-m-d') : '')" required />
                        <x-input-error :messages="$errors->get('ngay_ket_thuc')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="gia_thue" value="Giá thuê / tháng (đồng)" />
                        <x-text-input id="gia_thue" name="gia_thue" type="number" min="0" step="1" class="mt-1 block w-full"
                                      :value="old('gia_thue', $sua ? (int) $hopDong->gia_thue : (int) $phong->gia_thue)" required />
                        <x-input-error :messages="$errors->get('gia_thue')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="chu_ky_thu" value="Chu kỳ thu tiền phòng" />
                        <select id="chu_ky_thu" name="chu_ky_thu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ([1 => 'Mỗi tháng', 3 => 'Mỗi quý (3 tháng)'] as $v => $nhan)
                                <option value="{{ $v }}" @selected((int) old('chu_ky_thu', $sua ? $hopDong->chu_ky_thu : 1) === $v)>{{ $nhan }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('chu_ky_thu')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-800">Người ở cùng (tối đa 5, để trống dòng không dùng)</h3>
                    @for ($i = 0; $i < 5; $i++)
                        <div class="mb-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <input type="text" name="nguoi_o[{{ $i }}][ho_ten]" placeholder="Họ tên"
                                   value="{{ old("nguoi_o.$i.ho_ten", $nguoiO[$i]->ho_ten ?? '') }}"
                                   class="rounded-md border-gray-300 text-sm shadow-sm">
                            <input type="text" name="nguoi_o[{{ $i }}][cccd]" placeholder="CCCD (nếu có)"
                                   value="{{ old("nguoi_o.$i.cccd", $nguoiO[$i]->cccd ?? '') }}"
                                   class="rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                    @endfor
                    <x-input-error :messages="$errors->get('nguoi_o.*.ho_ten')" class="mt-1" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ $sua ? 'Lưu thay đổi' : 'Lập hợp đồng' }}</x-primary-button>
                    <a href="{{ $sua ? route('chutro.hop-dong.show', $hopDong) : route('chutro.yeu-cau.index') }}" class="text-sm text-gray-600 underline">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
