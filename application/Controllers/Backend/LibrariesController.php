<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use Exception;
use System\Libraries\Render;
use ZipArchive;
use System\Libraries\Events;
use App\Libraries\Fastlang as Flang;


class LibrariesController extends BackendController
{
    protected $managerType;
    protected $managerDir;
    protected $activeKey;
    protected $viewPath;

    protected function initializeManager($type, $dir, $activeKey, $viewPath)
    {
        $this->managerType = $type;
        $this->managerDir = $dir;
        $this->activeKey = $activeKey;
        $this->viewPath = $viewPath;
    }

    // Route handlers for /admin/libraries/plugins and /admin/libraries/themes
    public function plugins()
    {
        $this->initializeManager(
            'plugins',
            PATH_ROOT . '/plugins',
            'plugins_active',
            'Backend/libraries_index'
        );
        Flang::load('Global', APP_LANG);
        Flang::load('Backend/Plugins', APP_LANG);
        return $this->index();
    }

    public function themes()
    {
        $this->initializeManager(
            'themes',
            PATH_ROOT . '/themes',
            'themes_active',
            'Backend/libraries_index'
        );
        Flang::load('Global', APP_LANG);
        Flang::load('Backend/Themes', APP_LANG);
        return $this->index();
    }

    public function index()
    {
        $activeItems = $this->getActiveItems();
        $installedItems = $this->scanDirectory();
        $items = $this->mergeItemData($installedItems, $activeItems);
        $stats = $this->calculateStats($items);

        $this->data('title', __('title ' . $this->managerType));
        $this->data($this->managerType, $items);
        $this->data('stats', $stats);
        $this->data('activeItems', $activeItems);
        $this->data('managerType', $this->managerType);

        $result = Render::html($this->viewPath, $this->data);
        echo $result;
    }

    private function getActiveItems()
    {
        $activeItems = option($this->activeKey, APP_LANG, false);
        if ($activeItems && is_string($activeItems)) {
            $activeItems = json_decode($activeItems, true);
        }
        if (!$activeItems || !is_array($activeItems)) {
            return [];
        }
        $activeNames = [];
        foreach ($activeItems as $item) {
            if (is_array($item) && isset($item['name'])) {
                $activeNames[] = strtolower($item['name']);
            }
        }
        return $activeNames;
    }

    private function scanDirectory()
    {
        $items = [];
        if (!is_dir($this->managerDir)) {
            return $items;
        }
        $directories = glob($this->managerDir . '/*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $itemName = basename($dir);
            $configFile = $dir . '/Config/Config.php';
            if (file_exists($configFile)) {
                $config = include $configFile;
                if (isset($config[$this->managerType === 'plugins' ? 'plugin' : 'theme'])) {
                    $itemData = $config[$this->managerType === 'plugins' ? 'plugin' : 'theme'];
                    $itemData['directory'] = $itemName;
                    $itemData['slug'] = strtolower($itemName);
                    $itemData['status'] = $itemData['status'] ?? false;
                    $itemData['downloads'] = $itemData['downloads'] ?? 0;
                    $itemData['category'] = $itemData['category'] ?? 'General';
                    $itemData['rating'] = $itemData['rating'] ?? 0;
                    $itemData['description'] = $itemData['description'] ?? 'No description available';
                    if (is_string($itemData['category'])) {
                        $categories = array_map('trim', explode(',', $itemData['category']));
                        $itemData['categories'] = $categories;
                        $itemData['category'] = $categories[0];
                    } else {
                        $itemData['categories'] = [$itemData['category']];
                    }
                    $items[] = $itemData;
                }
            }
        }
        return $items;
    }

    private function mergeItemData($installedItems, $activeItems)
    {
        $merged = [];
        foreach ($installedItems as $item) {
            $item['is_active'] = in_array($item['slug'], $activeItems);
            $item['status_text'] = $item['is_active'] ? 'Active' : 'Inactive';
            $item['status_class'] = $item['is_active'] ? 'success' : 'warning';
            $item['actions'] = [
                'activate' => !$item['is_active'],
                'deactivate' => $item['is_active'],
                'settings' => true,
                'delete' => true
            ];
            $merged[] = $item;
        }

        if ($this->managerType === 'themes') {
            $activeCount = 0;
            foreach ($merged as &$item) {
                if ($item['is_active']) {
                    $activeCount++;
                    if ($activeCount > 1) {
                        $item['is_active'] = false;
                        $item['status_text'] = 'Inactive';
                        $item['status_class'] = 'warning';
                        $item['actions']['activate'] = true;
                        $item['actions']['deactivate'] = false;
                    }
                }
            }
        }

        usort($merged, function ($a, $b) {
            if ($a['is_active'] && !$b['is_active']) return -1;
            if (!$a['is_active'] && $b['is_active']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });
        return $merged;
    }

    private function calculateStats($items)
    {
        $total = count($items);
        $active = 0;
        $inactive = 0;
        foreach ($items as $item) {
            if ($item['is_active']) $active++;
            else $inactive++;
        }
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'updates' => 0,
            'store' => 156,
        ];
    }

    public function action()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        // Detect and set manager by type
        $reqType = $input['type'] ?? '';
        if ($reqType === 'themes' || isset($input['theme'])) {
            $this->initializeManager('themes', PATH_ROOT . '/themes', 'themes_active', 'Backend/libraries_index');
        } else if ($reqType === 'plugins' || isset($input['plugin'])) {
            $this->initializeManager('plugins', PATH_ROOT . '/plugins', 'plugins_active', 'Backend/libraries_index');
        }
        $action = $input['action'] ?? '';
        $itemSlug = $input['item'] ?? $input['theme'] ?? $input['plugin'] ?? $input[$this->managerType] ?? '';
        if (empty($action) || empty($itemSlug)) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        try {
            switch ($action) {
                case 'activate':
                
                    $result = $this->activateItem($itemSlug);
                    break;
                case 'deactivate':
                    $result = $this->deactivateItem($itemSlug);
                    break;
                case 'delete':
                    $result = $this->deleteItem($itemSlug);
                    break;
                default:
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid action']);
                    return;
            }
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function activateItem($itemSlug)
    {
        $activeItems = option($this->activeKey, APP_LANG, false);
        if ($activeItems && is_string($activeItems)) {
            $activeItems = json_decode($activeItems, true);
        }
        if (!$activeItems || !is_array($activeItems)) $activeItems = [];
        $itemDir = $this->managerDir . '/' . $itemSlug;
        if (!is_dir($itemDir)) {
            return ['success' => false, 'message' => __($this->managerType . ' not found')];
        }
        foreach ($activeItems as $item) {
            if (is_array($item) && isset($item['name']) && strtolower($item['name']) === $itemSlug) {
                return ['success' => false, 'message' => __($this->managerType . ' already active')];
            }
        }
        if ($this->managerType === 'themes') {
            $activeItems = [];
        }
        $activeItems[] = ['name' => $itemSlug];
        option_set($this->activeKey, json_encode($activeItems));
        // Fire event when plugin/theme is activated
        if ($this->managerType === 'plugins') {
            Events::run('Backend\\PluginActivateEvent', [
                'plugin' => $itemSlug,
                'type' => 'plugins',
            ]);
        } elseif ($this->managerType === 'themes') {
            Events::run('Backend\\ThemeActivateEvent', [
                'theme' => $itemSlug,
                'type' => 'themes',
            ]);
        }
        return ['success' => true, 'message' => __($this->managerType . ' activated successfully')];
    }

    private function deactivateItem($itemSlug)
    {
        $activeItems = option($this->activeKey, APP_LANG, false);
        if ($activeItems && is_string($activeItems)) {
            $activeItems = json_decode($activeItems, true);
        }
        if (!$activeItems || !is_array($activeItems)) $activeItems = [];
        $exists = false;
        foreach ($activeItems as $item) {
            if (is_array($item) && isset($item['name']) && strtolower($item['name']) === $itemSlug) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            return ['success' => false, 'message' => __($this->managerType . ' not active')];
        }
        $activeItems = array_filter($activeItems, function ($item) use ($itemSlug) {
            return !is_array($item) || !isset($item['name']) || strtolower($item['name']) !== $itemSlug;
        });
        option_set($this->activeKey, json_encode(array_values($activeItems)));
        // Fire event when plugin/theme is deactivated
        if ($this->managerType === 'plugins') {
            Events::run('Backend\\PluginDeactivateEvent', [
                'plugin' => $itemSlug,
                'type' => 'plugins',
            ]);
        } elseif ($this->managerType === 'themes') {
            Events::run('Backend\\ThemeDeactivateEvent', [
                'theme' => $itemSlug,
                'type' => 'themes',
            ]);
        }
        return ['success' => true, 'message' => __($this->managerType . ' deactivated successfully')];
    }

    private function deleteItem($itemSlug)
    {
        $itemDir = $this->managerDir . '/' . $itemSlug;
        if (!is_dir($itemDir)) {
            return ['success' => false, 'message' => __($this->managerType . ' not found')];
        }
        $this->deactivateItem($itemSlug);
        $this->rrmdir($itemDir);
        return ['success' => true, 'message' => __($this->managerType . ' deleted successfully')];
    }

    public function uploadWithOverwrite()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['item_files'])) {
            $this->jsonResponse(['success' => false, 'message' => __('no file uploaded')]);
        }
        // Detect and set manager by type (POST form)
        $reqType = $_POST['type'] ?? '';
        if ($reqType === 'themes') {
            $this->initializeManager('themes', PATH_ROOT . '/themes', 'themes_active', 'Backend/libraries_index');
        } else if ($reqType === 'plugins') {
            $this->initializeManager('plugins', PATH_ROOT . '/plugins', 'plugins_active', 'Backend/libraries_index');
        }
        $files = $_FILES['item_files'];
        $errors = [];
        $successCount = 0;
        $overwriteItems = [];
        if (isset($_POST['overwrite_items'])) {
            $overwriteItems = json_decode($_POST['overwrite_items'], true) ?: [];
        }
        $existingItems = $this->scanDirectory();
        $existingSlugs = array_map(function ($item) {
            return strtolower($item['slug']);
        }, $existingItems);
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $fileCount; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "$name: " . __('upload error');
                continue;
            }
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
                $errors[] = "$name: " . __('only zip allowed');
                continue;
            }
            $zip = new ZipArchive();
            if ($zip->open($tmpName) === TRUE) {
                $firstEntry = $zip->getNameIndex(0);
                $itemFolder = explode('/', $firstEntry)[0];
                $itemSlug = strtolower($itemFolder);
                $extractPath = $this->managerDir . '/' . $itemFolder;
                if (in_array($itemSlug, $existingSlugs)) {
                    if (!in_array($itemSlug, $overwriteItems)) {
                        $errors[] = "$name: " . __($this->managerType . ' exists') . " '$itemFolder'";
                        $zip->close();
                        continue;
                    }
                }
                if (is_dir($extractPath)) {
                    $this->rrmdir($extractPath);
                }
                if (!$zip->extractTo($this->managerDir)) {
                    $errors[] = "$name: " . __('failed extract zip');
                    $zip->close();
                    continue;
                }
                $zip->close();
                $configPath = $extractPath . '/Config/Config.php';
                if (!file_exists($configPath)) {
                    $this->rrmdir($extractPath);
                    $errors[] = "$name: " . __($this->managerType . ' invalid missing config');
                    continue;
                }
                $successCount++;
            } else {
                $errors[] = "$name: " . __('failed open zip');
            }
        }
        if ($successCount > 0) {
            $msg = __($this->managerType . ' upload success');
            if ($errors) $msg .= ' ' . __($this->managerType . ' upload errors') . ': ' . implode(' ', $errors);
            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }
    }

    private function rrmdir($dir)
    {
        if (!is_dir($dir)) return;
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object == "." || $object == "..") continue;
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}


