<?php
namespace System\Core;
use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}
class BaseController {

    /**
     * Data to be passed to view
     * @var array
     */
    protected $data = [];

    public function __construct() {
        // Common initialization for all controllers
        // Example: load helpers, libraries, check session, etc.
    }

    /**
     * Data method: set or get data
     * - If 2 parameters passed: set data
     * - If 1 parameter passed: get data
     * 
     * @param string $key Data name
     * @param mixed|null $value Data value (if any)
     * @return mixed|null Returns data if only 1 parameter passed
     */
    public function data($key, $value = null) {
        if ($value !== null) {
            // Set data if 2 parameters
            $this->data[$key] = $value;
        } else {
            // Get data if only 1 parameter
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }
    }

    /**
     * Call render method from Render library to load view
     * 
     * @param string $layout Layout name
     * @param string $view View name
     * @param bool $isreturn Return the rendered view instead of echoing it
     */
    protected function render($layout, $view = null, $isreturn = false) {
        if (!empty($view)){
            $this->data['view'] = $view;
        }
        if ($isreturn){
            return Render::render($layout, $this->data);
        }else{
            echo Render::render($layout, $this->data);
        }
    }

    /**
     * Return data as JSON
     */
    protected function json($data = [], $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    /**
     * Echo JSON when request is successful
     */
    protected function success($data = [], $message = 'Success') {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
        $this->json($response);
    }

    /**
     * Echo JSON when there's an error
     */
    protected function error($message = 'An error occurred', $errors = [], $statusCode = 400) {
        $response = [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ];
        $this->json($response, $statusCode);
    }

    /**
     * Return JSON when request is successful
     */
    protected function get_success($data = [], $message = 'Success') {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
        return $response;
    }

    /**
     * Return JSON when there's an error
     */
    protected function get_error($message = 'An error occurred', $errors = [], $statusCode = 400) {
        $response = [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ];
        return $response;
    }
}