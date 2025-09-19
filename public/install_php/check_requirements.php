<?php

// Function to check PHP extension
function checkExtension($extension) {
    return [
        'name' => $extension,
        'required' => 'Enabled',
        'current' => extension_loaded($extension) ? 'Enabled' : 'Disabled',
        'status' => extension_loaded($extension) ? 'success' : 'error'
    ];
}

// Function to check PHP version
function checkPhpVersion() {
    $current = PHP_VERSION;
    $required = '7.4.0';
    
    return [
        'name' => 'PHP Version',
        'required' => $required . '+',
        'current' => $current,
        'status' => version_compare($current, $required, '>=') ? 'success' : 'error'
    ];
}

// Function to check MySQL support
function checkMysqlSupport() {
    $extensions = ['mysqli', 'pdo_mysql'];
    $hasSupport = false;
    
    foreach ($extensions as $ext) {
        if (extension_loaded($ext)) {
            $hasSupport = true;
            break;
        }
    }
    
    return [
        'name' => 'MySQL Support',
        'required' => 'Enabled',
        'current' => $hasSupport ? 'Enabled' : 'Disabled',
        'status' => $hasSupport ? 'success' : 'error'
    ];
}

// Function to check memory limit
function checkMemoryLimit() {
    $current = ini_get('memory_limit');
    $required = '128M';
    
    // Convert to bytes for comparison
    $currentBytes = return_bytes($current);
    $requiredBytes = return_bytes($required);
    
    return [
        'name' => 'Memory Limit',
        'required' => $required . '+',
        'current' => $current,
        'status' => $currentBytes >= $requiredBytes ? 'success' : 'warning'
    ];
}

// Function to check max execution time
function checkMaxExecutionTime() {
    $current = ini_get('max_execution_time');
    $required = '30';
    
    // 0 means no limit
    if ($current == 0) {
        $current = 'No Limit';
        $status = 'success';
    } else {
        $status = $current >= $required ? 'success' : 'warning';
    }
    
    return [
        'name' => 'Max Execution Time',
        'required' => $required . 's+',
        'current' => $current,
        'status' => $status
    ];
}

// Function to check upload max filesize
function checkUploadMaxFilesize() {
    $current = ini_get('upload_max_filesize');
    $required = '10M';
    
    $currentBytes = return_bytes($current);
    $requiredBytes = return_bytes($required);
    
    return [
        'name' => 'Upload Max Filesize',
        'required' => $required . '+',
        'current' => $current,
        'status' => $currentBytes >= $requiredBytes ? 'success' : 'warning'
    ];
}

// Function to check post max size
function checkPostMaxSize() {
    $current = ini_get('post_max_size');
    $required = '10M';
    
    $currentBytes = return_bytes($current);
    $requiredBytes = return_bytes($required);
    
    // Post max size should be larger than upload max filesize
    $uploadMax = ini_get('upload_max_filesize');
    $uploadMaxBytes = return_bytes($uploadMax);
    
    if ($currentBytes < $uploadMaxBytes) {
        $status = 'error';
    } else {
        $status = $currentBytes >= $requiredBytes ? 'success' : 'warning';
    }
    
    return [
        'name' => 'Post Max Size',
        'required' => $required . '+',
        'current' => $current,
        'status' => $status
    ];
}

// Function to check security settings
function checkSecuritySettings() {
    $settings = [];
    
    // Check display_errors
    $displayErrors = ini_get('display_errors');
    $settings[] = [
        'name' => 'Display Errors',
        'required' => 'Off (Production)',
        'current' => $displayErrors ? 'On' : 'Off',
        'status' => !$displayErrors ? 'success' : 'warning',
        'description' => 'Should be disabled in production for security'
    ];
    
    // Check log_errors
    $logErrors = ini_get('log_errors');
    $settings[] = [
        'name' => 'Log Errors',
        'required' => 'On',
        'current' => $logErrors ? 'On' : 'Off',
        'status' => $logErrors ? 'success' : 'warning',
        'description' => 'Should be enabled to log errors for debugging'
    ];
    
    // Check expose_php
    $exposePhp = ini_get('expose_php');
    $settings[] = [
        'name' => 'Expose PHP',
        'required' => 'Off',
        'current' => $exposePhp ? 'On' : 'Off',
        'status' => !$exposePhp ? 'success' : 'warning',
        'description' => 'Should be disabled to hide PHP version information'
    ];
    
    // Check allow_url_fopen
    $allowUrlFopen = ini_get('allow_url_fopen');
    $settings[] = [
        'name' => 'Allow URL fopen',
        'required' => 'Off (Recommended)',
        'current' => $allowUrlFopen ? 'On' : 'Off',
        'status' => !$allowUrlFopen ? 'success' : 'warning',
        'description' => 'Disabling prevents remote file inclusion attacks'
    ];
    
    // Check allow_url_include
    $allowUrlInclude = ini_get('allow_url_include');
    $settings[] = [
        'name' => 'Allow URL Include',
        'required' => 'Off',
        'current' => $allowUrlInclude ? 'On' : 'Off',
        'status' => !$allowUrlInclude ? 'success' : 'error',
        'description' => 'Critical security setting - should always be disabled'
    ];
    
    return $settings;
}

// Function to check dangerous functions
function checkDangerousFunctions() {
    $dangerousFunctions = [
        'exec', 'system', 'passthru', 'shell_exec', 'proc_close', 'proc_open', 'popen',
        'pcntl_exec', 'pcntl_fork', 'pcntl_alarm', 'pcntl_waitpid', 'pcntl_wait',
        'pcntl_wifexited', 'pcntl_wifsignaled', 'pcntl_wifstopped', 'pcntl_wifcontinued',
        'pcntl_wexitstatus', 'pcntl_wtermsig', 'pcntl_wstopsig', 'pcntl_signal',
        'pcntl_signal_dispatch', 'pcntl_get_last_error', 'pcntl_strerror', 'pcntl_sigprocmask',
        'pcntl_sigwaitinfo', 'pcntl_sigtimedwait', 'pcntl_getpriority', 'pcntl_setpriority',
        'dl', 'putenv', 'apache_setenv', 'ini_alter', 'ini_restore', 'chroot', 'chgrp',
        'chown', 'openlog', 'syslog', 'readlink', 'symlink', 'popepassthru', 'posix_kill',
        'posix_mkfifo', 'posix_getpwuid', 'posix_setpgid', 'posix_setsid', 'posix_setuid',
        'posix_setgid', 'posix_seteuid', 'posix_setegid', 'posix_uname', 'imap_open', 'show_source'
    ];
    
    $enabledFunctions = [];
    
    foreach ($dangerousFunctions as $func) {
        if (function_exists($func)) {
            $enabledFunctions[] = $func;
        }
    }
    
    if (empty($enabledFunctions)) {
        return [
            'name' => 'Dangerous Functions',
            'required' => 'All Disabled',
            'current' => 'All Disabled',
            'status' => 'success',
            'description' => 'All dangerous functions are properly disabled'
        ];
    } else {
        return [
            'name' => 'Dangerous Functions',
            'required' => 'All Disabled',
            'current' => 'Enabled: ' . implode(', ', $enabledFunctions),
            'status' => 'error',
            'description' => '⚠️ WARNING: These dangerous functions are enabled and should be disabled for security!'
        ];
    }
}

// Helper function to convert memory size to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

// Collect requirements by category
$requiredRequirements = [
    checkPhpVersion(),
    checkExtension('ctype'),
    checkExtension('json'),
    checkExtension('fileinfo'),
    checkExtension('mbstring'),
    checkExtension('openssl'),
    checkExtension('tokenizer'),
    checkExtension('xml'),
    checkMysqlSupport()
];

$recommendedRequirements = [
    checkExtension('curl'),
    checkExtension('gd'),
    checkExtension('zip'),
    checkMemoryLimit(),
    checkMaxExecutionTime(),
    checkUploadMaxFilesize(),
    checkPostMaxSize()
];

$securityRequirements = array_merge(
    checkSecuritySettings(),
    [checkDangerousFunctions()]
);

// Calculate overall status for each category
function calculateCategoryStatus($requirements) {
    $total = count($requirements);
    $success = count(array_filter($requirements, function($req) {
        return $req['status'] === 'success';
    }));
    $warning = count(array_filter($requirements, function($req) {
        return $req['status'] === 'warning';
    }));
    $error = count(array_filter($requirements, function($req) {
        return $req['status'] === 'error';
    }));
    
    $overallStatus = 'success';
    if ($error > 0) {
        $overallStatus = 'error';
    } elseif ($warning > 0) {
        $overallStatus = 'warning';
    }
    
    return [
        'total' => $total,
        'success' => $success,
        'warning' => $warning,
        'error' => $error,
        'overallStatus' => $overallStatus
    ];
}

$requiredSummary = calculateCategoryStatus($requiredRequirements);
$recommendedSummary = calculateCategoryStatus($recommendedRequirements);
$securitySummary = calculateCategoryStatus($securityRequirements);

// Overall system status - allow proceeding even with failed requirements
$overallSystemStatus = 'success'; // Always allow proceeding

// Return JSON response
echo json_encode([
    'status' => 'success',
    'data' => [
        'categories' => [
            'required' => [
                'title' => 'Required Requirements',
                'description' => 'These requirements must be met for the installer to work',
                'requirements' => $requiredRequirements,
                'summary' => $requiredSummary
            ],
            'recommended' => [
                'title' => 'Recommended Requirements',
                'description' => 'These requirements are recommended for optimal performance',
                'requirements' => $recommendedRequirements,
                'summary' => $recommendedSummary
            ],
            'security' => [
                'title' => 'Security Recommendations',
                'description' => 'These security settings are recommended for production',
                'requirements' => $securityRequirements,
                'summary' => $securitySummary
            ]
        ],
        'overallStatus' => $overallSystemStatus,
        'canProceed' => true // Always allow proceeding
    ]
], JSON_PRETTY_PRINT);
?>
