<?php
// Eng. Ahmed AShraf Salem
$Pagename = 'طلبات';
require_once 'erheader.php';

$apiResult = "";

$today = date('Y-m-d');
$fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '2025-01-01';
$toDate = isset($_POST['toDate']) ? $_POST['toDate'] : "{$today}";

// -- cURL: POST with required payload --
$ch = curl_init("https://aba-clients.com:12405/MobApp/table_inq_lt.php");

$data = [
    "username" => "mob",
    "password" => "+R.zR070UxB!",
    "fromDate" => $fromDate,
    "toDate" => $toDate,
    "clientType"  => $clientType
];

$jsonData = json_encode($data);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer jvPG6MdrLiVjOFY7aAXzeFct85ADAP",
    "Content-Length: " . strlen($jsonData)
]);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    $apiResult = 'Curl error: ' . curl_error($ch);
} else {
    $apiResult = $result;
}
curl_close($ch);
?>

<head>
    <style>
        .service_card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }
        .monthly-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-family: Arial, sans-serif;
        }
        .monthly-table th, .monthly-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .monthly-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        .monthly-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .monthly-table tr:hover {
            background-color: #e9e9e9;
        }
        .total-row {
            font-weight: bold;
            background-color: #dff0d8 !important;
        }
        .month-row {
            font-weight: bold;
            background-color: #e7f3fe !important;
        }
        .radio-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .radio-container label {
            margin-right: 15px;
            font-weight: bold;
        }
        .dashboard-title {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="col-md-12 text-center mb-4">
            <h3 class="dashboard-title">عملاء حاليين قاموا بعمل حساب على تطبيق العملاء</h3>
        </div>
        <div class="row justify-content-center mb-4">
            <div class="service_card">
                <form method="POST" class="row align-items-end">
                    <div class="col-5">
                        <label for="fromDate"> من</label>
                        <input type="date" id="fromDate" name="fromDate" placeholder="YYYY-MM-DD"
                            value="<?= htmlspecialchars($fromDate); ?>" required>
                    </div>
                    <div class="col-5">
                        <label for="toDate">إلى</label>
                        <input type="date" id="toDate" name="toDate" placeholder="YYYY-MM-DD"
                            value="<?= htmlspecialchars($toDate); ?>" required>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-success" id="dataButton">عرض</button>
                    </div>
                </form>
            </div>
        </div>

     
        <div class="text-center">
            <div class="radio-container">
                <label><input type="radio" name="dataFilter" value="allData" checked> الكل</label> &nbsp;&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="dataFilter" value="talabData"> قاموا بتقديم طلبات</label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="dataFilter" value="chartData"> لم يقدموا طلبات</label>
            </div>
        </div>

        <div class="table-container">
            <div id="allDataTable">
                <?php
                if (!empty($apiResult)) {
                    $decoded = json_decode($apiResult, true);
                    if ($decoded !== null && isset($decoded['all_data'])) {
                        $allData = $decoded['all_data'];
                        
                        // Arabic month names
                        $arabicMonths = [
                            'January' => 'يناير',
                            'February' => 'فبراير',
                            'March' => 'مارس',
                            'April' => 'أبريل',
                            'May' => 'مايو',
                            'June' => 'يونيو',
                            'July' => 'يوليو',
                            'August' => 'أغسطس',
                            'September' => 'سبتمبر',
                            'October' => 'أكتوبر',
                            'November' => 'نوفمبر',
                            'December' => 'ديسمبر'
                        ];
                        
                        // Group data by month
                        $monthlyData = [];
                        foreach ($allData as $item) {
                            $monthKey = $item['MONTH_YEAR'];
                            if (!isset($monthlyData[$monthKey])) {
                                $date = DateTime::createFromFormat('Y-m', $monthKey);
                                $monthName = $arabicMonths[$date->format('F')] . ' ' . $date->format('Y');
                                
                                $monthlyData[$monthKey] = [
                                    'month_name' => $monthName,
                                    'IN_PROGRESS' => 0,
                                    'PAID_IN_FULL' => 0,
                                    'HAS_A_CHECK_NOT_ISSUED' => 0,
                                    'LAST_LOAN_DROPPED' => 0,
                                    'NO_REQUEST_EXISTS' => 0,
                                    'HAS_OPEN_REQUEST' => 0,
                                    'REJECTED' => 0
                                ];
                            }
                            
                            $monthlyData[$monthKey]['IN_PROGRESS'] += $item['IN_PROGRESS'] ?? 0;
                            $monthlyData[$monthKey]['PAID_IN_FULL'] += $item['PAID_IN_FULL'] ?? 0;
                            $monthlyData[$monthKey]['HAS_A_CHECK_NOT_ISSUED'] += $item['HAS_A_CHECK_NOT_ISSUED'] ?? 0;
                            $monthlyData[$monthKey]['LAST_LOAN_DROPPED'] += $item['LAST_LOAN_DROPPED'] ?? 0;
                            $monthlyData[$monthKey]['NO_REQUEST_EXISTS'] += $item['NO_REQUEST_EXISTS'] ?? 0;
                            $monthlyData[$monthKey]['HAS_OPEN_REQUEST'] += $item['HAS_OPEN_REQUEST'] ?? 0;
                            $monthlyData[$monthKey]['REJECTED'] += $item['REJECTED'] ?? 0;
                        }
                        
                        // Sort by year and month
                        ksort($monthlyData);
                        
                        // Calculate totals
                        $totals = [
                            'IN_PROGRESS' => 0,
                            'PAID_IN_FULL' => 0,
                            'HAS_A_CHECK_NOT_ISSUED' => 0,
                            'LAST_LOAN_DROPPED' => 0,
                            'NO_REQUEST_EXISTS' => 0,
                            'HAS_OPEN_REQUEST' => 0,
                            'REJECTED' => 0,
                            'all' => 0
                        ];
                        
                        // Display table
                        echo '<table class="monthly-table">';
                        echo '<thead><tr>';
                        echo '<th>الشهر</th>';
                        echo '<th>جاري</th>';
                        echo '<th>مسدد بالكامل</th>';
                        echo '<th>له شيك لم يصدر</th>';
                        echo '<th>تم اسقاط آخر قرض</th>';
                        echo '<th>لا يوجد له طلب</th>';
                        echo '<th>له طلب مفتوح</th>';
                        echo '<th>مرفوض</th>';
                        echo '<th>الإجمالي</th>';
                        echo '</tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($monthlyData as $month) {
                            $monthTotal = $month['IN_PROGRESS'] + $month['PAID_IN_FULL'] + $month['HAS_A_CHECK_NOT_ISSUED'] + 
                                          $month['LAST_LOAN_DROPPED'] + $month['NO_REQUEST_EXISTS'] + $month['HAS_OPEN_REQUEST'] + 
                                          $month['REJECTED'];
                                          
                            // Add to totals
                            $totals['IN_PROGRESS'] += $month['IN_PROGRESS'];
                            $totals['PAID_IN_FULL'] += $month['PAID_IN_FULL'];
                            $totals['HAS_A_CHECK_NOT_ISSUED'] += $month['HAS_A_CHECK_NOT_ISSUED'];
                            $totals['LAST_LOAN_DROPPED'] += $month['LAST_LOAN_DROPPED'];
                            $totals['NO_REQUEST_EXISTS'] += $month['NO_REQUEST_EXISTS'];
                            $totals['HAS_OPEN_REQUEST'] += $month['HAS_OPEN_REQUEST'];
                            $totals['REJECTED'] += $month['REJECTED'];
                            $totals['all'] += $monthTotal;
                            
                            echo '<tr class="month-row">';
                            echo '<td>' . $month['month_name'] . '</td>';
                            echo '<td>' . $month['IN_PROGRESS'] . '</td>';
                            echo '<td>' . $month['PAID_IN_FULL'] . '</td>';
                            echo '<td>' . $month['HAS_A_CHECK_NOT_ISSUED'] . '</td>';
                            echo '<td>' . $month['LAST_LOAN_DROPPED'] . '</td>';
                            echo '<td>' . $month['NO_REQUEST_EXISTS'] . '</td>';
                            echo '<td>' . $month['HAS_OPEN_REQUEST'] . '</td>';
                            echo '<td>' . $month['REJECTED'] . '</td>';
                            echo '<td>' . $monthTotal . '</td>';
                            echo '</tr>';
                        }
                        
                        // Display totals row
                        echo '<tr class="total-row">';
                        echo '<td>الإجمالي</td>';
                        echo '<td>' . $totals['IN_PROGRESS'] . '</td>';
                        echo '<td>' . $totals['PAID_IN_FULL'] . '</td>';
                        echo '<td>' . $totals['HAS_A_CHECK_NOT_ISSUED'] . '</td>';
                        echo '<td>' . $totals['LAST_LOAN_DROPPED'] . '</td>';
                        echo '<td>' . $totals['NO_REQUEST_EXISTS'] . '</td>';
                        echo '<td>' . $totals['HAS_OPEN_REQUEST'] . '</td>';
                        echo '<td>' . $totals['REJECTED'] . '</td>';
                        echo '<td>' . $totals['all'] . '</td>';
                        echo '</tr>';
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<div class="no-data">لا توجد بيانات متاحة</div>';
                    }
                } else {
                    echo '<div class="no-data">لا توجد بيانات متاحة</div>';
                }
                ?>
            </div>
            
            <div id="talabDataTable" style="display:none;">
                <!-- Table for "قاموا بتقديم طلبات" will be populated by JavaScript -->
                <div class="no-data">الرجاء اختيار "قاموا بتقديم طلبات" لعرض البيانات</div>
            </div>
            
            <div id="chartDataTable" style="display:none;">
                <!-- Table for "لم يقدموا طلبات" will be populated by JavaScript -->
                <div class="no-data">الرجاء اختيار "لم يقدموا طلبات" لعرض البيانات</div>
            </div>
        </div>
    </div>
    
    <?php
    // If the API returned data, inject it into JavaScript
    if (!empty($apiResult)) {
        $decoded = json_decode($apiResult, true);
        if ($decoded !== null) {
            echo '<script>';
            echo 'var chartData = ' . json_encode($decoded['data'], JSON_UNESCAPED_UNICODE) . ';';
            echo 'var talabData = ' . json_encode($decoded['app_cdata'], JSON_UNESCAPED_UNICODE) . ';';
            echo 'var allData = ' . json_encode($decoded['all_data'], JSON_UNESCAPED_UNICODE) . ';';
            echo 'var fromDate = "' . $fromDate . '";';
            echo 'var toDate = "' . $toDate . '";';
            echo '</script>';
        }
    }
    ?>
    
    <script>
    // Arabic month names
    const arabicMonths = {
        'January': 'يناير',
        'February': 'فبراير',
        'March': 'مارس',
        'April': 'أبريل',
        'May': 'مايو',
        'June': 'يونيو',
        'July': 'يوليو',
        'August': 'أغسطس',
        'September': 'سبتمبر',
        'October': 'أكتوبر',
        'November': 'نوفمبر',
        'December': 'ديسمبر'
    };
    
    // Function to generate monthly data table
    function generateMonthlyTable(data, containerId) {
        if (!data || data.length === 0) {
            document.getElementById(containerId).innerHTML = '<div class="no-data">لا توجد بيانات متاحة</div>';
            return;
        }
         
        // Group data by month
        const monthlyData = {};
        data.forEach(item => {
            const monthKey = item.MONTH_YEAR;
            if (!monthlyData[monthKey]) {
                const date = new Date(monthKey + '-01');
                const monthName = arabicMonths[date.toLocaleString('default', { month: 'long' })] + ' ' + date.getFullYear();
                
                monthlyData[monthKey] = {
                    month_name: monthName,
                    IN_PROGRESS: 0,
                    PAID_IN_FULL: 0,
                    HAS_A_CHECK_NOT_ISSUED: 0,
                    LAST_LOAN_DROPPED: 0,
                    NO_REQUEST_EXISTS: 0,
                    HAS_OPEN_REQUEST: 0,
                    REJECTED: 0
                };
            }
            
            monthlyData[monthKey].IN_PROGRESS += parseInt(item.IN_PROGRESS || 0);
            monthlyData[monthKey].PAID_IN_FULL += parseInt(item.PAID_IN_FULL || 0);
            monthlyData[monthKey].HAS_A_CHECK_NOT_ISSUED += parseInt(item.HAS_A_CHECK_NOT_ISSUED || 0);
            monthlyData[monthKey].LAST_LOAN_DROPPED += parseInt(item.LAST_LOAN_DROPPED || 0);
            monthlyData[monthKey].NO_REQUEST_EXISTS += parseInt(item.NO_REQUEST_EXISTS || 0);
            monthlyData[monthKey].HAS_OPEN_REQUEST += parseInt(item.HAS_OPEN_REQUEST || 0);
            monthlyData[monthKey].REJECTED += parseInt(item.REJECTED || 0);
        });
        
        // Sort by year and month
        const sortedMonths = Object.keys(monthlyData).sort();
        
        // Calculate totals
        const totals = {
            IN_PROGRESS: 0,
            PAID_IN_FULL: 0,
            HAS_A_CHECK_NOT_ISSUED: 0,
            LAST_LOAN_DROPPED: 0,
            NO_REQUEST_EXISTS: 0,
            HAS_OPEN_REQUEST: 0,
            REJECTED: 0,
            all: 0
        };
        
        // Generate HTML table
        let html = '<table class="monthly-table"><thead><tr>';
        html += '<th>الشهر</th>';
        html += '<th>جاري</th>';
        html += '<th>مسدد بالكامل</th>';
        html += '<th>له شيك لم يصدر</th>';
        html += '<th>تم اسقاط آخر قرض</th>';
        html += '<th>لا يوجد له طلب</th>';
        html += '<th>له طلب مفتوح</th>';
        html += '<th>مرفوض</th>';
        html += '<th>الإجمالي</th>';
        html += '</tr></thead><tbody>';
        
        sortedMonths.forEach(monthKey => {
            const month = monthlyData[monthKey];
            const monthTotal = month.IN_PROGRESS + month.PAID_IN_FULL + month.HAS_A_CHECK_NOT_ISSUED + 
                              month.LAST_LOAN_DROPPED + month.NO_REQUEST_EXISTS + month.HAS_OPEN_REQUEST + 
                              month.REJECTED;
            
            // Add to totals
            totals.IN_PROGRESS += month.IN_PROGRESS;
            totals.PAID_IN_FULL += month.PAID_IN_FULL;
            totals.HAS_A_CHECK_NOT_ISSUED += month.HAS_A_CHECK_NOT_ISSUED;
            totals.LAST_LOAN_DROPPED += month.LAST_LOAN_DROPPED;
            totals.NO_REQUEST_EXISTS += month.NO_REQUEST_EXISTS;
            totals.HAS_OPEN_REQUEST += month.HAS_OPEN_REQUEST;
            totals.REJECTED += month.REJECTED;
            totals.all += monthTotal;
            
            html += '<tr class="month-row">';
            html += `<td>${month.month_name}</td>`;
            html += `<td>${month.IN_PROGRESS}</td>`;
            html += `<td>${month.PAID_IN_FULL}</td>`;
            html += `<td>${month.HAS_A_CHECK_NOT_ISSUED}</td>`;
            html += `<td>${month.LAST_LOAN_DROPPED}</td>`;
            html += `<td>${month.NO_REQUEST_EXISTS}</td>`;
            html += `<td>${month.HAS_OPEN_REQUEST}</td>`;
            html += `<td>${month.REJECTED}</td>`;
            html += `<td>${monthTotal}</td>`;
            html += '</tr>';
        });
        
        // Add totals row
        html += '<tr class="total-row">';
        html += '<td>الإجمالي</td>';
        html += `<td>${totals.IN_PROGRESS}</td>`;
        html += `<td>${totals.PAID_IN_FULL}</td>`;
        html += `<td>${totals.HAS_A_CHECK_NOT_ISSUED}</td>`;
        html += `<td>${totals.LAST_LOAN_DROPPED}</td>`;
        html += `<td>${totals.NO_REQUEST_EXISTS}</td>`;
        html += `<td>${totals.HAS_OPEN_REQUEST}</td>`;
        html += `<td>${totals.REJECTED}</td>`;
        html += `<td>${totals.all}</td>`;
        html += '</tr>';
        
        html += '</tbody></table>';
        
        document.getElementById(containerId).innerHTML = html;
    }
    
    // Initialize tables for all data types
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare talabData table (قاموا بتقديم طلبات)
        if (talabData && talabData.length > 0) {
            generateMonthlyTable(talabData, 'talabDataTable');
        } else {
            document.getElementById('talabDataTable').innerHTML = '<div class="no-data">لا توجد بيانات متاحة</div>';
        }
        
        // Prepare chartData table (لم يقدموا طلبات)
        if (chartData && chartData.length > 0) {
            generateMonthlyTable(chartData, 'chartDataTable');
        } else {
            document.getElementById('chartDataTable').innerHTML = '<div class="no-data">لا توجد بيانات متاحة</div>';
        }
    });
    
    // Handle radio button changes
    document.querySelectorAll('input[name="dataFilter"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const selected = this.value;
            
            document.getElementById('allDataTable').style.display = 'none';
            document.getElementById('talabDataTable').style.display = 'none';
            document.getElementById('chartDataTable').style.display = 'none';
            
            if (selected === 'allData') {
                document.getElementById('allDataTable').style.display = 'block';
            } else if (selected === 'talabData') {
                document.getElementById('talabDataTable').style.display = 'block';
            } else if (selected === 'chartData') {
                document.getElementById('chartDataTable').style.display = 'block';
            }
        });
    });
    </script>
</body>
</html>