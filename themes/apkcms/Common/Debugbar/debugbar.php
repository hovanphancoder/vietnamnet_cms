<?php
global $debug_sql, $debug_events;
// Assume variables $profiles, $request, $debug_events, $debug_cache, $error_logs, $is_ajax have been set from DebugbarBlock
$performance = \System\Libraries\Monitor::endFramework();
$profiles = \System\Libraries\Monitor::getProfiles();
$views = \System\Libraries\Render::getViews();
$logs = \System\Libraries\Logger::getLogs();
$debug_cache = $GLOBALS['debug_cache'];
$request = [
    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri'        => '/' . APP_URI['uri'] ?? '',
    'controller' => defined('APP_ROUTE') ? APP_ROUTE['controller'] : '404',
    'action'     => defined('APP_ROUTE') ? APP_ROUTE['action'] : '404',
    'headers'    => function_exists('getallheaders') ? getallheaders() : []
];
$environment = [
    'app_name'         => config('app')['app_name'] ?? 'MyApp',
    'debug'            => config('app')['debug'] ?? false,
    'php_version'      => phpversion(),
    'memory_limit'     => ini_get('memory_limit'),
    'loaded_extensions' => get_loaded_extensions(),
    '_server'   => $_SERVER
];
?>
<style>
    /* Modern Debugbar with Tabs Interface */
    #debugbar {
        background: #0f172a;
        border-top: 3px solid #3b82f6;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px;
        color: #e2e8f0;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 48px;
        min-height: 48px;
        max-height: calc(100vh - 100px);
        z-index: 9999;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px 8px 0 0;
    }

    #debugbar.open {
        height: 400px;
    }

    .debugbar-resize-handle {
        height: 6px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        cursor: ns-resize;
        position: relative;
        z-index: 10000;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        visibility: hidden;
        border-radius: 3px 3px 0 0;
    }

    #debugbar.open .debugbar-resize-handle {
        opacity: 1;
        visibility: visible;
    }

    .debugbar-resize-handle:hover {
        background: linear-gradient(90deg, #2563eb, #7c3aed, #0891b2);
        height: 8px;
    }

    .debugbar-resize-handle::after {
        content: '⋮⋮⋮';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        color: #3b82f6;
        font-size: 14px;
        font-weight: 600;
        line-height: 1;
        pointer-events: none;
        transition: color 0.2s ease;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .debugbar-resize-handle:hover::after {
        color: #2563eb;
    }

    .debugbar-header {
        background: linear-gradient(135deg, #1e293b, #334155);
        padding: 12px 20px;
        border-bottom: 1px solid #475569;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .debugbar-header:hover {
        background: linear-gradient(135deg, #334155, #475569);
    }

    .debugbar-title {
        font-weight: 700;
        color: #ffffff;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .debugbar-stats {
        display: flex;
        gap: 20px;
        font-size: 13px;
        color: #cbd5e1;
        font-weight: 500;

        span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    }

    .debugbar-content {
        display: none;
        height: calc(100% - 48px);
        background: #0f172a;
        overflow: hidden;
        flex: 1;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #debugbar.open .debugbar-content {
        display: block;
    }

    .debugbar-content.active {
        display: block;
    }

    .debugbar-tabs {
        display: flex;
        background: linear-gradient(135deg, #1e293b, #334155);
        border-bottom: 1px solid #475569;
        overflow-x: auto;
        padding: 0 4px;
    }

    .debugbar-tab {
        padding: 12px 16px;
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        border-bottom: 3px solid transparent;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        position: relative;
        border-radius: 6px 6px 0 0;
        margin: 4px 2px 0 2px;
    }

    .debugbar-tab:hover {
        background: rgba(59, 130, 246, 0.1);
        color: #e2e8f0;
        transform: translateY(-1px);
    }

    .debugbar-tab.active {
        background: #0f172a;
        color: #ffffff;
        border-bottom-color: #3b82f6;
        box-shadow: 0 -2px 8px rgba(59, 130, 246, 0.2);
    }

    .debugbar-panel {
        display: none;
        height: 100%;
        overflow-y: auto;
        padding: 20px;
        background: #0f172a;
    }

    .debugbar-panel.active {
        display: block;
    }

    .debugbar-code {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 16px;
        font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.7;
        color: #e2e8f0;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-word;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .debugbar-code::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        border-radius: 8px 8px 0 0;
    }

    .debugbar-query {
        background: #1e293b;
        border-left: 4px solid #3b82f6;
        margin: 12px 0;
        padding: 16px;
        border-radius: 0 8px 8px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-query:hover {
        transform: translateX(2px);
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.1);
    }

    .debugbar-query-header {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
        font-weight: 700;
        color: #60a5fa;
        font-size: 14px;
    }

    .debugbar-query-time {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
    }

    .debugbar-query-sql {
        color: #e2e8f0;
        margin-bottom: 8px;
        font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        background: #0f172a;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #334155;
    }

    .debugbar-query-params {
        color: #a78bfa;
        font-size: 13px;
        font-weight: 500;
        background: #1e293b;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #475569;
    }

    .debugbar-profile-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #30363d;
    }

    .debugbar-profile-item:last-child {
        border-bottom: none;
    }

    .debugbar-profile-label {
        color: #7dd3fc;
        font-weight: 500;
    }

    .debugbar-profile-stats {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #94a3b8;
    }

    .debugbar-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .debugbar-info-item {
        background: #1e293b;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid #334155;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-info-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    .debugbar-info-label {
        font-weight: 700;
        color: #60a5fa;
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .debugbar-info-value {
        color: #e2e8f0;
        word-break: break-word;
        font-size: 14px;
        font-weight: 500;
    }

    .debugbar-toggle {
        color: #94a3b8;
        font-size: 12px;
    }

    .debugbar-toggle:hover {
        color: #f1f5f9;
    }

    /* Request Tab Styling */
    .debugbar-section {
        margin: 24px 0;
        background: #1e293b;
        border-radius: 12px;
        border: 1px solid #334155;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-section:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        transform: translateY(-1px);
    }

    .debugbar-section-title {
        background: linear-gradient(135deg, #334155, #475569);
        padding: 16px 20px;
        font-weight: 700;
        color: #60a5fa;
        border-bottom: 1px solid #475569;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .debugbar-section-title:hover {
        background: linear-gradient(135deg, #475569, #64748b);
    }

    .debugbar-method-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-method-get {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .debugbar-method-post {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .debugbar-method-put {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .debugbar-method-delete {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
    }

    .debugbar-method-patch {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    /* .debugbar-headers-list,
    .debugbar-server-list,
    .debugbar-post-list,
    .debugbar-get-list {
        max-height: 200px;
        overflow-y: auto;
    } */

    .debugbar-header-item,
    .debugbar-server-item,
    .debugbar-post-item,
    .debugbar-get-item {
        display: flex;
        padding: 12px 20px;
        border-bottom: 1px solid #334155;
        align-items: flex-start;
        transition: all 0.2s ease;
    }

    .debugbar-header-item:hover,
    .debugbar-server-item:hover,
    .debugbar-post-item:hover,
    .debugbar-get-item:hover {
        background: #1e293b;
    }

    .debugbar-header-item:last-child,
    .debugbar-server-item:last-child,
    .debugbar-post-item:last-child,
    .debugbar-get-item:last-child {
        border-bottom: none;
    }

    .debugbar-header-key,
    .debugbar-server-key,
    .debugbar-post-key,
    .debugbar-get-key {
        min-width: 220px;
        font-weight: 700;
        color: #60a5fa;
        font-size: 13px;
        margin-right: 20px;
        word-break: break-word;
        background: #0f172a;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #334155;
    }

    .debugbar-header-value,
    .debugbar-server-value,
    .debugbar-post-value,
    .debugbar-get-value {
        flex: 1;
        color: #e2e8f0;
        font-size: 13px;
        word-break: break-word;
        line-height: 1.5;
        font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    .debugbar-json {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 16px;
        margin: 12px 0;
        font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 13px;
        color: #e2e8f0;
        overflow-x: auto;
        line-height: 1.6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .debugbar-json::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        border-radius: 8px 8px 0 0;
    }

    .debugbar-empty {
        padding: 20px;
        text-align: center;
        color: #94a3b8;
        font-style: italic;
        font-size: 14px;
    }

    /* Environment Tab Styling */
    .debugbar-status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-status-enabled {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .debugbar-status-disabled {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
    }

    .debugbar-extensions-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .debugbar-extension-item {
        flex: 1;
        background: #21262d;
        border: 1px solid #30363d;
        border-radius: 4px;
        padding: 6px 10px;
        text-align: center;
    }

    .debugbar-extension-name {
        color: #7dd3fc;
        font-size: 12px;
        font-weight: 500;
    }

    .debugbar-server-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .debugbar-server-info-item {
        background: #161b22;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #30363d;
    }

    .debugbar-server-info-label {
        font-weight: 600;
        color: #7dd3fc;
        margin-bottom: 5px;
        font-size: 12px;
    }

    .debugbar-server-info-value {
        color: #f1f5f9;
        word-break: break-word;
        font-size: 12px;
    }

    .debugbar-user-agent {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 12px;
        background: #0d1117;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #21262d;
        color: #f0f6fc;
        line-height: 1.4;
    }

    .debugbar-performance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .debugbar-performance-item {
        background: #1e293b;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #334155;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .debugbar-performance-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .debugbar-performance-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
    }

    .debugbar-performance-label {
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 12px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .debugbar-performance-value {
        color: #60a5fa;
        font-size: 18px;
        font-weight: 800;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* Cookies Tab Styling - Simplified */

    .debugbar-cookie-simple-item {
        display: flex;
        padding: 8px 12px;
        border-bottom: 1px solid #30363d;
        align-items: flex-start;
        transition: all 0.2s ease;
    }

    .debugbar-cookie-simple-item:hover {
        background: #21262d;
    }

    .debugbar-cookie-simple-item:last-child {
        border-bottom: none;
    }

    .debugbar-cookie-simple-key {
        min-width: 200px;
        font-weight: 600;
        color: #7dd3fc;
        font-size: 12px;
        margin-right: 15px;
        word-break: break-word;
    }

    .debugbar-cookie-simple-value {
        flex: 1;
        color: #f1f5f9;
        font-size: 12px;
        word-break: break-word;
        line-height: 1.4;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    /* Views Tab Styling */

    .debugbar-view-item {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        margin: 12px 0;
        padding: 16px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .debugbar-view-item:hover {
        border-color: #3b82f6;
        background: #334155;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.1);
    }

    .debugbar-view-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .debugbar-view-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .debugbar-view-type {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-view-type-layout {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .debugbar-view-type-component {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .debugbar-view-type-block {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
    }

    .debugbar-view-type-view {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .debugbar-view-type-template {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .debugbar-view-type-block {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
    }

    .debugbar-view-type-block_data {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .debugbar-view-type-block_instance {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
        opacity: 0.8;
    }

    .debugbar-view-type-pagination {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .debugbar-view-type-input {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .debugbar-view-filename {
        font-weight: 600;
        color: #f1f5f9;
        font-size: 14px;
    }



    .debugbar-view-path {
        color: #94a3b8;
        font-size: 12px;
        word-break: break-all;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    /* Message Logs Tab Styling */

    /* Cache Tab Styling */

    .debugbar-cache-item {
        background: #161b22;
        border: 1px solid #30363d;
        border-radius: 6px;
        margin: 8px 0;
        padding: 12px;
        transition: all 0.2s ease;
    }

    .debugbar-cache-item:hover {
        border-color: #58a6ff;
        background: #1c2128;
    }

    .debugbar-cache-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .debugbar-cache-operation {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .debugbar-cache-operation-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .debugbar-cache-operation-set {
        background: #238636;
        color: white;
    }

    .debugbar-cache-operation-get {
        background: #58a6ff;
        color: white;
    }

    .debugbar-cache-operation-delete {
        background: #da3633;
        color: white;
    }

    .debugbar-cache-operation-clear {
        background: #fb8500;
        color: white;
    }

    .debugbar-cache-driver {
        font-size: 11px;
        color: #8b949e;
        background: #21262d;
        padding: 2px 6px;
        border-radius: 4px;
    }

    .debugbar-cache-success {
        color: #238636;
        font-weight: bold;
    }

    .debugbar-cache-fail {
        color: #da3633;
        font-weight: bold;
    }

    .debugbar-cache-time {
        font-size: 12px;
        color: #8b949e;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    .debugbar-cache-details {
        margin-top: 8px;
    }

    .debugbar-cache-key {
        color: #f1f5f9;
        font-size: 12px;
        margin-bottom: 8px;
        word-break: break-all;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    .debugbar-cache-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 8px;
    }

    .debugbar-cache-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .debugbar-cache-meta-label {
        font-size: 11px;
        color: #8b949e;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .debugbar-cache-meta-value {
        color: #f1f5f9;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 11px;
    }

    .debugbar-cache-message {
        color: #f85149;
        font-size: 11px;
        margin-top: 5px;
        padding: 4px 8px;
        background: #2d1b1b;
        border-radius: 4px;
        border-left: 2px solid #f85149;
    }

    .debugbar-log-item {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        margin: 12px 0;
        padding: 16px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .debugbar-log-item:hover {
        border-color: #3b82f6;
        background: #334155;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.1);
    }

    .debugbar-log-info {
        border-left: 4px solid #58a6ff;
    }

    .debugbar-log-warning {
        border-left: 4px solid #fb8500;
    }

    .debugbar-log-error {
        border-left: 4px solid #f85149;
    }

    .debugbar-log-debug {
        border-left: 4px solid #7c3aed;
    }

    .debugbar-log-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .debugbar-log-level-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .debugbar-log-level-info {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .debugbar-log-level-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .debugbar-log-level-error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .debugbar-log-level-debug {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .debugbar-log-time {
        color: #94a3b8;
        font-size: 11px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    }

    .debugbar-log-message {
        color: #f1f5f9;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 8px;
        word-break: break-word;
    }

    .debugbar-log-location {
        color: #94a3b8;
        font-size: 11px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        background: #0d1117;
        padding: 6px 8px;
        border-radius: 4px;
        border: 1px solid #21262d;
    }

    /* JSON Log Styling */
    .debugbar-log-json-badge {
        display: inline-block;
        background: #7c3aed;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 6px;
    }

    .debugbar-log-json-toggle {
        background: #21262d;
        border: 1px solid #30363d;
        border-radius: 4px;
        padding: 8px 12px;
        margin: 8px 0;
        transition: all 0.2s ease;
        user-select: none;
    }

    .debugbar-log-json-toggle:hover {
        background: #30363d;
        border-color: #58a6ff;
    }

    .debugbar-log-json-icon {
        display: inline-block;
        margin-right: 8px;
        font-weight: bold;
        color: #58a6ff;
        transition: transform 0.2s ease;
    }

    .debugbar-log-json-content {
        margin-top: 8px;
        border: 1px solid #30363d;
        border-radius: 4px;
        overflow: hidden;
    }

    .debugbar-log-json-pretty {
        background: #0d1117;
        color: #f0f6fc;
        padding: 12px;
        margin: 0;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.5;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-word;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Collapsible Sections Styling */
    .debugbar-section-content {
        display: block;
        padding: 15px;
        border-top: 1px solid #30363d;
    }

    .debugbar-section-content.collapsed {
        display: none;
    }

    .debugbar-section-title {
        user-select: none;
        transition: all 0.2s ease;
    }

    .debugbar-section-title:hover {
        background: #21262d;
    }

    .debugbar-sql-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }

    .debugbar-stat-item {
        background: #161b22;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #30363d;
        text-align: center;
    }

    .debugbar-stat-label {
        font-weight: 600;
        color: #8b949e;
        margin-bottom: 8px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .debugbar-stat-value {
        color: #7dd3fc;
        font-size: 16px;
        font-weight: 700;
    }

    /* Scrollbar styling */
    .debugbar-panel::-webkit-scrollbar {
        width: 10px;
    }

    .debugbar-panel::-webkit-scrollbar-track {
        background: #0f172a;
        border-radius: 5px;
    }

    .debugbar-panel::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #334155, #475569);
        border-radius: 5px;
        border: 2px solid #0f172a;
    }

    .debugbar-panel::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #475569, #64748b);
    }

    /* Add smooth scrolling */
    .debugbar-panel {
        scroll-behavior: smooth;
    }

    /* Add loading animation */
    @keyframes debugbar-pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .debugbar-loading {
        animation: debugbar-pulse 2s infinite;
    }
</style>

<!-- Modern Debugbar with Tabs -->
<div id="debugbar">
    <div class="debugbar-resize-handle" id="debugbar-resize-handle"></div>
    <div class="debugbar-header" onclick="toggleDebugbar()">
        <div class="debugbar-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 12l2 2 4-4" />
                <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3" />
                <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3" />
                <path d="M12 3c0 1-1 3-3 3s-3-2-3-3 1-3 3-3 3 2 3 3" />
                <path d="M12 21c0-1 1-3 3-3s3 2 3 3-1 3-3 3-3-2-3-3" />
            </svg>
            Debug Console
        </div>
        <div class="debugbar-stats">
            <span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12,6 12,12 16,14" />
                </svg>
                <?= round($performance['execution_time'] * 1000, 2) ?>ms
            </span>
            <span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="10" rx="2" ry="2" />
                    <rect x="6" y="11" width="4" height="2" rx="0.5" />
                    <rect x="14" y="11" width="4" height="2" rx="0.5" />
                    <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2" />
                </svg>
                <?= $performance['memory_used'] ?>
            </span>
            <?php if (isset($performance['cpu_usage']) && $performance['cpu_usage'] !== null): ?>
                <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                        <line x1="8" y1="21" x2="16" y2="21" />
                        <line x1="12" y1="17" x2="12" y2="21" />
                    </svg>
                    <?= $performance['cpu_usage'] ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="debugbar-toggle" id="debugbar-toggle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6,9 12,15 18,9" />
            </svg>
        </div>
    </div>

    <div class="debugbar-content" id="debugbar-content">
        <div class="debugbar-tabs">
            <button class="debugbar-tab active" onclick="switchTab('sql')">
                SQL (<?= isset($debug_sql) ? count($debug_sql) : 0 ?>)
            </button>
            <button class="debugbar-tab" onclick="switchTab('profiling')">
                Profiling (<?= isset($profiles) ? count($profiles) : 0 ?>)
            </button>
            <button class="debugbar-tab" onclick="switchTab('views')">
                Views (<?= isset($views) ? count($views) : 0 ?>)
            </button>
            <button class="debugbar-tab" onclick="switchTab('request')">
                Request
            </button>
            <button class="debugbar-tab" onclick="switchTab('environment')">
                Environment
            </button>
            <button class="debugbar-tab" onclick="switchTab('events')">
                Events (<?= isset($debug_events) ? count($debug_events) : 0 ?>)
            </button>
            <button class="debugbar-tab" onclick="switchTab('cache')">
                Cache
            </button>
            <button class="debugbar-tab" onclick="switchTab('errors')">
                Errors
            </button>
            <button class="debugbar-tab" onclick="switchTab('cookies')">
                Cookies
            </button>
            <button class="debugbar-tab" onclick="switchTab('logs')">
                Logs (<?= count($logs) ?>)
            </button>
        </div>

        <!-- SQL Queries Tab -->
        <div class="debugbar-panel active" id="tab-sql">
            <?php if (isset($debug_sql) && !empty($debug_sql)): ?>
                <!-- SQL Summary -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title" onclick="toggleSection('sql-summary')" style="cursor: pointer;">
                        📊 SQL Summary <span id="sql-summary-toggle">▲</span>
                    </div>
                    <div class="debugbar-section-content" id="sql-summary-content">
                        <div class="debugbar-sql-stats">
                            <div class="debugbar-stat-item">
                                <div class="debugbar-stat-label">Total Queries</div>
                                <div class="debugbar-stat-value"><?= count($debug_sql) ?></div>
                            </div>
                            <div class="debugbar-stat-item">
                                <div class="debugbar-stat-label">Total Time</div>
                                <div class="debugbar-stat-value"><?= round(array_sum(array_column($debug_sql, 'time')) * 1000, 2) ?>ms</div>
                            </div>
                            <div class="debugbar-stat-item">
                                <div class="debugbar-stat-label">Avg Time</div>
                                <div class="debugbar-stat-value"><?= round((array_sum(array_column($debug_sql, 'time')) / count($debug_sql)) * 1000, 2) ?>ms</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SQL Queries List -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title" onclick="toggleSection('sql-queries')" style="cursor: pointer;">
                        🔍 SQL Queries (<?= count($debug_sql) ?>) <span id="sql-queries-toggle">▲</span>
                    </div>
                    <div class="debugbar-section-content" id="sql-queries-content">
                        <?php foreach ($debug_sql as $i => $q): ?>
                            <div class="debugbar-query">
                                <div class="debugbar-query-header">
                                    <span>Query #<?= $i + 1 ?></span>
                                    <span class="debugbar-query-time"><?= round(1000 * $q['time'], 2) ?>ms</span>
                                </div>
                                <div class="debugbar-query-sql"><?= htmlspecialchars($q['sql']) ?></div>
                                <?php if (!empty($q['params'])): ?>
                                    <div class="debugbar-query-params">
                                        <strong>Params:</strong> <?= htmlspecialchars(json_encode($q['params'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="debugbar-code">No SQL queries executed</div>
            <?php endif; ?>
        </div>

        <!-- Profiling Tab -->
        <div class="debugbar-panel" id="tab-profiling">
            <?php if (isset($profiles) && !empty($profiles)): ?>
                <?php foreach ($profiles as $p): ?>
                    <div class="debugbar-profile-item">
                        <div class="debugbar-profile-label"><?= htmlspecialchars($p['label']) ?></div>
                        <div class="debugbar-profile-stats">
                            <span><?= round($p['time'], 4) ?>s</span>
                            <span><?= $p['memory'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="debugbar-code">No profiling data available</div>
            <?php endif; ?>
        </div>

        <!-- Views Tab -->
        <div class="debugbar-panel" id="tab-views">
            <?php if (!empty($views)): ?>
                <!-- Views List -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title">📄 Rendered Views (<?= count($views) ?>)</div>
                    <div class="debugbar-views-list">
                        <?php foreach ($views as $i => $view): ?>
                            <div class="debugbar-view-item">
                                <div class="debugbar-view-header">
                                    <div class="debugbar-view-name">
                                        <span class="debugbar-view-type debugbar-view-type-<?= $view['type'] ?>">
                                            <?= ucfirst($view['type']) ?>
                                        </span>
                                        <span class="debugbar-view-filename"><?= htmlspecialchars(basename($view['name'])) ?></span>
                                    </div>
                                </div>
                                <div class="debugbar-view-path">
                                    <strong>Path:</strong> <?= htmlspecialchars($view['path']) ?>
                                </div>
                                <?php if (isset($view['duration_ms']) && $view['duration_ms'] !== null): ?>
                                    <div class="debugbar-view-path">
                                        <strong>Duration:</strong> <?= htmlspecialchars($view['duration_ms']) ?> ms
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($view['data_keys'])): ?>
                                    <div class="debugbar-view-path">
                                        <strong>Extracted vars:</strong> <?= htmlspecialchars(implode(', ', $view['data_keys'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="debugbar-empty">
                    <div style="font-size: 48px; margin-bottom: 15px;">📄</div>
                    <div>No views rendered</div>
                    <div style="font-size: 12px; color: #8b949e; margin-top: 5px;">
                        Views will appear here when they are rendered by the application
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Request Tab -->
        <div class="debugbar-panel" id="tab-request">
            <!-- Basic Request Info -->
            <div class="debugbar-section">
                <div class="debugbar-section-title" onclick="toggleSection('request-basic')" style="cursor: pointer;">
                    ℹ️ Basic Request Info <span id="request-basic-toggle">▲</span>
                </div>
                <div class="debugbar-section-content" id="request-basic-content">
                    <div class="debugbar-info-grid">
                        <div class="debugbar-info-item">
                            <div class="debugbar-info-label">Method</div>
                            <div class="debugbar-info-value">
                                <span class="debugbar-method-badge debugbar-method-<?= strtolower($request['method']) ?>">
                                    <?= htmlspecialchars($request['method']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="debugbar-info-item">
                            <div class="debugbar-info-label">URI</div>
                            <div class="debugbar-info-value"><?= htmlspecialchars($request['uri']) ?></div>
                        </div>
                        <div class="debugbar-info-item">
                            <div class="debugbar-info-label">Controller</div>
                            <div class="debugbar-info-value"><?= htmlspecialchars($request['controller']) ?></div>
                        </div>
                        <div class="debugbar-info-item">
                            <div class="debugbar-info-label">Action</div>
                            <div class="debugbar-info-value"><?= htmlspecialchars($request['action']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Headers Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title" onclick="toggleSection('request-headers')" style="cursor: pointer;">
                    📋 Headers (<?= !empty($request['headers']) ? count($request['headers']) : 0 ?>) <span id="request-headers-toggle">▲</span>
                </div>
                <div class="debugbar-section-content" id="request-headers-content">
                    <div class="debugbar-headers-list">
                        <?php if (!empty($request['headers'])): ?>
                            <?php foreach ($request['headers'] as $key => $value): ?>
                                <div class="debugbar-header-item">
                                    <div class="debugbar-header-key"><?= htmlspecialchars($key) ?></div>
                                    <div class="debugbar-header-value"><?= htmlspecialchars($value) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="debugbar-empty">No headers available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Server Variables Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title" onclick="toggleSection('request-server')" style="cursor: pointer;">
                    🖥️ Server Variables <span id="request-server-toggle">▲</span>
                </div>
                <div class="debugbar-section-content" id="request-server-content">
                    <div class="debugbar-server-list">
                        <?php
                        $importantServerVars = [
                            'HTTP_HOST',
                            'SERVER_NAME',
                            'SERVER_PORT',
                            'HTTPS',
                            'REQUEST_SCHEME',
                            'HTTP_USER_AGENT',
                            'HTTP_ACCEPT',
                            'HTTP_ACCEPT_LANGUAGE',
                            'HTTP_ACCEPT_ENCODING',
                            'HTTP_CONNECTION',
                            'HTTP_UPGRADE_INSECURE_REQUESTS',
                            'HTTP_CACHE_CONTROL',
                            'REMOTE_ADDR',
                            'REMOTE_PORT',
                            'SERVER_SOFTWARE',
                            'SERVER_PROTOCOL',
                            'REQUEST_TIME',
                            'REQUEST_TIME_FLOAT',
                            'QUERY_STRING',
                            'REQUEST_URI'
                        ];

                        foreach ($importantServerVars as $var):
                            if (isset($_SERVER[$var])):
                        ?>
                                <div class="debugbar-server-item">
                                    <div class="debugbar-server-key"><?= htmlspecialchars($var) ?></div>
                                    <div class="debugbar-server-value"><?= htmlspecialchars($_SERVER[$var]) ?></div>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>

            <!-- POST Data Section -->
            <?php if (!empty($_POST)): ?>
                <div class="debugbar-section">
                    <div class="debugbar-section-title" onclick="toggleSection('request-post')" style="cursor: pointer;">
                        📝 POST Data (<?= count($_POST) ?>) <span id="request-post-toggle">▲</span>
                    </div>
                    <div class="debugbar-section-content" id="request-post-content">
                        <div class="debugbar-post-list">
                            <?php foreach ($_POST as $key => $value): ?>
                                <div class="debugbar-post-item">
                                    <div class="debugbar-post-key"><?= htmlspecialchars($key) ?></div>
                                    <div class="debugbar-post-value">
                                        <?php if (is_array($value) || is_object($value)): ?>
                                            <pre class="debugbar-json"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                        <?php else: ?>
                                            <?= htmlspecialchars($value) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- GET Data Section -->
            <?php if (!empty($_GET)): ?>
                <div class="debugbar-section">
                    <div class="debugbar-section-title" onclick="toggleSection('request-get')" style="cursor: pointer;">
                        🔍 GET Data (<?= count($_GET) ?>) <span id="request-get-toggle">▲</span>
                    </div>
                    <div class="debugbar-section-content" id="request-get-content">
                        <div class="debugbar-get-list">
                            <?php foreach ($_GET as $key => $value): ?>
                                <div class="debugbar-get-item">
                                    <div class="debugbar-get-key"><?= htmlspecialchars($key) ?></div>
                                    <div class="debugbar-get-value"><?= htmlspecialchars($value) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Environment Tab -->
        <div class="debugbar-panel" id="tab-environment">
            <!-- Application Info Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title">🚀 Application Info</div>
                <div class="debugbar-info-grid">
                    <div class="debugbar-info-item">
                        <div class="debugbar-info-label">App Name</div>
                        <div class="debugbar-info-value"><?= htmlspecialchars($environment['app_name']) ?></div>
                    </div>
                    <div class="debugbar-info-item">
                        <div class="debugbar-info-label">Debug Mode</div>
                        <div class="debugbar-info-value">
                            <span class="debugbar-status-badge debugbar-status-<?= $environment['debug'] ? 'enabled' : 'disabled' ?>">
                                <?= $environment['debug'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </div>
                    </div>
                    <div class="debugbar-info-item">
                        <div class="debugbar-info-label">PHP Version</div>
                        <div class="debugbar-info-value"><?= htmlspecialchars($environment['php_version']) ?></div>
                    </div>
                    <div class="debugbar-info-item">
                        <div class="debugbar-info-label">Memory Limit</div>
                        <div class="debugbar-info-value"><?= htmlspecialchars($environment['memory_limit']) ?></div>
                    </div>
                </div>
            </div>

            <!-- PHP Extensions Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title">🔧 PHP Extensions (<?= count($environment['loaded_extensions']) ?>)</div>
                <div class="debugbar-extensions-list">
                    <?php
                    $extensions = $environment['loaded_extensions'];
                    $chunks = array_chunk($extensions, 4); // 4 extensions per row
                    foreach ($chunks as $chunk):
                    ?>
                        <div class="debugbar-extensions-row">
                            <?php foreach ($chunk as $ext): ?>
                                <div class="debugbar-extension-item">
                                    <span class="debugbar-extension-name"><?= htmlspecialchars($ext) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Server Info Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title">🖥️ Server Information</div>
                <div class="debugbar-server-info-grid">
                    <?php
                    $serverInfo = [
                        'HTTP_HOST' => 'Host',
                        'SERVER_NAME' => 'Server Name',
                        'SERVER_PORT' => 'Port',
                        'SERVER_SOFTWARE' => 'Software',
                        'SERVER_PROTOCOL' => 'Protocol',
                        'REQUEST_SCHEME' => 'Scheme',
                        'HTTPS' => 'HTTPS',
                        'REMOTE_ADDR' => 'Client IP',
                        'HTTP_USER_AGENT' => 'User Agent'
                    ];

                    foreach ($serverInfo as $key => $label):
                        if (isset($_SERVER[$key])):
                    ?>
                            <div class="debugbar-server-info-item">
                                <div class="debugbar-server-info-label"><?= htmlspecialchars($label) ?></div>
                                <div class="debugbar-server-info-value">
                                    <?php if ($key === 'HTTP_USER_AGENT'): ?>
                                        <div class="debugbar-user-agent"><?= htmlspecialchars($_SERVER[$key]) ?></div>
                                    <?php else: ?>
                                        <?= htmlspecialchars($_SERVER[$key]) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- Performance Info Section -->
            <div class="debugbar-section">
                <div class="debugbar-section-title">⚡ Performance Info</div>
                <div class="debugbar-performance-grid">
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Memory Usage</div>
                        <div class="debugbar-performance-value">
                            <?= \System\Libraries\Monitor::formatMemorySize(memory_get_usage()) ?>
                        </div>
                    </div>
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Peak Memory</div>
                        <div class="debugbar-performance-value">
                            <?= \System\Libraries\Monitor::formatMemorySize(memory_get_peak_usage()) ?>
                        </div>
                    </div>
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Memory Limit</div>
                        <div class="debugbar-performance-value">
                            <?= ini_get('memory_limit') ?>
                        </div>
                    </div>
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Max Execution Time</div>
                        <div class="debugbar-performance-value">
                            <?= ini_get('max_execution_time') ?>s
                        </div>
                    </div>
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Upload Max Filesize</div>
                        <div class="debugbar-performance-value">
                            <?= ini_get('upload_max_filesize') ?>
                        </div>
                    </div>
                    <div class="debugbar-performance-item">
                        <div class="debugbar-performance-label">Post Max Size</div>
                        <div class="debugbar-performance-value">
                            <?= ini_get('post_max_size') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raw Server Data (Collapsible) -->
            <div class="debugbar-section">
                <div class="debugbar-section-title" onclick="toggleServerData()" style="cursor: pointer;">
                    📄 Raw Server Data <span id="server-data-toggle">▲</span>
                </div>
                <div class="debugbar-server-raw" id="server-data-content">
                    <div class="debugbar-code">
                        <?= htmlspecialchars(json_encode($_SERVER, JSON_PRETTY_PRINT)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Tab -->
        <div class="debugbar-panel" id="tab-events">
            <div class="debugbar-code">
                <?= isset($debug_events) ? htmlspecialchars(print_r($debug_events, true)) : 'No events logged' ?>
            </div>
        </div>

        <!-- Cache Tab -->
        <div class="debugbar-panel" id="tab-cache">
            <?php if (!empty($debug_cache)): ?>
                <!-- Cache Operations List -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title">💾 Cache Operations (<?= count($debug_cache) ?>)</div>
                    <div class="debugbar-cache-list">
                        <?php foreach ($debug_cache as $i => $operation): ?>
                            <div class="debugbar-cache-item debugbar-cache-<?= strtolower($operation['operation']) ?>">
                                <div class="debugbar-cache-header">
                                    <div class="debugbar-cache-operation">
                                        <span class="debugbar-cache-operation-badge debugbar-cache-operation-<?= strtolower($operation['operation']) ?>">
                                            <?= strtoupper($operation['operation']) ?>
                                        </span>
                                        <span class="debugbar-cache-driver"><?= $operation['driver'] ?></span>
                                        <?php if ($operation['success']): ?>
                                            <span class="debugbar-cache-success">✓</span>
                                        <?php else: ?>
                                            <span class="debugbar-cache-fail">✗</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="debugbar-cache-time"><?= $operation['execution_time'] ?>ms</div>
                                </div>

                                <div class="debugbar-cache-details">
                                    <div class="debugbar-cache-key">
                                        <strong>Key:</strong> <?= htmlspecialchars($operation['key']) ?>
                                    </div>

                                    <div class="debugbar-cache-meta">
                                        <div class="debugbar-cache-meta-item">
                                            <span class="debugbar-cache-meta-label">Type:</span>
                                            <span class="debugbar-cache-meta-value"><?= htmlspecialchars($operation['value_type']) ?></span>
                                        </div>
                                        <div class="debugbar-cache-meta-item">
                                            <span class="debugbar-cache-meta-label">Size:</span>
                                            <span class="debugbar-cache-meta-value"><?= $operation['value_size'] ?> <?= is_string($operation['value_size']) ? 'chars' : 'items' ?></span>
                                        </div>
                                        <?php if ($operation['ttl'] > 0): ?>
                                            <div class="debugbar-cache-meta-item">
                                                <span class="debugbar-cache-meta-label">TTL:</span>
                                                <span class="debugbar-cache-meta-value"><?= $operation['ttl'] ?>s</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($operation['compression_ratio']) && $operation['compression_ratio'] > 0): ?>
                                            <div class="debugbar-cache-meta-item">
                                                <span class="debugbar-cache-meta-label">Compression:</span>
                                                <span class="debugbar-cache-meta-value"><?= $operation['compression_ratio'] ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($operation['redis_host'])): ?>
                                            <div class="debugbar-cache-meta-item">
                                                <span class="debugbar-cache-meta-label">Redis:</span>
                                                <span class="debugbar-cache-meta-value"><?= htmlspecialchars($operation['redis_host']) ?>:<?= $operation['redis_port'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (isset($operation['message']) && $operation['message']): ?>
                                        <div class="debugbar-cache-message">
                                            <?= htmlspecialchars($operation['message']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="debugbar-empty">
                    <div style="font-size: 48px; margin-bottom: 15px;">💾</div>
                    <div>No cache operations logged</div>
                    <div style="font-size: 12px; color: #8b949e; margin-top: 5px;">
                        Cache operations will appear here when they are performed
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Errors Tab -->
        <div class="debugbar-panel" id="tab-errors">
            <div class="debugbar-code">
                <?= isset($error_logs) ? htmlspecialchars(print_r($error_logs, true)) : 'No errors logged' ?>
            </div>
        </div>

        <!-- Cookies Tab -->
        <div class="debugbar-panel" id="tab-cookies">
            <?php if (!empty($_COOKIE)): ?>
                <!-- Cookies List -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title">🍪 Cookies (<?= count($_COOKIE) ?>)</div>
                    <div class="debugbar-cookies-simple">
                        <?php foreach ($_COOKIE as $name => $value): ?>
                            <div class="debugbar-cookie-simple-item">
                                <div class="debugbar-cookie-simple-key"><?= htmlspecialchars($name) ?></div>
                                <div class="debugbar-cookie-simple-value">
                                    <?php if (is_array($value) || is_object($value)): ?>
                                        <pre class="debugbar-json"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                    <?php else: ?>
                                        <?= htmlspecialchars($value) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="debugbar-empty">
                    <div style="font-size: 48px; margin-bottom: 15px;">🍪</div>
                    <div>No cookies found</div>
                    <div style="font-size: 12px; color: #8b949e; margin-top: 5px;">
                        Cookies will appear here when they are set by the application
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message Logs Tab -->
        <div class="debugbar-panel" id="tab-logs">
            <?php if (!empty($logs)): ?>
                <!-- Logs List -->
                <div class="debugbar-section">
                    <div class="debugbar-section-title">📝 Message Logs (<?= count($logs) ?>)</div>
                    <div class="debugbar-logs-list">
                        <?php foreach ($logs as $i => $log): ?>
                            <div class="debugbar-log-item debugbar-log-<?= strtolower($log['level']) ?>">
                                <div class="debugbar-log-header">
                                    <div class="debugbar-log-level">
                                        <span class="debugbar-log-level-badge debugbar-log-level-<?= strtolower($log['level']) ?>">
                                            <?= $log['level'] ?>
                                        </span>
                                        <?php if ($log['is_json']): ?>
                                            <span class="debugbar-log-json-badge">JSON</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="debugbar-log-time"><?= $log['timestamp'] ?></div>
                                </div>

                                <?php if ($log['is_json']): ?>
                                    <!-- JSON Message with Collapsible -->
                                    <div class="debugbar-log-message">
                                        <div class="debugbar-log-json-toggle" onclick="toggleJsonLog(<?= $i ?>)" style="cursor: pointer;">
                                            <span class="debugbar-log-json-icon" id="json-icon-<?= $i ?>">▼</span>
                                            <strong>JSON Data:</strong> Click to expand/collapse
                                        </div>
                                        <div class="debugbar-log-json-content" id="json-content-<?= $i ?>" style="display: none;">
                                            <pre class="debugbar-log-json-pretty"><?= htmlspecialchars(json_encode($log['json_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Regular Message -->
                                    <div class="debugbar-log-message">
                                        <?= htmlspecialchars($log['message']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($log['file'] && $log['line']): ?>
                                    <div class="debugbar-log-location">
                                        <strong>Location:</strong> <?= htmlspecialchars($log['file']) ?>:<?= $log['line'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="debugbar-empty">
                    <div style="font-size: 48px; margin-bottom: 15px;">📝</div>
                    <div>No logs recorded</div>
                    <div style="font-size: 12px; color: #8b949e; margin-top: 5px;">
                        Logs will appear here when Logger methods are called
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleDebugbar() {
        const debugbar = document.getElementById('debugbar');
        const toggle = document.getElementById('debugbar-toggle');
        const toggleIcon = toggle ? toggle.querySelector('svg') : null;

        if (debugbar.classList.contains('open')) {
            // Close debugbar
            debugbar.classList.remove('open');
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(0deg)';
            }
            // Reset height to default when closing
            debugbar.style.height = '48px';
        } else {
            // Open debugbar
            debugbar.classList.add('open');
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(180deg)';
            }
            // Set default height when opening
            debugbar.style.height = '400px';
        }
    }

    function switchTab(tabName) {
        try {
            // Hide all panels
            const panels = document.querySelectorAll('.debugbar-panel');
            panels.forEach(panel => panel.classList.remove('active'));

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.debugbar-tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Show selected panel
            const targetPanel = document.getElementById('tab-' + tabName);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }

            // Add active class to clicked tab
            if (event && event.target) {
                event.target.classList.add('active');
            }
        } catch (error) {
            console.error('Debugbar switchTab error:', error);
        }
    }

    function toggleServerData() {
        try {
            const content = document.getElementById('server-data-content');
            const toggle = document.getElementById('server-data-toggle');

            if (content && toggle) {
                if (content.classList.contains('collapsed')) {
                    content.classList.remove('collapsed');
                    toggle.textContent = '▲';
                } else {
                    content.classList.add('collapsed');
                    toggle.textContent = '▼';
                }
            }
        } catch (error) {
            console.error('Debugbar toggleServerData error:', error);
        }
    }



    function toggleJsonLog(logIndex) {
        try {
            const content = document.getElementById('json-content-' + logIndex);
            const icon = document.getElementById('json-icon-' + logIndex);

            if (content && icon) {
                if (content.style.display === 'none' || content.style.display === '') {
                    // Expand JSON
                    content.style.display = 'block';
                    icon.textContent = '▲';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    // Collapse JSON
                    content.style.display = 'none';
                    icon.textContent = '▼';
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        } catch (error) {
            console.error('Debugbar toggleJsonLog error:', error);
        }
    }

    function toggleSection(sectionId) {
        try {
            const content = document.getElementById(sectionId + '-content');
            const toggle = document.getElementById(sectionId + '-toggle');

            if (content && toggle) {
                if (content.classList.contains('collapsed')) {
                    // Expand section
                    content.classList.remove('collapsed');
                    toggle.textContent = '▲';
                } else {
                    // Collapse section
                    content.classList.add('collapsed');
                    toggle.textContent = '▼';
                }
            }
        } catch (error) {
            console.error('Debugbar toggleSection error:', error);
        }
    }

    // Resize functionality
    let isResizing = false;
    let startY = 0;
    let startHeight = 0;
    let resizeTimeout = null;

    function initResize() {
        try {
            const resizeHandle = document.getElementById('debugbar-resize-handle');
            const debugbar = document.getElementById('debugbar');

            if (!resizeHandle || !debugbar) {
                return;
            }

            resizeHandle.addEventListener('mousedown', function(e) {
                try {
                    // Only allow resize when debugbar is open
                    if (!debugbar.classList.contains('open')) {
                        return;
                    }

                    isResizing = true;
                    startY = e.clientY;
                    startHeight = parseInt(document.defaultView.getComputedStyle(debugbar).height, 10);

                    // Disable transition during resize for smooth dragging
                    debugbar.style.transition = 'none';

                    document.addEventListener('mousemove', handleResize);
                    document.addEventListener('mouseup', stopResize);

                    e.preventDefault();
                } catch (error) {
                    console.error('Debugbar resize mousedown error:', error);
                }
            });
        } catch (error) {
            console.error('Debugbar initResize error:', error);
        }
    }

    function handleResize(e) {
        try {
            if (!isResizing) return;

            const debugbar = document.getElementById('debugbar');
            if (!debugbar) return;

            const newHeight = startHeight - (e.clientY - startY);
            const minHeight = 200;
            const maxHeight = Math.max(window.innerHeight - 100, 400);

            // Ensure height is within bounds
            const clampedHeight = Math.max(minHeight, Math.min(newHeight, maxHeight));
            debugbar.style.height = clampedHeight + 'px';
        } catch (error) {
            console.error('Debugbar handleResize error:', error);
        }
    }

    function stopResize() {
        try {
            if (!isResizing) return;

            isResizing = false;

            const debugbar = document.getElementById('debugbar');
            if (debugbar) {
                // Re-enable transition after resize
                debugbar.style.transition = 'height 0.3s ease';
            }

            // Clean up event listeners
            document.removeEventListener('mousemove', handleResize);
            document.removeEventListener('mouseup', stopResize);
        } catch (error) {
            console.error('Debugbar stopResize error:', error);
        }
    }

    // Initialize resize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        try {
            initResize();
        } catch (error) {
            console.error('Debugbar initResize on DOMContentLoaded error:', error);
        }
    });

    // Initialize debugbar as collapsed
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const debugbar = document.getElementById('debugbar');
            const toggle = document.getElementById('debugbar-toggle');

            if (debugbar && toggle) {
                // Ensure debugbar starts closed
                debugbar.classList.remove('open');

                // Reset any inline height styles
                debugbar.style.height = '48px';
            }
        } catch (error) {
            console.error('Debugbar initialization error:', error);
        }
    });

    // Add error handling for resize operations
    function resetDebugbarState() {
        try {
            const debugbar = document.getElementById('debugbar');
            const toggle = document.getElementById('debugbar-toggle');

            if (debugbar) {
                // Reset to closed state
                debugbar.classList.remove('open');
                debugbar.style.height = '48px';
                debugbar.style.transition = 'height 0.3s ease';
            }
        } catch (error) {
            console.error('Debugbar resetDebugbarState error:', error);
        }
    }

    // Add global error handler
    window.addEventListener('error', function(e) {
        console.error('Debugbar error:', e.error);
        // Reset debugbar state on error
        setTimeout(resetDebugbarState, 100);
    });
</script>
