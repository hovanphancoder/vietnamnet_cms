# System Libraries Documentation

## Database Library
### Usage Examples
```php
// Initialize database connection
$db = new Database();
$db->connect(DB_HOST, DB_NAME, DB_USER, DB_PASS);

// Execute query
$result = $db->query("SELECT * FROM users WHERE status = ?", ['active']);

// Fetch results
while ($row = $db->fetch_assoc($result)) {
    echo $row['username'];
}
```

## File Upload Library
### Usage Examples
```php
// Initialize upload handler
$upload = new FileUpload();

// Set upload options
$upload->setMaxSize(5 * 1024 * 1024); // 5MB
$upload->setAllowedTypes(['jpg', 'png', 'pdf']);

// Process upload
if ($upload->process($_FILES['document'])) {
    $filename = $upload->getFilename();
    echo "File uploaded: " . $filename;
}
```

## Image Processing Library
### Usage Examples
```php
// Initialize image processor
$image = new ImageProcessor();

// Load image
$image->load('input.jpg');

// Resize image
$image->resize(800, 600);

// Save processed image
$image->save('output.jpg', 80); // 80% quality
```

## Cache Library
### Usage Examples
```php
// Initialize cache
$cache = new Cache();

// Set cache value
$cache->set('user_data', $userData, 3600); // 1 hour

// Get cache value
$userData = $cache->get('user_data');

// Delete cache
$cache->delete('user_data');
```

## Logger Library
### Usage Examples
```php
// Initialize logger
$logger = new Logger();

// Log different levels
$logger->info('User logged in successfully');
$logger->warning('High memory usage detected');
$logger->error('Database connection failed');
$logger->debug('Query executed: ' . $sql);
```
