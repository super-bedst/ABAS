</main>
<footer class="border-t border-gray-200 bg-white mt-auto">
    <div class="max-w-4xl mx-auto px-4 py-4 flex flex-wrap justify-between gap-2 text-xs text-gray-500">
        <span>© <?= date('Y') ?> TrekantBrand · <?= htmlspecialchars(abas_config()['app_name']) ?></span>
        <a href="mailto:alarmadm@trekantbrand.dk" class="text-brand hover:underline">Spørgsmål? alarmadm@trekantbrand.dk</a>
    </div>
</footer>
<script src="<?= htmlspecialchars(abas_asset_url('assets/js/abas-ui.js')) ?>" defer></script>
</body>
</html>
