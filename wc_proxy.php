<?php
// WC News proxy - fetches + caches Warhammer Community headlines.
// Called by index.php via JS fetch; never blocks the main page render.

header('Content-Type: application/json');
header('Cache-Control: no-store');

// -- Debug mode: wc_proxy.php?debug=1 (never expose to public) --
$debug = !empty($_GET['debug']);

$cacheFile = __DIR__ . '/data/wc_news_cache.json';
$ttl       = 4 * 3600;
$now       = time();

// Serve from cache when fresh
if (file_exists($cacheFile)) {
    $raw = json_decode(file_get_contents($cacheFile), true);
    if (!empty($raw['fetched_at']) && ($now - $raw['fetched_at']) < $ttl && !empty($raw['articles'])) {
        if ($debug) { echo json_encode(['source'=>'cache','count'=>count($raw['articles']),'articles'=>$raw['articles']]); exit; }
        echo json_encode($raw['articles']);
        exit;
    }
}

// --- HTTP fetch helper (curl preferred, file_get_contents fallback) ---
function wcFetch($url, $timeout = 15) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'WaaghPaint/1.0 (personal hobby tracker)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) return [null, "curl: $err (HTTP $code)"];
        return [$body, null];
    }
    if (ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create([
            'http' => ['timeout' => $timeout, 'header' => "User-Agent: WaaghPaint/1.0 (personal hobby tracker)\r\n"],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? [$body, null] : [null, 'file_get_contents failed'];
    }
    return [null, 'No HTTP method available (curl off, allow_url_fopen off)'];
}

// --- Strategy 1: Google News RSS (indexes WC articles as published, no Cloudflare) ---
$urls = [];
$gnRss = 'https://news.google.com/rss/search?q=site:warhammer-community.com&hl=en-GB&gl=GB&ceid=GB:en';
[$rssBody, $rssErr] = wcFetch($gnRss, 20);
if ($rssBody) {
    $rss = @simplexml_load_string($rssBody);
    if ($rss) {
        foreach ($rss->channel->item ?? [] as $item) {
            $title = html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = preg_replace('/ [-–] Warhammer Community$/u', '', $title);
            $pub   = (string)$item->pubDate;
            $mod   = $pub ? date('c', strtotime($pub)) : '';
            // Extract Google News article ID from description href for viewer URL
            $desc  = (string)$item->description;
            preg_match('#href="https://news\.google\.com/rss/articles/([^"?]+)#i', $desc, $dm);
            $articleId = $dm[1] ?? '';
            // Google redirect link (works in browsers) + viewer URL (fetchable for og:image)
            $gnLink    = $articleId ? 'https://news.google.com/rss/articles/' . $articleId . '?oc=5' : '';
            $viewerUrl = $articleId ? 'https://news.google.com/articles/' . $articleId : '';
            if ($title && $gnLink) $urls[] = ['url' => $gnLink, 'viewer' => $viewerUrl, 'title' => $title, 'mod' => $mod];
            if (count($urls) >= 20) break;
        }
    }
}

// --- Strategy 2: sitemap fallback ---
if (empty($urls)) {
    [$sitemap, ] = wcFetch('https://www.warhammer-community.com/sitemap.xml', 20);
    if ($sitemap) {
        $xml = @simplexml_load_string($sitemap);
        if ($xml) {
            $iter = $xml->getName() === 'sitemapindex' ? $xml->sitemap : $xml->url;
            foreach ($iter as $u) {
                $loc = (string)$u->loc;
                $mod = (string)($u->lastmod ?? '');
                if (strpos($loc, '/en-gb/articles/') !== false)
                    $urls[] = ['url' => $loc, 'title' => '', 'mod' => $mod];
            }
            usort($urls, fn($a, $b) => strcmp($b['mod'], $a['mod']));
        }
    }
}

$urls = array_slice($urls, 0, 20);

if ($debug && empty($urls)) {
    echo json_encode(['error' => 'no article URLs found', 'rss_err' => $rssErr, 'curl' => function_exists('curl_init'), 'fopen' => (bool)ini_get('allow_url_fopen')]);
    exit;
}

$articles = [];
foreach ($urls as $art) {
    if (count($articles) >= 8) break;
    $articles[] = [
        'title' => $art['title'],
        'url'   => $art['url'],
        'image' => '',
        'mod'   => $art['mod'],
        'ork'   => (bool)preg_match('/\bOrks?\b/i', $art['title']),
    ];
}

@file_put_contents($cacheFile, json_encode([
    'fetched_at' => $now,
    'articles'   => $articles,
], JSON_UNESCAPED_SLASHES), LOCK_EX);

if ($debug) {
    echo json_encode(['source' => 'fresh', 'curl' => function_exists('curl_init'), 'fopen' => (bool)ini_get('allow_url_fopen'), 'urls_found' => count($urls), 'articles' => $articles, 'fetch_errors' => $fetchErrors]);
    exit;
}

echo json_encode($articles);
