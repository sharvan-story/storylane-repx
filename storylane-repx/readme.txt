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

This plugin installs RepX on your WordPress site. You paste your RepX script once. The plugin does the rest.

= Who it is for =

* Marketing teams who want more from their website traffic.
* Demand gen teams who need more qualified pipeline.
* Website owners who want a website sales agent without writing code.

= Setup in 3 steps =

1. Sign up for RepX and create your agent in Storylane.
2. Copy your RepX script from your Storylane account.
3. Go to Settings > RepX in WordPress, paste the script and click Save changes. RepX is now live on your site.

The plugin adds the RepX script to every public page of your site.

== External services ==

This plugin connects to a service provided by Storylane to show the RepX AI agent on your website.

Once you save your RepX script, the plugin loads this script on every front-end page of your site:

`https://js.storylane.io/js/v1/repx.js`

The script is needed to display and run the RepX agent for your visitors. The script tag also carries your RepX ID and the value `wordpress`, so Storylane knows which agent to show.

Data sent: when the script loads, Storylane receives the URL of the page being viewed and standard request data from the visitor's browser. When a visitor chats with the agent, Storylane receives what the visitor types into the chat.

The plugin does not load the script in the WordPress admin, and it makes no calls to Storylane from the admin. Nothing is loaded until you enable it.

This service is provided by Storylane: [Terms of Service](https://www.storylane.io/terms-of-service) and [Privacy Policy](https://www.storylane.io/privacy-policy).

== Installation ==

1. Upload the `storylane-repx` folder to `/wp-content/plugins/`, or install it from the Plugins screen in WordPress.
2. Activate the plugin.
3. Go to Settings > RepX.
4. Paste your RepX script and click Save changes.
5. To pause RepX later, click Turn off. Your script is kept.

== Frequently Asked Questions ==

= Where do I find my RepX script? =

Sign in to Storylane and open your RepX settings. Copy the RepX script. It looks like this: `<script src="https://js.storylane.io/js/v1/repx.js" data-id="your-id" async></script>`.

= Does it slow down my site? =

The RepX script loads asynchronously. That means it does not block your page from showing.

= Where does the plugin keep my settings? =

In a single WordPress option. Uninstalling the plugin removes it.

== Screenshots ==

1. The Settings > RepX page where you paste your RepX script.
2. RepX answering a visitor's question on a website.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
