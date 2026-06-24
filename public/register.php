<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/registration.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'register.php') {
    abas_redirect('index.php');
}

$conn = abas_db();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $miscList = $_POST['miscno2'] ?? [];
    if (!is_array($miscList)) {
        $miscList = [$miscList];
    }
    $result = abas_submit_registration(
        $conn,
        (string) ($_POST['registration_type'] ?? ''),
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['phone'] ?? ''),
        $miscList,
        !empty($_POST['request_new_company']),
        (string) ($_POST['requested_company_name'] ?? '')
    );
    if ($result['ok']) {
        $success = true;
    } else {
        $error = $result['message'] ?? 'Kunne ikke sende anmodning.';
    }
}

$portalTitle = 'Anmod om adgang';
require __DIR__ . '/partials/public-header.php';
?>
<div class="max-w-4xl mx-auto px-4 py-8 sm:py-12">
    <p class="text-xs uppercase tracking-widest text-brand font-semibold mb-2">TrekantBrand</p>
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Anmod om adgang</h1>
    <p class="text-gray-600 mb-8 max-w-2xl">Eksterne montører, anlægsejere og anlægsafprøvere kan her ansøge om brugeradgang til ABA Service.</p>

    <?php if ($success): ?>
        <div class="abas-portal-info mb-8">
            <h2 class="font-semibold text-gray-900 mb-2">Tak for din anmodning</h2>
            <p class="text-gray-700">Din ansøgning er modtaget og afventer godkendelse af TrekantBrand. Du modtager e-mail med login, når den er behandlet.</p>
        </div>
        <?php require __DIR__ . '/partials/register-steps.php'; ?>
    <?php else: ?>

    <div class="abas-portal-info mb-6">
        <h2 class="font-semibold text-gray-900 mb-2">Hvad er ABA Service?</h2>
        <p class="text-sm text-gray-700">ABA Service er en platform til at sætte automatiske brandalarmeringsanlæg i service og se alarmlog. Montører skal have e-mail fra et godkendt installatør-firma. Anlægsejere og afprøvere angiver hvilke anlæg de skal have adgang til.</p>
    </div>

    <?php if ($error): ?><p class="abas-alert-error mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="abas-portal-form">
        <div class="abas-portal-form-head">Anmodning om adgang</div>
        <form method="post" class="abas-form p-5 sm:p-6" id="register-form" data-abas-loading="Sender anmodning…">
            <fieldset class="space-y-2 mb-4">
                <legend class="abas-label">Jeg ansøger som *</legend>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="registration_type" value="montor" class="abas-checkbox" required checked> Teknikker (montør)</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="registration_type" value="anlaegsejer" class="abas-checkbox"> Anlægsejer</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" name="registration_type" value="anlaegsafprover" class="abas-checkbox"> Anlægsafprøver</label>
            </fieldset>

            <div class="abas-field">
                <label class="abas-label" for="name">Navn *</label>
                <input id="name" name="name" required class="abas-input" placeholder="Fornavn og efternavn" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="abas-field" id="field-company">
                <label class="abas-label">Firma</label>
                <div id="company-preview" class="abas-input bg-gray-50 text-gray-500 text-sm">Dit firma vises automatisk når du indtaster din e-mail</div>
            </div>

            <div class="abas-field hidden border border-amber-200 bg-amber-50 rounded-xl p-4 space-y-3" id="field-new-company">
                <label class="flex items-start gap-2 text-sm text-gray-800">
                    <input type="checkbox" name="request_new_company" value="1" class="abas-checkbox mt-1" id="request_new_company"<?= !empty($_POST['request_new_company']) ? ' checked' : '' ?>>
                    <span id="request-new-company-label">Ønsker at oprette virksomhed med domænet …</span>
                </label>
                <div class="abas-field !mb-0">
                    <label class="abas-label text-sm" for="requested_company_name">Virksomhedsnavn</label>
                    <input id="requested_company_name" name="requested_company_name" class="abas-input" placeholder="Fx Friis Brandteknik ApS" value="<?= htmlspecialchars($_POST['requested_company_name'] ?? '') ?>">
                </div>
            </div>

            <div class="abas-field">
                <label class="abas-label" for="email">E-mail *</label>
                <input id="email" name="email" type="email" required class="abas-input" placeholder="navn@firma.dk" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="abas-field">
                <label class="abas-label" for="phone">Telefon *</label>
                <input id="phone" name="phone" required class="abas-input" placeholder="+45 00 00 00 00" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="abas-field hidden" id="field-installations">
                <label class="abas-label">Ønskede anlæg (ABA-nr.) *</label>
                <div id="miscno2-list" class="space-y-2">
                    <div class="flex gap-2 miscno2-row">
                        <input name="miscno2[]" class="abas-input flex-1 font-mono" placeholder="fx fab0100">
                    </div>
                </div>
                <button type="button" id="add-miscno2" class="abas-btn-secondary mt-2 text-sm">+ Tilføj anlæg</button>
            </div>

            <div class="abas-portal-note text-sm" id="note-montor">
                Bemærk: Din e-mail skal matche dit firmas godkendte domæne — eller du kan ansøge om oprettelse af en ny virksomhed. Efter godkendelse modtager du login via e-mail.
            </div>
            <div class="abas-portal-note text-sm hidden" id="note-owner">
                Bemærk: Angiv de anlæg du skal have adgang til. TrekantBrand godkender og tilknytter anlæggene.
            </div>

            <button type="submit" class="abas-btn-primary abas-btn-block mt-4">Send anmodning →</button>
        </form>
    </div>

    <?php require __DIR__ . '/partials/register-steps.php'; ?>
    <?php endif; ?>
</div>
<script>
(function () {
    var typeInputs = document.querySelectorAll('input[name="registration_type"]');
    var fieldCompany = document.getElementById('field-company');
    var fieldInst = document.getElementById('field-installations');
    var noteMontor = document.getElementById('note-montor');
    var noteOwner = document.getElementById('note-owner');
    var emailInput = document.getElementById('email');
    var companyPreview = document.getElementById('company-preview');
    var fieldNewCompany = document.getElementById('field-new-company');
    var requestNewCompany = document.getElementById('request_new_company');
    var requestNewCompanyLabel = document.getElementById('request-new-company-label');
    var requestedCompanyName = document.getElementById('requested_company_name');
    var lookupUrl = <?= json_encode(abas_url('api/register-domain-lookup.php')) ?>;
    var domainKnown = false;

    function updateNewCompanyUi() {
        var email = emailInput.value.trim();
        var domain = '';
        if (email.indexOf('@') > 0) {
            domain = email.split('@').pop().toLowerCase();
        }
        if (requestNewCompanyLabel) {
            requestNewCompanyLabel.textContent = domain
                ? 'Ønsker at oprette virksomhed med domænet ' + domain
                : 'Ønsker at oprette ny virksomhed';
        }
        var showNew = document.querySelector('input[name="registration_type"]:checked')?.value === 'montor' && !domainKnown && domain !== '';
        fieldNewCompany.classList.toggle('hidden', !showNew);
        if (!showNew) {
            requestNewCompany.checked = false;
            requestedCompanyName.required = false;
        } else {
            requestedCompanyName.required = requestNewCompany.checked;
        }
    }

    function updateType() {
        var type = document.querySelector('input[name="registration_type"]:checked');
        var isMontor = type && type.value === 'montor';
        fieldCompany.classList.toggle('hidden', !isMontor);
        fieldInst.classList.toggle('hidden', isMontor);
        noteMontor.classList.toggle('hidden', !isMontor);
        noteOwner.classList.toggle('hidden', isMontor);
        fieldInst.querySelectorAll('input').forEach(function (inp) {
            inp.required = !isMontor;
        });
        updateNewCompanyUi();
    }
    typeInputs.forEach(function (el) { el.addEventListener('change', updateType); });
    updateType();

    function lookupCompany() {
        var email = emailInput.value.trim();
        domainKnown = false;
        if (!email || email.indexOf('@') < 1) {
            companyPreview.textContent = 'Dit firma vises automatisk når du indtaster din e-mail';
            companyPreview.className = 'abas-input bg-gray-50 text-gray-500 text-sm';
            updateNewCompanyUi();
            return;
        }
        fetch(lookupUrl + '?email=' + encodeURIComponent(email))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.company) {
                    domainKnown = true;
                    companyPreview.textContent = data.company;
                    companyPreview.className = 'abas-input bg-gray-50 text-gray-900 font-medium text-sm';
                } else {
                    domainKnown = false;
                    companyPreview.textContent = data.message || 'Domænet er ikke godkendt endnu';
                    companyPreview.className = 'abas-input bg-amber-50 text-amber-800 text-sm';
                }
                updateNewCompanyUi();
            });
    }
    emailInput.addEventListener('blur', lookupCompany);
    emailInput.addEventListener('input', updateNewCompanyUi);
    if (emailInput.value.trim()) {
        lookupCompany();
    } else {
        updateNewCompanyUi();
    }

    requestNewCompany.addEventListener('change', function () {
        requestedCompanyName.required = requestNewCompany.checked;
    });
    if (requestNewCompany.checked) {
        requestedCompanyName.required = true;
    }

    document.getElementById('register-form').addEventListener('submit', function (e) {
        var isMontor = document.querySelector('input[name="registration_type"]:checked')?.value === 'montor';
        if (isMontor && !domainKnown && requestNewCompany.checked && !requestedCompanyName.value.trim()) {
            e.preventDefault();
            alert('Angiv virksomhedsnavn når du ansøger om oprettelse af ny virksomhed.');
            requestedCompanyName.focus();
        }
    });

    document.getElementById('add-miscno2').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'flex gap-2 miscno2-row';
        row.innerHTML = '<input name="miscno2[]" class="abas-input flex-1 font-mono" placeholder="fx fab0100" required>' +
            '<button type="button" class="abas-btn-secondary text-sm remove-misc">Fjern</button>';
        document.getElementById('miscno2-list').appendChild(row);
    });
    document.getElementById('miscno2-list').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-misc')) {
            var rows = document.querySelectorAll('.miscno2-row');
            if (rows.length > 1) e.target.closest('.miscno2-row').remove();
        }
    });
})();
</script>
<?php require __DIR__ . '/partials/public-footer.php';
