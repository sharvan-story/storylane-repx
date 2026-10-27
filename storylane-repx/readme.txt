=== Storylane RepX: AI SDR & AI Sales Agent for Your Website ===
Contributors: storylane
Tags: ai-sdr, ai-bdr, ai-sales-agent, ai-chatbot, lead-generation
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

RepX is an AI sales agent for your website. It works as your AI SDR and AI BDR, answering buyer questions, qualifying leads and booking demos.

== Description ==

RepX by Storylane is an AI sales agent that lives on your website. It talks to your visitors and answers their questions.

RepX works as your AI SDR and your AI BDR. It answers buyer questions, qualifies leads and books demos.

Qualified leads and conversation summaries flow to HubSpot, Salesforce and Slack. Your team sees who is interested and what they asked.

This plugin installs RepX on your WordPress site. You paste your embed code or ID once. The plugin does the rest.

= Who it is for =

* Marketing teams who want more from their website traffic.
* Demand gen teams who need more qualified pipeline.
* Website owners who want a website sales agent without writing code.

= Setup in 3 steps =

1. Copy your RepX embed code from your Storylane account.
2. Go to Settings > RepX in WordPress and paste the code or the ID.
3. Tick "Show RepX on my site" and save.

The plugin saves only the ID. It adds the RepX script to every public page of your site. You can place it in the footer (default) or the header.

== External services ==

This plugin connects to a service provided by Storylane to show the RepX AI agent on your website.

Once you enable the plugin and save a RepX ID, it loads this script on every front-end page of your site:

`https://js.storylane.io/js/v1/repx.js`

The script is needed to display and run the RepX agent for your visitors. The script tag also carries your RepX ID and the value `wordpress`, so Storylane knows which agent to show.

Data sent: when the script loads, Storylane receives the URL of the page being viewed and standard request data from the visitor's browser. When a visitor chats with the agent, Storylane receives what the visitor types into the chat.

The plugin does not load the script in the WordPress admin, and it makes no calls to Storylane from the admin. Nothing is loaded until you enable it.

This service is provided by Storylane: [Terms of Service](https://www.storylane.io/terms-of-service) and [Privacy Policy](https://www.storylane.io/privacy-policy).

== Installation ==

1. Upload the `storylane-repx` folder to `/wp-content/plugins/`, or install it from the Plugins screen in WordPress.
2. Activate the plugin.
3. Go to Settings > RepX.
4. Paste your RepX embed code or ID.
5. Choose footer or header, tick "Show RepX on my site", and save.

== Frequently Asked Questions ==

= Where do I find my embed code? =

Sign in to Storylane, open your RepX settings and find the install or embed section. Copy the script tag. It looks like this: `<script src="https://js.storylane.io/js/v1/repx.js" data-id="your-id" async></script>`. You can paste the whole tag, or just the value of `data-id`.

= Should I use the header or the footer? =

Use the footer. It is the default and works for most sites. Choose the header only if you want RepX to start loading earlier.

= Does it slow down my site? =

The RepX script loads asynchronously. That means it does not block your page from showing.

= Does the plugin store my embed code? =

It stores only the ID, in a single WordPress option. Uninstalling the plugin removes it.

== Screenshots ==

1. The Settings > RepX page where you paste your embed code or ID.
2. RepX answering a visitor's question on a website.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
