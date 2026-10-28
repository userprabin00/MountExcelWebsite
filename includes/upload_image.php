<?php
/**
 * Secure Image Upload Helper
 * Mount Excel Boarding School
 *
 * Usage:
 *   require_once __DIR__ . '/upload_image.php';
 *   $filename = processUploadedImage($_FILES['photo'], ROOT_PATH . '/uploads/gallery');
 *
 * Returns the bare filename (no path) on success.
 * Throws RuntimeException with a user-safe message on any failure.
 */

defined('MEBS_APP_INIT') || require_once __DIR__ . '/config.php';

/**
 * Process and securely store an uploaded image.
 *
 * @param array  $file    Single entry from $_FILES (e.g. $_FILES['photo'])
 * @param string $destDir Absolute path to the destination directory (must exist)
 * @return string         Generated filename (basename only, no directory)
 * @throws RuntimeException on any validation or processing failure
 */
function processUploadedImage(array $file, string $destDir): string
{
    // ── 1. Handle post_max_size overflow (empty $_POST / $_FILES) ──────────────
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid upload structure. Please try again.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file was selected for upload.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('The uploaded file is too large. Maximum allowed size is 8 MB.');
        default:
            throw new RuntimeException('Upload failed with error code ' . $file['error'] . '. Please try again.');
    }

    // ── 2. Hard size limit (8 MB) ───────────────────────────────────────────────
    $maxBytes = 8 * 1024 * 1024; // 8 MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File exceeds the 8 MB size limit. Please resize the image and try again.');
    }

    // ── 3. Allowed MIME types ────────────────────────────────────────────────────
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];

    // Verify extension from the client filename (not the tmp name)
    $clientExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($clientExt, $allowedExts, true)) {
        throw new RuntimeException('Invalid file type. Only JPG, PNG, and WebP images are allowed.');
    }

    // ── 4. Real MIME via finfo (content-based, not client-provided) ─────────────
    if (!function_exists('finfo_open')) {
        throw new RuntimeException('Server error: fileinfo extension is not available.');
    }
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new RuntimeException('File content does not match an allowed image type. Upload rejected.');
    }

    // ── 5. Verify it is actually a valid image with getimagesize() ───────────────
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Uploaded file could not be read as an image. Upload rejected.');
    }

    // ── 6. Megapixel limit (40 MP) ───────────────────────────────────────────────
    $maxMegapixels = 40 * 1_000_000;
    $pixels = (int)$imageInfo[0] * (int)$imageInfo[1];
    if ($pixels > $maxMegapixels) {
        throw new RuntimeException('Image resolution exceeds 40 megapixels. Please downscale and re-upload.');
    }

    // ── 7. Re-encode with GD (strips EXIF, fixes orientation, resizes) ───────────
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $source = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new RuntimeException('Unsupported image format after decoding. Upload rejected.');
    }

    if ($source === false) {
        throw new RuntimeException('Image could not be decoded. The file may be corrupt.');
    }

    // Fix EXIF orientation for JPEG images
    if ($imageInfo[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        if (!empty($exif['Orientation'])) {
            $source = _fixImageOrientation($source, (int)$exif['Orientation']);
        }
    }

    // Resize to max 1600px on longest side (keep aspect ratio)
    $origW = imagesx($source);
    $origH = imagesy($source);
    $maxDim = 1600;

    if ($origW > $maxDim || $origH > $maxDim) {
        if ($origW >= $origH) {
            $newW = $maxDim;
            $newH = (int)round($origH * ($maxDim / $origW));
        } else {
            $newH = $maxDim;
            $newW = (int)round($origW * ($maxDim / $origH));
        }
        $resized = imagecreatetruecolor($newW, $newH);
        // Preserve transparency for PNG/WebP
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_WEBP) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
        }
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($source);
        $source = $resized;
    }

    // ── 8. Generate random filename ──────────────────────────────────────────────
    // Always store as JPEG for uniform compatibility (except PNG/WebP stay as-is)
    if ($imageInfo[2] === IMAGETYPE_PNG) {
        $outExt = 'png';
    } elseif ($imageInfo[2] === IMAGETYPE_WEBP) {
        $outExt = 'webp';
    } else {
        $outExt = 'jpg';
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $outExt;

    // ── 9. Validate destination directory (realpath check) ───────────────────────
    $realDest = realpath($destDir);
    if ($realDest === false || !is_dir($realDest) || !is_writable($realDest)) {
        imagedestroy($source);
        throw new RuntimeException('Upload destination directory is not accessible. Contact the administrator.');
    }

    $outPath = $realDest . DIRECTORY_SEPARATOR . $filename;

    // ── 10. Save the re-encoded image ─────────────────────────────────────────────
    $saved = false;
    if ($outExt === 'png') {
        $saved = imagepng($source, $outPath, 8);
    } elseif ($outExt === 'webp') {
        $saved = imagewebp($source, $outPath, 85);
    } else {
        $saved = imagejpeg($source, $outPath, 88);
    }
    imagedestroy($source);

    if (!$saved) {
        throw new RuntimeException('Failed to save the processed image. Check server disk space and permissions.');
    }

    return $filename;
}

/**
 * Delete an uploaded file safely.
 * Only removes files inside /uploads/ — never touches /images/ or any other directory.
 *
 * @param string $filename  Bare filename (no path)
 * @param string $subDir    'gallery' or 'staff'
 * @return bool
 */
function deleteUploadedImage(string $filename, string $subDir): bool
{
    if (empty($filename)) {
        return false;
    }

    $uploadsRoot = realpath(ROOT_PATH . '/uploads');
    if ($uploadsRoot === false) {
        return false;
    }

    $targetPath = realpath($uploadsRoot . '/' . $subDir . '/' . $filename);
    if ($targetPath === false) {
        return false; // File doesn't exist
    }

    // Ensure target is strictly inside /uploads/
    if (strpos($targetPath, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0) {
        error_log("SECURITY: deleteUploadedImage blocked path traversal attempt: $targetPath");
        return false;
    }

    if (is_file($targetPath)) {
        return unlink($targetPath);
    }

    return false;
}

/**
 * Fix JPEG image rotation based on EXIF orientation tag.
 *
 * @param GdImage $image
 * @param int     $orientation  EXIF orientation value (1-8)
 * @return GdImage
 */
function _fixImageOrientation($image, int $orientation)
{
    switch ($orientation) {
        case 3:
            return imagerotate($image, 180, 0);
        case 6:
            return imagerotate($image, -90, 0);
        case 8:
            return imagerotate($image, 90, 0);
        default:
            return $image;
    }
}
