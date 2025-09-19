<?php
namespace App\Controllers\Api\V1;
    

use App\Controllers\ApiController;
use App\Models\UsersModel;
use System\Core\AppException;
use App\Libraries\Fastmail;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Validate;
use System\Libraries\Security;
use System\Libraries\Session;
use System\Drivers\Cache\UriCache;
use DateTime;
use App\Libraries\Fasttoken;

class AuthController extends ApiController
{
    protected $usersModel;

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        Flang::load('auth', APP_LANG);
        $this->usersModel = new UsersModel();
    }

    // Lấy danh sách tất cả người dùng
    // public function me()
    // {
    //     try {
    //         if (Session::has('user_id')) {
    //             $user_id = clean_input(Session::get('user_id'));
    //             $me = $this->usersModel->getUserById($user_id);
    //             if (empty($me)) {
    //                 return $this->error(Flang::_e('user_notfound'), [], 404);
    //             }
    //             unset($me['password']);
    //             unset($me['location']);
    //             return $this->success(['me'=>$me], Flang::_e('login_success'));
    //         }else{
    //             $access_token = Fasttoken::getToken();
    //             if ($access_token) {
    //                 $config_security = config('security');
    //                 //$config_secret = !empty($config_secret) && !empty($config_secret['app_secret']) ? $config_secret['app_secret'] : null;
    //                 $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
    //                 if (!$me_data['success']) {
    //                     return $this->error(Flang::_e('auth_token_invalid'), [$me_data['message']], 401);
    //                 }
    //                 $user_id = $me_data['data']['user_id'] ?? null;
    //                 if (empty($user_id)) {
    //                     return $this->error(Flang::_e('token_invalid'), [], 401);
    //                 }
    //                 $me = $this->usersModel->getUserById($user_id);
    //                 if (empty($me)) {
    //                     return $this->error(Flang::_e('user_notfound'), [], 404);
    //                 }
    //                 unset($me['password']);
    //                 return $this->success(['me'=>$me], Flang::_e('login_success'));
    //             }
    //             $this->error(Flang::_e('auth_token_invalid'), [], 403);
    //         }
    //     } catch (AppException $e) {
    //         $this->error($e->getMessage(), [], 500);
    //     }
    // }

    public function login()
    {
        try {
            // Kiểm tra nếu có yêu cầu POST với dữ liệu 'username' và 'password'
            if (HAS_POST('username') && HAS_POST('password')) {
                $input = [
                    'username' => S_POST('username'),
                    'password' => S_POST('password')
                ];

                $rules = [
                    'username' => [
                        'rules' => [Validate::alnum("@._"), Validate::length(6, 150)],
                        'messages' => [Flang::_e('loginname_invalid'), Flang::_e('username_length', 6, 150)]
                    ],
                    'password' => [
                        'rules' => [Validate::length(6, null)],
                        'messages' => [Flang::_e('password_length', 6)]
                    ]
                ];

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    // Trả về lỗi nếu validate thất bại
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('login_failed', $input['username']), $errors, 400);
                }

                // Kiểm tra thông tin đăng nhập
                if (!filter_var($input['username'], FILTER_VALIDATE_EMAIL)) {
                    $me = $this->usersModel->getUserByUsername($input['username']);
                } else {
                    $me = $this->usersModel->getUserByEmail($input['username']);
                }

                if ($me && Security::verifyPassword($input['password'], $me['password'])) {
                    if ($me['status'] !== 'active') {
                        return $this->error(Flang::_e('users_noactive', $input['username']), [], 403);
                    }
                    
                    // Set thông tin đăng nhập vào session
                    Session::set('user_id', $me['id']);
                    Session::set('role', $me['role']);
                    Session::set('permissions', json_decode($me['permissions'], true));
                    // Tái tạo session ID để tránh session fixation
                    Session::regenerate();

                    // Tạo JWT token
                    $config_security = config('security');
                    $me_data = [
                        'id' => $me['id'],
                        'role' => $me['role'],
                        'username' => $me['username'],
                        'email' => $me['email']
                    ];
                    $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                    return $this->success([
                        'me' => [
                            'id' => $me['id'],
                            'username' => $me['username'],
                            'email' => $me['email'],
                            'fullname' => $me['fullname'],
                            'avatar' => $me['avatar'],
                            'role' => $me['role'],
                            'status' => $me['status'],
                            'created_at' => $me['created_at'],
                            'updated_at' => $me['updated_at'],
                            'phone' => $me['phone'],
                            'telegram' => $me['telegram'],
                            'whatsapp' => $me['whatsapp'],
                            'skype' => $me['skype'],
                            'birthday' => $me['birthday'],
                            'gender' => $me['gender'],
                            'about_me' => $me['about_me'],
                            'display' => $me['display'],
                            'coin' => $me['coin'],
                            'package_name' => $me['package_name'],
                            'package_exp' => $me['package_exp'],
                            'personal' => is_string($me['personal']) ? json_decode($me['personal'], true) : $me['personal'],
                            'online' => $me['online'],
                            'rel_status' => Flang::_e($me['rel_status'] ?? '')
                        ],
                        'access_token' => $access_token
                    ], Flang::_e('login_success'));
                } else {
                    return $this->error(Flang::_e('login_failed', $input['username']), [], 401);
                }
            }

            return $this->error(Flang::_e('username_invalid'), [], 400);
        } catch (AppException $e) {
            return $this->error(    $e->getMessage(), [], 500);
        }
    }

    public function logout()
    {
        $user = $this->_authentication();
        try {
            // Xóa session nếu tồn tại
            if (Session::has('user_id')) {
                Session::del('user_id');
                Session::del('role');
                Session::del('permissions');
            }
            if(HAS_POST('device_id')) {
                // decode optionals
                $optional = json_decode($user['optional'], true);
                // remove fcm token is array 
                $device_id = S_POST('device_id') ?? '';
                if(!empty($device_id)) {

                    // remove fcm token in key device_id
                    if(isset($optional['fcm_token'][$device_id])) {
                        unset($optional['fcm_token'][$device_id]);
                    }

                }

                // update optional in user datase 
                $this->usersModel->updateUser($user['id'], ['optional' => json_encode($optional)]);
                return $this->success(['user_id' => $user['id'],'device_id' => $device_id ], Flang::_e('logout_success'));
            }

            return $this->success([], Flang::_e('logout_success'));
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    public function csrf_create(){
        return $this->success(['csrf_token' => Session::csrf_token(600)], 'CSRF Token Created');
    }

    public function register()
    {
        load_helpers(['backend']);
        try {
            // Kiểm tra nếu có yêu cầu POST với các trường cần thiết
            if (HAS_POST('username')) {
                // $csrf_token = S_POST('csrf_token') ?? '';
                // if (!Session::csrf_verify($csrf_token)) {
                //     return $this->error(Flang::_e('csrf_failed'), [], 400);
                // }   

                $input = [
                    'username' => S_POST('username'),
                    'fullname' => S_POST('fullname'),
                    'email' => S_POST('email'),
                    'password' => S_POST('password'),
                    'password_repeat' => S_POST('password_repeat'),
                    // 'phone' => S_POST('phone'),
                ];
        
                // Quy tắc kiểm tra
                $rules = [
                    'username' => [
                        'rules' => [Validate::alnum("@._"), Validate::length(6, 30)],
                        'messages' => [Flang::_e('username_invalid'), Flang::_e('username_length', 6, 30)]
                    ],
                    'fullname' => [
                        'rules' => [ Validate::regex('/^[\p{L}\p{M}\s]+$/u') , Validate::length(6, 60)],
                        'messages' => [Flang::_e('fullname_invalid'), Flang::_e('fullname_length', 6, 60)]
                    ],
                    'email' => [
                        'rules' => [Validate::email(), Validate::length(6, 150)],
                        'messages' => [Flang::_e('email_invalid'), Flang::_e('email_length', 6, 150)]
                    ],
                    // 'phone' => [
                    //     'rules' => [Validate::phone(), Validate::length(6, 30)],
                    //     'messages' => [Flang::_e('phone_invalid'), Flang::_e('phone_length', 6, 30)]
                    // ],
                    'password' => [
                        'rules' => [Validate::length(6, 60)],
                        'messages' => [Flang::_e('password_length', 6, 60)]
                    ],
                    'password_repeat' => [
                        'rules' => [
                            Validate::equals($input['password'])
                        ],
                        'messages' => [
                            Flang::_e('password_repeat_invalid')
                        ]
                    ],
                ];

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('register_failed'), $errors, 400);
                }

                // Kiểm tra xem username hoặc email đã tồn tại hay chưa
                if ($this->usersModel->getUserByUsername($input['username'])) {
                    $errors = [
                        'username' => 'The username address is already registered in the system.'
                    ];
                    return $this->error(Flang::_e('Username_already_exists'), $errors, 400);
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors = [
                        'email' => 'The email address is already registered in the system.'
                    ];
                    return $this->error(Flang::_e('Email_already_exists'), $errors, 400);
                }

                // Hash mật khẩu và thêm dữ liệu người dùng
                $input['password'] = Security::hashPassword($input['password']);
                $input['avatar'] = '';
                $input['role'] = 'member';
                $input['permissions'] = json_encode(config('member', 'Roles')['permissions'] ?? []);
                $input['status'] = 'active';
                $input['created_at'] = date('Y-m-d H:i:s');
                $input['updated_at'] = date('Y-m-d H:i:s');
                unset($input['password_repeat']);
                // Tạo mã kích hoạt
                $activationNo = strtoupper(random_string(6));
                $activationCode = strtolower(random_string(20));
                $optionalData = [
                    'activation_no' => $activationNo,
                    'activation_code' => $activationCode,
                    'activation_expires' => time() + 86400,
                ];
                $input['optional'] = json_encode($optionalData);

                $user_id = $this->usersModel->addUser($input);
                
                if ($user_id) {
                    // Gửi email kích hoạt
                    $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode . '/');
                    // $mailer = new Fastmail();
                    // $mailer->send($input['email'], Flang::_e('active_account'), 'activation', [
                    //     'username' => $input['username'],
                    //     'activation_link' => $activationLink,
                    //     'activation_no' => $activationNo
                    // ]);
                    $input['id'] = $user_id;

                    $config_security = config('security');
                    $me_data = [
                        'id' => $input['id'],
                        'role' => $input['role'],
                        'username' => $input['username'],
                        'email' => $input['email']
                    ];
                    $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                    $input['personal'] = $input['personal'] ?? [];
                    $data = [
                        'me' => [
                            'id' => $input['id'],
                            'username' => $input['username'] ?? '',
                            'email' => $input['email'] ?? '',
                            'fullname' => $input['fullname']?? '',
                            'avatar' => $input['avatar'] ?? '',
                            'role' => $input['role'] ?? '',
                            'status' => $input['status'] ?? 'inactive',
                            'created_at' => $input['created_at'] ?? '',
                            'updated_at' => $input['updated_at'] ?? '',
                            'phone' => $input['phone'] ?? '',
                            'telegram' => $input['telegram'] ?? '',
                            'whatsapp' => $input['whatsapp'] ?? '',
                            'skype' => $input['skype'] ?? '',
                            'birthday' => $input['birthday'] ?? '',
                            'gender' => $input['gender'] ?? '',
                            'about_me' => $input['about_me'] ?? '',
                            'display' => $input['display'] ?? 0,
                            'coin' => $input['coin'] ?? 0,
                            'package_name' => $input['package_name'] ?? 'membership',
                            'package_exp' => $input['package_exp'] ?? '',
                            'personal' => is_string($input['personal']) ? json_decode($input['personal'], true) : $input['personal'],
                            'online' => $input['online'] ?? 1,
                            'rel_status' => Flang::_e($me['rel_status'] ?? '')
                        ],
                        'access_token'=> $access_token
                    ];
                    return $this->success($data, Flang::_e('register_success'));
                } else {
                    return $this->error(Flang::_e('register_error'), [], 500);
                }
            }  else {
                $csrf_token = Session::csrf_token(600);
                return $this->success(['csrf_token' => $csrf_token], Flang::_e('register_success'));
            }

            
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }



    public function google() {
        // post add in app
        // {
        //     "scopes": [
        //         "https://www.googleapis.com/auth/userinfo.profile",
        //         "https://www.googleapis.com/auth/userinfo.email",
        //         "openid",
        //         "profile",
        //         "email"
        //     ],
        //     "serverAuthCode": "4/0AQSTgQFujPf1a4KrZvfQfXJROe36DeRc3BvhxvPSk793TZznfR_QwrqqNN8cORLZwCAg6w",
        //     "idToken": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjkxNGZiOWIwODcxODBiYzAzMDMyODQ1MDBjNWY1NDBjNmQ0ZjVlMmYiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhenAiOiI4NjIzMjYwNTYxMTYtOWFudTQyaTFzdnMwMTRxb2hhZWwxOW85Y29ramt0cm4uYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJhdWQiOiI4NjIzMjYwNTYxMTYtdjdxcXBlMWZsbnJwbjg1bHJzdnJtMzNrNGhuNXYwanUuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJzdWIiOiIxMDAzOTk0MDI3NDkzODU3NjQ3OTMiLCJlbWFpbCI6ImhpZXVudm1vYkBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwibmFtZSI6Imhp4bq_dSBuZ3V54buFbiIsInBpY3R1cmUiOiJodHRwczovL2xoMy5nb29nbGV1c2VyY29udGVudC5jb20vYS9BQ2c4b2NKSzZVY181X29hTUo3SUtDVmFCaEk2UzhaUjV4cllSZUhZcllFX0hGTG8tZUUzT0E9czk2LWMiLCJnaXZlbl9uYW1lIjoiaGnhur91IiwiZmFtaWx5X25hbWUiOiJuZ3V54buFbiIsImlhdCI6MTc0MTc1MzM4MiwiZXhwIjoxNzQxNzU2OTgyfQ.MM-iywgW9VpbxEVP0b88kVdblLYFNYhelDg7r3XQaWWCvGiWDUGYJJ99d7AkxicuxcQ-QAK4-vY-EURbSTW2Wxf-U799LL-A_Eg9I2w9XV3oFLgbj3Rcv66oY8F0Sjjx_LITLsBOX-jz1PFmBkGZ8NQN6xYq4zUocdMC_Ks7zdMjnGcyKkjkXcFfANwWHDMqnqIH_RmrfZ2t9KYyQJlvFxBHel1PNVVhiyUVmHIRkTwo7c5kxKQR0Q-b15htfvQ7VXfiS5MxTfkJt63TcdMCq_f7lyc8U1Joq6GcZDePTrL0AXZHSfdgCUMklMLwF7nsHbHyVy2i3ysGR_2vcP6Rmw",
            // "user": {
            //     "photo": "https://lh3.googleusercontent.com/a/ACg8ocJK6Uc_5_oaMJ7IKCVaBhI6S8ZR5xrYReHYrYE_HFLo-eE3OA=s96-c",
            //     "givenName": "hiếu",
            //     "familyName": "nguyễn",
            //     "email": "hieunvmob@gmail.com",
            //     "name": "hiếu nguyễn",
            //     "id": "100399402749385764793"
            // }
        // }
        // if(!HAS_POST('idToken')) {
        //     return $this->error(Flang::_e('google_login_failed'), [], 400);
        // }
        $data = [
            'idToken' => S_POST('idToken') ?? '',
            'user' => $_POST['user'] ?? ''
        ];
        if(empty($data['idToken']) || empty($data['user'])) {
            return $this->error(Flang::_e('google_login_failed'), [], 400);
        }
        $config_security = config('security');
        
        $data['user'] = json_decode($data['user'], true);
        // check email exist
        $me = $this->usersModel->getUserByEmail($data['user']['email']);
        if($me) {
            // check google token
            $optional = json_decode($me['optional'], true);
            $google_token = $optional['id_google'] ?? '';
            if($google_token != $data['user']['id']) {
                $this->error(Flang::_e('google_login_failed'), [], 400);
            } else {
                $me_data = [
                    'id' => $me['id'],
                    'role' => $me['role'],
                    'username' => $me['username'],
                    'email' => $me['email']
                ];

                $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);

                return $this->success([
                    'me' => [
                        'id' => $me['id'],
                        'username' => $me['username'],
                        'email' => $me['email'],
                        'fullname' => $me['fullname'],
                        'avatar' => $me['avatar'],
                        'role' => $me['role'],
                        'status' => $me['status'],
                        'created_at' => $me['created_at'],
                        'updated_at' => $me['updated_at'],
                        'phone' => $me['phone'],
                        'telegram' => $me['telegram'],
                        'whatsapp' => $me['whatsapp'],
                        'skype' => $me['skype'],
                        'birthday' => $me['birthday'],
                        'gender' => $me['gender'],
                        'about_me' => $me['about_me'],
                        'display' => $me['display'],
                        'coin' => $me['coin'],
                        'package_name' => $me['package_name'],
                        'package_exp' => $me['package_exp'],
                        'personal' => is_string($me['personal']) ? json_decode($me['personal'], true) : $me['personal'],
                        'online' => $me['online'],
                        'rel_status' => Flang::_e($me['rel_status'] ?? '')
                    ],
                    'access_token' => $access_token
                ], Flang::_e('login_success'));

            }
        } else {
            $input['fullname'] = $data['user']['name'];
            $input['username'] = $data['user']['email'];
            $input['email'] = $data['user']['email'];
            $input['password'] = 'null';
            $input['avatar'] = $data['user']['photo'];
            $input['role'] = 'member';
            $input['permissions'] = json_encode(config('member', 'Roles')['permissions'] ?? []);
            $input['status'] = 'active';
            $input['coin'] =   0;
            $input['package_name'] = 'membership';
            $input['created_at'] = date('Y-m-d H:i:s');
            $input['updated_at'] = date('Y-m-d H:i:s');
            $optionNal = [
                'google_token' => $data['idToken'],
                'id_google' => $data['user']['id']
            ]; 
            $input['optional'] = json_encode($optionNal);
            $user_id = $this->usersModel->addUser($input);
    
            if ($user_id) {
                $me_data = [
                    'id' => $user_id,
                    'role' => $input['role'],
                    'username' => $input['username'],
                    'email' => $input['email']
                ];
                $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                return $this->success([
                    'me' => [
                        'id' => $user_id,
                        'username' => $input['username'],
                        'email' => $input['email'],
                        'fullname' => $input['fullname'],
                        'avatar' => $input['avatar'],
                        'role' => $input['role'],
                        'status' => $input['status'],
                        'created_at' => $input['created_at'],
                        'updated_at' => $input['updated_at']
                    ],
                    'access_token' => $access_token
                ], Flang::_e('login_success'));
            } else {
                return $this->error(Flang::_e('register_error'), [], 500);
            }
        }
        exit;        
    }

    public function facebook() {
        exit;
    }
    // public function profile() {
        
    //     try {

    //         if(Session::has('user_id')) {
    //             $user_id = clean_input(Session::get('user_id'));
    //             $avatarPath = '';
    //             $me = $this->usersModel->getUserById($user_id);
    //             if (empty($me)) {
    //                 return $this->error(Flang::_e('user_notfound'), [], 404);
    //             }
    //             $rules = [];
    //             $input = [];
    //             if (HAS_POST('username')) {
    //                 $input['username'] = S_POST('username') ?? '';
    //                 $rules['username'] = [
    //                     'rules' => [
    //                         Validate::alnum('_'),
    //                         Validate::length(3, 30)
    //                     ],
    //                     'messages' => [
    //                         Flang::_e('username_invalid'),
    //                         Flang::_e('username_length', 3, 30)
    //                     ]
    //                 ];
    //             }

    //             if (HAS_POST('phone')) {
    //                 $input['phone'] = S_POST('phone') ?? '';
    //                 $rules['phone'] = [
    //                     'rules' => [
    //                         Validate::phone(),
    //                         Validate::length(10, 30)
    //                     ],
    //                     'messages' => [
    //                         Flang::_e('phone_invalid'),
    //                         Flang::_e('phone_length', 10, 30)
    //                     ]
    //                 ];
    //             }
                
    //             if (HAS_POST('about_me')) {
    //                 $input['about_me'] = S_POST('about_me') ?? '';
    //                 $rules['about_me'] = [
    //                     'rules' => [
    //                         Validate::length(10, 300)
    //                     ],
    //                     'messages' => [
    //                         Flang::_e('about_me_length', 10, 300)
    //                     ]
    //                 ];
    //             }
    //             if (HAS_POST('birthday')) {
    //                 $input['birthday'] = S_POST('birthday') ?? '';
    //                 $rules['birthday'] = [
    //                     'rules' => [  
    //                         Validate::maxAge(100),
    //                         Validate::minAge(15),
    //                     ],
    //                     'messages' => [
    //                         Flang::_e('birthday max 100'), 
    //                         Flang::_e('birthday min 15'), 
    //                     ]
    //                 ];
    //             }

    //             if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    //                 $fileTmpPath = $_FILES['avatar']['tmp_name'];
    //                 $fileName = $_FILES['avatar']['name'];
    //                 $fileNameCmps = explode(".", $fileName);
    //                 $fileExtension = strtolower(end($fileNameCmps));
    //                 $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    //                 if (in_array($fileExtension, $allowedfileExtensions)) {
    //                     $newFileName = pathinfo('avatar', PATHINFO_FILENAME) . '.webp';
    //                     $uploadBaseDir = realpath(__DIR__ . '../../../../../public/uploads/') . '/';
    //                     if ($uploadBaseDir === false) {
    //                         $this->error('Đường dẫn thư mục tải lên không hợp lệ.', [], 500);
    //                         exit;
    //                     }
                        
    //                     $uploadFileDir = $uploadBaseDir . ceil($user_id / 1000) . '/' . $user_id . '/';
                        
    //                     if (!is_dir($uploadFileDir) && !mkdir($uploadFileDir, 0755, true)) {
    //                         $this->error('Không thể tạo thư mục tải lên.', [], 500);
    //                         exit;
    //                     }

    //                     $dest_path = $uploadFileDir . $newFileName;
    //                     if (!function_exists('imagewebp')) {
    //                         $this->error('Server không hỗ trợ WebP.', [], 500);
    //                         exit;
    //                     }

    //                     switch ($fileExtension) {
    //                         case 'jpg':
    //                         case 'jpeg':
    //                             $image = imagecreatefromjpeg($fileTmpPath);
    //                             break;
    //                         case 'png':
    //                             $image = imagecreatefrompng($fileTmpPath);
    //                             break;
    //                         case 'gif':
    //                             $image = imagecreatefromgif($fileTmpPath);
    //                             break;
    //                         case 'webp':
    //                             $image = imagecreatefromwebp($fileTmpPath);
    //                             break;
    //                         default:
    //                             $this->error('Định dạng ảnh không được hỗ trợ.', [], 400);
    //                             exit;
    //                     }

    //                     if ($image === false) {
    //                         $this->error('Không thể tạo đối tượng ảnh từ tệp tải lên.', [], 500);
    //                         exit;
    //                     }
    //                     $conversionSuccess = imagewebp($image, $dest_path, 80);
    //                     if ($conversionSuccess === false) {
    //                         $this->error('Không thể lưu file WebP vào: ' . $dest_path, [], 500);
    //                         exit;
    //                     }
    //                     imagedestroy($image);
    //                     if ($conversionSuccess) {
    //                         $avatarPath = '/uploads/' . ceil($user_id / 1000) . '/' . $user_id . '/' . $newFileName;
    //                     } else {
    //                         $this->error('Có lỗi khi chuyển đổi và lưu avatar dưới định dạng WebP.', [], 500);
    //                         exit;
    //                     }
    //                 } else {
    //                     $this->error('Tệp avatar không hợp lệ.', [], 400);
    //                     exit;
    //                 }
    //             }
                    
    //             if (HAS_POST('gender')) {
    //                 $input['gender'] = S_POST('gender') ?? '';
    //                 $rules['gender'] = [
    //                     'rules' => [
    //                         Validate::in(['male', 'female']) 
    //                     ],
    //                     'messages' => [
    //                         Flang::_e('gender_invalid')
    //                     ]
    //                 ];
    //             }
    //             $input['updated_at'] = DateTime();
    //             $input['avatar'] = $avatarPath;
    //             $input['display'] = S_POST('display') ?? 1;

    //             $validator = new Validate();
    //             if (!$validator->check($input, $rules)) {
    //                 // Get errors and display
    //                 $errors = $validator->getErrors();
    //                 $this->error('errors', $errors);
    //             } else {
    //                 $errors = [];
    //                 if (isset($input['username'])) {
    //                     $existingUser = $this->usersModel->getUserByUsername($input['username']);
    //                     if ($existingUser && $existingUser['id'] != $user_id) {
    //                         $errors['username'] = [Flang::_e('username_double', $input['username'])];
    //                     }
    //                 }
    //                 if (empty($errors)) {
    //                     $result = $this->usersModel->updateUser($user_id, $input);
    //                     if ($result) {
    //                         return $this->success($result, Flang::_e('User updated successfully'));
    //                     } else {
    //                         return $this->error(Flang::_e('User updated error'), [], 404);
    //                     }
    //                 } else {
    //                     return $this->error($errors, [], 404);
    //                 }
    //             }

    //         } else {
    //             $this->error(Flang::_e('user not foundfound'), [], 403);
    //         }
    //     } catch (AppException $e) {
    //         $this->error($e->getMessage(), [], 500);
    //     }
    // }

}