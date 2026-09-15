-- =====================================================================
-- ระบบสารสนเทศเพื่อการบริหารจัดการการรับงานและการนัดหมาย
-- กรณีศึกษา ร้านเอกเซอร์วิส
-- Database: ekservice_db  (MySQL / MariaDB, InnoDB, utf8mb4)
-- =====================================================================

DROP DATABASE IF EXISTS ekservice_db;
CREATE DATABASE ekservice_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ekservice_db;

-- ---------------------------------------------------------------------
-- 1. Admin  (Master) : เก็บข้อมูลผู้ดูแลระบบและรหัสผ่าน
-- ---------------------------------------------------------------------
CREATE TABLE Admin (
    admin_id    INT(11) NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)  NOT NULL,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) DEFAULT NULL,
    status      VARCHAR(20)  NOT NULL DEFAULT 'Active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Owner  (Master) : เก็บข้อมูลเจ้าของกิจการและบัญชี LINE Login
-- ---------------------------------------------------------------------
CREATE TABLE Owner (
    owner_id      INT(11) NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  DEFAULT NULL,
    password      VARCHAR(255) DEFAULT NULL,
    line_user_id  VARCHAR(50)  DEFAULT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(15)  DEFAULT NULL,
    shop_name     VARCHAR(100) NOT NULL DEFAULT 'ร้านเอกเซอร์วิส',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (owner_id),
    UNIQUE KEY uq_owner_username (username),
    UNIQUE KEY uq_owner_line_id (line_user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Customer  (Master) : เก็บข้อมูลพื้นฐานลูกค้า และ LINE User ID
-- ---------------------------------------------------------------------
CREATE TABLE Customer (
    customer_id   INT(11) NOT NULL AUTO_INCREMENT,
    line_user_id  VARCHAR(50)  NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(15)  DEFAULT NULL,
    email         VARCHAR(100) DEFAULT NULL,
    password      VARCHAR(255) DEFAULT NULL,
    address       TEXT         DEFAULT NULL,
    latitude      DECIMAL(10,6) DEFAULT NULL,
    longitude     DECIMAL(10,6) DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (customer_id),
    UNIQUE KEY uq_customer_line_id (line_user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Technician  (Master) : เก็บข้อมูลพนักงานช่าง ความถนัด และเบอร์โทรศัพท์
-- ---------------------------------------------------------------------
CREATE TABLE Technician (
    tech_id       INT(11) NOT NULL AUTO_INCREMENT,
    line_user_id  VARCHAR(50)  DEFAULT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(15)  DEFAULT NULL,
    specialty     VARCHAR(100) DEFAULT NULL,
    status        VARCHAR(20)  NOT NULL DEFAULT 'ว่าง',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tech_id),
    UNIQUE KEY uq_tech_line_id (line_user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Services  (Master) : เก็บรายการประเภทงานบริการ และราคาพื้นฐาน
-- ---------------------------------------------------------------------
CREATE TABLE Services (
    service_id    INT(11) NOT NULL AUTO_INCREMENT,
    service_name  VARCHAR(100) NOT NULL,
    category      VARCHAR(50)  DEFAULT NULL,
    base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description   TEXT DEFAULT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'เปิดใช้งาน',
    PRIMARY KEY (service_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. Booking  (Transaction) : เก็บข้อมูลตารางคิวนัดหมาย สถานะงาน และช่างรับผิดชอบ
-- ---------------------------------------------------------------------
CREATE TABLE Booking (
    booking_id           INT(11) NOT NULL AUTO_INCREMENT,
    customer_id          INT(11) NOT NULL,
    service_id           INT(11) NOT NULL,
    tech_id_1             INT(11) DEFAULT NULL,
    tech_id_2             INT(11) DEFAULT NULL,
    booking_date          DATE NOT NULL,
    booking_time          TIME NOT NULL,
    status_service         VARCHAR(30) NOT NULL DEFAULT 'รอรับงาน',
    problem_description    TEXT DEFAULT NULL,
    problem_photo_url      VARCHAR(255) DEFAULT NULL,
    reschedule_reason      TEXT DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (booking_id),
    KEY idx_booking_date (booking_date, booking_time),
    CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id) REFERENCES Customer(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES Services(service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_tech1 FOREIGN KEY (tech_id_1) REFERENCES Technician(tech_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_booking_tech2 FOREIGN KEY (tech_id_2) REFERENCES Technician(tech_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. Job_Material  (Transaction) : เก็บรายการวัสดุอุปกรณ์ที่ใช้ และค่าแรงของแต่ละงาน
-- ---------------------------------------------------------------------
CREATE TABLE Job_Material (
    material_id   INT(11) NOT NULL AUTO_INCREMENT,
    booking_id    INT(11) NOT NULL,
    material_name VARCHAR(100) NOT NULL,
    quantity      INT(11) NOT NULL DEFAULT 1,
    unit_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    labor_cost    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price   DECIMAL(10,2) GENERATED ALWAYS AS
                   ((quantity * unit_price) + labor_cost) STORED,
    PRIMARY KEY (material_id),
    CONSTRAINT fk_material_booking FOREIGN KEY (booking_id) REFERENCES Booking(booking_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. Payment  (Transaction) : เก็บข้อมูลหลักฐานการโอนเงิน ภาพสลิป และสถานะอนุมัติ
-- ---------------------------------------------------------------------
CREATE TABLE Payment (
    payment_id      INT(11) NOT NULL AUTO_INCREMENT,
    booking_id      INT(11) NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    slip_photo_url  VARCHAR(255) DEFAULT NULL,
    payment_date    DATETIME DEFAULT NULL,
    verify_status   VARCHAR(20) NOT NULL DEFAULT 'รอตรวจสอบ',
    verified_by     INT(11) DEFAULT NULL,
    PRIMARY KEY (payment_id),
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES Booking(booking_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_payment_owner FOREIGN KEY (verified_by) REFERENCES Owner(owner_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. Expense  (Transaction) : เก็บข้อมูลรายจ่ายทั่วไปและค่าวัสดุของร้าน
-- ---------------------------------------------------------------------
CREATE TABLE Expense (
    expense_id    INT(11) NOT NULL AUTO_INCREMENT,
    owner_id      INT(11) NOT NULL,
    expense_type  VARCHAR(50) NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    expense_date  DATE NOT NULL,
    note          TEXT DEFAULT NULL,
    PRIMARY KEY (expense_id),
    CONSTRAINT fk_expense_owner FOREIGN KEY (owner_id) REFERENCES Owner(owner_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- ข้อมูลตัวอย่าง (Sample Data) — ใช้สำหรับทดสอบระบบเบื้องต้น
-- =====================================================================
INSERT INTO Admin (username, password, full_name, email) VALUES
('admin01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมชาย ใจดี', 'admin@ekservice.com');

INSERT INTO Owner (username, password, line_user_id, full_name, phone, shop_name) VALUES
('owner01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'U4af4980000000000000000000000000', 'เอกชัย มั่นคง', '081-234-5678', 'ร้านเอกเซอร์วิส');

INSERT INTO Customer (line_user_id, full_name, phone, address, latitude, longitude) VALUES
('U8bd23c1000000000000000000000000', 'สมหญิง รักดี', '089-876-5432', '12 ถ.นิมมานเหมินท์ ต.สุเทพ อ.เมือง จ.เชียงใหม่', 18.796143, 98.979263);

INSERT INTO Technician (line_user_id, full_name, phone, specialty, status) VALUES
('U9fe4471000000000000000000000000', 'วิชัย ช่างเก่ง', '086-111-2233', 'ไฟฟ้า, เครื่องปรับอากาศ', 'ว่าง'),
('U9fe4472000000000000000000000000', 'สมศักดิ์ ช่างมือทอง', '086-222-3344', 'ประปา, งานทั่วไป', 'ว่าง');

INSERT INTO Services (service_name, category, base_price, description) VALUES
('ซ่อมเครื่องปรับอากาศ', 'แอร์', 500.00, 'ล้างทำความสะอาด ตรวจเช็คน้ำยา และซ่อมแซมเครื่องปรับอากาศ'),
('ซ่อมระบบไฟฟ้า', 'ไฟฟ้า', 400.00, 'ตรวจเช็คและซ่อมแซมระบบไฟฟ้าภายในบ้าน'),
('ซ่อมระบบประปา', 'ประปา', 350.00, 'ตรวจเช็คและซ่อมแซมท่อประปา ก๊อกน้ำ');

INSERT INTO Booking (customer_id, service_id, tech_id_1, booking_date, booking_time, status_service, problem_description) VALUES
(1, 1, 1, '2026-10-15', '10:30:00', 'กำลังเดินทาง', 'แอร์มีน้ำหยดคอยล์เย็น');

-- =====================================================================
-- จบไฟล์
-- =====================================================================
