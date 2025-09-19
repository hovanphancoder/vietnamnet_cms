<?php
namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Fasttoken
{
    private static $algorithm = 'HS256';
    private static $tokenExpiration = 157680000; // 5 years

    /**
     * Retrieve token from request headers
     * 
     * @return string|null The Bearer token if present, null otherwise
     */
    public static function getToken()
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
     * @param string $secret The secret key for verification
     * @return array Decoded token data with success status
     */
    public static function decodeToken($token, $secret)
    {
        try {
            if (empty($token) || empty($secret)) {
                return [
                    'success' => false,
                    'message' => 'Invalid token or secret'
                ];
            }

            $decoded = JWT::decode($token, new Key($secret, self::$algorithm));
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
     * Generate a new JWT token
     * 
     * @param array $userData User data to encode in the token
     * @param string $secret Secret key for signing
     * @param string $issuer Name of the token issuer
     * @return string|null Generated JWT token or null on failure
     */
    public static function createToken($userData, $secret, $issuer = 'cmsff')
    {
        try {
            if (empty($userData) || empty($secret)) {
                return null;
            }

            $issuedAt = time();
            $expire = $issuedAt + self::$tokenExpiration;

            $payload = [
                'iss' => $issuer,
                'iat' => $issuedAt,
                'exp' => $expire,
                'nbf' => $issuedAt,
                'user_id' => $userData['id'],
                'role' => $userData['role'],
                'username' => $userData['username'],
                'email' => $userData['email'] ?? null
            ];

            return JWT::encode($payload, $secret, self::$algorithm);
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