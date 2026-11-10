@php
    $mau = match ($trangThai) {
        'trong'         => 'bg-green-100 text-green-800',
        'da_giu_cho'    => 'bg-yellow-100 text-yellow-800',
        'dang_thue'     => 'bg-blue-100 text-blue-800',
        'cho_tra_phong' => 'bg-orange-100 text-orange-800',
        default         => 'bg-gray-200 text-gray-700',
    };
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mau }}">
    {{ \App\Models\Phong::NHAN_TRANG_THAI[$trangThai] ?? $trangThai }}
</span>
