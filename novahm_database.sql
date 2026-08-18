-- ============================================================
--  NOVAHM - HỆ THỐNG QUẢN LÝ NHÂN SỰ
--  Cơ sở dữ liệu MySQL đầy đủ (thiết kế lại + bổ sung module còn thiếu)
--  Charset: utf8mb4 (hỗ trợ tiếng Việt có dấu đầy đủ)
-- ============================================================

DROP DATABASE IF EXISTS novahm_db;
CREATE DATABASE novahm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE novahm_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. NHÓM DANH MỤC DÙNG CHUNG (đã bị "phẳng" thành text trong HTML gốc
--    -> nay tách bảng riêng để dễ quản lý & báo cáo)
-- ============================================================

-- 1.1 Phòng ban
CREATE TABLE phong_ban (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ma_phong_ban  VARCHAR(20)  NOT NULL UNIQUE,
    ten_phong_ban VARCHAR(100) NOT NULL,
    truong_phong_id INT NULL,               -- FK tới nhan_vien, khai báo sau
    mo_ta         VARCHAR(255),
    ngay_tao      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 1.2 Chức vụ
CREATE TABLE chuc_vu (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ten_chuc_vu VARCHAR(100) NOT NULL UNIQUE,
    cap_bac     TINYINT DEFAULT 1,           -- 1: NV, 2: Trưởng nhóm, 3: Trưởng phòng, 4: BGĐ...
    mo_ta       VARCHAR(255)
) ENGINE=InnoDB;

-- 1.3 Vai trò hệ thống (phân quyền đăng nhập)
CREATE TABLE vai_tro (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ma_vai_tro VARCHAR(30) NOT NULL UNIQUE,  -- ADMIN, HR, KETOAN, TRUONGPHONG, NHANVIEN
    ten_vai_tro VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- 1.4 Danh sách quyền & bảng phân quyền chi tiết (RBAC) -- tính năng còn thiếu
CREATE TABLE quyen_han (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    ma_quyen  VARCHAR(50) NOT NULL UNIQUE,   -- vd: NHANVIEN_XEM, LUONG_DUYET, PHEP_DUYET
    mo_ta     VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE vai_tro_quyen (
    id_vai_tro INT NOT NULL,
    id_quyen   INT NOT NULL,
    PRIMARY KEY (id_vai_tro, id_quyen),
    FOREIGN KEY (id_vai_tro) REFERENCES vai_tro(id) ON DELETE CASCADE,
    FOREIGN KEY (id_quyen) REFERENCES quyen_han(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 1.5 Loại phép (trong giao diện chỉ hard-code "Phép năm/Nghỉ ốm/Không lương")
CREATE TABLE loai_phep (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    ten_loai_phep    VARCHAR(50) NOT NULL UNIQUE,
    huong_luong      BOOLEAN DEFAULT TRUE,
    tru_vao_quy_phep BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- ============================================================
-- 2. NHÂN VIÊN & TÀI KHOẢN
-- ============================================================

CREATE TABLE nhan_vien (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    ma_nv            VARCHAR(20) NOT NULL UNIQUE,      -- NV001, NV002...
    ho_ten           VARCHAR(100) NOT NULL,
    ngay_sinh        DATE,
    gioi_tinh        ENUM('Nam','Nữ','Khác') DEFAULT 'Nam',
    cccd             VARCHAR(20),
    dia_chi          VARCHAR(255),
    sdt              VARCHAR(20),
    email            VARCHAR(100) UNIQUE,
    id_phong_ban     INT,
    id_chuc_vu       INT,
    ngay_vao_lam     DATE,
    ngay_nghi_viec   DATE NULL,
    trang_thai_lam_viec ENUM('Thử việc','Chính thức','Nghỉ việc') DEFAULT 'Thử việc',
    anh_dai_dien     VARCHAR(255),
    ngay_tao         DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_phong_ban) REFERENCES phong_ban(id) ON DELETE SET NULL,
    FOREIGN KEY (id_chuc_vu) REFERENCES chuc_vu(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE phong_ban
    ADD CONSTRAINT fk_phongban_truongphong
    FOREIGN KEY (truong_phong_id) REFERENCES nhan_vien(id) ON DELETE SET NULL;

-- Tài khoản đăng nhập (tách khỏi nhan_vien để 1 nhân viên có thể không có tài khoản,
-- và để mã hoá mật khẩu đúng chuẩn thay vì so sánh chuỗi thô như login.html hiện tại)
CREATE TABLE nguoi_dung (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_nv         INT NULL,                 -- NULL nếu là tài khoản hệ thống không gắn nhân viên
    email         VARCHAR(100) NOT NULL UNIQUE,
    mat_khau_hash VARCHAR(255) NOT NULL,     -- lưu bcrypt/argon2 hash, KHÔNG lưu plaintext
    id_vai_tro    INT NOT NULL,
    trang_thai    ENUM('Hoạt động','Khoá') DEFAULT 'Hoạt động',
    lan_dang_nhap_cuoi DATETIME NULL,
    token_reset_mk     VARCHAR(255) NULL,   -- phục vụ chức năng "Quên mật khẩu" còn thiếu trên login.html
    token_het_han      DATETIME NULL,
    ngay_tao      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE SET NULL,
    FOREIGN KEY (id_vai_tro) REFERENCES vai_tro(id)
) ENGINE=InnoDB;

-- Tài liệu / hồ sơ đính kèm nhân viên (CV, bằng cấp, CCCD scan...) -- tính năng còn thiếu
CREATE TABLE tai_lieu_nhan_vien (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_nv          INT NOT NULL,
    ten_tai_lieu   VARCHAR(150) NOT NULL,
    loai_tai_lieu  VARCHAR(50),              -- CCCD, Bằng cấp, CV, Hợp đồng...
    duong_dan_file VARCHAR(255) NOT NULL,
    ngay_tai_len   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Hợp đồng lao động (khớp hopdong.html)
CREATE TABLE hop_dong_lao_dong (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ma_hd           VARCHAR(30) NOT NULL UNIQUE,   -- HD-2024-001
    id_nv           INT NOT NULL,
    loai_hop_dong   VARCHAR(100) NOT NULL,         -- Thử việc / Xác định thời hạn / Không xác định thời hạn
    ngay_bat_dau    DATE NOT NULL,
    ngay_ket_thuc   DATE NULL,                     -- NULL = không xác định thời hạn
    muc_luong_hd    DECIMAL(15,2),
    file_dinh_kem   VARCHAR(255),
    trang_thai      ENUM('Còn hiệu lực','Sắp hết hạn','Hết hạn','Đã chấm dứt') DEFAULT 'Còn hiệu lực',
    ngay_tao        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3. CHẤM CÔNG
-- ============================================================

-- Thiết bị chấm công (vân tay/thẻ) -- tính năng còn thiếu, được nhắc tới trong lichsuravao.html
CREATE TABLE thiet_bi_cham_cong (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ten_thiet_bi VARCHAR(100) NOT NULL,     -- "Cổng chính (Máy A1)"
    vi_tri       VARCHAR(150),
    trang_thai   ENUM('Hoạt động','Bảo trì','Ngưng dùng') DEFAULT 'Hoạt động'
) ENGINE=InnoDB;

-- Lịch sử quét thẻ/vân tay thô (khớp lichsuravao.html)
CREATE TABLE lich_su_ra_vao (
    id             BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_nv          INT NOT NULL,
    thoi_gian      DATETIME NOT NULL,
    loai_su_kien   ENUM('Check-in','Check-out') NOT NULL,
    id_thiet_bi    INT,
    trang_thai     ENUM('Hợp lệ','Muộn','Bất thường') DEFAULT 'Hợp lệ',
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_thiet_bi) REFERENCES thiet_bi_cham_cong(id) ON DELETE SET NULL,
    INDEX idx_lichsu_nv_thoigian (id_nv, thoi_gian)
) ENGINE=InnoDB;

-- Bảng chấm công ngày đã tổng hợp giờ vào/ra (khớp bangchamcong.html)
CREATE TABLE bang_cham_cong_ngay (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_nv        INT NOT NULL,
    ngay         DATE NOT NULL,
    gio_vao      TIME NULL,
    gio_ra       TIME NULL,
    so_gio_lam   DECIMAL(4,2) DEFAULT 0,
    so_phut_tre  INT DEFAULT 0,
    trang_thai   ENUM('Đúng giờ','Đi muộn','Về sớm','Vắng mặt','Nghỉ phép') DEFAULT 'Vắng mặt',
    ghi_chu      VARCHAR(255),
    UNIQUE KEY uq_nv_ngay (id_nv, ngay),
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tăng ca -- tính năng còn thiếu, cần thiết cho một hệ thống chấm công/lương hoàn chỉnh
CREATE TABLE tang_ca (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_nv         INT NOT NULL,
    ngay          DATE NOT NULL,
    so_gio        DECIMAL(4,2) NOT NULL,
    he_so_luong   DECIMAL(3,2) DEFAULT 1.50,   -- 150%, 200%, 300% tuỳ ngày thường/lễ/tết
    ly_do         VARCHAR(255),
    trang_thai    ENUM('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
    id_nguoi_duyet INT NULL,
    ngay_duyet    DATETIME NULL,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguoi_duyet) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4. LƯƠNG
-- ============================================================

-- Cấu hình lương (khớp cauhinhluong.html) -- lưu lịch sử theo ngày áp dụng để không mất
-- dữ liệu lương cũ khi thay đổi cấu hình (điểm còn thiếu trong bản HTML gốc)
CREATE TABLE cau_hinh_luong (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    so_ngay_cong_chuan    INT DEFAULT 22,
    luong_co_so           DECIMAL(15,2) DEFAULT 1800000,
    phu_cap_an_trua       DECIMAL(15,2) DEFAULT 730000,
    phu_cap_di_lai        DECIMAL(15,2) DEFAULT 500000,
    ty_le_bhxh            DECIMAL(5,2) DEFAULT 8.00,
    ty_le_bhyt            DECIMAL(5,2) DEFAULT 1.50,
    ty_le_bhtn            DECIMAL(5,2) DEFAULT 1.00,
    muc_phat_di_muon      DECIMAL(15,2) DEFAULT 10000,
    ngay_ap_dung          DATE NOT NULL,
    ngay_tao              DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng lương tháng (khớp bangtinhluongthang.html)
CREATE TABLE bang_luong_thang (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_nv               INT NOT NULL,
    thang               TINYINT NOT NULL,
    nam                 SMALLINT NOT NULL,
    luong_co_ban        DECIMAL(15,2) NOT NULL,
    ngay_cong_thuc_te   DECIMAL(4,1) DEFAULT 0,
    ngay_cong_chuan     DECIMAL(4,1) DEFAULT 22,
    tong_phu_cap        DECIMAL(15,2) DEFAULT 0,
    tong_khau_tru       DECIMAL(15,2) DEFAULT 0,
    thuc_linh           DECIMAL(15,2) DEFAULT 0,
    trang_thai          ENUM('Chờ duyệt','Đã duyệt','Đã thanh toán') DEFAULT 'Chờ duyệt',
    id_nguoi_duyet      INT NULL,
    ngay_duyet          DATETIME NULL,
    ngay_tao            DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nv_thang_nam (id_nv, thang, nam),
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguoi_duyet) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Chi tiết các khoản phụ cấp trong phiếu lương -- tính năng còn thiếu (bản gốc chỉ có 1 số tổng)
CREATE TABLE chi_tiet_phu_cap (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_bang_luong  INT NOT NULL,
    ten_phu_cap    VARCHAR(100) NOT NULL,   -- Ăn trưa, Xăng xe, Tăng ca, Thưởng KPI...
    so_tien        DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (id_bang_luong) REFERENCES bang_luong_thang(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Chi tiết các khoản khấu trừ trong phiếu lương
CREATE TABLE chi_tiet_khau_tru (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_bang_luong  INT NOT NULL,
    ten_khoan_tru  VARCHAR(100) NOT NULL,   -- BHXH, BHYT, BHTN, Đi muộn, Thuế TNCN...
    so_tien        DECIMAL(15,2) NOT NULL,
    ghi_chu        VARCHAR(255),
    FOREIGN KEY (id_bang_luong) REFERENCES bang_luong_thang(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. NGHỈ PHÉP
-- ============================================================

-- Đơn nghỉ phép (khớp duyetdonphep.html)
CREATE TABLE don_nghi_phep (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_nv          INT NOT NULL,
    id_loai_phep   INT NOT NULL,
    tu_ngay        DATE NOT NULL,
    den_ngay       DATE NOT NULL,
    so_ngay        DECIMAL(3,1) NOT NULL,
    ly_do          VARCHAR(255),
    trang_thai     ENUM('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
    id_nguoi_duyet INT NULL,
    ngay_duyet     DATETIME NULL,
    ghi_chu_duyet  VARCHAR(255),
    ngay_tao       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (id_loai_phep) REFERENCES loai_phep(id),
    FOREIGN KEY (id_nguoi_duyet) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Quỹ phép năm (khớp quyphepnam.html)
CREATE TABLE quy_phep_nam (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_nv      INT NOT NULL,
    nam        SMALLINT NOT NULL,
    tong_phep  DECIMAL(4,1) DEFAULT 12,
    da_su_dung DECIMAL(4,1) DEFAULT 0,
    con_lai    DECIMAL(4,1) GENERATED ALWAYS AS (tong_phep - da_su_dung) STORED,
    UNIQUE KEY uq_nv_nam (id_nv, nam),
    FOREIGN KEY (id_nv) REFERENCES nhan_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. TIỆN ÍCH HỆ THỐNG (còn thiếu trong bản HTML gốc)
-- ============================================================

-- Thông báo (chuông thông báo trên header đang chưa có dữ liệu thật)
CREATE TABLE thong_bao (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    tieu_de       VARCHAR(150) NOT NULL,
    noi_dung      VARCHAR(500),
    lien_ket      VARCHAR(255),             -- link tới trang liên quan, vd đơn phép cần duyệt
    da_doc        BOOLEAN DEFAULT FALSE,
    ngay_tao      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nguoi_dung) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Nhật ký hoạt động (audit log) -- bắt buộc cho hệ thống HR thật để truy vết ai sửa gì
CREATE TABLE nhat_ky_hoat_dong (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NULL,
    hanh_dong     VARCHAR(50) NOT NULL,     -- CREATE, UPDATE, DELETE, LOGIN, APPROVE...
    doi_tuong     VARCHAR(50) NOT NULL,     -- tên bảng bị tác động
    id_doi_tuong  INT NULL,
    chi_tiet      TEXT,
    dia_chi_ip    VARCHAR(45),
    thoi_gian     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nguoi_dung) REFERENCES nguoi_dung(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 7. DỮ LIỆU MẪU (khớp với dữ liệu đang hard-code trong các file HTML)
-- ============================================================

INSERT INTO vai_tro (ma_vai_tro, ten_vai_tro) VALUES
('ADMIN','Quản trị viên'),
('HR','Nhân sự'),
('KETOAN','Kế toán'),
('TRUONGPHONG','Trưởng phòng'),
('NHANVIEN','Nhân viên');

INSERT INTO quyen_han (ma_quyen, mo_ta) VALUES
('NHANVIEN_XEM','Xem danh sách nhân viên'),
('NHANVIEN_SUA','Thêm/sửa/xoá nhân viên'),
('CHAMCONG_XEM','Xem bảng chấm công'),
('CHAMCONG_SUA','Chỉnh sửa giờ chấm công'),
('LUONG_XEM','Xem bảng lương'),
('LUONG_DUYET','Duyệt bảng lương'),
('PHEP_DUYET','Duyệt đơn nghỉ phép'),
('CAUHINH_SUA','Sửa cấu hình lương hệ thống');

INSERT INTO phong_ban (ma_phong_ban, ten_phong_ban) VALUES
('KT','Kỹ thuật'),
('NS','Nhân sự'),
('KETOAN','Kế toán'),
('KD','Kinh doanh');

INSERT INTO chuc_vu (ten_chuc_vu, cap_bac) VALUES
('Lập trình viên Senior', 2),
('Chuyên viên HR', 1),
('Kế toán trưởng', 3);

INSERT INTO loai_phep (ten_loai_phep, huong_luong, tru_vao_quy_phep) VALUES
('Phép năm', TRUE, TRUE),
('Nghỉ ốm', TRUE, FALSE),
('Nghỉ không lương', FALSE, FALSE);

INSERT INTO thiet_bi_cham_cong (ten_thiet_bi, vi_tri) VALUES
('Cổng chính (Máy A1)','Sảnh chính'),
('Cổng chính (Máy A2)','Sảnh chính');

INSERT INTO nhan_vien (ma_nv, ho_ten, sdt, email, id_phong_ban, id_chuc_vu, ngay_vao_lam, trang_thai_lam_viec) VALUES
('NV001','Nguyễn Văn A','0912345678','nguyenvana@novacompany.vn', 1, 1, '2024-01-01','Chính thức'),
('NV002','Trần Thị B','0987654321','tranthib@novacompany.vn', 2, 2, '2024-03-01','Thử việc'),
('NV003','Lê Hoàng C','0933111222','lehoangc@novacompany.vn', 3, 3, '2023-06-15','Chính thức');

INSERT INTO nguoi_dung (id_nv, email, mat_khau_hash, id_vai_tro) VALUES
(1, 'admin@novacompany.vn', '$2y$10$REPLACE_WITH_REAL_BCRYPT_HASH', 1);

INSERT INTO hop_dong_lao_dong (ma_hd, id_nv, loai_hop_dong, ngay_bat_dau, ngay_ket_thuc, trang_thai) VALUES
('HD-2024-001', 1, 'Xác định thời hạn (12 tháng)', '2024-01-01', '2024-12-31', 'Còn hiệu lực'),
('HD-2024-002', 2, 'Thử việc (2 tháng)', '2024-03-01', '2024-04-30', 'Sắp hết hạn'),
('HD-2023-089', 3, 'Không xác định thời hạn', '2023-06-15', NULL, 'Còn hiệu lực');

INSERT INTO cau_hinh_luong (so_ngay_cong_chuan, luong_co_so, phu_cap_an_trua, phu_cap_di_lai, ty_le_bhxh, ty_le_bhyt, ty_le_bhtn, muc_phat_di_muon, ngay_ap_dung) VALUES
(22, 1800000, 730000, 500000, 8.00, 1.50, 1.00, 10000, '2026-01-01');

INSERT INTO quy_phep_nam (id_nv, nam, tong_phep, da_su_dung) VALUES
(1, 2026, 12, 1),
(2, 2026, 12, 8),
(3, 2026, 14, 14);

-- ============================================================
-- 8. TRIGGER TỰ ĐỘNG (khắc phục việc bản HTML gốc phải tính tay)
-- ============================================================

DELIMITER $$

-- Khi đơn nghỉ phép chuyển sang "Đã duyệt" -> tự trừ vào quỹ phép năm tương ứng
CREATE TRIGGER trg_don_phep_sau_khi_duyet
AFTER UPDATE ON don_nghi_phep
FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'Đã duyệt' AND OLD.trang_thai <> 'Đã duyệt' THEN
        UPDATE quy_phep_nam
        SET da_su_dung = da_su_dung + NEW.so_ngay
        WHERE id_nv = NEW.id_nv AND nam = YEAR(NEW.tu_ngay);
    END IF;
END$$

-- Tự tính số giờ làm và trạng thái khi cập nhật giờ vào/ra trong bảng chấm công ngày
CREATE TRIGGER trg_tinh_gio_lam_truoc_khi_luu
BEFORE INSERT ON bang_cham_cong_ngay
FOR EACH ROW
BEGIN
    IF NEW.gio_vao IS NOT NULL AND NEW.gio_ra IS NOT NULL THEN
        SET NEW.so_gio_lam = TIME_TO_SEC(TIMEDIFF(NEW.gio_ra, NEW.gio_vao)) / 3600;
    END IF;
    IF NEW.gio_vao IS NOT NULL AND NEW.gio_vao > '08:00:00' THEN
        SET NEW.so_phut_tre = TIME_TO_SEC(TIMEDIFF(NEW.gio_vao, '08:00:00')) / 60;
        SET NEW.trang_thai = 'Đi muộn';
    ELSEIF NEW.gio_vao IS NOT NULL THEN
        SET NEW.trang_thai = 'Đúng giờ';
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 9. VIEW HỖ TRỢ DASHBOARD (khớp 4 thẻ thống kê trên index.html)
-- ============================================================

CREATE VIEW vw_thong_ke_tong_quan AS
SELECT
    (SELECT COUNT(*) FROM nhan_vien WHERE trang_thai_lam_viec <> 'Nghỉ việc') AS tong_nhan_vien,
    (SELECT COUNT(*) FROM bang_cham_cong_ngay WHERE ngay = CURDATE() AND gio_vao IS NOT NULL) AS cham_cong_hom_nay,
    (SELECT COALESCE(SUM(thuc_linh),0) FROM bang_luong_thang WHERE thang = MONTH(CURDATE()) AND nam = YEAR(CURDATE())) AS quy_luong_thang_nay,
    (SELECT COUNT(*) FROM don_nghi_phep WHERE trang_thai = 'Chờ duyệt') AS don_phep_cho_duyet;

-- Danh sách hợp đồng sắp hết hạn (cảnh báo tự động - tính năng còn thiếu)
CREATE VIEW vw_hop_dong_sap_het_han AS
SELECT hd.ma_hd, nv.ma_nv, nv.ho_ten, hd.ngay_ket_thuc,
       DATEDIFF(hd.ngay_ket_thuc, CURDATE()) AS so_ngay_con_lai
FROM hop_dong_lao_dong hd
JOIN nhan_vien nv ON nv.id = hd.id_nv
WHERE hd.ngay_ket_thuc IS NOT NULL
  AND hd.ngay_ket_thuc >= CURDATE()
  AND hd.ngay_ket_thuc <= DATE_ADD(CURDATE(), INTERVAL 30 DAY);
