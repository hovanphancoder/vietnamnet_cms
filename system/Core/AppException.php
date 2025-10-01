<?php
namespace System\Core;
use Exception;
use System\Libraries\Logger;

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class AppException extends Exception {

    protected $statusCode;

    public function __construct($message, $code = 0, $previous = null, $statusCode = 500) {
        _cors();
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Handle exception, log and display error information.
     */
    public function handle() {
        // Log error
        Logger::error($this->getMessage(), $this->getFile(), $this->getLine());
        // Display exception information to user
        if ($this->statusCode == 404) {
            $this->render404();
        } else {
            // Display general exception information for other errors
            $this->renderError();
        }
    }

    /**
     * Display 404 error page from view.
     */
    private function render404() {
        http_response_code($this->statusCode);
        
        // Prepare error data for the view (similar to renderError)
        $errorData = [
            'statusCode' => $this->statusCode,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'debug' => !empty(config('app')['debug'])
        ];
        
        echo \System\Libraries\Render::html('Common/404', $errorData);
        exit(); // Stop further execution
    }

    /**
     * Display exception information as HTML to user.
     */
    private function renderError() {
        http_response_code($this->statusCode);
        
        // Prepare error data for the view
        $errorData = [
            'statusCode' => $this->statusCode,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'debug' => !empty(config('app')['debug'])
        ];
        
        echo \System\Libraries\Render::html('Common/errors', $errorData);
        exit(); // Stop further execution
    }
}
