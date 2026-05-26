# Plain PHP Visit Tracker API (Render + SQLite)

This is a lightweight deployment-ready visit tracker API you can call from ANY website.

You can:

* Track visitors from multiple websites
* Track active users
* View total visitors
* View page visits
* Store all traffic in SQLite
* Deploy directly to Render from GitHub

---

# Final Project Structure

```txt
visit-tracker/
│
├── index.php
├── db.php
├── render.yaml
├── .gitignore
│
├── data/
│   └── tracker.sqlite
│
└── README.md
```

---

# 1. index.php

```php
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

// =========================
// TRACK VISIT
// =========================

if ($route === '/track' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $website = $data['website'] ?? 'unknown';
    $page = $data['page'] ?? '/';

    $ip = getIpAddress();

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    $stmt = $db->prepare(
        "INSERT INTO visits (
            website,
            ip,
            page,
            user_agent,
            referrer
        ) VALUES (?, ?, ?, ?, ?)"
    );

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

// =========================
// TOTAL VISITORS
// =========================

if ($route === '/visitors' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT ip) as total
         FROM visits
         WHERE website = ?"
    );

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'website' => $website,
        'total_visitors' => (int)$result['total']
    ]);
}

// =========================
// ACTIVE USERS
// =========================

if ($route === '/active' && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $website = $_GET['website'] ?? null;

    if (!$website) {
        jsonResponse([
            'error' => 'website parameter required'
        ], 400);
    }

    $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT ip) as active
         FROM visits
         WHERE website = ?
         AND created_at >= datetime('now', '-5 minutes')"
    );

    $stmt->execute([$website]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

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
```

---

# 2. db.php

```php
<?php

if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

$db = new PDO('sqlite:data/tracker.sqlite');

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec(
    "CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        website TEXT,
        ip TEXT,
        page TEXT,
        user_agent TEXT,
        referrer TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
);
```

---

# 3. render.yaml

```yaml
services:
  - type: web
    name: visit-tracker-api
    runtime: php
    plan: free
    startCommand: php -S 0.0.0.0:10000
```

---

# 4. .gitignore

```gitignore
/data/*.sqlite
```

---

# 5. README.md

```md
# Visit Tracker API

Simple plain PHP visit tracker.

## Routes

### Track Visit

POST /track

Body:

{
  "website": "mysite.com",
  "page": "/home"
}

---

### Get Visitors

GET /visitors?website=mysite.com

---

### Get Active Users

GET /active?website=mysite.com

---

### Get Page Views

GET /pageviews?website=mysite.com

---

### Get Latest Visits

GET /latest?website=mysite.com
```

---

# Frontend Integration

Put this on ANY website:

```html
<script>
fetch('https://YOUR-RENDER-URL.onrender.com/track', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        website: window.location.hostname,
        page: window.location.pathname
    })
});
</script>
```

---

# Example API Calls

## Get Visitors

```txt
https://YOUR-RENDER-URL.onrender.com/visitors?website=mysite.com
```

## Get Active Users

```txt
https://YOUR-RENDER-URL.onrender.com/active?website=mysite.com
```

## Get Page Views

```txt
https://YOUR-RENDER-URL.onrender.com/pageviews?website=mysite.com
```

## Get Latest Visits

```txt
https://YOUR-RENDER-URL.onrender.com/latest?website=mysite.com
```

---

# GitHub Deployment

1. Create GitHub repository
2. Upload files
3. Push repository
4. Connect GitHub to Render
5. Create new Web Service on Render
6. Deploy

---

# IMPORTANT: Persistent Database Storage

Without persistent storage, SQLite data can reset.

On Render:

1. Open your service
2. Go to Settings
3. Open Disks
4. Add Persistent Disk

Use:

Mount Path:

```txt
/opt/render/project/src/data
```

Size:

```txt
1 GB
```

This keeps all visitor data permanently.

---

# Recommended Improvements Later

* API key security
* Dashboard
* Country detection
* Bot filtering
* Session tracking
* Device tracking
* Realtime websocket users
* PostgreSQL migration
* Admin authentication
