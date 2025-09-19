<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Libraries\Fastlang as Flang;
use Exception;
use System\Libraries\Render;
use ZipArchive;
use System\Libraries\Events;


class LibraryController extends BackendController
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

    public function index()
    {
        $activeItems = $this->getActiveItems();
        $installedItems = $this->scanDirectory();
        $items = $this->mergeItemData($installedItems, $activeItems);
        $stats = $this->calculateStats($items);

        $this->data('title', Flang::_e('title_' . $this->managerType));
        $this->data($this->managerType, $items);
        $this->data('stats', $stats);
        $this->data('activeItems', $activeItems);

        $result = Render::html($this->viewPath, $this->data);
        echo $result;
    }

    private function getActiveItems()
    {
        $activeItems = option($this->activeKey);
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
            if ($item['is_active']) $active++; else $inactive++;
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
        $action = $input['action'] ?? '';
        $itemSlug = $input['item'] ?? $input['theme'] ?? $input['plugin'] ?? $input[$this->managerType] ?? '';
        if (empty($action) || empty($itemSlug)) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        try {
            switch ($action) {
                case 'activate':
                    $result = $this->activateItem($itemSlug); break;
                case 'deactivate':
                    $result = $this->deactivateItem($itemSlug); break;
                case 'delete':
                    $result = $this->deleteItem($itemSlug); break;
                default:
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid action']); return;
            }
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function activateItem($itemSlug)
    {
        $activeItems = option($this->activeKey);
        if (!$activeItems || !is_array($activeItems)) $activeItems = [];
        $itemDir = $this->managerDir . '/' . $itemSlug;
        if (!is_dir($itemDir)) {
            return ['success' => false, 'message' => Flang::_e($this->managerType . '_not_found')];
        }
        foreach ($activeItems as $item) {
            if (is_array($item) && isset($item['name']) && strtolower($item['name']) === $itemSlug) {
                return ['success' => false, 'message' => Flang::_e($this->managerType . '_already_active')];
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
        return ['success' => true, 'message' => Flang::_e($this->managerType . '_activated_success')];
    }

    private function deactivateItem($itemSlug)
    {
        $activeItems = option($this->activeKey);
        if (!$activeItems || !is_array($activeItems)) $activeItems = [];
        $exists = false;
        foreach ($activeItems as $item) {
            if (is_array($item) && isset($item['name']) && strtolower($item['name']) === $itemSlug) {
                $exists = true; break;
            }
        }
        if (!$exists) {
            return ['success' => false, 'message' => Flang::_e($this->managerType . '_not_active')];
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
        return ['success' => true, 'message' => Flang::_e($this->managerType . '_deactivated_success')];
    }

    private function deleteItem($itemSlug)
    {
        $itemDir = $this->managerDir . '/' . $itemSlug;
        if (!is_dir($itemDir)) {
            return ['success' => false, 'message' => Flang::_e($this->managerType . '_not_found')];
        }
        $this->deactivateItem($itemSlug);
        $this->rrmdir($itemDir);
        return ['success' => true, 'message' => Flang::_e($this->managerType . '_deleted_success')];
    }

    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['item_files'])) {
            $this->jsonResponse(['success' => false, 'message' => Flang::_e('no_file_uploaded')]);
        }
        $files = $_FILES['item_files'];
        $errors = [];
        $successCount = 0;
        $existingItems = $this->scanDirectory();
        $existingSlugs = array_map(function ($item) { return strtolower($item['slug']); }, $existingItems);
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $fileCount; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($error !== UPLOAD_ERR_OK) { $errors[] = "$name: " . Flang::_e('upload_error'); continue; }
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') { $errors[] = "$name: " . Flang::_e('only_zip_allowed'); continue; }
            $zip = new ZipArchive();
            if ($zip->open($tmpName) === TRUE) {
                $firstEntry = $zip->getNameIndex(0);
                $itemFolder = explode('/', $firstEntry)[0];
                $itemSlug = strtolower($itemFolder);
                $extractPath = $this->managerDir . '/' . $itemFolder;
                if (in_array($itemSlug, $existingSlugs)) { $errors[] = "$name: " . Flang::_e($this->managerType . 's_exists') . " '$itemFolder'"; $zip->close(); continue; }
                if (is_dir($extractPath)) { $this->rrmdir($extractPath); }
                if (!$zip->extractTo($this->managerDir)) { $errors[] = "$name: " . Flang::_e('failed_extract_zip'); $zip->close(); continue; }
                $zip->close();
                $configPath = $extractPath . '/Config/Config.php';
                if (!file_exists($configPath)) { $this->rrmdir($extractPath); $errors[] = "$name: " . Flang::_e($this->managerType . '_invalid_missing_config'); continue; }
                $successCount++;
            } else { $errors[] = "$name: " . Flang::_e('failed_open_zip'); }
        }
        if ($successCount > 0) {
            $msg = Flang::_e($this->managerType . '_upload_success');
            if ($errors) $msg .= ' ' . Flang::_e($this->managerType . '_upload_errors') . ': ' . implode(' ', $errors);
            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }
    }

    public function uploadWithOverwrite()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['item_files'])) {
            $this->jsonResponse(['success' => false, 'message' => Flang::_e('no_file_uploaded')]);
        }
        $files = $_FILES['item_files'];
        $errors = [];
        $successCount = 0;
        $overwriteItems = [];
        if (isset($_POST['overwrite_items'])) { $overwriteItems = json_decode($_POST['overwrite_items'], true) ?: []; }
        $existingItems = $this->scanDirectory();
        $existingSlugs = array_map(function ($item) { return strtolower($item['slug']); }, $existingItems);
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $fileCount; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($error !== UPLOAD_ERR_OK) { $errors[] = "$name: " . Flang::_e('upload_error'); continue; }
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') { $errors[] = "$name: " . Flang::_e('only_zip_allowed'); continue; }
            $zip = new ZipArchive();
            if ($zip->open($tmpName) === TRUE) {
                $firstEntry = $zip->getNameIndex(0);
                $itemFolder = explode('/', $firstEntry)[0];
                $itemSlug = strtolower($itemFolder);
                $extractPath = $this->managerDir . '/' . $itemFolder;
                if (in_array($itemSlug, $existingSlugs)) {
                    if (!in_array($itemSlug, $overwriteItems)) { $errors[] = "$name: " . Flang::_e($this->managerType . 's_exists') . " '$itemFolder'"; $zip->close(); continue; }
                }
                if (is_dir($extractPath)) { $this->rrmdir($extractPath); }
                if (!$zip->extractTo($this->managerDir)) { $errors[] = "$name: " . Flang::_e('failed_extract_zip'); $zip->close(); continue; }
                $zip->close();
                $configPath = $extractPath . '/Config/Config.php';
                if (!file_exists($configPath)) { $this->rrmdir($extractPath); $errors[] = "$name: " . Flang::_e($this->managerType . '_invalid_missing_config'); continue; }
                $successCount++;
            } else { $errors[] = "$name: " . Flang::_e('failed_open_zip'); }
        }
        if ($successCount > 0) {
            $msg = Flang::_e($this->managerType . '_upload_success');
            if ($errors) $msg .= ' ' . Flang::_e($this->managerType . '_upload_errors') . ': ' . implode(' ', $errors);
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


