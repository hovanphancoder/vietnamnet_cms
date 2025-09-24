# Code Examples Documentation

## PHP Examples

### Database Query
```php
$users = $this->db->table('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->get();
```

### File Upload
```php
$file = $request->file('document');
$filename = $file->store('uploads/documents');
```

### API Response
```php
return response()->json([
    'success' => true,
    'data' => $result,
    'message' => 'Operation completed successfully'
]);
```

## JavaScript Examples

### API Call
```javascript
fetch('/api/users')
    .then(response => response.json())
    .then(data => console.log(data));
```
