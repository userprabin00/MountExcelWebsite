<?php
/**
 * Safe CSV Export for Admissions
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Strict Role Check: Staff cannot export CSV
requireRole(['admin', 'super_admin']);

$pdo = getDB();

// Optional filters matching admissions view
$search = trim($_GET['search'] ?? '');
$filterGrade = trim($_GET['grade'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE :s1 OR application_no LIKE :s2 OR parent_contact LIKE :s3)";
    $searchTerm = "%{$search}%";
    $params[':s1'] = $searchTerm;
    $params[':s2'] = $searchTerm;
    $params[':s3'] = $searchTerm;
}
if ($filterGrade !== '') {
    $where[] = "grade = :grade";
    $params[':grade'] = $filterGrade;
}
if ($filterStatus !== '') {
    $where[] = "status = :status";
    $params[':status'] = $filterStatus;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT application_no, full_name, dob, gender, grade, parent_name, parent_contact, parent_email, address, previous_school, status, admin_notes, created_at 
    FROM admissions 
    {$whereSql} 
    ORDER BY id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Log audit action
$currentUser = currentAdmin();
logActivity('CSV_EXPORT_ADMISSIONS', "Staff {$currentUser['username']} exported " . count($rows) . " admission records to CSV", $currentUser['id']);

$filename = 'mount_excel_admissions_' . date('Y-m-d_His') . '.csv';

// Output CSV headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens Nepali Devanagari Unicode characters without corrupting them
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, [
    'Application Number',
    'Student Full Name',
    'Date of Birth',
    'Gender',
    'Grade Applying For',
    'Parent/Guardian Name',
    'Parent Contact Number',
    'Parent Email',
    'Residential Address',
    'Previous School',
    'Status',
    'Internal Staff Notes',
    'Submission Date'
]);

// Write Rows with Formula Injection Escaping
foreach ($rows as $row) {
    fputcsv($output, [
        escapeCsvCell($row['application_no']),
        escapeCsvCell($row['full_name']),
        escapeCsvCell($row['dob']),
        escapeCsvCell($row['gender']),
        escapeCsvCell($row['grade']),
        escapeCsvCell($row['parent_name'] ?? ''),
        escapeCsvCell($row['parent_contact']),
        escapeCsvCell($row['parent_email'] ?? ''),
        escapeCsvCell($row['address']),
        escapeCsvCell($row['previous_school'] ?? ''),
        escapeCsvCell($row['status']),
        escapeCsvCell($row['admin_notes'] ?? ''),
        escapeCsvCell($row['created_at'])
    ]);
}

fclose($output);
exit;
