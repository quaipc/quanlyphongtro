<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sự cố phòng {{ $suCo->phong->ma_phong }}</h2>
            @include('partials.badge', ['tt' => $suCo->trang_thai, 'nhan' => $suCo->nhan()])
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('loi'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('loi') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold">{{ $suCo->tieu_de }}</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Khách: <strong>{{ $suCo->khach->ho_ten }}</strong> ({{ $suCo->khach->sdt }}) · báo lúc {{ $suCo->ngay_bao->format('H:i d/m/Y') }}
                </p>
                <p class="mt-3 whitespace-pre-line text-gray-700">{{ $suCo->mo_ta ?: 'Khách không ghi mô tả.' }}</p>

                @if (in_array($suCo->trang_thai, ['cho_khach_xac_nhan', 'hoan_tat']))
                    <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                        Chi phí sửa: <strong>{{ number_format($suCo->chi_phi, 0, ',', '.') }} đ</strong> ·
                        {{ $suCo->loi_do_khach ? 'Lỗi do khách' : 'Không phải lỗi do khách' }}
                        @if ($suCo->loi_do_khach && (int) $suCo->chi_phi > 0)
                            <p class="mt-1 text-xs text-gray-500">
                                @if ($suCo->daTinhVaoHoaDon())
                                    Phí này đã được tính vào hóa đơn.
                                @elseif ($suCo->trang_thai === 'hoan_tat')
                                    Phí này sẽ được thêm vào hóa đơn kế tiếp (hóa đơn định kỳ hoặc thanh lý).
                                @else
                                    Phí sẽ được thêm vào hóa đơn kế tiếp sau khi khách xác nhận.
                                @endif
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Mới báo: tiếp nhận --}}
                @if ($suCo->trang_thai === 'moi')
                    <form method="POST" action="{{ route('chutro.su-co.tiep-nhan', $suCo) }}" class="mt-4">
                        @csrf
                        <x-primary-button>Tiếp nhận sự cố</x-primary-button>
                    </form>
                @endif

                {{-- Đang xử lý: báo sửa xong, ghi chi phí --}}
                @if ($suCo->trang_thai === 'dang_xu_ly')
                    <form method="POST" action="{{ route('chutro.su-co.bao-sua-xong', $suCo) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4"
                          onsubmit="return confirm('Báo đã sửa xong và chuyển cho khách xác nhận?')">
                        @csrf
                        <div>
                            <label for="chi_phi" class="block text-sm font-medium text-gray-700">Chi phí sửa (đồng, nhập 0 nếu không tốn)</label>
                            <input id="chi_phi" type="number" name="chi_phi" min="0" step="1" value="{{ old('chi_phi', 0) }}" required
                                   class="mt-1 w-56 rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="loi_do_khach" value="1" @checked(old('loi_do_khach')) class="rounded border-gray-300">
                            <span>Lỗi do khách (chi phí sẽ được tính vào hóa đơn kỳ sau)</span>
                        </label>
                        <x-primary-button>Báo đã sửa xong</x-primary-button>
                    </form>
                @endif

                @if ($suCo->trang_thai === 'cho_khach_xac_nhan')
                    <p class="mt-4 rounded-md bg-orange-50 p-3 text-sm text-orange-900">Đang chờ khách xác nhận đã sửa xong.</p>
                @endif
            </div>

            {{-- Sự cố nặng buộc khách rời đi --}}
            @if ($suCo->trang_thai !== 'hoan_tat' && $hopDong)
                <div class="rounded-lg border border-red-200 bg-red-50 p-6">
                    <h3 class="font-semibold text-red-900">Sự cố nặng (phòng không ở được hoặc không an toàn)</h3>
                    @if ($hopDong->mien_phat)
                        <p class="mt-1 text-sm text-red-900">
                            Hợp đồng HĐ{{ $hopDong->id }} đã được đánh dấu miễn phạt.
                            <a href="{{ route('chutro.hop-dong.show', $hopDong) }}" class="underline">Mở hợp đồng để thanh lý</a>.
                        </p>
                    @else
                        <p class="mt-1 text-sm text-red-900">
                            Nếu khách buộc phải rời đi, đánh dấu hợp đồng HĐ{{ $hopDong->id }} là miễn phạt: khách không phải báo trước 1 tháng,
                            không mất cọc và được hoàn tiền phòng những ngày không ở. Sau đó bạn thanh lý hợp đồng.
                            Phòng đang thuê không được chuyển sang Bảo trì.
                        </p>
                        <form method="POST" action="{{ route('chutro.su-co.nang', $suCo) }}" class="mt-3"
                              onsubmit="return confirm('Đánh dấu hợp đồng được miễn phạt do sự cố nặng buộc khách rời đi?')">
                            @csrf
                            <button class="rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500">
                                Đánh dấu sự cố nặng – miễn phạt
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            <a href="{{ route('chutro.su-co.index') }}" class="text-sm text-gray-600 underline">&larr; Danh sách sự cố</a>
        </div>
    </div>
</x-app-layout>
