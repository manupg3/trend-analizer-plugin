=== WooCommerce Trend Analyzer ===
Contributors: manupg3
Tags: woocommerce, analytics, trends, ai, openai
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Trend Analyzer analiza los datos de tu tienda y utiliza ChatGPT para ofrecer informes sobre las tendencias de ventas.

== Description ==
WooCommerce Trend Analyzer analiza tus estadísticas de ventas y consulta la API de OpenAI para identificar productos más vendidos, productos con menos ventas y sugerencias de tendencias.

== Installation ==
1. Sube la carpeta del plugin al directorio `/wp-content/plugins/`.
2. Activa el plugin desde el menú "Plugins" en WordPress.
3. Abre "Trend Analyzer" en el menú de administración y configura tu clave API de OpenAI.

== Uninstalling ==
Al desinstalar el plugin se eliminan las opciones `wta_openai_api_key` y `wta_analysis_data`.

== Frequently Asked Questions ==

= ¿Qué modelo de OpenAI utiliza? =
Actualmente se utiliza GPT-4o para calcular las tendencias.

= ¿Con qué frecuencia puedo ejecutar un análisis? =
Los análisis pueden ejecutarse una vez cada siete días para asegurar que haya nuevos datos de ventas.

== Changelog ==
= 1.1 =
* Versión inicial del plugin.
