-- ============================================================
-- PATCH PHÂN QUYỀN THEO VAI TRÒ - NovaHM
-- Chạy file này SAU KHI đã import novahm_database.sql gốc.
-- Vào phpMyAdmin -> chọn database novahm_db -> tab Import -> chọn file này -> Go.
-- An toàn để chạy lại nhiều lần (dùng IF NOT EXISTS / DELETE trước INSERT).
-- ============================================================

-- 1. Đổi tên vai trò "Nhân sự" -> "Nhân viên Hành chính - Nhân sự tổng hợp"
UPDATE vai_tro SET ten_vai_tro = 'Nhân viên Hành chính - Nhân sự tổng hợp' WHERE ma_vai_tro = 'HR';

-- 2. Cấu hình hệ thống: luật chấm công + quy tắc tính phép (chỉ Admin được sửa)
CREATE TABLE IF NOT EXISTS cau_hinh_he_thong (
    id                          INT AUTO_INCREMENT PRIMARY KEY,
    gio_vao_chuan               TIME DEFAULT '08:00:00',
    gio_ra_chuan                TIME DEFAULT '17:00:00',
    so_phut_cho_phep_tre        INT DEFAULT 0,
    so_ngay_phep_mac_dinh       DECIMAL(4,1) DEFAULT 12,
    so_ngay_phep_toi_da_chuyen  DECIMAL(4,1) DEFAULT 5,
    ngay_ap_dung                DATE DEFAULT (CURRENT_DATE),
    id_nguoi_cap_nhat           INT NULL,
    FOREIGN KEY (id_nguoi_cap_nhat) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO cau_hinh_he_thong (gio_vao_chuan, gio_ra_chuan, so_phut_cho_phep_tre, so_ngay_phep_mac_dinh, so_ngay_phep_toi_da_chuyen)
SELECT '08:00:00', '17:00:00', 0, 12, 5
WHERE NOT EXISTS (SELECT 1 FROM cau_hinh_he_thong);

-- 3. Đánh giá KPI (Trưởng phòng chấm cho cấp dưới trong phòng mình)
CREATE TABLE IF NOT EXISTS danh_gia_kpi (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    id_nv              INT NOT NULL,
    id_nguoi_danh_gia  INT NOT NULL,
    ky_danh_gia        VARCHAR(20) NOT NULL,   -- ví dụ: 2026-Q3, 2026-08
    diem_kpi           DECIMAL(5,2),
    nhan_xet           TEXT,
    ngay_tao           DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nv_ky (id_nv, ky_danh_gia),
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguoi_danh_gia) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Đơn từ khác: giải trình đi muộn / đăng ký tăng ca (Trưởng phòng duyệt cho phòng mình)
CREATE TABLE IF NOT EXISTS don_tu_khac (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_nv          INT NOT NULL,
    loai_don       ENUM('Giải trình đi muộn','Đăng ký tăng ca') NOT NULL,
    ngay           DATE NOT NULL,
    gio_bat_dau    TIME,
    gio_ket_thuc   TIME,
    ly_do          VARCHAR(255),
    trang_thai     ENUM('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
    id_nguoi_duyet INT NULL,
    ngay_duyet     DATETIME NULL,
    ngay_tao       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguoi_duyet) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Gán quyền hạn chi tiết theo vai trò (dùng lại 2 bảng quyen_han / vai_tro_quyen có sẵn nhưng chưa dùng)
DELETE FROM vai_tro_quyen;

-- ADMIN: full access, có tất cả quyền
INSERT INTO vai_tro_quyen (id_vai_tro, id_quyen)
SELECT vt.id, qh.id FROM vai_tro vt JOIN quyen_han qh ON 1=1 WHERE vt.ma_vai_tro = 'ADMIN';

-- HR (Hành chính - Nhân sự tổng hợp): hồ sơ nhân viên + chấm công (đầy đủ)
INSERT INTO vai_tro_quyen (id_vai_tro, id_quyen)
SELECT vt.id, qh.id FROM vai_tro vt JOIN quyen_han qh
  ON qh.ma_quyen IN ('NHANVIEN_XEM','NHANVIEN_SUA','CHAMCONG_XEM','CHAMCONG_SUA')
WHERE vt.ma_vai_tro = 'HR';

-- KETOAN: xem chấm công (read-only, không có CHAMCONG_SUA) + toàn quyền tài chính (lương, cấu hình lương)
INSERT INTO vai_tro_quyen (id_vai_tro, id_quyen)
SELECT vt.id, qh.id FROM vai_tro vt JOIN quyen_han qh
  ON qh.ma_quyen IN ('CHAMCONG_XEM','LUONG_XEM','LUONG_DUYET','CAUHINH_SUA')
WHERE vt.ma_vai_tro = 'KETOAN';

-- TRUONGPHONG: xem nhân viên (sẽ lọc theo phòng ban trong code), duyệt đơn từ (phép + đơn khác)
INSERT INTO vai_tro_quyen (id_vai_tro, id_quyen)
SELECT vt.id, qh.id FROM vai_tro vt JOIN quyen_han qh
  ON qh.ma_quyen IN ('NHANVIEN_XEM','PHEP_DUYET')
WHERE vt.ma_vai_tro = 'TRUONGPHONG';

-- NHANVIEN: không có quyền quản trị nào (chỉ dùng giao diện tự phục vụ riêng, không qua bảng quyền này)
