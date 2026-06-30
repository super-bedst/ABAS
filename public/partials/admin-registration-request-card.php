<?php

declare(strict_types=1);

/** @var array<string, mixed> $p */
/** @var int $userId */
/** @var string $regType */
/** @var bool $isMontor */
/** @var bool $isOwnerType */
/** @var bool $needsNewCompany */
/** @var bool $hasInstaller */
/** @var string $emailDomain */
/** @var list<array<string, mixed>> $instPreview */
/** @var bool $allInstFound */
/** @var list<array<string, mixed>> $installationGroups */
/** @var bool $groupPickerUsesSearch */
/** @var bool $approveLocked */

$displayName = (string) ($p['registration_display_name'] ?? $p['username'] ?? '');
$requestedAt = substr((string) ($p['registration_requested_at'] ?? ''), 0, 16);
$effectiveSmsRole = $isMontor ? 'montor' : $regType;
$showSmsOptions = abas_user_role_uses_sms_code($effectiveSmsRole);
$showMontorScope = $isMontor;
$canApprove = !$approveLocked;
?>
<article class="abas-reg-request" data-reg-request-card>
    <header class="abas-reg-request-header">
        <div class="min-w-0">
            <h2 class="abas-reg-request-title"><?= htmlspecialchars($displayName) ?></h2>
            <p class="abas-reg-request-subtitle">
                <?= htmlspecialchars(abas_registration_type_label($regType)) ?>
                <?php if ($hasInstaller): ?>
                    <span class="abas-badge abas-badge-ok ml-1">Godkendt installatør</span>
                <?php elseif ($needsNewCompany): ?>
                    <span class="abas-badge abas-badge-pending ml-1">Kræver nyt firma</span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($requestedAt !== ''): ?>
            <time class="abas-reg-request-time" datetime="<?= htmlspecialchars((string) ($p['registration_requested_at'] ?? '')) ?>">
                <?= htmlspecialchars($requestedAt) ?>
            </time>
        <?php endif; ?>
    </header>

    <dl class="abas-reg-request-meta">
        <div><dt>E-mail</dt><dd><?= htmlspecialchars((string) $p['email']) ?></dd></div>
        <div><dt>Telefon</dt><dd><?= htmlspecialchars((string) ($p['phone'] ?? '—')) ?></dd></div>
        <?php if (!empty($p['display_company_name'])): ?>
            <div class="sm:col-span-2"><dt>Firma</dt><dd><?= htmlspecialchars((string) $p['display_company_name']) ?></dd></div>
        <?php endif; ?>
    </dl>

    <?php if ($needsNewCompany): ?>
    <section class="abas-reg-request-module abas-reg-request-module--warn">
        <h3 class="abas-reg-request-module-title">Ny virksomhed</h3>
        <form method="post" class="abas-reg-request-inline-form">
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <input type="hidden" name="action" value="create_company">
            <div class="abas-field">
                <label class="abas-label">Firmanavn</label>
                <input name="company_name" required class="abas-input text-sm"
                       value="<?= htmlspecialchars((string) ($p['registration_requested_company_name'] ?? '')) ?>">
            </div>
            <div class="abas-field">
                <label class="abas-label">Domæne</label>
                <input name="email_domain" required class="abas-input text-sm font-mono" value="<?= htmlspecialchars($emailDomain) ?>">
            </div>
            <button type="submit" class="abas-btn-secondary text-sm shrink-0">Opret og tilknyt</button>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($isOwnerType && $instPreview !== []): ?>
    <details class="abas-reg-request-module" <?= count($instPreview) <= 3 ? 'open' : '' ?>>
        <summary class="abas-reg-request-module-summary">
            Ønskede anlæg (<?= count($instPreview) ?>)
            <?php if (!$allInstFound): ?>
                <span class="abas-badge abas-badge-pending ml-2">Mangler i cache</span>
            <?php endif; ?>
        </summary>
        <div class="abas-reg-request-module-body">
            <?php if (!$allInstFound): ?>
            <form method="post" class="mb-3">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                <input type="hidden" name="action" value="sync_installations">
                <button type="submit" class="abas-btn-secondary text-xs">Hent manglende fra Trekant</button>
            </form>
            <?php endif; ?>
            <div class="abas-table-wrap">
                <table class="abas-table text-sm" data-abas-client-sort>
                    <thead>
                        <tr>
                            <?php abas_render_client_table_sort_th('ABA-nr.', 0); ?>
                            <?php abas_render_client_table_sort_th('Navn', 1); ?>
                            <?php abas_render_client_table_sort_th('By', 2); ?>
                            <?php abas_render_client_table_sort_th('Status', 3); ?>
                            <?php abas_render_client_table_sort_th('Cache', 4); ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($instPreview as $row): ?>
                        <?php $inst = $row['installation']; ?>
                        <tr>
                            <td class="font-mono font-medium"><?= htmlspecialchars($row['miscno2']) ?></td>
                            <?php if ($inst): ?>
                                <td><?= htmlspecialchars((string) ($inst['name'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($inst['city'] ?? '—')) ?></td>
                                <td>
                                    <span class="<?= htmlspecialchars(abas_mon_stat_badge_class((string) ($inst['mon_stat'] ?? ''))) ?> text-xs">
                                        <?= htmlspecialchars(abas_mon_stat_label((string) ($inst['mon_stat'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="text-emerald-700">Ja</td>
                            <?php else: ?>
                                <td colspan="3" class="text-amber-800">Ikke i lokal cache</td>
                                <td class="text-amber-700">Nej</td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <form method="post" class="abas-reg-request-approve">
        <input type="hidden" name="user_id" value="<?= $userId ?>">

        <div class="abas-reg-request-options">
            <label class="abas-reg-request-option">
                <input type="checkbox" name="send_welcome_email" value="1" class="abas-checkbox" checked>
                <span>Send velkomst-e-mail</span>
            </label>

            <?php if ($isMontor): ?>
            <label class="abas-reg-request-option">
                <input type="checkbox" name="as_virksomhedsadmin" value="1" class="abas-checkbox" data-reg-role-virksomhedsadmin>
                <span>Godkend som installatøradministrator</span>
            </label>
            <?php endif; ?>

            <?php if ($showSmsOptions): ?>
            <div class="abas-reg-request-option-stack" data-reg-module="sms">
                <label class="abas-reg-request-option">
                    <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox" data-reg-sms-toggle>
                    <span>Må betjene via SMS</span>
                </label>
                <div class="abas-reg-request-sms-panel" data-reg-sms-panel hidden>
                    <label class="abas-label text-xs" for="sms-code-<?= $userId ?>">SMS-kode</label>
                    <input id="sms-code-<?= $userId ?>" name="sms_code" class="abas-input font-mono text-sm max-w-[14rem]"
                           data-reg-sms-input minlength="6" autocomplete="off" placeholder="Min. 6 tegn">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($showMontorScope): ?>
        <details class="abas-reg-request-scope" data-reg-scope-details>
            <summary class="abas-reg-request-scope-summary">Begræns adgang</summary>
            <div class="abas-reg-request-scope-body">
                <label class="flex items-start gap-2 text-sm mb-3">
                    <input type="checkbox" name="montor_scoped_access" value="1" class="abas-checkbox mt-0.5" data-reg-scoped-toggle>
                    <span>Aktivér begrænsning — brugeren ser kun valgte grupper (fuld adgang som standard).</span>
                </label>
                <?php if ($installationGroups === []): ?>
                    <p class="text-sm text-gray-500">Ingen anlægsgrupper oprettet endnu.</p>
                <?php else: ?>
                    <?php if ($groupPickerUsesSearch): ?>
                        <input type="search" class="abas-input text-sm mb-2" placeholder="Søg grupper …" data-reg-group-filter>
                    <?php endif; ?>
                    <ul class="abas-reg-request-group-list" data-reg-group-list>
                        <?php foreach ($installationGroups as $group): ?>
                            <li data-reg-group-item data-reg-group-label="<?= htmlspecialchars(strtolower((string) $group['name'] . ' ' . ($group['public_id'] ?? ''))) ?>">
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="group_ids[]" value="<?= (int) $group['id'] ?>" class="abas-checkbox mt-0.5">
                                    <span>
                                        <span class="font-medium"><?= htmlspecialchars((string) $group['name']) ?></span>
                                        <span class="text-gray-500 text-xs block font-mono"><?= htmlspecialchars((string) $group['public_id']) ?> · <?= (int) ($group['member_count'] ?? 0) ?> anlæg</span>
                                    </span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <div class="abas-reg-request-actions">
            <button type="submit" name="action" value="approve" class="abas-btn-primary"
                <?= $canApprove ? '' : ' disabled title="Synk anlæg først"' ?>>
                Godkend
            </button>
            <button type="submit" name="action" value="reject" class="abas-btn-secondary" onclick="return confirm('Afvis ansøgning?')">Afvis</button>
        </div>
        <?php if (!$canApprove): ?>
            <p class="abas-hint">Godkend er låst indtil alle anlæg findes i cache.</p>
        <?php endif; ?>
    </form>
</article>
