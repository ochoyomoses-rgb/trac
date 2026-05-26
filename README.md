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
