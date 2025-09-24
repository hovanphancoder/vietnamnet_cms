# Building Plugins

## Plugin Structure
```
plugins/
├── MyPlugin/
│   ├── MyPlugin.php
│   ├── config.php
│   ├── Controllers/
│   │   └── PluginController.php
│   ├── Models/
│   │   └── PluginModel.php
│   ├── Views/
│   │   └── admin/
│   │       └── settings.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── Languages/
│   │   ├── en/
│   │   └── vi/
│   └── README.md
```

## Step-by-Step Guide

### 1. Create Plugin Directory
```bash
mkdir -p plugins/MyPlugin/{Controllers,Models,Views,assets,Languages}
```

### 2. Create Main Plugin File
```php
// plugins/MyPlugin/MyPlugin.php
class MyPlugin extends BasePlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'My Plugin';
        $this->version = '1.0.0';
        $this->description = 'A sample plugin';
        $this->author = 'Your Name';
    }
    
    public function activate()
    {
        // Create database tables
        $this->createTables();
        
        // Register hooks
        $this->registerHooks();
        
        // Set default options
        $this->setDefaultOptions();
    }
    
    public function deactivate()
    {
        // Clean up if necessary
        // Remove hooks
        $this->removeHooks();
    }
    
    public function uninstall()
    {
        // Remove database tables
        $this->dropTables();
        
        // Remove plugin files
        $this->removeFiles();
    }
    
    private function createTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }
    
    private function registerHooks()
    {
        // Register to various system hooks
        $this->hook('init', [$this, 'onInit']);
        $this->hook('admin_menu', [$this, 'addAdminMenu']);
        $this->hook('wp_head', [$this, 'addHeadContent']);
    }
    
    public function onInit()
    {
        // Plugin initialization code
    }
    
    public function addAdminMenu()
    {
        // Add menu item to admin panel
        add_menu_page(
            'My Plugin',
            'My Plugin',
            'manage_options',
            'my-plugin',
            [$this, 'adminPage']
        );
    }
    
    public function adminPage()
    {
        // Admin page content
        include $this->getPath('Views/admin/settings.php');
    }
}
```

### 3. Create Configuration File
```php
// plugins/MyPlugin/config.php
return [
    'name' => 'My Plugin',
    'version' => '1.0.0',
    'description' => 'A sample plugin description',
    'author' => 'Your Name',
    'author_url' => 'https://yoursite.com',
    'requires' => '5.0.0',
    'tested' => '5.8.0',
    'requires_php' => '7.4',
    'text_domain' => 'my-plugin',
    'domain_path' => '/languages',
    'network' => false,
    'auto_update' => false
];
```

### 4. Create Controller
```php
// plugins/MyPlugin/Controllers/PluginController.php
class PluginController extends BaseController
{
    public function index()
    {
        $data = $this->pluginModel->getAll();
        return $this->view('index', ['data' => $data]);
    }
    
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->pluginModel->save($data);
            $this->setMessage('Settings saved successfully');
        }
        $this->redirect('admin.php?page=my-plugin');
    }
}
```

### 5. Create Model
```php
// plugins/MyPlugin/Models/PluginModel.php
class PluginModel extends BaseModel
{
    protected $table = 'plugin_table';
    
    public function getAll()
    {
        return $this->db->table($this->table)->get();
    }
    
    public function save($data)
    {
        if (isset($data['id'])) {
            return $this->db->table($this->table)
                ->where('id', $data['id'])
                ->update($data);
        } else {
            return $this->db->table($this->table)->insert($data);
        }
    }
}
```

### 6. Create Admin View
```php
<!-- plugins/MyPlugin/Views/admin/settings.php -->
<div class="wrap">
    <h1>My Plugin Settings</h1>
    
    <form method="post" action="<?= admin_url('admin-post.php') ?>">
        <?php wp_nonce_field('my_plugin_nonce'); ?>
        <input type="hidden" name="action" value="my_plugin_save">
        
        <table class="form-table">
            <tr>
                <th scope="row">Plugin Name</th>
                <td>
                    <input type="text" name="name" value="<?= esc_attr($options['name'] ?? '') ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">Plugin Value</th>
                <td>
                    <textarea name="value" rows="5" class="large-text"><?= esc_textarea($options['value'] ?? '') ?></textarea>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
```

### 7. Register Plugin
```php
// In your main application
$app->registerPlugin('MyPlugin');
```

## Plugin Hooks
- `init` - Plugin initialization
- `admin_menu` - Add admin menu items
- `wp_head` - Add content to page head
- `wp_footer` - Add content to page footer
- `save_post` - When post is saved
- `delete_post` - When post is deleted

## Best Practices
- Always use namespaces
- Implement proper activation/deactivation hooks
- Use nonces for security
- Sanitize and validate all inputs
- Escape all outputs
- Follow coding standards
- Include uninstall cleanup
- Test thoroughly before release
