-- ============================================
-- Campus Lost & Found Portal - Database Setup
-- ============================================

CREATE DATABASE IF NOT EXISTS lost_found_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lost_found_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    status ENUM('Lost', 'Found', 'Claimed') NOT NULL DEFAULT 'Lost',
    description TEXT,
    location VARCHAR(200) NOT NULL,
    date DATE NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SAMPLE USERS (passwords are hashed via bcrypt)
-- Plain-text passwords: admin123, student123
-- ============================================
INSERT INTO users (name, email, password) VALUES
('Admin User',     'admin@campus.edu',   '$2b$12$8KzFl2O7i9RvWvQxN1DxXeqZXCdYs1V9VVBuOLKUQD5WyT2gE.hZO'),
('John Student',   'john@campus.edu',    '$2b$12$8KzFl2O7i9RvWvQxN1DxXeqZXCdYs1V9VVBuOLKUQD5WyT2gE.hZO'),
('Sarah Johnson',  'sarah@campus.edu',   '$2b$12$8KzFl2O7i9RvWvQxN1DxXeqZXCdYs1V9VVBuOLKUQD5WyT2gE.hZO');

-- ============================================
-- SAMPLE ITEMS
-- ============================================
INSERT INTO items (item_name, category, status, description, location, date, contact_number, image) VALUES
('Blue Backpack',        'Bags',         'Lost',    'Dark blue Jansport backpack with laptop compartment. Has a keychain with a red star attached.',  'Main Library, 2nd Floor', '2025-07-10', '555-0101', NULL),
('iPhone 14 Pro',        'Electronics',  'Found',   'Black iPhone 14 Pro found near the benches. Screen has a small crack on corner.',                'Student Center Cafeteria', '2025-07-12', '555-0102', NULL),
('Calculus Textbook',    'Books',        'Lost',    'Stewart Calculus 8th edition, name "Mike R." written inside front cover.',                        'Science Building Room 204', '2025-07-14', '555-0103', NULL),
('Silver Keys Bundle',   'Keys',         'Found',   '3 keys on a blue lanyard with a small Toyota keychain. Found in the parking lot.',                 'Parking Lot B',            '2025-07-15', '555-0104', NULL),
('Black Wallet',         'Accessories',  'Claimed', 'Leather bifold wallet. Returned to owner on July 18th.',                                           'Gym Locker Room',          '2025-07-11', '555-0105', NULL),
('Airpods Pro Case',     'Electronics',  'Lost',    'White Airpods Pro charging case, missing lid. Has a small dent on the back.',                      'Engineering Lab B3',       '2025-07-16', '555-0106', NULL),
('Red Umbrella',         'Accessories',  'Found',   'Compact red umbrella with black handle. Brand: Repel.',                                            'Bus Stop near Main Gate',  '2025-07-17', '555-0107', NULL),
('Student ID Card',      'Cards',        'Found',   'ID for a student named "Emily Chen" — Dept. of Biology, Class 2026.',                             'Campus Bookstore',         '2025-07-18', '555-0108', NULL),
('MacBook Charger',      'Electronics',  'Lost',    '61W USB-C Apple MagSafe charger. Has white tape label with initials "A.K."',                       'Library Study Room 3',     '2025-07-19', '555-0109', NULL),
('Purple Water Bottle',  'Accessories',  'Found',   'Hydro Flask 32oz in purple. Name "Lisa" scratched on the bottom.',                                 'Athletics Track Field',    '2025-07-20', '555-0110', NULL),
('Chemistry Lab Coat',   'Clothing',     'Lost',    'White lab coat size M with name tag slot. Has ink stain on left sleeve.',                          'Chemistry Department',     '2025-07-21', '555-0111', NULL),
('Prescription Glasses', 'Accessories',  'Found',   'Black wire-frame prescription glasses in a brown case. Lens have slight scratches.',               'Lecture Hall A',           '2025-07-22', '555-0112', NULL);
