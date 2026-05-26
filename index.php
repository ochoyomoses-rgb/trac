<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';

$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function jsonResponse($data, $status = 200)
{
    http_response_code($status);

    echo json_encode($data);

    exit;
}

function getIpAddress()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/*
|--------------------------------------------------------------------------
| TRACK VISIT
|--------------------------------------------------------------------------
*/

if ($route === '/track' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $website = $data['website'] ?? 'unknown';

    $page = $data['page'] ?? '/';

    $ip = getIpAddress();

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    $stmt = $db->prepare("
        INSERT INTO visits (
            website,
            ip,
            page,
            user_agent,
            referrer
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $website,
        $ip,
        $page,
        $userAgent,
        $referrer
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Visit tracked'
    ]);
}

/*
|--------------------------------------------------------------------------
| TOTAL VISITORS
|--------------------------------------------------------------------------
*/

if ($route === '/visitors' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ip) as total
        FROM visits
        WHERE website = ?
    ");

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'website' => $website,
        'total_visitors' => (int)$result['total']
    ]);
}

/*
|--------------------------------------------------------------------------
| ACTIVE USERS
|--------------------------------------------------------------------------
*/

if ($route === '/active' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ip) as active
        FROM visits
        WHERE website = ?
        AND created_at >= datetime('now', '-5 minutes')
    ");

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'website' => $website,
        'active_users' => (int)$result['active']
    ]);
}

/*
|--------------------------------------------------------------------------
| PAGE VIEWS
|--------------------------------------------------------------------------
*/

if ($route === '/pageviews' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT COUNT(*) as views
        FROM visits
        WHERE website = ?
    ");

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'website' => $website,
        'page_views' => (int)$result['views']
    ]);
}

/*
|--------------------------------------------------------------------------
| LATEST VISITS
|--------------------------------------------------------------------------
*/

if ($route === '/latest' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT website, ip, page, created_at
        FROM visits
        WHERE website = ?
        ORDER BY id DESC
        LIMIT 20
    ");

    $stmt->execute([$website]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse($results);
}

/*
|--------------------------------------------------------------------------
| 404
|--------------------------------------------------------------------------
*/

jsonResponse([
    'error' => 'Route not found'
], 404);
