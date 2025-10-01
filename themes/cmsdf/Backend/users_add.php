<?php

use System\Libraries\Render;
use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;

// Load language files
Flang::load('Backend/Global', APP_LANG);
Flang::load('Backend/Users', APP_LANG);

if (!empty($user)) {
    $isEdit = true;
    $actionUrl = admin_url('users/edit/' . $user['id']);
} else {
    $actionUrl = admin_url('users/add');
    $isEdit = false;
}

$breadcrumbs = array(
  [
      'name' => __('Dashboard'),
      'url' => admin_url('home')
  ],
  [
      'name' => __('Users'),
      'url' => admin_url('users')
  ],
  [
      'name' => $isEdit ? __('Edit User') : __('Add User'),
      'url' => admin_url('users/' . ($isEdit ? 'edit/' . $user['id'] : 'add')),
      'active' => true
  ]
);
Render::block('Backend\\Header', ['layout' => 'default', 'title' => $title ?? 'CMS Full Form', 'breadcrumb' => $breadcrumbs ]);


$admin_permissions = $roles['admin']['permissions'] ?? [];
$user_permissions = [];
if ($isEdit && !empty($user['permissions'])) {
    if (is_string($user['permissions'])) {
        $user_permissions = json_decode($user['permissions'], true) ?: [];
    } else {
        $user_permissions = $user['permissions'];
    }
}
?>

<div x-data="userForm()">

  <!-- Header -->
  <div class="flex flex-col gap-4">
    <div>
      <h1 class="text-2xl font-bold text-foreground"><?= $isEdit ? __('Edit User') : __('Add New User') ?></h1>
      <p class="text-muted-foreground"><?= $isEdit ? __('Update user information and permissions') : __('Create a new user account with roles and permissions') ?></p>
    </div>

    <?php if (!empty($error)): ?>
      <?php Render::block('Backend\\Notification', ['layout' => 'default', 'type' => 'error', 'message' => $error]) ?>
    <?php endif; ?>
  </div>

  <!-- Main Content -->
    <div class="p-0">
      <div class="space-y-4 sm:space-y-6 w-full">
        
        <!-- Tabs Navigation -->
        <div dir="ltr" data-orientation="horizontal" class="w-full">
          <div role="tablist" aria-orientation="horizontal"
            class="items-center justify-center rounded-md bg-muted py-1 px-1 text-muted-foreground grid w-full grid-cols-2"
            tabindex="0" data-orientation="horizontal" style="outline: none;">
            <button type="button" role="tab"
              :aria-selected="activeTab === 'basic'" 
              :data-state="activeTab === 'basic' ? 'active' : 'inactive'"
              @click="activeTab = 'basic'"
              class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex items-center gap-2"
              tabindex="0" data-orientation="horizontal" data-radix-collection-item="">
              <i data-lucide="user" class="h-4 w-4"></i>
              <?= __('Basic Information') ?>
            </button>
            <button type="button" role="tab"
              :aria-selected="activeTab === 'security'" 
              :data-state="activeTab === 'security' ? 'active' : 'inactive'"
              @click="activeTab = 'security'"
              class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex items-center gap-2"
              tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
              <i data-lucide="shield" class="h-4 w-4"></i>
              <?= __('Security & Roles') ?>
            </button>
          </div>

        <!-- Main Form -->
        <form id="userForm" action="<?= $actionUrl ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
            
            <!-- Tab Content -->
            <div :data-state="activeTab === 'basic' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
              :aria-labelledby="'tab-basic'" tabindex="0"
              class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
              :hidden="activeTab !== 'basic'">
              
              <div class="">
              
              <!-- Basic Information Tab -->
              <div>
                  <div class="space-y-6">
                      <div>
                          <h3 class="text-xl font-bold flex items-center gap-2">
                          <i data-lucide="user" class="h-5 w-5"></i>
                          <?= __('Basic Information') ?>
                          </h3>
                          <p class="text-sm text-muted-foreground"><?= __('Enter the user\'s basic information') ?></p>
                      </div>

                  
                      
                      <div class="grid gap-y-6 gap-x-4">
                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                              <div class="space-y-2">
                              <label for="username" class="text-sm font-medium"><?= __('username') ?> <span class="text-red-500">*</span></label>
                              <input id="username" name="username" type="text" 
                                      value="<?= $isEdit ? htmlspecialchars($user['username']) : '' ?>"
                                      placeholder="<?= __('placeholder_username') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                              <?php if (!empty($errors['username'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['username'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>

                              <div class="space-y-2">
                              <label for="fullname" class="text-sm font-medium"><?= __('fullname') ?> <span class="text-red-500">*</span></label>
                              <input id="fullname" name="fullname" type="text" 
                                      value="<?= $isEdit ? htmlspecialchars($user['fullname']) : '' ?>"
                                      placeholder="<?= __('placeholder_fullname') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                              <?php if (!empty($errors['fullname'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['fullname'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                              <div class="space-y-2">
                              <label for="email" class="text-sm font-medium"><?= __('email') ?> <span class="text-red-500">*</span></label>
                              <input id="email" name="email" type="email" 
                                      value="<?= $isEdit ? htmlspecialchars($user['email']) : '' ?>"
                                      placeholder="<?= __('placeholder_email') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                              <?php if (!empty($errors['email'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['email'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>

                              <div class="space-y-2">
                              <label for="phone" class="text-sm font-medium"><?= __('phone') ?></label>
                              <input id="phone" name="phone" type="tel" 
                                      value="<?= $isEdit ? htmlspecialchars($user['phone']) : '' ?>"
                                      placeholder="<?= __('placeholder_phone') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                              <?php if (!empty($errors['phone'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['phone'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                              <div class="space-y-2">
                              <label for="status" class="text-sm font-medium"><?= __('status') ?></label>
                              <select id="status" name="status" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                  <?php foreach ($status as $status_option): ?>
                                  <option value="<?= $status_option ?>" <?= ($isEdit && $user['status'] == $status_option) ? 'selected' : '' ?>><?= __($status_option) ?></option>
                                  <?php endforeach; ?>
                              </select>
                              <?php if (!empty($errors['status'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['status'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>

                              <div class="space-y-2">
                              <label for="role" class="text-sm font-medium"><?= __('role') ?></label>
                              <select id="role" name="role" x-model="selectedRole" @change="setActiveRole($event.target.value)" class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                  <?php foreach ($roles as $role_key => $role_permissions): ?>
                                  <option value="<?= $role_key ?>" <?= ($isEdit && $user['role'] == $role_key) ? 'selected' : '' ?>><?= __(ucfirst($role_key)) ?></option>
                                  <?php endforeach; ?>
                              </select>
                              <?php if (!empty($errors['role'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                  <?php foreach ($errors['role'] as $error): ?>
                                      <p><?= $error; ?></p>
                                  <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                              </div>
                          </div>

                          <?php if (!$isEdit): ?>
                          <!-- Password fields for new user -->
                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                              <div class="space-y-2">
                                  <label for="password" class="text-sm font-medium"><?= __('password') ?> <span class="text-red-500">*</span></label>
                                  <input id="password" name="password" type="password" 
                                      placeholder="<?= __('placeholder_password') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                                  <?php if (!empty($errors['password'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                      <?php foreach ($errors['password'] as $error): ?>
                                      <p><?= $error; ?></p>
                                      <?php endforeach; ?>
                                  </div>
                                  <?php endif; ?>
                              </div>

                              <div class="space-y-2">
                                  <label for="password_repeat" class="text-sm font-medium"><?= __('password_repeat') ?> <span class="text-red-500">*</span></label>
                                  <input id="password_repeat" name="password_repeat" type="password" 
                                      placeholder="<?= __('placeholder_password_repeat') ?>"
                                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                                  <?php if (!empty($errors['password_repeat'])): ?>
                                  <div class="text-red-500 text-sm mt-1">
                                      <?php foreach ($errors['password_repeat'] as $error): ?>
                                      <p><?= $error; ?></p>
                                      <?php endforeach; ?>
                                  </div>
                                  <?php endif; ?>
                              </div>
                          </div>
                          <?php endif; ?>
                      </div>
                  
                  </div>
              </div>

              </div>
            </div>

        <!-- Security Tab -->
        <div :data-state="activeTab === 'security' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
          :aria-labelledby="'tab-security'" tabindex="0"
          class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
          :hidden="activeTab !== 'security'">
          
          <div class="">
          
          <!-- Security Tab Content -->
          <div>
              <div class="space-y-6">
                  <div>
                      <h3 class="text-xl font-bold flex items-center gap-2">
                      <i data-lucide="shield" class="h-5 w-5"></i>
                      <?= __('Security & Roles') ?>
                      </h3>
                      <p class="text-sm text-muted-foreground">Manage password and role permissions</p>
                  </div>

                  <div class="border-border space-y-6">
                      
                      <?php if ($isEdit): ?>
                      <!-- Password Section for Edit Mode -->
                      <div class="space-y-4">
                          <div class="flex items-center justify-between">
                              <div><h4 class="text-lg font-medium"><?= __('change password') ?>?</h4></div>
                              <button type="button" @click="changePassword = !changePassword" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                              <i data-lucide="square-pen" class="h-4 w-4 mr-2"></i>
                              <?= __('change password') ?>
                              </button>
                          </div>

                          <div x-show="changePassword" x-transition class="space-y-4 p-4 bg-muted/50 rounded-lg">
                              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                  <div class="space-y-2">
                                      <label for="password" class="text-sm font-medium"><?= __('password') ?> <span class="text-red-500">*</span></label>
                                      <input id="password" name="password" type="password" 
                                          placeholder="<?= __('placeholder_password') ?>"
                                          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                      <?php if (!empty($errors['password'])): ?>
                                      <div class="text-red-500 text-sm mt-1">
                                          <?php foreach ($errors['password'] as $error): ?>
                                          <p><?= $error; ?></p>
                                          <?php endforeach; ?>
                                      </div>
                                      <?php endif; ?>
                                  </div>

                                  <div class="space-y-2">
                                      <label for="password_repeat" class="text-sm font-medium"><?= __('password_repeat') ?> <span class="text-red-500">*</span></label>
                                      <input id="password_repeat" name="password_repeat" type="password" 
                                          placeholder="<?= __('placeholder_password_repeat') ?>"
                                          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                      <?php if (!empty($errors['password_repeat'])): ?>
                                      <div class="text-red-500 text-sm mt-1">
                                          <?php foreach ($errors['password_repeat'] as $error): ?>
                                          <p><?= $error; ?></p>
                                          <?php endforeach; ?>
                                      </div>
                                      <?php endif; ?>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <?php endif; ?>

                      <!-- Roles & Permissions Section -->
                      <div class="space-y-4 overflow-hidden">
                          <div>
                              <h4 class="text-lg font-medium"><?= __('roles_permissions') ?></h4>
                              <p class="text-sm text-muted-foreground"><?= __('Configure user roles and permissions') ?></p>
                          </div>

                          <div class="bg-card border rounded-lg overflow-hidden">
                              <div class="flex">
                                  <!-- Roles Sidebar -->
                                  <div class="border-r border-border">
                                      <div class="p-4 border-b border-border">
                                          <h5 class="text-sm font-medium text-foreground"><?= __('Select Role') ?></h5>
                                          <p class="text-xs text-muted-foreground mt-1"><?= __('Choose a role to configure permissions') ?></p>
                                      </div>
                                      <div class="p-2 space-y-1 max-h-[400px] overflow-y-auto">
                                          <template x-for="(permissions, role) in roles" :key="role">
                                              <div class="flex items-center space-x-3 p-3 rounded-lg cursor-pointer transition-colors hover:bg-accent/50"
                                                  :class="{ 'bg-accent border border-primary/20': selectedRole === role }"
                                                  @click="setActiveRole(role)">
                                                  <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-medium"
                                                      :class="{
                                                          'bg-primary': role === 'admin',
                                                          'bg-yellow-500': role === 'moderator', 
                                                          'bg-green-500': role === 'author',
                                                          'bg-blue-500': role === 'member',
                                                          'bg-purple-500': role === 'editor',
                                                          'bg-gray-500': role === 'subscriber'
                                                      }">
                                                      <i :class="getRoleIcon(role)" class="text-sm"></i>
                                                  </div>
                                                  <div class="flex-1 min-w-0">
                                                      <div class="text-sm font-medium text-foreground capitalize" x-text="role"></div>
                                                      <div class="text-xs text-muted-foreground" x-text="getRoleDescription(role)"></div>
                                                  </div>
                                                  <div x-show="selectedRole === role" class="flex-shrink-0">
                                                      <i data-lucide="check" class="text-primary"></i>
                                                  </div>
                                              </div>
                                          </template>
                                      </div>
                                  </div>

                                  <!-- Role Content -->
                                  <div class="flex-1 h-[470px] overflow-y-auto" x-show="selectedRole">
                                      <div class="p-6">
                                          <!-- Role Header -->
                                          <div class="flex items-center space-x-4 mb-0 pb-2">
                                              <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-white text-lg font-medium"
                                                  :class="{
                                                      'bg-primary': selectedRole === 'admin',
                                                      'bg-yellow-500': selectedRole === 'moderator', 
                                                      'bg-green-500': selectedRole === 'author',
                                                      'bg-blue-500': selectedRole === 'member',
                                                      'bg-purple-500': selectedRole === 'editor',
                                                      'bg-gray-500': selectedRole === 'subscriber'
                                                  }">
                                                  <i :class="getRoleIcon(selectedRole)" class="text-lg"></i>
                                              </div>
                                              <div class="flex-1">
                                                  <h3 class="text-lg font-semibold text-foreground capitalize" x-text="selectedRole"></h3>
                                                  <p class="text-sm text-muted-foreground" x-text="getRoleDescription(selectedRole)"></p>
                                              </div>
                                          </div>

                                          <!-- Permission Summary -->
                                          <div class="bg-muted/50 rounded-lg p-4 py-2 mb-2">
                                              <div class="flex items-center gap-8 mb-3">
                                                  <div class="flex items-center space-x-4">
                                                      <div class="text-center">
                                                          <div class="text-2xl font-bold text-foreground" x-text="getGrantedPermissionsCount() + '/' + getTotalPermissionsCount()"></div>
                                                          <div class="text-xs text-muted-foreground" x-text="translations['Permissions']"></div>
                                                      </div>
                                                      <div class="text-center">
                                                          <div class="text-lg font-semibold text-foreground" x-text="getPermissionPercentage() + '%'"></div>
                                                          <div class="text-xs text-muted-foreground" x-text="translations['Access Level']"></div>
                                                      </div>
                                                  </div>
                                                  <div class="text-right flex-1">
                                                      <div class="text-sm font-medium text-foreground" x-text="getPermissionLevelText()"></div>
                                                          <div class="w-auto h-2 bg-muted rounded-full overflow-hidden mt-1">
                                                              <div class="h-full rounded-full transition-all duration-300"
                                                                  :class="{
                                                                  'bg-green-500': getPermissionPercentage() >= 90,
                                                                  'bg-blue-500': getPermissionPercentage() >= 70 && getPermissionPercentage() < 90,
                                                                  'bg-yellow-500': getPermissionPercentage() >= 40 && getPermissionPercentage() < 70,
                                                                  'bg-orange-500': getPermissionPercentage() >= 20 && getPermissionPercentage() < 40,
                                                                  'bg-red-500': getPermissionPercentage() < 20
                                                                  }"
                                                                  :style="'width: ' + getPermissionPercentage() + '%'"></div>
                                                          </div>
                                                      </div>
                                                  </div>

                                                  <!-- Permission Groups -->
                                                  <div class="space-y-4">
                                                      <template x-for="(permissions, resource) in adminPermissions" :key="resource">
                                                          <div class="border border-border rounded-lg overflow-hidden">
                                                              <div class="flex items-center justify-between p-3 border-b border-border">
                                                                  <div class="flex items-center space-x-3">
                                                                      <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                                                      <i :class="getResourceIcon(resource)" class="text-primary text-sm"></i>
                                                                      </div>
                                                                      <div>
                                                                      <h4 class="text-sm font-medium text-foreground capitalize" x-text="resource"></h4>
                                                                      <p class="text-xs text-muted-foreground" x-text="Array.isArray(permissions) ? permissions.length + ' ' + translations['permissions available'] : '0 ' + translations['permissions available']"></p>
                                                                      </div>
                                                                  </div>
                                                                  <div class="flex items-center space-x-2">
                                                                      <span class="text-xs text-muted-foreground" x-text="translations['Enable All']"></span>
                                                                      <label class="relative inline-flex items-center cursor-pointer">
                                                                      <input type="checkbox" 
                                                                              :checked="isAllPermissionsInGroupEnabled(resource)"
                                                                              @change="toggleAllPermissionsInGroup(resource, $event.target.checked)"
                                                                              class="sr-only peer">
                                                                      <div class="w-9 h-5 bg-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-background after:border-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                                                      </label>
                                                                  </div>
                                                              </div>
                                                              <div class="p-2 space-y-1">
                                                                  <template x-for="permission in (Array.isArray(permissions) ? permissions : [])" :key="permission">
                                                                      <div class="flex items-center justify-between px-2 py-1 rounded-lg hover:bg-muted/50 transition-colors">
                                                                          <div class="flex items-center space-x-3 flex-1">
                                                                              <div class="w-5 h-5 rounded bg-muted flex items-center justify-center">
                                                                                  <i class="fas fa-key text-xs text-muted-foreground"></i>
                                                                              </div>
                                                                              <div class="flex-1">
                                                                                  <div class="text-sm font-medium text-foreground capitalize" x-text="permission"></div>
                                                                                  <div class="text-xs text-muted-foreground" x-text="getPermissionDescription(permission)"></div>
                                                                              </div>
                                                                          </div>
                                                                          <div class="flex-shrink-0">
                                                                          <label class="relative inline-flex items-center cursor-pointer">
                                                                              <input type="checkbox" 
                                                                                  :name="`permissions[${resource}][]`" 
                                                                                  :value="permission" 
                                                                                  x-model="selectedPermissions[resource]"
                                                                                  class="sr-only peer">
                                                                              <div class="w-9 h-5 bg-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-background after:border-border after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                                                          </label>
                                                                          </div>
                                                                      </div>
                                                                  </template>
                                                              </div>
                                                          </div>
                                                      </template>
                                                  </div>

                                                  <!-- Hidden input for selected role -->
                                                  <input type="hidden" name="role" :value="selectedRole">
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Submit Buttons -->
    <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-2 gap-2 pt-6">
    <a href="<?= admin_url('users') ?>" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
        <i data-lucide="chevron-left" class="h-4 w-4 mr-2"></i>
        <?= __('back_to_list') ?>
    </a>
    <button type="submit" form="userForm" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
        <i data-lucide="save" class="h-4 w-4 mr-2"></i>
        <?= $isEdit ? __('submit_edit') : __('submit_add') ?>
    </button>
    </div>
      </div>
    </div>
    </form>
</div>

<!-- Include FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
function userForm() {
    return {
        activeTab: 'basic',
        showAdd: false,
        changePassword: false,
        roles: <?php echo json_encode($roles); ?>,
        adminPermissions: <?php echo json_encode($admin_permissions); ?>,
        selectedRole: '<?= $isEdit ? $user['role'] : 'member' ?>',
        selectedPermissions: {},
        userPermissions: <?php echo json_encode($user_permissions); ?>,
        isEditMode: <?= $isEdit ? 'true' : 'false' ?>,
        translations: {
            'Standard user role': '<?= __('Standard user role') ?>',
            'Permission access': '<?= __('Permission access') ?>',
            'Create new items': '<?= __('Create new items') ?>',
            'View and read items': '<?= __('View and read items') ?>',
            'Edit existing items': '<?= __('Edit existing items') ?>',
            'Remove items': '<?= __('Remove items') ?>',
            'Full management access': '<?= __('Full management access') ?>',
            'Publish content': '<?= __('Publish content') ?>',
            'Moderate content': '<?= __('Moderate content') ?>',
            'permissions available': '<?= __('permissions available') ?>',
            'Enable All': '<?= __('Enable All') ?>',
            'Permissions': '<?= __('Permissions') ?>',
            'Access Level': '<?= __('Access Level') ?>',
            'Full Access': '<?= __('Full Access') ?>',
            'High Access': '<?= __('High Access') ?>',
            'Medium Access': '<?= __('Medium Access') ?>',
            'Low Access': '<?= __('Low Access') ?>',
            'Minimal Access': '<?= __('Minimal Access') ?>'
        },
        formData: {
            username: '<?= $isEdit ? htmlspecialchars($user['username']) : '' ?>',
            fullname: '<?= $isEdit ? htmlspecialchars($user['fullname']) : '' ?>',
            email: '<?= $isEdit ? htmlspecialchars($user['email']) : '' ?>',
            phone: '<?= $isEdit ? htmlspecialchars($user['phone']) : '' ?>',
            password: '',
            password_repeat: '',
            status: '<?= $isEdit ? htmlspecialchars($user['status']) : 'active' ?>'
        },

        init() {
            // Lưu trữ role ban đầu nếu đang ở chế độ edit
            if (this.isEditMode && this.userPermissions) {
                this.userPermissions.originalRole = this.selectedRole;
            }
            
            this.updatePermissions(this.selectedRole);
            
            // Set default role if none selected
            if (!this.selectedRole && Object.keys(this.roles).length > 0) {
                this.selectedRole = Object.keys(this.roles)[0];
            }
        },

        setActiveRole(role) {
            // Cập nhật selectedRole
            this.selectedRole = role;
            
            // Kiểm tra xem có phải đang ở chế độ edit và có user permissions không
            const hasUserPermissions = this.isEditMode && this.userPermissions && Object.keys(this.userPermissions).length > 0;
            
            // Nếu đang ở chế độ edit và có user permissions, hỏi xác nhận
            if (hasUserPermissions) {
                if (confirm('<?= __('Switching role will reset permissions to default. Continue?') ?>')) {
                    this.isEditMode = false; // Tắt edit mode để load permissions mặc định
                    this.updatePermissions(role);
                } else {
                    // Nếu user không muốn thay đổi, revert lại role cũ
                    this.selectedRole = this.userPermissions.originalRole || role;
                    return;
                }
            } else {
                // Nếu không phải edit mode hoặc không có user permissions, cập nhật bình thường
                this.updatePermissions(role);
            }
        },
        
        getRoleIcon(role) {
            const icons = {
                'admin': 'fas fa-crown',
                'moderator': 'fas fa-user-shield',
                'author': 'fas fa-pen-fancy',
                'member': 'fas fa-user',
                'editor': 'fas fa-edit',
                'subscriber': 'fas fa-user-plus'
            };
            return icons[role.toLowerCase()] || 'fas fa-user';
        },
        
        getRoleColorClass(role) {
            const colors = {
                'admin': 'admin-color',
                'moderator': 'moderator-color',
                'author': 'author-color',
                'member': 'member-color',
                'editor': 'author-color',
                'subscriber': 'member-color'
            };
            return colors[role.toLowerCase()] || 'member-color';
        },
        
        getRoleDescription(role) {
            const descriptions = {
                'admin': 'Full system access and control',
                'moderator': 'Content moderation and user management',
                'author': 'Content creation and editing',
                'member': 'Basic user access',
                'editor': 'Content editing and publishing',
                'subscriber': 'Read-only access'
            };
            return descriptions[role.toLowerCase()] || this.translations['Standard user role'];
        },
        
        getResourceIcon(resource) {
            const icons = {
                'dashboard': 'fas fa-tachometer-alt',
                'users': 'fas fa-users',
                'posts': 'fas fa-file-alt',
                'pages': 'fas fa-file',
                'media': 'fas fa-images',
                'comments': 'fas fa-comments',
                'settings': 'fas fa-cog',
                'plugins': 'fas fa-plug',
                'themes': 'fas fa-palette'
            };
            return icons[resource.toLowerCase()] || 'fas fa-folder';
        },
        
        getPermissionDescription(permission) {
            if (typeof permission !== 'string') {
                return this.translations['Permission access'];
            }
            const descriptions = {
                'create': this.translations['Create new items'],
                'read': this.translations['View and read items'],
                'update': this.translations['Edit existing items'],
                'delete': this.translations['Remove items'],
                'manage': this.translations['Full management access'],
                'publish': this.translations['Publish content'],
                'moderate': this.translations['Moderate content']
            };
            return descriptions[permission.toLowerCase()] || this.translations['Permission access'];
        },
        
        updatePermissions(role) {
            // Reset selected permissions
            this.selectedPermissions = {};

            // Initialize permissions arrays
            for (let resource in this.adminPermissions) {
                if (this.adminPermissions.hasOwnProperty(resource)) {
                    this.selectedPermissions[resource] = [];
                }
            }

            // Nếu đang ở chế độ edit và có user permissions từ database
            if (this.isEditMode && this.userPermissions && Object.keys(this.userPermissions).length > 0) {
                // Load permissions thực tế từ database
                for (let resource in this.userPermissions) {
                    if (this.userPermissions.hasOwnProperty(resource)) {
                        this.selectedPermissions[resource] = [...this.userPermissions[resource]];
                    }
                }
            } else if (role && this.roles[role] && this.roles[role].permissions) {
                // Nếu không phải edit mode hoặc không có user permissions, dùng role mặc định
                const rolePermissions = this.roles[role].permissions;
                for (let resource in rolePermissions) {
                    if (rolePermissions.hasOwnProperty(resource)) {
                        this.selectedPermissions[resource] = [...rolePermissions[resource]];
                    }
                }
            }
        },
        
        isAllPermissionsInGroupEnabled(resource) {
            if (!this.selectedPermissions[resource] || !this.adminPermissions[resource] || !Array.isArray(this.adminPermissions[resource])) {
                return false;
            }
            return this.adminPermissions[resource].every(permission => 
                this.selectedPermissions[resource].includes(permission)
            );
        },
        
        toggleAllPermissionsInGroup(resource, enabled) {
            if (!this.selectedPermissions[resource]) {
                this.selectedPermissions[resource] = [];
            }
            
            if (enabled && Array.isArray(this.adminPermissions[resource])) {
                this.selectedPermissions[resource] = [...this.adminPermissions[resource]];
            } else {
                this.selectedPermissions[resource] = [];
            }
        },

        resetToRoleDefaults() {
            if (this.selectedRole && this.roles[this.selectedRole] && this.roles[this.selectedRole].permissions) {
                const rolePermissions = this.roles[this.selectedRole].permissions;
                
                // Reset về permissions mặc định của role
                for (let resource in this.adminPermissions) {
                    if (this.adminPermissions.hasOwnProperty(resource)) {
                        if (rolePermissions[resource]) {
                            this.selectedPermissions[resource] = [...rolePermissions[resource]];
                        } else {
                            this.selectedPermissions[resource] = [];
                        }
                    }
                }
            }
        },
        
        getGrantedPermissionsCount() {
            let count = 0;
            for (let resource in this.selectedPermissions) {
                if (this.selectedPermissions[resource]) {
                    count += this.selectedPermissions[resource].length;
                }
            }
            return count;
        },
        
        getTotalPermissionsCount() {
            let count = 0;
            for (let resource in this.adminPermissions) {
                count += this.adminPermissions[resource].length;
            }
            return count;
        },
        
        getPermissionPercentage() {
            const total = this.getTotalPermissionsCount();
            const granted = this.getGrantedPermissionsCount();
            return total > 0 ? Math.round((granted / total) * 100) : 0;
        },
        
        getPermissionLevelClass() {
            const percentage = this.getPermissionPercentage();
            if (percentage >= 90) return 'permission-level-full';
            if (percentage >= 70) return 'permission-level-high';
            if (percentage >= 40) return 'permission-level-medium';
            if (percentage >= 20) return 'permission-level-low';
            return 'permission-level-minimal';
        },
        
        getPermissionLevelText() {
            const percentage = this.getPermissionPercentage();
            if (percentage >= 90) return this.translations['Full Access'];
            if (percentage >= 70) return this.translations['High Access'];
            if (percentage >= 40) return this.translations['Medium Access'];
            if (percentage >= 20) return this.translations['Low Access'];
            return this.translations['Minimal Access'];
        }
    }
}
</script>

<?php Render::block('Backend\\Footer', ['layout' => 'default']); ?>
