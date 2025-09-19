<?php
return [
    'posttype_add_title' => 'Thêm Loại Bài Viết',
    
    // CSRF Errors
    'csrf_failed' => 'Mã CSRF không hợp lệ.',

    // Add PostType Messages
    'posttype_add_success' => 'Loại bài viết đã được thêm thành công.',
    'posttype_add_error' => 'Đã xảy ra lỗi khi thêm loại bài viết.',

    // Edit PostType Messages
    'posttype_edit_success' => 'Loại bài viết đã được cập nhật thành công.',
    'posttype_edit_error' => 'Đã xảy ra lỗi khi cập nhật loại bài viết.',

    // Delete PostType Messages
    'posttype_delete_success' => 'Loại bài viết đã được xóa thành công.',
    'posttype_delete_error' => 'Đã xảy ra lỗi khi xóa loại bài viết.',

    // Validation Errors
    'language_not_found' => 'Không tìm thấy ngôn ngữ.',
    'slug_exist' => 'Slug đã tồn tại.',

    // PostType Name Validation
    'posttype_name_required' => 'Tên loại bài viết là bắt buộc.',
    'posttype_name_length' => 'Tên loại bài viết phải có độ dài từ 3 đến 150 ký tự.',

    // PostType Slug Validation
    'posttype_slug_required' => 'Slug của loại bài viết là bắt buộc.',
    'posttype_slug_length' => 'Slug của loại bài viết phải có độ dài từ 3 đến 150 ký tự.',
    'posttype_slug_lowercase' => 'Slug của loại bài viết phải viết thường.',

    // PostType Status Validation
    'posttype_status_required' => 'Trạng thái loại bài viết là bắt buộc.',
    'posttype_status_invalid' => 'Trạng thái loại bài viết không hợp lệ.',

    // Field Validation
    'field_type_required' => 'Loại trường là bắt buộc.',
    'field_label_required' => 'Nhãn trường là bắt buộc.',
    'field_name_required' => 'Tên trường là bắt buộc.',
    'field_name_lowercase' => 'Tên trường phải viết thường.',
    'field_required_invalid' => 'Giá trị bắt buộc cho trường không hợp lệ.',
    'field_visibility_required' => 'Trạng thái hiển thị trường là bắt buộc.',
    'field_visibility_invalid' => 'Trạng thái hiển thị trường không hợp lệ.',
    'field_collapsed_invalid' => 'Trạng thái thu gọn trường không hợp lệ.',
    'upload_to_another_server'  => 'Tải lên server khác',
    // Term Validation
    'synchronous_init'  =>  'Đồng bộ dữ liệu các ngôn ngữ',
    'hierarchical'  =>  'Phân cấp',
    'always'    =>  'Luôn luôn',
    'never' =>  'Không',
    'first time'    =>  'Lần đầu',
    'term_name_required' => 'Tên phân loại là bắt buộc.',
    'term_type_required' => 'Loại phân loại là bắt buộc.',
    'term_type_lowercase' => 'Loại phân loại phải viết thường.',

    // Field Translate
    'field_type'    =>  'Loại Field',
    'choose_field_type' =>  'Chọn Loại Field',

    // *** New keys added *** //

    // General
    'id' => 'ID',
    'name' => 'Tên',
    'slug' => 'Slug',
    'languages' => 'Ngôn ngữ',
    'terms' => 'Phân loại',
    'status' => 'Trạng thái',
    'action' => 'Hành động',

    // Actions
    'edit' => 'Chỉnh sửa',
    'delete' => 'Xóa',
    'add_new_posttype' => 'Thêm mới',

    // Page Titles and Buttons
    'posttype_list' => 'Danh sách Loại Bài Viết',
    'create_posttype' => 'Tạo Loại Bài Viết', // Fixed from 'creat_posttype'
    'posts_per_page' => 'posts mỗi trang',
    // Notifications for No Data
    'no_languages' => 'Không có ngôn ngữ nào.',
    'no_terms' => 'Không có phân loại nào.',

    'posttype_edit_title' => 'Chỉnh sửa Loại Bài Viết',

    // Additional Localization Strings

    // Form Labels and Placeholders
    'posttype_name' => 'Tên Loại Bài Viết',
    'enter_posttype_name' => 'Nhập Tên Loại Bài Viết',
    'enter_slug' => 'Nhập Slug',
    'search_posttype' => 'Tìm kiếm Loại Bài Viết',

    // Status
    'status' => 'Trạng thái',
    'active' => 'Hoạt động',
    'inactive' => 'Không hoạt động',

    // Languages
    'languages' => 'Ngôn ngữ',
    'multi_lang'    =>  'Đa Ngôn Ngữ',

    // Terms
    'add_term' => 'Thêm Phân Loại',
    'term' => 'Phân Loại',
    'remove_term' => 'Xóa Phân Loại',

    // Field Management
    'fields' => 'Field',
    'add_field' => 'Thêm Field',

    // JSON Data
    'json_form_data' => 'Dữ liệu JSON của Form',

    // Submit Button
    'save_posttype' => 'Save Loại Bài Viết',

    // Additional Terms in the Template
    'name_terms' => 'Tên Phân Loại',
    'slug_terms' => 'Slug Phân Loại',
    'enter_type' => 'Nhập Loại',

    // Labels and Placeholders (Duplicate keys were merged)
    'label' => 'Tên Hiển Thị',
    'enter_label' => 'Nhập Tên Hiển Thị',
    'field_name_slug' => 'Slug Field',
    'enter_field_name' => 'Nhập Tên Trường',
    'description' => 'Mô tả ngắn',
    'enter_description' => 'Nhập Mô tả',
    'css_class_name' => 'Tên Class CSS',
    'enter_css_class_name' => 'Nhập Tên Class CSS',
    'placeholder' => 'Placeholder',
    'enter_placeholder' => 'Nhập nội dung Placeholder',
    'default_value' => 'Giá trị Mặc định',
    'enter_default_value' => 'Nhập Giá trị Mặc định',
    'position'  =>  'Vị trí',
    'width_input'   =>  'Kích thước',

    // Toggles
    'required' => 'Bắt buộc',
    'visibility' => 'Hiển thị',
    'multiple' => 'Chọn nhiều Files',

    // Validation
    'min' => 'Min Char',
    'enter_min' => 'Số kí tự tối thiểu',
    'max' => 'Max Char',
    'enter_max' => 'Số kí tự tối đa',
    'rows' => 'Số hàng',
    'enter_number_of_rows' => 'Nhập Số Hàng',

    // Options
    'options' => 'Cấu hình Web',
    'value' => 'Giá trị',
    'add_option' => 'Thêm cấu hình',
    'group' => 'Nhóm',

    // File/Image Types
    'allow_types' => 'Cho phép Loại',
    'max_file_size_mb' => 'Kích thước Tệp Tối đa (MB)',
    'enter_max_file_size' => 'Nhập Kích thước Tệp Tối đa',

    // WYSIWYG
    'wysiwyg_notice' => 'Trường này sẽ sử dụng lớp <code>fast-editors</code> để gọi trình soạn thảo.',

    // Reference
    'choose_post_type_reference' => 'Chọn Loại Bài Viết Tham chiếu',
    'choose_post_type' => 'Chọn Loại Bài Viết',
    'post_status_filter' => 'Bộ lọc Trạng thái Bài Viết',

    // Repeater
    'fields_in_repeater' => 'Các Trường trong Repeater',
    'add_field_to_repeater' => 'Thêm Trường vào Repeater',

    'menu_options' => 'Menu cấu hình',
    'menu_hide' => 'Ẩn Menu',
    'menu_root' => 'Menu Gốc',
    'menu_child' => 'Menu Con',
    'fields_default' => 'Trường mặc định',
    'multiple_server' => 'Save trữ nhiều server',

    'server_configurations' => 'Cấu hình server',
    'server_url'       => 'Đường dẫn',
    'add_server'       => 'Thêm Server',
    'server_token'      => 'Token Server',
    'enter_server_url'  => 'domain.com',
    'enter_server_token'  => 'Nhập Token server',
    'resize_image'      => 'Thay đổi kích thước ảnh',
    'resize_width'      => 'Chiều rộng',
    'resize_height'     => 'Chiều cao',
    'auto_crop_image'   => 'Tự động cắt ảnh (ảnh sẽ được tự động cắt theo kích thước mặc định, lấy phần giữa ảnh.)',
    'auto_crop_description' => ''
];
