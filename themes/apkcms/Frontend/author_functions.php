<?php
/**
 * Lấy thông tin tác giả đầy đủ từ author ID
 * 
 * @param mixed $author_id ID của tác giả
 * @return array Thông tin tác giả
 */
if (!function_exists('get_author_info')) {
    function get_author_info($author_id) {
        // Khởi tạo dữ liệu mặc định
        $default_author = [
            'id' => $author_id,
            'name' => 'Admin',
            'username' => 'admin',
            'avatar' => '/themes/apkcms/Frontend/assets/images/default-avatar.png',
            'bio' => '',
            'url' => '/author/admin',
            'join_date' => 'N/A',
            'posts_count' => 0,
            'role' => 'author',
            'status' => 'active'
        ];
        
        // Nếu không có author_id, trả về dữ liệu mặc định
        if (empty($author_id)) {
            return $default_author;
        }
        
        try {
            // Lấy thông tin tác giả từ database
            $author_info = getAuthor($author_id);
            
            // Nếu không tìm thấy tác giả, trả về dữ liệu mặc định
            if (!$author_info || !is_array($author_info)) {
                return $default_author;
            }
            
            // Xử lý dữ liệu tác giả
            $author_data = [
                'id' => $author_info['id'] ?? $author_id,
                'name' => $author_info['fullname'] ?? $author_info['username'] ?? 'Admin',
                'username' => $author_info['username'] ?? 'admin',
                'avatar' => !empty($author_info['avatar']) ? $author_info['avatar'] : $default_author['avatar'],
                'bio' => $author_info['about_me'] ?? '',
                'url' => '/author/' . ($author_info['username'] ?? $author_id),
                'join_date' => !empty($author_info['created_at']) ? date('d/m/Y', strtotime($author_info['created_at'])) : 'N/A',
                'posts_count' => countAuthorThemesPlugins('posts', $author_id),
                'role' => $author_info['role'] ?? 'author',
                'status' => $author_info['status'] ?? 'active',
                'email' => $author_info['email'] ?? '',
                'phone' => $author_info['phone'] ?? '',
                'gender' => $author_info['gender'] ?? 'male',
                'birthday' => $author_info['birthday'] ?? '',
                'location' => $author_info['location'] ?? '',
                'socials' => []
            ];
            
            // Xử lý thông tin social media nếu có
            if (!empty($author_info['personal'])) {
                $personal = is_string($author_info['personal']) ? json_decode($author_info['personal'], true) : $author_info['personal'];
                if (is_array($personal) && !empty($personal['socials'])) {
                    $author_data['socials'] = $personal['socials'];
                }
            }
            
            return $author_data;
            
        } catch (Exception $e) {
            error_log('Error in get_author_info: ' . $e->getMessage());
            return $default_author;
        }
    }
}

/**
 * Hiển thị HTML thông tin tác giả
 * 
 * @param mixed $author_id ID của tác giả
 * @param array $options Tùy chọn hiển thị
 * @return string HTML
 */
if (!function_exists('display_author_info')) {
    function display_author_info($author_id, $options = []) {
        // Cấu hình mặc định
        $default_options = [
            'show_avatar' => true,
            'show_bio' => true,
            'show_stats' => true,
            'show_socials' => false,
            'avatar_size' => 'w-12 h-12',
            'container_class' => 'author-info mt-6 p-4 bg-gray-50 rounded-lg',
            'name_class' => 'notosans-bold font-bold text-lg text-gray-800',
            'bio_class' => 'text-sm text-gray-600 mt-1 line-clamp-2',
            'stats_class' => 'flex items-center space-x-4 mt-2 text-xs text-gray-500'
        ];
        
        // Merge options
        $options = array_merge($default_options, $options);
        
        // Lấy thông tin tác giả
        $author = get_author_info($author_id);
        
        // Bắt đầu HTML
        $html = '<div class="' . $options['container_class'] . '">';
        $html .= '<div class="flex items-center space-x-4">';
        
        // Avatar
        if ($options['show_avatar']) {
            $html .= '<div class="flex-shrink-0">';
            $html .= '<img src="' . htmlspecialchars($author['avatar']) . '" ';
            $html .= 'alt="' . htmlspecialchars($author['name']) . '" ';
            $html .= 'class="' . $options['avatar_size'] . ' rounded-full object-cover border-2 border-gray-200">';
            $html .= '</div>';
        }
        
        // Author Details
        $html .= '<div class="flex-grow">';
        
        // Name
        $html .= '<h4 class="' . $options['name_class'] . '">';
        $html .= '<a href="' . htmlspecialchars($author['url']) . '" ';
        $html .= 'title="Xem tất cả bài viết của ' . htmlspecialchars($author['name']) . '" ';
        $html .= 'class="hover:text-blue-600 transition-colors">';
        $html .= htmlspecialchars($author['name']);
        $html .= '</a></h4>';
        
        // Bio
        if ($options['show_bio'] && !empty($author['bio'])) {
            $html .= '<p class="' . $options['bio_class'] . '">';
            $html .= htmlspecialchars($author['bio']);
            $html .= '</p>';
        }
        
        // Stats
        if ($options['show_stats']) {
            $html .= '<div class="' . $options['stats_class'] . '">';
            $html .= '<span><i class="fas fa-calendar-alt mr-1"></i>';
            $html .= 'Tham gia: ' . $author['join_date'] . '</span>';
            $html .= '<span><i class="fas fa-newspaper mr-1"></i>';
            $html .= $author['posts_count'] . ' bài viết</span>';
            $html .= '</div>';
        }
        
        // Social Links
        if ($options['show_socials'] && !empty($author['socials'])) {
            $html .= '<div class="flex space-x-2 mt-2">';
            foreach ($author['socials'] as $platform => $url) {
                if (!empty($url)) {
                    $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank" ';
                    $html .= 'class="text-gray-500 hover:text-blue-600 transition-colors">';
                    $html .= '<i class="fab fa-' . $platform . '"></i>';
                    $html .= '</a>';
                }
            }
            $html .= '</div>';
        }
        
        $html .= '</div>'; // flex-grow
        $html .= '</div>'; // flex items-center
        $html .= '</div>'; // container
        
        return $html;
    }
}
