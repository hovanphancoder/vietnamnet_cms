<?php
namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Fasttoken
{
    private static $algorithm = 'HS256';
    private static $tokenExpiration = 157680000; // 5 years
    private static $appId = null;
    private static $appSecret = null;

    public static function init()
    {
        if (is_null(self::$appId) || is_null(self::$appSecret)) {
            $security = config('security');
            if (isset($security['app_id'])){
                self::$appId = $security['app_id'];
            }else{
                self::$appId = '';
            }
            if (isset($security['app_secret'])){
                self::$appSecret = $security['app_secret'];
            }else{
                self::$appSecret = '';
            }
        }
    }

    /**
     * Retrieve token from request headers
     * 
     * @return string|null The Bearer token if present, null otherwise
     */
    public static function headerToken()
    {
        $headers = getallheaders();
        if ($headers === false) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        } else {
            $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }
        if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }
        if (isset($_COOKIE['cmsff_token'])) {
            return $_COOKIE['cmsff_token'];
        }
        return null;
    }

    /**
     * Decode and validate a JWT token
     * 
     * @param string $token The JWT token to decode
     * @return array Decoded token data with success status
     */
    public static function decodeToken($token)
    {
        try {
            self::init();
            if (empty($token) || empty(self::$appSecret)) {
                return [
                    'success' => false,
                    'message' => 'Invalid token or secret'
                ];
            }

            $decoded = JWT::decode($token, new Key(self::$appSecret, self::$algorithm));
            return [
                'success' => true,
                'data' => (array)$decoded
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Token (Decode and Validate User Data)
     * 
     * @param string $token The JWT token to decode
     * @param string $secret The secret key for verification
     * @return array Decoded token data with success status
     */
    public static function checkToken($token)
    {
        $tokenDecode = self::decodeToken($token);
        if ($tokenDecode['success']) {
            $userData = $tokenDecode['data'] ?? null;
            if (!empty($userData) && isset($userData['user_id']) && isset($userData['exp']) && $userData['exp'] > time()){
                return $userData;
            }
        }
        return null;
    }

    /**
     * Generate a new JWT token
     * 
     * @param array $userData User data to encode in the token
     * @param string $secret Secret key for signing
     * @param string $issuer Name of the token issuer
     * @return string|null Generated JWT token or null on failure
     */
    public static function createToken($userData)
    {
        try {
            self::init();
            if (empty($userData) || empty(self::$appSecret)) {
                return null;
            }
            $issuedAt = time();
            $expire = $issuedAt + self::$tokenExpiration;

            $payload = [
                'iss' => self::$appId,
                'iat' => $issuedAt,
                'exp' => $expire,
                'nbf' => $issuedAt,
                'user_id' => $userData['id'],
                'role' => $userData['role'],
                'username' => $userData['username'],
                'email' => $userData['email'] ?? null,
                'password_at' => $userData['password_at'] ?? 0
            ];

            return JWT::encode($payload, self::$appSecret, self::$algorithm);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set custom token expiration time
     * 
     * @param int $seconds Expiration time in seconds
     */
    public static function setTokenExpiration($seconds)
    {
        self::$tokenExpiration = $seconds;
    }

    /**
     * Set custom JWT algorithm
     * 
     * @param string $algorithm JWT algorithm to use
     */
    public static function setAlgorithm($algorithm)
    {
        self::$algorithm = $algorithm;
    }
}