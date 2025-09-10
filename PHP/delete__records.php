<?php
header("Content-Type: application/json");
include 'opr_connect.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "Failed", "Message" => "Only POST method is allowed"]);
    exit;
}

// Ensure content-type is application/json
if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
    echo json_encode(["status" => "Failed", "Message" => "Content-Type must be application/json"]);
    exit;
}

// Decode input
//$decoded = json_decode(file_get_contents("php://input"), true);
$content = trim(file_get_contents("php://input"));
$content = str_replace("'", '"', $content);
$decoded = json_decode($content, true);
$decoded = json_decode(file_get_contents("php://input"), true);
//print_r($decoded);
/*if (
    !isset($decoded['username']) || $decoded['username'] !== 'System' ||
    !isset($decoded['password']) || $decoded['password'] !== '2023@AbaToday' ||
    getBearerToken() !== 'jvPG6MdrLiVjOFY7aAXzeFct85ADAP'
) {
    echo json_encode(["status" => "Failed", "Message" => "Invalid Username or Password"]);
    exit;
}*/

if (!isset($decoded['username']) || $decoded['username'] !== 'System') {
    echo json_encode(["status" => "Failed", "Message" => "Invalid Username"]);
    exit;
}

if (!isset($decoded['password']) || $decoded['password'] !== '2023@AbaToday') {
    echo json_encode(["status" => "Failed", "Message" => "Invalid Password"]);
    exit;
}

if (getBearerToken() !== 'jvPG6MdrLiVjOFY7aAXzeFct85ADAP') {
    echo json_encode(["status" => "Failed", "Message" => "Invalid Token"]);
    exit;
}



// Validate records
if (!isset($decoded['records']) || !is_array($decoded['records']) || count($decoded['records']) == 0) {
    echo json_encode(["status" => "Failed", "Message" => "No records to delete"]);
    exit;
}

// Log to a file (optional)
file_put_contents('dm_log.txt', json_encode($decoded) . PHP_EOL, FILE_APPEND);

$records = $decoded['records'];


mysqli_begin_transaction($link);
$deletedRecords = [];
$allDeleted = true;
$recordsFound = false; // Track if any records were found
foreach ($records as $rec) {
//    $serial = mysqli_real_escape_string($link, $rec['serial']);
    $serial = (int) $rec['serial'];
    $nationalno = mysqli_real_escape_string($link, $rec['nationalno']);
    $empStatus = mysqli_real_escape_string($link, $rec['empStatus']);

    if ($empStatus === 'A') {
        $selectQuery = "
            SELECT OFF_BRANCH_CODE, OFF_CODE, APPDATE, LASTUPDATE 
            FROM dm_clientapp 
            WHERE serial = $serial AND nationalno = '$nationalno'";
    } elseif ($empStatus === 'T') {
        // NOW() > DATE_ADD(LASTUPDATE, INTERVAL 2 DAY)
        $selectQuery = "
            SELECT OFF_BRANCH_CODE, OFF_CODE, APPDATE, LASTUPDATE 
            FROM dm_clientapp 
            WHERE serial = $serial AND nationalno = '$nationalno'
            AND NOW() > DATE_ADD(
                LASTUPDATE,
                INTERVAL (
                    CASE 
                    -- If lastupdate is Wednesday, add 4 days instead of 2 (skip Fri & Sat)
                        WHEN DAYOFWEEK(LASTUPDATE) = 4 THEN 4
                    -- If Thursday, add 4 days (Thu + 4 = Mon)
                        WHEN DAYOFWEEK(LASTUPDATE) = 5 THEN 4
                    -- If Friday, add 3 days (Fri + 3 = Mon)
                        WHEN DAYOFWEEK(LASTUPDATE) = 6 THEN 3
                    -- If Saturday, add 2 days (Sat + 2 = Mon)
                        WHEN DAYOFWEEK(LASTUPDATE) = 7 THEN 2
                        ELSE 2
                    END
                ) DAY
            )";
    } else {
        continue;
    } 

    $result = mysqli_query($link, $selectQuery);

    if (!$result) {
        echo json_encode([
            "status" => "Error",
            "Message" => "Select query failed: " . mysqli_error($link)
        ]);
        exit;
    }

    if ($result && mysqli_num_rows($result) > 0) {
        $recordsFound = true;
        $row = mysqli_fetch_assoc($result);
        $deletedRecords[] = [
            "serial" => $serial,
            "nationalno" => $nationalno,
            "emp_status" => $empStatus,
            "OFF_BRANCH_CODE" => $row['OFF_BRANCH_CODE'],
            "OFF_CODE" => $row['OFF_CODE'],
            "APPDATE" => $row['APPDATE'],
            "LASTUPDATE" => $row['LASTUPDATE']
        ];


        if ($empStatus === 'A') {
            $archiveQuery = "INSERT INTO dm_clnt_d SELECT * FROM dm_clientapp WHERE serial = $serial AND nationalno = '$nationalno'";
            mysqli_query($link, $archiveQuery);

            $deleteQuery = "DELETE FROM dm_clientapp WHERE serial = $serial AND nationalno = '$nationalno'";
        } elseif ($empStatus === 'T') {
            $archiveQuery = "INSERT INTO dm_clnt_d SELECT * FROM dm_clientapp WHERE serial = $serial AND nationalno = '$nationalno' 
                             AND NOW() > DATE_ADD(
                                LASTUPDATE,
                                INTERVAL (
                                    CASE 
                                        WHEN DAYOFWEEK(LASTUPDATE) = 4 THEN 4
                                        WHEN DAYOFWEEK(LASTUPDATE) = 5 THEN 4
                                        WHEN DAYOFWEEK(LASTUPDATE) = 6 THEN 3
                                        WHEN DAYOFWEEK(LASTUPDATE) = 7 THEN 2
                                        ELSE 2
                                    END
                                ) DAY
                            )";
            mysqli_query($link, $archiveQuery);

            $deleteQuery = "DELETE FROM dm_clientapp WHERE serial = $serial AND nationalno = '$nationalno' 
                            AND NOW() > DATE_ADD(
                                LASTUPDATE,
                                INTERVAL (
                                    CASE 
                                        WHEN DAYOFWEEK(LASTUPDATE) = 4 THEN 4
                                        WHEN DAYOFWEEK(LASTUPDATE) = 5 THEN 4
                                        WHEN DAYOFWEEK(LASTUPDATE) = 6 THEN 3
                                        WHEN DAYOFWEEK(LASTUPDATE) = 7 THEN 2
                                        ELSE 2
                                    END
                                ) DAY
                            )";
        } else {
            continue;
        }
   
         $deleteResult = mysqli_query($link, $deleteQuery);

        if (!$deleteResult || mysqli_affected_rows($link) == 0) {
            $allDeleted = false;
            break;
        }
    }
}

// After processing all records
if (!$recordsFound) {
    echo json_encode([
        "status" => "Error",
        "Message" => "No matching officers found."
    ]);
    exit;
}

if ($allDeleted) {
    mysqli_commit($link);
    echo json_encode(["status" => "OK", "Message" => "All records deleted successfully",
        "deleted_records" => $deletedRecords]);
} else {
    mysqli_rollback($link);
    echo json_encode(["status" => "Failed", "Message" => "Some or all records were not deleted"]);
}


function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

?>