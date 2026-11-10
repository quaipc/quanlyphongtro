<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Cập nhật thủ tục báo trả phòng theo script mới (QT23): ngày trả không được sau ngày kết thúc hợp đồng.
// Lấy nguyên văn từ file SQL.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_bao_tra_phong');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_bao_tra_phong(IN p_hop_dong_id INT, IN p_ngay_tra DATE)
BEGIN
  DECLARE v_tt VARCHAR(20);
  DECLARE v_ket DATE;
  DECLARE v_mien TINYINT;
  DECLARE v_pt VARCHAR(20);

  START TRANSACTION;
  SELECT h.trang_thai, h.ngay_ket_thuc, h.mien_phat, p.trang_thai INTO v_tt, v_ket, v_mien, v_pt
  FROM hop_dong h JOIN phong p ON p.id = h.phong_id
  WHERE h.id = p_hop_dong_id FOR UPDATE;

  -- Chỉ báo trả khi hợp đồng còn hiệu lực và khách đã nhận phòng (phòng đang thuê)
  IF v_tt IS NULL OR v_tt <> 'hieu_luc' OR v_pt <> 'dang_thue' THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hợp đồng không tồn tại, không còn hiệu lực hoặc khách chưa nhận phòng';
  END IF;
  IF p_ngay_tra < CURDATE() THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ngày trả phòng phải từ hôm nay trở đi';
  END IF;
  IF p_ngay_tra > v_ket THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ngày trả phòng không được sau ngày kết thúc hợp đồng; hãy gia hạn hợp đồng trước';
  END IF;

  UPDATE hop_dong
  SET ngay_bao_tra = CURDATE(), ngay_tra_phong = p_ngay_tra, trang_thai = 'cho_thanh_ly'
  WHERE id = p_hop_dong_id;

  UPDATE phong p
  JOIN hop_dong h ON h.phong_id = p.id
  SET p.trang_thai = 'cho_tra_phong'
  WHERE h.id = p_hop_dong_id AND p.trang_thai = 'dang_thue';
  COMMIT;

  SELECT (v_mien = 0 AND p_ngay_tra < v_ket
          AND p_ngay_tra < DATE_ADD(CURDATE(), INTERVAL 1 MONTH)) AS bao_tre;
END
SQL);
    }

    public function down(): void
    {
        // Không khôi phục bản cũ (thiếu kiểm tra QT23)
    }
};
