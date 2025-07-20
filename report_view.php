<?php
// ---- ARCHIVO: report_view.php ----
$page_title = 'Reporte Detallado';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
$voting_id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT * FROM votings WHERE id = ? AND status = 'closed'");
    $stmt->execute([$voting_id]);
    $voting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voting) {
        redirect('reports.php?error=report_not_found');
    }

    $sql_results = "SELECT vo.option_text, COUNT(v.id) as vote_count 
                    FROM voting_options vo 
                    LEFT JOIN votes v ON vo.id = v.option_id 
                    WHERE vo.voting_id = ? 
                    GROUP BY vo.id ORDER BY vote_count DESC";
    $stmt_results = $pdo->prepare($sql_results);
    $stmt_results->execute([$voting_id]);
    $results_data = $stmt_results->fetchAll(PDO::FETCH_ASSOC);
    $total_votes = array_sum(array_column($results_data, 'vote_count'));

} catch (PDOException $e) {
    die("Error al obtener datos del reporte: " . $e->getMessage());
}
require_once __DIR__ . '/chart_config.php'; // Incluir la configuración
require_once __DIR__ . '/includes/header.php';
?>
<!-- Incluir librerías para gráficos y exportación a PDF -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="flex justify-between items-center mb-6">
    <a href="reports.php" class="text-blue-600 hover:underline">&larr; Volver a todos los reportes</a>
    <button id="export-pdf-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-300 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
        Exportar a PDF
    </button>
</div>

<!-- Contenedor del contenido que se va a exportar -->
<div id="report-content" class="p-10 bg-white rounded-lg shadow-lg border">
    <h1 class="text-3xl font-bold text-center mb-2"><?php echo htmlspecialchars($voting['title']); ?></h1>
    <p class="text-center text-gray-500 mb-8">Reporte de Votación Finalizada</p>
    
    <?php if ($voting['description']): ?>
    <p class="text-gray-700 mb-6 p-4 bg-gray-50 rounded-md"><?php echo nl2br(htmlspecialchars($voting['description'])); ?></p>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <!-- Tabla de Resultados -->
        <div>
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Resultados Detallados</h2>
            <table class="min-w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="text-left py-2 px-3">Opción</th>
                        <th class="text-center py-2 px-3">Votos</th>
                        <th class="text-center py-2 px-3">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results_data as $result): ?>
                    <tr class="border-b">
                        <td class="py-2 px-3"><?php echo htmlspecialchars($result['option_text']); ?></td>
                        <td class="text-center py-2 px-3 font-bold"><?php echo $result['vote_count']; ?></td>
                        <td class="text-center py-2 px-3">
                            <?php 
                                $percentage = ($total_votes > 0) ? ($result['vote_count'] / $total_votes) * 100 : 0;
                                echo number_format($percentage, 2) . '%';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-800 text-white font-bold">
                    <tr>
                        <td class="py-2 px-3">TOTAL</td>
                        <td class="text-center py-2 px-3"><?php echo $total_votes; ?></td>
                        <td class="text-center py-2 px-3">100.00%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- Gráfico de Pastel -->
        <div class="w-full max-w-sm mx-auto">
            <canvas id="resultsChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('resultsChart').getContext('2d');
    const resultsData = <?php echo json_encode($results_data); ?>;
 
 
    const chartType = <?php echo json_encode(CHART_TYPE); ?>;

    let chartConfig = createChartConfig(chartType, resultsData.map(r => r.option_text), resultsData.map(r => r.vote_count));
    new Chart(ctx, chartConfig);
 



    function createChartConfig(type, labels, data) {
        let config = {
            type: type === 'horizontalBar' ? 'bar' : type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Votos',
                    data: data,
                    backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        };
        if (type === 'bar' || type === 'horizontalBar') {
            config.options.indexAxis = (type === 'horizontalBar') ? 'y' : 'x';
            config.options.scales = { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { beginAtZero: true, ticks: { stepSize: 1 } } };
        } else { // pie
            config.options.plugins = { legend: { position: 'top' } };
        }
        return config;  
    }
    // Lógica para exportar a PDF
    document.getElementById('export-pdf-btn').addEventListener('click', function() {
        const reportElement = document.getElementById('report-content');
        const { jsPDF } = window.jspdf;

        html2canvas(reportElement, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            // A4 size: 210x297 mm
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            const ratio = canvasWidth / canvasHeight;
            const imgWidth = pdfWidth - 20; // with margin
            const imgHeight = imgWidth / ratio;

            let heightLeft = imgHeight;
            let position = 10; // top margin

            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
            heightLeft -= pdfHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight + 10;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pdfHeight;
            }
            
            pdf.save('reporte-votacion-<?php echo $voting_id; ?>.pdf');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
