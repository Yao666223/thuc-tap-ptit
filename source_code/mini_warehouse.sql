CREATE DATABASE mini_warehouse;
USE mini_warehouse;

CREATE TABLE san_pham (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_san_pham VARCHAR(100) NOT NULL,
    gia DECIMAL(10,2),
    so_luong INT DEFAULT 0,
    don_vi VARCHAR(20)
);
CREATE TABLE phieu_nhap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ghi_chu TEXT
);

CREATE TABLE chi_tiet_nhap (
    id_phieu_nhap INT,
    id_san_pham INT,
    so_luong INT,
    gia DECIMAL(10,2),
    PRIMARY KEY (id_phieu_nhap, id_san_pham),
    FOREIGN KEY (id_phieu_nhap) REFERENCES phieu_nhap(id),
    FOREIGN KEY (id_san_pham) REFERENCES san_pham(id)
);

CREATE TABLE phieu_xuat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ghi_chu TEXT
);

CREATE TABLE chi_tiet_phieu_xuat (
    id_phieu_xuat INT,
    id_san_pham INT,
    so_luong INT,
    gia DECIMAL(10,2),
    PRIMARY KEY (id_phieu_xuat, id_san_pham),
    FOREIGN KEY (id_phieu_xuat) REFERENCES phieu_xuat(id),
    FOREIGN KEY (id_san_pham) REFERENCES san_pham(id)
);

INSERT INTO san_pham (ten_san_pham, gia, so_luong, don_vi)
VALUES
  ('Sữa tươi TH True Milk 1L', 32000, 120, 'hộp'),
  ('Bánh mì Sandwich', 22000, 200, 'gói'),
  ('Trứng gà ta', 4000, 300, 'quả'),
  ('Mì tôm Hảo Hảo', 3500, 500, 'gói'),
  ('Nước suối Lavie 500ml', 7500, 300, 'chai'),
  ('Gạo ST25', 19000, 1000, 'kg'),
  ('Đường trắng Biên Hòa', 15000, 250, 'kg'),
  ('Dầu ăn Tường An 1L', 40000, 180, 'chai');

INSERT INTO phieu_nhap (ngay_tao, ghi_chu)
VALUES
  ('2025-07-01 08:00:00', 'Nhập hàng đầu tháng'),
  ('2025-07-03 14:30:00', 'Bổ sung thêm gạo và dầu ăn'),
  ('2025-07-05 09:15:00', 'Nhập bổ sung hàng tạp hóa');

INSERT INTO chi_tiet_nhap (id_phieu_nhap, id_san_pham, so_luong, gia)
VALUES
  (1, 1, 50, 30000),
  (1, 2, 80, 20000),
  (1, 3, 100, 3800),
  (1, 4, 150, 3300);
  
INSERT INTO chi_tiet_nhap (id_phieu_nhap, id_san_pham, so_luong, gia)
VALUES
  (2, 6, 500, 18500),
  (2, 8, 100, 39000);

INSERT INTO chi_tiet_nhap (id_phieu_nhap, id_san_pham, so_luong, gia)
VALUES
  (3, 5, 100, 7000),
  (3, 7, 150, 14500);
  
INSERT INTO phieu_xuat (ngay_tao, ghi_chu)
VALUES
  ('2025-07-04 09:00:00', 'Xuất cho cửa hàng số 1'),
  ('2025-07-06 16:20:00', 'Xuất đi đơn hàng online'),
  ('2025-07-07 10:30:00', 'Giao cho đại lý phân phối khu vực Q1');
  
INSERT INTO chi_tiet_phieu_xuat (id_phieu_xuat, id_san_pham, so_luong, gia)
VALUES
  (1, 1, 20, 35000),
  (1, 2, 30, 25000),
  (1, 4, 50, 4000);
  
INSERT INTO chi_tiet_phieu_xuat (id_phieu_xuat, id_san_pham, so_luong, gia)
VALUES
  (2, 3, 80, 4200),
  (2, 5, 60, 8000);
INSERT INTO chi_tiet_phieu_xuat (id_phieu_xuat, id_san_pham, so_luong, gia)
VALUES
  (3, 6, 200, 20000),
  (3, 7, 100, 16000);
  
SELECT * FROM san_pham;
SELECT * FROM phieu_nhap;
SELECT * FROM chi_tiet_nhap;
SELECT * FROM phieu_xuat;
SELECT * FROM chi_tiet_phieu_xuat;

use mini_warehouse;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'nhanvien'
);
INSERT INTO users (username, password, role) VALUES
('nv1', MD5('nv123'), 'nhanvien');

USE mini_warehouse;

CREATE TABLE IF NOT EXISTS stock_changes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  san_pham_id INT NOT NULL,
  change_qty INT NOT NULL,
  change_type ENUM('nhap','xuat','adjust') NOT NULL,
  ref_table VARCHAR(50) DEFAULT NULL,
  ref_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  note VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (san_pham_id) REFERENCES san_pham(id),
  INDEX idx_sanph(id),
  INDEX idx_created_at (created_at)
);
