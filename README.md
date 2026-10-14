# LeadFlow CRM

A complete lead-management CRM for software agencies — leads in, clients out.
Built with **Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS**, SQLite-ready.

## Features at a glance

- **Leads** — searchable/filterable list (industry, city, website status, stage), full profiles with map coordinates, CSV import, **Google Sheets sync**
- **Dashboard** — KPI cards (Total / Contacted / Replied / Won + PKR revenue), pipeline summary, recent activity
- **Pipeline** — drag-and-drop kanban: New → Contacted → Replied → Proposal → Won / Lost
- **Email outreach** — template builder with `{{variables}}`, compose + schedule, **open/click tracking**, send log
- **Campaigns** — pick leads, pick template, launch via queue workers, per-campaign stats
- **WhatsApp** — Cloud API integration (template + text messages), webhook receiver, chat threads
- **Unified Inbox** — sent emails + WhatsApp messages in one place
- **Proposals** — services/pricing catalog, proposal builder, **shareable public link** (`/p/{token}`) with Accept/Decline
- **Tasks & Notes** — per-lead notes, follow-up tasks with due dates and reminders
- **Automation** — rule engine (`no_reply_days`, `lead_created`, `stage_changed`) + `app:evaluate-automations`
- **Reports** — funnel, per-industry, campaign and proposal revenue (PKR)
- **Settings** — business profile, services catalog, integrations (Google / WhatsApp / SMTP)

## Setup

### Requirements
- PHP 8.2+ with extensions: mbstring, xml, sqlite3, curl, zip
- Composer, Node 18+ / npm

### Install

```bash
cd leadflow-crm
composer install
cp .env.example .env
php artisan key:generate
```

### Configure `.env`

```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
MAIL_MAILER=log            # dev: emails go to laravel.log. Production: set smtp + MAIL_* vars
APP_URL=http://localhost:8000
```

Optional integrations (all degrade gracefully when empty — see FEATURES.md):

```env
GOOGLE_SHEETS_ID=
GOOGLE_CREDENTIALS_JSON=      # service-account JSON (or GOOGLE_CREDENTIALS_PATH=/path/file.json)
WHATSAPP_TOKEN=
WHATSAPP_PHONE_ID=
WHATSAPP_VERIFY_TOKEN=
```

### Database + demo data

```bash
touch database/database.sqlite
php artisan migrate --seed
```

Demo login: **admin@leadflow.pk** / **password**
(~39 Pakistani sample leads, 8 PKR services, 3 email templates, 2 automations)

### Run

```bash
npm install && npm run build
php artisan serve            # app at http://127.0.0.1:8000
php artisan queue:work       # background worker for email/campaign sends
```

### Useful commands

```bash
php artisan test                        # full test suite (84 tests)
php artisan app:sync-sheet               # pull leads from Google Sheet
php artisan app:evaluate-automations     # run automation rules now
```

## Notes

- Composer is configured to use the Tencent Packagist mirror
  (`https://mirrors.cloud.tencent.com/composer/`) because api.github.com is
  unreachable from this network. If your network reaches GitHub directly, you can
  remove the `repositories` override in `composer.json`.
- `Lead` has both a `notes` text column (freeform) and a `notes()` relation
  (timestamped note records) — use `->notes` for the column, `->notes()` for the relation.
