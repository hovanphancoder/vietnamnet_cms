<?php
namespace App\Controllers\Api;

use System\Core\BaseController;
use App\Models\UsersModel;
use App\Models\FastModel;
use System\Core\AppException;

class UsersController extends BaseController
{
    protected $usersModel;

    public function __construct()
    {
        parent::__construct();
        $this->usersModel = new UsersModel();
    }

    // Get all users list
    public function index()
    {
        try {
            $users = $this->usersModel->getUsers();
            $this->success($users, 'Users retrieved successfully.');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Get user information by ID
    public function show($id = '')
    {
        try {
            $user = $this->usersModel->getUserById($id);
            if ($user) {
                $this->success($user, 'User retrieved successfully.');
            } else {
                $this->error('User not found', [], 404);
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Add new user
    public function store()
    {
        try {
            $data = [
                'name' => $_POST['name'] ?? null,
                'email' => $_POST['email'] ?? null,
                'password' => $_POST['password'] ?? null,
                'age' => $_POST['age'] ?? null,
            ];

            $userId = $this->usersModel->addUser($data);
            if ($userId) {
                $this->success(['user_id' => $userId], 'User created successfully.');
            } else {
                $this->error('Failed to create user');
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Update user information
    public function update($id)
    {
        try {
            $data = [
                'name' => $_POST['name'] ?? null,
                'email' => $_POST['email'] ?? null,
                'password' => $_POST['password'] ?? null,
                'age' => $_POST['age'] ?? null,
            ];

            $result = $this->usersModel->updateUser($id, $data);
            if ($result) {
                $this->success([], 'User updated successfully.');
            } else {
                $this->error('Failed to update user');
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Delete user
    public function delete($id)
    {
        try {
            $result = $this->usersModel->deleteUser($id);
            if ($result) {
                $this->success([], 'User deleted successfully.');
            } else {
                $this->error('Failed to delete user');
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }
    

    public function list($page = 1, $limit = 10)
    {

        
        $keyword = $_GET['s'] ?? '';
        $usersFast = new FastModel('fast_users');
        $users = $usersFast->select(['id', 'fullname', 'email', 'role']);
        if($keyword) {
            $users->where('fullname', 'like', '%'.$keyword.'%');
            $users->orWhere('email', 'like', '%'.$keyword.'%');
        }
        $users = $users->where('status', 'active');
        $users = $users->orderBy('id', 'desc');
        $users = $users->paginate($limit, $page);
        $this->success($users, 'Users retrieved successfully.');
    }

    public function getUserByIds()
    {
        $ids = $_POST['ids'] ?? '';
        if(is_string($ids)) {
            $ids = json_decode($ids, true);
        }   
        $usersModel = new UsersModel();
        $users = $usersModel->select(['id', 'fullname', 'email', 'role'])->whereIn('id', $ids)->get();
        $this->success($users, 'Users retrieved successfully.');
    }
}
