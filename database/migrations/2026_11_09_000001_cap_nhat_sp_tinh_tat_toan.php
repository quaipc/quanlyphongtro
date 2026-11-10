<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Cập nhật thủ tục tất toán theo script mới: thủ tục KHÔNG tự mở/đóng giao dịch,
// để ứng dụng bọc toàn bộ luồng thanh lý trong một giao dịch. Lấy nguyên văn từ file SQL.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_tinh_tat_toan');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_tinh_tat_toan(IN p_hop_dong_id INT)
BEGIN
  DECLARE v_coc DECIMAL(12,0);
  DECLARE v_hu  DECIMAL(12,0);
  DECLARE v_bao DATE;
  DECLARE v_tra DATE;
  DECLARE v_ket DATE;
  DECLARE v_no  DECIMAL(12,0);
  DECLARE v_du  DECIMAL(12,0);
  DECLARE v_bao_tre TINYINT DEFAULT 0;
  DECLARE v_mien TINYINT DEFAULT 0;
  DECLARE v_gia DECIMAL(12,0);
  DECLARE v_chu TINYINT;
  DECLARE v_tu  DATE;
  DECLARE v_den DATE;
  DECLARE v_hoan_pq DECIMAL(12,0) DEFAULT 0;

  -- Thủ tục này KHÔNG tự mở hay đóng giao dịch, để ứng dụng bọc toàn bộ luồng thanh lý
  -- (lập hóa đơn thanh lý, tất toán, cập nhật hợp đồng và phòng) trong MỘT giao dịch:
  -- lỗi ở bước nào thì hoàn tác cả luồng, không để lại trạng thái dở dang.
  SELECT tien_coc, tien_tru_hu_hong, ngay_bao_tra, ngay_tra_phong, ngay_ket_thuc, mien_phat,
         gia_thue, chu_ky_thu
  INTO v_coc, v_hu, v_bao, v_tra, v_ket, v_mien, v_gia, v_chu
  FROM hop_dong WHERE id = p_hop_dong_id FOR UPDATE;

  IF v_coc IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không tìm thấy hợp đồng';
  END IF;

  -- Nợ = phần chưa được xác nhận thanh toán của các hóa đơn còn mở
  SELECT COALESCE(SUM(h.tong_tien - COALESCE(t.da_tra, 0)), 0) INTO v_no
  FROM hoa_don h
  LEFT JOIN (
    SELECT hoa_don_id, SUM(so_tien) AS da_tra
    FROM thanh_toan
    WHERE trang_thai = 'da_xac_nhan' AND hoa_don_id IS NOT NULL
      AND hinh_thuc <> 'tru_coc'  -- dòng trừ cọc là kết quả của tất toán, không tính là khách đã trả
    GROUP BY hoa_don_id
  ) t ON t.hoa_don_id = h.id
  WHERE h.hop_dong_id = p_hop_dong_id
    AND h.trang_thai IN ('chua_thanh_toan','qua_han');

  -- Báo trễ: trả sớm (trước ngày kết thúc) mà báo trước chưa đủ 1 tháng
  IF v_mien = 0 AND v_bao IS NOT NULL AND v_tra IS NOT NULL
     AND v_tra < v_ket AND v_tra < DATE_ADD(v_bao, INTERVAL 1 MONTH) THEN
    SET v_bao_tre = 1;
  END IF;

  -- Sự cố nặng (mien_phat = 1): hoàn tiền phòng đã trả trước cho những ngày không ở.
  -- Chu kỳ thu chứa ngày trả phòng lấy từ hóa đơn bàn giao hoặc định kỳ gần nhất.
  IF v_mien = 1 AND v_tra IS NOT NULL THEN
    SELECT h.tu_ngay, h.den_ngay INTO v_tu, v_den
    FROM hoa_don h
    WHERE h.hop_dong_id = p_hop_dong_id
      AND h.loai IN ('ban_giao','dinh_ky')
      AND v_tra >= h.tu_ngay AND v_tra < h.den_ngay
    ORDER BY h.tu_ngay DESC
    LIMIT 1;
    IF v_tu IS NOT NULL THEN
      -- Đơn giá ngày = tiền phòng cả chu kỳ đầy đủ / số ngày của chu kỳ đầy đủ
      SET v_hoan_pq = ROUND(v_gia * v_chu * DATEDIFF(v_den, v_tra)
                            / DATEDIFF(DATE_ADD(v_tu, INTERVAL v_chu MONTH), v_tu), 0);
    END IF;
  END IF;

  SET v_du = v_coc - v_no - v_hu;

  IF v_du < 0 THEN
    UPDATE hop_dong
    SET tien_hoan_coc = 0, tien_mat_coc = 0, no_con_lai = -v_du
    WHERE id = p_hop_dong_id;
  ELSEIF v_bao_tre = 1 THEN
    UPDATE hop_dong
    SET tien_hoan_coc = 0, tien_mat_coc = v_du, no_con_lai = 0
    WHERE id = p_hop_dong_id;
  ELSE
    UPDATE hop_dong
    SET tien_hoan_coc = v_du, tien_mat_coc = 0, no_con_lai = 0
    WHERE id = p_hop_dong_id;
  END IF;

  UPDATE hop_dong SET tien_hoan_tien_phong = v_hoan_pq WHERE id = p_hop_dong_id;
END
SQL);
    }

    public function down(): void
    {
        // Không khôi phục bản cũ (bản cũ tự mở giao dịch và không dùng được trong giao dịch của ứng dụng)
    }
};
