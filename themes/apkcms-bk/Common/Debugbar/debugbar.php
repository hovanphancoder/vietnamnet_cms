<?php
global $debug_sql,$debug_events, $debug_cache;
// Assume variables $profiles, $request, $debug_events, $debug_cache, $error_logs, $is_ajax have been set from DebugbarBlock
$performance = \System\Libraries\Monitor::endFramework();
$profiles = \System\Libraries\Monitor::getProfiles();
$request = [
    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri'        => '/'.APP_URI['uri'] ?? '',
    'controller' => defined('APP_ROUTE') ? APP_ROUTE['controller'] : '404',
    'action'     => defined('APP_ROUTE') ? APP_ROUTE['action'] : '404',
    'headers'    => function_exists('getallheaders') ? getallheaders() : []
];
$environment = [
    'app_name'         => config('app')['app_name'] ?? 'MyApp',
    'debug'            => config('app')['debug'] ?? false,
    'php_version'      => phpversion(),
    'memory_limit'     => ini_get('memory_limit'),
    'loaded_extensions'=> get_loaded_extensions(),
    '_server'   => $_SERVER
];
?>
<style>
    /* CSS for Debugbar including title bar and inner content */
    #debugbar {
        background: #f8f8f8;
        border-top: 2px solid #ccc;
        font-family: sans-serif;
        font-size: 13px;
        color: #333;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow: auto;
        z-index: 9999;
    }
    #debugbar summary{
        cursor: pointer;
    }
    #debugbar > summary {
        padding: 10px;
        background-color: #fff;
        border-bottom: 1px solid #ccc;
        font-weight: bold;
    }
    #debugbar details {
        margin: 5px;
    }
    #debugbar pre {
        background: #fff;
        padding: 5px;
        border: 1px solid #ccc;
        overflow-x: auto;
    }
    /* Customization for DebugBar when collapsed (summary always visible) */
    #debugbar[open] > summary::after {
        content: " (Click for minimize)";
        font-size: 12px;
        color: #666;
    }
    #debugbar:not([open]) > summary::after {
        content: " (Click for explaine)";
        font-size: 12px;
        color: #666;
    }
</style>

<!-- Use <details> element to create collapsible/expandable Debugbar -->
<details id="debugbar">
    <summary>
        Debug Bar - Time Total: <?= round($performance['execution_time'] * 1000, 2) ?> ms | Total Mem: <?= $performance['memory_used'] ?>
        <?php if(isset($performance['cpu_usage'])): ?>
         | CPU Load: <?= $performance['cpu_usage'] ?>
        <?php endif; ?>
    </summary>
    
    <!-- Debug sections below only display when expanded -->
    <details>
        <?php if(isset($debug_sql)): ?>
            <summary>SQL Queries (<?= count($debug_sql) ?>)</summary>
            <pre><?php 
                $i = 0;

                foreach ($debug_sql as $q):
                    $i++; 
                    echo $i . ' - ' . $q['sql'] . " <b>(" . json_encode($q['params']) . ")</b> - <b>" . (1000 * $q['time']) . "</b>ms\n";
                endforeach;
                ?></pre>
        <?php endif; ?>
    </details>

    <details>
        <summary>Profiling</summary>
        <pre>
<?php 
if(isset($profiles)) {
    foreach ($profiles as $p):
        echo $p['label'] . " - " . $p['time'] . " s - " . $p['memory'] . "\n";
    endforeach;
}
?>
        </pre>
    </details>

    <details>
        <summary>Request Info</summary>
        <pre>
<b>Method:</b> <?= isset($request['method']) ? $request['method'] : '' ?>

<b>URI:</b> <?= isset($request['uri']) ? $request['uri'] : '' ?>

<b>Controller:</b> <?= isset($request['controller']) ? $request['controller'] : '' ?>

<b>Action:</b> <?= isset($request['action']) ? $request['action'] : '' ?>

<b>Headers:</b> <?= isset($request['headers']) ? json_encode($request['headers'], JSON_PRETTY_PRINT) : '' ?>

        </pre>
    </details>

    <details>
        <summary>Environment</summary>
        <pre>
<?= json_encode($environment, JSON_PRETTY_PRINT) ?>
        </pre>
    </details>

    <details>
        <summary>Event Logs</summary>
        <pre>
<?= isset($debug_events) ? print_r($debug_events, true) : 'N/A' ?>
        </pre>
    </details>

    <details>
        <summary>Cache Logs</summary>
        <pre>
<?= isset($debug_cache) ? print_r($debug_cache, true) : 'N/A' ?>
        </pre>
    </details>

    <details>
        <summary>Error Logs</summary>
        <pre>
<?= isset($error_logs) ? print_r($error_logs, true) : 'N/A' ?>
        </pre>
    </details>

    <details>
        <summary>Cookie</summary>
        <pre>
<?= json_encode($_COOKIE, JSON_PRETTY_PRINT) ?>
        </pre>
    </details>

</details>
