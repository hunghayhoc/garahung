<?php
// pages/index.php
$page_title = 'TrungBeo – Showroom Ô Tô Hàng Đầu';
require_once '../includes/header.php';
require_once '../config/db.php';

// Xe nổi bật (còn hàng, lấy 8 xe)
$q = trim($_GET['q'] ?? '');
$sql = "SELECT x.*, h.ten_hang, d.ten_danh_muc
        FROM xe_oto x
        LEFT JOIN hang_xe h ON x.id_hang = h.id
        LEFT JOIN danh_muc_xe d ON x.id_danh_muc = d.id
        WHERE x.trang_thai = 'còn hàng'";
$params = [];
if ($q !== '') {
    $sql .= " AND (x.ten_xe LIKE ? OR h.ten_hang LIKE ? OR d.ten_danh_muc LIKE ?)";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY x.ngay_tao DESC LIMIT 8";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$xe_list = $stmt->fetchAll();

// Thống kê
$total_xe  = $db->query("SELECT COUNT(*) FROM xe_oto WHERE trang_thai='còn hàng'")->fetchColumn();
$total_kh  = $db->query("SELECT COUNT(*) FROM khach_hang")->fetchColumn();
$total_hang = $db->query("SELECT COUNT(*) FROM hang_xe")->fetchColumn();

// Hãng xe
$hang_list = $db->query("SELECT h.*, COUNT(x.id) AS so_xe FROM hang_xe h LEFT JOIN xe_oto x ON x.id_hang=h.id AND x.trang_thai='còn hàng' GROUP BY h.id ORDER BY so_xe DESC")->fetchAll();
?>

<!-- HERO -->
<section class="hero">
    <h1>🚗 Showroom TrungBeo</h1>
    <p>Hàng trăm mẫu xe chính hãng – Giá tốt nhất – Hỗ trợ tư vấn 24/7</p>
    <form class="hero-search" method="GET" action="xe.php">
        <input type="text" name="q" placeholder="Tìm kiếm xe theo tên, hãng, dòng xe..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit">🔍 Tìm kiếm</button>
    </form>
    <div style="display:flex;justify-content:center;gap:40px;margin-top:36px;flex-wrap:wrap;">
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:900;"><?= number_format($total_xe) ?>+</div>
            <div style="color:rgba(255,255,255,.7);font-size:.9rem;">Xe đang bán</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:900;"><?= number_format($total_kh) ?>+</div>
            <div style="color:rgba(255,255,255,.7);font-size:.9rem;">Khách hàng</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:900;"><?= number_format($total_hang) ?>+</div>
            <div style="color:rgba(255,255,255,.7);font-size:.9rem;">Hãng xe</div>
        </div>
    </div>
</section>

<div class="container">

    <!-- HÃNG XE -->
    <div style="margin-bottom:32px;">
        <h2 style="font-size:1.3rem;color:var(--primary);font-weight:800;margin-bottom:18px;">Các hãng xe</h2>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <?php foreach ($hang_list as $h): ?>
            <a href="xe.php?hang=<?= $h['id'] ?>" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border-radius:10px;border:2px solid var(--border);font-weight:700;color:var(--primary);font-size:.92rem;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,.05);"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                🚗 <?= htmlspecialchars($h['ten_hang']) ?>
                <span style="background:var(--bg);border-radius:20px;padding:2px 8px;font-size:.76rem;color:var(--text-light);"><?= $h['so_xe'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- XE NỔI BẬT -->
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h2 style="font-size:1.3rem;color:var(--primary);font-weight:800;">Xe nổi bật</h2>
            <a href="xe.php" class="btn btn-outline btn-sm">Xem tất cả →</a>
        </div>
        <?php if (empty($xe_list)): ?>
            <div class="alert alert-info">Không tìm thấy xe phù hợp.</div>
        <?php else: ?>
        <div class="xe-grid">
            <?php foreach ($xe_list as $xe): ?>
            <a href="xe_detail.php?id=<?= $xe['id'] ?>" style="text-decoration:none;color:inherit;">
                <div class="xe-card">
                    <div class="img-wrap">
                        <?php if ($xe['hinh_anh_chinh']): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($xe['hinh_anh_chinh']) ?>" alt="<?= htmlspecialchars($xe['ten_xe']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">🚗</div>
                        <?php endif; ?>
                        <?php if ($xe['gia_khuyen_mai']): ?>
                            <span style="position:absolute;top:10px;right:10px;background:var(--accent);color:#fff;padding:3px 9px;border-radius:20px;font-size:.76rem;font-weight:700;">SALE</span>
                        <?php endif; ?>
                    </div>
                    <div class="xe-info">
                        <div class="xe-name"><?= htmlspecialchars($xe['ten_xe']) ?></div>
                        <div class="xe-meta">
                            <span>🏭 <?= htmlspecialchars($xe['ten_hang']) ?></span>
                            <span>📂 <?= htmlspecialchars($xe['ten_danh_muc']) ?></span>
                            <span>📅 <?= $xe['nam_san_xuat'] ?></span>
                        </div>
                        <div class="xe-price">
                            <?= number_format($xe['gia_khuyen_mai'] ?? $xe['gia_ban']) ?> ₫
                            <?php if ($xe['gia_khuyen_mai']): ?>
                                <span class="original"><?= number_format($xe['gia_ban']) ?> ₫</span>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-green">Còn hàng</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- DỊCH VỤ -->
    <div style="margin-top:40px;">
        <h2 style="font-size:1.3rem;color:var(--primary);font-weight:800;margin-bottom:18px;">Tại sao chọn TrungBeo?</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;">
            <?php foreach ([
                ['🏆','Xe chính hãng 100%','Cam kết toàn bộ xe đều có nguồn gốc rõ ràng, chính hãng.'],
                ['💰','Giá tốt nhất thị trường','Cạnh tranh giá, không qua trung gian, tiết kiệm cho bạn.'],
                ['🔧','Bảo hành dài hạn','Dịch vụ hậu mãi, bảo hành, bảo dưỡng uy tín.'],
                ['📱','Tư vấn 24/7','Đội ngũ tư vấn chuyên nghiệp, sẵn sàng hỗ trợ mọi lúc.'],
            ] as [$icon,$title,$desc]): ?>
            <div style="background:#fff;border-radius:12px;padding:22px;box-shadow:var(--shadow);text-align:center;">
                <div style="font-size:2.2rem;margin-bottom:10px;"><?= $icon ?></div>
                <div style="font-weight:800;color:var(--primary);margin-bottom:6px;"><?= $title ?></div>
                <div style="font-size:.88rem;color:var(--text-light);line-height:1.6;"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
