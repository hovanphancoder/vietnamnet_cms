<?php
/**
 * @var string $type
 * @var string $message
 * @var string|null $title
 */
$type = $type ?? 'info';
$icon = '';
$color = '';
$border = '';
$close = '';
switch ($type) {
  case 'success':
    $icon = '<i data-lucide="check" class="h-5 w-5 flex-shrink-0"></i>';
    $color = 'bg-green-100 text-green-800';
    $border = 'border border-green-300';
    $close = 'text-green-700 hover:text-green-900';
    break;
  case 'error':
    $icon = '<i data-lucide="x" class="h-5 w-5 flex-shrink-0"></i>';
    $color = 'bg-red-100 text-red-800';
    $border = 'border border-red-300';
    $close = 'text-red-700 hover:text-red-900';
    break;
  case 'warning':
    $icon = '<i data-lucide="alert-triangle" class="h-5 w-5 flex-shrink-0"></i>';
    $color = 'bg-yellow-100 text-yellow-800';
    $border = 'border border-yellow-300';
    $close = 'text-yellow-700 hover:text-yellow-900';
    break;
  default:
    $icon = '<i data-lucide="info" class="h-5 w-5 flex-shrink-0"></i>';
    $color = 'bg-blue-100 text-blue-800';
    $border = 'border border-blue-300';
    $close = 'text-blue-700 hover:text-blue-900';
    break;
}
?>
<div x-data="{ show: true }" x-show="show" x-transition.opacity class="flex items-center gap-3 <?= $color ?> <?= $border ?> px-4 py-3 rounded-lg mb-4 shadow">
  <?= $icon ?>
  <div class="flex-1">
    <?php if (!empty($title)): ?>
      <div class="font-semibold mb-0.5"><?= htmlspecialchars($title) ?></div>
    <?php endif; ?>
    <span><?= htmlspecialchars($message) ?></span>
  </div>
  <button @click="show = false" class="ml-2 <?= $close ?> focus:outline-none">
    <i data-lucide="x" class="h-4 w-4"></i>
    <span class="sr-only">Close</span>
  </button>
</div>