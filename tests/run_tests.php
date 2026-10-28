<?php
/**
 * Automated Test Suite & Quality Assurance Checks
 * Mount Excel Boarding School
 * Run via CLI: php tests/run_tests.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function it($description, $assertion) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    try {
        if ($assertion()) {
            $passedTests++;
            echo "  [PASS] {$description}\n";
        } else {
            $failedTests++;
            echo "  [FAIL] {$description}\n";
        }
    } catch (Throwable $e) {
        $failedTests++;
        echo "  [ERROR] {$description} -> " . $e->getMessage() . "\n";
    }
}

echo "\n========================================================\n";
echo " Mount Excel Boarding School - Automated QA Test Suite\n";
echo "========================================================\n\n";

echo "1. Database & Schema Verification:\n";
it("Database connection via PDO succeeds", function() {
    $pdo = getDB();
    return ($pdo instanceof PDO);
});

it("Required tables exist in database", function() {
    $pdo = getDB();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['admissions', 'contact_messages', 'admins', 'activity_logs'];
    return count(array_intersect($required, $tables)) === count($required);
});

echo "\n2. Name Validation (Nepali Unicode & English):\n";
it("Validates English full names", function() {
    return validateNepaliName("Aarav Sharma") && validateNepaliName("John Doe");
});

it("Validates Nepali Devanagari Unicode names", function() {
    return validateNepaliName("राम बहादुर श्रेष्ठ") && validateNepaliName("सीता शर्मा");
});

it("Validates names with initials or hyphens", function() {
    return validateNepaliName("Aarav K.C.") && validateNepaliName("Mary-Jane Watson");
});

it("Rejects numbers and malicious script characters in names", function() {
    return !validateNepaliName("Aarav123") && 
           !validateNepaliName("<script>alert(1)</script>") && 
           !validateNepaliName("Aarav @ Home");
});

echo "\n3. Nepali Phone Number Validation:\n";
it("Accepts standard 10-digit Nepali mobile numbers (98, 97, 96)", function() {
    return validateNepaliPhone("9818987497") && 
           validateNepaliPhone("9741234567") && 
           validateNepaliPhone("9612345678");
});

it("Accepts phone numbers with +977 prefix and hyphens/spaces", function() {
    return validateNepaliPhone("+977-9818987497") && 
           validateNepaliPhone("+977 9818987497") && 
           validateNepaliPhone("9779818987497");
});

it("Rejects invalid / malformed phone numbers", function() {
    return !validateNepaliPhone("12345") && 
           !validateNepaliPhone("9512345678") && 
           !validateNepaliPhone("abcd9818987497") && 
           !validateNepaliPhone("9818987497123");
});

echo "\n4. Email Validation:\n";
it("Validates standard email addresses", function() {
    return validateEmail("mtexcel.principal@gmail.com") && validateEmail("parent@test.edu.np");
});

it("Rejects malformed emails", function() {
    return !validateEmail("invalid-email") && !validateEmail("@gmail.com");
});

echo "\n5. CSRF Protection:\n";
it("Generates a 64-character hexadecimal CSRF token", function() {
    $token = getCsrfToken();
    return strlen($token) === 64 && ctype_xdigit($token);
});

it("Validates identical CSRF tokens and rejects forged tokens", function() {
    $validToken = getCsrfToken();
    return verifyCsrfToken($validToken) && !verifyCsrfToken("forged_attacker_token_12345");
});

echo "\n6. CSV Formula Injection Defense:\n";
it("Escapes formula injection characters (=, +, -, @)", function() {
    return escapeCsvCell("=CMD|' /C calc'!A0") === "'=CMD|' /C calc'!A0" &&
           escapeCsvCell("+12345") === "'+12345" &&
           escapeCsvCell("-50") === "'-50" &&
           escapeCsvCell("@SUM(A1:A10)") === "'@SUM(A1:A10)" &&
           escapeCsvCell("Normal Text") === "Normal Text";
});

echo "\n7. Admin Password Hashing & Authentication:\n";
it("Hashes passwords with standard algorithm and verifies correctly", function() {
    $plain = "SecurePassword123!";
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    return password_verify($plain, $hash) && !password_verify("WrongPassword", $hash);
});

echo "\n8. Audit Activity Logging:\n";
it("Records administrative and security audit events into database", function() {
    $pdo = getDB();
    logActivity('QA_TEST_EVENT', 'Automated QA suite verified audit log pipeline');
    $stmt = $pdo->query("SELECT action FROM activity_logs WHERE action = 'QA_TEST_EVENT' ORDER BY id DESC LIMIT 1");
    $res = $stmt->fetch();
    return ($res && $res['action'] === 'QA_TEST_EVENT');
});

echo "\n9. Database Record Insertion & Query Performance:\n";
it("Can insert and retrieve student admission with reference number", function() {
    $pdo = getDB();
    $ref = generateApplicationNo();
    $stmt = $pdo->prepare("
        INSERT INTO admissions (application_no, full_name, dob, gender, grade, parent_contact, address, status, created_at)
        VALUES (:ref, 'परीक्षण विद्यार्थी', '2017-05-15', 'Female', 'Grade 2', '+977-9818987497', 'Bhimtar-12', 'pending', NOW())
    ");
    $stmt->execute([':ref' => $ref]);

    $fetchStmt = $pdo->prepare("SELECT full_name, grade FROM admissions WHERE application_no = :ref");
    $fetchStmt->execute([':ref' => $ref]);
    $row = $fetchStmt->fetch();

    return ($row && $row['full_name'] === 'परीक्षण विद्यार्थी' && $row['grade'] === 'Grade 2');
});

echo "\n========================================================\n";
echo " Test Results: {$passedTests} passed, {$failedTests} failed (Total: {$totalTests})\n";
echo "========================================================\n";

if ($failedTests > 0) {
    exit(1);
}
