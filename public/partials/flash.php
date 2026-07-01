<?php

declare(strict_types=1);

/** @var array{type:string, message:string, installation_links?:list<array{id:int, miscno2:string, name?:string}>} $flash */
?>
<div class="<?= $flash['type'] === 'error' ? 'abas-alert-error' : 'abas-alert-success' ?><?= !empty($flash['installation_links']) ? ' mb-4' : '' ?>">
    <p class="<?= !empty($flash['installation_links']) ? 'mb-0' : '' ?>"><?= htmlspecialchars((string) $flash['message']) ?></p>
    <?php if (!empty($flash['installation_links'])): ?>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($flash['installation_links'] as $link): ?>
                <?php
                $miscno2 = (string) ($link['miscno2'] ?? '');
                $name = trim((string) ($link['name'] ?? ''));
                $label = 'Gå til anlægskortet ' . $miscno2;
                if ($name !== '') {
                    $label .= ' — ' . $name;
                }
                ?>
                <a href="<?= htmlspecialchars(abas_url('installation.php?id=' . (int) ($link['id'] ?? 0))) ?>"
                   class="inline-flex items-center rounded-full bg-white/80 px-3 py-1.5 text-sm font-medium text-red-900 border border-red-200 hover:bg-white no-underline">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
