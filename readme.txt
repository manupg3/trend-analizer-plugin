=== Sales Trend Analyzer ===
Contributors: manu9912
Tags: woocommerce, analytics, trends, ai, openai
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sales Trend Analyzer analyzes your store data and uses ChatGPT to provide sales trend reports.

== Description ==
Sales Trend Analyzer analyzes your sales statistics and queries the OpenAI API to identify best-selling products, low-performing products, and trend suggestions.

== Installation ==
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Open "Trend Analyzer" in the admin menu and set your OpenAI API key.

== Uninstalling ==
Al desinstalar el plugin se eliminan las opciones `stai_openai_api_key` y `stai_analysis_data`.

== Frequently Asked Questions ==

= Which OpenAI model does it use? =
It currently uses GPT-4o to calculate trends.

= How often can I run an analysis? =
Analyses can be run once every seven days to ensure there is new sales data.

== Privacy ==
The plugin sends order information (product, quantity, city, etc.) to the OpenAI API to generate the trend report.

== External services ==
This plugin connects to the OpenAI service at https://api.openai.com/v1/chat/completions in order to build the trend reports.
It sends product names, quantities sold, revenue totals and billing cities when the administrator runs an analysis.
For details about the service please review OpenAI's [terms of use](https://openai.com/policies/terms-of-use) and [privacy policy](https://openai.com/policies/privacy-policy).

== Changelog ==
= 1.1 =
* Initial plugin release.
