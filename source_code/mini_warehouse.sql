CREATE DATABASE mini_warehouse;
USE mini_warehouse;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    unit_price DECIMAL(10,2),
    quantity INT DEFAULT 0,
    unit VARCHAR(20)
);

CREATE TABLE import_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    note TEXT
);
CREATE TABLE import_details (
    receipt_id INT,
    product_id INT,
    quantity INT,
    unit_price DECIMAL(10,2),
    PRIMARY KEY (receipt_id, product_id),
    FOREIGN KEY (receipt_id) REFERENCES import_receipts(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE TABLE export_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    note TEXT
);
CREATE TABLE export_details (
    receipt_id INT,
    product_id INT,
    quantity INT,
    unit_price DECIMAL(10,2),
    PRIMARY KEY (receipt_id, product_id),
    FOREIGN KEY (receipt_id) REFERENCES export_receipts(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
SHOW TABLES;
INSERT INTO products (name, unit_price, quantity, unit)
VALUES ('Sữa tươi', 15000, 100, 'hộp'),
       ('Bánh mì', 5000, 50, 'ổ'),
       ('Nước suối', 10000, 30, 'chai');

USE mini_warehouse;
INSERT INTO products (name, unit_price, quantity, unit)
VALUES
  ('Sữa tươi TH True Milk', 15000, 100, 'hộp'),
  ('Bánh mì ABC', 5000, 50, 'ổ'),
  ('Nước khoáng Lavie 500ml', 8000, 80, 'chai'),
  ('Trứng gà công nghiệp', 3000, 200, 'quả'),
  ('Mì tôm Hảo Hảo', 3500, 300, 'gói');
  
INSERT INTO import_receipts (note)
VALUES
  ('Nhập hàng đợt đầu tháng 7'),
  ('Bổ sung thêm trứng và nước suối');
  
INSERT INTO import_details (receipt_id, product_id, quantity, unit_price)
VALUES
  (1, 1, 50, 14000),
  (1, 2, 40, 4500),
  (1, 5, 100, 3200);


INSERT INTO import_details (receipt_id, product_id, quantity, unit_price)
VALUES
  (2, 3, 60, 7800),
  (2, 4, 100, 2900);
  
INSERT INTO export_receipts (note)
VALUES
  ('Xuất hàng cho cửa hàng số 1'),
  ('Giao hàng buổi sáng ngày 4/7');
  
INSERT INTO export_details (receipt_id, product_id, quantity, unit_price)
VALUES
  (1, 1, 20, 15500),
  (1, 2, 30, 5500);
  
INSERT INTO export_details (receipt_id, product_id, quantity, unit_price)
VALUES
  (2, 3, 10, 9000),
  (2, 5, 50, 4000);

SELECT * FROM users;
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

USE mini_warehouse;

-- 1. Thêm cột created_by (lưu id của users)
ALTER TABLE phieu_nhap ADD COLUMN created_by INT NULL;
ALTER TABLE phieu_xuat ADD COLUMN created_by INT NULL;

-- 2. (Tùy) gán giá trị mặc định cho các record hiện có (vd: user 'admin')
-- Lấy id admin (nếu có)
SELECT id FROM users WHERE username = 'admin' LIMIT 1;
-- Giả sử id admin = 1, bạn chạy:
UPDATE phieu_nhap SET created_by = 12 WHERE created_by IS NULL;
UPDATE phieu_xuat SET created_by = 12 WHERE created_by IS NULL;

-- 3. (Tùy) thêm foreign key để đảm bảo ràng buộc
ALTER TABLE phieu_nhap
  ADD CONSTRAINT fk_phieu_nhap_user FOREIGN KEY (created_by) REFERENCES users(id);

ALTER TABLE phieu_xuat
  ADD CONSTRAINT fk_phieu_xuat_user FOREIGN KEY (created_by) REFERENCES users(id);
  

SHOW TABLES LIKE 'stock_changes';
SELECT * FROM stock_changes;

SHOW TABLES LIKE 'stock_changes';
SELECT COUNT(*) FROM stock_changes;
SELECT * FROM stock_changes ORDER BY created_at DESC LIMIT 10;

SELECT id, created_by FROM phieu_nhap ORDER BY id DESC LIMIT 10;
SELECT id, created_by FROM phieu_xuat ORDER BY id DESC LIMIT 10;

SELECT * FROM chi_tiet_nhap WHERE id_san_pham = 1 LIMIT 10;
SELECT * FROM chi_tiet_phieu_xuat WHERE id_san_pham = 1 LIMIT 10;

UPDATE phieu_nhap SET created_by = 12 WHERE created_by IS NULL;
UPDATE phieu_xuat SET created_by = 12 WHERE created_by IS NULL;

INSERT INTO stock_changes (san_pham_id, change_qty, change_type, ref_table, ref_id, user_id, note)
VALUES (1, 5, 'nhap', 'manual', 0, 1, 'test insert');
SELECT * FROM stock_changes ORDER BY id DESC LIMIT 5;