# SPEED AUDIT PRO — Setup Guide

> A Laravel MVP that analyzes any website and returns expert-level performance
> reports with actionable fix advice. Your lead-generation engine.

---

## Prerequisites

- PHP 8.1+
- Composer
- MySQL (or SQLite for local dev)
- A Google Cloud account (free)

---

## 1. Clone / create the Laravel project

```bash
composer create-project laravel/laravel speedauditpro
cd speedauditpro
```

Then copy all the files from this repo into the project root, matching the
directory structure shown below.

---

## 2. Install dependencies

No extra packages needed for the MVP — it uses Laravel's built-in HTTP client.

---

## 3. Get your Google PageSpeed API key (free, 2 minutes)

1. Go to https://console.cloud.google.com/
2. Create a project (or use an existing one)
3. Navigate to **APIs & Services → Library**
4. Search for **"PageSpeed Insights API"** → Enable it
5. Go to **APIs & Services → Credentials → Create Credentials → API Key**
6. Copy the key

> You get **25,000 free requests/day** — more than enough for MVP.

---

## 4. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
DB_DATABASE=speedaudit_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass

PAGESPEED_API_KEY=AIzaSy...your_key_here
```

---

## 5. Run database migration

```bash
php artisan migrate
```

This creates the single `reports` table.

---

## 6. Add PageSpeed key to `config/services.php`

Open `config/services.php` and add:

```php
'pagespeed' => [
    'key' => env('PAGESPEED_API_KEY', ''),
],
```

---

## 7. Place the files

```
app/
  Http/Controllers/AnalyzerController.php
  Models/Report.php
  Services/PageSpeedService.php
database/
  migrations/..._create_reports_table.php
resources/views/
  layouts/app.blade.php
  index.blade.php
  report.blade.php
routes/
  web.php
```

---

## 8. Run locally

```bash
php artisan serve
```

Visit http://localhost:8000

---

## 9. Customize the CTA (IMPORTANT — your money maker)

In `resources/views/report.blade.php`, find the **CTA section** near the bottom
and replace:

```html
href="https://calendly.com/YOUR_LINK" href="https://wa.me/YOUR_NUMBER?text=..."
```

with your actual Calendly URL and WhatsApp number.

---

## 10. Deploy to production

### Shared hosting / cPanel

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### VPS (DigitalOcean / Hetzner)

- Use Nginx + PHP-FPM
- Point document root to `public/`
- Set up SSL via Let's Encrypt (Certbot)

### Quick option: Laravel Forge + DigitalOcean $6/mo droplet

---

## How to use this as a lead-generation engine

1. Find a slow website (use BuiltWith, Google search, local businesses)
2. Run your tool on their URL
3. Copy the shareable report link
4. Send them: _"I ran a free audit on your site — here's what I found: [link]. I can fix these in 2–3 days."_
5. Link to your Calendly or WhatsApp in the CTA

---

## File structure overview

```
speedauditpro/
├── app/
│   ├── Http/Controllers/AnalyzerController.php   — routes logic
│   ├── Models/Report.php                          — Eloquent model
│   └── Services/PageSpeedService.php              — API + parsing + fix advice
├── database/migrations/..._create_reports_table.php
├── resources/views/
│   ├── layouts/app.blade.php                      — base layout
│   ├── index.blade.php                            — homepage (URL input)
│   └── report.blade.php                           — results page
├── routes/web.php
├── config/services.php                            — add pagespeed key here
└── .env.example
```

---

## Phase 2 ideas (only after MVP is generating leads)

- [ ] PDF export of the report
- [ ] Email the report to user
- [ ] Re-analyze button (hits API again)
- [ ] Comparison: before vs after fix
- [ ] User accounts + saved history

# speedauditpro
