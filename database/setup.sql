-- ============================================================
-- Setup Database untuk Proyek Zuzu (CRUD Products)
-- Jalankan script ini di phpMyAdmin atau MySQL CLI
-- ============================================================

-- 1. Buat database baru
CREATE DATABASE IF NOT EXISTS zuzu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Gunakan database tersebut
USE zuzu_db;

-- 3. Buat tabel products
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)   NOT NULL,
    description TEXT,
    price       DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    stock       INT            NOT NULL DEFAULT 0,
    category    VARCHAR(100)   NOT NULL DEFAULT 'Umum',
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Masukkan data contoh (seeding)
INSERT INTO products (name, description, price, stock, category) VALUES
('Laptop Asus VivoBook 14', 'Laptop ringan dengan prosesor Intel Core i5, RAM 8GB, SSD 512GB', 8500000, 15, 'Elektronik'),
('Mechanical Keyboard Keychron K2', 'Keyboard wireless dengan switch Brown, layout 75%', 1250000, 30, 'Aksesori'),
('Mouse Logitech MX Master 3', 'Mouse ergonomis dengan 7 tombol dan scroll horizontal', 950000, 25, 'Aksesori'),
('Monitor LG 24 inch IPS', 'Monitor Full HD 1080p IPS dengan refresh rate 75Hz', 2300000, 10, 'Elektronik'),
('Headset Sony WH-1000XM4', 'Headset noise cancelling dengan baterai 30 jam', 4200000, 8, 'Elektronik'),
('USB Hub 7 Port', 'USB 3.0 hub dengan port USB-A dan USB-C', 180000, 50, 'Aksesori'),
('Mousepad XL Gaming', 'Mousepad besar 80x40cm dengan permukaan kain premium', 120000, 100, 'Aksesori'),
('Webcam Logitech C920', 'Webcam 1080p untuk video conference', 850000, 20, 'Elektronik'),
('SSD External Samsung T7', 'SSD portabel 1TB dengan kecepatan baca 1050MB/s', 1450000, 12, 'Penyimpanan'),
('Flash Drive Sandisk Ultra', 'USB Flash Drive 64GB USB 3.0', 95000, 200, 'Penyimpanan');
