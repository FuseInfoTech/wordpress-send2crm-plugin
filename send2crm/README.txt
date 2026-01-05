=== Send2CRM ===
Contributors: fuseit
Tags: Send2CRM, Salesforce, Analytics, CRM, Sales
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 
Easily integrate your WordPress site with your CRM through FuseIT’s official Send2CRM Wordpress plugin.  
 
== Description ==

Send2CRM by FuseIT seamlessly integrates your WordPress site with Salesforce CRM. This plugin connects your WordPress site to the Send2CRM service to capture leads, automatically sync form data to Salesforce, and track real-time website activity. Leverage automation and AI-powered insights, real-time customer activity tracking, and dynamic website personalization all from within Salesforce to boost conversion rates and shorten your sales cycle.

**The advantages of Send2CRM in WordPress**
Turn website activity into sales-ready insights, right inside Salesforce. Send2CRM connects your WordPress site directly to your CRM, giving your team the context they need to personalize outreach and close deals faster.
 
Key Benefits:
 
* Real-time website activity tracked directly into Salesforce
 
* Understand visitor intent for warmer, more effective follow-ups
 
* Simple, low-effort way to boost conversions with personalized experiences
 
* Native Salesforce connection—no complex integrations or API maintenance
 
* Reliable, secure, and fully aligned with your existing Salesforce setup
 
== External Services ==

This plugin relies on several external services to function properly. By using this plugin, you acknowledge that data will be transmitted to these services as described below:

**1. Send2CRM and Salesforce CRM**

* **What it does**: Captures website visitor activity, form submissions, and lead data, then syncs this information to your Salesforce CRM. Send2CRM operates as a managed package within your Salesforce org, processing and storing the data within Salesforce.
* **What data is sent**: Visitor page views, form submissions, user interactions, IP addresses, browser information, and any data you configure to be tracked. 
* **When it's sent**: Periodically when a website visitor interacts with your website while the plugin is active.
* **Service providers**: FuseIT and Salesforce
* **Account required**: Yes - You must have both a Salesforce license and the Send2CRM package installed from the Salesforce AppExchange.
* **Terms of Service**: 
  - FuseIT Send2CRM: https://www.fuseit.com/extended-content/terms-conditions/
  - Salesforce: https://www.salesforce.com/company/legal/agreements/
* **Privacy Policy**: 
  - FuseIT: https://www.fuseit.com/privacy-policy/
  - Salesforce: https://www.salesforce.com/company/privacy/

**2. GitHub and jsDelivr CDN** 

* **What it does**: Delivers the version of Send2CRM JavaScript that you select to match your Salesforce package version. This ensures reliable, fast delivery of the correctly verified, versioned assets to your website visitors.
* **What data is sent**: Send2CRM Repository and version information you select.  No tracking or personal data is collected by the plugin through this service. 
* **When it's sent**: By default, when fetching available versions from the settings page. If you select to 'Use Content Delivery Network', the Send2CRM Javascript will be fetched from the CDN by visitors to your website.
* **Terms of Service**: 
  - GitHub, Inc.: https://docs.github.com/en/site-policy/github-terms/github-terms-of-serviceterms-conditions/
  - jsDelivr: https://www.jsdelivr.com/terms
* **Privacy Policy**: 
  - GitHub, Inc.: https://docs.github.com/en/site-policy/privacy-policies/github-privacy-statement
  - jsDelivr: https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net

== Installation ==

# Prerequisites:
A Salesforce org with Send2CRM already installed from the [Salesforce AppExchange](https://appexchange.salesforce.com/appxListingDetail?listingId=95b16078-064f-4006-ad72-6b564c4e2079).
 
# Installation instructions:
1. Install via the WordPress plugin installer or upload the ZIP file
2. Retrieve API key and domain from Send2CRM app in Salesforce
3. Enter API key and domain in the settings: WordPress Admin Console -> Settings -> Send2CRM
4. Select the version of Send2CRM JavaScript that is compatible with your Salesforce Send2CRM package version.

== Changelog ==
 
= 1.0.0 =
* Initial Version