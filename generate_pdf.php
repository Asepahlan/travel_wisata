<?php
// Load configuration and helper functions
require __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/functions.php';

// Get booking code from URL
$kode_booking = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode_booking)) {
    header('Location: index.php');
    exit();
}

try {
    // Fetch booking details
    $stmt = $pdo->prepare("
        SELECT b.*, p.nama_paket, p.jenis_layanan, 
               CONCAT(r.asal, ' - ', r.tujuan) as rute,
               a.nama as nama_armada, a.jenis as jenis_armada
        FROM booking b
        JOIN paket p ON b.id_paket = p.id
        JOIN rute r ON p.id_rute = r.id
        JOIN armada a ON b.id_armada = a.id
        WHERE b.kode_booking = ?
    ");
    $stmt->execute([$kode_booking]);
    $pemesanan = $stmt->fetch();

    if (!$pemesanan) {
        throw new Exception('Pemesanan tidak ditemukan');
    }

    // Format dates
    $tanggal_pesan = date('d F Y H:i', strtotime($pemesanan['created_at']));
    $tgl_berangkat = date('d F Y', strtotime($pemesanan['tanggal_berangkat']));
    
    // Status text
    $status_text = [
        'menunggu' => 'Menunggu Konfirmasi',
        'dikonfirmasi' => 'Terkonfirmasi',
        'ditolak' => 'Ditolak',
        'selesai' => 'Selesai'
    ][$pemesanan['status']] ?? $pemesanan['status'];

    // Generate HTML content
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Bukti Pemesanan ' . htmlspecialchars($kode_booking) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
            .subtitle { font-size: 16px; color: #444; margin-bottom: 10px; }
            .section { margin-bottom: 20px; }
            .section-title { 
                font-size: 16px; 
                font-weight: bold; 
                color: #2c5282; 
                border-bottom: 1px solid #2c5282; 
                padding-bottom: 5px; 
                margin-bottom: 10px;
            }
            .info-row { margin-bottom: 5px; overflow: hidden; }
            .label { float: left; width: 150px; font-weight: bold; color: #555; }
            .value { margin-left: 160px; }
            .divider { border-top: 1px dashed #ddd; margin: 20px 0; }
            .footer { 
                margin-top: 40px; 
                font-size: 12px; 
                color: #666; 
                text-align: center;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }
            .total { font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">' . htmlspecialchars(site_name) . '</div>
            <div class="subtitle">Bukti Pemesanan</div>
            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                Dicetak pada: ' . date('d F Y H:i:s') . '
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Informasi Pemesanan</div>
            <div class="info-row">
                <div class="label">Kode Booking</div>
                <div class="value">' . htmlspecialchars($pemesanan['kode_booking']) . '</div>
            </div>
            <div class="info-row">
                <div class="label">Tanggal Pemesanan</div>
                <div class="value">' . $tanggal_pesan . ' WIB</div>
            </div>
            <div class="info-row">
                <div class="label">Status</div>
                <div class="value">' . $status_text . '</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section">
            <div class="section-title">Detail Pemesan</div>
            <div class="info-row">
                <div class="label">Nama Lengkap</div>
                <div class="value">' . htmlspecialchars($pemesanan['nama_pemesan']) . '</div>
            </div>
            <div class="info-row">
                <div class="label">Nomor WhatsApp</div>
                <div class="value">' . htmlspecialchars($pemesanan['no_wa']) . '</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section">
            <div class="section-title">Detail Perjalanan</div>
            <div class="info-row">
                <div class="label">Rute</div>
                <div class="value">' . htmlspecialchars($pemesanan['rute']) . '</div>
            </div>
            <div class="info-row">
                <div class="label">Tanggal Berangkat</div>
                <div class="value">' . $tgl_berangkat . '</div>
            </div>
            <div class="info-row">
                <div class="label">Jenis Layanan</div>
                <div class="value">' . ($pemesanan['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In') . '</div>
            </div>
            <div class="info-row">
                <div class="label">Armada</div>
                <div class="value">' . htmlspecialchars($pemesanan['nama_armada'] . ' (' . $pemesanan['jenis_armada'] . ')') . '</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section">
            <div class="section-title">Ringkasan Pembayaran</div>
            <div class="info-row">
                <div class="label text-right">Harga Paket</div>
                <div class="value" style="width: 100px; float: right; text-align: right;">' . formatRupiah($pemesanan['total_harga']) . '</div>
            </div>
            <div class="info-row" style="margin-top: 10px;">
                <div class="label text-right"><strong>Total Pembayaran</strong></div>
                <div class="value" style="width: 100px; float: right; text-align: right; font-weight: bold;">' . formatRupiah($pemesanan['total_harga']) . '</div>
            </div>
            <div class="text-center" style="font-style: italic; font-size: 12px; color: #666; margin-top: 5px;">
                * Harga sudah termasuk PPN 11%
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section">
            <div class="section-title">Informasi Penting</div>
            <ol style="padding-left: 20px; margin: 10px 0;">
                <li style="margin-bottom: 8px;">Tunjukkan bukti pemesanan ini saat check-in.</li>
                <li style="margin-bottom: 8px;">Pastikan tiba di lokasi penjemputan 30 menit sebelum keberangkatan.</li>
                <li style="margin-bottom: 8px;">Hubungi tim kami jika ada perubahan atau pertanyaan.</li>
                <li>Pembayaran dapat dilakukan melalui transfer bank yang akan diinformasikan oleh tim kami.</li>
            </ol>
        </div>
        
        <div class="footer">
            <div style="margin-bottom: 5px;">' . htmlspecialchars(site_name) . '</div>
            <div>' . htmlspecialchars(contact_phone) . ' | ' . htmlspecialchars(contact_email) . '</div>
            <div style="margin-top: 5px;">' . htmlspecialchars(contact_address) . '</div>
        </div>
    </body>
    </html>';

    // Force download as HTML file with PDF extension
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="Bukti_Pemesanan_' . $kode_booking . '.pdf"');
    
    // Add JavaScript to auto-close the window after download starts
    echo $html . '
    <script>
        // Close the window after a short delay
        setTimeout(function() {
            window.close();
        }, 500);
    </script>';
    exit;
    
} catch (Exception $e) {
    // Log error
    error_log('PDF Generation Error: ' . $e->getMessage());
    
    // Redirect to error page or back to booking page
    header('Location: index.php?error=pdf_generation_failed');
    exit();
}
?>
