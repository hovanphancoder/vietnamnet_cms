<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS Full Form Installer</title>
  
  <!-- Load built assets from Vue build output -->
  <link rel="stylesheet" href="dist/css/main.css">
</head>
<body>
  <div id="app"></div>
  
  <!-- Pass system requirements and locales data to Vue -->
  <script>
    window.systemRequirements = <?php
      // Execute the PHP file to get requirements
      ob_start();
      include __DIR__ . '/check_requirements.php';
      $requirementsOutput = ob_get_clean();
      
      // Try to decode the JSON outputs
      $requirementsData = json_decode($requirementsOutput, true);
      
      if ($requirementsData === null) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Invalid requirements data format'
        ]);
      } else {
        echo json_encode($requirementsData);
      }
    ?>;
    
    window.availableLanguages = <?php
      // Load available languages from locales.php
      ob_start();
      include __DIR__ . '/locales.php';
      $output = ob_get_clean();
      
      // Try to decode the JSON output
      $decoded = json_decode($output, true);
      if ($decoded === null) {
        // Fallback to default languages
        echo json_encode([
          [
            'code' => 'en',
            'name' => 'English',
            'flag' => 'https://flagcdn.com/w20/us.png',
            'speakers' => '1.5 billion'
          ]
        ]);
      } else {
        echo $output;
      }
    ?>;
  </script>
  
  <!-- Load built JavaScript from Vue build output -->
  <script type="module" src="dist/js/main.js"></script>
</body>
</html>
