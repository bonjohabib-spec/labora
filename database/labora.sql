
CREATE DATABASE IF NOT EXISTS labora;
USE labora;


CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('owner','kasir') DEFAULT 'kasir',
  nama_lengkap VARCHAR(100)
);


INSERT INTO users (username, password, role, nama_lengkap)
VALUES ('owner1', 'password123', 'owner', 'Owner LABORA');


CREATE TABLE barang (
  id_barang INT AUTO_INCREMENT PRIMARY KEY,
  nama_barang VARCHAR(200) NOT NULL,
  warna VARCHAR(50),
  ukuran VARCHAR(50),
  harga_beli DECIMAL(12,2) DEFAULT 0,
  harga_jual DECIMAL(12,2) DEFAULT 0,
  stok INT DEFAULT 0,
  stok_min INT DEFAULT 10,
  stok_max INT DEFAULT 9999
);


CREATE TABLE penjualan (
  id_penjualan INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  pelanggan VARCHAR(100) AFTER tanggal,
  total DECIMAL(14,2) DEFAULT 0,
  keuntungan DECIMAL(14,2) DEFAULT 0,
  kasir VARCHAR(100),
  status ENUM('aktif','selesai','batal') DEFAULT 'aktif',
  FOREIGN KEY (kasir) REFERENCES users(username)
);


CREATE TABLE detail_penjualan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_penjualan INT,
  id_barang INT,
  qty INT,
  harga_jual DECIMAL(12,2),
  subtotal DECIMAL(14,2),
  pelanggan VARCHAR(100) DEFAULT NULL,
  status ENUM('aktif', 'batal', 'selesai') DEFAULT 'aktif',
  FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan),
  FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
);




CREATE TABLE pengeluaran (
  id_pengeluaran INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  kategori VARCHAR(100),
  deskripsi TEXT,
  nominal DECIMAL(14,2) DEFAULT 0,
  input_by VARCHAR(100),
  FOREIGN KEY (input_by) REFERENCES users(username)
);

CREATE TABLE riwayat_stok (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_barang INT NOT NULL,
  jumlah INT NOT NULL,
  tipe ENUM('penambahan','pengurangan') NOT NULL,
  keterangan VARCHAR(255),
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
);
