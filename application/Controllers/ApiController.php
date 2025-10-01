<?php
namespace App\Controllers;

use System\Core\BaseController;
use App\Libraries\Fasttoken;
use System\Libraries\Session;

class ApiController extends BaseController
{
    protected $usersModel;

    public function __construct()
    {
        parent::__construct();
        $this->usersModel = new \App\Models\UsersModel();
    }

    protected function _auth() {
        $access_token = Fasttoken::headerToken();
        //If Client/App send Bearer Token: (Bearer <token>) at Header: Focus validate by Token
        if (!empty($access_token)) {
            $token_data = Fasttoken::checkToken($access_token);
            if (empty($token_data) || !isset($token_data['password_at']) || !isset($token_data['user_id'])) {
                return null;
            }
            $user = $this->usersModel->getUserById($token_data['user_id']);
            if ($user && !empty($user['password_at']) && $user['password_at'] == $token_data['password_at']) {
                return $user;
            }
            return null;
        }
        //If Client/App not send Token: Focus validate by Session
        if(Session::has('user_id')) {
            $user_id = clean_input(Session::get('user_id'));
            return $this->usersModel->getUserById($user_id);
        }
        return null;
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
            'success' => true,
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
            'success' => false,
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
            'success' => true,
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
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
        return $response;
    }

    //  custom errorV2 add key "timestamp": 1759130698, call hàm geterror xong rồi thêm timestamp
    protected function error_v2($message = 'An error occurred', $errors = [], $statusCode = 400) {
        $response = $this->get_error($message, $errors, $statusCode);
        $response['timestamp'] = time();
        $this->json($response, $statusCode);
    }

    //  custom successV2 add key "timestamp": 1759130698, call hàm getsuccess xong rồi thêm timestamp
    protected function success_v2($data = [], $message = 'Success') {
        $response = $this->get_success($data, $message);
        $response['timestamp'] = time();
        $this->json($response);
    }
}