<?php
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Core\BaseController;
use App\Models\UsersModel;
use System\Libraries\Session;
use System\Libraries\Render;
use System\Libraries\Security;
use App\Libraries\Fastmail;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Validate;
use App\Libraries\Fasttoken;

class UsersController extends BackendController {

    protected $usersModel;
    protected $mailer;  

    public function __construct()
    {
        parent::__construct();
        Flang::load('Backend/Users');
        $this->usersModel = new UsersModel();
        //Flang::load('general', APP_LANG);
        //Flang::load('users', APP_LANG);
    }

    public function index() {
        $search = S_GET('q') ?? '';
        $limit = S_GET('limit') ??  option('default_limit');;
        $sort = S_GET('sort') ?? 'id';
        $order = S_GET('order') ?? 'DESC';
        $paged = S_GET('page') ?? 1;
        $role = S_GET('role') ?? '';

        // If page = 0 or negative, set to 1
        if ($paged < 1) {
            $paged = 1;
        }
        // If limit is invalid, set to 10
        if ($limit < 1) {
            $limit =  option('default_limit');
        }

        // Build where clause
        $where = '';
        $params = [];

        if(!empty($search)) {
            $where = "username LIKE ? OR email LIKE ? OR phone LIKE ? OR fullname LIKE ?";
            $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
        }

        if(!empty($role)) {
            if(!empty($where)) {
                $where .= ' AND role = ?';
            } else {
                $where = 'role = ?';
            }
            $params[] = $role;
        }

        // Build order by clause
        if(!empty($sort) && !empty($order)) {
            $orderBy = $sort . ' ' . $order;
        } else {
            $orderBy = 'id DESC';
        }

        $users = $this->usersModel->getUsersPage($where, $params, $orderBy, $paged, $limit);
        
        $this->data('users', $users);
        $this->data('limit', $limit);
        $this->data('title', __('list user'));
        $this->data('csrf_token', Session::csrf_token()); //token security
        
        echo Render::html('Backend/users_index', $this->data);
    }

    //index, add, edit, delete, update
    public function add() {
        //TODO: code: not yet coded permission check.
        Render::asset('css', 'css/users.css', ['area' => 'backend', 'location' => 'head']);
        //Render::asset('js', 'js/users.js', ['area' => 'backend', 'location' => 'footer']);  
        
        if (HAS_POST('username')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                $this->data('error', __('csrf_failed'));
            }

            $permissions = S_POST('permissions') ?? [];
            if (!is_array($permissions)) {
                $permissions = [];
            }
            if (S_POST('role') == 'member'){
                if (!empty($permissions)){
                    foreach ($permissions as $key => $item){
                        if (strpos($key, 'Backend\\') !== FALSE){
                            unset($permissions[$key]);
                        }
                    }
                }
            }

            $input = [
                'username' => S_POST('username') ?? '',
                'fullname' => S_POST('fullname') ?? '',
                'email' => S_POST('email') ?? '',
                'phone' => S_POST('phone') ?? '',
                'password' => S_POST('password') ?? '',
                'password_repeat' => S_POST('password_repeat'),
                'role' => S_POST('role') ?? '',
                'permissions' => json_encode($permissions),
                'status' => S_POST('status') ?? '',
            ];
            $rules = [
                'username' => [
                    'rules' => [
                        Validate::alnum('_'),
                        Validate::length(6, 30)
                    ],
                    'messages' => [
                        __('username_invalid'),
                        sprintf(__('username_length'), 6, 30)
                    ]
                ],
                'fullname' => [
                    'rules' => [
                        Validate::length(6, 30)
                    ],
                    'messages' => [
                        sprintf(__('fullname_length'), 6, 50)
                    ]
                ],
                'email' => [
                    'rules' => [
                        Validate::email(),
                        Validate::length(6, 150)
                    ],
                    'messages' => [
                        __('email_invalid'),
                        sprintf(__('email_length'), 6, 150)
                    ]
                ],
                'phone' => [
                    'rules' => [
                        Validate::optional(Validate::phone()),
                        Validate::optional(Validate::length(6, 30))
                    ],
                    'messages' => [
                        __('phone_invalid'),
                        sprintf(__('phone_length'), 6, 30)
                    ]
                ],
                'password' => [
                    'rules' => [
                        Validate::optional(Validate::length(6, 60)),
                    ],
                    'messages' => [
                        sprintf(__('password_length'), 6, 60),
                    ]
                ],
                'password_repeat' => [
                    'rules' => [
                        Validate::optional(Validate::equals($input['password']))
                    ],
                    'messages' => [
                        sprintf(__('password_repeat_invalid'), $input['password_repeat'])
                    ]
                ],
                'role' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        __('role_option'),
                    ]
                ],
                'permissions' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        __('permission_array_json'),
                    ]
                ],
                'status' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        __('status_option'),
                    ]
                ]

            ];
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Get errors and display
                $errors = $validator->getErrors();
                $this->data('errors', $errors);     
            }else{
                $errors = [];
                if ($this->usersModel->getUserByUsername($input['username'])) {
                    $errors['username'] = array(
                        sprintf(__('username_double'), $input['username'])
                    );
                    $isExists = true;
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors['email'] = array(
                        sprintf(__('email_double'), $input['email'])
                    );
                    $isExists = true;
                }
                if (empty($isExists) && empty($errors)){
                    $input['password'] = Security::hashPassword($input['password']);
                    if (isset($input['password_repeat'])) {
                        unset($input['password_repeat']);
                    }
                    //xu ly them 1 so field social
                    
                    $input['created_at'] = DateTime();
                    $input['updated_at'] = DateTime();
                    return $this->_add($input);
                } else {
                    $this->data('errors', $errors);
                }
            }
        }
        //render khi ko co request POST, or bi errors show ra.
        $admin = config('admin', 'Roles');
        $moderator = config('moderator', 'Roles');
        $author = config('author', 'Roles');
        $member = config('member', 'Roles');

        $roles = [
            'admin' => $admin,
            'moderator' => $moderator,
            'author' => $author,
            'member' => $member
        ];

        $status = ['active', 'inactive', 'banned'];
        $this->data('roles', $roles);
        $this->data('status', $status);
        $this->data('title', __('title_add_member'));
        $this->data('csrf_token', Session::csrf_token(600)); 
        echo Render::html('Backend/users_add', $this->data);
    }


    private function _add($input)
    {
        if ($input['status'] !== 'active') {
            $activationNo = strtoupper(random_string(6)); // Create 6-character code
            $activationCode = strtolower(random_string(20)); // Create 20-character code
            $optionalData = [
                'activation_no' => $activationNo,
                'activation_code' => $activationCode,
                'activation_expires' => time() + 86400,
            ];
            $input['optional'] = json_encode($optionalData);
        } else {
            $input['optional'] = null;
        }
        $user_id = $this->usersModel->addUser($input);
       
        if ($user_id) {
            // If status is not 'active' then send activation email
            if ($input['status'] !== 'active') {
                $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode . '/');
                $this->mailer = new Fastmail();
                $this->mailer->send($input['email'], __('active_account'), 'activation', ['username' => $input['username'], 'activation_link' => $activationLink]);
            }
            Session::flash('success', __('User added successfully'));
            \System\Libraries\Events::run('Backend\\UsersAddEvent', $input);
            redirect(admin_url('users/index'));
        } else {
            Session::flash('error', __('Failed to add user'));
            redirect(auth_url('dashboard'));
        }
    }

    public function edit($user_id) {
        Render::asset('css', 'css/users.css', ['area' => 'backend', 'location' => 'head']);
        //Render::asset('js', 'js/users.js', ['area' => 'backend', 'location' => 'footer']);  
        // Check if the user exists
        $user = $this->usersModel->getUserById($user_id);
        // check session user if not admin, admin can not edit admin orther
        global $me_info;
        //xoi code: chua code check perrmision cua $me_info.

        if (!$user) {
            // User not found, redirect or show an error message
            Session::flash('error', __('User not found'));
            redirect(admin_url('users/index'));
        }
    
        if (!empty($_POST)) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                $this->data('error', __('csrf_failed'));
            }else{
                // Initialize an empty array for rules
                $rules = [];
                $input = [];
        
                // Check each field and add the validation rules accordingly
                if (HAS_POST('username')) {
                    $input['username'] = S_POST('username') ?? '';
                    $rules['username'] = [
                        'rules' => [
                            Validate::alnum('_'),
                            Validate::length(6, 30)
                        ],
                        'messages' => [
                            __('username_invalid'),
                            sprintf(__('username_length'), 6, 30)
                        ]
                    ];
                }
                if (HAS_POST('fullname')) {
                    $input['fullname'] = S_POST('fullname') ?? '';
                    $rules['fullname'] = [
                        'rules' => [
                            Validate::length(6, 50)
                        ],
                        'messages' => [
                            sprintf(__('fullname_length'), 6, 50)
                        ]
                    ];
                }
                if (HAS_POST('email')) {
                    $input['email'] = S_POST('email') ?? '';
                    $rules['email'] = [
                        'rules' => [
                            Validate::email(),
                            Validate::length(6, 150)
                        ],
                        'messages' => [
                            __('email_invalid'),
                            sprintf(__('email_length'), 6, 150)
                        ]
                    ];
                }
                if (HAS_POST('phone')) {
                    $input['phone'] = S_POST('phone') ?? '';
                    $rules['phone'] = [
                        'rules' => [
                            Validate::optional(Validate::phone()),
                            Validate::optional(Validate::length(6, 30))
                        ],
                        'messages' => [
                            __('phone_invalid'),
                            sprintf(__('phone_length'), 6, 30)
                        ]
                    ];
                }
                if (HAS_POST('role')) {
                    $input['role'] = S_POST('role') ?? '';
                    $rules['role'] = [
                        'rules' => [
                            Validate::notEmpty(),
                        ],
                        'messages' => [
                            __('role_option'),
                        ]
                    ];
                }
                if (HAS_POST('permissions')) {
                    $permissions = S_POST('permissions') ?? [];
                    if (!is_array($permissions)) {
                        $permissions = [];
                    }
                    if (S_POST('role') == 'member'){
                        if (!empty($permissions)){
                            foreach ($permissions as $key => $item){
                                //if (strpos($key, 'Backend\\') !== FALSE){
                                //    unset($permissions[$key]);
                                //}
                            }
                        }
                    }
                    $input['permissions'] = json_encode($permissions);
                    $rules['permissions'] = [
                        'rules' => [
                            Validate::notEmpty(),
                        ],
                        'messages' => [
                            __('permission_array_json'),
                        ]
                    ];
                }
                if (HAS_POST('status')) {
                    $input['status'] = S_POST('status') ?? '';
                    $rules['status'] = [
                        'rules' => [
                            Validate::notEmpty(),
                        ],
                        'messages' => [
                            __('status_option'),
                        ]
                    ];
                }

                if(HAS_POST('password') && S_POST('password') != ''){
                    $input['password'] = S_POST('password') ?? '';
                    $rules['password'] = [
                        'rules' => [
                            Validate::length(6, 60),
                        ],
                        'messages' => [
                            sprintf(__('password_length'), 6, 60),
                        ]
                    ];
                }
                if(HAS_POST('password_repeat') && S_POST('password_repeat') != ''){
                    $input['password_repeat'] = S_POST('password_repeat');
                    $rules['password_repeat'] = [
                        'rules' => [
                            Validate::equals($input['password'])
                        ],
                        'messages' => [
                            sprintf(__('password_repeat_invalid'), $input['password_repeat'])
                        ]
                    ];
                }

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    // Get errors and display
                    $errors = $validator->getErrors();
                    $this->data('errors', $errors);
                    $this->data('error', __('Please fix the validation errors'));
                } else {
                    $errors = [];
                    // Check for duplicate username or email
                    if(!empty($input['password'])){
                        $input['password'] = Security::hashPassword($input['password']);
                    } 
                    
                    if (isset($input['username'])) {
                        $existingUser = $this->usersModel->getUserByUsername($input['username']);
                        if ($existingUser && $existingUser['id'] != $user_id) {
                            $errors['username'] = [sprintf(__('username_double'), $input['username'])];
                        }
                    }
        
                    if (isset($input['email'])) {
                        $existingEmailUser = $this->usersModel->getUserByEmail($input['email']);
                        if ($existingEmailUser && $existingEmailUser['id'] != $user_id) {
                            $errors['email'] = [sprintf(__('email_double'), $input['email'])];
                        }
                    }
                    if (empty($errors)) {
                        // Update user data
                        $input['updated_at'] = DateTime();
                        $this->_edit($user_id, $input);
        
                        // Set success message and retrieve updated user data
                        $this->data('success', __('User updated successfully'));
                        $user = $this->usersModel->getUserById($user_id); // Retrieve updated user
                    } else {
                        $this->data('errors', $errors);
                    }
                }
            }
        }
    
        // Preload roles and status for the form
        $roles = [
            'admin' => config('admin', 'Roles'),
            'moderator' => config('moderator', 'Roles'),
            'author' => config('author', 'Roles'),
            'member' => config('member', 'Roles')
        ];
        $status = ['active', 'inactive', 'banned'];
    

        $this->data('roles', $roles);
        $this->data('status', $status);
        $this->data('user', $user); // Pass current user data to the view
        $this->data('title', __('title_edit_member'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Backend/users_add', $this->data);
    }
    
    private function _edit($user_id, $input) {

        $dataToUpdate = array_filter($input, function($value) {
            return $value !== '';
        }); // Remove empty values to only update filled fields
    
        if (isset($dataToUpdate['email'])){
            if (isset($dataToUpdate['status']) && $dataToUpdate['status'] !== 'active') {
                $activationNo = strtoupper(random_string(6));
                $activationCode = strtolower(random_string(20));
                $optionalData = [
                    'activation_no' => $activationNo,
                    'activation_code' => $activationCode,
                    'activation_expires' => time() + 86400,
                ];
                $dataToUpdate['optional'] = json_encode($optionalData);
            } else {
                $dataToUpdate['optional'] = null;
            }
        }
        
        $result = $this->usersModel->updateUser($user_id, $dataToUpdate);
        
    
        if ($result) {
            if (isset($dataToUpdate['status']) && $dataToUpdate['status'] !== 'active' && isset($dataToUpdate['email'])) {
                $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode . '/');
                $this->mailer = new Fastmail();
                $this->mailer->send($dataToUpdate['email'], __('active_account'), 'activation', ['username' => $dataToUpdate['username'], 'activation_link' => $activationLink]);
            }else{
                if (isset($dataToUpdate['status']) && !isset($dataToUpdate['email']) && count($dataToUpdate) < 3){
                    echo json_encode($dataToUpdate);exit();
                }
            }
            Session::flash('success', __('User updated successfully'));
            \System\Libraries\Events::run('Backend\\UsersEditEvent', $input);
            redirect(admin_url('users/index'));
        } else {
            $this->data('error', __('Failed to update user'));
        }
    }

    public function update_status() {
        $user_id = S_POST('user_id') ?? '';
        $status = S_POST('status') ?? '';
        $user = $this->usersModel->getUserById($user_id);
        if (empty($user)) {
            return $this->error(__('User not found'), [], 404);
        }
        if ($status == 'active') {
            $this->usersModel->updateUser($user_id, ['status' => 'active']);
        } elseif($status == 'inactive') {
            $this->usersModel->updateUser($user_id, ['status' => 'inactive']);
        } elseif($status == 'banned') {
            $this->usersModel->updateUser($user_id, ['status' => 'banned']);
        } else {
            return $this->error(__('status_option'), [], 400);
        }
        return $this->success([], __('User status updated successfully'), 200);
    }

    public function changestatus($id)
    {
        $user = $this->usersModel->getUserById($id);

        if (!$user) {
            Session::flash('error', __('User not found'));
            redirect(admin_url('users'));
        }

        $status = $user['status'] == 'active' ? 'inactive' : 'active';
        $data = [
            'status' => $status
        ];
        $result = $this->usersModel->updateUser($id, $data);

        if (!$result) {
            Session::flash('error', __('Failed to update user status'));
        } else {
            Session::flash('success', __('User status updated successfully'));
        }
        redirect(admin_url('users'));
    }

    // Xóa User
    public function delete($user_id = null) {
        if(!empty($user_id)) {
            $this->_delete($user_id);
        } elseif($_POST['ids']) {
            $ids = $_POST['ids'];
            $ids = json_decode($ids, true);
            foreach($ids as $id) {
                $this->_delete($id);
            }
            $this->success([], __('Users deleted successfully'));
        } else {
            $this->error(__('No users selected for deletion'));
        }
        redirect(admin_url('users/index'));
    }

    // Đoạn này dựng tạm để xử lý cho Event
    public function _delete($user_id) {
        if ($this->usersModel->deleteUser($user_id)){
            \System\Libraries\Events::run('Backend\\UsersDeleteEvent', $user_id);
            return true;
        }
        return false;
    }

    // protected function _authentication() {
    //     $access_token = Fasttoken::getToken();
    //     if(Session::has('user_id')) {
    //         $user_id = clean_input(Session::get('user_id'));
            
    //     } elseif (!empty($access_token)) {
    //         $config_security = config('security');
    //         $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
    //         if (!isset($me_data['success'])) {
    //             return $this->error(Flang::_e('auth_token_invalid'), [$me_data['message']], 401);
    //         }
    //         $user_id = $me_data['data']['user_id'] ?? null;
    //         if (empty($user_id)) {
    //             return $this->error(Flang::_e('token_invalid'), [], 401);
    //         }
    //     } else {
    //         $this->error(Flang::_e('user_not_found'), [], 403);
    //     }

    //     $user = $this->usersModel->getUserById($user_id);
    //     if (empty($user)) {
    //         return $this->error(Flang::_e('user_not_found'), [], 404);
    //     } else {
    //         return $user;
    //     }
    // }
}