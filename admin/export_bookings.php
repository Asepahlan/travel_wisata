<?php
// Pastikan tidak ada output sebelum header
if (ob_get_level()) ob_end_clean();

// Set error reporting
error_reporting(0);

// Include config file
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Nama file
$filename = 'daftar_pemesanan_' . date('Y-m-d') . '.xls';

// Header untuk download file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// Buat header tabel Excel
$html = '<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #d9ead3; font-weight: bold; text-align: center; padding: 8px; border: 1px solid #000; }
        td { padding: 6px; border: 1px solid #ddd; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Daftar Pemesanan - ' . date('d/m/Y') . '</h2>
    <table>
        <tr>
            <th>No</th>
            <th>Kode Booking</th>
            <th>Nama Pemesan</th>
            <th>No. WhatsApp</th>
            <th>Tujuan</th>
            <th>Tanggal Berangkat</th>
            <th>Jml. Penumpang</th>
            <th>Total Harga</th>
            <th>Status</th>
            <th>Tanggal Pemesanan</th>
            <th>Catatan Admin</th>
        </tr>';

try {
    // Query untuk mengambil data pemesanan
    $query = "SELECT b.*, 
                     p.nama_paket,
                     r.asal, 
                     r.tujuan,
                     a.nama as nama_armada,
                     s.nama_supir
              FROM booking b 
              LEFT JOIN paket p ON b.id_paket = p.id
              LEFT JOIN rute r ON p.id_rute = r.id
              LEFT JOIN armada a ON b.id_armada = a.id
              LEFT JOIN supir s ON b.id_supir = s.id
              ORDER BY b.tanggal_berangkat DESC, b.created_at DESC";
    
    $stmt = $pdo->query($query);
    $no = 1;
    
    while ($booking = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format status
        $status = '';
        $statusClass = '';
        switch ($booking['status']) {
            case 'menunggu':
                $status = 'Menunggu Konfirmasi';
                $statusClass = 'background-color: #FFFF00;';
                break;
            case 'dikonfirmasi':
                $status = 'Terkonfirmasi';
                $statusClass = 'background-color: #92D050; color: #000;';
                break;
            case 'ditolak':
                $status = 'Ditolak';
                $statusClass = 'background-color: #FF0000; color: #FFF;';
                break;
            case 'selesai':
                $status = 'Selesai';
                $statusClass = 'background-color: #0070C0; color: #FFF;';
                break;
            default:
                $status = ucfirst($booking['status']);
                $statusClass = '';
        }
        
        $html .= '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td>' . htmlspecialchars($booking['kode_booking']) . '</td>
            <td>' . htmlspecialchars($booking['nama_pemesan']) . '</td>
            <td>' . htmlspecialchars($booking['no_wa']) . '</td>
            <td>' . htmlspecialchars(($booking['asal'] ?? '-') . ' - ' . ($booking['tujuan'] ?? '-')) . '</td>
            <td class="text-center">' . date('d/m/Y', strtotime($booking['tanggal_berangkat'])) . '</td>
            <td class="text-center">' . ($booking['jumlah_penumpang'] ?? '1') . ' orang</td>
            <td class="text-right">' . (isset($booking['total_harga']) ? 'Rp ' . number_format($booking['total_harga'], 0, ',', '.') : '-') . '</td>
            <td style="' . $statusClass . '" class="text-center">' . $status . '</td>
            <td class="text-center">' . date('d/m/Y H:i', strtotime($booking['created_at'])) . '</td>
            <td>' . htmlspecialchars($booking['catatan_admin'] ?? '-') . '</td>
        </tr>';
    }
    
} catch (Exception $e) {
    $html .= '<tr><td colspan="11" style="color:red;">Error: ' . $e->getMessage() . '</td></tr>';
}

$html .= '</table>
</body>
</html>';

// Output HTML ke browser
echo $html;
exit;