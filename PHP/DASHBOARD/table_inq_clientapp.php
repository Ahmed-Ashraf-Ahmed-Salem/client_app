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
// Added 18/8/2025 :D
$clientType  = $request_data['clientType'] ?? 'all';      // افتراضي all

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

// Prepare the SQL query
$sql = "WITH reg AS (
    SELECT 
        NATIONAL_ID, 
        MIN(CREATED_AT) AS CREATED_AT
    FROM DM_CLIENT_REGISTER
    WHERE CREATED_AT >= :fromDate
      AND CREATED_AT <= :toDate
      AND CREATED_AT >= '2025-01-20'
    GROUP BY NATIONAL_ID
),

app_summary AS (
    SELECT 
        NATIONALNO,
        -- Flags to replace EXISTS
        MAX(CASE WHEN app_flag = 'C' AND emp_code IS NULL 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE('2025-01-20', 'yyyy-mm-dd') 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE(:fromDate, 'yyyy-mm-dd') 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') <= TO_DATE(:toDate, 'yyyy-mm-dd')
                   THEN 1 END) AS has_any_request,

        MAX(CASE WHEN app_flag = 'C' AND emp_code IS NULL 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE('2025-01-20', 'yyyy-mm-dd') 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE(:fromDate, 'yyyy-mm-dd') 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') <= TO_DATE(:toDate, 'yyyy-mm-dd')
                   AND app_stat <> 'D'
                   THEN 1 END) AS not_rejected,

        MAX(CASE WHEN app_flag = 'C' AND emp_code IS NULL 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE('2025-01-20', 'yyyy-mm-dd')
                   AND TO_DATE(appdate, 'dd-mm-yyyy') >= TO_DATE(:fromDate, 'yyyy-mm-dd') 
                   AND TO_DATE(appdate, 'dd-mm-yyyy') <= TO_DATE(:toDate, 'yyyy-mm-dd')
                   AND app_stat = 'D'
                   THEN 1 END) AS rejected
    FROM dm_clientapp
    GROUP BY nationalno
)

SELECT 
    TO_CHAR(TO_DATE(r.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM') as MONTH_YEAR,
    EXTRACT(YEAR FROM TO_DATE(r.CREATED_AT, 'YYYY-MM-DD')) as YEAR,
    EXTRACT(MONTH FROM TO_DATE(r.CREATED_AT, 'YYYY-MM-DD')) as MONTH,

    -- No_Request_Exists
    CASE 
      WHEN :clientType = 'cl' THEN 
         COUNT(CASE WHEN (a.has_any_request IS NULL) AND c.nationalno IS NOT NULL THEN 1 END)
      WHEN :clientType = 'pcl' THEN 
         COUNT(CASE WHEN (a.has_any_request IS NULL) AND c.nationalno IS NULL THEN 1 END)
      WHEN :clientType = 'all' THEN 
         COUNT(CASE WHEN (a.has_any_request IS NULL) AND c.nationalno IS NOT NULL THEN 1 END)
       + COUNT(CASE WHEN (a.has_any_request IS NULL) AND c.nationalno IS NULL THEN 1 END)
    END AS No_Request_Exists,

    -- Has_Request
    CASE 
      WHEN :clientType = 'cl' THEN 
         COUNT(CASE WHEN a.has_any_request IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
      WHEN :clientType = 'pcl' THEN 
         COUNT(CASE WHEN a.has_any_request IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
      WHEN :clientType = 'all' THEN 
         COUNT(CASE WHEN a.has_any_request IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
       + COUNT(CASE WHEN a.has_any_request IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
    END AS Has_Request,
    -- not_rejected
    CASE 
      WHEN :clientType = 'cl' THEN 
         COUNT(CASE WHEN a.not_rejected IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
      WHEN :clientType = 'pcl' THEN 
         COUNT(CASE WHEN a.not_rejected IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
      WHEN :clientType = 'all' THEN 
         COUNT(CASE WHEN a.not_rejected IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
       + COUNT(CASE WHEN a.not_rejected IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
    END AS Not_Rejected,

    -- Rejected
    CASE 
      WHEN :clientType = 'cl' THEN 
         COUNT(CASE WHEN a.rejected IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
      WHEN :clientType = 'pcl' THEN 
         COUNT(CASE WHEN a.rejected IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
      WHEN :clientType = 'all' THEN 
         COUNT(CASE WHEN a.rejected IS NOT NULL AND c.nationalno IS NOT NULL THEN 1 END)
       + COUNT(CASE WHEN a.rejected IS NOT NULL AND c.nationalno IS NULL THEN 1 END)
    END AS Rejected

FROM reg r
LEFT JOIN app_summary a ON r.national_id = a.nationalno
LEFT JOIN lm_client c   ON r.national_id = c.nationalno

GROUP BY 
    TO_CHAR(TO_DATE(r.CREATED_AT, 'YYYY-MM-DD'), 'YYYY-MM'),
    EXTRACT(YEAR FROM TO_DATE(r.CREATED_AT, 'YYYY-MM-DD')),
    EXTRACT(MONTH FROM TO_DATE(r.CREATED_AT, 'YYYY-MM-DD'))

ORDER BY YEAR, MONTH;
";

// Prepare and execute the query
$stmt = @oci_parse($conn, $sql);

// Added 16/6/2025 :D
oci_bind_by_name($stmt, ':fromDate', $fromDate);
oci_bind_by_name($stmt, ':toDate', $toDate);
oci_bind_by_name($stmt, ":clientType", $clientType);

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

oci_close($conn);

// Return the results as JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    "status" => "OK",
    "message" => "Data retrieved successfully",
    "data" => $data
));
?>