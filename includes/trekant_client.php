<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/curl_cainfo.php';

class TrekantClient
{
    private string $baseUrl;
    private string $loginUser;
    private string $loginPass;
    private string $term;
    private ?string $token = null;
    private int $tokenExpiresAt = 0;

    public function __construct(?array $override = null)
    {
        $cfg = abas_config()['trekant'];
        $this->baseUrl = rtrim($override['url'] ?? $cfg['url'], '/');
        $this->baseUrl = (string) preg_replace('#/api/v1$#i', '', $this->baseUrl);
        $this->loginUser = strtoupper((string) ($override['user'] ?? $cfg['user']));
        $this->loginPass = (string) ($override['pass'] ?? $cfg['pass']);
        $this->term = (string) ($override['term'] ?? $cfg['term']);
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function login(): string
    {
        if ($this->token && time() < $this->tokenExpiresAt - 60) {
            return $this->token;
        }
        $resp = $this->requestRaw('/login/login', ['loginName' => $this->loginUser, 'loginPass' => $this->loginPass], false);
        if (empty($resp['success']) && empty($resp['message']['token']['result'])) {
            throw new RuntimeException('TrekantBrand login fejlede: ' . ($resp['message'] ?? 'ukendt'));
        }
        $token = $resp['message']['token']['result'] ?? null;
        if (!$token) {
            throw new RuntimeException('TrekantBrand returnerede intet token');
        }
        $this->token = $token;
        $ttl = (int) ($resp['message']['token']['expiresIn'] ?? $resp['message']['token']['expiration_seconds'] ?? 50400);
        $this->tokenExpiresAt = time() + $ttl;

        return $this->token;
    }

    public function call(string $procedure, array $payload): array
    {
        $token = $this->login();

        return $this->requestRaw('/api/v1/' . $procedure, $payload, true, $token);
    }

    public function searchInstallations(string $userid, ?string $miscno2 = null, ?string $insNo = null, int $maxrows = 100): array
    {
        $body = ['userid' => strtoupper($userid), 'maxrows' => min(100, max(1, $maxrows))];
        if ($miscno2 !== null && $miscno2 !== '') {
            $body['miscno2'] = strtolower($miscno2);
        }
        if ($insNo !== null && $insNo !== '') {
            $body['ins_no'] = $insNo;
        }

        return $this->call('g_search_installations', $body);
    }

    public function getTestQueueSummary(string $userid, string $dealId = 'TB', int $sIns = 0): array
    {
        return $this->call('g_ma_testqueue_summary', [
            'userid' => strtoupper($userid),
            'noaccess' => 0,
            'noprofile' => 0,
            's_ins' => $sIns,
            'deal_id' => $dealId,
            's_inc' => -1,
            'scrolldir' => 1,
            'tstrun' => 1,
            'debug' => 0,
        ]);
    }

    public function getTestQueueStatus(int $sIns, string $dealId, int $lines = 5): array
    {
        return $this->call('g_ma_testqueue', [
            's_ins' => $sIns,
            'deal_id' => $dealId,
            'lines' => $lines,
        ]);
    }

    public function startService(string $userid, int $sIns, string $dealId, string $testTime, string $comm = '', int $zoneix = -1, ?int $sInc = null): array
    {
        $body = [
            'userid' => strtoupper($userid),
            's_ins' => $sIns,
            'deal_id' => $dealId,
            'test_time' => $testTime,
            'comm' => $comm,
            'zoneix' => $zoneix,
            'term' => $this->term,
        ];
        if ($sInc !== null && $sInc > 0) {
            $body['s_inc'] = $sInc;
        }

        return $this->call('c_ma_testqueue', $body);
    }

    public function stopService(int $sIns, string $dealId, ?int $sInc = null, string $comment = ''): array
    {
        $body = [
            's_ins' => $sIns,
            'deal_id' => $dealId,
            'term' => $this->term,
            'comment' => $comment,
        ];
        if ($sInc !== null) {
            $body['s_inc'] = $sInc;
        }

        return $this->call('d_ma_testqueue', $body);
    }

    public function getTestQueueRemaining(string $userid, int $sIns, string $dealId, int $sInc): array
    {
        return $this->call('c_ma_testqueue_remaining', [
            'userid' => strtoupper($userid),
            's_ins' => $sIns,
            'deal_id' => $dealId,
            's_inc' => $sInc,
            'test_time' => '0000:00:00:00',
        ]);
    }

    public function setTestQueueRemaining(string $userid, int $sIns, string $dealId, int $sInc, string $testTime, string $comm = ''): array
    {
        $body = [
            'userid' => strtoupper($userid),
            's_ins' => $sIns,
            'deal_id' => $dealId,
            's_inc' => $sInc,
            'test_time' => $testTime,
        ];
        if ($comm !== '') {
            $body['comm'] = $comm;
        }

        return $this->call('c_ma_testqueue_remaining', $body);
    }

    public function getAlarmLog(string $userid, int $sIns, string $dealId, int $lines = 20, ?array $dateRange = null): array
    {
        $body = [
            'userid' => strtoupper($userid),
            's_ins' => $sIns,
            'deal_id' => $dealId,
            'lines' => $lines,
        ];
        if ($dateRange) {
            foreach (['startdate', 'starttime', 'enddate', 'endtime'] as $k) {
                if (!empty($dateRange[$k])) {
                    $body[$k] = $dateRange[$k];
                }
            }
        }

        return $this->call('g_ma_alarmlog', $body);
    }

    public function addLogComment(int $sIns, string $dealId, int $sInc, string $comm): array
    {
        return $this->call('c_ma_alarmlog_comment', [
            's_ins' => $sIns,
            'deal_id' => $dealId,
            's_inc' => $sInc,
            'comm' => $comm,
        ]);
    }

    public function getInstallationDetails(int $sIns, string $dealId): array
    {
        return $this->call('g_ma_installations', [
            's_ins' => $sIns,
            'deal_id' => $dealId,
        ]);
    }

    public function getInstallationZones(string $userid, int $sIns, string $dealId): array
    {
        return $this->call('g_ma_zone', [
            'userid' => strtoupper($userid),
            's_ins' => $sIns,
            'deal_id' => $dealId,
        ]);
    }

    public function getInstallationContacts(int $sIns, string $dealId, string $userid): array
    {
        return $this->call('g_contper', [
            's_ins' => $sIns,
            'deal_id' => $dealId,
            'userid' => strtoupper($userid),
            's_cont' => 0,
        ]);
    }

    private function requestRaw(string $path, array $payload, bool $useToken, ?string $token = null): array
    {
        $url = $this->baseUrl . $path;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('JSON encode failed');
        }
        $headers = ['Content-Type: application/json'];
        if ($useToken && $token) {
            $headers[] = 'User-Token: ' . $token;
        }
        $ch = curl_init($url);
        $curlOpts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ] + abas_curl_ssl_options();
        curl_setopt_array($ch, $curlOpts);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($errno) {
            throw new RuntimeException('TrekantBrand HTTP fejl: ' . $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException(
                'TrekantBrand HTTP ' . $code . ': ' . substr(trim((string) $raw), 0, 300)
            );
        }
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Ugyldigt JSON-svar (HTTP ' . $code . ')');
        }

        return $data;
    }
}

function abas_trekant(): TrekantClient
{
    static $client = null;
    if (!$client) {
        $client = new TrekantClient();
    }

    return $client;
}

function abas_trekant_userid(?array $user): string
{
    if ($user && !empty($user['trekant_userid'])) {
        return strtoupper((string) $user['trekant_userid']);
    }
    $cfg = abas_config()['trekant'];

    return strtoupper((string) $cfg['user']);
}

function abas_format_test_time_hours(float $hours): string
{
    return abas_format_test_time_seconds((int) round($hours * 3600));
}

function abas_format_test_time_seconds(int $totalSeconds): string
{
    $totalSeconds = max(0, $totalSeconds);
    $h = intdiv($totalSeconds, 3600);
    $rem = $totalSeconds % 3600;
    $m = intdiv($rem, 60);
    $s = $rem % 60;

    return sprintf('0000:%02d:%02d:%02d', $h, $m, $s);
}

/** @deprecated Use abas_format_test_time_seconds() */
function abas_format_test_time_minutes(int $totalMinutes): string
{
    return abas_format_test_time_seconds($totalMinutes * 60);
}

function abas_trekant_extract_tim_rem(array $response): ?int
{
    $rows = abas_trekant_rows($response);
    if ($rows === []) {
        return null;
    }
    $first = $rows[0];
    if (!is_array($first) || !array_key_exists('tim_rem', $first)) {
        return null;
    }
    $n = (int) $first['tim_rem'];

    return $n >= 0 ? $n : null;
}

function abas_unlimited_test_time(): string
{
    return '9999:23:59:59';
}

function abas_trekant_pick_nested(array $response, array $paths): mixed
{
    foreach ($paths as $path) {
        $val = $response;
        $ok = true;
        foreach ($path as $key) {
            if (!is_array($val) || !array_key_exists($key, $val)) {
                $ok = false;
                break;
            }
            $val = $val[$key];
        }
        if ($ok && $val !== null && $val !== '') {
            return $val;
        }
    }

    return null;
}

function abas_trekant_rows(array $response): array
{
    $rows = abas_trekant_pick_nested($response, [
        ['ResultSet'],
        ['resultSet'],
        ['message', 'ResultSet'],
        ['message', 'resultSet'],
        ['message', 'result'],
        ['message', 'rows'],
        ['Message', 'ResultSet'],
    ]);
    if (!is_array($rows)) {
        return [];
    }
    if ($rows === [] || isset($rows[0])) {
        return $rows;
    }

    return [$rows];
}

function abas_trekant_return_code(array $response): int
{
    $code = abas_trekant_pick_nested($response, [
        ['ReturnCode'],
        ['returnCode'],
        ['returncode'],
        ['message', 'ReturnCode'],
        ['message', 'returnCode'],
        ['message', 'returncode'],
        ['Message', 'ReturnCode'],
    ]);

    return $code === null ? -1 : (int) $code;
}

/** g_ma_testqueue_summary returnerer 15342 naar s_ins=0 (ingen global liste). */
function abas_trekant_summary_return_ok(int $code): bool
{
    return $code === 0 || $code === 15342;
}

function abas_trekant_extract_s_inc(array $response): ?int
{
    $rows = abas_trekant_rows($response);
    if ($rows !== []) {
        $first = $rows[0];
        if (is_array($first) && isset($first['s_inc'])) {
            $n = (int) $first['s_inc'];

            return $n > 0 ? $n : null;
        }
        if (is_numeric($first)) {
            $n = (int) $first;

            return $n > 0 ? $n : null;
        }
    }

    $raw = abas_trekant_pick_nested($response, [
        ['ResultSet'],
        ['resultSet'],
        ['message', 'ResultSet'],
        ['message', 'resultSet'],
    ]);
    if (is_numeric($raw)) {
        $n = (int) $raw;

        return $n > 0 ? $n : null;
    }
    if (is_array($raw) && isset($raw[0]) && is_numeric($raw[0])) {
        $n = (int) $raw[0];

        return $n > 0 ? $n : null;
    }

    return null;
}

function abas_trekant_trim_comment(string $comment, int $maxLen = 80): string
{
    $text = trim($comment);
    if ($text === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? (string) mb_substr($text, 0, $maxLen)
        : substr($text, 0, $maxLen);
}

function abas_trekant_active_test_s_inc(TrekantClient $client, int $sIns, string $dealId): ?int
{
    $resp = $client->getTestQueueStatus($sIns, $dealId, 5);
    if (abas_trekant_return_code($resp) !== 0) {
        return null;
    }
    $rows = abas_trekant_rows($resp);
    if ($rows === []) {
        return null;
    }
    $n = (int) ($rows[0]['s_inc'] ?? 0);

    return $n > 0 ? $n : null;
}

function abas_trekant_response_hint(array $response): string
{
    if (isset($response['message']) && is_string($response['message'])) {
        return $response['message'];
    }
    if (isset($response['error']) && is_string($response['error'])) {
        return $response['error'];
    }
    if (isset($response['message']) && is_array($response['message'])) {
        foreach (['message', 'Message', 'error', 'Error'] as $key) {
            if (!empty($response['message'][$key]) && is_string($response['message'][$key])) {
                return $response['message'][$key];
            }
        }

        return 'message: ' . implode(', ', array_keys($response['message']));
    }

    return implode(', ', array_keys($response));
}

