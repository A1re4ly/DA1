-- ============================================================
-- PATCH CHỐT BẢNG CÔNG - NovaHM
-- Chạy SAU khi đã import novahm_database.sql VÀ patch_phanquyen.sql.
-- Vào phpMyAdmin -> chọn database novahm_db -> tab Import -> chọn file này -> Go.
-- An toàn để chạy lại nhiều lần.
-- ============================================================

CREATE TABLE IF NOT EXISTS chot_bang_cong (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    thang          TINYINT NOT NULL,
    nam            SMALLINT NOT NULL,
    id_nguoi_chot  INT NOT NULL,
    ngay_chot      DATETIME DEFAULT CURRENT_TIMESTAMP,
    ghi_chu        VARCHAR(255),
    UNIQUE KEY uq_thang_nam (thang, nam),
    FOREIGN KEY (id_nguoi_chot) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB;
