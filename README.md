# Public Transport Crowd Predictor (TransitCrowd)

> A complete, functional, responsive, and open-source web application for estimating and predicting public transit crowd levels using **PHP 8** and **Apache CouchDB** as the primary NoSQL database.

---

## 1. Project Overview

Passengers using buses, commuter trains, and metro services often experience overcrowding, long station queues, and uncomfortable journeys due to lack of crowd visibility.

**Public Transport Crowd Predictor** provides:
- **Current Crowd Level Estimates**: Categorized into Low, Moderate, High, and Very High.
- **Explainable Future Forecasts**: Multi-horizon predictions (+15, +30, +60, +120 minutes) using statistical historical averages, peak-hour multipliers, and recent passenger observations.
- **Departure Time Comparison**: Side-by-side comparison across travel windows to recommend the least-crowded departure time.
- **Voluntary Passenger Crowd Reporting**: Moderated community observations with built-in rate limiting and anti-spam duplicate detection.
- **Interactive Route & Station Explorer**: OpenStreetMap & Leaflet mapping with complete corridor stop sequences.
- **Personalized Passenger Dashboard**: Saved favorite routes, favorite stations, recent searches, and suggested departure windows.
- **Administrative Control Panel**: Full CRUD operations for routes, stops, operating schedules, capacity metrics, CouchDB system health, and report moderation.

---

## 2. Technology Stack

- **Backend**: PHP 8.x, PHP cURL extension, PHP Sessions, Native Password Hashing (Bcrypt), Modular Services & Controllers.
- **NoSQL Database**: Apache CouchDB 3.x (HTTP/JSON REST API, Mango Indexes, Fauxton Web Administration).
- **Frontend**: Responsive HTML5, CSS3 (Transport Navy Theme), Vanilla JavaScript (no heavy frontend frameworks required).
- **Visualization & Maps**: Chart.js 4.x (Hourly curves, Weekday vs Weekend patterns, Category distributions) and Leaflet 1.9 with OpenStreetMap tiles.

---

## 3. Crowd Categories & Classification

Crowd capacity utilization estimates are classified into standard categories:
- **Low**: 0–30% capacity utilization (Many seats available)
- **Moderate**: 31–60% capacity utilization (Some seating left, light standing)
- **High**: 61–80% capacity utilization (Standing room only)
- **Very High**: 81–100% capacity utilization (Crowded / crush load)

*Transparency Notice: The application strictly distinguishes between verified historical observations, rule-based forecasts, and voluntary passenger reports. When insufficient evidence exists, the engine explicitly outputs `data_quality: "insufficient"` and `estimated_occupancy_percent: null` rather than fabricating random values.*

---

## 4. Apache CouchDB Database Architecture

All application data is stored natively in Apache CouchDB across 6 dedicated lowercase databases:

| Database Name | Schema Type | Description |
|---|---|---|
| `transport_routes` | `route` | Route numbers, line names, transport mode (bus/metro/train), ordered stop sequences, headway frequencies, and peak multipliers. |
| `transport_stops` | `stop` | Station names, stop codes, zones, latitude/longitude, and platform facilities. |
| `transport_observations`| `observation` | Historical calibration observations with timestamps, crowd categories, and vehicle occupancy levels. |
| `transport_reports` | `crowd_report` | Voluntary passenger submissions with timestamps, hashed client IPs, and moderation statuses (`pending`, `approved`, `rejected`). |
| `transport_users` | `user` | Passenger and administrator accounts with Bcrypt password hashes and saved travel preferences. |
| `transport_predictions`| `prediction` | Audit log and cache of generated prediction calculations with explainable metadata. |

### Mango Query Indexes
Optimized Mango indexes are created automatically during initialization:
- `idx_routes_active_mode`: `['active', 'transport_mode', 'route_number']`
- `idx_stops_mode`: `['transport_mode', 'stop_name']`
- `idx_obs_route_stop_time`: `['route_id', 'stop_id', 'observed_at']`
- `idx_rep_status_time`: `['status', 'reported_at']`
- `idx_users_email`: `['email']`

---

## 5. Explainable Prediction Engine

The PHP prediction engine (`app/services/CrowdPredictionService.php`) executes an explainable rule-based and statistical estimation pipeline:
1. **Query Relevant Observations**: Retrieves historical CouchDB records for the specified route and stop.
2. **Time Window & Day Partitioning**: Filters records falling within a configurable window (e.g., target time ±45 minutes) and matches day type (weekday commute vs weekend leisure).
3. **Distance-Weighted Average**: Computes a time-proximity weighted historical average.
4. **Commuter Peak Adjustments**: Factors in corridor operating schedules and configured peak multipliers (e.g. 08:00–10:30 and 17:00–19:30).
5. **Real-Time Passenger Report Blending**: If approved passenger observations exist within the last 60 minutes, the engine blends recent live feedback (recency decay).
6. **Data Sufficiency Guard**: If fewer than 2 valid observations are available, the engine returns `data_quality: "insufficient"` with explanatory text advising the passenger to submit a crowd report.

---

## 6. Project Folder Structure

```
PublicTransportCrowdPredictor/
  app/
    config/
      config.php               # Environment and application configuration loader
    controllers/
      AdminController.php       # Administrator dashboard, CRUD, and moderation
      AnalyticsController.php   # Chart.js endpoints and historical trends
      AuthController.php        # Passenger sign-in, registration, and session control
      DashboardController.php   # Passenger personalized dashboard
      HomeController.php        # Public landing page
      PredictionController.php   # Multi-horizon forecasts and time comparison
      ReportController.php      # Passenger voluntary report submission
      RouteController.php       # Route explorer, details, and Leaflet map
    middleware/
      CsrfMiddleware.php        # CSRF token generation and validation
    services/
      AdminService.php          # CouchDB storage stats and system health
      AnalyticsService.php      # Trend aggregation and hourly histograms
      AuthenticationService.php # Bcrypt auth, rate limiting, and favorites
      CouchDBService.php        # Secure CouchDB REST API client via PHP cURL
      CrowdPredictionService.php# Explainable crowd prediction engine
      CrowdReportService.php    # Report validation, duplicate/spam prevention
      RouteService.php          # Route discovery and stop sequencing
      StopService.php           # Stop retrieval and geographical coordinates
    validators/
      ValidationService.php     # Input sanitization and form validation
  database/
    init_couchdb.php            # Automated, repeatable CouchDB initializer
    indexes.php                 # Mango index definitions
    seed_data.php               # Realistic demonstration dataset
  public/
    index.php                   # Front controller and application router
    assets/
      css/style.css             # Mobile-first responsive transport CSS
      js/
        app.js                  # Navigation, UI linking, and AJAX favorites
        charts.js               # Chart.js trend renderers
        map.js                  # Leaflet OpenStreetMap visualizer
  views/
    admin/                      # Admin dashboard, routes, stops, and reports
    analytics/                  # Analytics and visual trends
    auth/                       # Login and registration views
    dashboard/                  # Passenger dashboard
    home/                       # Landing page
    layout/                     # Shared header and footer
    predictions/                # Prediction and time comparison views
    reports/                    # Report submission form and recent feed
    routes/                     # Route explorer and detail views
    stops/                      # Stop detail view
    errors/                     # 404 and 403 error pages
  tests/
    test_all.php                # Master automated test suite (18 assertions)
    test_couchdb_connection.php # CouchDB connection verification
    test_auth.php               # Authentication verification
    test_routes_stops.php       # Routes and stops verification
    test_reports.php            # Report submission and spam check
    test_predictions.php        # Prediction engine verification
  .env.example                  # Environment configuration template
  .env                          # Active environment configuration
  .gitignore                    # Git ignore file
  start_couchdb.bat             # Windows launcher for CouchDB
  start_app.bat                 # Windows launcher for Web App
  run_tests.bat                 # Windows launcher for test suite
  README.md                     # Comprehensive documentation
```

---

## 7. Windows Installation & Setup Guide

### Prerequisites
1. **PHP 8.x**: Installed (e.g. via XAMPP at `C:\xampp\php\php.exe` or standalone PHP).
   - Ensure `curl`, `json`, `mbstring`, `openssl`, and `session` extensions are enabled in `php.ini`.
2. **Apache CouchDB 3.x**: Installed and running on `http://127.0.0.1:5984`.
   - On this system, CouchDB 3.5.3 is configured at `C:\Users\HP\couchdb\server`.

---

### Step 1: Start Apache CouchDB
Launch CouchDB using the provided batch launcher:
```cmd
start_couchdb.bat
```
*Or manually via PowerShell:*
```powershell
& "C:\Users\HP\couchdb\server\bin\couchdb.cmd"
```

Verify CouchDB is running by visiting:
- **CouchDB HTTP API**: `http://127.0.0.1:5984/`
- **Fauxton Web Administration**: `http://127.0.0.1:5984/_utils/`

---

### Step 2: Initialize Databases, Indexes & Seed Data
Run the database initialization script (idempotent and safe to execute repeatedly):
```cmd
C:\xampp\php\php.exe database\init_couchdb.php
```

Output:
```
[1/4] Verifying connection to Apache CouchDB...
 [OK] Connected to CouchDB (Version: 3.5.3)

[2/4] Initializing required application databases...
 + Database 'transport_routes' created.
 + Database 'transport_stops' created.
 + Database 'transport_observations' created.
 + Database 'transport_reports' created.
 + Database 'transport_users' created.
 + Database 'transport_predictions' created.

[3/4] Creating Mango indexes...
 + Created idx_routes_active_mode, idx_stops_mode, idx_obs_route_stop_time, etc.

[4/4] Seeding demonstration dataset...
 * transport_users: 2 seeded
 * transport_stops: 8 seeded
 * transport_routes: 4 seeded
 * transport_observations: 552 seeded
 * transport_reports: 4 seeded
```

---

### Step 3: Launch the Web Application
Start PHP's built-in development server:
```cmd
start_app.bat
```
*Or manually:*
```cmd
C:\xampp\php\php.exe -S localhost:8000 -t public
```

Open your browser and navigate to:
👉 **`http://localhost:8000`**

---

## 8. Default Demonstration Credentials

For quick evaluation during college presentations, use the pre-seeded accounts or the one-click **"Quick-Fill Demo Credentials"** buttons on `/login`:

| Role | Email Address | Password | Permissions |
|---|---|---|---|
| **System Administrator** | `admin@example.test` | `Admin@123` | Full access to `/admin`, route/stop CRUD, database health, and report moderation. |
| **Demo Passenger** | `passenger@example.test` | `Passenger@123` | Access to personalized dashboard, favorite routes/stops, and departure suggestions. |

---

## 9. Running the Automated Test Suite

Verify all 18 automated acceptance checks with a single command:
```cmd
run_tests.bat
```
*Or via command line:*
```cmd
C:\xampp\php\php.exe tests\test_all.php
```

Expected result:
```
====================================================================
   Public Transport Crowd Predictor - Master Automated Test Suite   
====================================================================

 [PASS] 1. CouchDB Connectivity & Version Check
 [PASS] 2. All 6 Required Application Databases Exist in CouchDB
 [PASS] 3. Admin Authentication with Bcrypt Verification
 [PASS] 4. Invalid Password Rejected Securely
 [PASS] 5. Passenger Registration with Unique Email
 [PASS] 6. Duplicate Email Registration Prevented
 [PASS] 7. Route Retrieval with Ordered Stop Sequence
 [PASS] 8. Stop Filtering by Transport Mode
 [PASS] 9. Origin-Destination Route Discovery
 [PASS] 10. Voluntary Crowd Report Submission
 [PASS] 11. Duplicate / Spam Report Prevention within 10-Minute Window
 [PASS] 12. Explainable Prediction with Non-Empty Reasoning
 [PASS] 13. Insufficient Data Handling Returns NULL Occupancy & "insufficient" Quality
 [PASS] 14. Departure Time Comparison & Optimal Window Recommendation
 [PASS] 15. Analytics Dashboard Aggregates Actual CouchDB Records
 [PASS] 16. Hourly Crowd Trend Profile Computed (6:00 - 23:00)
 [PASS] 17. Weekday Commute vs Weekend Leisure Patterns Computed
 [PASS] 18. System Health and CouchDB Storage Metrics Inspected

====================================================================
 Test Results: 18 / 18 Passed (100%)
 Status: ALL ACCEPTANCE CRITERIA MET
====================================================================
```

---

## 10. Troubleshooting on Windows

### 1. CouchDB Fails to Start with "Monster detected ohno!"
- **Cause**: CouchDB checks that the Erlang distribution cookie in `etc\vm.args` is not set to the default placeholder `monster`.
- **Solution**: Ensure `C:\Users\HP\couchdb\server\etc\vm.args` contains a custom string such as `-setcookie transport_crowd_secret_cookie_2026`.

### 2. cURL Extension Missing in PHP
- **Symptom**: `cURL error: Failed to initialize PHP cURL handle`.
- **Solution**: Open `php.ini`, ensure `extension=curl` is uncommented (remove the leading `;`), and restart the PHP development server.

### 3. Port 5984 Already in Use
- Check active listeners using: `netstat -ano | findstr 5984`.
- If an existing CouchDB or Erlang instance is running, you can connect directly without restarting.

### 4. Admin Access Blocked (HTTP 403)
- Ensure you have logged in with `admin@example.test`. Passenger accounts are forbidden from accessing `/admin` endpoints by design.

---

## 11. Future Integration Roadmap

- **GTFS Static**: Import official transit schedule packages directly into `transport_routes` and `transport_stops`.
- **GTFS-Realtime**: Ingest verified vehicle positions and vehicle occupancy status feeds where transit agencies provide live APIs.
- **Python ML Microservice**: Train Random Forest / Gradient Boosting regression models on CouchDB observation streams and expose predictions via a lightweight FastAPI sidecar.

---

## 12. Open-Source License

This project is released under the **MIT Open Source License**. Suitable for academic mini-projects, research demonstrations, and transit planning software.
