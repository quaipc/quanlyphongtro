<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Thủ tục và view lấy nguyên từ quan_ly_phong_cho_thue.sql.
// Sự kiện ev_xu_ly_het_han (MySQL event) được thay bằng Laravel Scheduler (xem routes/console.php).
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
-- Khách tự hủy yêu cầu giữ chỗ.
--   cho_duyet, cho_coc : chưa có tiền, hủy bình thường
--   cho_xac_nhan       : khách đã chuyển nhưng chưa được xác nhận, chủ trọ phải hoàn lại tiền
--   da_coc, da_ky      : khách tự hủy sau khi đã cọc (kể cả đã ký nhưng chưa bàn giao), mất cọc
CREATE PROCEDURE sp_khach_huy_giu_cho(IN p_yeu_cau_id INT)
BEGIN
  DECLARE v_phong INT;
  DECLARE v_tt VARCHAR(20);
  DECLARE v_pt VARCHAR(20);

  START TRANSACTION;
  SELECT y.phong_id, y.trang_thai, p.trang_thai INTO v_phong, v_tt, v_pt
  FROM yeu_cau_giu_cho y JOIN phong p ON p.id = y.phong_id
  WHERE y.id = p_yeu_cau_id FOR UPDATE;

  -- 'da_ky' chỉ được hủy khi chưa bàn giao (phòng vẫn 'da_giu_cho')
  IF v_tt IS NULL
     OR v_tt NOT IN ('cho_duyet','cho_coc','cho_xac_nhan','da_coc','da_ky')
     OR (v_tt = 'da_ky' AND v_pt <> 'da_giu_cho') THEN
    ROLLBACK;
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Yêu cầu không tồn tại hoặc không thể hủy ở trạng thái này';
  END IF;

  UPDATE yeu_cau_giu_cho
  SET trang_thai = 'da_huy',
      xu_ly_coc = CASE v_tt WHEN 'cho_xac_nhan' THEN 'cho_xu_ly'
                            WHEN 'da_coc' THEN 'mat_coc'
                            WHEN 'da_ky' THEN 'mat_coc'
                            ELSE 'khong_ap_dung' END
  WHERE id = p_yeu_cau_id;

  IF v_tt <> 'cho_duyet' THEN
    UPDATE phong SET trang_thai = 'trong' WHERE id = v_phong AND trang_thai = 'da_giu_cho';
  END IF;

  IF v_tt IN ('da_coc','da_ky') THEN
    UPDATE hop_dong SET trang_thai = 'da_huy'
    WHERE yeu_cau_id = p_yeu_cau_id AND trang_thai IN ('cho_ky','hieu_luc');
  END IF;
  COMMIT;
END
SQL);

        DB::unprepared(<<<'SQL'
-- Chạy định kỳ: hết hạn chuyển cọc, hết hạn giữ chỗ, hóa đơn quá hạn.
CREATE PROCEDURE sp_xu_ly_het_han()
BEGIN
  START TRANSACTION;

  -- (1) Quá 24 giờ chưa chuyển cọc: hết hạn, phòng về trống.
  --     Yêu cầu đã có biên lai chờ xác nhận (cho_xac_nhan) không bị tự hủy.
  UPDATE yeu_cau_giu_cho y
  JOIN phong p ON p.id = y.phong_id
  SET y.trang_thai = 'het_han',
      p.trang_thai = IF(p.trang_thai = 'da_giu_cho', 'trong', p.trang_thai)
  WHERE y.trang_thai = 'cho_coc' AND y.han_chuyen_coc < NOW();

  -- (2) Đã cọc nhưng quá 7 ngày chưa ký: hủy hợp đồng chờ ký, phòng về trống.
  --     Cọc chuyển sang 'cho_xu_ly' để chủ trọ quyết định giữ hay hoàn
  --     (khách không ký có thể do lỗi của chủ trọ).
  UPDATE hop_dong h
  JOIN yeu_cau_giu_cho y ON y.id = h.yeu_cau_id
  SET h.trang_thai = 'da_huy'
  WHERE y.trang_thai = 'da_coc' AND y.han_giu < CURDATE() AND h.trang_thai = 'cho_ky';

  UPDATE yeu_cau_giu_cho y
  JOIN phong p ON p.id = y.phong_id
  SET y.trang_thai = 'het_han',
      y.xu_ly_coc = 'cho_xu_ly',
      p.trang_thai = IF(p.trang_thai = 'da_giu_cho', 'trong', p.trang_thai)
  WHERE y.trang_thai = 'da_coc' AND y.han_giu < CURDATE();

  -- (3) Hợp đồng đã ký nhưng quá ngày bắt đầu mà khách chưa nhận phòng
  --     (phòng vẫn 'da_giu_cho'): hủy hợp đồng, phòng về trống,
  --     cọc chuyển sang 'cho_xu_ly' để chủ trọ quyết định.
  UPDATE yeu_cau_giu_cho y
  JOIN hop_dong h ON h.yeu_cau_id = y.id
  JOIN phong p ON p.id = h.phong_id
  SET y.trang_thai = 'da_huy',
      y.xu_ly_coc = 'cho_xu_ly',
      h.trang_thai = 'da_huy',
      p.trang_thai = 'trong'
  WHERE h.trang_thai = 'hieu_luc'
    AND p.trang_thai = 'da_giu_cho'
    AND h.ngay_bat_dau < CURDATE();

  -- (4) Hóa đơn quá hạn thanh toán.
  UPDATE hoa_don SET trang_thai = 'qua_han'
  WHERE trang_thai = 'chua_thanh_toan' AND han_thanh_toan < CURDATE();

  COMMIT;
END
SQL);

        DB::unprepared(<<<'SQL'
-- Khách báo trả phòng. Ghi ngày báo (hôm nay) và ngày trả dự kiến, chuyển hợp đồng
-- sang 'cho_thanh_ly' và phòng sang 'cho_tra_phong'.
-- Kết quả trả về cột bao_tre = 1 nếu là trả sớm mà báo trước chưa đủ 1 tháng.
-- Giao diện nên tính trước điều kiện này và yêu cầu khách xác nhận trước khi gọi thủ tục.
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

        DB::unprepared(<<<'SQL'
-- Tính tất toán khi thanh lý (chỉ tính và ghi kết quả vào hợp đồng).
--   du = tiền cọc - hóa đơn chưa trả hết - tiền trừ hư hỏng
--   du >= 0, báo đúng hạn : hoàn lại du
--   du >= 0, báo trễ      : phần du bị mất (tien_mat_coc), hoàn 0
--   du <  0               : cọc dùng hết, phần thiếu ghi vào no_con_lai
-- Việc đánh dấu hóa đơn đã trả bằng hình thức 'tru_coc' do ứng dụng thực hiện.
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

  START TRANSACTION;
  SELECT tien_coc, tien_tru_hu_hong, ngay_bao_tra, ngay_tra_phong, ngay_ket_thuc, mien_phat,
         gia_thue, chu_ky_thu
  INTO v_coc, v_hu, v_bao, v_tra, v_ket, v_mien, v_gia, v_chu
  FROM hop_dong WHERE id = p_hop_dong_id FOR UPDATE;

  IF v_coc IS NULL THEN
    ROLLBACK;
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
  COMMIT;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE VIEW v_hoa_don_can_nhac_no AS
SELECT h.id AS hoa_don_id, h.hop_dong_id, h.thang, h.tong_tien, h.han_thanh_toan,
       DATEDIFF(CURDATE(), h.han_thanh_toan) AS so_ngay_qua_han
FROM hoa_don h
WHERE h.trang_thai = 'qua_han'
  AND DATEDIFF(CURDATE(), h.han_thanh_toan) >= 4
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_hoa_don_can_nhac_no');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_khach_huy_giu_cho');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_xu_ly_het_han');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_bao_tra_phong');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_tinh_tat_toan');
    }
};
