=== RelocateWhere ===
Contributors: relocatewhere
Tags: cost of living, kenya, relocation, map, openai
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Help users discover the best places to relocate within Kenya based on AI-generated cost of living data.

== Description ==

RelocateWhere is a cost-of-living discovery tool for Kenya. Users answer a few simple questions about their destination county, household size, and income, then get detailed cost breakdowns for towns within that county.

Features:

* Accordion-style multi-step form
* All 47 Kenya counties with major towns
* OpenAI-powered cost-of-living estimates
* Interactive OpenStreetMap with cost markers
* KES/USD currency toggle
* Mobile responsive (map hidden on mobile)
* Email capture for contributors
* Human contribution system with admin review
* Privacy-first: contributor emails never disclosed
* Google AdSense integration
* Donate button support

== Installation ==

1. Upload the `relocatewhere` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to RelocateWhere > Settings and enter your OpenAI API key
4. Create a page and add the shortcode `[relocatewhere]`
5. Optionally create a Privacy Policy page and link it in settings

== Shortcode ==

Use `[relocatewhere]` on any page or post to display the tool.

== Frequently Asked Questions ==

= What OpenAI model should I use? =
GPT-4o Mini is recommended for cost efficiency. GPT-4o provides more accurate results but costs more per request.

= How is data cached? =
Results are cached for 7 days by default (configurable in settings). This reduces API costs.

= How do human contributions work? =
Users can optionally leave their email. They receive a private link to submit cost data for their area. Admins review submissions before they go live.

== Changelog ==

= 1.0.0 =
* Initial release
