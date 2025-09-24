#!/bin/bash

# Test upload script with debug logging
echo "=== Testing file upload with debug logging ==="

# Clear previous logs
echo "Clearing previous logs..."
> /Users/macvn/Desktop/www/vietnamnet2.vn/writeable/logs/logger.log

# Run the curl command
echo "Running curl upload..."
curl 'https://vietnamnet2.vn/api/v1/files/upload/' \
  -H 'accept: */*' \
  -H 'accept-language: en-US,en;q=0.9,vi;q=0.8' \
  -H 'content-type: multipart/form-data; boundary=----WebKitFormBoundarykArcZMJdkp2jNWAF' \
  -b 'cmsff_logged=1; PHPSESSID=vi84lpcce771papptprbp3oibb; cmsff_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiI0NTgzOTU0Mzc1NDUiLCJpYXQiOjE3NTg1Mzc0MjksImV4cCI6MTkxNjIxNzQyOSwibmJmIjoxNzU4NTM3NDI5LCJ1c2VyX2lkIjoxLCJyb2xlIjoiYWRtaW4iLCJ1c2VybmFtZSI6InNhZG1pbiIsImVtYWlsIjoiYWRtaW5AZXhhbXBsZS5jb20ifQ.K2DunPs3DDVLmNz_9vGxH2O10LkBb5YW8SkoYtRxdO0; cmsff_secret=8456f4a56df4c49f471ae5272d11839f' \
  -H 'origin: https://vietnamnet2.vn' \
  -H 'priority: u=1, i' \
  -H 'referer: https://vietnamnet2.vn/admin/files/timeline/' \
  -H 'sec-ch-ua: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"' \
  -H 'sec-ch-ua-mobile: ?0' \
  -H 'sec-ch-ua-platform: "macOS"' \
  -H 'sec-fetch-dest: empty' \
  -H 'sec-fetch-mode: cors' \
  -H 'sec-fetch-site: same-origin' \
  -H 'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36' \
  --data-raw $'------WebKitFormBoundarykArcZMJdkp2jNWAF\r\nContent-Disposition: form-data; name="path"\r\n\r\n2025:09:23\r\n------WebKitFormBoundarykArcZMJdkp2jNWAF\r\nContent-Disposition: form-data; name="config"\r\n\r\n{"resizes":[],"watermark":false,"watermark_img":null,"output":{"jpg":{"name":"jpg","q":80},"webp":{"name":"jpg.webp","q":80}},"original":true}\r\n------WebKitFormBoundarykArcZMJdkp2jNWAF\r\nContent-Disposition: form-data; name="files[]"; filename="The-Gioi-24H.jpg"\r\nContent-Type: image/jpeg\r\n\r\n\r\n------WebKitFormBoundarykArcZMJdkp2jNWAF--\r\n' \
  -v

echo ""
echo "=== Upload completed, checking logs ==="
echo ""

# Show the logs
echo "=== DEBUG LOGS ==="
cat /Users/macvn/Desktop/www/vietnamnet2.vn/writeable/logs/logger.log

echo ""
echo "=== Checking if upload directory exists ==="
ls -la /Users/macvn/Desktop/www/vietnamnet2.vn/writeable/uploads/2025/09/23/ 2>/dev/null || echo "Directory does not exist"

echo ""
echo "=== Checking if any files were created ==="
find /Users/macvn/Desktop/www/vietnamnet2.vn/writeable/uploads/ -name "*The-Gioi-24H*" -type f 2>/dev/null || echo "No files found with that name"
