# 🧩 CMS Admin Dashboard Component Plan

## 📝 Tổng quan
Tài liệu này mô tả danh sách các component có thể được hiển thị trong trang dashboard của hệ thống quản trị CMS. Mỗi component được thiết kế dạng module, cho phép người dùng:
- Kéo thả để sắp xếp lại.
- Tùy chọn bật/tắt theo nhu cầu.
- Tự động cập nhật dữ liệu theo thời gian thực hoặc theo cron.

---

## 📊 Nhóm tổng quan hệ thống

### 1. Website Overview
- Thông tin domain, logo, slogan, tên site.
- Trạng thái HTTPS, CDN, cache.

### 2. System Status
- Tình trạng PHP, DB, Web server.
- Dung lượng ổ đĩa, RAM, CPU.

### 3. Uptime & Health
- Tình trạng cronjob, queue, background job.
- Ping, uptime, phản hồi API nội bộ.

### 4. Security Warnings
- Lỗi permission, file config chưa bảo mật, plugin lỗi thời.

### 5. Multi-language Summary
- Số ngôn ngữ hỗ trợ, ngôn ngữ mặc định, % bài viết đã dịch.

---

## 📈 Nhóm thống kê nội dung

### 6. Post Stats
- Tổng số bài viết, draft, pending.
- Theo từng post type (blog, news, doc...).

### 7. Page Stats
- Tổng page, đang hiển thị, ẩn.

### 8. Comments & Reviews
- Tổng bình luận, số chờ duyệt, spam.

### 9. Post by Language
- Phân loại bài viết theo ngôn ngữ.

### 10. Popular Content
- Top 10 bài xem nhiều trong tuần/tháng.

### 11. Post Growth Chart
- Biểu đồ tăng trưởng số lượng bài theo ngày/tuần.

### 12. Missing Translations
- Danh sách post chưa có bản dịch ở ngôn ngữ phụ.

---

## 👥 Nhóm liên quan đến người dùng

### 13. User Overview
- Tổng số user, số online, số bị khóa.

### 14. User Role Distribution
- Số user theo vai trò: admin, editor, user, seller...

### 15. Online Users Now
- Ai đang hoạt động (live tracking nếu có).

### 16. Activity Log
- Hành vi gần đây: đăng nhập, sửa bài, cài plugin...

### 17. New Registrations
- Tài khoản vừa đăng ký.

---

## 📦 Nhóm quản lý dữ liệu & media

### 18. Media Library Stats
- Tổng số ảnh/video/tệp, dung lượng chiếm.

### 19. Storage Usage
- Phân tích dung lượng theo thư mục: uploads, logs, cache.

### 20. Database Size
- Tổng dung lượng, dung lượng theo bảng.

### 21. Backup Status
- Trạng thái bản backup gần nhất, backup kế tiếp.

---

## 📢 Thông báo, hệ thống & tiện ích

### 22. System Notifications
- Thông báo lỗi, trạng thái hệ thống.

### 23. Plugin / Module Updates
- Liệt kê plugin có bản cập nhật.

### 24. Theme Updates
- Tình trạng cập nhật theme.

### 25. Recent Edits / Updates
- Post vừa được chỉnh sửa gần đây.

### 26. Quick Draft
- Form nhập bài mới nhanh.

### 27. Todo Notes / Reminders
- Sticky notes cho admin tự ghi chú.

---

## 💬 Giao tiếp & thống kê ngoài

### 28. Chat Inbox / Support
- Tin nhắn mới từ người dùng (nếu có contact/chat).

### 29. Feedback & Survey
- Tổng hợp góp ý, survey user.

### 30. Analytics Summary
- Số truy cập, bounce rate từ GA hoặc nội bộ.

### 31. Top Referrers
- Danh sách nguồn traffic (facebook, google...).

---

## ⚙️ Cài đặt nhanh và shortcut

### 32. Site Settings Shortcut
- Link nhanh tới cài đặt chung.

### 33. Navigation Editor
- Link nhanh chỉnh sửa menu.

### 34. Language & Translation Access
- Link nhanh tới màn quản lý bản dịch.

### 35. Clear Cache Button
- Nút xoá cache hệ thống/ngôn ngữ/API.

### 36. Flush Rewrite Rules
- Nút reset permalink (nếu CMS cần).

---

## ✅ Nâng cao (tuỳ chọn)

### 37. Scheduled Tasks
- Danh sách cronjob và tác vụ nền.

### 38. SEO Overview
- Điểm SEO tổng quan, các bài thiếu meta.

### 39. Broken Links
- Liệt kê liên kết 404 hoặc lỗi tải trang.

### 40. Keyword Ranking
- Nếu có tích hợp công cụ từ khoá.

### 41. Export/Import Tools
- Công cụ xuất nhập cài đặt cấu hình.

---

## 🔄 Cấu trúc dữ liệu mỗi component
```json
{
  "id": "post_stats",
  "name": "Post Stats",
  "group": "content",
  "icon": "📈",
  "enabled_by_default": true,
  "description": "Hiển thị tổng quan số lượng bài viết theo loại."
}
```

---

## 🛠️ Lưu ý khi phát triển
- Component nên hoạt động độc lập, load qua Ajax/API.
- Cho phép enable/disable từng component với người dùng.
- Nên có caching nhẹ nếu dữ liệu lớn.
- Hỗ trợ responsive, mobile friendly.
- Hệ thống nên hỗ trợ chia bố cục: 1-2-3 cột tùy thiết bị.

---

> Bản kế hoạch này có thể mở rộng, tùy theo mục đích CMS: blog, thương mại, học liệu hay cộng đồng.