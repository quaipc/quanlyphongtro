<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Thủ tục từ chối biên lai cọc (QT20): đặt lại hạn chuyển cọc 24 giờ. Lấy nguyên văn từ file SQL.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_tu_choi_bien_lai');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tu_choi_bien_lai(IN p_thanh_toan_id INT, IN p_nguoi_xac_nhan_id INT)
BEGIN
  DECLARE v_yc INT;
  DECLARE v_tt VARCHAR(20);
  DECLARE v_loai VARCHAR(20);

  START TRANSACTION;
  SELECT yeu_cau_id, trang_thai, loai INTO v_yc, v_tt, v_loai
  FROM thanh_toan WHERE id = p_thanh_toan_id FOR UPDATE;

  IF v_loai IS NULL OR v_loai <> 'coc' OR v_tt <> 'cho_xac_nhan' THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chỉ từ chối được biên lai cọc đang chờ xác nhận';
  END IF;

  UPDATE thanh_toan
  SET trang_thai = 'tu_choi', nguoi_xac_nhan_id = p_nguoi_xac_nhan_id
  WHERE id = p_thanh_toan_id;

  UPDATE yeu_cau_giu_cho
  SET trang_thai = 'cho_coc', han_chuyen_coc = DATE_ADD(NOW(), INTERVAL 24 HOUR)
  WHERE id = v_yc AND trang_thai = 'cho_xac_nhan'
    AND NOT EXISTS (SELECT 1 FROM thanh_toan t2
                    WHERE t2.yeu_cau_id = v_yc AND t2.trang_thai = 'cho_xac_nhan');
  COMMIT;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_tu_choi_bien_lai');
    }
};
