<?php
class WooCommerce_Trend_Analyzer {
    /**
     * Holds class instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Option name for the OpenAI API key.
     *
     * @var string
     */
    private $api_key_option = 'wta_openai_api_key';

    /**
     * Option name for selected language.
     *
     * @var string
     */
    private $language_option = 'wta_language';

    /**
     * Returns class instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }



    /**
     * Class constructor. Hooks into WordPress actions.
     */
    private function __construct() {
        add_action( 'admin_menu',                   [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init',                   [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_wta_analyze_trends',   [ $this, 'analyze_trends' ] );
        add_action( 'wp_ajax_wta_get_analysis_data',[ $this, 'get_analysis_data' ] );
        add_action( 'admin_enqueue_scripts',        [ $this, 'enqueue_scripts' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Trend Analyzer', 'woocommerce-trend-analyzer' ),
            __( 'Trend Analyzer', 'woocommerce-trend-analyzer' ),
            'manage_options',
            'trend-analyzer',
            [ $this, 'render_dashboard_page' ],
            'dashicons-chart-line',
            25
        );
    }

    public function register_settings() {
        register_setting( 'wta_settings_group', $this->api_key_option, [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
            'default'           => '',
        ] );
        add_settings_section(
            'wta_settings_section',
            __( 'Configuración de OpenAI', 'woocommerce-trend-analyzer' ),
            null,
            'trend-analyzer'
        );
        add_settings_field(
            'wta_api_key',
            __( 'Clave de API de OpenAI', 'woocommerce-trend-analyzer' ),
            [ $this, 'render_api_key_field' ],
            'trend-analyzer',
            'wta_settings_section'
        );
    }

    public function sanitize_settings( $input ) {
        return sanitize_text_field( $input );
    }

    public function render_api_key_field() {
        $api_key     = get_option( $this->api_key_option );
        $placeholder = $api_key
            ? __( 'Clave API configurada', 'woocommerce-trend-analyzer' )
            : __( 'Introduce tu clave API de OpenAI', 'woocommerce-trend-analyzer' );

        echo '<input type="password" name="' . esc_attr( $this->api_key_option ) . '" value="" size="50" placeholder="' . esc_attr( $placeholder ) . '" id="wta-api-key-field" />';
        echo ' <button type="button" class="button" id="wta-clear-api-key-button">' . esc_html__( 'Clear', 'woocommerce-trend-analyzer' ) . '</button>';
        if ( $api_key ) {
            echo '<p>' . esc_html__( 'La clave API está configurada. Ingresa una nueva clave para ', 'woocommerce-trend-analyzer' ) . '<strong>' . esc_html__( 'actualizarla', 'woocommerce-trend-analyzer' ) . '</strong> ' . esc_html__( 'o pulsa "Clear" para eliminarla.', 'woocommerce-trend-analyzer' ) . '</p>';
        } else {
            echo '<p>' . esc_html__( 'No hay ninguna clave API configurada actualmente. Ingresa tu clave API para configurarla.', 'woocommerce-trend-analyzer' ) . '</p>';
        }
        // Modal Clear
        echo '
        <div id="wta-clear-api-key-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:9999;">
          <div style="background:#fff;margin:10% auto;padding:20px;border-radius:4px;width:320px;text-align:center;">
            <p>' . esc_html__( '¿Estás seguro de que deseas eliminar la clave API?', 'woocommerce-trend-analyzer' ) . '</p>
            <button id="wta-confirm-clear" class="button button-primary">' . esc_html__( 'Sí', 'woocommerce-trend-analyzer' ) . '</button>
            <button id="wta-cancel-clear"  class="button">' . esc_html__( 'No', 'woocommerce-trend-analyzer' ) . '</button>
          </div>
        </div>
        <script>
        (function(){
          var btn    = document.getElementById("wta-clear-api-key-button");
          var modal  = document.getElementById("wta-clear-api-key-modal");
          var confirm= document.getElementById("wta-confirm-clear");
          var cancel = document.getElementById("wta-cancel-clear");
          btn.addEventListener("click", function(){ modal.style.display="block"; });
          cancel.addEventListener("click", function(){ modal.style.display="none"; });
          confirm.addEventListener("click", function(){
            document.getElementById("wta-api-key-field").value="";
            modal.style.display="none";
            var save = document.getElementById("submit");
            if(save) save.click();
          });
          window.addEventListener("click", function(e){
            if(e.target===modal) modal.style.display="none";
          });
        })();
        </script>
        ';
    }

    public function render_dashboard_page() {
        $api_key           = get_option( $this->api_key_option );
        $connection_status = $api_key ? __( 'Conectado', 'woocommerce-trend-analyzer' ) : __( 'No conectado', 'woocommerce-trend-analyzer' );
        
        
        
        ?>
        <div class="wrap container-dashboard-analyzer">
          <h1><?php esc_html_e( 'Sales Trend Analyzer – IA', 'woocommerce-trend-analyzer' ); ?></h1>
          <details>
            <summary>
              <?php esc_html_e( 'Configuración', 'woocommerce-trend-analyzer' ); ?>
              <?php if ( $connection_status === 'Conectado' ): ?>
                <span class="conection-ok"><?php echo esc_html( $connection_status ); ?></span>
              <?php else: ?>
                <span class="conection-error"><?php echo esc_html( $connection_status ); ?></span>
              <?php endif; ?>
            </summary>
            <form method="post" action="options.php">
              <?php
                settings_fields( 'wta_settings_group' );
                do_settings_sections( 'trend-analyzer' );
                submit_button();
              ?>
            </form>
          </details>
          <hr>
          <div style="padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
            <h2><?php esc_html_e( 'Dashboard', 'woocommerce-trend-analyzer' ); ?></h2>
            <p><?php esc_html_e( 'Obtén análisis detallados y personalizados sobre las tendencias actuales en tu tienda', 'woocommerce-trend-analyzer' ); ?></p>
            <div class="button-container">
              <form id="wta-lang-form" class="wta-language-select" method="get">
                <input type="hidden" name="page" value="trend-analyzer" />
                <?php $sel = get_option( $this->language_option, 'es_ES' ); ?>
                <select id="wta-lang-select" name="wta_lang">
                  <option value="es_ES" <?php selected( $sel, 'es_ES' ); ?>><?php esc_html_e( 'Español', 'woocommerce-trend-analyzer' ); ?></option>
                  <option value="en_US" <?php selected( $sel, 'en_US' ); ?>><?php esc_html_e( 'English', 'woocommerce-trend-analyzer' ); ?></option>
                </select>
              </form>
              <button id="wta-analyze-trends-button" class="button button-primary"><?php esc_html_e( 'Analizar Tendencias', 'woocommerce-trend-analyzer' ); ?></button>
              <a class="obtener-pro-version" href="#"><?php esc_html_e( 'Obtener Pro Version', 'woocommerce-trend-analyzer' ); ?></a>
            </div>
            <div id="wta-analysis-result" style="margin-top:20px;"></div>
          </div>
        </div>
        <?php
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'toplevel_page_trend-analyzer' ) {
            return;
        }
        wp_enqueue_script( 'chart-js', WTA_PLUGIN_URL . 'assets/js/Chart.min.js', [], '3.9.1', true );
        wp_enqueue_script(
            'wta-admin-js',
            WTA_PLUGIN_URL . 'assets/js/analyzer-admin.js',
            [ 'jquery', 'chart-js' ],
            WTA_VERSION,
            true
        );
        wp_enqueue_style(
            'wta-admin-css',
            WTA_PLUGIN_URL . 'assets/css/analyzer-admin.css',
            [],
            '1.0'
        );
        wp_localize_script( 'wta-admin-js', 'wta_ajax_object', [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wta_analyze_trends_nonce' ),
            'get_nonce' => wp_create_nonce( 'wta_get_analysis_data_nonce' ),
        ] );
        wp_localize_script( 'wta-admin-js', 'wta_i18n', [
            'lang'    => get_option( $this->language_option, 'es_ES' ),
            'strings' => $this->get_js_strings(),
        ] );
    }

    private function get_js_strings() {
        return [
            'click_analyze'   => __( 'Haz clic en "Analizar Tendencias"', 'woocommerce-trend-analyzer' ),
            'error_fetch'     => __( 'Error al obtener datos.', 'woocommerce-trend-analyzer' ),
            'error_analysis'  => __( 'Error al analizar tendencias.', 'woocommerce-trend-analyzer' ),
            'total_sales'     => __( 'Ventas Totales', 'woocommerce-trend-analyzer' ),
            'products_sold'   => __( 'Productos Vendidos', 'woocommerce-trend-analyzer' ),
            'customers'       => __( 'Clientes', 'woocommerce-trend-analyzer' ),
            'need_7_days'     => __( 'Se necesitan 7 días de datos nuevos para analizar tendencias…', 'woocommerce-trend-analyzer' ),
            'last_analysis'   => __( 'Último Análisis: hace %s', 'woocommerce-trend-analyzer' ),
            'tab_top'         => __( 'Más Vendidos', 'woocommerce-trend-analyzer' ),
            'tab_least'       => __( 'Menos Vendidos', 'woocommerce-trend-analyzer' ),
            'tab_trend'       => __( 'Tendencia Actual', 'woocommerce-trend-analyzer' ),
            'tab_suggestions' => __( 'Sugerencias', 'woocommerce-trend-analyzer' ),
            'tab_geo'         => __( 'Distribución Geográfica', 'woocommerce-trend-analyzer' ),
            'tab_comp'        => __( 'Analisis de Competidores <span class="badge--soon">Pro</span>', 'woocommerce-trend-analyzer' ),
            'tab_forecast'    => __( 'Pronóstico de Ventas<span class="badge--soon">Pro</span>', 'woocommerce-trend-analyzer' ),
            'top_products'    => __( 'Productos Más Vendidos', 'woocommerce-trend-analyzer' ),
            'least_products'  => __( 'Productos Menos Vendidos', 'woocommerce-trend-analyzer' ),
            'table_name'      => __( 'Nombre', 'woocommerce-trend-analyzer' ),
            'table_qty'       => __( 'Cantidad', 'woocommerce-trend-analyzer' ),
            'table_rev'       => __( 'Ingresos', 'woocommerce-trend-analyzer' ),
            'table_avg'       => __( 'Precio Prom.', 'woocommerce-trend-analyzer' ),
            'customer_analysis'=> __( 'Análisis De Clientes Por Producto', 'woocommerce-trend-analyzer' ),
            'product_analysis'=> __( 'Análisis de %s', 'woocommerce-trend-analyzer' ),
            'location_analysis'=> __( 'Análisis por Ubicación', 'woocommerce-trend-analyzer' ),
            'other_trends'    => __( 'Otras Tendencias', 'woocommerce-trend-analyzer' ),
            'no_top_data'     => __( 'No hay datos de productos más vendidos.', 'woocommerce-trend-analyzer' ),
            'no_least_data'   => __( 'No hay datos de productos menos vendidos.', 'woocommerce-trend-analyzer' ),
            'trends_title'    => __( 'Tendencias', 'woocommerce-trend-analyzer' ),
            'no_trends'       => __( 'No se identificaron tendencias.', 'woocommerce-trend-analyzer' ),
            'suggestions_intro'=> __( 'A continuación, encontrarás recomendaciones:', 'woocommerce-trend-analyzer' ),
            'no_suggestions'  => __( 'No hay sugerencias disponibles.', 'woocommerce-trend-analyzer' ),
            'geo_sales_title' => __( 'Distribución Geográfica de Ventas', 'woocommerce-trend-analyzer' ),
            'sales_label'     => __( 'Ventas', 'woocommerce-trend-analyzer' ),
            'no_geo_data'     => __( 'No hay datos geográficos disponibles.', 'woocommerce-trend-analyzer' ),
            'day'             => __( 'día', 'woocommerce-trend-analyzer' ),
            'hour'            => __( 'hora', 'woocommerce-trend-analyzer' ),
            'minute'          => __( 'minuto', 'woocommerce-trend-analyzer' ),
            'few_seconds'     => __( 'unos segundos', 'woocommerce-trend-analyzer' ),
        ];
    }


public function analyze_trends() {
    check_ajax_referer( 'wta_analyze_trends_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Permisos insuficientes.', 'woocommerce-trend-analyzer' ) );
    }
    
    $totalQuantityAll = 0;
    $totalRevenueAll  = 0;
    // Clientes únicos
    $customerEmails = [];


    // 1) Recolectar pedidos completados
    $orders = wc_get_orders([
        'limit'  => -1,
        'status' => 'completed',
        'type'   => 'shop_order',
    ]);
    if ( empty( $orders ) ) {
        wp_send_json_error( __( 'No se encontraron pedidos.', 'woocommerce-trend-analyzer' ) );
    }

    // 2) Inicializar estructuras
    $products              = []; // stats por producto
    $product_city_sales    = []; // [product_id][city] => qty
    $global_city_sales     = []; // [city] => qty

    // 3) Recorrer pedidos e ítems
    foreach ( $orders as $order ) {
        
        $city = $order->get_billing_city() ?: __( 'Sin ciudad', 'woocommerce-trend-analyzer' );

        foreach ( $order->get_items() as $item ) {
            if ( 'line_item' !== $item->get_type() ) {
                continue;
            }

            $pid      = $item->get_product_id();
            $qty      = intval( $item->get_quantity() );
            $product  = $item->get_product();

            // Precio unitario fiable
            if ( $product && '' !== $product->get_price() ) {
                $unit_price = floatval( $product->get_price() );
            } else {
                $unit_price = floatval( $item->get_total() ) / max(1, $qty);
            }
            $line_total = $unit_price * $qty;

            // Inicializar producto
            if ( ! isset( $products[ $pid ] ) ) {
                $products[ $pid ] = [
                    'name'     => $item->get_name(),
                    'quantity' => 0,
                    'total'    => 0,
                ];
            }

            // Sumar stats
            $products[ $pid ]['quantity'] += $qty;
            $products[ $pid ]['total']    += $line_total;

            // Ventas por ciudad (producto)
            if ( ! isset( $product_city_sales[ $pid ][ $city ] ) ) {
                $product_city_sales[ $pid ][ $city ] = 0;
            }
            $product_city_sales[ $pid ][ $city ] += $qty;

            // Ventas globales por ciudad
            if ( ! isset( $global_city_sales[ $city ] ) ) {
                $global_city_sales[ $city ] = 0;
            }
            $global_city_sales[ $city ] += $qty;
        }
    }

    // 4) Preparar input para OpenAI (sin distribución)
    $ai_input = [];
    foreach ( $products as $pid => $d ) {
        $ai_input[] = [
            'name'          => $d['name'],
            'quantity_sold' => $d['quantity'],
            'total_revenue' => $d['total'],
            'average_price'=> $d['quantity'] ? ( $d['total'] / $d['quantity'] ) : 0,
        ];
    }
    $ai_json = wp_json_encode( $ai_input );

    // 5) Prompt para OpenAI adaptado al idioma seleccionado
    $lang       = get_option( $this->language_option, 'es_ES' );
    $prompt = '';
    if ( 'en_US' === $lang ) {
        $prompt = <<<EOT
You have this list of products with their sales:

$ai_json

Return ONLY a JSON in English with this EXACT structure:

{
  "top_selling_products": [ /* up to 10 */ ],
  "least_selling_products": [ /* at least 2 */ ],
  "trends": "identify at least 2 short trends",
  "sugerencias": ["…","…","…"],
  "location_distribution": [ /* ignore this, we will compute in PHP */ ]
}

For each product in top/least, add a `customer_analysis.other_trends` field with brief text. Nothing more.
EOT;
    } else {
        $prompt = <<<EOT
Tienes este listado de productos con sus ventas:

$ai_json

Devuélveme solo un JSON en español con esta estructura EXACTA:

{
  "top_selling_products": [ /* hasta 10 */ ],
  "least_selling_products": [ /* al menos 2 */ ],
  "trends": "identifica al menos 2 tendencias breves",
  "sugerencias": ["…","…","…"],
  "location_distribution": [ /* ignore esto, lo calcularemos en PHP */ ]
}

En cada producto de top/least, añade un campo `customer_analysis.other_trends` con texto breve. Nada más.
EOT;
    }

    // 6) Llamada a OpenAI
    $api_key = get_option( $this->api_key_option );
    if ( ! $api_key ) {
        wp_send_json_error( __( 'Clave API no configurada.', 'woocommerce-trend-analyzer' ) );
    }
    $resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => wp_json_encode([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => ( 'en_US' === $lang )
                        ? 'You are an assistant that only responds with JSON.'
                        : 'Eres un asistente que responde SOLO JSON.',
                ],
                [ 'role'=>'user',  'content'=>$prompt ],
            ],
            'temperature'=>0.0,
        ]),
        'timeout' => 60,
    ]);
    if ( is_wp_error( $resp ) ) {
        wp_send_json_error( $resp->get_error_message() );
    }
    $code    = wp_remote_retrieve_response_code( $resp );
    $body    = wp_remote_retrieve_body( $resp );
    if ( 200 !== $code ) {
        wp_send_json_error( sprintf( __( 'Error API OpenAI: %s', 'woocommerce-trend-analyzer' ), $body ) );
    }

    // 7) Parsear respuesta
    $api_data = json_decode( $body, true );
    $content  = $api_data['choices'][0]['message']['content'] ?? '';
    // Extraer JSON si viene entre fences
    if ( preg_match( '/```json\s*(\{.*\})\s*```/s', $content, $m ) ) {
        $json = $m[1];
    } else {
        $json = $content;
    }
    $result = json_decode( $json, true );
    if ( json_last_error() ) {
        wp_send_json_error( sprintf( __( 'JSON inválido: %s', 'woocommerce-trend-analyzer' ), json_last_error_msg() ) );
    }

    // 8) Inyectar distribución correcta en PHP
    // Global
    $global = [];
    foreach ( $global_city_sales as $city => $sales ) {
        $global[] = [ 'city'=> $city, 'sales'=> $sales ];
    }
    $result['location_distribution'] = $global;

    // Por producto
    foreach ( [ 'top_selling_products', 'least_selling_products' ] as $key ) {
        if ( ! empty( $result[ $key ] ) && is_array( $result[ $key ] ) ) {
            foreach ( $result[ $key ] as &$prod ) {
                // encontrar pid por nombre
                foreach ( $products as $pid => $d ) {
                    if ( $d['name'] === $prod['name'] ) {
                        $dist = [];
                        if ( isset( $product_city_sales[ $pid ] ) ) {
                            foreach ( $product_city_sales[ $pid ] as $c => $s ) {
                                $dist[] = [ 'city'=> $c, 'sales'=> $s ];
                            }
                        }
                        $prod['customer_analysis']['location_distribution'] = $dist;
                        break;
                    }
                }
            }
            unset( $prod );
        }
    }

   
   foreach ( $orders as $order ) {
    $customerEmails[] = $order->get_billing_email();
}
$totalUniqueCustomers = count( array_unique( array_filter( $customerEmails ) ) );

// Sumamos cantidad y revenue
foreach ( $products as $d ) {
    $totalQuantityAll += $d['quantity'];
    $totalRevenueAll  += $d['total'];
}

// Lo guardamos en el resultado
$result['totals'] = [
    'revenue'       => round( $totalRevenueAll, 2 ),
    'quantity_sold' => $totalQuantityAll,
    'customers'     => $totalUniqueCustomers,
];




    // 9) Guardar y devolver
    update_option( 'wta_analysis_data', [
        'data'      => $result,
        'timestamp' => time(),
    ], false );
    
    wp_send_json_success( get_option( 'wta_analysis_data' ) );
}




    public function get_analysis_data() {
        check_ajax_referer( 'wta_get_analysis_data_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permisos insuficientes.', 'woocommerce-trend-analyzer' ) );
        }
        $stored = get_option( 'wta_analysis_data' );
        if ( $stored ) {
            wp_send_json_success( $stored );
        } else {
            wp_send_json_error( __( 'No hay datos de análisis disponibles.', 'woocommerce-trend-analyzer' ) );
        }
    }
}
