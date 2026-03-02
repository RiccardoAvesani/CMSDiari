<?php

declare(strict_types=1);

$title = $title ?? null;
$title = is_string($title) ? trim($title) : '';
if ($title === '') {
    return;
}
?>

<div class="pt-2">
    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
        <?php echo e($title); ?>
    </div>
</div>