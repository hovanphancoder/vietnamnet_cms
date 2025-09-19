<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Libraries\Fastlang;
use App\Libraries\Fasttoken;
use App\Models\UsersModel;
use System\Libraries\Events;
use System\Libraries\Logger;
use System\Libraries\Render;
use System\Libraries\Session;
use System\Libraries\Validate;

class MeController extends BackendController
{
    private $usersModel;
    public function __construct()
    {
        parent::__construct();
        Fastlang::load('general', APP_LANG);
        Fastlang::load('profile', APP_LANG);
        $this->usersModel = new UsersModel();
    }
    public function index()
    {
        global $me_info;

        // Xử lý cập nhật profile
        if (HAS_POST('fullname')) {
            $csrf_token = S_POST('csrf_token') ?? '';

            if (!Session::csrf_verify($csrf_token)) {
                Session::flash('error', Fastlang::_e('csrf_failed'));
                redirect($_SERVER['REQUEST_URI']);
                exit();
            } else {
                $input = [
                    'fullname' => S_POST('fullname') ?? '',
                    'phone' => S_POST('phone') ?? '',
                    'birthday' => S_POST('birthday') ?? '',
                    'gender' => S_POST('gender') ?? '',
                    'about_me' => S_POST('about_me') ?? '',
                    'province' => S_POST('province') ?? '',
                    'district' => S_POST('district') ?? '',
                    'ward' => S_POST('ward') ?? '',
                    'address' => S_POST('address') ?? '',
                ];

                $rules = [
                    'fullname' => [
                        'rules' => [
                            Validate::length(3, 50)
                        ],
                        'messages' => [
                            Fastlang::_e('fullname_length', 3, 50)
                        ]
                    ],
                    'phone' => [
                        'rules' => [
                            Validate::length(null, 30)
                        ],
                        'messages' => [
                            Fastlang::_e('phone_length', 0, 30)
                        ]
                    ],
                    'address' => [
                        'rules' => [
                            Validate::length(null, 255)
                        ],
                        'messages' => [
                            Fastlang::_e('address_length', 0, 255)
                        ]
                    ]
                ];

                $validator = new Validate();
                $validation_result = $validator->check($input, $rules);

                if (!$validation_result) {
                    $errors = $validator->getErrors();
                    Session::flash('error', implode(', ', array_values($errors)[0]));
                    redirect($_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    $updated = $this->usersModel->updateUser($me_info['id'], $input);

                    if ($updated) {
                        // Redirect with success message
                        Session::flash('success', Fastlang::_e('profile_updated'));
                        redirect($_SERVER['REQUEST_URI']);
                        exit();
                    } else {
                        // Redirect with error message
                        Session::flash('error', Fastlang::_e('profile_update_failed'));
                        redirect($_SERVER['REQUEST_URI']);
                        exit();
                    }
                }
            }
        }

        // Xử lý đổi mật khẩu
        if (HAS_POST('current_password')) {
            $csrf_token = S_POST('csrf_token') ?? '';

            if (!Session::csrf_verify($csrf_token)) {
                Session::flash('error', Fastlang::_e('csrf_failed'));
                redirect($_SERVER['REQUEST_URI']);
                exit();
            } else {
                $current_password = S_POST('current_password') ?? '';
                $new_password = S_POST('new_password') ?? '';
                $confirm_password = S_POST('confirm_password') ?? '';

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    Session::flash('error', Fastlang::_e('password_fields_required'));
                    redirect($_SERVER['REQUEST_URI']);
                    exit();
                } elseif ($new_password !== $confirm_password) {
                    Session::flash('error', Fastlang::_e('password_confirm_mismatch'));
                    redirect($_SERVER['REQUEST_URI']);
                    exit();
                } elseif (strlen($new_password) < 6) {
                    Session::flash('error', Fastlang::_e('password_min_length'));
                    redirect($_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    // Verify current password
                    $password_verify_result = password_verify($current_password, $me_info['password']);

                    if ($password_verify_result) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $updated = $this->usersModel->updateUser($me_info['id'], ['password' => $hashed_password]);

                        if ($updated) {
                            // Redirect with success message
                            Session::flash('success', Fastlang::_e('password_updated'));
                        } else {
                            // Redirect with error message
                            Session::flash('error', Fastlang::_e('password_update_failed'));
                        }
                    } else {
                        Session::flash('error', Fastlang::_e('current_password_incorrect'));
                    }
                }
            }
        }

        // Don't read flash messages here - let the view handle them
        // This prevents the double-read issue where flash messages get consumed

        $this->data('title', Fastlang::_e('profile_settings'));
        $this->data('user_info', $me_info);
        $this->data('csrf_token', Session::csrf_token(600));

        $result = Render::html('Backend/profile_index', $this->data);
        echo $result;
    }
}
