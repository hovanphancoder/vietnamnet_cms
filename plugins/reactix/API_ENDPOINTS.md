# Reactix Plugin API Endpoints

## Tổng quan
Plugin Reactix cung cấp các API endpoints để quản lý tương tác của người dùng với posttype, bao gồm comment, rating và like.

## Base URL
```
/api/reactix
```

## Authentication
API sử dụng token authentication hoặc session authentication:
- **Token**: Gửi trong header `Authorization: Bearer {token}`
- **Session**: Sử dụng session của user đã đăng nhập

## Endpoints

### 1. Lấy danh sách comment

#### GET `/api/reactix/get_comment/{posttype}/{post_id}/{paged}`
Lấy danh sách comment của một post cụ thể với phân trang.

**Parameters:**
- `posttype` (string): Loại post (ví dụ: 'post', 'product')
- `post_id` (number): ID của post
- `paged` (number): Số trang (mặc định: 1)

**Response:**
```json
{
  "success": true,
  "message": "Comment retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Comment on Post Title",
        "content": "Comment content",
        "rating": 5,
        "user_id": 1,
        "post_id": 123,
        "posttype": "post",
        "par_comment": 0,
        "like_count": 0,
        "created_at": "2024-01-01 00:00:00",
        "user": {
          "id": 1,
          "username": "user1",
          "email": "user1@example.com"
        },
        "children": [
          {
            "id": 2,
            "content": "Reply content",
            "user_id": 2,
            "par_comment": 1,
            "user": {
              "id": 2,
              "username": "user2"
            }
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 5,
      "total": 10
    }
  }
}
```

### 2. Thêm comment/rating

#### POST `/api/reactix/comment`
Thêm comment hoặc rating cho một post.

**Request Body:**
```json
{
  "rating": 5,
  "par_comment": 0,
  "content": "Comment content",
  "posttype": "post",
  "post_id": 123
}
```

**Parameters:**
- `rating` (number, optional): Điểm đánh giá từ 0-5 (0 = chỉ comment, 1-5 = rating + comment)
- `par_comment` (number, optional): ID comment cha (0 = comment gốc)
- `content` (string, optional): Nội dung comment
- `posttype` (string, required): Loại post
- `post_id` (number, required): ID của post

**Response Success:**
```json
{
  "success": true,
  "message": "Comment added successfully",
  "data": {
    "id": 1,
    "title": "Comment on Post Title",
    "slug": "comment-on-post-title",
    "user_id": 1,
    "rating": 5,
    "content": "Comment content",
    "posttype": "post",
    "post_id": 123,
    "created_at": "2024-01-01 00:00:00"
  }
}
```

**Response Error:**
```json
{
  "success": false,
  "message": "Invalid post id",
  "data": [],
  "code": 400
}
```

### 3. Like comment

#### GET `/api/reactix/like_comment/{id}`
Tăng số lượt like của một comment.

**Parameters:**
- `id` (number): ID của comment

**Response Success:**
```json
{
  "success": true,
  "message": "Comment liked successfully",
  "data": {
    "like_count": 5
  }
}
```

**Response Error:**
```json
{
  "success": false,
  "message": "Comment not found",
  "data": [],
  "code": 404
}
```

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Invalid post id | ID post không hợp lệ |
| 400 | Invalid post type | Loại post không hợp lệ |
| 400 | Invalid rating | Rating không hợp lệ (phải từ 0-5) |
| 400 | Comment not added | Không thể thêm comment |
| 404 | Post not found | Không tìm thấy post |
| 404 | Comment not found | Không tìm thấy comment |
| 500 | Internal server error | Lỗi server |

## Lưu ý

1. **Rating System**: 
   - Rating 0 = chỉ comment, không cập nhật điểm trung bình
   - Rating 1-5 = comment + cập nhật điểm trung bình của post

2. **Comment Structure**:
   - Comment gốc có `par_comment = 0`
   - Reply comment có `par_comment = ID_comment_cha`

3. **Pagination**:
   - Mỗi trang hiển thị tối đa 5 comment
   - Comment được sắp xếp theo thời gian tạo mới nhất

4. **User Information**:
   - Mỗi comment đều có thông tin user (nếu user_id > 0)
   - Anonymous comment có user_id = 0

## Ví dụ sử dụng

### JavaScript/Fetch
```javascript
// Lấy comment
fetch('/api/reactix/get_comment/post/123/1')
  .then(response => response.json())
  .then(data => console.log(data));

// Thêm comment
fetch('/api/reactix/comment', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer your-token'
  },
  body: JSON.stringify({
    rating: 5,
    content: 'Great post!',
    posttype: 'post',
    post_id: 123
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### cURL
```bash
# Lấy comment
curl -X GET "https://yourdomain.com/api/reactix/get_comment/post/123/1"

# Thêm comment
curl -X POST "https://yourdomain.com/api/reactix/comment" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "rating": 5,
    "content": "Great post!",
    "posttype": "post",
    "post_id": 123
  }'
```
