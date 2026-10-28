<?php
declare(strict_types=1);

/*
 * การตั้งค่าฐานข้อมูลสำหรับ XAMPP
 * ค่าเริ่มต้นของ XAMPP มักใช้ผู้ใช้ root และไม่มีรหัสผ่าน
 * หากตั้งรหัสผ่าน MySQL ไว้ ให้แก้ DB_PASS ด้านล่าง
 */
const DB_HOST = 'localhost';
const DB_NAME = 'member_system';
const DB_USER = 'root';
const DB_PASS = '';

function getPDO(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}
