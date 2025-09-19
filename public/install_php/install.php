<?php
/**
 * PHPFast CMS Installation Handler
 * Handles step-by-step installation process
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit();
}

$step = $data['step'] ?? '';
$installationData = $data['data'] ?? [];

// Validate step
$validSteps = ['init', '1', '2', '3', '4', '5', '6', 'complete'];
if (!in_array($step, $validSteps)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid installation step']);
    exit();
}

try {
    switch ($step) {
        case 'init':
            $result = handleInitStep($installationData);
            break;
            
        case '1':
            $result = handleDatabaseStep($installationData);
            break;
            
        case '2':
            $result = handleAdminUserStep($installationData);
            break;
            
        case '3':
            $result = handleWebsiteConfigStep($installationData);
            break;
            
        case '4':
            $result = handleEmailConfigStep($installationData);
            break;
            
        case '5':
            $result = handleFilesConfigStep($installationData);
            break;
            
        case '6':
            $result = handleRolesConfigStep($installationData);
            break;
            
        case 'complete':
            $result = handleCompleteStep($installationData);
            break;
            
        default:
            $result = ['status' => 'error', 'message' => 'Unknown step'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Installation failed: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}

/**
 * Initialize installation
 */
function handleInitStep($data) {
    // TODO: Initialize installation process
    // - Check system requirements
    // - Create necessary directories
    // - Set up basic configuration
    
    return [
        'status' => 'success',
        'message' => 'Installation initialized successfully',
        'data' => []
    ];
}

/**
 * Handle database configuration
 */
function handleDatabaseStep($data) {
    // TODO: Set up database
    // - Create .env file with database config
    // - Test database connection
    // - Create database tables
    
    return [
        'status' => 'success',
        'message' => 'Database configured successfully',
        'data' => []
    ];
}

/**
 * Handle admin user creation
 */
function handleAdminUserStep($data) {
    // TODO: Create admin user
    // - Hash password
    // - Insert user into database
    // - Set user permissions
    
    return [
        'status' => 'success',
        'message' => 'Admin user created successfully',
        'data' => []
    ];
}

/**
 * Handle website configuration
 */
function handleWebsiteConfigStep($data) {
    // TODO: Configure website settings
    // - Update website configuration
    // - Set timezone
    // - Configure security settings
    
    return [
        'status' => 'success',
        'message' => 'Website configured successfully',
        'data' => []
    ];
}

/**
 * Handle email configuration
 */
function handleEmailConfigStep($data) {
    // TODO: Configure email settings
    // - Update SMTP configuration
    // - Test email connection
    // - Save email settings
    
    return [
        'status' => 'success',
        'message' => 'Email configured successfully',
        'data' => []
    ];
}

/**
 * Handle files configuration
 */
function handleFilesConfigStep($data) {
    // TODO: Configure file settings
    // - Set allowed file types
    // - Set max file size
    // - Create upload directories
    
    return [
        'status' => 'success',
        'message' => 'Files configured successfully',
        'data' => []
    ];
}

/**
 * Handle roles configuration
 */
function handleRolesConfigStep($data) {
    // TODO: Configure user roles
    // - Create role definitions
    // - Set up permissions
    // - Configure default roles
    
    return [
        'status' => 'success',
        'message' => 'Roles configured successfully',
        'data' => []
    ];
}

/**
 * Complete installation
 */
function handleCompleteStep($data) {
    // TODO: Finalize installation
    // - Create installation lock file
    // - Set up final configurations
    // - Clean up temporary files
    
    return [
        'status' => 'success',
        'message' => 'Installation completed successfully',
        'data' => [
            'admin_url' => $data['website']['url'] ?? 'http://localhost/admin'
        ]
    ];
}
?>
