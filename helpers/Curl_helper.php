<?php
/**
 * Curl_Helper.php
 *
 * Provides helper functions for sending HTTP requests using cURL: GET, POST (including file upload), and downloading files.
 * Optimized by extracting common parts into curl_run().
 */

if ( ! function_exists('curl_run'))
{
    /**
     * Common cURL execution function.
     *
     * @param string $url URL to call.
     * @param array  $customOptions Additional cURL options.
     * @param int    $timeout Request timeout (seconds).
     * @return mixed Response content from curl_exec or false on error.
     */
    function curl_run($url, $customOptions = array(), $timeout = 10)
    {
        $ch = curl_init();
        // Cài đặt các option cơ bản
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // Tắt SSL (nên bật lại trong môi trường production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Thiết lập các tùy chọn bổ sung nếu có
        foreach ($customOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $response = curl_exec($ch);
        if(curl_errno($ch)) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $response;
    }
}

if ( ! function_exists('curl_get'))
{
    /**
     * Execute GET request with cURL.
     *
     * @param string $url URL to call.
     * @param array  $params Query string data.
     * @param array  $headers Optional headers array.
     * @param int    $timeout Request timeout (seconds).
     * @return mixed Response content or false on error.
     */
    function curl_get($url, $params = array(), $headers = array(), $timeout = 10)
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $customOptions = array();
        if (!empty($headers)) {
            $customOptions[CURLOPT_HTTPHEADER] = $headers;
        }
        return curl_run($url, $customOptions, $timeout);
    }
}

if ( ! function_exists('curl_post'))
{
    /**
     * Execute POST request with cURL.
     *
     * @param string $url URL to call.
     * @param array  $postData POST data.
     * @param array  $headers Optional headers array.
     * @param int    $timeout Request timeout (seconds).
     * @return mixed Response content or false on error.
     */
    function curl_post($url, $postData = array(), $headers = array(), $timeout = 10)
    {
        $customOptions = array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $postData,
        );
        if (!empty($headers)) {
            $customOptions[CURLOPT_HTTPHEADER] = $headers;
        }
        return curl_run($url, $customOptions, $timeout);
    }
}

if ( ! function_exists('curl_post_file'))
{
    /**
     * Execute file upload (or send multipart file) with cURL.
     * After execution, if temporary file paths are provided, they will be deleted automatically.
     *
     * @param string $url API upload URL.
     * @param array  $postData POST data array (may include CURLFile objects).
     * @param array  $tempFiles (Optional) List of temporary files to delete after upload.
     * @param array  $headers Optional headers array.
     * @param int    $timeout Request timeout (seconds).
     * @return mixed Response content or false on error.
     */
    function curl_post_file($url, $postData = array(), $tempFiles = array(), $headers = array(), $timeout = 10)
    {
        $customOptions = array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $postData,
        );
        if (!empty($headers)) {
            $customOptions[CURLOPT_HTTPHEADER] = $headers;
        }
        $response = curl_run($url, $customOptions, $timeout);
        if ($response === false) {
            // Delete temporary files if error
            if (!empty($tempFiles)) {
                foreach ($tempFiles as $file) {
                    @unlink($file);
                }
            }
            return false;
        }
        // Delete temporary files after successful upload
        if (!empty($tempFiles)) {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }
        return $response;
    }
}

if ( ! function_exists('curl_download_file'))
{
    /**
     * Download file from URL and save to specified path.
     *
     * @param string $url File URL to download.
     * @param string $savePath Path to save file after download.
     * @param int    $timeout Request timeout (seconds).
     * @return mixed Saved file path if successful, false on error.
     */
    function curl_download_file($url, $savePath, $timeout = 10)
    {
        // Check $savePath must be in PATH_WRITE
        $realSavePath = realpath(dirname($savePath));
        $realWritePath = realpath(PATH_WRITE);
        if ($realWritePath === false || $realSavePath === false || strpos($realSavePath, $realWritePath) !== 0) {
            return false;
        }
        $response = curl_run($url, array(), $timeout);
        if ($response === false) {
            return false;
        }
        if (file_put_contents($savePath, $response) !== false) {
            return $savePath;
        }
        return false;
    }
}

/*

1. Example usage of curl_get
<?php
// Example usage of curl_get
$url = 'https://api.example.com/data';
$params = [
    'user'  => 'john_doe',
    'limit' => 10
];
$headers = [
    'Accept: application/json'
];

// Call curl_get and get result
$response = curl_get($url, $params, $headers);

if ($response !== false) {
    $data = json_decode($response, true);
    // Handle returned data
    print_r($data);
} else {
    echo "An error occurred during GET request.";
}

2. Example usage of curl_post
<?php
// Example usage of curl_post
$url = 'https://api.example.com/login';
$postData = [
    'username' => 'john_doe',
    'password' => 'secret123'
];
$headers = [
    'Content-Type: application/x-www-form-urlencoded'
];

$response = curl_post($url, $postData, $headers);

if ($response !== false) {
    $result = json_decode($response, true);
    // Xử lý kết quả trả về, ví dụ: kiểm tra status, token,...
    print_r($result);
} else {
    echo "Có lỗi xảy ra trong quá trình gọi POST.";
}

3. Example usage of curl_post_file
<?php
// Example usage of curl_post_file
$url = 'https://api.example.com/upload';
$uploadPath = '/uploads/images/';
$tempFile = '/path/to/temp/image.webp';
$mimeType = 'image/webp';
$fileName = 'image.webp';

// Tạo đối tượng CURLFile từ file tạm
$cFile = new CURLFile($tempFile, $mimeType, $fileName);

// Mảng dữ liệu POST, bao gồm thông tin thư mục upload và file cần upload
$postData = [
    'path'    => $uploadPath,
    'files[]' => $cFile
];

// Nếu file tạm cần được xóa sau upload, truyền đường dẫn file vào mảng tempFiles
$response = curl_post_file($url, $postData, [$tempFile]);

if ($response !== false) {
    $result = json_decode($response, true);
    if (is_array($result) && isset($result['status']) && $result['status'] === 'success') {
        $imageUrl = $result['data']['url'] ?? '';
        echo "Upload thành công: " . $imageUrl;
    } else {
        echo "Upload thất bại.";
    }
} else {
    echo "Có lỗi xảy ra trong quá trình upload file.";
}

4. Example usage of curl_download_file

<?php
// Example usage of curl_download_file
$url = 'https://example.com/path/to/image.jpg';
$savePath = './downloads/image.jpg';

$result = curl_download_file($url, $savePath);

if ($result !== false) {
    echo "Tải file thành công, file được lưu tại: " . $result;
} else {
    echo "Có lỗi xảy ra khi tải file.";
}


*/