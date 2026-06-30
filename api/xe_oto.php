<?php

session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function xeOtoAnhTableExists(PDO $db): bool {
    static $cached = null;
    if ($cached !== null) return $cached;
    try {
        $db->query("SELECT 1 FROM xe_oto_anh LIMIT 1");
        $cached = true;
    } catch (Throwable $e) {
        $cached = false;
    }
    return $cached;
}

function sanitizeImageFilename($name): ?string {
    $name = trim((string)$name);
    if ($name === '') return null;

    if (strpos($name, '/') !== false || strpos($name, '\\') !== false) return null;
    if (preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $name)) return null;

    $name = basename($name);
    if ($name === '' || $name === '.' || $name === '..') return null;
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name)) return null;

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) return null;

    return $name;
}

function validateImageExistsOrNull(?string $filename): ?string {
    if ($filename === null) return null;
    $dir = __DIR__ . '/../assets/uploads';
    if (!is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
        return null;
    }
    return $filename;
}

function deleteUploadedFiles(array $filenames): void {
    if (empty($filenames)) return;
    $dir = __DIR__ . '/../assets/uploads';
    foreach ($filenames as $fn) {
        $fn = sanitizeImageFilename($fn);
        if ($fn === null) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $fn;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function normalizeUploadedFiles(string $field): array {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'] ?? null)) return [];

    $out = [];
    $count = count($f['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name'     => $f['name'][$i] ?? '',
            'type'     => $f['type'][$i] ?? '',
            'tmp_name' => $f['tmp_name'][$i] ?? '',
            'error'    => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $f['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function saveUploadedImages(string $field, array &$errors, int $maxFiles = 12, int $maxSizeBytes = 5242880): array {
    $files = normalizeUploadedFiles($field);
    if (empty($files)) return [];

    $uploadDir = __DIR__ . '/../assets/uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    if (!is_dir($uploadDir)) {
        $errors[] = 'Không tạo được thư mục uploads.';
        return [];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $saved = [];
    foreach ($files as $idx => $file) {
        if (count($saved) >= $maxFiles) break;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload ảnh bị lỗi (mã lỗi: ' . (int)$file['error'] . ').';
            continue;
        }
        if ((int)($file['size'] ?? 0) <= 0) {
            $errors[] = 'File ảnh rỗng.';
            continue;
        }
        if ((int)$file['size'] > $maxSizeBytes) {
            $errors[] = 'Ảnh quá lớn (tối đa ' . (int)($maxSizeBytes / 1024 / 1024) . 'MB).';
            continue;
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $errors[] = 'File upload không hợp lệ.';
            continue;
        }

        $mime = $finfo->file($tmp) ?: '';
        if (!isset($allowedMime[$mime])) {
            $errors[] = 'Định dạng ảnh không hỗ trợ.';
            continue;
        }

        try {
            $rand = bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            $rand = bin2hex(openssl_random_pseudo_bytes(16) ?: pack('H*', uniqid('', true)));
        }
        $filename = $rand . '.' . $allowedMime[$mime];
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            $errors[] = 'Không lưu được ảnh lên máy chủ.';
            continue;
        }
        $saved[] = $filename;
    }

    return $saved;
}

function isImageFilenameInUse(PDO $db, string $filename, ?int $excludeImageId = null): bool {
    if ($excludeImageId !== null && xeOtoAnhTableExists($db)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM xe_oto_anh WHERE filename=? AND id!=?");
        $stmt->execute([$filename, $excludeImageId]);
        if ((int)$stmt->fetchColumn() > 0) return true;
    } elseif (xeOtoAnhTableExists($db)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM xe_oto_anh WHERE filename=?");
        $stmt->execute([$filename]);
        if ((int)$stmt->fetchColumn() > 0) return true;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM xe_oto WHERE hinh_anh_chinh=?");
    $stmt->execute([$filename]);
    return (int)$stmt->fetchColumn() > 0;
}

$admin_only = ['add', 'update', 'delete'];
if (in_array($action, $admin_only, true) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php?error=Bạn không có quyền thực hiện thao tác này.");
    exit;
}

switch ($action) {

    case 'add':
        if ($method !== 'POST') { header("Location: ../admin/xe_oto.php?error=Phương thức không hợp lệ."); exit; }
        $ten_xe       = trim($_POST['ten_xe']        ?? '');
        $ma_xe        = trim($_POST['ma_xe']         ?? '');
        $id_hang      = (int)($_POST['id_hang']      ?? 0);
        $id_danh_muc  = (int)($_POST['id_danh_muc']  ?? 0);
        $gia_ban      = floatval($_POST['gia_ban']    ?? 0);
        $gia_km       = trim($_POST['gia_khuyen_mai'] ?? '') !== '' ? floatval($_POST['gia_khuyen_mai']) : null;
        $nam_sx       = (int)($_POST['nam_san_xuat']  ?? date('Y'));
        $nhien_lieu   = trim($_POST['nhien_lieu']     ?? 'Xăng');
        $so_cho       = (int)($_POST['so_cho_ngoi']   ?? 5);
        $hop_so       = trim($_POST['hop_so']         ?? 'Số tự động');
        $trang_thai   = trim($_POST['trang_thai']     ?? 'còn hàng');
        $mo_ta        = trim($_POST['mo_ta']          ?? '') ?: null;

        $errors = [];
        $multi = xeOtoAnhTableExists($db);
        $hinh_anh_input = validateImageExistsOrNull(sanitizeImageFilename($_POST['hinh_anh_chinh'] ?? ''));

        $hinh_anh_main_file = null;
        $main_uploads = saveUploadedImages('hinh_anh_chinh_file', $errors, 1);
        if (!empty($main_uploads)) {
            $hinh_anh_main_file = $main_uploads[0];
        }

        if ($ten_xe === '') $errors[] = 'Tên xe không được để trống.';
        if ($ma_xe  === '') $errors[] = 'Mã xe không được để trống.';
        if ($gia_ban <= 0)  $errors[] = 'Giá bán phải lớn hơn 0.';
        if ($id_hang <= 0)  $errors[] = 'Vui lòng chọn hãng xe.';
        if (!in_array($nhien_lieu, ['Xăng','Dầu','Điện','Hybrid'], true)) $errors[] = 'Nhiên liệu không hợp lệ.';
        if (!in_array($trang_thai, ['còn hàng','hết hàng','đang bảo dưỡng'], true)) $trang_thai = 'còn hàng';
        if (trim((string)($_POST['hinh_anh_chinh'] ?? '')) !== '' && $hinh_anh_input === null && empty($main_uploads)) {
            $errors[] = 'Ảnh chính chọn từ danh sách không hợp lệ hoặc không tồn tại trong thư mục assets/uploads/.';
        }

        $ck = $db->prepare("SELECT id FROM xe_oto WHERE ma_xe=? LIMIT 1"); $ck->execute([$ma_xe]);
        if ($ck->fetch()) $errors[] = 'Mã xe đã tồn tại.';

        if (!$multi) {
            $uploads = normalizeUploadedFiles('hinh_anh_files');
            $cnt = 0;
            foreach ($uploads as $u) if (($u['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $cnt++;
            if ($cnt > 1) $errors[] = 'Chưa có bảng xe_oto_anh nên chỉ lưu được 1 ảnh. Hãy chạy sql/xe_oto_anh.sql.';
        }

        if ($errors) { 
            if (isset($main_uploads)) deleteUploadedFiles($main_uploads);
            header("Location: ../admin/xe_oto.php?error=".urlencode(implode(' | ', $errors))); exit; 
        }

        $uploaded = saveUploadedImages('hinh_anh_files', $errors);
        if ($errors) {
            if (isset($main_uploads)) deleteUploadedFiles($main_uploads);
            deleteUploadedFiles($uploaded);
            header("Location: ../admin/xe_oto.php?error=".urlencode(implode(' | ', $errors))); exit;
        }

        $hinh_anh = $hinh_anh_main_file ?? $hinh_anh_input;
        if ($hinh_anh === null && !empty($uploaded)) $hinh_anh = $uploaded[0];

        try {
            $db->beginTransaction();

            $db->prepare("INSERT INTO xe_oto (ten_xe,ma_xe,id_hang,id_danh_muc,gia_ban,gia_khuyen_mai,nam_san_xuat,nhien_lieu,so_cho_ngoi,hop_so,trang_thai,mo_ta,hinh_anh_chinh) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$ten_xe,$ma_xe,$id_hang,$id_danh_muc,$gia_ban,$gia_km,$nam_sx,$nhien_lieu,$so_cho,$hop_so,$trang_thai,$mo_ta,$hinh_anh]);

            $id_xe = (int)$db->lastInsertId();
            if ($id_xe > 0 && $multi) {
                $all = [];
                foreach ($uploaded as $fn) $all[$fn] = true;
                if ($hinh_anh) $all[$hinh_anh] = true;
                $all = array_keys($all);

                $order = 0;
                $ins = $db->prepare("INSERT IGNORE INTO xe_oto_anh (id_xe, filename, is_chinh, thu_tu) VALUES (?,?,?,?)");
                foreach ($all as $fn) {
                    $ins->execute([$id_xe, $fn, 0, $order++]);
                }
                if ($hinh_anh) {
                    $db->prepare("UPDATE xe_oto_anh SET is_chinh=0 WHERE id_xe=?")->execute([$id_xe]);
                    $db->prepare("UPDATE xe_oto_anh SET is_chinh=1 WHERE id_xe=? AND filename=?")->execute([$id_xe, $hinh_anh]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            deleteUploadedFiles($uploaded);
            header("Location: ../admin/xe_oto.php?error=".urlencode("Lỗi lưu dữ liệu: ".$e->getMessage())); exit;
        }

        header("Location: ../admin/xe_oto.php?msg=Thêm xe thành công!"); exit;

    case 'update':
        if ($method !== 'POST') { header("Location: ../admin/xe_oto.php?error=Phương thức không hợp lệ."); exit; }
        $id          = (int)($_POST['id']            ?? 0);
        $ten_xe      = trim($_POST['ten_xe']         ?? '');
        $ma_xe       = trim($_POST['ma_xe']          ?? '');
        $id_hang     = (int)($_POST['id_hang']       ?? 0);
        $id_danh_muc = (int)($_POST['id_danh_muc']  ?? 0);
        $gia_ban     = floatval($_POST['gia_ban']    ?? 0);
        $gia_km      = trim($_POST['gia_khuyen_mai'] ?? '') !== '' ? floatval($_POST['gia_khuyen_mai']) : null;
        $nam_sx      = (int)($_POST['nam_san_xuat']  ?? date('Y'));
        $nhien_lieu  = trim($_POST['nhien_lieu']     ?? 'Xăng');
        $so_cho      = (int)($_POST['so_cho_ngoi']   ?? 5);
        $hop_so      = trim($_POST['hop_so']         ?? 'Số tự động');
        $trang_thai  = trim($_POST['trang_thai']     ?? 'còn hàng');
        $mo_ta       = trim($_POST['mo_ta']          ?? '') ?: null;

        $errors = [];
        $multi = xeOtoAnhTableExists($db);
        $hinh_anh_input = validateImageExistsOrNull(sanitizeImageFilename($_POST['hinh_anh_chinh'] ?? ''));

        // Handle main image file upload (new: from any folder, copy to uploads)
        $hinh_anh_main_file = null;
        $main_uploads = saveUploadedImages('hinh_anh_chinh_file', $errors, 1);
        if (!empty($main_uploads)) {
            $hinh_anh_main_file = $main_uploads[0];
        }

        if ($id <= 0) $errors[] = 'ID không hợp lệ.';
        if ($ten_xe === '') $errors[] = 'Tên xe không được để trống.';
        if ($gia_ban <= 0) $errors[] = 'Giá bán phải lớn hơn 0.';

        $ck = $db->prepare("SELECT id FROM xe_oto WHERE ma_xe=? AND id!=? LIMIT 1"); $ck->execute([$ma_xe, $id]);
        if ($ck->fetch()) $errors[] = 'Mã xe đã tồn tại.';

        if (!$multi) {
            $uploads = normalizeUploadedFiles('hinh_anh_files');
            $cnt = 0;
            foreach ($uploads as $u) if (($u['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $cnt++;
            if ($cnt > 1) $errors[] = 'Chưa có bảng xe_oto_anh nên chỉ lưu được 1 ảnh. Hãy chạy sql/xe_oto_anh.sql.';
        }

        if ($errors) { 
            if (isset($main_uploads)) deleteUploadedFiles($main_uploads);
            header("Location: ../admin/xe_oto.php?error=".urlencode(implode(' | ', $errors))); exit; 
        }

        $uploaded = saveUploadedImages('hinh_anh_files', $errors);
        if ($errors) {
            if (isset($main_uploads)) deleteUploadedFiles($main_uploads);
            deleteUploadedFiles($uploaded);
            header("Location: ../admin/xe_oto.php?error=".urlencode(implode(' | ', $errors))); exit;
        }

        $hinh_anh = $hinh_anh_input;
        $hinh_anh = $hinh_anh_main_file ?? $hinh_anh_input;
        // Nếu không chọn ảnh chính trong form thì giữ nguyên ảnh chính hiện tại
        if ($hinh_anh === null && trim((string)($_POST['hinh_anh_chinh'] ?? '')) === '') {
            $cur = $db->prepare("SELECT hinh_anh_chinh FROM xe_oto WHERE id=? LIMIT 1");
            $cur->execute([$id]);
            $hinh_anh = $cur->fetchColumn();
            $hinh_anh = $hinh_anh !== false ? (string)$hinh_anh : null;
        }
        if ($hinh_anh === null && !empty($uploaded)) {
            $hinh_anh = $uploaded[0];
        }

        try {
            $db->beginTransaction();

            $db->prepare("UPDATE xe_oto SET ten_xe=?,ma_xe=?,id_hang=?,id_danh_muc=?,gia_ban=?,gia_khuyen_mai=?,nam_san_xuat=?,nhien_lieu=?,so_cho_ngoi=?,hop_so=?,trang_thai=?,mo_ta=?,hinh_anh_chinh=? WHERE id=?")
               ->execute([$ten_xe,$ma_xe,$id_hang,$id_danh_muc,$gia_ban,$gia_km,$nam_sx,$nhien_lieu,$so_cho,$hop_so,$trang_thai,$mo_ta,$hinh_anh,$id]);

            if ($multi) {
                $orderStmt = $db->prepare("SELECT COALESCE(MAX(thu_tu),0) FROM xe_oto_anh WHERE id_xe=?");
                $orderStmt->execute([$id]);
                $order = (int)$orderStmt->fetchColumn();

                $ins = $db->prepare("INSERT IGNORE INTO xe_oto_anh (id_xe, filename, is_chinh, thu_tu) VALUES (?,?,0,?)");
                foreach ($uploaded as $fn) {
                    $ins->execute([$id, $fn, ++$order]);
                }

                if ($hinh_anh) {
                    $db->prepare("UPDATE xe_oto_anh SET is_chinh=0 WHERE id_xe=?")->execute([$id]);
                    $db->prepare("INSERT IGNORE INTO xe_oto_anh (id_xe, filename, is_chinh, thu_tu) VALUES (?,?,1,0)")->execute([$id, $hinh_anh]);
                    $db->prepare("UPDATE xe_oto_anh SET is_chinh=1 WHERE id_xe=? AND filename=?")->execute([$id, $hinh_anh]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            deleteUploadedFiles($uploaded);
            header("Location: ../admin/xe_oto.php?error=".urlencode("Lỗi lưu dữ liệu: ".$e->getMessage())); exit;
        }

        header("Location: ../admin/xe_oto.php?msg=Cập nhật xe thành công!"); exit;

    case 'delete_image':
        if ($method !== 'POST') { header("Location: ../admin/xe_oto.php?error=Phương thức không hợp lệ."); exit; }
        if (($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../pages/login.php?error=Bạn không có quyền."); exit; }
        if (!xeOtoAnhTableExists($db)) { header("Location: ../admin/xe_oto.php?error=Chưa có bảng xe_oto_anh."); exit; }

        $id_xe  = (int)($_POST['id_xe'] ?? 0);
        $id_anh = (int)($_POST['id_anh'] ?? 0);
        if ($id_xe <= 0 || $id_anh <= 0) { header("Location: ../admin/xe_oto.php?error=Dữ liệu không hợp lệ."); exit; }

        $stmt = $db->prepare("SELECT id, filename, is_chinh FROM xe_oto_anh WHERE id=? AND id_xe=? LIMIT 1");
        $stmt->execute([$id_anh, $id_xe]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { header("Location: ../admin/xe_oto.php?edit={$id_xe}&error=Ảnh không tồn tại."); exit; }

        $filename = (string)$row['filename'];
        $wasMain = !empty($row['is_chinh']);

        try {
            $db->beginTransaction();

            $db->prepare("DELETE FROM xe_oto_anh WHERE id=? AND id_xe=?")->execute([$id_anh, $id_xe]);

            if ($wasMain) {
                $next = $db->prepare("SELECT filename FROM xe_oto_anh WHERE id_xe=? ORDER BY thu_tu ASC, id ASC LIMIT 1");
                $next->execute([$id_xe]);
                $newMain = $next->fetchColumn();
                $newMain = $newMain !== false ? (string)$newMain : null;

                $db->prepare("UPDATE xe_oto SET hinh_anh_chinh=? WHERE id=?")->execute([$newMain, $id_xe]);
                $db->prepare("UPDATE xe_oto_anh SET is_chinh=0 WHERE id_xe=?")->execute([$id_xe]);
                if ($newMain) {
                    $db->prepare("UPDATE xe_oto_anh SET is_chinh=1 WHERE id_xe=? AND filename=?")->execute([$id_xe, $newMain]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            header("Location: ../admin/xe_oto.php?edit={$id_xe}&error=".urlencode("Lỗi xóa ảnh: ".$e->getMessage())); exit;
        }

        // Xóa file nếu không còn được dùng ở đâu
        if (!isImageFilenameInUse($db, $filename, null)) {
            $path = __DIR__ . '/../assets/uploads/' . $filename;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        header("Location: ../admin/xe_oto.php?edit={$id_xe}&msg=Xóa ảnh thành công!");
        exit;

    case 'delete':
        if ($method !== 'POST') { header("Location: ../admin/xe_oto.php?error=Phương thức không hợp lệ."); exit; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { header("Location: ../admin/xe_oto.php?error=ID không hợp lệ."); exit; }

        $multi = xeOtoAnhTableExists($db);
        $filenames = [];
        if ($multi) {
            $imgs = $db->prepare("SELECT id, filename FROM xe_oto_anh WHERE id_xe=?");
            $imgs->execute([$id]);
            foreach ($imgs->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $fn = (string)($r['filename'] ?? '');
                if ($fn !== '') $filenames[$fn] = true;
            }
        } else {
            $cur = $db->prepare("SELECT hinh_anh_chinh FROM xe_oto WHERE id=? LIMIT 1");
            $cur->execute([$id]);
            $fn = $cur->fetchColumn();
            if ($fn !== false && (string)$fn !== '') $filenames[(string)$fn] = true;
        }

        try {
            $db->beginTransaction();
            if ($multi) {
                $db->prepare("DELETE FROM xe_oto_anh WHERE id_xe=?")->execute([$id]);
            }
            $db->prepare("DELETE FROM xe_oto WHERE id=?")->execute([$id]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            header("Location: ../admin/xe_oto.php?error=".urlencode("Lỗi xóa xe: ".$e->getMessage())); exit;
        }

        foreach (array_keys($filenames) as $fn) {
            if (!isImageFilenameInUse($db, $fn, null)) {
                $path = __DIR__ . '/../assets/uploads/' . $fn;
                if (is_file($path)) @unlink($path);
            }
        }

        header("Location: ../admin/xe_oto.php?msg=Xóa xe thành công!"); exit;

    case 'search':
        header('Content-Type: application/json');
        $q          = trim($_GET['q']          ?? '');
        $id_hang    = (int)($_GET['id_hang']    ?? 0);
        $id_dm      = (int)($_GET['id_danh_muc'] ?? 0);
        $gia_min    = floatval($_GET['gia_min'] ?? 0);
        $gia_max    = floatval($_GET['gia_max'] ?? 0);
        $trang_thai = trim($_GET['trang_thai']  ?? '');
        $page       = max(1,(int)($_GET['page'] ?? 1));
        $limit      = 12;
        $offset     = ($page-1)*$limit;
        $where = []; $params = [];
        if ($q !== '') { $where[] = "(x.ten_xe LIKE ? OR x.ma_xe LIKE ? OR h.ten_hang LIKE ?)"; $params[]= "%$q%"; $params[]= "%$q%"; $params[]= "%$q%"; }
        if ($id_hang > 0) { $where[] = "x.id_hang=?"; $params[] = $id_hang; }
        if ($id_dm   > 0) { $where[] = "x.id_danh_muc=?"; $params[] = $id_dm; }
        if ($gia_min > 0) { $where[] = "COALESCE(x.gia_khuyen_mai,x.gia_ban)>=?"; $params[] = $gia_min; }
        if ($gia_max > 0) { $where[] = "COALESCE(x.gia_khuyen_mai,x.gia_ban)<=?"; $params[] = $gia_max; }
        if ($trang_thai !== '') { $where[] = "x.trang_thai=?"; $params[] = $trang_thai; }
        $w = $where ? 'WHERE '.implode(' AND ',$where) : '';
        $total = $db->prepare("SELECT COUNT(*) FROM xe_oto x LEFT JOIN hang_xe h ON x.id_hang=h.id $w");
        $total->execute($params); $total = (int)$total->fetchColumn();
        $stmt = $db->prepare("SELECT x.*,h.ten_hang,d.ten_danh_muc FROM xe_oto x LEFT JOIN hang_xe h ON x.id_hang=h.id LEFT JOIN danh_muc_xe d ON x.id_danh_muc=d.id $w ORDER BY x.ngay_tao DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'pages'=>ceil($total/$limit),'page'=>$page]); exit;

    default:
        header("Location: ../admin/xe_oto.php?error=Action không hợp lệ."); exit;
}
