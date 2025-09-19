<?php
$siteUrl = config('app')['app_url'];
$timezone = config('app')['app_timezone'];
$themeActive = config('theme')['theme_name'];
$logo = option('site_logo');
$brandName = option('site_brand');
$slogan = option('site_desc');

// Helper function để format URL
function formatUrl($url) {
    return str_replace(['http://', 'https://'], '', $url);
}

// Helper function để get logo URL
function getLogoUrl($logo) {
    if (is_array($logo) && isset($logo['path'])) {
        return base_url('uploads/' . $logo['path']);
    }
    return base_url('uploads/default-logo.png');
}

// Helper function để get timezone offset
function getTimezoneOffset($timezone) {
    $dateTimeZone = new DateTimeZone($timezone);
    $dateTime = new DateTime('now', $dateTimeZone);
    return $dateTime->format('P');
}
?>

<!-- Website Overview Component -->
<div class="bg-card text-card-foreground border card-content">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-primary/10 p-3 rounded-full">
                <i data-lucide="globe" class="w-6 h-6 text-primary"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-foreground">Website Overview</h3>
                <p class="text-sm text-muted-foreground">Thông tin cơ bản về website</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-2 py-1 bg-accent text-accent-foreground text-xs rounded-full">Active</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Brand Information -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-muted flex items-center justify-center overflow-hidden">
                    <?php if ($logo && is_array($logo)): ?>
                        <img src="<?= getLogoUrl($logo) ?>" alt="<?= htmlspecialchars($brandName) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i data-lucide="image" class="w-6 h-6 text-muted-foreground"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-foreground"><?= htmlspecialchars($brandName) ?></h4>
                    <p class="text-sm text-muted-foreground"><?= htmlspecialchars($slogan) ?></p>
                </div>
            </div>

            <!-- Website URL -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center">
                    <i data-lucide="link" class="w-4 h-4 text-accent-foreground"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">Website URL</p>
                    <p class="text-sm text-muted-foreground font-mono"><?= formatUrl($siteUrl) ?></p>
                </div>
                <a href="<?= $siteUrl ?>" target="_blank" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" title="Visit Website">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                </a>
            </div>

            <!-- Theme Information -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-secondary/20 flex items-center justify-center">
                    <i data-lucide="palette" class="w-4 h-4 text-secondary-foreground"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">Active Theme</p>
                    <p class="text-sm text-muted-foreground capitalize"><?= htmlspecialchars($themeActive) ?></p>
                </div>
                <a href="<?= admin_url('themes') ?>" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md h-8 w-8 p-0" title="Manage Themes">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                </a>
            </div>
        </div>

        <!-- System Information -->
        <div class="space-y-4">
            <!-- Timezone -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4 text-accent-foreground"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">Timezone</p>
                    <p class="text-sm text-muted-foreground"><?= htmlspecialchars($timezone) ?> (<?= getTimezoneOffset($timezone) ?>)</p>
                </div>
            </div>

            <!-- PHP Version -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                    <i data-lucide="code" class="w-4 h-4 text-primary"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">PHP Version</p>
                    <p class="text-sm text-muted-foreground"><?= PHP_VERSION ?></p>
                </div>
            </div>

            <!-- Server Status -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center">
                    <i data-lucide="server" class="w-4 h-4 text-accent-foreground"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">Server Status</p>
                    <p class="text-sm text-muted-foreground"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                </div>
            </div>

            <!-- Database Status -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center">
                    <i data-lucide="database" class="w-4 h-4 text-primary"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-foreground">Database</p>
                    <p class="text-sm text-muted-foreground">Connected</p>
                </div>
                <span class="px-2 py-1 bg-accent/20 text-accent-foreground text-xs rounded-full">Online</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 pt-6 border-t border-border">
        <h4 class="text-sm font-medium text-foreground mb-3">Quick Actions</h4>
        <div class="flex flex-wrap gap-2">
            <a href="<?= admin_url('options') ?>" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-foreground bg-muted rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Site Settings
            </a>
            <a href="<?= admin_url('themes') ?>" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-foreground bg-muted rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors">
                <i data-lucide="palette" class="w-4 h-4"></i>
                Manage Themes
            </a>
            <a href="<?= admin_url('files') ?>" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-foreground bg-muted rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors">
                <i data-lucide="folder" class="w-4 h-4"></i>
                Media Library
            </a>
            <a href="<?= $siteUrl ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-foreground bg-muted rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                Visit Site
            </a>
        </div>
    </div>
</div>

<script>
// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
