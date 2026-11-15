<p align="center">
  <img src=".wordpress-org/banner-1544x500.png" alt="RapidTextAI: researched articles, written and published for you" width="100%">
</p>

# AI Content Writer & Auto Blogging for WordPress

[![WordPress plugin](https://img.shields.io/wordpress/plugin/v/ai-text-block?label=wordpress.org&color=f2b84b&labelColor=151f39)](https://wordpress.org/plugins/ai-text-block/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/ai-text-block?color=f2b84b&labelColor=151f39)](https://wordpress.org/plugins/ai-text-block/)
[![Rating](https://img.shields.io/wordpress/plugin/stars/ai-text-block?color=f2b84b&labelColor=151f39)](https://wordpress.org/support/plugin/ai-text-block/reviews/)
[![License: GPL v2+](https://img.shields.io/badge/license-GPLv2%2B-3b4cca?labelColor=151f39)](https://www.gnu.org/licenses/gpl-2.0.html)

Generate researched, SEO-ready articles inside WordPress with **RapidTextAI**. It reads the live web, writes a structured article with cited sources, images, categories, tags and an excerpt, checks it, and publishes it on your schedule.

**Free plan: 10 AI-generated articles per month.** Paid plans start at $10/month.

**[Get it on WordPress.org](https://wordpress.org/plugins/ai-text-block/)** · [RapidTextAI](https://rapidtextai.com) · [Web app](https://app.rapidtextai.com) · [Video tour](https://app.rapidtextai.com/v3/plugin-videos/full-tour.mp4)

## Features

| | |
|---|---|
| **Write Article (Pro)** | A researched article with quality score, sources, FAQ, images and embedded tweets, from its own page or from the post editor. |
| **Auto Blogging** | Pro campaigns research each topic and write on a schedule (hourly, twice a day, daily or weekly), saved as drafts or published. Classic campaigns keep working unchanged. |
| **SEO Tools** | Originality check, Fact check, Content refresh (with backup and restore) and Competitor gap. Results are saved so you can reopen them. |
| **Brands** | Save your voice, tone, audience, banned and preferred words and facts once. Fill a brand in from your website address. |
| **Onboard your website** | Connect your site and RapidTextAI researches your brand, competitors and keywords, then drafts your first articles for review. |
| **AI Chatbots** | Unlimited chat widgets with a custom knowledge base, external tool and API calls, full appearance control and shortcode embedding. |
| **AI Campaign Assistant** | A guided chat that reads your site context and helps plan, create or update campaigns. |
| **Editor integrations** | Meta box in the post editor plus AI content blocks for Gutenberg, WP Bakery and Elementor. |
| **Dashboard** | Usage, articles written, SEO runs, recent activity and brand health. |

**Models:** GPT (OpenAI), Claude (Anthropic), Gemini (Google), DeepSeek, Grok (xAI) and GLM (ZhipuAI).

## Installation

**From WordPress.org**

1. In WordPress, go to **Plugins > Add New** and search for "RapidTextAI".
2. Click **Install Now**, then **Activate**.
3. Open **RapidTextAI > Onboard your website** to connect your account.

**From this repository**

1. Download or clone this repo into `wp-content/plugins/ai-text-block/`.
2. Activate **AI Content Writer & Auto Blogging for WordPress by RapidTextAI** under **Plugins**.

Requires WordPress 6.0+ and PHP 7.0+.

## Repository layout

| Path | Purpose |
|---|---|
| `rapidtext-ai-text-block.php` | Main plugin file and bootstrap |
| `rapidtextai-v2-*.php`, `rapidtextai-rest-api.php` | Pro Article, SEO tools and REST endpoints |
| `rapidtextai-onboarding.php` | Onboard your website flow |
| `admin/`, `block/`, `ext/`, `assets/` | Admin UI, editor block, builder extensions, CSS and JS |
| `readme.txt` | WordPress.org plugin page (description, FAQ, changelog) |
| `.wordpress-org/` | WordPress.org assets: banners, icon and screenshots |
| `.github/workflows/` | Deploys `readme.txt` and assets (and releases) to WordPress.org SVN |

## Releasing

Pushing to `main` runs the **Deploy to WordPress.org** workflow. A new `Version` in `rapidtext-ai-text-block.php` is released to trunk and tagged. An unchanged version only updates `readme.txt` and the `.wordpress-org/` assets. See the header of the workflow file for the required secrets and the dry-run switch.

## External services

The plugin sends nothing until you connect your site or use a feature that needs RapidTextAI. Payments, if you choose a paid plan, happen on Stripe's own checkout page. Details are in the [External services](readme.txt) section of `readme.txt`, plus the [Terms](https://app.rapidtextai.com/terms) and [Privacy Policy](https://app.rapidtextai.com/privacy-policy).

## Support

- Questions and bugs: [open an issue](https://github.com/alisaleem252/ai-text-block/issues) or use the [WordPress.org support forum](https://wordpress.org/support/plugin/ai-text-block/).
- Built and maintained by [Ali Saleem](https://alisaleem252.com).

## License

GPLv2 or later. See [the license](https://www.gnu.org/licenses/gpl-2.0.html).
