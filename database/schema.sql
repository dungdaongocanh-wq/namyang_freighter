CREATE DATABASE IF NOT EXISTS namyang_freight CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE namyang_freight;

CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  role ENUM('admin','cs','ops','driver','accounting','customer') NOT NULL,
  customer_id INT NULL,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE customers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_code VARCHAR(20) UNIQUE NOT NULL,
  company_name VARCHAR(200),
  email VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE shipments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  customer_code VARCHAR(20) NOT NULL,
  hawb VARCHAR(50) NOT NULL,
  mawb VARCHAR(50),
  flight_no VARCHAR(20),
  pol VARCHAR(10),
  etd DATE,
  eta DATE,
  packages INT,
  weight DECIMAL(10,2),
  remark TEXT,
  customer_note TEXT,
  status ENUM('pending_customs','cleared','waiting_pickup','in_transit','delivered','kt_reviewing','pending_approval','rejected','debt','invoiced') DEFAULT 'pending_customs',
  import_date DATE NOT NULL,
  active_date DATE NOT NULL,
  imported_by INT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP ON UPDATE NOW(),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE shipment_customs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  cd_number VARCHAR(30),
  cd_status VARCHAR(10),
  file_path VARCHAR(255) NULL,
  uploaded_by INT NULL,
  uploaded_at TIMESTAMP NULL,
  downloaded_by INT NULL,
  downloaded_at TIMESTAMP NULL,
  FOREIGN KEY (shipment_id) REFERENCES shipments(id)
);

CREATE TABLE import_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  filename VARCHAR(255),
  imported_by INT,
  import_date DATE,
  total_rows INT,
  inserted INT,
  updated_rows INT,
  skipped INT,
  warnings TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE shipment_photos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  photo_path VARCHAR(255),
  uploaded_by INT,
  uploaded_at TIMESTAMP DEFAULT NOW(),
  FOREIGN KEY (shipment_id) REFERENCES shipments(id)
);

CREATE TABLE delivery_trips (
  id INT PRIMARY KEY AUTO_INCREMENT,
  trip_code VARCHAR(30) UNIQUE,
  driver_id INT,
  ops_id INT,
  trip_date DATE,
  status ENUM('pending','in_transit','completed') DEFAULT 'pending',
  note TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE delivery_trip_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  trip_id INT NOT NULL,
  shipment_id INT NOT NULL,
  FOREIGN KEY (trip_id) REFERENCES delivery_trips(id),
  FOREIGN KEY (shipment_id) REFERENCES shipments(id)
);

CREATE TABLE delivery_signatures (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  trip_id INT NULL,
  signed_by_name VARCHAR(100),
  signature_path VARCHAR(255),
  signed_by_driver INT,
  signed_at TIMESTAMP DEFAULT NOW(),
  FOREIGN KEY (shipment_id) REFERENCES shipments(id)
);

CREATE TABLE shipment_costs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  cost_name VARCHAR(100),
  quantity DECIMAL(10,2),
  unit VARCHAR(20),
  unit_price DECIMAL(15,2),
  amount DECIMAL(15,2),
  source ENUM('auto','manual','ops','kt','quotation') DEFAULT 'auto',
  quotation_item_id INT NULL,
  created_by INT,
  created_at TIMESTAMP DEFAULT NOW(),
  FOREIGN KEY (shipment_id) REFERENCES shipments(id),
  FOREIGN KEY (quotation_item_id) REFERENCES quotation_items(id) ON DELETE SET NULL
);

CREATE TABLE quotations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NULL,
  name VARCHAR(200) NOT NULL DEFAULT 'Báo giá',
  valid_from DATE NULL,
  valid_to DATE NULL,
  is_active TINYINT DEFAULT 1,
  note TEXT NULL,
  created_at TIMESTAMP DEFAULT NOW(),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE quotation_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  quotation_id INT NOT NULL,
  description VARCHAR(255),
  currency VARCHAR(10) DEFAULT 'VND',
  unit_price DECIMAL(15,2) DEFAULT 0,
  quantity DECIMAL(10,2) DEFAULT 1,
  amount DECIMAL(15,2) DEFAULT 0,
  vat_pct INT DEFAULT 8,
  note TEXT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (quotation_id) REFERENCES quotations(id)
);

CREATE TABLE approval_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  action ENUM('approved','rejected','resubmitted'),
  customer_id INT,
  user_id INT,
  reason TEXT NULL,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE debts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  month CHAR(7),
  total_amount DECIMAL(15,2),
  status ENUM('open','invoiced','paid') DEFAULT 'open',
  invoice_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE debt_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  debt_id INT NOT NULL,
  shipment_id INT NOT NULL,
  amount DECIMAL(15,2)
);

CREATE TABLE shipment_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  shipment_id INT NOT NULL,
  from_status VARCHAR(30),
  to_status VARCHAR(30),
  triggered_by VARCHAR(50),
  user_id INT NULL,
  note TEXT NULL,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  shipment_id INT NULL,
  message TEXT,
  is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Migration (run on existing DB):
-- ALTER TABLE shipment_costs MODIFY COLUMN source ENUM('auto','manual','ops','kt','quotation') DEFAULT 'auto';
-- ALTER TABLE shipment_costs ADD COLUMN quotation_item_id INT NULL AFTER source, ADD FOREIGN KEY (quotation_item_id) REFERENCES quotation_items(id) ON DELETE SET NULL;

-- Seed admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
