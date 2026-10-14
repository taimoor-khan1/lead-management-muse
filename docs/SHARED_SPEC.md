# LeadFlow CRM — Shared Build Spec (read this first)

Project: `~/workspace/leadflow-crm` — Laravel 11 + Vue 3 + Inertia.js (Breeze vue stack), SQLite dev DB, Tailwind CSS.
UI style: SIMPLE, CLEAN, LIGHT, MODERN — match the approved prototype:
- font: "DM Sans" (Google Fonts, already linked in prototype; add link in app.blade.php)
- bg: #eef0f2 (page), cards: #ffffff, radius 12px, soft shadow
- ink: #181a1c, muted: #72777d, borders: #e5e7e9
- PRIMARY ACCENT: #f26a2e (orange) for primary buttons/active nav
- status pills: green #268866/#eaf7f1, amber #ad6c14/#fff6dc, blue #3178c6/#edf5ff, red #c64f4f/#fff0f0
- sidebar 230px white, logo "LeadFlow", nav: Dashboard, Leads, Pipeline, Inbox, Campaigns, Proposals, Tasks, Automation, Reports, Settings
- Lots of whitespace, simple sans-serif, minimal icons (inline SVG, stroke style)

## Conventions (MANDATORY)
- Pages: `resources/js/Pages/<Module>/*.vue`, `<script setup>`, `defineOptions({ layout: AppLayout })` with `import AppLayout from '@/Layouts/AppLayout.vue'`.
- Controllers: `app/Http/Controllers/<Module>/<Name>Controller.php`, return `Inertia::render('Module/Page', [...])`.
- Use route names via Ziggy `route('leads.index')` — NEVER hardcode URLs in Vue except public/token links.
- Currency: PKR. Sample data: Pakistani businesses (Lahore, Karachi, Islamabad, Rawalpindi, Faisalabad, Multan, Peshawar).
- Do NOT edit: `routes/web.php` (coordinator owns), `resources/js/Layouts/AppLayout.vue` (coordinator owns), other modules' files.
- Write your routes as a partial file `routes/<module>.php` using the route names below; coordinator will `require` it from web.php.
- Feature tests: `tests/Feature/<Module>Test.php`. Use `RefreshDatabase`.
- All user-facing strings in English. Comments minimal.

## Route name contract (use exactly these)
- dashboard: `dashboard` → DashboardController@index
- leads: `leads.index/.create/.store/.show/.edit/.update/.destroy`, `leads.import` (GET form + POST), `leads.sync-sheet` (POST)
- pipeline: `pipeline.index`, `pipeline.move` (POST {lead_id, stage})
- email-templates: `email-templates.index/.create/.store/.edit/.update/.destroy`
- emails: `emails.index` (sent log), `emails.compose` (GET), `emails.send` (POST)
- tracking (PUBLIC, no auth): `track.open` GET /t/o/{token}, `track.click` GET /t/c/{token}
- campaigns: `campaigns.index/.create/.store/.show/.destroy`, `campaigns.launch` (POST)
- inbox: `inbox.index`
- whatsapp: `whatsapp.index`, `whatsapp.send` (POST)
- webhooks (PUBLIC): GET+POST /webhooks/whatsapp (no route name needed, or `webhooks.whatsapp`)
- proposals: `proposals.index/.create/.store/.show/.edit/.update/.destroy`, `proposals.send` (POST)
- proposals public (PUBLIC): `proposals.public` GET /p/{token}, `proposals.accept` POST /p/{token}/accept, `proposals.decline` POST /p/{token}/decline
- services: `services.index/.store/.update/.destroy` (managed inside Settings page or own page)
- settings: `settings.index`, `settings.update` (POST)
- tasks: `tasks.index`, `tasks.store`, `tasks.toggle` (POST), `tasks.destroy`
- notes: `leads.notes.store` POST /leads/{lead}/notes, `leads.notes.destroy`
- automations: `automations.index/.store/.update/.destroy`, `automations.run` (POST)
- reports: `reports.index`

## Database schema (exact — migrations must match)
users: id, name, email unique, password, remember_token, timestamps (Breeze default — do not touch)

leads: id, business_name string, contact_person string nullable, phone string nullable,
  email string nullable, address text nullable, latitude decimal(10,7) nullable,
  longitude decimal(10,7) nullable, website string nullable,
  website_status string default 'none' (none|weak|ok),
  city string nullable, industry string nullable, notes text nullable,
  suggested_service string nullable, stage string default 'new' (new|contacted|replied|proposal|won|lost),
  score integer default 0, source string default 'manual',
  owner_id foreignId nullable → users (nullOnDelete), last_contacted_at timestamp nullable, timestamps

lead_notes: id, lead_id FK cascade, user_id FK cascade, body text, timestamps
tasks: id, lead_id FK nullable cascade, user_id FK cascade (assignee), title string,
  due_date date nullable, done boolean default false, timestamps
email_templates: id, name string, subject string, body text (supports {{business_name}} {{contact_person}} {{city}} {{industry}} {{suggested_service}}), timestamps
email_sends: id, lead_id FK cascade, email_template_id nullable FK nullOnDelete,
  subject string, body text, status string default 'queued' (queued|sending|sent|failed|opened|clicked|replied),
  tracking_token string unique, scheduled_at nullable, sent_at nullable, opened_at nullable,
  clicked_at nullable, opens integer default 0, clicks integer default 0, error text nullable, timestamps
campaigns: id, name string, channel string default 'email' (email|whatsapp),
  email_template_id nullable FK nullOnDelete, whatsapp_template text nullable,
  status string default 'draft' (draft|scheduled|sending|completed), scheduled_at nullable, timestamps
campaign_leads: id, campaign_id FK cascade, lead_id FK cascade, status string default 'pending' (pending|sent|failed),
  sent_at nullable, error text nullable, timestamps, unique(campaign_id, lead_id)
whatsapp_templates: id, name string, language string default 'en', body text, timestamps
whatsapp_messages: id, lead_id nullable FK nullOnDelete, phone string, direction string default 'out' (in|out),
  body text nullable, wa_message_id string nullable, status string default 'queued', timestamps
services: id, name string, description text nullable, price decimal(12,2), currency string default 'PKR',
  active boolean default true, timestamps
proposals: id, lead_id FK cascade, token string unique, title string, status string default 'draft' (draft|sent|accepted|declined),
  subtotal decimal(12,2) default 0, discount decimal(12,2) default 0, total decimal(12,2) default 0,
  valid_until date nullable, notes text nullable, timestamps
proposal_items: id, proposal_id FK cascade, service_id nullable FK nullOnDelete, name string,
  qty integer default 1, price decimal(12,2), timestamps
automations: id, name string, trigger string (no_reply_days|lead_created|stage_changed),
  action string (create_task|send_email|change_stage), config json nullable, active boolean default true,
  last_run_at nullable, timestamps
settings: id, key string unique, value text nullable, timestamps

## Model relationships
Lead: belongsTo owner (User), hasMany notes (LeadNote), hasMany tasks, hasMany emailSends, hasMany whatsappMessages, hasMany proposals
User: hasMany leads (owner), hasMany tasks, hasMany notes
Campaign: belongsTo template (EmailTemplate), belongsToMany leads via campaign_leads (with pivot status)
Proposal: belongsTo lead, hasMany items (ProposalItem)
EmailSend: belongsTo lead, belongsTo template

## Env keys your code must read (with graceful fallbacks)
GOOGLE_SHEETS_ID, GOOGLE_CREDENTIALS_JSON (or GOOGLE_CREDENTIALS_PATH), WHATSAPP_TOKEN, WHATSAPP_PHONE_ID,
WHATSAPP_VERIFY_TOKEN, MAIL_* (standard). Never crash when missing — show clear "not configured" message / stub mode.
