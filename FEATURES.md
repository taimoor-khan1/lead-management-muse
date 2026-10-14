# LeadFlow CRM — Feature Checklist

Legend: **[WORKING]** = fully functional in this build · **[NEEDS-KEYS]** = code complete, needs Taimoor's real credentials to go live.

## Core
- [WORKING] Auth — login / register / logout / profile (Laravel Breeze, Vue/Inertia)
- [WORKING] Dashboard — KPIs (Total / Contacted / Replied / Won), PKR revenue, pipeline summary, recent leads, upcoming tasks, recent emails
- [WORKING] Leads — search + filters (industry / city / website status / stage), create / edit / delete, detail page (all fields incl. lat/lng, Suggested Service), per-lead notes / tasks / email & WhatsApp history / proposals
- [WORKING] CSV import with duplicate detection
- [NEEDS-KEYS] Google Sheets sync — `GoogleSheetService` + `php artisan app:sync-sheet` + in-app Sync button. Works end-to-end; returns a clear "not configured" message until `GOOGLE_SHEETS_ID` and `GOOGLE_CREDENTIALS_JSON` are set in `.env` (Google Cloud service-account key)
- [WORKING] Pipeline — drag-and-drop kanban (New → Contacted → Replied → Proposal → Won / Lost), stage persists, `last_contacted_at` auto-set

## Outreach
- [WORKING] Email templates — CRUD with `{{business_name}}` `{{contact_person}}` `{{city}}` `{{industry}}` `{{suggested_service}}`, live preview
- [WORKING] Compose & send — now or scheduled (queue), variable replacement, send log with status
- [WORKING] Open / click tracking — pixel + redirect routes (`/t/o/{token}`, `/t/c/{token}`)
- [NEEDS-KEYS] Real SMTP delivery — sending pipeline is complete; dev uses `MAIL_MAILER=log`. Set `MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION` + from address for production sending
- [WORKING] Campaigns — create (email/WhatsApp channel), lead multi-select, launch via queue, per-lead status, stats (sent / failed / opens / clicks)
- [NEEDS-KEYS] WhatsApp sending — `WhatsAppService` complete for the official WhatsApp Business Cloud API; **stub mode** active without keys (logs + fake IDs, clearly badged "STUB MODE" in UI). Needs `WHATSAPP_TOKEN` + `WHATSAPP_PHONE_ID` (Meta developer app, ~free to create)
- [WORKING] WhatsApp webhook — verification + incoming-message receiver (`/webhooks/whatsapp`, CSRF-exempt); auto-creates leads from unknown numbers
- [NEEDS-KEYS] WhatsApp webhook going live — needs `WHATSAPP_VERIFY_TOKEN` set and the public app URL registered in the Meta app
- [WORKING] Unified Inbox — emails + WhatsApp threads, mark-as-replied

## Sales
- [WORKING] Services & pricing catalog (PKR) — CRUD in Settings
- [WORKING] Proposal builder — catalog items, qty/price overrides, discount, totals, valid-until
- [WORKING] Shareable proposal link — public `/p/{token}` page (no login), Accept → lead goes to Won, Decline (+ reason)
- [WORKING] Tasks — create / toggle / delete, overdue + due-date views, per-lead tasks, dashboard reminders
- [WORKING] Automation — rules (`no_reply_days` / `lead_created` / `stage_changed` → create_task / send_email / change_stage), CRUD UI, `app:evaluate-automations` command, model observer
- [WORKING] Reports — funnel, per-industry counts, campaign stats, proposal revenue (PKR)
- [WORKING] Settings — business profile, services, integrations (masked secrets, blank never wipes)

## What Taimoor needs to provide (when ready to go live)
1. **Google** — a service-account JSON key + the Sheet ID → paste into Settings (or `.env`)
2. **WhatsApp** — Meta developer app: phone number ID + permanent access token + verify token
3. **Email** — SMTP credentials (Gmail app password or any SMTP provider)

Everything else runs as-is today.
