CREATE DATABASE IF NOT EXISTS hotel_booking_system;
USE hotel_booking_system;

-- USER TABLE
CREATE TABLE user (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    role VARCHAR(50) NOT NULL,
    profile_image VARCHAR(255),
    account_status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ROOM TYPES
CREATE TABLE room_types (
    room_types_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- ROOMS
# CREATE TABLE rooms (
#     room_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
#     room_number VARCHAR(20) NOT NULL UNIQUE,
#     room_type_id INT UNSIGNED NOT NULL,
#     price_per_night DECIMAL(10,2) NOT NULL,
#     capacity INT UNSIGNED NOT NULL,
#     room_picture VARCHAR(255),
#     equipments TEXT,
#     room_status VARCHAR(50) DEFAULT 'available',
#     room_description TEXT,
#     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
#     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
# 
#     FOREIGN KEY (room_type_id) REFERENCES room_types(room_types_id)
);

-- BOOKINGS
CREATE TABLE bookings (
    booking_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    total_payment DECIMAL(10,2) NOT NULL,
    booking_status VARCHAR(50) DEFAULT 'pending',
    payment_status VARCHAR(50) DEFAULT 'unpaid',
    booking_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);

-- PAYMENTS
CREATE TABLE payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    payment_date DATETIME NOT NULL,
    transaction_id VARCHAR(255),
    payment_status VARCHAR(50) DEFAULT 'completed',

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- INVOICES
CREATE TABLE invoices (
    invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);
INSERT INTO user (user_name, email, password, role)
VALUES ('Admin', 'admin@hotel.com', SHA2('admin123', 256), 'admin');

USE hotel_booking_system;
SELECT * FROM user;

USE hotel_booking_system;
SELECT * FROM user;
# TRUNCATE table user;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE bookings;
TRUNCATE TABLE user;

SET FOREIGN_KEY_CHECKS = 1;

USE hotel_booking_system;

INSERT INTO user (
    user_name,
    email,
    password,
    phone_number,
    role,
    profile_image,
    account_status
) VALUES (
    'admin',
    'admin@gmail.com',
    '$2a$12$NNtFO.g45S.YLtDO6QYoOuELCe1bTktrnN8bZSRCVTnzbslgJeE1u',
    '012345678',
    'admin',
    'default.png',
    'active'
);

USE hotel_booking_system;
ALTER TABLE rooms
ADD COLUMN room_picture VARCHAR(255) ;

INSERT INTO room_types (type_name, description) VALUES
('បន្ទប់ស្តង់ដារ', 'បន្ទប់មានផាសុកភាពជាមួយសេវាកម្មមូលដ្ឋាន សមស្របសម្រាប់អ្នកធ្វើដំណើរតែម្នាក់ឯង ឬគូស្នេហ៍។'),

('បន្ទប់ដេលុច', 'បន្ទប់ធំទូលាយ មានគ្រឿងសង្ហារឹមទំនើប ទិដ្ឋភាពទីក្រុង និងសេវាកម្មប្រណិត។'),

('បន្ទប់ស៊ុបភើរៀ', 'បន្ទប់ប្រណិតផ្តល់ភាពងាយស្រួលបន្ថែម រចនាប័ទ្មទំនើប និងសេវាកម្មល្អប្រសើរ។'),

('បន្ទប់គ្រួសារ', 'បន្ទប់ធំសម្រាប់គ្រួសារ មានគ្រែច្រើន និងកន្លែងសម្រាកធំទូលាយ។'),

('បន្ទប់ប្រធានាធិបតី', 'បន្ទប់ប្រណិតកម្រិតខ្ពស់ មានសេវាកម្មពិសេស កន្លែងអង្គុយឯកជន និងទិដ្ឋភាពសណ្ឋាគារស្រស់ស្អាត។');


# USE hotel_booking_system;
# SELECT * FROM rooms;
# 
# SET FOREIGN_KEY_CHECKS = 0;
# 
# DROP TABLE IF EXISTS rooms;

CREATE TABLE rooms (
    room_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type_id INT UNSIGNED NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    room_image VARCHAR(255) DEFAULT NULL,
    equipments TEXT,
    room_status VARCHAR(50) DEFAULT 'available',
    room_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (room_type_id)
        REFERENCES room_types(room_types_id)
);

SET FOREIGN_KEY_CHECKS = 1;
USE hotel_booking_system;
-- Add guests column if it doesn't exist
ALTER TABLE bookings ADD COLUMN guests INT DEFAULT 1 AFTER total_payment;
-- Check the table structure
DESCRIBE bookings;