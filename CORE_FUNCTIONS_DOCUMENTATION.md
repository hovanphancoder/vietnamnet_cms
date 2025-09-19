# 📚 CORE CMS FUNCTIONS DOCUMENTATION

## 🎯 **CÁC HÀM CORE CÓ THỂ SỬ DỤNG Ở FRONTEND**

### 🔗 **1. URL & ROUTING FUNCTIONS**

#### `base_url($path = '', $lang = APP_LANG)`
- **Mô tả**: Lấy URL gốc của site
- **Tham số**: 
  - `$path`: Đường dẫn con (optional)
  - `$lang`: Mã ngôn ngữ (optional)
- **Trả về**: String URL đầy đủ
- **Ví dụ**: 
  ```php
  echo base_url(); // https://example.com/
  echo base_url('about'); // https://example.com/about
  echo base_url('about', 'vi'); // https://example.com/vi/about
  ```

#### `public_url($path = '')`
- **Mô tả**: Lấy URL public của ứng dụng
- **Tham số**: `$path`: Đường dẫn con (optional)
- **Trả về**: String URL public
- **Ví dụ**: 
  ```php
  echo public_url('assets/css/style.css'); // https://example.com/assets/css/style.css
  ```

#### `lang_url($lang = APP_LANG, $uri = null)`
- **Mô tả**: Đổi ngôn ngữ URL
- **Tham số**: 
  - `$lang`: Mã ngôn ngữ mới
  - `$uri`: URI hiện tại (optional)
- **Trả về**: String URL với ngôn ngữ mới
- **Ví dụ**: 
  ```php
  echo lang_url('vi'); // Chuyển sang tiếng Việt
  ```

#### `theme_url($path = '')`
- **Mô tả**: Lấy URL theme
- **Tham số**: `$path`: Đường dẫn con trong theme
- **Trả về**: String URL theme
- **Ví dụ**: 
  ```php
  echo theme_url('images/logo.png'); // https://example.com/themes/apkcms/images/logo.png
  ```

#### `theme_assets($path = '', $area = 'Frontend')`
- **Mô tả**: Lấy URL assets theme
- **Tham số**: 
  - `$path`: Đường dẫn asset
  - `$area`: Khu vực (Frontend/Backend)
- **Trả về**: String URL asset
- **Ví dụ**: 
  ```php
  echo theme_assets('css/style.css'); // https://example.com/themes/apkcms/Frontend/Assets/css/style.css
  ```

#### `download_url($file = '', $lang = APP_LANG)`
- **Mô tả**: Lấy URL download
- **Tham số**: 
  - `$file`: Tên file download
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String URL download
- **Ví dụ**: 
  ```php
  echo download_url('app.apk'); // https://example.com/downloads/app.apk
  ```

#### `get_page_url($type, $slug = '', $lang = APP_LANG)`
- **Mô tả**: Lấy URL trang theo loại
- **Tham số**: 
  - `$type`: Loại trang (home, about, contact, etc.)
  - `$slug`: Slug trang (optional)
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String URL trang
- **Ví dụ**: 
  ```php
  echo get_page_url('home'); // https://example.com/
  echo get_page_url('about', 'gioi-thieu'); // https://example.com/about/gioi-thieu
  ```

#### `redirect($url)`
- **Mô tả**: Chuyển hướng đến URL
- **Tham số**: `$url`: URL đích
- **Trả về**: void (redirects)
- **Ví dụ**: 
  ```php
  redirect(base_url('login'));
  ```

### 🔒 **2. SECURITY & INPUT FUNCTIONS**

#### `xss_clean($data)`
- **Mô tả**: Làm sạch dữ liệu chống XSS
- **Tham số**: `$data`: Dữ liệu cần làm sạch
- **Trả về**: String đã được làm sạch
- **Ví dụ**: 
  ```php
  $clean = xss_clean('<script>alert("xss")</script>'); // &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;
  ```

#### `clean_input($data)`
- **Mô tả**: Làm sạch input data toàn diện
- **Tham số**: `$data`: Dữ liệu cần làm sạch (string hoặc array)
- **Trả về**: Dữ liệu đã được làm sạch
- **Ví dụ**: 
  ```php
  $clean = clean_input($_POST['content']);
  ```

#### `_e($text)`
- **Mô tả**: Echo text đã được escape
- **Tham số**: `$text`: Text cần echo
- **Trả về**: void (echoes escaped text)
- **Ví dụ**: 
  ```php
  _e($user_input); // An toàn hơn echo
  ```

#### `S_GET($key, $default = null)`
- **Mô tả**: Lấy GET parameter an toàn
- **Tham số**: 
  - `$key`: Tên parameter
  - `$default`: Giá trị mặc định
- **Trả về**: Giá trị đã được làm sạch
- **Ví dụ**: 
  ```php
  $search = S_GET('search', ''); // An toàn hơn $_GET['search']
  ```

#### `S_POST($key, $default = null)`
- **Mô tả**: Lấy POST parameter an toàn
- **Tham số**: 
  - `$key`: Tên parameter
  - `$default`: Giá trị mặc định
- **Trả về**: Giá trị đã được làm sạch
- **Ví dụ**: 
  ```php
  $email = S_POST('email', '');
  ```

#### `S_REQUEST($key, $default = null)`
- **Mô tả**: Lấy REQUEST parameter an toàn
- **Tham số**: 
  - `$key`: Tên parameter
  - `$default`: Giá trị mặc định
- **Trả về**: Giá trị đã được làm sạch
- **Ví dụ**: 
  ```php
  $id = S_REQUEST('id', 0);
  ```

#### `HAS_GET($key)`
- **Mô tả**: Kiểm tra GET parameter có tồn tại
- **Tham số**: `$key`: Tên parameter
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (HAS_GET('search')) {
      // Có parameter search
  }
  ```

#### `HAS_POST($key)`
- **Mô tả**: Kiểm tra POST parameter có tồn tại
- **Tham số**: `$key`: Tên parameter
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (HAS_POST('submit')) {
      // Form đã được submit
  }
  ```

#### `uri_security($uri)`
- **Mô tả**: Làm sạch và bảo mật URI
- **Tham số**: `$uri`: URI cần làm sạch
- **Trả về**: String URI đã được làm sạch
- **Ví dụ**: 
  ```php
  $clean_uri = uri_security($_SERVER['REQUEST_URI']);
  ```

### 🌍 **3. LANGUAGE & LOCALIZATION FUNCTIONS**

#### `__($key, ...$args)`
- **Mô tả**: Dịch text
- **Tham số**: 
  - `$key`: Key dịch
  - `...$args`: Tham số thay thế
- **Trả về**: String đã dịch
- **Ví dụ**: 
  ```php
  echo __('welcome_message', 'John'); // "Chào mừng John!"
  ```

#### `__e($key, ...$args)`
- **Mô tả**: Echo text đã dịch
- **Tham số**: 
  - `$key`: Key dịch
  - `...$args`: Tham số thay thế
- **Trả về**: void (echoes translated text)
- **Ví dụ**: 
  ```php
  __e('welcome_message', 'John'); // Echo "Chào mừng John!"
  ```

#### `lang_name($currentLang = APP_LANG)`
- **Mô tả**: Lấy tên ngôn ngữ
- **Tham số**: `$currentLang`: Mã ngôn ngữ
- **Trả về**: String tên ngôn ngữ
- **Ví dụ**: 
  ```php
  echo lang_name('vi'); // "Tiếng Việt"
  ```

#### `lang_code($currentLang = APP_LANG)`
- **Mô tả**: Lấy mã ngôn ngữ
- **Tham số**: `$currentLang`: Mã ngôn ngữ
- **Trả về**: String mã ngôn ngữ
- **Ví dụ**: 
  ```php
  echo lang_code(); // "vi"
  ```

#### `lang_country($currentLang = APP_LANG)`
- **Mô tả**: Lấy mã quốc gia
- **Tham số**: `$currentLang`: Mã ngôn ngữ
- **Trả về**: String mã quốc gia
- **Ví dụ**: 
  ```php
  echo lang_country('vi'); // "VN"
  ```

#### `lang_flag($currentLang = APP_LANG)`
- **Mô tả**: Lấy emoji cờ quốc gia
- **Tham số**: `$currentLang`: Mã ngôn ngữ
- **Trả về**: String emoji cờ
- **Ví dụ**: 
  ```php
  echo lang_flag('vi'); // 🇻🇳
  ```

#### `get_locale()`
- **Mô tả**: Lấy locale hiện tại
- **Tham số**: Không
- **Trả về**: String locale
- **Ví dụ**: 
  ```php
  echo get_locale(); // "vi_VN"
  ```

#### `remove_accents($text, $locale = '')`
- **Mô tả**: Bỏ dấu tiếng Việt
- **Tham số**: 
  - `$text`: Text cần bỏ dấu
  - `$locale`: Locale (optional)
- **Trả về**: String đã bỏ dấu
- **Ví dụ**: 
  ```php
  echo remove_accents('Xin chào'); // "Xin chao"
  ```

#### `url_slug($str, $options = array())`
- **Mô tả**: Tạo slug URL
- **Tham số**: 
  - `$str`: Chuỗi cần tạo slug
  - `$options`: Tùy chọn (delimiter, limit, lowercase, replacements)
- **Trả về**: String slug
- **Ví dụ**: 
  ```php
  echo url_slug('Xin chào bạn!'); // "xin-chao-ban"
  ```

#### `keyword_slug($str, $options = array())`
- **Mô tả**: Tạo keyword slug
- **Tham số**: 
  - `$str`: Chuỗi cần tạo slug
  - `$options`: Tùy chọn
- **Trả về**: String keyword slug
- **Ví dụ**: 
  ```php
  echo keyword_slug('Xin chào bạn!'); // "xin chao ban"
  ```

### 📊 **4. DATABASE & CONTENT FUNCTIONS**

#### `get_posts($args = [], $lang = '')`
- **Mô tả**: Lấy danh sách bài viết với bộ lọc
- **Tham số**: 
  - `$args`: Mảng tham số lọc
  - `$lang`: Mã ngôn ngữ
- **Trả về**: Array danh sách bài viết
- **Ví dụ**: 
  ```php
  $posts = get_posts([
      'posttype' => 'blogs',
      'perPage' => 10,
      'withCategories' => true,
      'sort' => ['created_at', 'DESC']
  ]);
  ```

#### `get_post($args = [])`
- **Mô tả**: Lấy 1 bài viết
- **Tham số**: `$args`: Mảng tham số (id, slug, posttype, etc.)
- **Trả về**: Array bài viết hoặc null
- **Ví dụ**: 
  ```php
  $post = get_post([
      'slug' => 'bai-viet-mau',
      'posttype' => 'blogs',
      'withCategories' => true
  ]);
  ```

#### `get_terms($posttype, $type = 'categories', $lang = APP_LANG)`
- **Mô tả**: Lấy danh sách terms/categories
- **Tham số**: 
  - `$posttype`: Loại posttype
  - `$type`: Loại term (categories, tags, etc.)
  - `$lang`: Mã ngôn ngữ
- **Trả về**: Array danh sách terms
- **Ví dụ**: 
  ```php
  $categories = get_terms('blogs', 'categories');
  ```

#### `getRelated($post, $postId, $limit = 4)`
- **Mô tả**: Lấy bài viết liên quan
- **Tham số**: 
  - `$post`: Loại posttype
  - `$postId`: ID bài viết
  - `$limit`: Số lượng bài viết
- **Trả về**: Array bài viết liên quan
- **Ví dụ**: 
  ```php
  $related = getRelated('blogs', 123, 5);
  ```

#### `getAuthor($authorId)`
- **Mô tả**: Lấy thông tin tác giả
- **Tham số**: `$authorId`: ID tác giả
- **Trả về**: Array thông tin tác giả
- **Ví dụ**: 
  ```php
  $author = getAuthor(1);
  echo $author['fullname'];
  ```

#### `getAuthorName($authorId)`
- **Mô tả**: Lấy tên tác giả
- **Tham số**: `$authorId`: ID tác giả
- **Trả về**: String tên tác giả
- **Ví dụ**: 
  ```php
  echo getAuthorName(1); // "Admin"
  ```

#### `getAuthorNames($authorIds)`
- **Mô tả**: Lấy tên nhiều tác giả
- **Tham số**: `$authorIds`: Array ID tác giả
- **Trả về**: Array tên tác giả
- **Ví dụ**: 
  ```php
  $names = getAuthorNames([1, 2, 3]);
  ```

#### `searching($keyword = '')`
- **Mô tả**: Tìm kiếm toàn site
- **Tham số**: `$keyword`: Từ khóa tìm kiếm
- **Trả về**: Array kết quả tìm kiếm
- **Ví dụ**: 
  ```php
  $results = searching('từ khóa');
  // Trả về: ['blogs' => [...], 'themes' => [...], 'plugins' => [...]]
  ```

#### `get_filters()`
- **Mô tả**: Lấy bộ lọc từ URL
- **Tham số**: Không
- **Trả về**: Array bộ lọc
- **Ví dụ**: 
  ```php
  $filters = get_filters();
  ```

#### `updateViews($posttype, $id)`
- **Mô tả**: Cập nhật lượt xem
- **Tham số**: 
  - `$posttype`: Loại posttype
  - `$id`: ID bài viết
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  updateViews('blogs', 123);
  ```

### 🖼️ **5. IMAGE & MEDIA FUNCTIONS**

#### `_img($src, $title = '', $lazy = true, $class = '', $style = '', $width = '', $height = '', $id = '')`
- **Mô tả**: Render img tag với lazy loading
- **Tham số**: 
  - `$src`: Đường dẫn hình ảnh
  - `$title`: Title/alt text
  - `$lazy`: Bật lazy loading
  - `$class`: CSS class
  - `$style`: Inline style
  - `$width`: Chiều rộng
  - `$height`: Chiều cao
  - `$id`: ID element
- **Trả về**: String HTML img tag
- **Ví dụ**: 
  ```php
  echo _img('/images/logo.png', 'Logo', true, 'img-fluid');
  ```

#### `get_image_size($item, $size = 'full')`
- **Mô tả**: Lấy hình ảnh theo kích thước
- **Tham số**: 
  - `$item`: Dữ liệu hình ảnh (JSON string hoặc array)
  - `$size`: Kích thước (full, thumb, square, vertical, horizontal)
- **Trả về**: String đường dẫn hình ảnh
- **Ví dụ**: 
  ```php
  echo get_image_size($post['featured_image'], 'thumb');
  ```

#### `get_image_full($item)`
- **Mô tả**: Lấy hình ảnh gốc
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình gốc
- **Ví dụ**: 
  ```php
  echo get_image_full($post['featured_image']);
  ```

#### `get_thumbnail($item)`
- **Mô tả**: Lấy thumbnail
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn thumbnail
- **Ví dụ**: 
  ```php
  echo get_thumbnail($post['featured_image']);
  ```

#### `get_square($item)`
- **Mô tả**: Lấy hình vuông
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình vuông
- **Ví dụ**: 
  ```php
  echo get_square($post['featured_image']);
  ```

#### `get_image_vertical($item)`
- **Mô tả**: Lấy hình dọc
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình dọc
- **Ví dụ**: 
  ```php
  echo get_image_vertical($post['featured_image']);
  ```

#### `get_image_horizontal($item)`
- **Mô tả**: Lấy hình ngang
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình ngang
- **Ví dụ**: 
  ```php
  echo get_image_horizontal($post['featured_image']);
  ```

#### `img_square($item)`
- **Mô tả**: Lấy hình vuông 150x150
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình vuông
- **Ví dụ**: 
  ```php
  echo img_square($post['featured_image']);
  ```

#### `img_vertical($item)`
- **Mô tả**: Lấy hình dọc 333x500
- **Tham số**: `$item`: Dữ liệu hình ảnh
- **Trả về**: String đường dẫn hình dọc
- **Ví dụ**: 
  ```php
  echo img_vertical($post['featured_image']);
  ```

#### `addSizeToPath($path, $size)`
- **Mô tả**: Thêm kích thước vào đường dẫn hình ảnh
- **Tham số**: 
  - `$path`: Đường dẫn gốc
  - `$size`: Kích thước
- **Trả về**: String đường dẫn mới
- **Ví dụ**: 
  ```php
  echo addSizeToPath('/images/photo.jpg', '150x150'); // /images/photo_150x150.jpg
  ```

### 📄 **6. PAGE & TEMPLATE FUNCTIONS**

#### `get_current_page()`
- **Mô tả**: Lấy thông tin trang hiện tại
- **Tham số**: Không
- **Trả về**: Array thông tin trang
- **Ví dụ**: 
  ```php
  $page = get_current_page();
  echo $page['type']; // 'home', 'about', etc.
  ```

#### `is_page($type)`
- **Mô tả**: Kiểm tra loại trang hiện tại
- **Tham số**: `$type`: Loại trang cần kiểm tra
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (is_page('home')) {
      echo 'Trang chủ';
  }
  ```

#### `get_current_page_title()`
- **Mô tả**: Lấy tiêu đề trang hiện tại
- **Tham số**: Không
- **Trả về**: String tiêu đề
- **Ví dụ**: 
  ```php
  echo get_current_page_title();
  ```

#### `get_page_title($type, $custom_title = '')`
- **Mô tả**: Lấy tiêu đề trang theo loại
- **Tham số**: 
  - `$type`: Loại trang
  - `$custom_title`: Tiêu đề tùy chỉnh
- **Trả về**: String tiêu đề
- **Ví dụ**: 
  ```php
  echo get_page_title('about'); // "Giới thiệu"
  ```

#### `get_page_heading($type, $custom_title = '')`
- **Mô tả**: Lấy heading trang
- **Tham số**: 
  - `$type`: Loại trang
  - `$custom_title`: Tiêu đề tùy chỉnh
- **Trả về**: String heading
- **Ví dụ**: 
  ```php
  echo get_page_heading('about'); // "Giới thiệu"
  ```

#### `get_page_description($type, $custom_title = '')`
- **Mô tả**: Lấy mô tả trang
- **Tham số**: 
  - `$type`: Loại trang
  - `$custom_title`: Tiêu đề tùy chỉnh
- **Trả về**: String mô tả
- **Ví dụ**: 
  ```php
  echo get_page_description('about');
  ```

#### `get_template($templateName, $data = [], $area = 'Frontend')`
- **Mô tả**: Lấy template file
- **Tham số**: 
  - `$templateName`: Tên template
  - `$data`: Dữ liệu truyền vào
  - `$area`: Khu vực (Frontend/Backend)
- **Trả về**: void (includes template)
- **Ví dụ**: 
  ```php
  get_template('sections/header', ['title' => 'Trang chủ']);
  ```

#### `get_header($args)`
- **Mô tả**: Lấy header template
- **Tham số**: `$args`: Tham số truyền vào header
- **Trả về**: void (includes header)
- **Ví dụ**: 
  ```php
  get_header(['title' => 'Trang chủ']);
  ```

#### `get_footer($args)`
- **Mô tả**: Lấy footer template
- **Tham số**: `$args`: Tham số truyền vào footer
- **Trả về**: void (includes footer)
- **Ví dụ**: 
  ```php
  get_footer();
  ```

### 🛠️ **7. UTILITY & HELPER FUNCTIONS**

#### `formatViews($views)`
- **Mô tả**: Format số lượt xem
- **Tham số**: `$views`: Số lượt xem
- **Trả về**: String đã format
- **Ví dụ**: 
  ```php
  echo formatViews(1500); // "1.5K"
  echo formatViews(1500000); // "1.5M"
  ```

#### `convert_to_string_number($number)`
- **Mô tả**: Chuyển số thành chuỗi format
- **Tham số**: `$number`: Số cần chuyển
- **Trả về**: String đã format
- **Ví dụ**: 
  ```php
  echo convert_to_string_number(1500); // "1.5K"
  ```

#### `calculateReadTime($content)`
- **Mô tả**: Tính thời gian đọc
- **Tham số**: `$content`: Nội dung
- **Trả về**: Integer số phút
- **Ví dụ**: 
  ```php
  echo calculateReadTime($post['content']); // 5
  ```

#### `time_compare($time1, $time2)`
- **Mô tả**: So sánh hai thời gian
- **Tham số**: 
  - `$time1`: Thời gian 1
  - `$time2`: Thời gian 2
- **Trả về**: Boolean (time1 < time2)
- **Ví dụ**: 
  ```php
  if (time_compare('2023-01-01', '2023-12-31')) {
      echo 'Thời gian 1 sớm hơn';
  }
  ```

#### `prt($variable, $name = '')`
- **Mô tả**: Debug print variable
- **Tham số**: 
  - `$variable`: Biến cần debug
  - `$name`: Tên biến (optional)
- **Trả về**: void (prints debug info)
- **Ví dụ**: 
  ```php
  prt($posts, 'Posts Data');
  ```

#### `dd(...$variables)`
- **Mô tả**: Dump and die (Laravel style)
- **Tham số**: `...$variables`: Các biến cần dump
- **Trả về**: void (dumps and exits)
- **Ví dụ**: 
  ```php
  dd($posts, $categories);
  ```

#### `dump(...$variables)`
- **Mô tả**: Dump variables (Laravel style)
- **Tham số**: `...$variables`: Các biến cần dump
- **Trả về**: void (dumps variables)
- **Ví dụ**: 
  ```php
  dump($posts);
  ```

#### `convers_array($data)`
- **Mô tả**: Chuyển đổi thành array
- **Tham số**: `$data`: Dữ liệu cần chuyển
- **Trả về**: Array
- **Ví dụ**: 
  ```php
  $array = convers_array($json_string);
  ```

#### `indexByFieldName($data)`
- **Mô tả**: Index array theo field name
- **Tham số**: `$data`: Dữ liệu cần index
- **Trả về**: Array đã index
- **Ví dụ**: 
  ```php
  $indexed = indexByFieldName($fields);
  ```

#### `indexByID($data)`
- **Mô tả**: Index array theo ID
- **Tham số**: `$data`: Dữ liệu cần index
- **Trả về**: Array đã index
- **Ví dụ**: 
  ```php
  $indexed = indexByID($posts);
  ```

#### `is_slug($str)`
- **Mô tả**: Kiểm tra slug hợp lệ
- **Tham số**: `$str`: Chuỗi cần kiểm tra
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (is_slug('bai-viet-mau')) {
      echo 'Slug hợp lệ';
  }
  ```

#### `is_sqltable($str)`
- **Mô tả**: Kiểm tra tên bảng SQL hợp lệ
- **Tham số**: `$str`: Tên bảng cần kiểm tra
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (is_sqltable('fast_posts')) {
      echo 'Tên bảng hợp lệ';
  }
  ```

#### `is_sqlcolumn($str)`
- **Mô tả**: Kiểm tra tên cột SQL hợp lệ
- **Tham số**: `$str`: Tên cột cần kiểm tra
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  if (is_sqlcolumn('post_title')) {
      echo 'Tên cột hợp lệ';
  }
  ```

### ⚙️ **8. CONFIGURATION & OPTIONS FUNCTIONS**

#### `config($key = '', $file = 'Config')`
- **Mô tả**: Lấy giá trị config
- **Tham số**: 
  - `$key`: Key config
  - `$file`: File config
- **Trả về**: Mixed giá trị config
- **Ví dụ**: 
  ```php
  $app_url = config('app_url', 'app');
  ```

#### `option($key, $lang = APP_LANG)`
- **Mô tả**: Lấy giá trị option
- **Tham số**: 
  - `$key`: Key option
  - `$lang`: Mã ngôn ngữ
- **Trả về**: Mixed giá trị option
- **Ví dụ**: 
  ```php
  $site_title = option('site_title');
  ```

#### `option_set($key, $value, $lang = '')`
- **Mô tả**: Set giá trị option
- **Tham số**: 
  - `$key`: Key option
  - `$value`: Giá trị mới
  - `$lang`: Mã ngôn ngữ
- **Trả về**: Mixed giá trị đã set
- **Ví dụ**: 
  ```php
  option_set('site_title', 'Tên site mới');
  ```

#### `env($key, $default = null)`
- **Mô tả**: Lấy biến môi trường
- **Tham số**: 
  - `$key`: Tên biến
  - `$default`: Giá trị mặc định
- **Trả về**: Mixed giá trị biến môi trường
- **Ví dụ**: 
  ```php
  $db_host = env('DB_HOST', 'localhost');
  ```

#### `posttype($key, $lang = APP_LANG)`
- **Mô tả**: Lấy cấu hình posttype
- **Tham số**: 
  - `$key`: Key posttype
  - `$lang`: Mã ngôn ngữ
- **Trả về**: Array cấu hình posttype
- **Ví dụ**: 
  ```php
  $blog_config = posttype('blogs');
  ```

#### `posttype_exists($slug, $lang = '')`
- **Mô tả**: Kiểm tra posttype tồn tại
- **Tham số**: 
  - `$slug`: Slug posttype
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String tên bảng hoặc null
- **Ví dụ**: 
  ```php
  if (posttype_exists('blogs')) {
      echo 'Posttype tồn tại';
  }
  ```

#### `table_posttype($slug, $lang = '')`
- **Mô tả**: Lấy tên bảng posttype
- **Tham số**: 
  - `$slug`: Slug posttype
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String tên bảng
- **Ví dụ**: 
  ```php
  $table = table_posttype('blogs', 'vi'); // "fast_posts_blogs_vi"
  ```

#### `table_posttype_relationship($slug)`
- **Mô tả**: Lấy tên bảng relationship
- **Tham số**: `$slug`: Slug posttype
- **Trả về**: String tên bảng
- **Ví dụ**: 
  ```php
  $table = table_posttype_relationship('blogs'); // "fast_posts_blogs_rel"
  ```

### 🚀 **9. API FUNCTIONS**

#### `api_rating($posttype, $id, $lang = APP_LANG)`
- **Mô tả**: Lấy URL API rating
- **Tham số**: 
  - `$posttype`: Loại posttype
  - `$id`: ID bài viết
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String URL API
- **Ví dụ**: 
  ```php
  echo api_rating('blogs', 123); // "https://example.com/vi/api/v1/posts/action/rating/blogs/123"
  ```

#### `api_count_view($posttype, $id, $lang = APP_LANG)`
- **Mô tả**: Lấy URL API đếm view
- **Tham số**: 
  - `$posttype`: Loại posttype
  - `$id`: ID bài viết
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String URL API
- **Ví dụ**: 
  ```php
  echo api_count_view('blogs', 123);
  ```

#### `api_like_post($posttype, $id, $lang = APP_LANG)`
- **Mô tả**: Lấy URL API like
- **Tham số**: 
  - `$posttype`: Loại posttype
  - `$id`: ID bài viết
  - `$lang`: Mã ngôn ngữ
- **Trả về**: String URL API
- **Ví dụ**: 
  ```php
  echo api_like_post('blogs', 123);
  ```

#### `api_upload_url($path = '')`
- **Mô tả**: Lấy URL API upload
- **Tham số**: `$path`: Đường dẫn con
- **Trả về**: String URL API
- **Ví dụ**: 
  ```php
  echo api_upload_url('images/');
  ```

### 🎨 **10. RENDER & ASSET FUNCTIONS**

#### `Render::component($component, $data)`
- **Mô tả**: Render component
- **Tham số**: 
  - `$component`: Tên component
  - `$data`: Dữ liệu truyền vào
- **Trả về**: String HTML
- **Ví dụ**: 
  ```php
  echo Render::component('header', ['title' => 'Trang chủ']);
  ```

#### `Render::block($blockName, $data)`
- **Mô tả**: Render block
- **Tham số**: 
  - `$blockName`: Tên block
  - `$data`: Dữ liệu truyền vào
- **Trả về**: String HTML
- **Ví dụ**: 
  ```php
  echo Render::block('Frontend\Sliders\SliderPost', ['posts' => $posts]);
  ```

#### `Render::asset($assetType, $file, $options)`
- **Mô tả**: Thêm asset
- **Tham số**: 
  - `$assetType`: Loại asset (css, js)
  - `$file`: Tên file
  - `$options`: Tùy chọn
- **Trả về**: void
- **Ví dụ**: 
  ```php
  Render::asset('css', 'style.css', ['area' => 'frontend']);
  ```

#### `Render::renderAssets($area)`
- **Mô tả**: Render assets
- **Tham số**: `$area`: Khu vực (frontend/backend)
- **Trả về**: String HTML
- **Ví dụ**: 
  ```php
  echo Render::renderAssets('frontend');
  ```

#### `do_shortcode($name, ...$params)`
- **Mô tả**: Thực thi shortcode
- **Tham số**: 
  - `$name`: Tên shortcode
  - `...$params`: Tham số
- **Trả về**: String HTML
- **Ví dụ**: 
  ```php
  echo do_shortcode('gallery', ['id' => 123]);
  ```

#### `add_shortcode($name, $callback)`
- **Mô tả**: Đăng ký shortcode
- **Tham số**: 
  - `$name`: Tên shortcode
  - `$callback`: Callback function
- **Trả về**: void
- **Ví dụ**: 
  ```php
  add_shortcode('gallery', function($params) {
      return '<div class="gallery">...</div>';
  });
  ```

#### `remove_shortcode($name)`
- **Mô tả**: Xóa shortcode
- **Tham số**: `$name`: Tên shortcode
- **Trả về**: Boolean
- **Ví dụ**: 
  ```php
  remove_shortcode('gallery');
  ```

## 📝 **VÍ DỤ SỬ DỤNG TỔNG HỢP**

```php
<?php
// Lấy danh sách bài viết
$posts = get_posts([
    'posttype' => 'blogs',
    'perPage' => 10,
    'withCategories' => true,
    'sort' => ['created_at', 'DESC']
]);

// Lấy 1 bài viết
$post = get_post([
    'slug' => 'bai-viet-mau',
    'posttype' => 'blogs',
    'withCategories' => true
]);

// Render hình ảnh
echo _img($post['featured_image'], $post['title'], true, 'img-fluid');

// Lấy URL
$home_url = base_url();
$about_url = base_url('about');
$theme_css = theme_assets('css/style.css');

// Dịch text
echo __('welcome_message', 'John');

// Làm sạch input
$search = S_GET('search', '');
$clean_search = xss_clean($search);

// Format số
echo formatViews(1500); // "1.5K"

// Kiểm tra trang
if (is_page('home')) {
    echo 'Trang chủ';
}

// Lấy categories
$categories = get_terms('blogs', 'categories');

// Render template
get_template('sections/header', ['title' => 'Trang chủ']);
?>
```

## 🔧 **CÁC CONSTANTS QUAN TRỌNG**

```php
PATH_ROOT          // Đường dẫn gốc
PATH_APP           // Đường dẫn application
PATH_SYS           // Đường dẫn system
APP_THEME_NAME     // Tên theme hiện tại
APP_THEME_PATH     // Đường dẫn theme
APP_LANG           // Ngôn ngữ hiện tại
APP_LANG_DF        // Ngôn ngữ mặc định
APP_LANGUAGES      // Mảng ngôn ngữ có sẵn
```

---

**📅 Ngày tạo**: <?php echo date('Y-m-d H:i:s'); ?>  
**📝 Phiên bản**: 1.0  
**👨‍💻 Tác giả**: Core CMS System
