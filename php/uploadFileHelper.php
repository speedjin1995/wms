<?php
/**
 * Upload a file and insert into the files table.
 *
 * @param array  $file    Single file array with keys: name, tmp_name, size, type, error
 * @param string $type    Subfolder/type name (e.g. 'weighing', 'logo')
 * @param string $company Company ID
 * @param mysqli $db      Database connection
 * @return array ['status' => 'success'|'failed', 'message' => string, 'fid' => int|null]
 */
function uploadFile($file, $type, $company, $db, $uploadMethod = 'file_table') {
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    $maxSize = ($type == 'logo') ? 25 * 1024 * 1024 : 10 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'failed', 'message' => 'No file uploaded or upload error', 'fid' => null];
    }
    if ($file['size'] > $maxSize) {
        return ['status' => 'failed', 'message' => 'File size exceeds ' . ($maxSize / 1024 / 1024) . 'MB limit', 'fid' => null];
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return ['status' => 'failed', 'message' => 'Only PNG, JPG, and JPEG files are allowed', 'fid' => null];
    }

    $method = 'local';
    $fileDir = 'uploads/';
    $uploadPath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $fileDir;
    if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

    $uploadDir = $uploadPath . $type . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = time() . '_' . $company . '_' . basename($file['name']);
    $filePath = $uploadDir . $filename;
    $dbPath = $fileDir . $type . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['status' => 'failed', 'message' => 'Failed to save file', 'fid' => null];
    }

    $fid = null;

    if ($uploadMethod == 'file_table') {
        if ($stmt = $db->prepare("INSERT INTO files (filename, filepath, method, company) VALUES (?, ?, ?, ?)")) {
            $stmt->bind_param('ssss', $filename, $dbPath, $method, $company);
            $stmt->execute();
            $fid = $stmt->insert_id;
            $stmt->close();
        }
    } else {
        $fid = $dbPath; // Return file path if not using file table
    }

    return ['status' => 'success', 'message' => 'File uploaded successfully!', 'fid' => $fid];
}

/**
 * Delete a file from disk and soft-delete from the files table.
 *
 * @param string $fileId  The file ID in the files table
 * @param mysqli $db      Database connection
 */
function deleteOldFile($file, $db, $uploadMethod = 'file_table') {
    if (!$file) return;

    if ($uploadMethod == 'file_table'){
        $stmt = $db->prepare("SELECT filepath FROM files WHERE id = ? AND deleted = 0");
        $stmt->bind_param('s', $file);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $oldPath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $row['filepath'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        $stmt->close();

        $stmtDel = $db->prepare("UPDATE files SET deleted = 1 WHERE id = ?");
        $stmtDel->bind_param('s', $file);
        $stmtDel->execute();
        $stmtDel->close();
    }else{
        $oldPath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/' . $file;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }
    
}
?>
