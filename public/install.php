<?php
// public/install.php
// Process database connection test
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'database' && isset($_POST['db_host'])) {
        $host   = $_POST['db_host'];
        $port   = $_POST['db_port'];
        $dbname = $_POST['db_name'];
        $user   = $_POST['db_user'];
        $pass   = $_POST['db_pass'];
        
        // Attempt to establish a database connection using mysqli
        try{
            $mysqli = new mysqli($host, $user, $pass, $dbname, $port);
            if ($mysqli->connect_error) {
                $result = ["status" => "error", "message" => "Connection failed: " . $mysqli->connect_error];
            } else {
                $result = ["status" => "success", "message" => "Connection successful"];
            }
        }catch(Exception $ex){
            $result = ["status" => "error", "message" => "Connection failed!"];
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }elseif ($_POST['action'] == 'email') {
        $mail_host     = $_POST['mail_host'];
        $mail_port     = $_POST['mail_port'];
        $mail_username = isset($_POST['mail_username']) ? $_POST['mail_username'] : '';
        $mail_password = isset($_POST['mail_password']) ? $_POST['mail_password'] : '';
        
        $timeout = 10;
        $errno   = 0;
        $errstr  = '';
        
        // Open connection to the SMTP server
        $fp = @fsockopen($mail_host, $mail_port, $errno, $errstr, $timeout);
        if (!$fp) {
                $result = ["status" => "error", "message" => "Connection failed: $errstr ($errno)"];
        } else {
                // Read the server's greeting (should start with 220)
                $response = fgets($fp, 515);
                if (substr($response, 0, 3) != "220") {
                    $result = ["status" => "error", "message" => "SMTP error: $response"];
                } else {
                    // Send EHLO command
                    fputs($fp, "EHLO example.com\r\n");
                    $response = "";
                    while ($line = fgets($fp, 515)) {
                        $response .= $line;
                        if (substr($line, 3, 1) == " ") break;
                    }
                    if (substr($response, 0, 3) != "250") {
                        $result = ["status" => "error", "message" => "EHLO failed: $response"];
                    } else {
                        // If SMTP username/password provided, perform authentication
                        if (!empty($mail_username) && !empty($mail_password)) {
                            fputs($fp, "AUTH LOGIN\r\n");
                            $response = fgets($fp, 515);
                            if (substr($response, 0, 3) != "334") {
                                $result = ["status" => "error", "message" => "AUTH LOGIN not accepted: $response"];
                            } else {
                                // Send base64 encoded username
                                fputs($fp, base64_encode($mail_username) . "\r\n");
                                $response = fgets($fp, 515);
                                if (substr($response, 0, 3) != "334") {
                                    $result = ["status" => "error", "message" => "Username not accepted: $response"];
                                } else {
                                    // Send base64 encoded password
                                    fputs($fp, base64_encode($mail_password) . "\r\n");
                                    $response = fgets($fp, 515);
                                    if (substr($response, 0, 3) != "235") {
                                        $result = ["status" => "error", "message" => "Authentication failed: $response"];
                                    } else {
                                        $result = ["status" => "success", "message" => "SMTP authentication successful"];
                                    }
                                }
                            }
                        } else {
                            $result = ["status" => "success", "message" => "SMTP connection successful (no authentication)"];
                        }
                    }
                }
                // Send QUIT and close the connection
                fputs($fp, "QUIT\r\n");
                fclose($fp);
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    print_r($_POST);
    exit();
}

// Require configuration files
require_once __DIR__ . '/../application/Config/Languages.php';
$config = require_once __DIR__ . '/../application/Config/Config.php';
$roles  = require_once __DIR__ . '/../application/Config/Roles.php';
?><!DOCTYPE html>
<html lang="<?php echo APP_LANG; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMS Installation Wizard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style type="text/tailwindcss">
    @layer components {
      .input-field {
        @apply mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 shadow-sm placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-blue-500;
      }
      .form-label {
        @apply block text-sm font-medium text-gray-700;
      }
      .btn-primary {
        @apply inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-6 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500;
      }
      .btn-default {
        @apply inline-flex items-center justify-center rounded-md border border-transparent bg-gray-600 px-6 py-2 text-base font-medium text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500;
      }
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="container mx-auto px-4 py-10">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-xl p-8">
      <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">CMS Installation Wizard</h1>
        <p class="mt-2 text-gray-600">Follow the steps to set up your content management system</p>
      </div>
      <form id="installForm" method="POST" action="">
        <!-- Step 1: Database Configuration -->
        <div id="step-1" class="step">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Step 1: Database Configuration</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="form-label">DB Host</label>
              <input type="text" name="db_host" id="db_host" class="input-field" placeholder="localhost" required value="<?php echo htmlspecialchars($config['db']['db_host']); ?>">
            </div>
            <div>
              <label class="form-label">DB Port</label>
              <input type="number" name="db_port" id="db_port" class="input-field" placeholder="3306" required value="<?php echo htmlspecialchars($config['db']['db_port']); ?>">
            </div>
            <div>
              <label class="form-label">DB Name</label>
              <input type="text" name="db_name" id="db_name" class="input-field" placeholder="cms_database" required value="<?php echo htmlspecialchars($config['db']['db_database']); ?>">
            </div>
            <div>
              <label class="form-label">DB Username</label>
              <input type="text" name="db_user" id="db_user" class="input-field" placeholder="root" required value="<?php echo htmlspecialchars($config['db']['db_username']); ?>">
            </div>
            <div>
              <label class="form-label">DB Password</label>
              <input type="password" name="db_pass" id="db_pass" class="input-field" placeholder="••••••" autocomplete="new-password" value="<?php echo htmlspecialchars($config['db']['db_password']); ?>">
            </div>
            <!-- Charset as select dropdown -->
            <div>
              <label class="form-label">Charset</label>
              <select name="db_charset" id="db_charset" class="input-field">
                <?php
                $charsets = [
                  'utf8mb4' => 'utf8mb4',
                  'utf8'    => 'utf8',
                  'latin1'  => 'latin1'
                ];
                foreach ($charsets as $value) {
                  $selected = ($value === $config['db']['db_charset']) ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$value</option>";
                }
                ?>
              </select>
            </div>
            <!-- Collation as select dropdown -->
            <div>
              <label class="form-label">Collation</label>
              <select name="db_collation" id="db_collation" class="input-field">
                <?php
                $collations = [
                  'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                  'utf8mb4_general_ci' => 'utf8mb4_general_ci',
                  'utf8_general_ci'      => 'utf8_general_ci',
                  'latin1_swedish_ci'    => 'latin1_swedish_ci'
                ];
                foreach ($collations as $value) {
                  $selected = ($value === $config['db']['db_collate']) ? 'selected' : '';
                  echo "<option value=\"$value\" $selected>$value</option>";
                }
                ?>
              </select>
            </div>
            <!-- Test Connection Button -->
            <div>
            <label class="form-label">Test Connection</label>
              <button type="button" id="testConnectionBtn" class="btn-primary">Test Connection</button>
              <span id="testConnectionStatus" class="ml-4 text-sm"></span>
            </div>
          </div>
        </div>

        <!-- Step 2: Website Configuration -->
        <div id="step-2" class="step hidden">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Step 2: Website Configuration</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="form-label">Brand Website</label>
              <input type="text" name="site_name" class="input-field" placeholder="Brand Website" required value="<?php echo htmlspecialchars($config['app']['app_name']); ?>">
            </div>
            <div>
              <label class="form-label">Website URL</label>
              <?php
                if (!empty($config['app']['app_url'])){
                    $site_url = $config['app']['app_url'];
                } else {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $site_url = $protocol . $_SERVER['HTTP_HOST'] . '/';
                }
              ?>
              <input type="url" name="site_url" class="input-field" placeholder="https://example.com" required value="<?= $site_url; ?>">
            </div>
            <div>
              <label class="form-label">Site Email</label>
              <input type="email" name="site_email" class="input-field" placeholder="admin@example.com" required value="<?php echo htmlspecialchars($config['email']['mail_from_address']); ?>">
            </div>
            <div>
              <?php
              // Scan themes directory for available themes
              $themes_dir = __DIR__ . '/../themes';
              $theme_dirs = array_filter(scandir($themes_dir), function($item) use ($themes_dir) {
                  return is_dir($themes_dir . '/' . $item) && !in_array($item, ['.', '..']);
              });
              ?>
              <label class="form-label">Theme Name</label>
              <select name="theme_name" class="input-field" required>
                <?php foreach ($theme_dirs as $theme): 
                        $selected = ($theme === APP_THEME_NAME) ? 'selected' : '';
                ?>
                  <option value="<?php echo $theme; ?>" <?php echo $selected; ?>><?php echo $theme; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Timezone</label>
              <select name="site_timezone" class="input-field" required>
                <option value="">Select Timezone</option>
                <?php
                  $timezones = timezone_identifiers_list();
                  sort($timezones);
                  foreach ($timezones as $tz) {
                    $selected = (isset($config['app']['app_timezone']) && $config['app']['app_timezone'] === $tz) ? 'selected' : '';
                    echo "<option value=\"$tz\" $selected>$tz</option>";
                  }
                ?>
              </select>
            </div>
            <div>
              <label class="form-label">Language</label>
              <select name="site_language" class="input-field" required>
                <option value="">Select Language</option>
                <?php
                foreach (APP_LANGUAGES as $lang => $langData):
                  $selected = ($lang === APP_LANG_DF) ? 'selected' : '';
                ?>
                  <option value="<?php echo $lang; ?>" <?php echo $selected; ?>><?php echo strtoupper($langData['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Security: AppId</label>
              <input type="text" name="app_id" class="input-field" placeholder="123456" required value="<?php echo htmlspecialchars($config['security']['app_id']); ?>">
            </div>
            <div>
              <label class="form-label">Security: Secret</label>
              <input type="text" name="app_secret" class="input-field" placeholder="PassSecurity@*" required value="<?php echo htmlspecialchars($config['security']['app_secret']); ?>">
            </div>
            <div class="md:col-span-2">
              <label class="form-label">Website Title</label>
              <input name="site_title" class="input-field" placeholder="Website Title" />
            </div>
            <div class="md:col-span-2">
              <label class="form-label">Website Description (Optional)</label>
              <textarea name="site_description" rows="3" class="input-field" placeholder="Short description about your website"></textarea>
            </div>
          </div>
        </div>

        <!-- Step 3: Admin Account & Advanced Configuration -->
        <div id="step-3" class="step hidden">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Step 3: Admin Account & Advanced Configuration</h2>
          
          <!-- Section 1: Admin Account -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Admin Account</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Username</label>
                <input type="text" name="admin_username" class="input-field" placeholder="admin" required value="admin">
              </div>
              <div>
                <label class="form-label">Admin Email</label>
                <input type="email" name="admin_email" class="input-field" placeholder="admin@example.com" required value="<?php echo htmlspecialchars($config['email']['mail_from_address']); ?>">
              </div>
              <div>
                <label class="form-label">Display Name</label>
                <input type="text" name="admin_display_name" class="input-field" placeholder="Administrator" value="Administrator">
              </div>
              <div>
                <label class="form-label">Avatar URL</label>
                <input type="url" name="admin_avatar" class="input-field" placeholder="https://example.com/avatar.png">
              </div>
              <div>
                <label class="form-label">Password</label>
                <input type="password" name="admin_password" class="input-field" placeholder="••••••" required>
              </div>
              <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="admin_password_confirmation" class="input-field" placeholder="••••••" required>
              </div>
            </div>
          </div>
          
          <!-- Section 2: Email SMTP Configuration -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Email SMTP Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Mail Mailer</label>
                <input type="text" name="mail_mailer" class="input-field" placeholder="smtp" required value="<?php echo htmlspecialchars($config['email']['mail_mailer']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Host</label>
                <input type="text" name="mail_host" id="mail_host" class="input-field" placeholder="smtp.gmail.com" required value="<?php echo htmlspecialchars($config['email']['mail_host']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Port</label>
                <input type="number" name="mail_port" id="mail_port" class="input-field" placeholder="587" required value="<?php echo htmlspecialchars($config['email']['mail_port']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Username</label>
                <input type="text" name="mail_username" class="input-field" placeholder="your-email@example.com" required value="<?php echo htmlspecialchars($config['email']['mail_username']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Password</label>
                <input type="password" name="mail_password" class="input-field" placeholder="••••••" required value="<?php echo htmlspecialchars($config['email']['mail_password']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Encryption</label>
                <input type="text" name="mail_encryption" class="input-field" placeholder="tls" required value="<?php echo htmlspecialchars($config['email']['mail_encryption']); ?>">
              </div>
              <div>
                <label class="form-label">Mail Charset</label>
                <input type="text" name="mail_charset" class="input-field" placeholder="UTF-8" required value="<?php echo htmlspecialchars($config['email']['mail_charset']); ?>">
              </div>
              <div>
                <label class="form-label">Mail From Address</label>
                <input type="email" name="mail_from_address" class="input-field" placeholder="noreply@example.com" required value="<?php echo htmlspecialchars($config['email']['mail_from_address']); ?>">
              </div>
              <div>
                <label class="form-label">Mail From Name</label>
                <input type="text" name="mail_from_name" class="input-field" placeholder="CMSFullForm" required value="<?php echo htmlspecialchars($config['email']['mail_from_name']); ?>">
              </div>
              <!-- Test Email Button (does not affect Next button) -->
              <div>
                <label class="form-label">Test Email Connection</label>
                <button type="button" id="testEmailBtn" class="btn-primary">Test Email</button>
                <span id="testEmailStatus" class="ml-4 text-sm"></span>
              </div>
            </div>
          </div>
          
          <!-- Section 3: Files Configuration -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Files Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Uploads Path</label>
                <input type="text" name="files_path" class="input-field" placeholder="writeable/uploads" required value="<?php echo htmlspecialchars($config['files']['path']); ?>">
              </div>
              <div>
                <label class="form-label">Allowed File Types (CSV, comma-separated)</label>
                <input type="text" name="allowed_types" class="input-field" placeholder="jpg,jpeg,png,gif,webp,pdf,docx,doc,xls,xlsx,csv,ppt,pptx,txt,rar,zip,iso,mp3,wav,mkv,mp4" required value="<?php echo htmlspecialchars(implode(',', $config['files']['allowed_types'])); ?>">
              </div>
              <div>
                <label class="form-label">Max File Size (bytes)</label>
                <input type="number" name="max_file_size" class="input-field" placeholder="10485760" required value="<?php echo htmlspecialchars($config['files']['max_file_size']); ?>">
              </div>
              <div>
                <label class="form-label">Max Files per Upload</label>
                <input type="number" name="max_file_count" class="input-field" placeholder="10" required value="<?php echo htmlspecialchars($config['files']['max_file_count']); ?>">
              </div>
              <div>
                <label class="form-label">Items per Page in File Manager</label>
                <input type="number" name="files_limit" class="input-field" placeholder="40" required value="<?php echo htmlspecialchars($config['files']['limit']); ?>">
              </div>
            </div>
          </div>
          
        </div>

        <!-- Step 4: Roles Configuration -->
        <div id="step-4" class="step hidden">
          <h2 class="text-2xl font-semibold text-gray-800 mb-4">Step 4: Roles Configuration</h2>
          <?php
            // Use admin modules for full set of options
            $adminModules = isset($roles['admin']) ? $roles['admin'] : [];
            // Loop through each role in the configuration
            foreach ($roles as $roleName => $roleConfig):
          ?>
            <div class="mb-6 border-b pb-4">
              <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo ucfirst($roleName); ?></h3>
              <!-- Grid layout with 2 columns -->
              <div class="grid grid-cols-2 gap-4">
                <?php foreach ($adminModules as $module => $adminActions): ?>
                  <div>
                    <p class="text-gray-600 font-medium mb-1"><?php echo $module; ?>:</p>
                    <?php foreach ($adminActions as $action):
                      $checked = (isset($roles[$roleName][$module]) && in_array($action, $roles[$roleName][$module])) ? 'checked' : '';
                    ?>
                      <label class="inline-flex items-center">
                        <input type="checkbox" name="roles[<?php echo $roleName; ?>][<?php echo $module; ?>][]" value="<?php echo $action; ?>" <?php echo $checked; ?> class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        <span class="ml-2 text-gray-700"><?php echo $action; ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between border-t pt-6 mt-6">
          <button id="prevBtn" type="button" class="btn-primary btn-default" disabled>← Back</button>
          <button id="nextBtn" type="button" class="btn-primary btn-default" disabled>Next →</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Wizard steps (4 steps)
    const steps = Array.from(document.querySelectorAll('.step'));
    let currentStep = 0;

    // Show current step and update navigation buttons
    const showStep = (index) => {
      steps.forEach((step, idx) => {
        step.classList.toggle('hidden', idx !== index);
      });
      // Disable Back button on first step
      if (index === 0) {
        document.getElementById('prevBtn').setAttribute('disabled', 'disabled');
      } else {
        document.getElementById('prevBtn').removeAttribute('disabled');
      }
      // Enable Next button for steps > 0; for step 1, Next remains disabled until connection test passes.
      if (index === 0 && !nextEnabled) {
        document.getElementById('nextBtn').setAttribute('disabled', 'disabled');
      } else {
        document.getElementById('nextBtn').removeAttribute('disabled');
      }
      document.getElementById('nextBtn').textContent = index === steps.length - 1 ? 'Install' : 'Next →';
    };

    // Initially, Next button is disabled until test connection passes
    let nextEnabled = false;
    showStep(currentStep);

    // Navigation buttons event listeners
    document.getElementById('nextBtn').addEventListener('click', () => {
      if (currentStep < steps.length - 1) {
        currentStep++;
        showStep(currentStep);
      } else {
        document.getElementById('installForm').submit();
      }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
      }
    });

    // Test Connection Button event listener
    document.getElementById('testConnectionBtn').addEventListener('click', () => {
      // Collect database connection parameters
      const data = new FormData();
      data.append('db_host', document.getElementById('db_host').value);
      data.append('db_port', document.getElementById('db_port').value);
      data.append('db_name', document.getElementById('db_name').value);
      data.append('db_user', document.getElementById('db_user').value);
      data.append('db_pass', document.getElementById('db_pass').value);
      data.append('action', 'database');

      // Send AJAX request using fetch API
      fetch('', {
        method: 'POST',
        body: data
      })
      .then(response => response.json())
      .then(result => {
        const statusSpan = document.getElementById('testConnectionStatus');
        if (result.status === 'success') {
          statusSpan.textContent = result.message;
          statusSpan.className = "ml-4 text-green-600";
          // Enable Next button if connection test is successful
          nextEnabled = true;
          document.getElementById('nextBtn').removeAttribute('disabled');
          document.getElementById('nextBtn').classList.remove('btn-default');
        } else {
          statusSpan.textContent = result.message;
          statusSpan.className = "ml-4 text-red-600";
          nextEnabled = false;
          document.getElementById('nextBtn').setAttribute('disabled', 'disabled');
          document.getElementById('nextBtn').classList.add('btn-default');
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    });

    // Test Email Button event listener
    document.getElementById('testEmailBtn').addEventListener('click', () => {
        // Collect email SMTP parameters
        const data = new FormData();
        data.append('mail_host', document.getElementById('mail_host').value);
        data.append('mail_port', document.getElementById('mail_port').value);
        data.append('mail_username', document.querySelector('input[name="mail_username"]').value);
        data.append('mail_password', document.querySelector('input[name="mail_password"]').value);
        data.append('action', 'email');

        // Send AJAX request using fetch API for email test
        fetch('', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            const emailStatusSpan = document.getElementById('testEmailStatus');
            if (result.status === 'success') {
            emailStatusSpan.textContent = result.message;
            emailStatusSpan.className = "ml-4 text-green-600";
            } else {
            emailStatusSpan.textContent = result.message;
            emailStatusSpan.className = "ml-4 text-red-600";
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
  </script>
</body>
</html>