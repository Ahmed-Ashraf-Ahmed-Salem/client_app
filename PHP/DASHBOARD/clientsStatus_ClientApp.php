<?php
// Eng. Ahmed AShraf Salem
$Pagename = 'طلبات';
require_once 'erheader.php';

$apiResult = "";

$today = date('Y-m-d');
$fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '2025-01-01';
$toDate = isset($_POST['toDate']) ? $_POST['toDate'] : "{$today}";
// New Added 18/8/2025 By Ashraf
$clientType = isset($_POST['clientType']) ? $_POST['clientType'] : 'all'; // all, cl, pcl

// -- cURL: POST with required payload --
$ch = curl_init("https://aba-clients.com:12405/MobApp/table_inq_clientapp.php");

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
        .custom-btn {
            min-width: 150px;           /* نفس العرض لكل زر */
            color: black;               /* لون النص أسود */
            background-color: #e9ecef;  /* رمادي فاتح */
            border: 1px solid #ced4da;  /* حدود خفيفة */
        }

        .custom-btn:hover {
            background-color: #cfe2ff;  /* أزرق فاتح عند hover */
            color: black;
            border-color: #9ec5fe;
        }

        .custom-btn.active,
        .custom-btn:focus,
        .custom-btn:active {
            background-color: #9ec5fe;  /* أزرق أغمق شوية */
            color: black;
            border-color: #6ea8fe;
            box-shadow: 0 0 5px rgba(13, 110, 253, 0.5);
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

        <div class="row justify-content-center mb-4">
            <form method="POST" class="row justify-content-center mt-3">
                <div class="col-auto">
                    <button type="submit" name="clientType" value="all" 
                    class="btn custom-btn <?= ($clientType === 'all') ? 'active' : '' ?>">الكل</button>
                </div>
                <div class="col-auto">
                    <button type="submit" name="clientType" value="cl" 
                    class="btn custom-btn <?= ($clientType === 'cl') ? 'active' : '' ?>">عملاء حاليين</button>
                </div>
                <div class="col-auto">
                    <button type="submit" name="clientType" value="pcl" 
                    class="btn custom-btn <?= ($clientType === 'pcl') ? 'active' : '' ?>">عملاء محتملين</button>
                </div>
            </form>
        </div>
        <!--
        <div class="text-center">
            <div class="radio-container">
                <label><input type="radio" name="dataFilter" value="allData" checked> الكل</label> &nbsp;&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="dataFilter" value="talabData"> قاموا بتقديم طلبات</label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="dataFilter" value="chartData"> لم يقدموا طلبات</label>
            </div>
        </div>
        -->
        <div class="table-container">
            <div id="allDataTable">
                <?php
                if (!empty($apiResult)) {
                    $decoded = json_decode($apiResult, true);
                    if ($decoded !== null && isset($decoded['data'])) {
                        $allData = $decoded['data'];
                        
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
                                    'NO_REQUEST_EXISTS' => 0,
                                    'HAS_REQUEST' => 0,
                                    'NOT_REJECTED' => 0,
                                    'REJECTED' => 0
                                ];
                            }
                            
                            $monthlyData[$monthKey]['NO_REQUEST_EXISTS'] += $item['NO_REQUEST_EXISTS'] ?? 0;
                            $monthlyData[$monthKey]['HAS_REQUEST'] += $item['HAS_REQUEST'] ?? 0;
                            $monthlyData[$monthKey]['NOT_REJECTED'] += $item['NOT_REJECTED'] ?? 0;
                            $monthlyData[$monthKey]['REJECTED'] += $item['REJECTED'] ?? 0;
                        }
                        
                        // Sort by year and month
                        ksort($monthlyData);
                        
                        // Calculate totals
                        $totals = [
                            'NO_REQUEST_EXISTS' => 0,
                            'HAS_REQUEST' => 0,
                            'NOT_REJECTED' => 0,
                            'REJECTED' => 0,
                            'all' => 0
                        ];
                        
                        // Display table
                        echo '<table class="monthly-table">';
                        echo '<thead><tr>';
                        echo '<th>الشهر</th>';
                        echo '<th>لم يقدموا طلبات</th>';
                        echo '<th>قاموا بتقديم طلبات</th>';
                        echo '<th>طلبات لم يم رفضها</th>';
                        echo '<th>طلبات مرفوضة</th>';
                        echo '<th>الإجمالي</th>';
                        echo '</tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($monthlyData as $month) {
                            $monthTotal = $month['NO_REQUEST_EXISTS'] + $month['HAS_REQUEST'] + $month['NOT_REJECTED'] + 
                                          $month['REJECTED'];
                                          
                            // Add to totals
                            $totals['NO_REQUEST_EXISTS'] += $month['NO_REQUEST_EXISTS'];
                            $totals['HAS_REQUEST'] += $month['HAS_REQUEST'];
                            $totals['NOT_REJECTED'] += $month['NOT_REJECTED'];
                            $totals['REJECTED'] += $month['REJECTED'];
                            $totals['all'] += $monthTotal;
                            
                            echo '<tr class="month-row">';
                            echo '<td>' . $month['month_name'] . '</td>';
                            echo '<td>' . $month['NO_REQUEST_EXISTS'] . '</td>';
                            echo '<td>' . $month['HAS_REQUEST'] . '</td>';
                            echo '<td>' . $month['NOT_REJECTED'] . '</td>';
                            echo '<td>' . $month['REJECTED'] . '</td>';
                            echo '<td>' . $monthTotal . '</td>';
                            echo '</tr>';
                        }
                        
                        // Display totals row
                        echo '<tr class="total-row">';
                        echo '<td>الإجمالي</td>';
                        echo '<td>' . $totals['NO_REQUEST_EXISTS'] . '</td>';
                        echo '<td>' . $totals['HAS_REQUEST'] . '</td>';
                        echo '<td>' . $totals['NOT_REJECTED'] . '</td>';
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
            <!--
            <div id="talabDataTable" style="display:none;">
                 Table for "قاموا بتقديم طلبات" will be populated by JavaScript 
                <div class="no-data">الرجاء اختيار "قاموا بتقديم طلبات" لعرض البيانات</div>
            </div>
            
            <div id="chartDataTable" style="display:none;">
                 Table for "لم يقدموا طلبات" will be populated by JavaScript
                <div class="no-data">الرجاء اختيار "لم يقدموا طلبات" لعرض البيانات</div>
            </div>
             -->
        </div>
    </div>
    
    <?php
    // If the API returned data, inject it into JavaScript
    if (!empty($apiResult)) {
        $decoded = json_decode($apiResult, true);
        if ($decoded !== null) {
            echo '<script>';
            echo 'var chartData = ' . json_encode($decoded['data'], JSON_UNESCAPED_UNICODE) . ';';
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
                    NO_REQUEST_EXISTS: 0,
                    HAS_REQUEST: 0,
                    NOT_REJECTED: 0,
                    REJECTED: 0
                };
            }
            
            monthlyData[monthKey].NO_REQUEST_EXISTS += parseInt(item.NO_REQUEST_EXISTS || 0);
            monthlyData[monthKey].HAS_REQUEST += parseInt(item.HAS_REQUEST || 0);
            monthlyData[monthKey].NOT_REJECTED += parseInt(item.NOT_REJECTED || 0);
            monthlyData[monthKey].REJECTED += parseInt(item.REJECTED || 0);
        });
        
        // Sort by year and month
        const sortedMonths = Object.keys(monthlyData).sort();
        
        // Calculate totals
        const totals = {
            NO_REQUEST_EXISTS: 0,
            HAS_REQUEST: 0,
            NOT_REJECTED: 0,
            REJECTED: 0,
            all: 0
        };
        

        // Generate HTML table
        let html = '<table class="monthly-table"><thead><tr>';
        html += '<th>الشهر</th>';
        html += '<th>لم يقدموا طلبات</th>';
        html += '<th>قاموا بتقديم طلبات</th>';
        html += '<th>طلبات لم يم رفضها</th>';
        html += '<th>طلبات مرفوضة</th>';
        html += '<th>الإجمالي</th>';
        html += '</tr></thead><tbody>';
        
        sortedMonths.forEach(monthKey => {
            const month = monthlyData[monthKey];
            const monthTotal = month.NO_REQUEST_EXISTS + month.HAS_REQUEST + month.NOT_REJECTED + 
                              month.REJECTED;
            
            // Add to totals
            totals.NO_REQUEST_EXISTS += month.NO_REQUEST_EXISTS;
            totals.HAS_REQUEST += month.HAS_REQUEST;
            totals.NOT_REJECTED += month.NOT_REJECTED;
            totals.REJECTED += month.REJECTED;
            totals.all += monthTotal;
            
            html += '<tr class="month-row">';
            html += `<td>${month.month_name}</td>`;
            html += `<td>${month.NO_REQUEST_EXISTS}</td>`;
            html += `<td>${month.HAS_REQUEST}</td>`;
            html += `<td>${month.NOT_REJECTED}</td>`;
            html += `<td>${month.REJECTED}</td>`;
            html += `<td>${monthTotal}</td>`;
            html += '</tr>';
        });
        
        // Add totals row
        html += '<tr class="total-row">';
        html += '<td>الإجمالي</td>';
        html += `<td>${totals.NO_REQUEST_EXISTS}</td>`;
        html += `<td>${totals.HAS_REQUEST}</td>`;
        html += `<td>${totals.NOT_REJECTED}</td>`;
        html += `<td>${totals.REJECTED}</td>`;
        html += `<td>${totals.all}</td>`;
        html += '</tr>';
        
        html += '</tbody></table>';
        
        document.getElementById(containerId).innerHTML = html;
    }
    
/*    // Initialize tables for all data types
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
    });*/
    
    // Handle radio button changes
/*    document.querySelectorAll('input[name="dataFilter"]').forEach(function(radio) {
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
    });*/
    </script>
</body>
</html>