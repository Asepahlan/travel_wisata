<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Laporan';

// Set default date range (30 days ago to today)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Get date range from URL parameters if provided
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

// Query to get report data
$query = "
    SELECT 
        DATE(created_at) as tanggal,
        COUNT(*) as total_pemesanan,
        SUM(total_harga) as total_pendapatan,
        SUM(CASE WHEN status = 'dikonfirmasi' THEN 1 ELSE 0 END) as dikonfirmasi,
        SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM booking
    WHERE DATE(created_at) BETWEEN :start_date AND :end_date
    GROUP BY DATE(created_at)
    ORDER BY tanggal DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_pemesanan = 0;
$total_pendapatan = 0;
$total_menunggu = 0;
$total_ditolak = 0;

foreach ($reports as $report) {
    $total_pemesanan += $report['total_pemesanan'];
    $total_pendapatan += $report['total_pendapatan'];
    $total_menunggu += $report['menunggu'];
    $total_ditolak += $report['ditolak'];
}

ob_start();
?>
<div class="space-y-6">
    <!-- Print Header (Hidden by default, shown only when printing) -->
    <div class="print-header" style="display: none;">
        <img src="../assets/images/deas.png" alt="Logo Perusahaan" class="print-logo">
        <h1 class="print-title">Laporan Pemesanan</h1>
        <div class="print-period">
            Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 print-hide">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Laporan</h2>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        <a href="reports.php" class="ml-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Pemesanan -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-50 text-blue-600 mr-4">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">Total Pemesanan</p>
                            <p class="text-xl font-semibold text-gray-900"><?php echo number_format($total_pemesanan); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Total Pendapatan -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-50 text-green-600 mr-4">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">Total Pendapatan</p>
                            <p class="text-xl font-semibold text-gray-900">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Menunggu Konfirmasi -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-50 text-yellow-600 mr-4">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">Menunggu Konfirmasi</p>
                            <p class="text-xl font-semibold text-gray-900"><?php echo number_format($total_menunggu); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Ditolak -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-50 text-red-600 mr-4">
                            <i class="fas fa-times-circle text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-500 truncate">Ditolak</p>
                            <p class="text-xl font-semibold text-gray-900"><?php echo number_format($total_ditolak); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 print-hide">
                <!-- Pendapatan Harian -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Pendapatan Harian</h3>
                    </div>
                    <div class="chart-container" style="min-height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <!-- Status Pemesanan -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Status Pemesanan</h3>
                    </div>
                    <div class="chart-container" style="min-height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detail Laporan -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden print:border-0">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-2 sm:mb-0">Detail Laporan</h3>
                        <div class="flex space-x-2 no-print">
                            <a href="export_reports.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-file-export mr-2"></i> Export Excel
                            </a>
                            <button onclick="window.print()" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-print mr-2"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <div class="align-middle inline-block min-w-full">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pemesanan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pendapatan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dikonfirmasi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Menunggu</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ditolak</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo date('d M Y', strtotime($report['tanggal'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo number_format($report['total_pemesanan']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                Rp <?php echo number_format($report['total_pendapatan'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    <?php echo $report['dikonfirmasi']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    <?php echo $report['menunggu']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    <?php echo $report['ditolak']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                                                <p class="mt-1">Tidak ada data laporan untuk rentang tanggal yang dipilih.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global chart instances
        let revenueChart = null;
        let statusChart = null;
        let isPrinting = false;

        // Check if charts should be initialized
        function canInitializeCharts() {
            return typeof Chart !== 'undefined' && !isPrinting;
        }

        // Format number to currency
        function formatCurrency(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID');
        }

        // Initialize Revenue Chart
        function initRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx || !canInitializeCharts()) return null;

            const dates = <?php echo json_encode(array_column($reports, 'tanggal')); ?>;
            const revenues = <?php echo json_encode(array_column($reports, 'total_pendapatan')); ?>;

            const formattedDates = dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            });

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: formattedDates,
                    datasets: [{
                        label: 'Pendapatan',
                        data: revenues,
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(59, 130, 246, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'white',
                            titleColor: '#1F2937',
                            bodyColor: '#4B5563',
                            borderColor: '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: ctx => formatCurrency(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { callback: value => formatCurrency(value) }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Initialize Status Chart
        function initStatusChart() {
            const ctx = document.getElementById('statusChart');
            if (!ctx || !canInitializeCharts()) return null;

            const confirmed = <?php echo json_encode(array_column($reports, 'dikonfirmasi')); ?>;
            const pending = <?php echo json_encode(array_column($reports, 'menunggu')); ?>;
            const rejected = <?php echo json_encode(array_column($reports, 'ditolak')); ?>;

            const confirmedTotal = confirmed.reduce((a, b) => a + b, 0);
            const pendingTotal = pending.reduce((a, b) => a + b, 0);
            const rejectedTotal = rejected.reduce((a, b) => a + b, 0);

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Dikonfirmasi', 'Menunggu', 'Ditolak'],
                    datasets: [{
                        data: [confirmedTotal, pendingTotal, rejectedTotal],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'white',
                            titleColor: '#1F2937',
                            bodyColor: '#4B5563',
                            borderColor: '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize all charts
        function initCharts() {
            if (!canInitializeCharts()) return;
            
            // Destroy existing charts if they exist
            if (revenueChart) revenueChart.destroy();
            if (statusChart) statusChart.destroy();
            
            // Initialize new charts
            revenueChart = initRevenueChart();
            statusChart = initStatusChart();
        }

        // Handle window resize with debounce
        function handleResize() {
            if (revenueChart) revenueChart.resize();
            if (statusChart) statusChart.resize();
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initial chart initialization
            initCharts();
            
            // Handle window resize with debounce
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(handleResize, 250);
            });

            // Handle print events
            window.matchMedia('print').addEventListener('change', function(e) {
                isPrinting = e.matches;
                if (!isPrinting) {
                    // Re-initialize charts when returning from print
                    setTimeout(initCharts, 100);
                }
            });
        });
    </script>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
