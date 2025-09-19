# Helper Functions Documentation

## Database Helper Functions
- `db_query($sql, $params)` - Execute database query with parameters
- `db_fetch_assoc($result)` - Fetch associative array from result
- `db_fetch_object($result)` - Fetch object from result
- `db_escape_string($string)` - Escape string for SQL injection prevention
- `db_last_insert_id()` - Get last inserted ID

## File Helper Functions
- `file_upload($file, $destination)` - Upload file to destination
- `file_delete($path)` - Delete file from path
- `file_exists($path)` - Check if file exists
- `file_size($path)` - Get file size in bytes
- `file_extension($filename)` - Get file extension

## String Helper Functions
- `str_slug($string)` - Convert string to URL-friendly slug
- `str_limit($string, $length)` - Limit string length
- `str_random($length)` - Generate random string
- `str_contains($haystack, $needle)` - Check if string contains substring
- `str_starts_with($string, $prefix)` - Check if string starts with prefix

## Array Helper Functions
- `array_get($array, $key, $default)` - Get array value with default
- `array_set($array, $key, $value)` - Set array value
- `array_has($array, $key)` - Check if array has key
- `array_first($array)` - Get first element of array
- `array_last($array)` - Get last element of array

## Validation Helper Functions
- `validate_email($email)` - Validate email format
- `validate_phone($phone)` - Validate phone number
- `validate_url($url)` - Validate URL format
- `validate_required($value)` - Check if value is required
- `validate_min_length($value, $min)` - Check minimum length
