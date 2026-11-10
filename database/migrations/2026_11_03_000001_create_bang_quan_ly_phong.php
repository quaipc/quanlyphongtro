<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2. Phòng
        Schema::create('phong', function (Blueprint $t) {
            $t->increments('id');
            $t->string('ma_phong', 20)->unique();
            $t->decimal('dien_tich', 6, 2);
            $t->decimal('gia_thue', 12, 0);
            $t->text('mo_ta')->nullable();
            $t->enum('trang_thai', ['trong', 'da_giu_cho', 'dang_thue', 'cho_tra_phong', 'bao_tri'])->default('trong');
        });

        Schema::create('anh_phong', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('phong_id');
            $t->string('duong_dan');
            $t->foreign('phong_id', 'fk_anh_phong')->references('id')->on('phong')->cascadeOnDelete();
        });

        // 3. Lịch xem phòng
        Schema::create('lich_xem', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('phong_id');
            $t->unsignedInteger('khach_id');
            $t->dateTime('thoi_gian');
            $t->enum('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'tu_choi', 'da_xem', 'da_huy'])->default('cho_xac_nhan');
            $t->foreign('phong_id', 'fk_lich_xem_phong')->references('id')->on('phong');
            $t->foreign('khach_id', 'fk_lich_xem_khach')->references('id')->on('nguoi_dung');
        });

        // 4. Yêu cầu giữ chỗ
        Schema::create('yeu_cau_giu_cho', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('phong_id');
            $t->unsignedInteger('khach_id');
            $t->decimal('tien_coc', 12, 0)->nullable()->comment('Mặc định bằng 1 tháng tiền phòng, chủ trọ xác nhận khi duyệt');
            $t->dateTime('han_chuyen_coc')->nullable()->comment('Giờ khách bấm "Tôi đã chuyển" + 24 giờ (theo quy tắc đã chốt)');
            $t->date('han_giu')->nullable()->comment('Ngày xác nhận cọc + 7 ngày');
            $t->enum('trang_thai', ['cho_duyet', 'cho_coc', 'cho_xac_nhan', 'da_coc', 'da_ky', 'bi_tu_choi', 'het_han', 'da_huy'])->default('cho_duyet');
            $t->enum('xu_ly_coc', ['khong_ap_dung', 'cho_xu_ly', 'mat_coc', 'da_hoan'])->default('khong_ap_dung');
            $t->dateTime('created_at')->useCurrent();
            // Mỗi phòng tối đa một yêu cầu đang giữ chỗ (cột tính toán + UNIQUE)
            $t->unsignedInteger('phong_dang_giu')->nullable()
              ->storedAs("IF(trang_thai IN ('cho_coc','cho_xac_nhan','da_coc'), phong_id, NULL)");
            $t->unique('phong_dang_giu', 'uq_phong_dang_giu');
            $t->foreign('phong_id', 'fk_yc_phong')->references('id')->on('phong');
            $t->foreign('khach_id', 'fk_yc_khach')->references('id')->on('nguoi_dung');
        });

        // 5. Hợp đồng
        Schema::create('hop_dong', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('yeu_cau_id')->nullable()->unique();
            $t->unsignedInteger('phong_id');
            $t->unsignedInteger('khach_id');
            $t->date('ngay_bat_dau');
            $t->date('ngay_ket_thuc');
            $t->date('ngay_ky')->nullable()->comment('Ngày hai bên ký hợp đồng');
            $t->decimal('gia_thue', 12, 0);
            $t->tinyInteger('chu_ky_thu')->default(1)->comment('Số tháng mỗi lần thu tiền phòng trước: 1 (tháng) hoặc 3 (quý)');
            $t->decimal('tien_coc', 12, 0)->comment('Bằng tổng cọc đã xác nhận của yêu cầu giữ chỗ');
            $t->date('ngay_bao_tra')->nullable()->comment('Ngày khách báo trả phòng');
            $t->date('ngay_tra_phong')->nullable();
            $t->decimal('tien_tru_hu_hong', 12, 0)->default(0);
            $t->decimal('tien_hoan_coc', 12, 0)->nullable();
            $t->decimal('tien_mat_coc', 12, 0)->default(0)->comment('Phần cọc bị mất do báo trả phòng trễ');
            $t->decimal('tien_hoan_tien_phong', 12, 0)->default(0)->comment('Hoàn tiền phòng trả trước cho ngày không ở (chỉ khi sự cố nặng, mien_phat = 1)');
            $t->boolean('mien_phat')->default(false)->comment('Sự cố nặng buộc khách rời đi: miễn báo trước, không mất cọc');
            $t->decimal('no_con_lai', 12, 0)->default(0)->comment('Phần nợ vượt quá tiền cọc khi thanh lý');
            $t->enum('trang_thai', ['cho_ky', 'hieu_luc', 'cho_thanh_ly', 'da_thanh_ly', 'da_huy'])->default('cho_ky');
            // Mỗi phòng tối đa một hợp đồng đang còn hiệu lực
            $t->unsignedInteger('phong_dang_thue')->nullable()
              ->storedAs("IF(trang_thai IN ('cho_ky','hieu_luc','cho_thanh_ly'), phong_id, NULL)");
            $t->unique('phong_dang_thue', 'uq_phong_dang_thue');
            $t->foreign('yeu_cau_id', 'fk_hd_yeu_cau')->references('id')->on('yeu_cau_giu_cho');
            $t->foreign('phong_id', 'fk_hd_phong')->references('id')->on('phong');
            $t->foreign('khach_id', 'fk_hd_khach')->references('id')->on('nguoi_dung');
        });

        Schema::create('nguoi_o', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('hop_dong_id');
            $t->string('ho_ten', 100);
            $t->string('cccd', 20)->nullable();
            $t->foreign('hop_dong_id', 'fk_nguoi_o_hd')->references('id')->on('hop_dong')->cascadeOnDelete();
        });

        // 6. Chỉ số điện nước
        Schema::create('chi_so_dien_nuoc', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('hop_dong_id');
            $t->char('thang', 7)->comment('Định dạng YYYY-MM');
            $t->enum('loai', ['ban_giao', 'hang_thang', 'tra_phong'])->default('hang_thang');
            $t->unsignedInteger('chi_so_dien');
            $t->unsignedInteger('chi_so_nuoc');
            $t->unique(['hop_dong_id', 'thang', 'loai'], 'uq_chi_so');
            $t->foreign('hop_dong_id', 'fk_chi_so_hd')->references('id')->on('hop_dong');
        });

        // 7. Dịch vụ, hóa đơn
        Schema::create('dich_vu', function (Blueprint $t) {
            $t->increments('id');
            $t->string('ten', 100)->unique();
            $t->decimal('don_gia', 12, 0);
            $t->string('don_vi', 20);
        });

        Schema::create('hoa_don', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('hop_dong_id');
            $t->char('thang', 7)->comment('YYYY-MM, theo tháng của ngày bắt đầu chu kỳ thu');
            $t->enum('loai', ['ban_giao', 'dinh_ky', 'thanh_ly'])->default('dinh_ky')
              ->comment('ban_giao: tiền phòng chu kỳ đầu; dinh_ky: tiền phòng kỳ sau + điện nước kỳ vừa hết; thanh_ly: điện nước kỳ cuối');
            $t->date('tu_ngay')->comment('Ngày bắt đầu chu kỳ thu tiền phòng (tính từ ngày nhận phòng)');
            $t->date('den_ngay')->comment('Ngày kết thúc chu kỳ thu (1 tháng hoặc 1 quý)');
            $t->decimal('no_cu', 12, 0)->default(0)->comment('Nợ cộng dồn từ hóa đơn trước');
            $t->decimal('tong_tien', 12, 0)->default(0)->comment('Tổng chi tiết + nợ cũ');
            $t->date('han_thanh_toan');
            $t->enum('trang_thai', ['chua_thanh_toan', 'da_thanh_toan', 'qua_han', 'da_chuyen_no'])->default('chua_thanh_toan');
            $t->unique(['hop_dong_id', 'thang', 'loai'], 'uq_hoa_don');
            $t->foreign('hop_dong_id', 'fk_hoa_don_hd')->references('id')->on('hop_dong');
        });

        // 8. Sự cố
        Schema::create('su_co', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('phong_id');
            $t->unsignedInteger('khach_id');
            $t->string('tieu_de', 150);
            $t->text('mo_ta')->nullable();
            $t->enum('trang_thai', ['moi', 'dang_xu_ly', 'cho_khach_xac_nhan', 'hoan_tat'])->default('moi');
            $t->boolean('loi_do_khach')->default(false);
            $t->decimal('chi_phi', 12, 0)->default(0);
            $t->dateTime('ngay_bao')->useCurrent();
            $t->foreign('phong_id', 'fk_su_co_phong')->references('id')->on('phong');
            $t->foreign('khach_id', 'fk_su_co_khach')->references('id')->on('nguoi_dung');
        });

        Schema::create('chi_tiet_hoa_don', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('hoa_don_id');
            $t->unsignedInteger('dich_vu_id')->nullable();
            $t->unsignedInteger('su_co_id')->nullable();
            $t->string('mo_ta');
            $t->decimal('thanh_tien', 12, 0);
            $t->foreign('hoa_don_id', 'fk_cthd_hoa_don')->references('id')->on('hoa_don')->cascadeOnDelete();
            $t->foreign('dich_vu_id', 'fk_cthd_dich_vu')->references('id')->on('dich_vu');
            $t->foreign('su_co_id', 'fk_cthd_su_co')->references('id')->on('su_co');
        });

        // 9. Thanh toán
        Schema::create('thanh_toan', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('hoa_don_id')->nullable();
            $t->unsignedInteger('yeu_cau_id')->nullable();
            $t->enum('loai', ['coc', 'hoa_don']);
            $t->decimal('so_tien', 12, 0);
            $t->enum('hinh_thuc', ['chuyen_khoan', 'tien_mat', 'tru_coc'])->default('chuyen_khoan');
            $t->string('noi_dung_ck', 100)->nullable();
            $t->string('anh_bien_lai')->nullable();
            $t->enum('trang_thai', ['cho_xac_nhan', 'da_xac_nhan', 'tu_choi'])->default('cho_xac_nhan');
            $t->dateTime('ngay_tt')->useCurrent();
            $t->unsignedInteger('nguoi_xac_nhan_id')->nullable();
            $t->foreign('hoa_don_id', 'fk_tt_hoa_don')->references('id')->on('hoa_don');
            $t->foreign('yeu_cau_id', 'fk_tt_yeu_cau')->references('id')->on('yeu_cau_giu_cho');
            $t->foreign('nguoi_xac_nhan_id', 'fk_tt_nguoi_xn')->references('id')->on('nguoi_dung');
        });

        // Ràng buộc CHECK (Blueprint không hỗ trợ nên dùng SQL trực tiếp; cần MySQL 8.0.16+ / MariaDB 10.2+)
        $checks = [
            'phong'            => ['ck_phong_dien_tich' => 'dien_tich > 0', 'ck_phong_gia_thue' => 'gia_thue >= 0'],
            'yeu_cau_giu_cho'  => ['ck_yc_tien_coc' => 'tien_coc IS NULL OR tien_coc >= 0'],
            'hop_dong'         => [
                'ck_hd_ngay'        => 'ngay_ket_thuc > ngay_bat_dau',
                'ck_hd_chu_ky'      => 'chu_ky_thu IN (1, 3)',
                'ck_hd_nhan_phong'  => 'ngay_ky IS NULL OR ngay_bat_dau <= DATE_ADD(ngay_ky, INTERVAL 14 DAY)',
                'ck_hd_tien'        => 'gia_thue >= 0 AND tien_coc >= 0 AND tien_tru_hu_hong >= 0 AND tien_mat_coc >= 0 AND tien_hoan_tien_phong >= 0 AND no_con_lai >= 0',
            ],
            'dich_vu'          => ['ck_dv_don_gia' => 'don_gia >= 0'],
            'hoa_don'          => ['ck_hoa_don_tien' => 'no_cu >= 0 AND tong_tien >= 0', 'ck_hoa_don_ky' => 'den_ngay >= tu_ngay'],
            'su_co'            => ['ck_su_co_chi_phi' => 'chi_phi >= 0'],
            'chi_tiet_hoa_don' => ['ck_cthd_mot_trong_hai' => 'dich_vu_id IS NULL OR su_co_id IS NULL'],
            'thanh_toan'       => [
                'ck_tt_so_tien'      => 'so_tien > 0',
                'ck_tt_mot_trong_hai' => "(loai = 'hoa_don' AND hoa_don_id IS NOT NULL AND yeu_cau_id IS NULL) OR (loai = 'coc' AND yeu_cau_id IS NOT NULL AND hoa_don_id IS NULL)",
            ],
        ];
        foreach ($checks as $bang => $dsRangBuoc) {
            foreach ($dsRangBuoc as $ten => $dieuKien) {
                DB::statement("ALTER TABLE {$bang} ADD CONSTRAINT {$ten} CHECK ({$dieuKien})");
            }
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['thanh_toan', 'chi_tiet_hoa_don', 'su_co', 'hoa_don', 'dich_vu', 'chi_so_dien_nuoc',
                  'nguoi_o', 'hop_dong', 'yeu_cau_giu_cho', 'lich_xem', 'anh_phong', 'phong'] as $bang) {
            Schema::dropIfExists($bang);
        }
        Schema::enableForeignKeyConstraints();
    }
};
