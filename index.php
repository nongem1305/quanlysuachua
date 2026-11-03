<?php
include 'ketnoi.php';

$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_them'])) {
    $ma_kh = trim($_POST['ma_kh']);
    $ma_tb = trim($_POST['ma_tb']);
    $ngay_nhan = trim($_POST['ngay_nhan']);
    $tinh_trang = trim($_POST['tinh_trang']);
    $chi_phi = trim($_POST['chi_phi']);
    $trang_thai = trim($_POST['trang_thai']);

    if (empty($ma_kh) || empty($ma_tb) || empty($ngay_nhan) || empty($tinh_trang) || $chi_phi === '') {
        $message = "Vui lòng nhập đầy đủ các thông tin bắt buộc!";
    } elseif ($chi_phi < 0) {
        $message = "Chi phí sửa chữa không được âm!";
    } else {
        $stmt = $conn->prepare("INSERT INTO PHIEUSUA (ma_kh, ma_tb, ngay_nhan, tinh_trang, chi_phi, trang_thai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $ma_kh, $ma_tb, $ngay_nhan, $tinh_trang, $chi_phi, $trang_thai);
        if ($stmt->execute()) {
            header("Location: index.php?msg=added");
            exit();
        } else {
            $message = "Lỗi khi thêm: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_capnhat'])) {
    $ma_phieu = trim($_POST['ma_phieu']);
    $tinh_trang = trim($_POST['tinh_trang']);
    $chi_phi = trim($_POST['chi_phi']);
    $trang_thai = trim($_POST['trang_thai']);

    if (empty($tinh_trang) || $chi_phi === '') {
        $message = "Vui lòng nhập đủ thông tin!";
    } elseif ($chi_phi < 0) {
        $message = "Chi phí không được nhỏ hơn 0!";
    } else {
        $stmt = $conn->prepare("UPDATE PHIEUSUA SET tinh_trang = ?, chi_phi = ?, trang_thai = ? WHERE ma_phieu = ?");
        $stmt->bind_param("sdsi", $tinh_trang, $chi_phi, $trang_thai, $ma_phieu);
        if ($stmt->execute()) {
            header("Location: index.php?msg=updated");
            exit();
        } else {
            $message = "Lỗi khi cập nhật: " . $conn->error;
        }
    }
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM PHIEUSUA WHERE ma_phieu = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: index.php?msg=deleted");
        exit();
    }
}

$edit_data = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM PHIEUSUA WHERE ma_phieu = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}

$khachhang_list = $conn->query("SELECT * FROM KHACHHANG");
$thietbi_list = $conn->query("SELECT * FROM THIETBI");

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT p.*, k.ten_kh, k.dien_thoai, t.ten_tb, t.hang_sx 
        FROM PHIEUSUA p 
        JOIN KHACHHANG k ON p.ma_kh = k.ma_kh 
        JOIN THIETBI t ON p.ma_tb = t.ma_tb";

if (!empty($search)) {
    $sql .= " WHERE k.ten_kh LIKE ? OR k.dien_thoai LIKE ?";
}
$sql .= " ORDER BY p.ma_phieu DESC";

$stmt_list = $conn->prepare($sql);
if (!empty($search)) {
    $param_search = "%$search%";
    $stmt_list->bind_param("ss", $param_search, $param_search);
}
$stmt_list->execute();
$result_list = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Tiếp Nhận Sửa Chữa Máy Tính</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f6f9; }
        .header { background: #0056b3; color: #fff; padding: 15px; text-align: center; border-radius: 5px; }
        .nav { margin: 15px 0; background: #e9ecef; padding: 10px; border-radius: 5px; }
        .nav a { margin-right: 15px; text-decoration: none; color: #0056b3; font-weight: bold; }
        .container { display: flex; gap: 20px; }
        .form-box { width: 35%; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .table-box { width: 65%; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 8px 15px; border: none; cursor: pointer; color: #fff; border-radius: 3px; }
        .btn-primary { background: #28a745; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-danger { background: #dc3545; }
        .btn-search { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f2f2f2; }
        .alert { padding: 10px; background: #f8d7da; color: #721c24; border-radius: 3px; margin-bottom: 10px; }
        .msg-success { padding: 10px; background: #d4edda; color: #155724; border-radius: 3px; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>QUẢN LÝ TIẾP NHẬN SỬA CHỮA MÁY TÍNH</h2>
    </div>

    <div class="nav">
        <a href="index.php">Trang chủ</a>
        <a href="index.php">Phiếu sửa chữa</a>
        <a href="#">Khách hàng</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg-success">
            <?php 
                if ($_GET['msg'] == 'added') echo "Thêm phiếu sửa chữa thành công!";
                if ($_GET['msg'] == 'updated') echo "Cập nhật phiếu thành công!";
                if ($_GET['msg'] == 'deleted') echo "Đã xóa phiếu sửa chữa!";
            ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="form-box">
            <h3><?php echo $edit_data ? "Sửa thông tin phiếu #" . $edit_data['ma_phieu'] : "Thêm phiếu sửa chữa mới"; ?></h3>
            <form method="POST" action="index.php">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="ma_phieu" value="<?php echo $edit_data['ma_phieu']; ?>">
                <?php else: ?>
                    <div class="form-group">
                        <label>Khách hàng (*):</label>
                        <select name="ma_kh" required>
                            <option value="">-- Chọn khách hàng --</option>
                            <?php while ($kh = $khachhang_list->fetch_assoc()): ?>
                                <option value="<?php echo $kh['ma_kh']; ?>"><?php echo $kh['ten_kh'] . " (" . $kh['dien_thoai'] . ")"; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thiết bị (*):</label>
                        <select name="ma_tb" required>
                            <option value="">-- Chọn thiết bị --</option>
                            <?php while ($tb = $thietbi_list->fetch_assoc()): ?>
                                <option value="<?php echo $tb['ma_tb']; ?>"><?php echo $tb['ten_tb'] . " - " . $tb['hang_sx']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày tiếp nhận (*):</label>
                        <input type="date" name="ngay_nhan" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Tình trạng thiết bị (*):</label>
                    <textarea name="tinh_trang" rows="3" required><?php echo $edit_data ? htmlspecialchars($edit_data['tinh_trang']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Chi phí dự kiến (VNĐ) (*):</label>
                    <input type="number" step="1000" name="chi_phi" min="0" value="<?php echo $edit_data ? $edit_data['chi_phi'] : '0'; ?>" required>
                </div>

                <div class="form-group">
                    <label>Trạng thái sửa chữa (*):</label>
                    <select name="trang_thai">
                        <?php 
                        $status_options = ["Đang tiếp nhận", "Đang sửa chữa", "Đã sửa xong", "Đã trả khách"];
                        $current_status = $edit_data ? $edit_data['trang_thai'] : "Đang tiếp nhận";
                        foreach ($status_options as $opt) {
                            $selected = ($current_status == $opt) ? "selected" : "";
                            echo "<option value='$opt' $selected>$opt</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if ($edit_data): ?>
                    <button type="submit" name="btn_capnhat" class="btn btn-warning">Cập nhật phiếu</button>
                    <a href="index.php" style="margin-left: 10px;">Hủy bỏ</a>
                <?php else: ?>
                    <button type="submit" name="btn_them" class="btn btn-primary">Thêm phiếu</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-box">
            <form method="GET" action="index.php" style="margin-bottom: 15px; display: flex; gap: 10px;">
                <input type="text" name="search" placeholder="Nhập tên KH hoặc SĐT..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 8px;">
                <button type="submit" class="btn btn-search">Tìm kiếm</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-danger" style="text-decoration: none; display: inline-block; line-height: 20px;">Xóa lọc</a>
                <?php endif; ?>
            </form>

            <h3>Danh sách phiếu sửa chữa</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Khách hàng</th>
                        <th>SĐT</th>
                        <th>Thiết bị</th>
                        <th>Ngày nhận</th>
                        <th>Tình trạng</th>
                        <th>Chi phí dự kiến</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_list->num_rows > 0): ?>
                        <?php while ($row = $result_list->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['ma_phieu']; ?></td>
                                <td><?php echo htmlspecialchars($row['ten_kh']); ?></td>
                                <td><?php echo htmlspecialchars($row['dien_thoai']); ?></td>
                                <td><?php echo htmlspecialchars($row['ten_tb'] . " (" . $row['hang_sx'] . ")"); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['ngay_nhan'])); ?></td>
                                <td><?php echo htmlspecialchars($row['tinh_trang']); ?></td>
                                <td><?php echo number_format($row['chi_phi'], 0, ',', '.') . ' đ'; ?></td>
                                <td><b><?php echo htmlspecialchars($row['trang_thai']); ?></b></td>
                                <td>
                                    <a href="index.php?action=edit&id=<?php echo $row['ma_phieu']; ?>">Sửa</a> | 
                                    <a href="index.php?action=delete&id=<?php echo $row['ma_phieu']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu sửa chữa này không?');" style="color: red;">Xóa</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">Không tìm thấy phiếu sửa chữa nào!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>