<?php
/**
 * Database Seeder for Development & QA Testing
 * Mount Excel Boarding School
 *
 * NOTE: Disabled in production (APP_ENV=production) for safety.
 *
 * Usage:
 * php database/seed.php --sample    (Creates synthetic test applications for testing)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

if (APP_ENV === 'production') {
    die("SECURITY ERROR: Database seeder is disabled in production environments.\n");
}

$pdo = getDB();
$opt = $argv[1] ?? '';

if ($opt === '--sample') {
    echo "Seeding synthetic sample applications and inquiries for local testing...\n";

    // 1. Synthetic Nepali Unicode student record
    $app1 = generateApplicationNo();
    $pdo->prepare("
        INSERT INTO admissions (application_no, full_name, dob, gender, grade, parent_name, parent_contact, address, status, created_at)
        VALUES (:app, 'राम बहादुर श्रेष्ठ', '2016-04-12', 'Male', 'Grade 3', 'श्याम श्रेष्ठ', '+977-9818987497', 'Indrawati-12, Bhimtar', 'pending', NOW())
    ")->execute([':app' => $app1]);

    // 2. Synthetic English name student record
    $app2 = generateApplicationNo();
    $pdo->prepare("
        INSERT INTO admissions (application_no, full_name, dob, gender, grade, parent_name, parent_contact, address, status, created_at)
        VALUES (:app, 'Aarav K.C.', '2018-09-20', 'Male', 'Grade 1', 'Bikash K.C.', '+977-9841234567', 'Bhimtar, Sindhupalchowk', 'reviewed', NOW())
    ")->execute([':app' => $app2]);

    // 3. Synthetic contact inquiry record
    $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
        VALUES ('Sunita Tamang', 'sunita@example.com', '+977-9801234567', 'School Bus Route Inquiry', 'Does the school bus cover the route near Sipaghat in the morning?', 'unread', NOW())
    ")->execute();

    echo "Synthetic test records inserted successfully!\n";
} else {
    echo "Usage:\n";
    echo "  php database/seed.php --sample    (Seed synthetic sample applications for QA testing)\n";
}
