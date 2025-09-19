<?php
namespace System\Libraries;

use System\Libraries\Render;
$current_user = 1;
$posttype = json_encode($posttype, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
if(!empty($post)) {
    $post_encode = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
} else {
  $post_encode = '[]';
}
$currentLang = S_GET('post_lang') ?? APP_LANG_DF;

// Lấy danh sách ngôn ngữ từ config
$languages = ['vi', 'en']; // Có thể lấy từ config thực tế
$type = S_GET('type') ?? '';
$isEdit = !empty($post);
$langHasPost = []; // Có thể lấy từ database nếu cần

// [1] LẤY CÁC THÔNG TIN CHUNG
Render::block('Backend\Header', ['layout'=>'default', 'title' => 'Add Posts']);
?>

<div class="pc-container">
    <div class="pc-content relative">

    <!-- PHP sẽ tạo form wrapper này -->
    <form id="acf-form" method="post" action="" enctype="multipart/form-data">
        <!-- PHP sẽ thêm các hidden fields cần thiết -->
        <input type="hidden" name="post_id" value="" />
        <input type="hidden" name="type" value="<?= S_GET('type') ?? '' ?>" />
        <!-- <input type="hidden" name="lang" value="<?= $currentLang ?>" /> -->

        <!-- TOP ACTION BAR - NGÔN NGỮ & PUBLISH -->
        <div class="bg-card rounded-xl mb-4 border">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between p-4">
                <!-- STATUS & TIME SECTION -->
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
                    <div class="flex items-center gap-2">
                        <label for="last-updated-input" class="text-sm font-medium text-muted-foreground whitespace-nowrap">Thời gian cập nhật:</label>
                        <input id="last-updated-input" name="last_updated" type="datetime-local" 
                               class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 min-w-[180px]" />
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="post-status" class="text-sm font-medium text-muted-foreground whitespace-nowrap">Trạng thái bài:</label>
                        <select id="post-status" name="status" 
                                class="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 min-w-[120px]">
                            <option value="active" <?= isset($post['status']) && $post['status'] == 'active' ? 'selected' : '' ?>>Đã xuất bản</option>
                            <option value="inactive" <?= isset($post['status']) && $post['status'] == 'inactive' ? 'selected' : '' ?>>Nháp</option>
                        </select>
                    </div>
                </div>

                <!-- APP_LANGUAGE SWITCHER -->
                <div class="flex items-center gap-2">
                    <!-- Ngôn ngữ hiện tại -->
                    <div class="inline-flex items-center px-3 py-2 rounded-md bg-primary text-primary-foreground shadow-sm">
                        <span class="text-sm font-medium uppercase"><?= $currentLang ?></span>
                        <span class="ml-2">
                            <?php if($isEdit) { ?>
                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                            <?php } else { ?>
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            <?php } ?>
                        </span>
                    </div>

                    <!-- Các ngôn ngữ khác -->
                    <?php foreach($languages as $language): 
                        if($language !== $currentLang):
                            if($isEdit) {
                              $langUrlAction = admin_url('posts/clone/' . $post['id']) 
                                                . '?type=' . $type
                                                . '&post_lang=' . $language
                                                . '&oldpost_lang=' . $currentLang;
                            } else {
                              $langUrlAction = admin_url('posts/add', '/'.APP_LANG)
                                                . '?type=' . $type
                                                . '&post_lang=' . $language;
                            }
                      ?>
                    <a href="<?= $langUrlAction; ?>" 
                       class="inline-flex items-center px-3 py-2 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground transition-colors">
                        <span class="text-sm font-medium uppercase"><?= $language ?></span>
                        <span class="ml-2">
                            <?php if( $isEdit && in_array($language, $langHasPost)) { ?>
                            <i data-lucide="edit" class="h-4 w-4"></i>
                            <?php } else { ?>
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            <?php }?>
                        </span>
                    </a>
                    <?php 
                        endif;
                      endforeach; ?>
                </div>

                <!-- PUBLISH BUTTON -->
                <div class="flex items-center">
                    <button type="submit" id="publish-btn" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                        <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                        Publish
                    </button>
                </div>
            </div>
        </div>

    <style>
        /* Custom styles for better spacing */
        .field-wrapper {
            margin-bottom: 1rem;
        }
        
        .field-wrapper:last-child {
            margin-bottom: 0;
        }
        
        /* Custom styles for better spacing */
        .field-wrapper {
            margin-bottom: 1rem;
        }
        
        .field-wrapper:last-child {
            margin-bottom: 0;
        }
        
        /* Editor.js styles */
        .codex-editor {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        
        .codex-editor__redactor {
            padding: 12px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* DND Kit specific styles */
        .repeater-item {
            transition: all 0.2s ease;
        }
        
        .repeater-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .repeater-handle {
            cursor: grab;
        }
        
        .repeater-handle:active {
            cursor: grabbing;
        }
        
        .flexible-layout-selector {
            max-height: 300px;
            overflow-y: auto;
        }

        /* DND Kit drag overlay styles */
        .dnd-overlay {
            opacity: 0.8;
            transform: rotate(5deg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
  <script type="module" crossorigin src="<?= theme_assets('js/posts_add.js', 'Backend') ?>"></script>
  <link rel="stylesheet" crossorigin href="<?= theme_assets('css/posts_add.css', 'Backend') ?>">

  <style>
       .ce-block__content {
        max-width: inherit;
       }
      .fixed-form-actions { transition: box-shadow 0.2s; }
      @media (max-width: 600px) {
        .fixed-form-actions { padding: 1rem 2vw 1rem 2vw; }
        .fixed-form-actions > div { flex-direction: column; align-items: stretch !important; gap: 0.5rem !important; }
      }
    </style>
        <!-- React app container - Chỉ render fields -->
        <div id="initial-loading" style="display: flex; justify-content: center; align-items: center; height: 200px; flex-direction: column;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 10px; color: #666;">Loading ACF Form Builder...</p>
            <p style="margin-top: 5px; color: #999; font-size: 12px;" id="initial-loading-content">Initializing drag & drop components...</p>
        </div>
        <div id="root">
            <!-- Initial loading state -->
            
        </div>
        
        <!-- PHP sẽ tạo submit buttons -->
        <div class="form-actions fixed-form-actions">
            <div style="display: flex; justify-content: center; align-items: center; max-width:1440px;margin:auto;">
                <!-- Status và time đã được chuyển lên top action bar -->
            </div>
        </div>
    </form>
    <!-- PHP sẽ inject data vào đây -->
    <script>
        // PHP sẽ truyền data vào window object
        window.ACF_DATA = {
        "lang": "<?= $currentLang ?>",
        "current_user": <?= $current_user; ?>,
        "inputConfig": {
          "border": {
            "width": 1,
            "style": "solid",
            "color": "#e2e8f0",
            "radius": 6
          },
          "background": {
            "color": "#ffffff",
            "hover": "#f8fafc",
            "focus": "#ffffff"
          },
          "text": {
            "color": "#1e293b",
            "fontSize": 14,
            "fontWeight": "normal"
          },
          "spacing": {
            "padding": { "x": 12, "y": 8 },
            "margin": { "x": 0, "y": 2 },
            "fieldGap": 16
          },
          "size": {
            "height": 38,
            "minHeight": 38
          },
          "wrapper": {
            "enabled": true,
            "border": {
              "width": 1,
              "style": "solid",
              "color": "#f1f5f9",
              "radius": 6
            },
            "background": "#ffffff",
            "padding": { "x": 12, "y": 12 },
            "margin": { "x": 0, "y": 8 }
          },
          "label": {
            "color": "#374151",
            "fontSize": 14,
            "fontWeight": "medium",
            "marginBottom": 4
          },
          "effects": {
            "transition": "all 0.2s ease",
            "focusRing": true,
            "hoverEnabled": true
          },
          "outerWrapper": {
            "enabled": true,
            "border": {
              "width": 0,
              "style": "solid",
              "color": "transparent",
              "radius": 8
            },
            "background": "#fafafa",
            "padding": { "x": 0, "y": 0 },
            "margin": { "x": 0, "y": 0 },
            "shadow": true
          }
        },
        "postType": <?= $posttype; ?>,
        "postEdit": <?= $post_encode; ?>
      };

        // Update last updated time
        //document.getElementById('last-updated').textContent = new Date().toLocaleString();
        // Handle form submission
        //document.getElementById('save-draft-btn').addEventListener('click', function() {
        //});
    </script>


    </div>
</div>

<?php Render::block('Backend\Footer'); ?>

