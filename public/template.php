<?php
// Template Generator API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
$config = require_once '../application/Config/Config.php';

// Get theme configuration
$theme_path = $config['theme']['theme_path'] ?? 'themes';
$theme_name = $config['theme']['theme_name'] ?? 'apkcms';
$template_dir = "../{$theme_path}/{$theme_name}/Frontend/";

// Ensure template directory exists
if (!is_dir($template_dir)) {
    mkdir($template_dir, 0755, true);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$page_type = $input['page_type'] ?? '';
$template_type = $input['template_type'] ?? '';
$custom_value = $input['custom_value'] ?? '';

if (empty($page_type) || empty($template_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Generate template filename based on type
$filename = generateTemplateFilename($page_type, $template_type, $custom_value);

if (!$filename) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid template configuration']);
    exit;
}

$filepath = $template_dir . $filename;

// Check if file already exists
if (file_exists($filepath)) {
    echo json_encode([
        'error' => 'Template file already exists',
        'filename' => $filename,
        'path' => $filepath
    ]);
    exit;
}

// Generate template content
$content = generateTemplateContent($page_type, $template_type, $custom_value, $filename);

// Create the template file
if (file_put_contents($filepath, $content) !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Template created successfully',
        'filename' => $filename,
        'path' => $filepath,
        'full_path' => realpath($filepath)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create template file']);
}

/**
 * Generate template filename based on page type and template type
 */
function generateTemplateFilename($page_type, $template_type, $custom_value = '') {
    $filename = '';
    
    switch ($page_type) {
        case 'Author Archive':
            if ($template_type === 'general') {
                $filename = 'author.php';
            } elseif ($template_type === 'nicename' && !empty($custom_value)) {
                $filename = 'author-' . sanitizeSlug($custom_value) . '.php';
            } elseif ($template_type === 'id' && !empty($custom_value)) {
                $filename = 'author-' . intval($custom_value) . '.php';
            }
            break;
            
            
        case 'Custom Post Type Archive':
            if ($template_type === 'general') {
                $filename = 'archive.php';
            } elseif ($template_type === 'posttype' && !empty($custom_value)) {
                $filename = 'archive-' . sanitizeSlug($custom_value) . '.php';
            }
            break;
            
        case 'Custom Taxonomy Archive':
            if ($template_type === 'general') {
                $filename = 'taxonomy.php';
            } elseif ($template_type === 'taxonomy' && !empty($custom_value)) {
                $filename = 'taxonomy-' . sanitizeSlug($custom_value) . '.php';
            } elseif ($template_type === 'taxonomy_term' && !empty($custom_value)) {
                $parts = explode('/', $custom_value);
                if (count($parts) === 2) {
                    $filename = 'taxonomy-' . sanitizeSlug($parts[0]) . '-' . sanitizeSlug($parts[1]) . '.php';
                }
            }
            break;
            
            
            
        case 'Single Post':
            if ($template_type === 'general') {
                $filename = 'single.php';
            } elseif ($template_type === 'posttype' && !empty($custom_value)) {
                $filename = 'single-' . sanitizeSlug($custom_value) . '.php';
            } elseif ($template_type === 'posttype_slug' && !empty($custom_value)) {
                $parts = explode('/', $custom_value);
                if (count($parts) === 2) {
                    $filename = 'single-' . sanitizeSlug($parts[0]) . '-' . sanitizeSlug($parts[1]) . '.php';
                }
            }
            break;
            
            
        case 'Static Page':
            if ($template_type === 'general') {
                $filename = 'page.php';
            } elseif ($template_type === 'slug' && !empty($custom_value)) {
                $filename = 'page-' . sanitizeSlug($custom_value) . '.php';
            } elseif ($template_type === 'id' && !empty($custom_value)) {
                $filename = 'page-' . intval($custom_value) . '.php';
            } elseif ($template_type === 'template' && !empty($custom_value)) {
                $filename = 'page-' . sanitizeSlug($custom_value) . '.php';
            }
            break;
            
        case 'Site Front Page':
            $filename = 'front-page.php';
            break;
            
            
        case 'Error 404':
            $filename = '404.php';
            break;
            
        case 'Search Results':
            $filename = 'search.php';
            break;
    }
    
    return $filename;
}

/**
 * Generate template content
 */
function generateTemplateContent($page_type, $template_type, $custom_value, $filename) {
    $date = date('Y-m-d H:i:s');
    $description = getTemplateDescription($page_type, $template_type, $custom_value);
    
    $content = "<?php
/**
 * Template: {$filename}
 * Page Type: {$page_type}
 * Template Type: {$template_type}
 * Description: {$description}
 * 
 * Auto-generated on: {$date}
 */

// Load language files if needed
// App\\Libraries\\Fastlang::load('TemplateName');

// Get template meta data
// get_template('_metas/meta_" . str_replace('.php', '', $filename) . "', ['locale' => \$locale]);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php get_template('_metas/meta_global', ['locale' => \$locale]); ?>
    <title><?php echo get_page_title(); ?></title>
</head>
<body>
    <?php get_header(); ?>
    
    <main class=\"main-content\">
        <div class=\"container\">
            <h1>{$page_type} Template</h1>
            <p>This is a custom template for: {$description}</p>
            
            <?php
            // Your template content here
            // Example: Display posts, pages, or custom content
            
            if (have_posts()) {
                while (have_posts()) {
                    the_post();
                    ?>
                    <article class=\"post\">
                        <h2><?php the_title(); ?></h2>
                        <div class=\"content\">
                            <?php the_content(); ?>
                        </div>
                    </article>
                    <?php
                }
            } else {
                ?>
                <p>No content found for this template.</p>
                <?php
            }
            ?>
        </div>
    </main>
    
    <?php get_footer(); ?>
</body>
</html>";

    return $content;
}

/**
 * Get template description
 */
function getTemplateDescription($page_type, $template_type, $custom_value) {
    $descriptions = [
        'Author Archive' => 'Author archive pages',
        'Custom Post Type Archive' => 'Custom post type archive pages',
        'Custom Taxonomy Archive' => 'Custom taxonomy archive pages',
        'Single Post' => 'Individual post pages',
        'Static Page' => 'Static pages',
        'Site Front Page' => 'Homepage/front page',
        'Error 404' => '404 error pages',
        'Search Results' => 'Search results pages'
    ];
    
    $base_desc = $descriptions[$page_type] ?? $page_type;
    
    if (!empty($custom_value)) {
        if ($template_type === 'slug' || $template_type === 'nicename') {
            $base_desc .= " for slug: {$custom_value}";
        } elseif ($template_type === 'id') {
            $base_desc .= " for ID: {$custom_value}";
        } elseif ($template_type === 'posttype') {
            $base_desc .= " for post type: {$custom_value}";
        } else {
            $base_desc .= " for: {$custom_value}";
        }
    }
    
    return $base_desc;
}

/**
 * Sanitize slug for filename
 */
function sanitizeSlug($slug) {
    return preg_replace('/[^a-z0-9\-_]/', '', strtolower(trim($slug)));
}
?>
