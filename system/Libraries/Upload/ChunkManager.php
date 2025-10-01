<?php

namespace System\Libraries\Upload;

use App\Libraries\Fastlang;
use System\Libraries\Logger;

/**
 * ChunkManager - Handle advanced chunk upload features with resume capability
 * 
 * Main features:
 * - Detailed progress tracking for chunk uploads
 * - Resume capability for interrupted uploads
 * - Session management for chunk uploads
 * - Cleanup of expired chunk sessions
 * - Validation of chunk upload integrity
 * - Support for large file uploads
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class ChunkManager
{
    /**
     * Get upload progress for a chunk upload session.
     * 
     * Returns the number of chunks already uploaded to determine resume point.
     * Client can use this to continue from the next chunk number.
     * 
     * @param string $uploadId Unique upload session identifier (sanitized for security)
     * 
     * @return array Progress information with structure:
     *               - success: bool - Operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Progress data if successful
     *                 Structure: [
     *                   'uploaded_count' => int - Number of chunks already uploaded,
     *                   'total_chunks' => int - Total number of chunks expected
     *                 ]
     */
    public static function getUploadProgress($uploadId)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $uploadId = preg_replace('/[^A-Za-z0-9_-]/', '', $uploadId);
        $tempDir = self::getChunkDirectory($uploadId);
        if (!is_dir($tempDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('upload session not found'),
                'data' => [
                    'uploaded_count' => 0,
                    'total_chunks' => 0
                ]
            ];
        }

        // Get metadata
        $metadata = self::getUploadMetadata($uploadId);
        if (!$metadata) {
            return [
                'success' => false,
                'error' => Fastlang::_e('no metadata found for upload session'),
                'data' => [
                    'uploaded_count' => 0,
                    'total_chunks' => 0
                ]
            ];
        }

        $totalChunks = $metadata['total_chunks'] ?? 0;
        $uploadedChunks = [];
        $lastChunkTime = null;

        // Scan for uploaded chunks
        $chunkFiles = glob($tempDir . '/chunk_*');
        // die;
        foreach ($chunkFiles as $chunkFile) {
            if (preg_match('/chunk_(\d+)$/', $chunkFile, $matches)) {
                $chunkNumber = (int)$matches[1];
                $uploadedChunks[] = $chunkNumber;
                $fileTime = filemtime($chunkFile);
                if (!$lastChunkTime || $fileTime > $lastChunkTime) {
                    $lastChunkTime = $fileTime;
                }
            }
        }

        sort($uploadedChunks);
        $uploadedCount = count($uploadedChunks);

        // Find missing chunks
        $missingChunks = [];
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!in_array($i, $uploadedChunks)) {
                $missingChunks[] = $i;
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'uploaded_count' => $uploadedCount,
                'total_chunks' => $totalChunks
            ]
        ];
    }

    /**
     * Save upload metadata for a chunk upload session.
     * 
     * Stores metadata about the chunk upload session including:
     * - Original filename and total chunks
     * - Upload start time and session information
     * - File size and type information
     * 
     * @param string $uploadId Unique upload session identifier
     * @param array $metadata Upload metadata to save
     *                        Structure: ['filename' => string, 'total_chunks' => int, 
     *                                   'file_size' => int, 'mime_type' => string, ...]
     * 
     * @return array Save result with structure:
     *               - success: bool - Save success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Save data if successful
     *                 Structure: [
     *                   'metadata_file' => string - Path to metadata file,
     *                   'bytes_written' => int - Number of bytes written
     *                 ]
     */
    public static function saveUploadMetadata($uploadId, $metadata)
    {
        $tempDir = self::getChunkDirectory($uploadId);

        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('failed to create chunk directory'),
                'data' => null
            ];
        }

        $metadataFile = $tempDir . '/metadata.json';
        $metadata['upload_id'] = $uploadId;
        $metadata['created_at'] = date('Y-m-d H:i:s');
        $metadata['updated_at'] = date('Y-m-d H:i:s');

        $result = file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

        if ($result === false) {
            return [
                'success' => false,
                'error' => Fastlang::_e('failed to save upload metadata'),
                'data' => null
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'metadata_file' => $metadataFile,
                'bytes_written' => $result
            ]
        ];
    }

    /**
     * Validate chunk upload and determine resume capability.
     * 
     * Validates the current chunk upload and checks for missing chunks.
     * Determines if the upload can be resumed from a specific point.
     * Provides detailed validation results.
     * 
     * @param string $uploadId Unique upload session identifier
     * @param int $currentChunk Current chunk number being uploaded
     * @param int $totalChunks Total number of chunks expected
     * 
     * @return array Validation result with structure:
     *               - success: bool - Whether chunk upload is valid
     *               - error: string|null - Error message if validation failed
     *               - data: array|null - Validation data including:
     *                 - valid: bool - Whether chunk upload is valid
     *                 - can_resume: bool - Whether upload can be resumed
     *                 - missing_chunks: array - Array of missing chunk numbers
     *                 - resume_from: int|null - Chunk number to resume from
     *                 - uploaded_chunks: array - Array of uploaded chunk numbers
     *                 - total_chunks: int - Total number of chunks expected
     */
    public static function validateChunkUpload($uploadId, $chunk, $chunks)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $tempDir = self::getChunkDirectory($uploadId);

        if (!is_dir($tempDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('upload session not found'),
                'data' => [
                    'valid' => false,
                    'can_resume' => false,
                    'missing_chunks' => [],
                    'resume_from' => null
                ]
            ];
        }

        // Check if current chunk is valid
        if ($chunk < 0 || $chunk >= $chunks) {
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid chunk number'),
                'data' => [
                    'valid' => false,
                    'can_resume' => false,
                    'missing_chunks' => [],
                    'resume_from' => null
                ]
            ];
        }

        // Get existing chunks
        $existingChunks = [];
        $chunkFiles = glob($tempDir . '/chunk_*');
        foreach ($chunkFiles as $chunkFile) {
            if (preg_match('/chunk_(\d+)$/', $chunkFile, $matches)) {
                $existingChunks[] = (int)$matches[1];
            }
        }

        sort($existingChunks);

        // Find missing chunks
        $missingChunks = [];
        for ($i = 0; $i < $chunks; $i++) {
            if (!in_array($i, $existingChunks)) {
                $missingChunks[] = $i;
            }
        }

        // Determine resume capability
        $canResume = !empty($existingChunks);
        $resumeFrom = null;

        if ($canResume) {
            // Find the highest consecutive chunk number
            $resumeFrom = max($existingChunks);
            if (in_array($resumeFrom + 1, $missingChunks)) {
                $resumeFrom++;
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'valid' => true,
                'can_resume' => $canResume,
                'missing_chunks' => $missingChunks,
                'resume_from' => $resumeFrom,
                'uploaded_chunks' => $existingChunks,
                'total_chunks' => $chunks
            ]
        ];
    }

    /**
     * Resume upload from a specific chunk number.
     * 
     * Initiates resume process from the specified chunk number.
     * Validates that all previous chunks exist and are valid.
     * Provides resume information for client-side handling.
     * 
     * @param string $uploadId Unique upload session identifier
     * @param int $startChunk Chunk number to resume from
     * 
     * @return array Resume result with structure:
     *               - success: bool - Whether resume was initiated successfully
     *               - error: string|null - Error message if resume failed
     *               - data: array|null - Resume data if successful
     *                 Structure: [
     *                   'resume_from' => int - Chunk number to resume from,
     *                   'missing_chunks' => array - Array of missing chunk numbers
     *                 ]
     */
    public static function resumeFromChunk($uploadId, $startChunk)
    {
        $tempDir = self::getChunkDirectory($uploadId);

        if (!is_dir($tempDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('upload session not found'),
                'data' => [
                    'resume_from' => null,
                    'missing_chunks' => []
                ]
            ];
        }

        // Validate start chunk
        if ($startChunk < 0) {
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid start chunk number'),
                'data' => [
                    'resume_from' => null,
                    'missing_chunks' => []
                ]
            ];
        }

        // Check if start chunk exists
        $startChunkFile = $tempDir . '/chunk_' . $startChunk;
        if (!file_exists($startChunkFile)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('start chunk does not exist'),
                'data' => [
                    'resume_from' => null,
                    'missing_chunks' => []
                ]
            ];
        }

        // Get missing chunks from start point
        $missingChunks = [];
        $chunkFiles = glob($tempDir . '/chunk_*');
        $existingChunks = [];

        foreach ($chunkFiles as $chunkFile) {
            if (preg_match('/chunk_(\d+)$/', $chunkFile, $matches)) {
                $existingChunks[] = (int)$matches[1];
            }
        }

        // Find missing chunks after start chunk
        $metadata = self::getUploadMetadata($uploadId);
        $totalChunks = $metadata['total_chunks'] ?? 0;

        for ($i = $startChunk + 1; $i < $totalChunks; $i++) {
            if (!in_array($i, $existingChunks)) {
                $missingChunks[] = $i;
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'resume_from' => $startChunk,
                'missing_chunks' => $missingChunks
            ]
        ];
    }

    /**
     * Clean up expired chunk upload sessions.
     * 
     * Removes chunk upload sessions that are older than the specified age.
     * Helps prevent disk space issues from abandoned uploads.
     * 
     * @param int $maxAge Maximum age in hours before cleanup (default: 24)
     * 
     * @return array Cleanup result with structure:
     *               - success: bool - Cleanup success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Cleanup data if successful
     *                 Structure: [
     *                   'cleaned_sessions' => int - Number of sessions cleaned up,
     *                   'errors' => array - Array of error messages
     *                 ]
     */
    public static function cleanupExpiredSessions($maxAge = 24)
    {
        $baseDir = self::getBaseChunkDirectory();
        if (!is_dir($baseDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('base chunk directory not found'),
                'data' => [
                    'cleaned_sessions' => 0,
                    'errors' => [Fastlang::_e('base chunk directory not found')]
                ]
            ];
        }

        $cleanedSessions = 0;
        $errors = [];
        $cutoffTime = time() - ($maxAge * 3600);

        $sessionDirs = glob($baseDir . '/*', GLOB_ONLYDIR);
        foreach ($sessionDirs as $sessionDir) {
            $sessionId = basename($sessionDir);

            // Check if session is expired
            if (is_dir($sessionDir)) {
                $dirTime = filemtime($sessionDir);
                if ($dirTime < $cutoffTime) {
                    $deleteResult = self::deleteSession($sessionId);
                    if ($deleteResult['success']) {
                        $cleanedSessions++;
                    } else {
                        $errors[] = Fastlang::_e('failed to clean up session', $sessionId);
                    }
                }
            }
        }

        return [
            'success' => empty($errors),
            'error' => empty($errors) ? null : implode('; ', $errors),
            'data' => [
                'cleaned_sessions' => $cleanedSessions,
                'errors' => $errors
            ]
        ];
    }

    /**
     * Delete a specific chunk upload session.
     * 
     * Removes all files and metadata for a specific upload session.
     * Cleans up temporary directory and all associated files.
     * 
     * @param string $uploadId Unique upload session identifier
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Delete data if successful
     *                 Structure: [
     *                   'session_id' => string - Session identifier,
     *                   'deleted' => bool - Whether session was deleted,
     *                   'deleted_files' => int - Number of files deleted,
     *                   'errors' => array - Array of error messages
     *                 ]
     */
    public static function deleteSession($uploadId)
    {
        $tempDir = self::getChunkDirectory($uploadId);

        if (!is_dir($tempDir)) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'session_id' => $uploadId,
                    'deleted' => false,
                    'reason' => Fastlang::_e('session directory not found')
                ]
            ];
        }

        // Remove all files in the session directory
        $files = glob($tempDir . '/*');
        $deletedFiles = 0;
        $errors = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedFiles++;
                } else {
                    $errors[] = Fastlang::_e('failed to delete file', $file);
                }
            }
        }

        // Remove the directory itself
        $dirDeleted = rmdir($tempDir);

        return [
            'success' => $dirDeleted && empty($errors),
            'error' => empty($errors) ? null : implode('; ', $errors),
            'data' => [
                'session_id' => $uploadId,
                'deleted' => $dirDeleted,
                'deleted_files' => $deletedFiles,
                'errors' => $errors
            ]
        ];
    }

    /**
     * Get all active chunk upload sessions.
     * 
     * Lists all currently active chunk upload sessions with their metadata.
     * Provides information about upload progress and session details.
     * 
     * @return array Array of active sessions with structure:
     *               - upload_id: string - Session identifier
     *               - filename: string - Original filename
     *               - total_chunks: int - Total chunks expected
     *               - uploaded_chunks: int - Number of uploaded chunks
     *               - percentage: float - Upload completion percentage
     *               - created_at: string - Session creation timestamp
     *               - last_activity: string - Last activity timestamp
     */
    public static function getActiveSessions()
    {
        $baseDir = self::getBaseChunkDirectory();
        if (!is_dir($baseDir)) {
            return [];
        }

        $sessions = [];
        $sessionDirs = glob($baseDir . '/*', GLOB_ONLYDIR);

        foreach ($sessionDirs as $sessionDir) {
            $uploadId = basename($sessionDir);
            $metadata = self::getUploadMetadata($uploadId);

            if ($metadata) {
                $progress = self::getUploadProgress($uploadId);

                $sessions[] = [
                    'upload_id' => $uploadId,
                    'filename' => $metadata['filename'] ?? 'Unknown',
                    'total_chunks' => $progress['total_chunks'],
                    'uploaded_chunks' => $progress['uploaded_chunks'],
                    'percentage' => $progress['percentage'],
                    'status' => $progress['status'],
                    'created_at' => $metadata['created_at'] ?? 'Unknown',
                    'last_activity' => $progress['last_chunk_time'] ?? 'Unknown'
                ];
            }
        }

        return $sessions;
    }

    /**
     * Get the base directory for chunk uploads.
     * 
     * Returns the base directory where all chunk upload sessions are stored.
     * Creates the directory if it doesn't exist.
     * 
     * @return string Path to the base chunk directory
     */
    private static function getBaseChunkDirectory()
    {
        $baseDir = PATH_WRITE . 'uploads/temp/chunks';

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        return $baseDir;
    }

    /**
     * Get the directory for a specific upload session.
     * 
     * Returns the directory path for a specific chunk upload session.
     * 
     * @param string $uploadId Unique upload session identifier
     * 
     * @return string Path to the session directory
     */
    private static function getChunkDirectory($uploadId)
    {
        return self::getBaseChunkDirectory() . '/' . $uploadId;
    }

    /**
     * Get upload metadata for a session.
     * 
     * Reads and parses the metadata file for a chunk upload session.
     * 
     * @param string $uploadId Unique upload session identifier
     * 
     * @return array|null Metadata array or null if not found
     */
    private static function getUploadMetadata($uploadId)
    {
        $metadataFile = self::getChunkDirectory($uploadId) . '/metadata.json';

        if (!file_exists($metadataFile)) {
            return null;
        }

        $content = file_get_contents($metadataFile);
        if ($content === false) {
            return null;
        }

        $metadata = json_decode($content, true);
        return is_array($metadata) ? $metadata : null;
    }
}
