# Lead Magnet changelog

## Unreleased

### Added: annuity capture flow (#6030)

- `[lead_magnet_capture offer="annuity-checklist"]` renders an unstyled, accessible form: name, email, phone (optional), state, question (optional), and a separate, unchecked consent checkbox. The form stays hidden until Settings > Lead Magnet has a seller name and a privacy policy URL (the URL falls back to the WordPress privacy page).
- `POST /wp-json/lead-magnet/v1/capture` (public, form nonce `lm_nonce`). Checks in order: configured (503), per-IP limit on `REMOTE_ADDR` (10 an hour, 429), honeypot `website` (answers 201 and stores nothing), field validation (400 with `errors` by field), consent wording unchanged (`consent_version`, 409), per-email limit (3 an hour, 429). Limits are filterable through `xophz_lead_magnet_rate_limits`.
- Phone needs consent; email-only does not. A lead with a phone must check the consent box. A lead without a phone may leave it unchecked and is stored with `consent_given = false` and `contact_channels = ["email"]`.
- Each lead is a private `compass_lead` post. Its meta (`_lm_*`) holds the fields, `consent_given`, the exact `consent_text` shown, `consent_version`, `seller`, `privacy_url`, `contact_channels`, `captured_at` (UTC), `ip`, `user_agent`, `page_url`, and attribution from the landing URL: `utm_source`, `utm_medium`, `utm_campaign`, `fm_thread`, `fm_reply`.
- After a lead is stored, `do_action( 'xophz_lead_magnet_lead_captured', $lead )` fires once. `$lead` is `Lead_Magnet_Leads::get()`: `id` and every field above, with `consent_given` as a bool and `contact_channels` as a list. Relays (such as the Fresh Mints HighLevel relay, #6024) subscribe to it.
- Leads are visible only to `manage_options`. Every post-type capability maps to it and `create_posts` is `do_not_allow`. The type is not in the REST API. The Leads screen under COMPASS has an Export consent records (CSV) button.
- The consent wording is a draft pending lawyer review (#6038).
