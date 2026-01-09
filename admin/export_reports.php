<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Ambil parameter filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Query untuk laporan pemesanan
$query = "
    SELECT 
        DATE(b.created_at) as tanggal,
        COUNT(*) as total_pemesanan,
        SUM(b.total_harga) as total_pendapatan,
        SUM(CASE WHEN b.status = 'dikonfirmasi' THEN 1 ELSE 0 END) as dikonfirmasi,
        SUM(CASE WHEN b.status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN b.status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM 
        booking b
    WHERE 
        DATE(b.created_at) BETWEEN :start_date AND :end_date
    GROUP BY 
        DATE(b.created_at)
    ORDER BY 
        tanggal DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$reports = $stmt->fetchAll();

// Hitung total
$total_pemesanan = 0;
$total_pendapatan = 0;
$total_dikonfirmasi = 0;
$total_menunggu = 0;
$total_ditolak = 0;

foreach ($reports as $report) {
    $total_pemesanan += $report['total_pemesanan'];
    $total_pendapatan += $report['total_pendapatan'];
    $total_dikonfirmasi += $report['dikonfirmasi'];
    $total_menunggu += $report['menunggu'];
    $total_ditolak += $report['ditolak'];
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_pemesanan_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pemesanan</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .total { font-weight: bold; background-color: #f2f2f2; }
        .title { font-size: 18px; margin-bottom: 10px; font-weight: bold; }
        .subtitle { margin-bottom: 20px; color: #555; }
    </style>
</head>
<body>
    <div class="title">Laporan Pemesanan</div>
    <div class="subtitle">
        Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Total Pemesanan</th>
                <th>Dikonfirmasi</th>
                <th>Menunggu</th>
                <th>Ditolak</th>
                <th>Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($reports as $report): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                <td><?php echo number_format($report['total_pemesanan'], 0, ',', '.'); ?></td>
                <td><?php echo $report['dikonfirmasi']; ?></td>
                <td><?php echo $report['menunggu']; ?></td>
                <td><?php echo $report['ditolak']; ?></td>
                <td>Rp <?php echo number_format($report['total_pendapatan'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2">TOTAL</td>
                <td><?php echo number_format($total_pemesanan, 0, ',', '.'); ?></td>
                <td><?php echo $total_dikonfirmasi; ?></td>
                <td><?php echo $total_menunggu; ?></td>
                <td><?php echo $total_ditolak; ?></td>
                <td>Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 20px; font-size: 12px; color: #777;">
        Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
<?php
// Output the content
$content = ob_get_clean();
echo $content;
?>