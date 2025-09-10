<?php
// Eng. Ahmed AShraf Salem
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("status" => "Failed", "message" => "Only POST requests are allowed."));
    exit();
}

// Receive JSON data from the request
$json_data = file_get_contents('php://input');

// Check if JSON data is valid
$request_data = json_decode($json_data, true);

if ($request_data === null || json_last_error() !== JSON_ERROR_NONE) {
    logError($json_data, "Invalid JSON data: " . json_last_error_msg());
    http_response_code(400); // Bad Request
    echo json_encode(array("status" => "Failed", "message" => "Invalid JSON data."));
    exit();
}

$username = $request_data['username'] ?? '';
$password = $request_data['password'] ?? '';
// Added 16/6/2025 :D
$fromDate = $request_data['fromDate'] ?? '';
$toDate = $request_data['toDate'] ?? '';

// Make sure that the content type of the POST request has been set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$pos = strpos($contentType, 'application/json');

if ($pos === false) {
    logError($contentType, "Content type must be: application/json ");
    echo 'Content type must be: application/json ';
    return;
}

if ($username != 'mob' || $password != '+R.zR070UxB!' || getBearerToken($_SERVER['HTTP_AUTHORIZATION']) != 'jvPG6MdrLiVjOFY7aAXzeFct85ADAP') {
    logError($username, "Invalid Username or Password.");
    echo json_encode(array("status" => "Failed", "message" => "Invalid Username or Password."));
    return;
}

// Connect to Database
$conn = db_oracle_connect();

if (!$conn) {
    $error_message = oci_error();
    logError($error_message, "Failed to connect to Oracle database.");
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "Failed", "message" => "Failed to connect to Oracle database."));
    exit();
}

// Prepare the first SQL query (لم يقدموا طلبات)
$sql = "SELECT 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM') as MONTH_YEAR,
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as YEAR,
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as MONTH,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'جاري' THEN 1 END) AS In_Progress,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مسدد بالكامل' THEN 1 END) AS Paid_in_Full,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له شيك لم يصدر' THEN 1 END) AS Has_a_Check_Not_Issued,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'تم اسقاط آخر قرض' THEN 1 END) AS Last_Loan_Dropped,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'لا يوجد له طلب' THEN 1 END) AS No_Request_Exists,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له طلب مفتوح' THEN 1 END) AS Has_Open_Request,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مرفوض' THEN 1 END) AS Rejected
FROM (select NATIONAL_ID, MIN(CREATED_AT) as CREATED_AT from DM_CLIENT_REGISTER where DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
      AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20' GROUP BY NATIONAL_ID) DM_CLIENT_REGISTER,
     lm_client, 
     lr_clstat, 
     lr_branch, 
     lr_govern,
     lr_branch eb, 
     lr_govern eg,
     (SELECT * FROM lm_allemp WHERE EMPSTAT_CODE IN('001', '004')) LM_ALLEMP  
WHERE DM_CLIENT_REGISTER.NATIONAL_ID NOT IN (SELECT nationalno FROM dm_clientapp WHERE app_flag = 'C' AND EMP_CODE IS NULL)  
    AND lm_client.nationalno(+) = DM_CLIENT_REGISTER.national_id
    AND lm_client.cbranch_code = lr_branch.code (+)
    AND lr_branch.govern_code = lr_govern.code (+)
    AND lm_allemp.current_branch = eb.code (+)
    AND eb.govern_code = eg.code (+)
    AND lr_clstat.code (+) = lm_client.clstat_code
    AND lm_allemp.NATIONAL_ID(+) = DM_CLIENT_REGISTER.NATIONAL_ID
/*    AND DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
    AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate
    AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20'*/
    AND DECODE(NVL(LM_CLIENT.BRANCH_cODE, 'a'), 'a', 
                DECODE(NVL(LM_ALLEMP.BRANCH_CODE, 'a'), 'a', 'عميل محتمل', 'موظف'), 'عميل') = 'عميل'      
GROUP BY 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM'), 
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')), 
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'))
ORDER BY YEAR, MONTH
";

// Prepare and execute the query
$stmt = @oci_parse($conn, $sql);

// Added 16/6/2025 :D
oci_bind_by_name($stmt, ':fromDate', $fromDate);
oci_bind_by_name($stmt, ':toDate', $toDate);

// Execute the query
if (oci_execute($stmt)) {
    $data = [];
    // Fetch the results
    while ($row = oci_fetch_assoc($stmt)) {
        $data[] = $row;
    }
} else {
    $error_message = oci_error($stmt);
    logError($error_message, "Failed to execute query.");
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "Failed", "message" => "Failed to execute query: " . $error_message['message']));
    exit();
}

// Close Oracle connection
oci_free_statement($stmt);

// Prepare the third SQL query (قدموا طلبات)
$sql3 = "SELECT 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM') as MONTH_YEAR,
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as YEAR,
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as MONTH,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'جاري' THEN 1 END) AS In_Progress,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مسدد بالكامل' THEN 1 END) AS Paid_in_Full,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له شيك لم يصدر' THEN 1 END) AS Has_a_Check_Not_Issued,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'تم اسقاط آخر قرض' THEN 1 END) AS Last_Loan_Dropped,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'لا يوجد له طلب' THEN 1 END) AS No_Request_Exists,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له طلب مفتوح' THEN 1 END) AS Has_Open_Request,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مرفوض' THEN 1 END) AS Rejected
FROM (select NATIONAL_ID, MIN(CREATED_AT) as CREATED_AT from DM_CLIENT_REGISTER where DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
      AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20' GROUP BY NATIONAL_ID) DM_CLIENT_REGISTER,
     lm_client, 
     lr_clstat, 
     lr_branch, 
     lr_govern,
     lr_branch eb, 
     lr_govern eg,
     (SELECT * FROM lm_allemp WHERE EMPSTAT_CODE IN('001', '004')) LM_ALLEMP  
WHERE DM_CLIENT_REGISTER.NATIONAL_ID IN (SELECT nationalno FROM dm_clientapp WHERE app_flag = 'C' AND EMP_CODE IS NULL)  
    AND lm_client.nationalno(+) = DM_CLIENT_REGISTER.national_id
    AND lm_client.cbranch_code = lr_branch.code (+)
    AND lr_branch.govern_code = lr_govern.code (+)
    AND lm_allemp.current_branch = eb.code (+)
    AND eb.govern_code = eg.code (+)
    AND lr_clstat.code (+) = lm_client.clstat_code
    AND lm_allemp.NATIONAL_ID(+) = DM_CLIENT_REGISTER.NATIONAL_ID
/*    AND DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
    AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate
    AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20'*/
    AND DECODE(NVL(LM_CLIENT.BRANCH_cODE, 'a'), 'a', 
                DECODE(NVL(LM_ALLEMP.BRANCH_CODE, 'a'), 'a', 'عميل محتمل', 'موظف'), 'عميل') = 'عميل'      
GROUP BY 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM'), 
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')), 
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'))
ORDER BY YEAR, MONTH
";

// Prepare and execute the query
$stmt3 = @oci_parse($conn, $sql3);

// Added 16/6/2025 :D
oci_bind_by_name($stmt3, ':fromDate', $fromDate);
oci_bind_by_name($stmt3, ':toDate', $toDate);

// Execute the query
if (oci_execute($stmt3)) {
    $data3 = [];
    // Fetch the results
    while ($row = oci_fetch_assoc($stmt3)) {
        $data3[] = $row;
    }
} else {
    $error_message = oci_error($stmt3);
    logError($error_message, "Failed to execute query.");
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "Failed", "message" => "Failed to execute query: " . $error_message['message']));
    exit();
}

// Close Oracle connection
oci_free_statement($stmt3);


// Prepare the fourth SQL query ((قدموا ولم يقدموا طلبات) الكل)
$sql4 = "SELECT 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM') as MONTH_YEAR,
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as YEAR,
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')) as MONTH,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'جاري' THEN 1 END) AS In_Progress,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مسدد بالكامل' THEN 1 END) AS Paid_in_Full,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له شيك لم يصدر' THEN 1 END) AS Has_a_Check_Not_Issued,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'تم اسقاط آخر قرض' THEN 1 END) AS Last_Loan_Dropped,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'لا يوجد له طلب' THEN 1 END) AS No_Request_Exists,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'له طلب مفتوح' THEN 1 END) AS Has_Open_Request,
    COUNT(CASE WHEN lr_clstat.NAMEA = 'مرفوض' THEN 1 END) AS Rejected
FROM (select NATIONAL_ID, MIN(CREATED_AT) as CREATED_AT from DM_CLIENT_REGISTER where DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
      AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20' GROUP BY NATIONAL_ID) DM_CLIENT_REGISTER,
     lm_client, 
     lr_clstat, 
     lr_branch, 
     lr_govern,
     lr_branch eb, 
     lr_govern eg,
     (SELECT * FROM lm_allemp WHERE EMPSTAT_CODE IN('001', '004')) LM_ALLEMP  
WHERE lm_client.nationalno(+) = DM_CLIENT_REGISTER.national_id
    AND lm_client.cbranch_code = lr_branch.code (+)
    AND lr_branch.govern_code = lr_govern.code (+)
    AND lm_allemp.current_branch = eb.code (+)
    AND eb.govern_code = eg.code (+)
    AND lr_clstat.code (+) = lm_client.clstat_code
    AND lm_allemp.NATIONAL_ID(+) = DM_CLIENT_REGISTER.NATIONAL_ID
/*    AND DM_CLIENT_REGISTER.CREATED_AT >= :fromDate
    AND DM_CLIENT_REGISTER.CREATED_AT <= :toDate
    AND DM_CLIENT_REGISTER.CREATED_AT >= '2025-01-20'*/
    AND DECODE(NVL(LM_CLIENT.BRANCH_cODE, 'a'), 'a', 
                DECODE(NVL(LM_ALLEMP.BRANCH_CODE, 'a'), 'a', 'عميل محتمل', 'موظف'), 'عميل') = 'عميل' 
GROUP BY 
    TO_CHAR(TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM'), 
    EXTRACT(YEAR FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD')), 
    EXTRACT(MONTH FROM TO_DATE(DM_CLIENT_REGISTER.CREATED_AT, 'YYYY-MM-DD'))
ORDER BY YEAR, MONTH
";

// Prepare and execute the query
$stmt4 = @oci_parse($conn, $sql4);

// Added 16/6/2025 :D
oci_bind_by_name($stmt4, ':fromDate', $fromDate);
oci_bind_by_name($stmt4, ':toDate', $toDate);

// Execute the query
if (oci_execute($stmt4)) {
    $data4 = [];
    // Fetch the results
    while ($row = oci_fetch_assoc($stmt4)) {
        $data4[] = $row;
    }
} else {
    $error_message = oci_error($stmt4);
    logError($error_message, "Failed to execute query.");
    http_response_code(500); // Internal Server Error
    echo json_encode(array("status" => "Failed", "message" => "Failed to execute query: " . $error_message['message']));
    exit();
}

// Close Oracle connection
oci_free_statement($stmt4);

oci_close($conn);

// Return the results as JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    "status" => "OK",
    "message" => "Data retrieved successfully",
    "data" => $data,
    "app_cdata" => $data3,
    "all_data" => $data4
));
?>