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