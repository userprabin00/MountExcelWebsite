<?php
/**
 * Content Seed Script
 * Mount Excel Boarding School
 * CLI-only, idempotent — safe to run multiple times.
 *
 * Imports the photos that are currently hardcoded in:
 *   excursion.php, art-classes.php, merit-scholarship.php
 * into the gallery_items table using their original /images/ paths as
 * reference filenames (not uploaded into /uploads — the original files
 * remain in /images and are served from there as the fallback).
 *
 * Usage:
 *   php database/seed_content.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden. This script must be run from the command line.");
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "Mount Excel Boarding School — Content Seed\n";
echo str_repeat('─', 50) . "\n";

try {
    $pdo = getDB();

    // ------------------------------------------------------------------
    // Seed data: mirrors the hardcoded arrays in the public PHP pages.
    // filename here is the path as served publicly (relative to web root),
    // stored in the title so the public pages can use it.
    // We store relative paths so existing /images files are referenced.
    // ------------------------------------------------------------------
    $seeds = [
        // ── Merit Scholarship ──────────────────────────────────────────
        [
            'album'      => 'merit',
            'filename'   => 'images/event1.jpg',
            'title'      => 'Award Ceremony Stage',
            'caption'    => 'Honoring top rankers and dedicated students during the formal award assembly.',
            'sort_order' => 1,
        ],
        [
            'album'      => 'merit',
            'filename'   => 'images/merit2.jpg',
            'title'      => 'Certificate Distribution',
            'caption'    => 'Recognizing hard work, discipline, and outstanding marks across academic subjects.',
            'sort_order' => 2,
        ],
        [
            'album'      => 'merit',
            'filename'   => 'images/merit3.jpg',
            'title'      => 'Celebrating Student Achievement',
            'caption'    => 'Encouraging confidence, pride in learning, and a spirit of constructive competition.',
            'sort_order' => 3,
        ],
        [
            'album'      => 'merit',
            'filename'   => 'images/merit4.jpg',
            'title'      => 'Honored Recipients',
            'caption'    => 'Congratulations to all scholarship awardees for their hard work and excellence!',
            'sort_order' => 4,
        ],

        // ── Excursion ─────────────────────────────────────────────────
        [
            'album'      => 'excursion',
            'filename'   => 'images/event2.jpg',
            'title'      => 'Assembly at Sikali',
            'caption'    => 'Students and teachers gathering at the scenic excursion grounds.',
            'sort_order' => 1,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion2.jpg',
            'title'      => 'Nature & Trail Walk',
            'caption'    => 'Observing local flora, landscape geology, and countryside beauty.',
            'sort_order' => 2,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion3.jpg',
            'title'      => 'Cultural Exploration',
            'caption'    => 'Learning about the history and heritage surrounding Sikali.',
            'sort_order' => 3,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion4.jpg',
            'title'      => 'Group Activities',
            'caption'    => 'Interactive games and outdoor learning exercises.',
            'sort_order' => 4,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion5.jpg',
            'title'      => 'Friends & Mentors',
            'caption'    => 'Bonding with teachers and peers outside the traditional classroom.',
            'sort_order' => 5,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion6.jpg',
            'title'      => 'Field Observations',
            'caption'    => 'Practical experiential learning encouraging keen observation.',
            'sort_order' => 6,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion7.jpg',
            'title'      => 'Scenic Landscapes',
            'caption'    => 'Taking in the pristine natural surroundings and clean fresh air.',
            'sort_order' => 7,
        ],
        [
            'album'      => 'excursion',
            'filename'   => 'images/excursion8.jpg',
            'title'      => 'Excursion Memories',
            'caption'    => 'A memorable, safe, and joyful day of collective learning.',
            'sort_order' => 8,
        ],

        // ── Art Classes ───────────────────────────────────────────────
        [
            'album'      => 'art',
            'filename'   => 'images/event3.jpg',
            'title'      => 'Classroom Art Session',
            'caption'    => 'Students collaborating on drawing and fine motor projects under guidance.',
            'sort_order' => 1,
        ],
        [
            'album'      => 'art',
            'filename'   => 'images/art1.jpg',
            'title'      => 'Coloring & Sketching',
            'caption'    => 'Creative color shading and pencil sketch work from primary students.',
            'sort_order' => 2,
        ],
        [
            'album'      => 'art',
            'filename'   => 'images/art2.jpg',
            'title'      => 'Visual Arts & Imagination',
            'caption'    => 'Expressing thoughts, nature themes, and festive ideas through artwork.',
            'sort_order' => 3,
        ],
        [
            'album'      => 'art',
            'filename'   => 'images/art3.jpg',
            'title'      => 'Fine Crafts & Patterns',
            'caption'    => 'Handmade crafts developing patience, focus, and eye-hand coordination.',
            'sort_order' => 4,
        ],
        [
            'album'      => 'art',
            'filename'   => 'images/art4.jpg',
            'title'      => 'Student Exhibition Display',
            'caption'    => "Proudly showcasing artistic achievements during parents' visiting day.",
            'sort_order' => 5,
        ],
    ];

    $insertStmt = $pdo->prepare("
        INSERT INTO gallery_items (album, filename, title, caption, sort_order, is_visible, created_at, updated_at)
        VALUES (:album, :filename, :title, :caption, :sort_order, 1, NOW(), NOW())
    ");
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_items WHERE filename = :filename LIMIT 1");

    $inserted = 0;
    $skipped  = 0;

    foreach ($seeds as $seed) {
        $checkStmt->execute([':filename' => $seed['filename']]);
        if ($checkStmt->fetchColumn()) {
            echo "  SKIP (already exists): {$seed['filename']}\n";
            $skipped++;
            continue;
        }

        $insertStmt->execute([
            ':album'      => $seed['album'],
            ':filename'   => $seed['filename'],
            ':title'      => $seed['title'],
            ':caption'    => $seed['caption'],
            ':sort_order' => $seed['sort_order'],
        ]);
        echo "  INSERTED [{$seed['album']}]: {$seed['filename']}\n";
        $inserted++;
    }

    echo str_repeat('─', 50) . "\n";
    echo "Done. Inserted: {$inserted} | Skipped (already existed): {$skipped}\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
