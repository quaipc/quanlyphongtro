@php
    $mau = match ($tt) {
        'cho_duyet', 'cho_xac_nhan', 'cho_ky', 'chua_thanh_toan', 'moi' => 'bg-yellow-100 text-yellow-800',
        'cho_coc', 'da_xac_nhan', 'da_coc', 'dang_xu_ly'          => 'bg-blue-100 text-blue-800',
        'da_xem', 'da_ky', 'hieu_luc', 'da_thanh_toan', 'hoan_tat' => 'bg-green-100 text-green-800',
        'cho_thanh_ly', 'cho_khach_xac_nhan'                     => 'bg-orange-100 text-orange-800',
        'tu_choi', 'bi_tu_choi', 'het_han', 'qua_han'            => 'bg-red-100 text-red-800',
        default                                                  => 'bg-gray-200 text-gray-700',
    };
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mau }}">{{ $nhan }}</span>
