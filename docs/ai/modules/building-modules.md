# Building Application Modules

## Module Structure
```
modules/
├── UserModule/
│   ├── Controllers/
│   │   ├── UserController.php
│   │   └── AuthController.php
│   ├── Models/
│   │   └── UserModel.php
│   ├── Views/
│   │   ├── login.php
│   │   └── profile.php
│   ├── Routes/
│   │   └── user_routes.php
│   └── config.php
```

## Step-by-Step Guide

### 1. Create Module Directory
```bash
mkdir -p modules/UserModule/{Controllers,Models,Views,Routes}
```

### 2. Create Module Configuration
```php
// modules/UserModule/config.php
return [
    'name' => 'User Module',
    'version' => '1.0.0',
    'description' => 'User management module',
    'author' => 'Your Name',
    'dependencies' => ['Database', 'Auth'],
    'routes' => 'Routes/user_routes.php'
];
```

### 3. Create Controller
```php
// modules/UserModule/Controllers/UserController.php
class UserController extends BaseController
{
    public function index()
    {
        $users = $this->userModel->getAll();
        return $this->view('users/index', ['users' => $users]);
    }
    
    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->userModel->create($data);
            $this->redirect('/users');
        }
        return $this->view('users/create');
    }
}
```

### 4. Create Model
```php
// modules/UserModule/Models/UserModel.php
class UserModel extends BaseModel
{
    protected $table = 'users';
    
    public function getAll()
    {
        return $this->db->table($this->table)->get();
    }
    
    public function create($data)
    {
        return $this->db->table($this->table)->insert($data);
    }
}
```

### 5. Create Views
```php
<!-- modules/UserModule/Views/users/index.php -->
<div class="users-list">
    <?php foreach ($users as $user): ?>
        <div class="user-item">
            <h3><?= $user['username'] ?></h3>
            <p><?= $user['email'] ?></p>
        </div>
    <?php endforeach; ?>
</div>
```

### 6. Define Routes
```php
// modules/UserModule/Routes/user_routes.php
return [
    'GET /users' => 'UserController@index',
    'GET /users/create' => 'UserController@create',
    'POST /users' => 'UserController@store',
    'GET /users/{id}' => 'UserController@show',
    'PUT /users/{id}' => 'UserController@update',
    'DELETE /users/{id}' => 'UserController@delete'
];
```

### 7. Register Module
```php
// In your main application
$app->registerModule('UserModule');
```

## Best Practices
- Follow MVC pattern strictly
- Use dependency injection
- Implement proper error handling
- Add input validation
- Write unit tests
- Document your code
- Use consistent naming conventions
