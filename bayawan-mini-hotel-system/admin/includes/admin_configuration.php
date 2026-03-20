<?php
// bayawan-mini-hotel-system/admin/includes/admin_configuration.php
// Database connection and query helper functions.
// Credentials are read from constants defined in config/env.php,
// which in turn reads them from the .env file at the project root.

require_once __DIR__ . '/../../config/env.php';

// ── Database connection ───────────────────────────────────────────────

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    $detail = (APP_ENV === 'local') ? $conn->connect_error : 'Please contact the system administrator.';
    die('<p style="font-family:sans-serif;color:#c0392b;">Database connection failed. ' . htmlspecialchars($detail) . '</p>');
}

$conn->set_charset('utf8mb4');


// ── Input sanitisation ───────────────────────────────────────────────

function filteration(array $data): array {
    foreach ($data as $key => $value) {
        $value      = trim($value);
        $value      = stripslashes($value);
        $value      = strip_tags($value);
        $value      = htmlspecialchars($value);
        $data[$key] = $value;
    }
    return $data;
}


// ── Query helpers ─────────────────────────────────────────────────────

function selectAll(string $table) {
    return mysqli_query($GLOBALS['conn'], "SELECT * FROM `$table`");
}

function select(string $sql, array $values, string $datatypes) {
    $conn = $GLOBALS['conn'];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $datatypes, ...$values);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }
        mysqli_stmt_close($stmt);
        die('Query cannot be executed — Select');
    }
    die('Query cannot be prepared — Select');
}

function update(string $sql, array $values, string $datatypes): int {
    $conn = $GLOBALS['conn'];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $datatypes, ...$values);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }
        mysqli_stmt_close($stmt);
        die('Query cannot be executed — Update');
    }
    die('Query cannot be prepared — Update');
}

function insert(string $sql, array $values, string $datatypes): int {
    $conn = $GLOBALS['conn'];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $datatypes, ...$values);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }
        mysqli_stmt_close($stmt);
        die('Query cannot be executed — Insert');
    }
    die('Query cannot be prepared — Insert');
}

function delete(string $sql, array $values, string $datatypes): int {
    $conn = $GLOBALS['conn'];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $datatypes, ...$values);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }
        mysqli_stmt_close($stmt);
        die('Query cannot be executed — Delete');
    }
    die('Query cannot be prepared — Delete');
}