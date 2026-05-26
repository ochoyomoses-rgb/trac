<?php

    jsonResponse([
        'website' => $website,
        'active_users' => (int)$result['active']
    ]);
}

// =========================
// PAGE VIEWS
// =========================

if ($route === '/pageviews' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare(
        "SELECT COUNT(*) as views
         FROM visits
         WHERE website = ?"
    );

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'website' => $website,
        'page_views' => (int)$result['views']
    ]);
}

// =========================
// LATEST VISITS
// =========================

if ($route === '/latest' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare(
        "SELECT website, ip, page, created_at
         FROM visits
         WHERE website = ?
         ORDER BY id DESC
         LIMIT 20"
    );

    $stmt->execute([$website]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse($results);
}

jsonResponse([
    'error' => 'Route not found'
], 404);
