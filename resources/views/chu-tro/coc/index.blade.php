@php
    $nhanKetQua = ['mat_coc' => 'Mất cọc (giữ lại)', 'da_hoan' => 'Đã hoàn cọc cho khách'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cọc chờ xử lý</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <p class="text-sm text-gray-600">
                Các khoản cọc của yêu cầu bị hủy hoặc hết hạn cần chủ trọ quyết định: giữ lại (khách mất cọc) hoặc hoàn cho khách.
                Hoàn cọc là chuyển khoản thủ công, hãy chuyển khoản trước rồi mới bấm "Đã hoàn cọc".
            </p>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Phòng</th>
                            <th class="px-4 py-3">Khách</th>
                            <th class="px-4 py-3">Lý do</th>
                            <th class="px-4 py-3">Tiền cọc</th>
                            <th class="px-4 py-3 text-right">Quyết định</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($choXuLy as $y)
                            @php
                                $daXacNhan = $y->tongCocDaXacNhan();
                                $baoChuyen = (int) $y->thanhToan->where('loai', 'coc')->where('trang_thai', 'cho_xac_nhan')->sum('so_tien');
                                $lyDo = match (true) {
                                    $y->trang_thai === 'het_han' => 'Đã cọc nhưng quá 7 ngày chưa ký hợp đồng',
                                    (bool) ($y->hopDong && $y->hopDong->trang_thai === 'da_huy') => 'Hợp đồng bị hủy (quá ngày nhận phòng hoặc chủ trọ hủy)',
                                    default => 'Khách hủy khi đã báo chuyển cọc nhưng chưa được xác nhận',
                                };
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 font-medium">{{ $y->phong->ma_phong }}</td>
                                <td class="px-4 py-3">{{ $y->khach->ho_ten }}<br><span class="text-gray-500">{{ $y->khach->sdt }}</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ $lyDo }}</td>
                                <td class="px-4 py-3">
                                    @if ($daXacNhan > 0)
                                        Đã xác nhận <strong>{{ number_format($daXacNhan, 0, ',', '.') }} đ</strong>
                                    @endif
                                    @if ($baoChuyen > 0)
                                        <br>Khách báo đã chuyển <strong>{{ number_format($baoChuyen, 0, ',', '.') }} đ</strong> (chưa xác nhận, hãy đối chiếu sao kê)
                                    @endif
                                    @if ($daXacNhan === 0 && $baoChuyen === 0)
                                        <span class="text-gray-500">Chưa ghi nhận khoản nào</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('chutro.coc.xu-ly', $y) }}" class="inline"
                                          onsubmit="return confirm('Xác nhận ĐÃ chuyển khoản hoàn cọc cho khách?')">
                                        @csrf
                                        <input type="hidden" name="quyet_dinh" value="da_hoan">
                                        <button class="text-indigo-600 hover:underline">Đã hoàn cọc</button>
                                    </form>
                                    <form method="POST" action="{{ route('chutro.coc.xu-ly', $y) }}" class="ml-3 inline"
                                          onsubmit="return confirm('Giữ lại khoản cọc, khách mất cọc?')">
                                        @csrf
                                        <input type="hidden" name="quyet_dinh" value="mat_coc">
                                        <button class="text-red-600 hover:underline">Mất cọc</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Không có khoản cọc nào chờ xử lý.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($daXuLy->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-900">Đã xử lý gần đây</h3>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($daXuLy as $y)
                                <tr>
                                    <td class="py-2 font-medium">{{ $y->phong->ma_phong }}</td>
                                    <td class="py-2">{{ $y->khach->ho_ten }}</td>
                                    <td class="py-2 {{ $y->xu_ly_coc === 'mat_coc' ? 'text-red-700' : 'text-green-700' }}">{{ $nhanKetQua[$y->xu_ly_coc] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
