<?php

use App\Libraries\Fastlang;
use System\Libraries\Render;
use System\Libraries\Session;

$breadcrumbs = array(
    [
        'name' => 'Dashboard',
        'url' => admin_url('home')
    ],
    [
        'name' => 'Plugins',
        'url' => admin_url('plugins'),
        'active' => true
    ]
);
Render::block('Backend\Header', ['layout' => 'default', 'title' => $title ?? 'Plugins', 'breadcrumb' => $breadcrumbs]);

?>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: .5;
        }
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal.show {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .drag-active {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1));
        border-color: #3b82f6;
        transform: scale(1.02);
    }

    .feather {
        margin-bottom: 0 !important;
    }
</style>
<?php
// Pass plugin data to JavaScript
$pluginDataJson = json_encode($plugins ?? []);

// Language data for JavaScript
$langData = [
    'loading_plugins' => Fastlang::_e('loading_plugins'),
    'no_plugins_found' => Fastlang::_e('no_plugins_found'),
    'try_adjusting_search' => Fastlang::_e('try_adjusting_search'),
    'no_plugins_found_store' => Fastlang::_e('no_plugins_found_store'),
    'try_adjusting_search_store' => Fastlang::_e('try_adjusting_search_store'),
    'showing' => Fastlang::_e('showing'),
    'of' => Fastlang::_e('of'),
    'plugins' => Fastlang::_e('plugins'),
    'activate' => Fastlang::_e('activate'),
    'deactivate' => Fastlang::_e('deactivate'),
    'delete' => Fastlang::_e('delete'),
    'delete_confirm' => Fastlang::_e('delete_confirm'),
    'plugin_details' => Fastlang::_e('plugin_details'),
    'upload_plugin_files' => Fastlang::_e('upload_plugin_files'),
    'upload_description' => Fastlang::_e('upload_description'),
    'drag_drop_plugin_files' => Fastlang::_e('drag_drop_plugin_files'),
    'or_click_browse' => Fastlang::_e('or_click_browse'),
    'choose_files' => Fastlang::_e('choose_files'),
    'supported_formats' => Fastlang::_e('supported_formats'),
    'selected_files' => Fastlang::_e('selected_files'),
    'upload_all' => Fastlang::_e('upload_all'),
    'upload_guidelines' => Fastlang::_e('upload_guidelines'),
    'guideline_1' => Fastlang::_e('guideline_1'),
    'guideline_2' => Fastlang::_e('guideline_2'),
    'guideline_3' => Fastlang::_e('guideline_3'),
    'open_new_tab' => Fastlang::_e('open_new_tab'),
    'success' => Fastlang::_e('success'),
    'error' => Fastlang::_e('error'),
    'action_failed' => Fastlang::_e('action_failed'),
    'error_occurred' => Fastlang::_e('error_occurred'),
    'upload_success' => Fastlang::_e('upload_success'),
    'upload_failed' => Fastlang::_e('upload_failed'),
    'upload_error' => Fastlang::_e('upload_error'),
    'plugin_exists' => Fastlang::_e('plugin_exists'),
    'confirm_overwrite' => Fastlang::_e('confirm_overwrite'),
    'previous' => Fastlang::_e('previous'),
    'next' => Fastlang::_e('next'),
    'installed' => Fastlang::_e('installed'),
    'install' => Fastlang::_e('install'),
    'details' => Fastlang::_e('details'),
    'by' => Fastlang::_e('by'),
    'downloads' => Fastlang::_e('downloads'),
    'version' => Fastlang::_e('version'),
    'update_available' => Fastlang::_e('update_available'),
    'featured' => Fastlang::_e('featured'),
    'popular' => Fastlang::_e('popular'),
    'recommended' => Fastlang::_e('recommended'),
    'favorites' => Fastlang::_e('favorites'),
    'all' => Fastlang::_e('all'),
    'active' => Fastlang::_e('active'),
    'inactive' => Fastlang::_e('inactive'),
    'updates' => Fastlang::_e('updates'),
];

$langDataJson = json_encode($langData);
?>
<div class="" x-data="pluginsManager()">
    <!-- Header & Filter -->
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Plugin Management</h1>
            <p class="text-muted-foreground">Manage and install plugins for your website</p>
        </div>

        <!-- Main Tabs -->
        <div dir="ltr" data-orientation="horizontal" class="w-full">
            <div role="tablist" aria-orientation="horizontal"
                class="items-center justify-center rounded-md bg-muted p-1 text-muted-foreground grid w-full grid-cols-2"
                tabindex="0" data-orientation="horizontal" style="outline: none;">
                <button type="button" role="tab"
                    :aria-selected="mainTab === 'installed'"
                    :data-state="mainTab === 'installed' ? 'active' : 'inactive'"
                    @click="mainTab = 'installed'"
                    class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex items-center gap-2"
                    tabindex="0" data-orientation="horizontal" data-radix-collection-item="">
                    <i data-lucide="package" class="h-4 w-4"></i>
                    <?= Fastlang::_e('installed_plugins') ?>
                </button>
                <button type="button" role="tab"
                    :aria-selected="mainTab === 'add-new'"
                    :data-state="mainTab === 'add-new' ? 'active' : 'inactive'"
                    @click="mainTab = 'add-new'"
                    class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex items-center gap-2"
                    tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <?= Fastlang::_e('add_plugin') ?>
                </button>
            </div>

            <!-- Installed Plugins Content -->
            <div :data-state="mainTab === 'installed' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
                :aria-labelledby="'tab-installed'" tabindex="0"
                class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
                :hidden="mainTab !== 'installed'">

                <!-- Search and Filter Section -->
                <div class="bg-card rounded-xl mb-4">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-1 w-full lg:w-auto">
                            <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4"></i>
                                <input
                                    x-model="installedSearchTerm"
                                    placeholder="<?= Fastlang::_e('place_search') ?>"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 pl-10" />
                            </div>
                        </div>
                        <div class="flex gap-2" x-show="updateCount > 0">
                            <button class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                                <i data-lucide="refresh-cw" class="h-4 w-4 mr-2"></i>
                                <?= Fastlang::_e('update_all') ?> (<span x-text="updateCount"></span>)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div dir="ltr" data-orientation="horizontal">
                    <div role="tablist" aria-orientation="horizontal"
                        class="inline-flex items-center justify-center rounded-md bg-muted p-1 text-muted-foreground h-9"
                        tabindex="0" data-orientation="horizontal" style="outline: none;">
                        <button type="button" role="tab"
                            :aria-selected="installedFilterTab === 'all'"
                            :data-state="installedFilterTab === 'all' ? 'active' : 'inactive'"
                            @click="installedFilterTab = 'all'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-2.5 py-1 font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm text-xs"
                            tabindex="-1" data-orientation="horizontal" data-radix-collection-item=""><?= Fastlang::_e('all') ?> (<span x-text="plugins.length"></span>)</button>
                        <button type="button" role="tab"
                            :aria-selected="installedFilterTab === 'active'"
                            :data-state="installedFilterTab === 'active' ? 'active' : 'inactive'"
                            @click="installedFilterTab = 'active'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-2.5 py-1 font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm text-xs"
                            tabindex="-1" data-orientation="horizontal" data-radix-collection-item=""><?= Fastlang::_e('active') ?> (<span x-text="activeCount"></span>)</button>
                        <button type="button" role="tab"
                            :aria-selected="installedFilterTab === 'inactive'"
                            :data-state="installedFilterTab === 'inactive' ? 'active' : 'inactive'"
                            @click="installedFilterTab = 'inactive'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-2.5 py-1 font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm text-xs"
                            tabindex="-1" data-orientation="horizontal" data-radix-collection-item=""><?= Fastlang::_e('inactive') ?> (<span x-text="inactiveCount"></span>)</button>
                    </div>
                </div>

                <!-- Plugins List -->
                <div class="bg-card card-content !p-0 border overflow-hidden">
                    <div class="overflow-x-auto">
                        <div class="relative w-full overflow-auto">
                            <div class="grid gap-2 p-4">
                                <template x-for="plugin in filteredPlugins" :key="plugin.slug">
                                    <div class="bg-card text-card-foreground rounded-lg border border-border hover:shadow-sm transition-shadow">
                                        <div class="p-4">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h3 class="font-medium text-sm" x-text="plugin.name"></h3>
                                                        <span :class="plugin.is_active ? 'bg-accent text-accent-foreground' : 'bg-secondary text-secondary-foreground'" class="text-xs font-medium px-2 py-0.5 rounded-full" x-text="plugin.is_active ? '<?= Fastlang::_e('active') ?>' : '<?= Fastlang::_e('inactive') ?>'"></span>
                                                        <template x-if="plugin.hasUpdate">
                                                            <span class="bg-destructive/20 text-destructive text-xs font-medium px-2 py-0.5 rounded-full inline-flex items-center">
                                                                <i data-lucide="refresh-cw" class="h-3 w-3 mr-1"></i>
                                                                <?= Fastlang::_e('update') ?>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <p class="text-xs text-muted-foreground mb-2 truncate" x-text="plugin.description"></p>
                                                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground mb-3">
                                                        <span x-text="'v' + plugin.version"></span>
                                                        <template x-if="plugin.hasUpdate">
                                                            <span class="text-orange-500" x-text="'→ ' + plugin.newVersion"></span>
                                                        </template>
                                                        <span x-text="plugin.author"></span>
                                                        <template x-if="Array.isArray(plugin.categories) && plugin.categories.length">
                                                            <span>
                                                                <template x-for="cat in plugin.categories" :key="cat">
                                                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full border border-border mr-1" x-text="cat"></span>
                                                                </template>
                                                            </span>
                                                        </template>
                                                        <template x-if="!plugin.categories || !plugin.categories.length">
                                                            <span class="text-xs font-medium px-1.5 py-0.5 rounded-full border border-border" x-text="plugin.category"></span>
                                                        </template>
                                                        <span class="flex items-center gap-1">
                                                            <i class="feather" data-feather="star"></i>
                                                            <span x-text="plugin.rating + '/5'"></span>
                                                        </span>
                                                    </div>

                                                </div>
                                                <div class="flex items-center gap-2 ml-4">
                                                    <button type="button"
                                                        @click="togglePlugin(plugin)"
                                                        :class="plugin.is_active ? 'bg-primary' : 'bg-input'"
                                                        class="peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background data-[state=checked]:bg-primary data-[state=unchecked]:bg-input"
                                                        role="switch"
                                                        :aria-checked="plugin.is_active">
                                                        <span :class="plugin.is_active ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg ring-0 transition-transform"></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-if="plugin.hasUpdate">
                                                    <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90">
                                                        <i data-lucide="download" class="h-3 w-3 mr-1"></i>
                                                        <?= Fastlang::_e('update') ?>
                                                    </button>
                                                </template>
                                                <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground">
                                                    <i data-lucide="settings" class="h-3 w-3 mr-1"></i>
                                                    <?= Fastlang::_e('settings') ?>
                                                </button>
                                                <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground">
                                                    <i data-lucide="info" class="h-3 w-3 mr-1"></i>
                                                    <?= Fastlang::_e('details') ?>
                                                </button>
                                                <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-primary text-primary-foreground hover:bg-primary/90 ml-auto" @click="deletePlugin(plugin)">
                                                    <i data-lucide="trash2" class="h-3 w-3 mr-1"></i>
                                                    <?= Fastlang::_e('delete') ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="filteredPlugins.length === 0">
                                    <div class="bg-card text-card-foreground rounded-lg border border-border">
                                        <div class="text-center py-8">
                                            <i data-lucide="info" class="h-8 w-8 text-muted-foreground mx-auto mb-2"></i>
                                            <h3 class="text-sm font-medium mb-1"><?= Fastlang::_e('no_plugins_found') ?></h3>
                                            <p class="text-xs text-muted-foreground" x-text="installedSearchTerm ? `<?= Fastlang::_e('no_plugins_match') ?> '${installedSearchTerm}'` : '<?= Fastlang::_e('no_plugins_in_category') ?>'"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Plugin Content -->
        <div :data-state="mainTab === 'add-new' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
            :aria-labelledby="'tab-add-new'" tabindex="0"
            class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
            :hidden="mainTab !== 'add-new'">
            <div class="w-full">
                <div dir="ltr" data-orientation="horizontal">
                    <div role="tablist" aria-orientation="horizontal"
                        class="items-center justify-center rounded-md bg-muted p-1 text-muted-foreground grid w-full grid-cols-2"
                        tabindex="0" data-orientation="horizontal" style="outline: none;">
                        <button type="button" role="tab"
                            :aria-selected="addNewTab === 'store'"
                            :data-state="addNewTab === 'store' ? 'active' : 'inactive'"
                            @click="addNewTab = 'store'"
                            class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm text-xs"
                            tabindex="0" data-orientation="horizontal" data-radix-collection-item=""><?= Fastlang::_e('plugin_store') ?></button>
                        <button type="button" role="tab"
                            :aria-selected="addNewTab === 'upload'"
                            :data-state="addNewTab === 'upload' ? 'active' : 'inactive'"
                            @click="addNewTab = 'upload'"
                            class="justify-center whitespace-nowrap rounded-sm px-2.5 py-1 font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm text-xs"
                            tabindex="-1" data-orientation="horizontal" data-radix-collection-item=""><?= Fastlang::_e('upload_plugin') ?></button>
                    </div>

                    <!-- Store Tab -->
                    <div :data-state="addNewTab === 'store' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
                        :aria-labelledby="'tab-store'" tabindex="0"
                        class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
                        :hidden="addNewTab !== 'store'">
                        <!-- Search and Filter -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4"></i>
                                <input x-model="storeSearchTerm" placeholder="<?= Fastlang::_e('place_search_store') ?>"
                                    class="pl-10 h-9 w-full bg-background border border-input rounded-md text-sm focus:ring-ring focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background" />
                            </div>
                            <div class="flex gap-2">
                                <select x-model="storeSelectedCategory" class="px-3 py-1.5 border border-input rounded-md bg-background text-xs h-9 focus:ring-ring focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background">
                                    <template x-for="category in categories" :key="category">
                                        <option :value="category" x-text="category === 'all' ? '<?= Fastlang::_e('all_categories') ?>' : category"></option>
                                    </template>
                                </select>
                                <button class="h-9 px-3 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground">
                                    <i data-lucide="filter" class="h-4 w-4 mr-1"></i>
                                    <?= Fastlang::_e('filter') ?>
                                </button>
                            </div>
                        </div>
                        <!--  comming soon -->
                        <div class="bg-card mt-4 text-card-foreground rounded-lg border border-border hover:shadow-sm transition-shadow">
                            <div class="p-4">
                                <h2 class="text-lg font-semibold mb-3"><?= Fastlang::_e('coming soon') ?></h2>
                            </div>
                        </div>
                        <?php /*
                                <!-- Featured Plugins -->
                                <div x-show="featuredPlugins.length > 0">
                                    <div class="flex items-center gap-2 mb-3">
                                        <i data-lucide="star" class="h-4 w-4 text-yellow-500"></i>
                                        <h2 class="text-lg font-semibold"><?= Fastlang::_e('featured_plugins') ?></h2>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                                        <template x-for="plugin in featuredPlugins.slice(0, 3)" :key="plugin.id">
                                            <div class="bg-card text-card-foreground rounded-lg border border-yellow-200 dark:border-yellow-800 hover:shadow-md transition-shadow">
                                                <div class="p-4 space-y-2">
                                                    <div class="flex items-start justify-between mb-1">
                                                        <span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 text-xs font-medium px-2 py-0.5 rounded-full inline-flex items-center">
                                                            <i data-lucide="star" class="h-3 w-3 mr-1"></i>
                                                            <?= Fastlang::_e('featured') ?>
                                                        </span>
                                                        <template x-if="plugin.isPremium">
                                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full border border-border inline-flex items-center">
                                                                <i data-lucide="zap" class="h-3 w-3 mr-1"></i>
                                                                <?= Fastlang::_e('premium') ?>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-sm font-semibold" x-text="plugin.name"></h3>
                                                        <p class="text-xs text-muted-foreground" x-text="plugin.shortDescription"></p>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                                        <div class="flex items-center gap-1">
                                                            <i data-lucide="star" class="h-3 w-3 fill-yellow-400 text-yellow-400"></i>
                                                            <span x-text="plugin.rating"></span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <i data-lucide="download" class="h-3 w-3"></i>
                                                            <span x-text="plugin.downloads.toLocaleString()"></span>
                                                        </div>
                                                        <span class="text-xs font-medium px-1.5 py-0.5 rounded-full border border-border" x-text="plugin.category"></span>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-sm font-semibold">
                                                            <span x-show="plugin.price === 0" class="text-green-600 dark:text-green-400"><?= Fastlang::_e('free') ?></span>
                                                            <span x-show="plugin.price > 0" x-text="`$${plugin.price}`"></span>
                                                        </div>
                                                        <div class="flex gap-1.5">
                                                            <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground"><?= Fastlang::_e('details') ?></button>
                                                            <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90" x-text="plugin.price === 0 ? '<?= Fastlang::_e('install') ?>' : '<?= Fastlang::_e('buy') ?>'"></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- All Plugins -->
                                <div>
                                    <h2 class="text-lg font-semibold mb-3"><?= Fastlang::_e('all_plugins') ?></h2>
                                    <div class="grid gap-2">
                                        <template x-for="plugin in filteredStorePlugins" :key="plugin.id">
                                            <div class="bg-card text-card-foreground rounded-lg border border-border hover:shadow-sm transition-shadow">
                                                <div class="p-4">
                                                    <div class="flex gap-3">
                                                        <div class="w-12 h-12 bg-muted rounded-md flex-shrink-0">
                                                            <img :src="plugin.images[0] || 'https://via.placeholder.com/48'" :alt="plugin.name" class="w-full h-full object-cover rounded-md" />
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between mb-1">
                                                                <div>
                                                                    <div class="flex items-center gap-2 mb-1">
                                                                        <h3 class="text-sm font-medium" x-text="plugin.name"></h3>
                                                                        <template x-if="plugin.isPremium">
                                                                            <span class="text-xs font-medium px-1.5 py-0.5 rounded-full border border-border inline-flex items-center">
                                                                                <i data-lucide="zap" class="h-3 w-3 mr-1"></i>
                                                                                <?= Fastlang::_e('premium') ?>
                                                                            </span>
                                                                        </template>
                                                                    </div>
                                                                    <p class="text-xs text-muted-foreground mb-2 truncate" x-text="plugin.description"></p>
                                                                </div>
                                                                <div class="text-right flex-shrink-0 ml-4">
                                                                    <div class="text-sm font-semibold mb-1">
                                                                        <span x-show="plugin.price === 0" class="text-green-600 dark:text-green-400"><?= Fastlang::_e('free') ?></span>
                                                                        <span x-show="plugin.price > 0" x-text="`$${plugin.price}`"></span>
                                                                    </div>
                                                                    <div class="flex gap-1.5">
                                                                        <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground"><?= Fastlang::_e('details') ?></button>
                                                                        <button class="h-7 px-2 inline-flex items-center justify-center rounded-md text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90" x-text="plugin.price === 0 ? '<?= Fastlang::_e('install') ?>' : '<?= Fastlang::_e('buy') ?>'"></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                                                <div class="flex items-center gap-1">
                                                                    <i class="feather" data-feather="star"></i>
                                                                    <span x-text="plugin.rating + '/5'"></span>
                                                                </div>
                                                                <div class="flex items-center gap-1">
                                                                    <i data-lucide="download" class="h-3 w-3"></i>
                                                                    <span x-text="plugin.downloads.toLocaleString()"></span>
                                                                </div>
                                                                <span x-text="plugin.author"></span>
                                                                <span class="text-xs font-medium px-1.5 py-0.5 rounded-full border border-border" x-text="plugin.category"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                */ ?>
                    </div>

                </div>

                <!-- Upload Tab -->
                <div :data-state="addNewTab === 'upload' ? 'active' : 'inactive'" data-orientation="horizontal" role="tabpanel"
                    :aria-labelledby="'tab-upload'" tabindex="0"
                    class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-4"
                    :hidden="addNewTab !== 'upload'">
                    <div class="bg-card text-card-foreground rounded-lg border border-border">
                        <div class="p-4 sm:p-6">
                            <div class="border-b border-border pb-4 mb-4">
                                <h3 class="text-lg font-semibold"><?= Fastlang::_e('upload_plugin') ?></h3>
                                <p class="text-sm text-muted-foreground"><?= Fastlang::_e('upload_plugin_description') ?></p>
                            </div>
                            <div class="space-y-4">
                                <div @dragenter.prevent="dragActive = true" @dragover.prevent="dragActive = true" @dragleave.prevent="dragActive = false" @drop.prevent="handleDrop" :class="dragActive ? 'border-primary bg-primary/10' : 'border-input'"
                                    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors">
                                    <i data-lucide="upload" class="h-8 w-8 text-muted-foreground mx-auto mb-3"></i>
                                    <h3 class="text-sm font-medium mb-1"><?= Fastlang::_e('drag_drop_files') ?></h3>
                                    <p class="text-xs text-muted-foreground mb-3"><?= Fastlang::_e('or_click_to_select') ?></p>
                                    <input type="file" multiple accept=".zip" @change="handleFileSelect($event)" class="hidden" x-ref="fileInput">
                                    <button type="button" @click="$refs.fileInput.click()" class="h-8 px-3 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground my-2"><?= Fastlang::_e('choose_zip_files') ?></button>
                                    <ul class="space-y-2">
                                        <template x-for="(file, idx) in selectedFiles" :key="file.name + idx">
                                            <li>
                                                <span x-text="file.name"></span>
                                                <button @click="removeSelectedFile(idx)" class="ml-2 text-red-500 text-xs"><?= Fastlang::_e('remove') ?></button>
                                            </li>
                                        </template>
                                    </ul>
                                    <button type="button" @click="uploadAllFiles" :disabled="selectedFiles.length === 0" class="h-8 px-3 inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground mt-4"><?= Fastlang::_e('upload') ?></button>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="text-sm font-medium"><?= Fastlang::_e('install_from_url') ?></h3>
                                    <div class="flex gap-2">
                                        <input placeholder="https://example.com/plugin.zip" class="flex-1 h-9 px-3 w-full bg-background border border-input rounded-md text-xs focus:ring-ring focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background" />
                                        <button class="h-9 px-4 inline-flex items-center justify-center rounded-md text-xs font-medium bg-primary text-primary-foreground hover:bg-primary/90"><?= Fastlang::_e('install') ?></button>
                                    </div>
                                </div>
                                <div class="bg-yellow-50 dark:bg-yellow-950/50 border border-yellow-200 dark:border-yellow-800/50 rounded-lg p-3">
                                    <div class="flex gap-2">
                                        <i data-lucide="alert-triangle" class="h-4 w-4 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5"></i>
                                        <div>
                                            <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-1 text-xs"><?= Fastlang::_e('security_notice') ?></h4>
                                            <p class="text-xs text-yellow-700 dark:text-yellow-300"><?= Fastlang::_e('security_notice_description') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-card text-card-foreground rounded-lg border border-border">
                        <div class="p-4 sm:p-6">
                            <h3 class="text-lg font-semibold border-b border-border pb-3 mb-3"><?= Fastlang::_e('installation_guide') ?></h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex gap-2">
                                    <div class="w-5 h-5 bg-accent text-accent-foreground rounded-full flex items-center justify-center font-medium text-xs flex-shrink-0">1</div>
                                    <div>
                                        <h4 class="font-medium mb-1 text-xs"><?= Fastlang::_e('download_plugin') ?></h4>
                                        <p class="text-muted-foreground text-xs"><?= Fastlang::_e('download_plugin_description') ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="w-5 h-5 bg-accent text-accent-foreground rounded-full flex items-center justify-center font-medium text-xs flex-shrink-0">2</div>
                                    <div>
                                        <h4 class="font-medium mb-1 text-xs"><?= Fastlang::_e('upload_file') ?></h4>
                                        <p class="text-muted-foreground text-xs"><?= Fastlang::_e('upload_file_description') ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="w-5 h-5 bg-accent text-accent-foreground rounded-full flex items-center justify-center font-medium text-xs flex-shrink-0">3</div>
                                    <div>
                                        <h4 class="font-medium mb-1 text-xs"><?= Fastlang::_e('activate_plugin') ?></h4>
                                        <p class="text-muted-foreground text-xs"><?= Fastlang::_e('activate_plugin_description') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Component -->
        <div x-show="notification.show" x-transition.opacity.250ms class="fixed bottom-6 right-6 z-50 min-w-[220px] max-w-xs bg-white border shadow-lg rounded-lg flex items-center gap-3 px-4 py-3"
            :class="notification.type === 'success' ? 'bg-green-400' : 'bg-red-400'">
            <template x-if="notification.type === 'success'">
                <i data-lucide="check" class="w-5 h-5 text-white"></i>
            </template>
            <template x-if="notification.type === 'error'">
                <i data-lucide="x" class="w-5 h-5 text-white"></i>
            </template>
            <span class="text-sm text-white" x-text="notification.message"></span>
        </div>
    </div>
</div>
<script src="https://unpkg.com/feather-icons"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
    feather.replace();
    window.pluginData = <?= $pluginDataJson ?>;

    function pluginsManager() {
        return {
            mainTab: 'installed',
            addNewTab: 'store',
            installedSearchTerm: '',
            installedFilterTab: 'all',
            storeSearchTerm: '',
            storeSelectedCategory: 'all',
            dragActive: false,
            plugins: window.pluginData || [],
            storePlugins: [{
                    id: "ecommerce-pro",
                    name: "E-commerce Pro",
                    description: "Giải pháp thương mại điện tử hoàn chỉnh với quản lý sản phẩm, đơn hàng, thanh toán và báo cáo chi tiết.",
                    shortDescription: "Giải pháp e-commerce hoàn chỉnh",
                    version: "3.2.1",
                    author: "Commerce Team",
                    category: "E-commerce",
                    rating: 4.9,
                    downloads: 25430,
                    price: 99,
                    isFeatured: true,
                    isPremium: true,
                    images: ["/placeholder.svg?height=48&width=48"],
                    tags: ["ecommerce", "shop", "payment", "inventory"]
                },
                {
                    id: "social-login",
                    name: "Social Login",
                    description: "Cho phép người dùng đăng nhập bằng tài khoản mạng xã hội như Facebook, Google, Twitter với cài đặt đơn giản.",
                    shortDescription: "Đăng nhập bằng mạng xã hội",
                    version: "2.1.0",
                    author: "Social Dev",
                    category: "Authentication",
                    rating: 4.7,
                    downloads: 18920,
                    price: 0,
                    isFeatured: false,
                    isPremium: false,
                    images: ["/placeholder.svg?height=48&width=48"],
                    tags: ["login", "social", "facebook", "google"]
                },
                {
                    id: "page-builder",
                    name: "Visual Page Builder",
                    description: "Trình tạo trang kéo thả trực quan với hàng trăm template và element có sẵn, không cần code.",
                    shortDescription: "Trình tạo trang kéo thả",
                    version: "4.0.2",
                    author: "Builder Pro",
                    category: "Page Builder",
                    rating: 4.8,
                    downloads: 32100,
                    price: 79,
                    isFeatured: true,
                    isPremium: true,
                    images: ["/placeholder.svg?height=48&width=48"],
                    tags: ["builder", "drag-drop", "template", "visual"]
                },
                {
                    id: "multilingual",
                    name: "Multilingual Support",
                    description: "Hỗ trợ đa ngôn ngữ hoàn chỉnh với quản lý dịch thuật, tự động phát hiện ngôn ngữ và SEO đa ngôn ngữ.",
                    shortDescription: "Hỗ trợ đa ngôn ngữ",
                    version: "1.9.3",
                    author: "Language Team",
                    category: "Localization",
                    rating: 4.6,
                    downloads: 12450,
                    price: 49,
                    isFeatured: false,
                    isPremium: true,
                    images: ["/placeholder.svg?height=48&width=48"],
                    tags: ["multilingual", "translation", "i18n", "seo"]
                },
                {
                    id: "cache-optimizer",
                    name: "Cache Optimizer",
                    description: "Tối ưu hóa tốc độ website với cache thông minh, nén file và CDN tích hợp.",
                    shortDescription: "Tối ưu hóa cache và tốc độ",
                    version: "2.5.1",
                    author: "Speed Team",
                    category: "Performance",
                    rating: 4.9,
                    downloads: 28760,
                    price: 0,
                    isFeatured: true,
                    isPremium: false,
                    images: ["/placeholder.svg?height=48&width=48"],
                    tags: ["cache", "speed", "performance", "cdn"]
                }
            ],
            categories: ["all", "E-commerce", "Authentication", "Page Builder", "Localization", "Performance"],
            notification: {
                show: false,
                type: 'success', // 'success' | 'error'
                message: ''
            },
            selectedFiles: [],

            showNotification(type, message) {
                this.notification.type = type;
                this.notification.message = message;
                this.notification.show = true;
                setTimeout(() => {
                    this.notification.show = false;
                }, 2500);
            },

            togglePlugin(plugin) {
                // Xác định action và slug
                const action = plugin.is_active ? 'deactivate' : 'activate';
                const slug = plugin.slug;

                fetch('/admin/plugins/action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            action,
                            plugin: slug
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success === true) {
                            plugin.is_active = !plugin.is_active;
                            plugin.status_text = plugin.is_active ? 'Active' : 'Inactive';
                            plugin.status_class = plugin.is_active ? 'success' : 'warning';
                            if (plugin.actions) {
                                plugin.actions.activate = !plugin.is_active;
                                plugin.actions.deactivate = plugin.is_active;
                            }
                            // Hiển thị notification thành công
                            this.showNotification('success', window.langData && window.langData[data.message] ? window.langData[data.message] : data.message);
                        } else {
                            this.showNotification('error', window.langData && window.langData[data.message] ? window.langData[data.message] : (data.message || 'Có lỗi xảy ra!'));
                        }
                    })
                    .catch(() => this.showNotification('error', 'Không thể kết nối máy chủ!'));
            },

            deletePlugin(plugin) {
                if (!confirm(window.langData && window.langData['delete_confirm'] ? window.langData['delete_confirm'] : 'Bạn có chắc chắn muốn xóa plugin này không? Plugin sẽ bị xóa hoàn toàn khỏi hệ thống.')) return;
                const slug = plugin.slug;
                fetch('/admin/plugins/action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            plugin: slug
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success === true) {
                            // Xoá plugin khỏi danh sách
                            this.plugins = this.plugins.filter(p => p.slug !== slug);
                            this.showNotification('success', window.langData && window.langData[data.message] ? window.langData[data.message] : data.message);
                        } else {
                            this.showNotification('error', window.langData && window.langData[data.message] ? window.langData[data.message] : (data.message || 'Có lỗi xảy ra!'));
                        }
                    })
                    .catch(() => this.showNotification('error', 'Không thể kết nối máy chủ!'));
            },

            get activeCount() {
                return this.plugins.filter(p => p.is_active).length;
            },
            get inactiveCount() {
                return this.plugins.filter(p => !p.is_active).length;
            },
            get updateCount() {
                return this.plugins.filter(p => p.hasUpdate).length;
            },

            get filteredPlugins() {
                return Array.isArray(this.plugins) ? this.plugins.filter(plugin => {
                    const matchesSearch = plugin.name.toLowerCase().includes(this.installedSearchTerm.toLowerCase()) ||
                        plugin.description.toLowerCase().includes(this.installedSearchTerm.toLowerCase());

                    if (this.installedFilterTab === 'all') return matchesSearch;
                    if (this.installedFilterTab === 'active') return matchesSearch && plugin.is_active;
                    if (this.installedFilterTab === 'inactive') return matchesSearch && !plugin.is_active;

                    return false;
                }) : [];
            },

            get filteredStorePlugins() {
                return this.storePlugins.filter(plugin => {
                    const matchesSearch = plugin.name.toLowerCase().includes(this.storeSearchTerm.toLowerCase()) ||
                        plugin.description.toLowerCase().includes(this.storeSearchTerm.toLowerCase()) ||
                        plugin.tags.some(tag => tag.toLowerCase().includes(this.storeSearchTerm.toLowerCase()));

                    const matchesCategory = this.storeSelectedCategory === 'all' || plugin.category === this.storeSelectedCategory;

                    return matchesSearch && matchesCategory;
                });
            },

            get featuredPlugins() {
                return this.filteredStorePlugins.filter(p => p.isFeatured);
            },

            handleDrop(e) {
                this.dragActive = false;
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    console.log("File dropped:", e.dataTransfer.files[0]);
                    // Handle file upload logic here
                }
            },

            handleFileSelect(e) {
                const files = e.target.files || e.dataTransfer.files;
                const validFiles = Array.from(files).filter(file => file.name.endsWith('.zip'));
                const allFiles = [...this.selectedFiles, ...validFiles];
                // Loại trùng theo tên
                this.selectedFiles = allFiles.filter((file, idx, arr) =>
                    arr.findIndex(f => f.name === file.name) === idx
                );
            },

            removeSelectedFile(idx) {
                this.selectedFiles.splice(idx, 1);
            },

            async uploadAllFiles() {
                if (this.selectedFiles.length === 0) return;

                // Kiểm tra trùng tên plugin
                for (const file of this.selectedFiles) {
                    if (!file.name.endsWith('.zip')) continue;

                    try {
                        const folderName = await this.getPluginFolderName(file);
                        const exists = this.plugins.some(p => p.slug === folderName.toLowerCase());

                        if (exists) {
                            const confirmOverwrite = window.confirm(
                                `${window.langData && window.langData['plugin_exists'] ? window.langData['plugin_exists'] : 'Plugin "'} ${folderName} ${window.langData && window.langData['confirm_overwrite'] ? window.langData['confirm_overwrite'] : 'exists. Do you want to overwrite?'}`
                            );
                            if (!confirmOverwrite) return; // Hủy upload nếu không đồng ý
                        }
                    } catch (error) {
                        console.error('Error checking plugin folder:', error);
                    }
                }

                // Nếu không có trùng hoặc đã xác nhận, tiến hành upload
                const formData = new FormData();
                this.selectedFiles.forEach(file => {
                    formData.append('item_files[]', file);
                });

                // Thêm thông tin về các item cần ghi đè
                const overwriteItems = [];
                for (const file of this.selectedFiles) {
                    if (!file.name.endsWith('.zip')) continue;
                    try {
                        const folderName = await this.getPluginFolderName(file);
                        const exists = this.plugins.some(p => p.slug === folderName.toLowerCase());
                        if (exists) {
                            overwriteItems.push(folderName.toLowerCase());
                        }
                    } catch (error) {
                        console.error('Error checking plugin folder:', error);
                    }
                }

                // Thêm thông tin overwrite vào formData
                formData.append('overwrite_items', JSON.stringify(overwriteItems));

                fetch('/admin/plugins/uploadWithOverwrite', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.showNotification('success', window.langData && window.langData[data.message] ? window.langData[data.message] : (data.message || 'Upload thành công!'));
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            this.showNotification('error', window.langData && window.langData[data.message] ? window.langData[data.message] : (data.message || 'Upload lỗi!'));
                        }
                    })
                    .catch(() => this.showNotification('error', 'Không thể kết nối máy chủ!'));
            },

            async getPluginFolderName(file) {
                // Đọc tên thư mục đầu tiên trong file zip
                if (typeof JSZip === 'undefined') {
                    throw new Error('JSZip library not loaded');
                }
                const zip = await JSZip.loadAsync(file);
                const firstEntry = Object.keys(zip.files)[0];
                return firstEntry.split("/")[0];
            }
        }
    }
</script>
<script>
    // Pass plugin data from PHP to JavaScript
    window.pluginData = <?= $pluginDataJson ?>;
    // Pass language data from PHP to JavaScript
    window.langData = <?= $langDataJson ?>;
</script>
<?php
Render::asset('js', 'js/jszip.min.js', ['area' => 'backend', 'location' => 'footer']);
// Render::asset('js', 'js/plugins.js', ['area' => 'backend', 'location' => 'footer']);
Render::block('Backend\Footer', ['layout' => 'default']);
?>
