-- สร้างฐานข้อมูลและตารางสำหรับระบบสมัครสมาชิก
CREATE DATABASE IF NOT EXISTS member_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE member_system;

CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fullname VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_members_username (username),
    UNIQUE KEY uq_members_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
