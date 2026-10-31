# EMAIL.md — Email Feature Implementation (Brevo API)

> Part of the project's agent rules. Read `AGENTS.md` first. This document defines how every email in the Subscription Management System must be built. Follow it for any feature that sends email.

---

## 1. Purpose and Scope

Emails this system sends are **transactional** (one-to-one, triggered by an action), never marketing newsletters.

| Priority | Email type | Trigger |
|---|---|---|
| High | Password reset | Customer requests a reset |
| High | Staff invitation / account created | Admin adds a staff member |
| Normal | Invoice sent | Invoice is confirmed/sent |
| Normal | Payment receipt | Payment recorded against an invoice |
| Normal | Subscription confirmed | Subscription becomes Confirmed/Active |
| Low | Renewal reminder | Subscription nearing expiry |
| Low | Overdue notice | Invoice passes its due date unpaid |

---

## 2. Provider Setup (Brevo)

1. Create a Brevo account (free plan; no credit card is required per Brevo).
2. Verify the sender address. Where possible, also authenticate your own domain with **SPF** and **DKIM** DNS records. Without this, emails often land in spam.
3. Create an API key under **SMTP & API → API Keys**.
4. Create one email template per email type in the Brevo dashboard and note each `templateId`.

### Environment variables

Add all of these to `.env.example` with placeholder values. Never commit real values or expose them to the frontend.

```
EMAIL_PROVIDER=brevo            # brevo | smtp (smtp = Mailpit in development)
BREVO_API_KEY=your-api-key-here
EMAIL_FROM_ADDRESS=billing@yourdomain.com
EMAIL_FROM_NAME=Brightloop Billing
EMAIL_DAILY_LIMIT=250           # stay below the provider's daily cap
BREVO_WEBHOOK_SECRET=long-random-string
BREVO_TEMPLATE_PASSWORD_RESET=1
BREVO_TEMPLATE_INVOICE_SENT=2
BREVO_TEMPLATE_PAYMENT_RECEIPT=3
# ...one per email type
```

---

## 3. Architecture

Build four parts. **Brevo-specific code lives in exactly one of them.**

1. **`EmailService` (interface):** exposes `send(type, recipient, data)`. The rest of the application only ever calls this.
2. **`BrevoProvider`:** the only code that knows Brevo's request format. A `SmtpProvider` (pointing at Mailpit) is used in development, selected by `EMAIL_PROVIDER`.
3. **Email queue (pg-boss):** the application never sends directly. It enqueues a job and a worker sends it, with retries.
4. **`email_log` table:** one row per email.

### Suggested folder layout

```
src/services/email/
├── EmailService.ts        # interface + orchestration
├── providers/
│   ├── BrevoProvider.ts
│   └── SmtpProvider.ts    # Mailpit (dev)
├── queue/
│   └── emailWorker.ts
├── templates.ts           # email type → templateId + required params
└── webhook.ts             # Brevo event handler
```

### `email_log` table

| Column | Notes |
|---|---|
| `id` | Primary key |
| `dedupe_key` | **Unique.** e.g. `invoice-sent:{invoiceId}` |
| `type` | Email type from Section 1 |
| `recipient` | Email address |
| `status` | `queued`, `sent`, `delivered`, `bounced`, `failed` |
| `provider_message_id` | Returned by Brevo on send |
| `attempts` | Retry count |
| `error` | Last error message, if any |
| `created_at`, `updated_at` | Timestamps |

---

## 4. Sending via the Brevo API

- Endpoint: `POST https://api.brevo.com/v3/smtp/email`
- Authentication: `api-key` header.
- Use `templateId` with `params` for content. When a template is used, its sender and subject apply unless overridden.
- Attachments (e.g. invoice PDF) can be a URL or base64 content. The app generates the PDF and attaches it as base64.
- Add `tags` (e.g. email type, invoice ID) so webhook events are easy to identify.
- The response contains a `messageId`. **Always save it** to `email_log`.

```ts
// BrevoProvider.send (simplified)
const res = await fetch("https://api.brevo.com/v3/smtp/email", {
  method: "POST",
  headers: {
    "api-key": process.env.BREVO_API_KEY!,
    "content-type": "application/json",
  },
  body: JSON.stringify({
    sender: { email: FROM, name: FROM_NAME },
    to: [{ email, name }],
    templateId,
    params,
    tags: [type, `invoice-${invoiceId}`],
    attachment: pdfBase64
      ? [{ name: "invoice.pdf", content: pdfBase64 }]
      : undefined,
  }),
});
```

For large batches, Brevo supports up to 1,000 personalised message versions in a single request. Use this if billing-day volume requires it.

---

## 5. Queue, Priority, and Daily Limits

- **Never send inline.** Enqueue first; the worker sends.
- Use **separate queues or priorities** so a billing-day batch can never delay password resets or invitations.
- The Brevo free plan allows **300 emails per day**. The worker must:
  - Track emails sent today (UTC or Brevo's reset time; confirm in their docs).
  - Stop normal and low-priority sends at `EMAIL_DAILY_LIMIT` (default 250).
  - Reserve the remaining headroom for high-priority emails.
  - Carry unsent jobs over to the next day instead of failing them.
- Throttle the send rate; do not fire hundreds of requests at once.

---

## 6. Preventing Duplicate Emails

- Every email gets a deterministic `dedupe_key` (e.g. `invoice-sent:{invoiceId}`, `payment-receipt:{paymentId}`).
- `email_log.dedupe_key` has a **unique constraint**. If a job runs twice, the second insert fails and the job is skipped.
- This supports the project rule: nothing billed, or notified, twice.

---

## 7. Retries and Error Handling

| Result | Action |
|---|---|
| Network error, 5xx, 429 | Retry with increasing delay, up to about 5 attempts |
| Other 4xx (bad template ID, invalid address) | Do **not** retry. Mark `failed`, surface to admin |
| Max attempts reached | Mark `failed`, alert admin |

- An email failure must **never roll back** a business action. A confirmed invoice stays confirmed even if its email fails.
- Never log the API key or full email bodies containing sensitive data.
- Provide an admin view of failed and bounced emails with a manual **Resend** button.

---

## 8. Webhooks (Delivery Tracking)

1. In Brevo, create a transactional webhook pointing to `POST /webhooks/brevo`.
2. Subscribe to at least: delivered, soft bounce, hard bounce, invalid email, blocked, complaint, unsubscribed, error. (Opened/clicked are optional.)
3. The handler:
   - Looks up `email_log` by `provider_message_id` and updates `status`.
   - On **hard bounce, invalid email, or complaint**: flag the customer's address as bad, stop emailing it, and notify staff.
4. Security: protect the endpoint with a long random secret (in the URL path or a header, compared to `BREVO_WEBHOOK_SECRET`). **Check Brevo's current docs for signature verification support** and use it if available.
5. Respond with `200` quickly; do heavy work after responding.
6. Webhooks need a **publicly reachable URL**. In local development, use a tunnelling tool, or skip webhooks and rely on Mailpit.

---

## 9. Feature-Specific Rules

### Password reset
- Generate a random token. Store **only its hash**.
- Expire in 15–60 minutes. Make it **single-use**.
- Respond identically whether or not the email is registered, so accounts cannot be discovered.
- Rate-limit reset requests per email and per IP.
- Always send through the **high-priority** queue.

### Invoice email
- Triggered when an invoice is confirmed/sent, not when drafted.
- Attach the PDF; include invoice number, total, and due date in `params`.
- `dedupe_key`: `invoice-sent:{invoiceId}`. A manual resend uses a new key with a counter.

### Payment receipt
- Triggered when a payment is recorded. `dedupe_key`: `payment-receipt:{paymentId}`.

### Overdue notice
- Sent by a scheduled job. Use a key like `overdue:{invoiceId}:{reminderNumber}` so each reminder goes out once.

---

## 10. Validation

- Validate recipient addresses and template params with the shared Zod schemas **before** enqueueing.
- Reject missing `templateId` or missing required params at enqueue time, not at send time.
- Normalise email addresses (trim, lowercase) before storing or comparing.

---

## 11. Testing

- **Unit tests:** `EmailService` with a fake provider; template parameter validation; daily-limit logic; dedupe behaviour.
- **Integration tests:** queue → worker → provider with a mocked Brevo HTTP response (success, 429, 5xx, 4xx).
- **Webhook tests:** each event type updates `email_log` correctly; invalid secret is rejected.
- **Local manual testing:** run Mailpit and set `EMAIL_PROVIDER=smtp`. No real emails are sent.
- Never run automated tests against the real Brevo account.

---

## 12. Known Limits and Production Notes

- **Brevo's free plan adds a Brevo footer to every email** and caps sending at 300/day. Treat the free tier as **development and demo only**.
- Free tiers use shared sending infrastructure and have no uptime guarantee, so deliverability can vary.
- Before a real launch, move to a paid plan or switch provider. Because all Brevo code lives in `BrevoProvider`, switching must require **only** a new provider class and config changes.
- Do not self-host a mail server for this project. New IPs are distrusted by major inbox providers, and many hosts block the required outgoing port.

---

## 13. Definition of Done for Email Work

- [ ] All sending goes through `EmailService` and the queue (no direct provider calls elsewhere)
- [ ] `dedupe_key` unique constraint in place; duplicates verified impossible
- [ ] Priority separation verified: batch emails cannot block password resets
- [ ] Daily limit logic implemented and tested
- [ ] Retry rules implemented; failures never roll back business actions
- [ ] Webhook endpoint secured and updating `email_log`
- [ ] Bounced/invalid addresses flagged and not emailed again
- [ ] `.env.example` updated; no secrets committed
- [ ] Tests passing; Mailpit flow verified locally
- [ ] `docs/DECISIONS.md` and `README.md` updated if anything changed
