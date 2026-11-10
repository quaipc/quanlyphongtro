<?php

// Thông tin tài khoản chủ trọ để khách chuyển cọc và tiền phòng. Điền trong file .env.
return [
    // Thông tin hiển thị trên website. Điền trong file .env, để trống thì không hiện.
    'ten'          => env('PHONGTRO_TEN', 'Phòng trọ'),
    'khau_hieu'    => env('PHONGTRO_KHAU_HIEU', 'Tìm phòng nhanh, thuê minh bạch'),
    'dia_chi'      => env('PHONGTRO_DIA_CHI', ''),
    'sdt'          => env('PHONGTRO_SDT', ''),
    'email'        => env('PHONGTRO_EMAIL', ''),
    'gio_lam_viec' => env('PHONGTRO_GIO_LAM_VIEC', ''),

    'chuyen_khoan' => [
        'ngan_hang'     => env('PHONGTRO_NGAN_HANG', '(chưa cấu hình)'),
        'so_tai_khoan'  => env('PHONGTRO_STK', '(chưa cấu hình)'),
        'chu_tai_khoan' => env('PHONGTRO_CHU_TK', '(chưa cấu hình)'),
    ],

    // Số ngày khách có để thanh toán hóa đơn định kỳ, tính từ ngày lập hóa đơn
    'han_thanh_toan_ngay' => (int) env('PHONGTRO_HAN_THANH_TOAN_NGAY', 5),
];
