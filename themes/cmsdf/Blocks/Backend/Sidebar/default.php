<script>
    window.menuData = <?= json_encode($menuData ?? []) ?>;
    window.currentUrl = '<?= $_SERVER['REQUEST_URI'] ?>';
    
    // Xác định menu items cần mở rộng từ backend
    window.expandedMenus = [];
    <?php if (isset($menuData)): ?>
        <?php foreach ($menuData as $section): ?>
            <?php if (isset($section['items'])): ?>
                <?php foreach ($section['items'] as $item): ?>
                    <?php if (isset($item['expanded']) && $item['expanded']): ?>
                        window.expandedMenus.push('<?= $section['id'] ?>-<?= $item['id'] ?>');
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</script>

<div class="min-h-screen w-full">
  <!-- Sidebar -->
  <nav :class="sidebarClasses()" @mouseenter="isHovered = true" @mouseleave="isHovered = false" class="fixed inset-y-0 left-0 z-[60] transition-all duration-300 ease-in-out bg-menu-background border-r border-menu-border">
    <div x-show="menuState !== 'hidden'" class="h-full flex flex-col">
      <div class="h-12 px-3 flex items-center border-b border-menu-border flex-shrink-0">
        <a href="<?= admin_url('home'); ?>" class="flex items-center gap-3 w-full" x-show="showText()"><img src="<?= theme_assets('favicon/apple-icon-180x180.png', 'Backend'); ?>" alt="<?= option('site_brand') ?>" width="32" height="32" class="flex-shrink-0 hidden dark:block" /><img src="<?= theme_assets('favicon/apple-icon-180x180.png', 'Backend'); ?>" alt="<?= option('site_brand') ?>" width="32" height="32" class="flex-shrink-0 block dark:hidden" /><span class="text-lg font-semibold text-gray-900 dark:text-white transition-opacity duration-200"><?= option('site_brand') ?></span></a>
        <div class="flex justify-center w-full" x-show="!showText()"><img src="<?= theme_assets('favicon/apple-icon-180x180.png', 'Backend'); ?>" alt="<?= option('site_brand') ?>" width="32" height="32" class="flex-shrink-0 hidden dark:block" /><img src="<?= theme_assets('favicon/apple-icon-180x180.png', 'Backend'); ?>" alt="<?= option('site_brand') ?>" width="32" height="32" class="flex-shrink-0 block dark:hidden" /></div>
      </div>
      <div class="flex-1 overflow-y-auto overflow-x-hidden py-0 px-2 scrollbar-none">
        <div class="space-y-6">
          <template x-for="section in menuData" :key="section.id">
            <div>
              <div x-show="showText()" class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-menu-section-label transition-opacity duration-200" x-text="section.label"></div>
              <div class="space-y-1">
                <template x-for="item in section.items" :key="item.id">
                  <div x-data="{ itemId: `${section.id}-${item.id}` }">
                    <!-- Render as link if item has href and no children, otherwise as div -->
                    <template x-if="item.href && item.href !== '#' && (!item.children || item.children.length === 0)">
                      <a :href="item.href" @click="isMobile ? isMobileMenuOpen = false : null" :class="getActiveClasses(item)" :title="!showText() ? item.label : ''">
                        <i :data-lucide="item.icon" class="h-4 w-4 flex-shrink-0 text-menu-icon group-hover:text-menu-icon-hover"></i>
                        <div x-show="showText()" class="mx-3 flex-1 flex items-center justify-between transition-opacity duration-200">
                          <span class="text-menu-text group-hover:text-menu-text-hover" x-text="item.label"></span>
                          <div class="flex items-center space-x-1"><template x-if="item.isNew"><span class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">New</span></template><template x-if="item.badge"><span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full" x-text="item.badge"></span></template></div>
                        </div>
                        <div x-show="!showText()" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50" x-text="item.label"></div>
                      </a>
                    </template>
                    <!-- Render as div if item has children or no href -->
                    <template x-if="!item.href || item.href === '#' || (item.children && item.children.length > 0)">
                      <div @click="hasChildren(item) ? toggleExpanded(itemId) : (isMobile ? isMobileMenuOpen = false : null)" :class="getActiveClasses(item)" :title="!showText() ? item.label : ''">
                        <i :data-lucide="item.icon" class="h-4 w-4 flex-shrink-0 text-menu-icon group-hover:text-menu-icon-hover"></i>
                        <div x-show="showText()" class="mx-3 flex-1 flex items-center justify-between transition-opacity duration-200">
                          <span class="text-menu-text group-hover:text-menu-text-hover" x-text="item.label"></span>
                          <div class="flex items-center space-x-1"><template x-if="item.isNew"><span class="px-1.5 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full">New</span></template><template x-if="item.badge"><span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full" x-text="item.badge"></span></template><template x-if="hasChildren(item)"><i data-lucide="chevron-down" class="h-3 w-3 transition-transform duration-200 text-gray-500" :class="isExpanded(itemId) ? 'rotate-180' : 'rotate-0'"></i></template></div>
                        </div>
                        <div x-show="!showText()" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50" x-text="item.label"></div>
                      </div>
                    </template>
                    <div x-show="isExpanded(itemId) && showText()" x-collapse class="mt-1 space-y-1">
                      <template x-for="child in item.children" :key="child.id"><a :href="child.href" :class="getActiveClasses(child, true)"><i :data-lucide="child.icon" class="h-4 w-4 mr-3 flex-shrink-0 text-menu-icon/80 group-hover:text-menu-icon-hover"></i><span x-text="child.label"></span></a></template>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
      </div>
      <div class="px-2 py-4 border-t border-menu-border flex-shrink-0">
        <div class="space-y-1">
          <a href="<?= admin_url('me') ?>" class="flex items-center py-2 px-3 text-sm rounded-md transition-colors text-menu-text hover:bg-menu-background-hover hover:text-menu-text-hover relative group">
            <i data-lucide="user-cog" class="h-4 w-4 flex-shrink-0 text-menu-icon group-hover:text-menu-icon-hover"></i>
            <span x-show="showText()" class="ml-3 flex-1">Change Profile</span>
          </a>
          <a href="https://docs.cmsfullform.com" target="_blank" class="flex items-center py-2 px-3 text-sm rounded-md transition-colors text-menu-text hover:bg-menu-background-hover hover:text-menu-text-hover relative group">
            <i data-lucide="help-circle" class="h-4 w-4 flex-shrink-0 text-menu-icon group-hover:text-menu-icon-hover"></i>
            <span x-show="showText()" class="ml-3 flex-1">User Guide</span>
          </a>
        </div>
      </div>
    </div>
  </nav>
  <div x-show="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-[55] lg:hidden"></div>
  <div :class="mainContentMargin()" class="flex-1 flex flex-col transition-all duration-300 ease-in-out">