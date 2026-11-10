@php
    $tien = fn ($v) => number_format($v, 0, ',', '.') . ' đ';
    $phanTram = fn ($so) => $tongPhong > 0 ? round($so * 100 / $tongPhong) : 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Báo cáo doanh thu và công nợ</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            <form method="GET" class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
                <div>
                    <label for="tu" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                    <input id="tu" type="date" name="tu" value="{{ $tu->format('Y-m-d') }}" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label for="den" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                    <input id="den" type="date" name="den" value="{{ $den->format('Y-m-d') }}" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Xem báo cáo</button>
                <a href="{{ route('chutro.bao-cao.index') }}" class="self-center text-sm text-gray-600 underline">6 tháng gần nhất</a>
            </form>
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            {{-- Số liệu tổng quan --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Doanh thu trong kỳ</p>
                    <p class="mt-1 text-2xl font-bold text-green-700">{{ $tien($tongDoanhThu) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $tu->format('d/m/Y') }} – {{ $den->format('d/m/Y') }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Tổng công nợ (đến hôm nay)</p>
                    <p class="mt-1 text-2xl font-bold text-red-700">{{ $tien($tongCongNo) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $hoaDonNo->count() }} hóa đơn chưa trả hết</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Hóa đơn quá hạn</p>
                    <p class="mt-1 text-2xl font-bold">{{ $quaHan->count() }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $quaHan->filter(fn ($h) => $h->canNhacNo())->count() }} hóa đơn cần nhắc nợ (từ 4 ngày)</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Tỷ lệ lấp đầy</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $tyLeLapDay }}%</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $dangThue }} đang thuê / {{ max(0, $tongPhong - $baoTri) }} phòng khả dụng</p>
                </div>
            </div>

            {{-- Doanh thu theo tháng --}}
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-1 font-semibold text-gray-900">Doanh thu theo tháng</h3>
                <p class="mb-4 text-xs text-gray-500">
                    Doanh thu là tiền thực thu từ hóa đơn (chuyển khoản, tiền mặt, trừ cọc) tính theo tháng thu tiền.
                    Không gồm tiền cọc giữ chỗ.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="py-2 pr-4">Tháng</th>
                                <th class="py-2 pr-4 text-right">Chuyển khoản</th>
                                <th class="py-2 pr-4 text-right">Tiền mặt</th>
                                <th class="py-2 pr-4 text-right">Trừ cọc</th>
                                <th class="py-2 pr-4 text-right">Tổng</th>
                                <th class="py-2 w-48"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($theoThang as $thang => $dong)
                                <tr>
                                    <td class="py-2 pr-4 font-medium">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $thang . '-01')->format('m/Y') }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $tien($dong['chuyen_khoan']) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $tien($dong['tien_mat']) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $tien($dong['tru_coc']) }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold">{{ $tien($dong['tong']) }}</td>
                                    <td class="py-2">
                                        <div class="h-3 rounded bg-gray-100">
                                            <div class="h-3 rounded bg-green-500" style="width: {{ round($dong['tong'] * 100 / $maxThang) }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 font-semibold">
                                <td class="py-2 pr-4" colspan="4">Tổng cộng</td>
                                <td class="py-2 pr-4 text-right">{{ $tien($tongDoanhThu) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Công nợ --}}
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-900">Công nợ</h3>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Hóa đơn chưa trả hết</dt><dd class="font-medium">{{ $tien($noHoaDon) }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Nợ còn lại sau thanh lý</dt><dd class="font-medium">{{ $tien($noSauThanhLy) }}</dd></div>
                        <div class="flex justify-between py-2 font-semibold"><dt>Tổng công nợ</dt><dd class="text-red-700">{{ $tien($tongCongNo) }}</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-500">Không tính hóa đơn đã chuyển nợ, vì nợ đó đã nằm trong hóa đơn sau hoặc trong nợ sau thanh lý.</p>
                </div>

                {{-- Phòng --}}
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 font-semibold text-gray-900">Tình trạng phòng ({{ $tongPhong }} phòng)</h3>
                    @foreach ([
                        ['Đang thuê (gồm chờ trả phòng)', $dangThue, 'bg-blue-500'],
                        ['Trống', $trong, 'bg-green-500'],
                        ['Đã giữ chỗ', $giuCho, 'bg-yellow-400'],
                        ['Bảo trì', $baoTri, 'bg-gray-400'],
                    ] as [$nhan, $so, $mau])
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between text-sm">
                                <span>{{ $nhan }}</span>
                                <span class="font-medium">{{ $so }} ({{ $phanTram($so) }}%)</span>
                            </div>
                            <div class="h-2 rounded bg-gray-100"><div class="h-2 rounded {{ $mau }}" style="width: {{ $phanTram($so) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Cọc chờ xử lý --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-6 shadow-sm">
                <div>
                    <h3 class="font-semibold text-gray-900">Cọc chờ xử lý</h3>
                    <p class="text-sm text-gray-600">
                        {{ $soCocCho }} khoản, tổng cọc đã xác nhận <strong>{{ $tien($tienCocCho) }}</strong>.
                    </p>
                </div>
                @if ($soCocCho > 0)
                    <a href="{{ route('chutro.coc.index') }}" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase text-white hover:bg-gray-700">Xử lý cọc</a>
                @endif
            </div>

            {{-- Hóa đơn quá hạn --}}
            <div class="rounded-lg bg-white shadow-sm">
                <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Hóa đơn quá hạn</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Mã</th>
                                <th class="px-4 py-3">Phòng</th>
                                <th class="px-4 py-3">Khách</th>
                                <th class="px-4 py-3">Hạn</th>
                                <th class="px-4 py-3">Quá hạn</th>
                                <th class="px-4 py-3 text-right">Còn nợ</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($quaHan as $h)
                                <tr>
                                    <td class="px-4 py-3">#{{ $h->id }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $h->hopDong->phong->ma_phong }}</td>
                                    <td class="px-4 py-3">{{ $h->hopDong->khach->ho_ten }} <span class="text-gray-500">{{ $h->hopDong->khach->sdt }}</span></td>
                                    <td class="px-4 py-3">{{ $h->han_thanh_toan->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        {{ $h->soNgayQuaHan() }} ngày
                                        @if ($h->canNhacNo())<span class="ml-1 text-xs font-medium text-red-700">Cần nhắc nợ</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">{{ $tien($h->con_no) }}</td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('chutro.hoa-don.show', $h) }}" class="text-indigo-600 hover:underline">Xem</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có hóa đơn nào quá hạn.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
