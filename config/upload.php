<?php
function saveUploadedImage(string $field, string $folder): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
    }
    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('ไฟล์ต้องมีขนาดไม่เกิน 5 MB');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG หรือ WEBP');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $absoluteDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
    }
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absoluteDir . '/' . $filename)) {
        throw new RuntimeException('ไม่สามารถบันทึกไฟล์ได้');
    }
    return '/web_app/uploads/' . $folder . '/' . $filename;
}
