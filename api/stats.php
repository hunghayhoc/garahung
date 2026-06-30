<?php
// api/stats.php – Chỉ admin
session_start();
require_once '../config/db.php';

if (($_SESSION['role']??'')!=='admin') {
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

header('Content-Type: application/json');
$type = trim($_GET['type'] ?? 'overview');

switch ($type) {
    case 'overview':
        $tong_don   = $db->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
        $doanh_thu  = $db->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai='hoàn thành'")->fetchColumn();
        $dt_thang   = $db->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai='hoàn thành' AND MONTH(ngay_dat_hang)=MONTH(CURDATE()) AND YEAR(ngay_dat_hang)=YEAR(CURDATE())")->fetchColumn();
        $xe_con_hang = $db->query("SELECT COUNT(*) FROM xe_oto WHERE trang_thai='còn hàng'")->fetchColumn();
        $xe_het_hang = $db->query("SELECT COUNT(*) FROM xe_oto WHERE trang_thai='hết hàng'")->fetchColumn();
        $tong_kh    = $db->query("SELECT COUNT(*) FROM khach_hang")->fetchColumn();
        $tong_nv    = $db->query("SELECT COUNT(*) FROM nhan_vien")->fetchColumn();
        $cho_xn     = $db->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai='chờ xác nhận'")->fetchColumn();
        $lich_cho   = $db->query("SELECT COUNT(*) FROM lich_hen WHERE trang_thai='chờ xác nhận'")->fetchColumn();
        echo json_encode(['success'=>true,'data'=>compact('tong_don','doanh_thu','dt_thang','xe_con_hang','xe_het_hang','tong_kh','tong_nv','cho_xn','lich_cho')]); break;

    case 'monthly':
        $year = (int)($_GET['year'] ?? date('Y'));
        $stmt = $db->prepare("SELECT MONTH(ngay_dat_hang) AS thang, COUNT(*) AS so_don, COALESCE(SUM(tong_tien),0) AS doanh_thu FROM don_hang WHERE YEAR(ngay_dat_hang)=? AND trang_thai='hoàn thành' GROUP BY MONTH(ngay_dat_hang) ORDER BY thang");
        $stmt->execute([$year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        for ($m=1;$m<=12;$m++) {
            $found = array_filter($rows, fn($r)=>$r['thang']==$m);
            $found = array_values($found);
            $result[] = ['thang'=>$m,'so_don'=>$found[0]['so_don']??0,'doanh_thu'=>$found[0]['doanh_thu']??0];
        }
        echo json_encode(['success'=>true,'year'=>$year,'data'=>$result]); break;

    case 'xe':
        $ban_chay = $db->query("SELECT x.ten_xe, SUM(c.so_luong) AS so_ban FROM chi_tiet_don_hang c JOIN xe_oto x ON x.id=c.id_xe JOIN don_hang d ON d.id=c.id_don_hang WHERE d.trang_thai='hoàn thành' GROUP BY x.id ORDER BY so_ban DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $by_hang  = $db->query("SELECT h.ten_hang, COUNT(x.id) AS so_xe FROM hang_xe h LEFT JOIN xe_oto x ON x.id_hang=h.id GROUP BY h.id ORDER BY so_xe DESC")->fetchAll(PDO::FETCH_ASSOC);
        $by_dm    = $db->query("SELECT d.ten_danh_muc, COUNT(x.id) AS so_xe FROM danh_muc_xe d LEFT JOIN xe_oto x ON x.id_danh_muc=d.id GROUP BY d.id ORDER BY so_xe DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'ban_chay'=>$ban_chay,'by_hang'=>$by_hang,'by_dm'=>$by_dm]); break;

    default:
        echo json_encode(['success'=>false,'message'=>'Type không hợp lệ']);
}
