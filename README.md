# Xophz Lead Magnet

> **Category:** Trajectory · **Version:** 0.0.1

Pull leads in with this super-powered magnetic form builder.

## Description

**Lead Magnet** is a form builder and lead capture engine for COMPASS. It provides drag-and-drop form creation with built-in conversion tracking, integrating directly with the Bomb Bag email system and Questbook CRM for seamless lead-to-subscriber pipelines.

### Core Capabilities

- **Form Builder** – Create contact forms, opt-in forms, and survey forms with a visual builder.
- **Lead Capture** – Collect and store submissions with full tracking data.
- **Conversion Tracking** – Monitor form views vs submissions for conversion rate insights.
- **Integration Ready** – Feeds directly into Bomb Bag subscriber lists and Questbook CRM contacts.

## Requirements

- **Xophz COMPASS** parent plugin (active)
- WordPress 5.8+, PHP 7.4+

## Installation

1. Ensure **Xophz COMPASS** is installed and active.
2. Upload `xophz-compass-lead-magnet` to `/wp-content/plugins/`.
3. Activate through the Plugins menu.
4. Access via the My Compass dashboard → **Lead Magnet**.

## Capture form

Put `[lead_magnet_capture offer="annuity-checklist"]` on a landing page. Before it renders, set the seller name and privacy policy URL under **Settings > Lead Magnet**. Submissions go to `POST /wp-json/lead-magnet/v1/capture` and are stored as private Leads (under the COMPASS menu, admins only) with their consent record and landing-URL attribution. Each stored lead fires `xophz_lead_magnet_lead_captured`. Details: [docs/CHANGELOG.md](docs/CHANGELOG.md).

The consent wording is a draft. Have a lawyer approve it before the form goes live.

## Annuity educator page

In the block editor, open the inserter, choose **Patterns > Lead Magnet** and insert **Annuity educator landing page**. It holds a hero, three YouTube video slots (paste a URL into each embed), three "Questions people ask" items, the capture form and a compliance footer. Every bracketed line is an instruction: replace each one with your own text, and have a compliance reviewer approve the answers and the footer before the page goes live. An unset video slot is left off the public page. Details: [docs/CHANGELOG.md](docs/CHANGELOG.md).

## Frontend Routes

| Route | View | Description |
|---|---|---|
| `/lead-magnet` | Dashboard | Form management, submissions, and conversion analytics |

## Changelog

### 0.0.1

- Initial scaffolding with plugin bootstrap and COMPASS integration
