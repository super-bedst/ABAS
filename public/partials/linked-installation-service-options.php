<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $options */
/** @var string $linkedOptionsContext */
$linkedOptionsContext = $linkedOptionsContext ?? 'start';
$isStop = $linkedOptionsContext === 'stop';
?>
<div class="abas-vc-linked-panel mb-4">
    <?php if ($isStop): ?>
        <p class="text-sm font-medium text-gray-800 mb-1">Koblede anlæg i service (valgfrit)</p>
        <p class="abas-hint mb-3">Vælg om tilknyttede anlæg også skal stoppes samtidig.</p>
    <?php else: ?>
        <p class="text-sm font-medium text-gray-800 mb-1">Koblede anlæg (valgfrit)</p>
        <p class="abas-hint mb-3">Vælg om tilknyttede anlæg også skal i service samtidig.</p>
    <?php endif; ?>
    <ul class="abas-vc-linked-list">
        <?php foreach ($options as $item): ?>
            <?php
            if ($isStop) {
                $disabled = false;
                $statusHint = '';
                $checked = true;
            } else {
                $disabled = empty($item['allows_service']) || !empty($item['in_service']);
                $statusHint = !empty($item['in_service'])
                    ? 'Allerede i service'
                    : (empty($item['allows_service']) ? (string) ($item['mon_stat_label'] ?? 'Kan ikke sættes i service') : '');
                $checked = false;
            }
            ?>
            <li>
                <label class="abas-vc-linked-option<?= $disabled ? ' abas-vc-linked-option--disabled' : '' ?>">
                    <input type="checkbox" name="linked_miscno2[]" value="<?= htmlspecialchars((string) $item['miscno2']) ?>"
                           class="abas-checkbox mt-0.5"<?= $disabled ? ' disabled' : '' ?><?= $checked ? ' checked' : '' ?>>
                    <span>
                        <span class="font-mono font-medium text-brand"><?= htmlspecialchars((string) $item['miscno2']) ?></span>
                        <span class="text-gray-600 ml-1"><?= htmlspecialchars((string) ($item['name'] ?? '')) ?></span>
                        <?php if ($statusHint !== ''): ?>
                            <span class="text-xs text-gray-500 block"><?= htmlspecialchars($statusHint) ?></span>
                        <?php endif; ?>
                    </span>
                </label>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
