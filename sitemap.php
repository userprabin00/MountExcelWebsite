<?php
/**
 * Dynamic Sitemap
 * Mount Excel Boarding School
 * Reads SITE_URL from .env so no hardcoded localhost references remain.
 */

require_once __DIR__ . '/includes/config.php';

$siteUrl = rtrim((string)env('SITE_URL', 'https://mountexcel.edu.np'), '/');

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/index.php</loc>
    <priority>1.0</priority>
    <changefreq>weekly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/about.php</loc>
    <priority>0.8</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/academics.php</loc>
    <priority>0.9</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/admissions.php</loc>
    <priority>0.9</priority>
    <changefreq>weekly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/facilities.php</loc>
    <priority>0.8</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/staff.php</loc>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/gallery.php</loc>
    <priority>0.7</priority>
    <changefreq>weekly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/merit-scholarship.php</loc>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/excursion.php</loc>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/art-classes.php</loc>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/video.php</loc>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc><?= htmlspecialchars($siteUrl) ?>/contact.php</loc>
    <priority>0.8</priority>
    <changefreq>monthly</changefreq>
  </url>
</urlset>
