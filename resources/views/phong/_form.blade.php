@php $khoa = isset($phong) && $phong->dangTrongQuyTrinh(); @endphp

<div>
    <x-input-label for="ma_phong" value="Mã phòng" />
    <x-text-input id="ma_phong" name="ma_phong" type="text" class="mt-1 block w-full"
                  :value="old('ma_phong', $phong->ma_phong ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('ma_phong')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="dien_tich" value="Diện tích (m²)" />
        <x-text-input id="dien_tich" name="dien_tich" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                      :value="old('dien_tich', isset($phong) ? rtrim(rtrim($phong->dien_tich, '0'), '.') : '')" required />
        <x-input-error :messages="$errors->get('dien_tich')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="gia_thue" value="Giá thuê / tháng (đồng)" />
        <x-text-input id="gia_thue" name="gia_thue" type="number" step="1" min="0" class="mt-1 block w-full"
                      :value="old('gia_thue', $phong->gia_thue ?? '')" required />
        <x-input-error :messages="$errors->get('gia_thue')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="trang_thai" value="Trạng thái" />
    @if ($khoa)
        <div class="mt-1 flex items-center gap-2">
            @include('phong._badge', ['trangThai' => $phong->trang_thai])
            <span class="text-sm text-gray-500">Trạng thái này do quy trình giữ chỗ/hợp đồng tự cập nhật.</span>
        </div>
    @else
        <select id="trang_thai" name="trang_thai" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @foreach (['trong' => 'Trống', 'bao_tri' => 'Bảo trì'] as $ma => $nhan)
                <option value="{{ $ma }}" @selected(old('trang_thai', $phong->trang_thai ?? 'trong') === $ma)>{{ $nhan }}</option>
            @endforeach
        </select>
    @endif
    <x-input-error :messages="$errors->get('trang_thai')" class="mt-2" />
</div>

<div>
    <x-input-label for="mo_ta" value="Mô tả" />
    <textarea id="mo_ta" name="mo_ta" rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('mo_ta', $phong->mo_ta ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('mo_ta')" class="mt-2" />
</div>

<div>
    <x-input-label for="anh" value="Thêm ảnh (tối đa 10 ảnh, mỗi ảnh dưới 2MB, định dạng jpg/png/webp)" />
    <input id="anh" name="anh[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
           class="mt-1 block w-full text-sm text-gray-700">
    <x-input-error :messages="$errors->get('anh')" class="mt-2" />
    <x-input-error :messages="$errors->get('anh.*')" class="mt-2" />
</div>
