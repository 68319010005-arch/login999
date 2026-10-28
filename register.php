<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$fullname = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim((string)($_POST['fullname'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่';
    }

    if ($fullname === '' || mb_strlen($fullname) < 2 || mb_strlen($fullname) > 150) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล 2–150 ตัวอักษร';
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $username)) {
        $errors[] = 'ชื่อผู้ใช้ต้องมี 4–50 ตัว และใช้ได้เฉพาะ a-z, A-Z, 0-9, จุด, ขีดกลาง หรือขีดล่าง';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    }

    if (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if (!$errors) {
        try {
            $pdo = getPDO();

            // ตรวจสอบชื่อผู้ใช้หรืออีเมลซ้ำก่อนบันทึก
            $check = $pdo->prepare(
                'SELECT username, email FROM members WHERE username = :username OR email = :email LIMIT 1'
            );
            $check->execute(['username' => $username, 'email' => $email]);
            $existing = $check->fetch();

            if ($existing) {
                if (strcasecmp((string)$existing['username'], $username) === 0) {
                    $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่ออื่น';
                }
                if (strcasecmp((string)$existing['email'], $email) === 0) {
                    $errors[] = 'อีเมลนี้ถูกสมัครสมาชิกแล้ว';
                }
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare(
                    'INSERT INTO members (fullname, username, email, password) 
                     VALUES (:fullname, :username, :email, :password)'
                );
                $insert->execute([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email' => $email,
                    'password' => $passwordHash,
                ]);

                $success = true;
                $fullname = $username = $email = '';
                // เปลี่ยน CSRF token หลังส่งฟอร์มสำเร็จ
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        } catch (PDOException $e) {
            // แสดงข้อความที่ผู้ใช้เข้าใจ โดยไม่เปิดเผยรายละเอียดฐานข้อมูล
            error_log('Registration database error: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
            } else {
                $errors[] = 'ไม่สามารถบันทึกข้อมูลได้ กรุณาตรวจสอบการตั้งค่าฐานข้อมูลและลองอีกครั้ง';
            }
        }
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ระบบสมัครสมาชิกตัวอย่างด้วย PHP, CSS และ MySQL">
    <title>สมัครสมาชิก | Member System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="page-shell">
        <section class="brand-panel">
            <div class="brand-mark" aria-hidden="true">M</div>
            <p class="eyebrow">MEMBER SYSTEM</p>
            <h1>เริ่มต้นใช้งาน<br>ไปกับเรา</h1>
            <p class="brand-copy">สมัครสมาชิกเพื่อสร้างบัญชีของคุณ ข้อมูลจะถูกบันทึกลงฐานข้อมูลอย่างปลอดภัย</p>
            <div class="feature-list">
                <div><span class="feature-icon">✓</span><span>ตรวจสอบข้อมูลก่อนบันทึก</span></div>
                <div><span class="feature-icon">✓</span><span>ป้องกันชื่อผู้ใช้และอีเมลซ้ำ</span></div>
                <div><span class="feature-icon">✓</span><span>เข้ารหัสรหัสผ่านก่อนจัดเก็บ</span></div>
            </div>
            <div class="decor decor-one"></div>
            <div class="decor decor-two"></div>
        </section>

        <section class="form-panel">
            <div class="form-heading">
                <span class="form-label">สร้างบัญชีใหม่</span>
                <h2>สมัครสมาชิก</h2>
                <p>กรอกข้อมูลด้านล่างให้ครบถ้วน</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success" role="status">
                    <strong>สมัครสมาชิกสำเร็จ!</strong>
                    <span>ข้อมูลของคุณถูกบันทึกลงฐานข้อมูลแล้ว</span>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <strong>ไม่สามารถสมัครสมาชิกได้</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <label for="fullname">ชื่อ-นามสกุล</label>
                <div class="input-wrap">
                    <span class="input-icon" aria-hidden="true">♙</span>
                    <input id="fullname" name="fullname" type="text" maxlength="150"
                           placeholder="เช่น สมชาย ใจดี" value="<?= e($fullname) ?>"
                           autocomplete="name" required>
                </div>

                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-wrap">
                    <span class="input-icon" aria-hidden="true">＠</span>
                    <input id="username" name="username" type="text" minlength="4" maxlength="50"
                           pattern="[a-zA-Z0-9_.-]{4,50}" placeholder="ตั้งชื่อผู้ใช้ 4 ตัวขึ้นไป"
                           value="<?= e($username) ?>" autocomplete="username" required>
                </div>
                <small>ใช้ภาษาอังกฤษ ตัวเลข จุด ขีดกลาง หรือขีดล่าง</small>

                <label for="email">อีเมล</label>
                <div class="input-wrap">
                    <span class="input-icon" aria-hidden="true">✉</span>
                    <input id="email" name="email" type="email" maxlength="190"
                           placeholder="you@example.com" value="<?= e($email) ?>"
                           autocomplete="email" required>
                </div>

                <label for="password">รหัสผ่าน</label>
                <div class="input-wrap">
                    <span class="input-icon" aria-hidden="true">♙</span>
                    <input id="password" name="password" type="password" minlength="8"
                           placeholder="อย่างน้อย 8 ตัวอักษร" autocomplete="new-password" required>
                    <button class="toggle-password" type="button" data-target="password" aria-label="แสดงหรือซ่อนรหัสผ่าน">แสดง</button>
                </div>

                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <div class="input-wrap">
                    <span class="input-icon" aria-hidden="true">✓</span>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="8"
                           placeholder="กรอกรหัสผ่านอีกครั้ง" autocomplete="new-password" required>
                    <button class="toggle-password" type="button" data-target="confirm_password" aria-label="แสดงหรือซ่อนรหัสผ่าน">แสดง</button>
                </div>
                <p id="password-message" class="password-message" aria-live="polite"></p>

                <button class="submit-button" type="submit">
                    <span>สมัครสมาชิก</span><span aria-hidden="true">→</span>
                </button>
            </form>

            <p class="privacy-note"><span aria-hidden="true">🔒</span> ข้อมูลรหัสผ่านจะถูกเข้ารหัสก่อนบันทึก</p>
            <p class="footer-note">ตัวอย่างเพื่อการศึกษา · PHP + CSS + MySQL</p>
        </section>
    </main>
    <script>
    (() => {
        document.querySelectorAll('.toggle-password').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.textContent = showing ? 'แสดง' : 'ซ่อน';
            });
        });

        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const message = document.getElementById('password-message');

        function checkPasswords() {
            if (!confirmPassword.value) {
                message.textContent = '';
                message.className = 'password-message';
                confirmPassword.setCustomValidity('');
                return;
            }
            const matches = password.value === confirmPassword.value;
            message.textContent = matches ? '✓ รหัสผ่านตรงกัน' : 'รหัสผ่านไม่ตรงกัน';
            message.className = 'password-message ' + (matches ? 'is-valid' : 'is-invalid');
            confirmPassword.setCustomValidity(matches ? '' : 'รหัสผ่านไม่ตรงกัน');
        }

        password.addEventListener('input', checkPasswords);
        confirmPassword.addEventListener('input', checkPasswords);
    })();
    </script>
</body>
</html>
