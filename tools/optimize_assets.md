# Asset optimization commands

These commands write optimized copies and leave the original assets untouched. Each video is scaled to fit within 1280×720, encoded as H.264 with CRF 28, AAC audio, and fast-start metadata. The 700 kb/s video ceiling is a practical size target; duration and scene complexity affect the final file size. Check each output and lower `-maxrate`/`-b:v` if it exceeds 5 MB.

```bash
ffmpeg -i images/video1.mp4 -vf "scale=1280:720:force_original_aspect_ratio=decrease:force_divisible_by=2" -c:v libx264 -preset slow -crf 28 -b:v 700k -maxrate 700k -bufsize 1400k -c:a aac -b:a 64k -movflags +faststart images/video1-optimized.mp4
ffmpeg -i images/video2.mp4 -vf "scale=1280:720:force_original_aspect_ratio=decrease:force_divisible_by=2" -c:v libx264 -preset slow -crf 28 -b:v 700k -maxrate 700k -bufsize 1400k -c:a aac -b:a 64k -movflags +faststart images/video2-optimized.mp4
```

For every image over 300 KB, create separate optimized copies and preserve source images:

```bash
mkdir -p images/optimized
find images -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \) -size +300k -print0 | while IFS= read -r -d '' image; do
  name="$(basename "${image%.*}")"
  cwebp -q 78 "$image" -o "images/optimized/$name.webp"
done
find images -type f \( -iname '*.jpg' -o -iname '*.jpeg' \) -size +300k -print0 | while IFS= read -r -d '' image; do
  jpegoptim --max=80 --strip-all --dest=images/optimized "$image"
done
```

Compare output appearance and file size before choosing an optimized copy for the site.
