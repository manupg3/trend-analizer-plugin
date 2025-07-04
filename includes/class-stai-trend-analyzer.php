<?php
class STAI_Trend_Analyzer {
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
    private $api_key_option = 'stai_openai_api_key';

    /**
     * Option name for selected language.
     *
     * @var string
     */
    private $language_option = 'stai_language';

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
        add_action( 'wp_ajax_stai_analyze_trends',   [ $this, 'analyze_trends' ] );
        add_action( 'wp_ajax_stai_get_analysis_data',[ $this, 'get_analysis_data' ] );
        add_action( 'admin_enqueue_scripts',        [ $this, 'enqueue_scripts' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Trend Analyzer', 'sales-trend-analyzer-ia' ),
            __( 'Trend Analyzer', 'sales-trend-analyzer-ia' ),
            'manage_options',
            'trend-analyzer',
            [ $this, 'render_dashboard_page' ],
            'dashicons-chart-line',
            25
        );
    }

    public function register_settings() {
        register_setting( 'stai_settings_group', $this->api_key_option, [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
            'default'           => '',
        ] );
        add_settings_section(
            'stai_settings_section',
            __( 'Configuración de OpenAI', 'sales-trend-analyzer-ia' ),
            null,
            'trend-analyzer'
        );
        add_settings_field(
            'stai_api_key',
            __( 'Clave de API de OpenAI', 'sales-trend-analyzer-ia' ),
            [ $this, 'render_api_key_field' ],
            'trend-analyzer',
            'stai_settings_section'
        );
    }

    public function sanitize_settings( $input ) {
        return sanitize_text_field( $input );
    }

    public function render_api_key_field() {
        $api_key     = get_option( $this->api_key_option );
        $placeholder = $api_key
            ? __( 'Clave API configurada', 'sales-trend-analyzer-ia' )
            : __( 'Introduce tu clave API de OpenAI', 'sales-trend-analyzer-ia' );

        echo '<input type="password" name="' . esc_attr( $this->api_key_option ) . '" value="" size="50" placeholder="' . esc_attr( $placeholder ) . '" id="wta-api-key-field" />';
        echo ' <button type="button" class="button" id="wta-clear-api-key-button">' . esc_html__( 'Clear', 'sales-trend-analyzer-ia' ) . '</button>';
        if ( $api_key ) {
            echo '<p>' . esc_html__( 'La clave API está configurada. Ingresa una nueva clave para ', 'sales-trend-analyzer-ia' ) . '<strong>' . esc_html__( 'actualizarla', 'sales-trend-analyzer-ia' ) . '</strong> ' . esc_html__( 'o pulsa "Clear" para eliminarla.', 'sales-trend-analyzer-ia' ) . '</p>';
        } else {
            echo '<p>' . esc_html__( 'No hay ninguna clave API configurada actualmente. Ingresa tu clave API para configurarla.', 'sales-trend-analyzer-ia' ) . '</p>';
        }
        // Modal Clear
        echo '
        <div id="wta-clear-api-key-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:9999;">
          <div style="background:#fff;margin:10% auto;padding:20px;border-radius:4px;width:320px;text-align:center;">
            <p>' . esc_html__( '¿Estás seguro de que deseas eliminar la clave API?', 'sales-trend-analyzer-ia' ) . '</p>
            <button id="wta-confirm-clear" class="button button-primary">' . esc_html__( 'Sí', 'sales-trend-analyzer-ia' ) . '</button>
            <button id="wta-cancel-clear"  class="button">' . esc_html__( 'No', 'sales-trend-analyzer-ia' ) . '</button>
          </div>
        </div>';

        $js = "(function(){\n".
              "var btn=document.getElementById('wta-clear-api-key-button');\n".
              "var modal=document.getElementById('wta-clear-api-key-modal');\n".
              "var confirm=document.getElementById('wta-confirm-clear');\n".
              "var cancel=document.getElementById('wta-cancel-clear');\n".
              "if(btn){btn.addEventListener('click',function(){modal.style.display='block';});}\n".
              "if(cancel){cancel.addEventListener('click',function(){modal.style.display='none';});}\n".
              "if(confirm){confirm.addEventListener('click',function(){document.getElementById('wta-api-key-field').value='';modal.style.display='none';var save=document.getElementById('submit');if(save)save.click();});}\n".
              "window.addEventListener('click',function(e){if(e.target===modal)modal.style.display='none';});\n".
              "})();";
        wp_add_inline_script( 'stai-admin-js', $js );
    }

    public function render_dashboard_page() {
        $api_key           = get_option( $this->api_key_option );
        $connection_status = $api_key ? __( 'Conectado', 'sales-trend-analyzer-ia' ) : __( 'No conectado', 'sales-trend-analyzer-ia' );
        $lang              = get_option( $this->language_option );


        ?>
        <div class="wrap container-dashboard-analyzer">
          <h1><?php esc_html_e( 'Sales Trend Analyzer – IA', 'sales-trend-analyzer-ia' ); ?></h1>
        
        
        
        ?>
        <div class="wrap container-dashboard-analyzer">
          <h1><?php esc_html_e( 'Sales Trend Analyzer – IA', 'sales-trend-analyzer-ia' ); ?></h1>
          <?php if ( ! $lang ) : ?>
            <div class="notice notice-warning"><p><?php esc_html_e( 'Seleccione su idioma para comenzar.', 'sales-trend-analyzer-ia' ); ?></p></div>
          <?php endif; ?>
          <details id="wta-settings">
            <summary>
              <?php esc_html_e( 'Configuración', 'sales-trend-analyzer-ia' ); ?>
              <?php if ( $connection_status === 'Conectado' ): ?>
                <span class="conection-ok"><?php echo esc_html( $connection_status ); ?></span>
              <?php else: ?>
                <span class="conection-error"><?php echo esc_html( $connection_status ); ?></span>
              <?php endif; ?>
            </summary>
            <form method="post" action="options.php">
              <?php
                settings_fields( 'stai_settings_group' );
                do_settings_sections( 'trend-analyzer' );
                submit_button();
              ?>
            </form>
            <?php if ( ! $lang ) : ?>
            <form id="wta-lang-form" class="wta-language-select" method="get" style="margin-top:10px;">
              <input type="hidden" name="page" value="trend-analyzer" />
                  <?php wp_nonce_field( 'stai_set_language', 'stai_lang_nonce' ); ?>
              <input type="hidden" name="setup" value="1" />
              <?php wp_nonce_field( 'stai_set_language', 'stai_lang_nonce' ); ?>
              <select id="wta-lang-select" name="stai_lang">
                <option value="en_US"><?php esc_html_e( 'English', 'sales-trend-analyzer-ia' ); ?></option>
                <option value="es_ES"><?php esc_html_e( 'Español', 'sales-trend-analyzer-ia' ); ?></option>
              </select>
              <?php submit_button( __( 'Confirmar Idioma', 'sales-trend-analyzer-ia' ), 'secondary', 'submit', false ); ?>
            </form>
            <?php endif; ?>
          </details>
          <hr>
          <div style="padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
            <h2><?php esc_html_e( 'Dashboard', 'sales-trend-analyzer-ia' ); ?></h2>
            <p><?php esc_html_e( 'Obtén análisis detallados y personalizados sobre las tendencias actuales en tu tienda', 'sales-trend-analyzer-ia' ); ?></p>
            <div class="button-container">

              <?php $sel = get_option( $this->language_option ); ?>
              <?php if ( ! $sel ) : ?>
                <form id="wta-lang-form" class="wta-language-select" method="get">
                  <input type="hidden" name="page" value="trend-analyzer" />
                  <?php wp_nonce_field( 'stai_set_language', 'stai_lang_nonce' ); ?>
                  <select id="wta-lang-select" name="stai_lang">
                    <option value="es_ES"><?php esc_html_e( 'Español', 'sales-trend-analyzer-ia' ); ?></option>
                    <option value="en_US"><?php esc_html_e( 'English', 'sales-trend-analyzer-ia' ); ?></option>
                  </select>
                  <?php submit_button( __( 'Guardar', 'sales-trend-analyzer-ia' ), 'primary', 'submit', false ); ?>
                </form>
              <?php else : ?>
                <span class="wta-selected-lang">
                  <?php echo ( 'es_ES' === $sel ) ? esc_html__( 'Español', 'sales-trend-analyzer-ia' ) : esc_html__( 'English', 'sales-trend-analyzer-ia' ); ?>
                </span>
              <?php endif; ?>
              <button id="wta-analyze-trends-button" class="button button-primary"><?php esc_html_e( 'Analizar Tendencias', 'sales-trend-analyzer-ia' ); ?></button>
              <a class="obtener-pro-version" href="#" title="<?php esc_attr_e( 'Próximamente', 'sales-trend-analyzer-ia' ); ?>"><?php esc_html_e( 'Obtener Pro Version', 'sales-trend-analyzer-ia' ); ?></a>
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
        wp_enqueue_script( 'chart-js', STAI_PLUGIN_URL . 'assets/js/Chart.min.js', [], '3.9.1', true );
        wp_enqueue_script(
            'stai-admin-js',
            STAI_PLUGIN_URL . 'assets/js/analyzer-admin.js',
            [ 'jquery', 'chart-js' ],
            STAI_VERSION,
            true
        );
        wp_enqueue_style(
            'stai-admin-css',
            STAI_PLUGIN_URL . 'assets/css/analyzer-admin.css',
            [],
            '1.0'
        );
        wp_localize_script( 'stai-admin-js', 'stai_ajax_object', [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'stai_analyze_trends_nonce' ),
            'get_nonce' => wp_create_nonce( 'stai_get_analysis_data_nonce' ),
        ] );
        wp_localize_script( 'stai-admin-js', 'stai_i18n', [
            'lang'    => get_option( $this->language_option, 'en_US' ),
            'strings' => $this->get_js_strings(),
        ] );
    }

    private function get_js_strings() {
        return [
            'click_analyze'   => __( 'Haz clic en "Analizar Tendencias"', 'sales-trend-analyzer-ia' ),
            'error_fetch'     => __( 'Error al obtener datos.', 'sales-trend-analyzer-ia' ),
            'error_analysis'  => __( 'Error al analizar tendencias.', 'sales-trend-analyzer-ia' ),
            'total_sales'     => __( 'Ventas Totales', 'sales-trend-analyzer-ia' ),
            'products_sold'   => __( 'Productos Vendidos', 'sales-trend-analyzer-ia' ),
            'customers'       => __( 'Clientes', 'sales-trend-analyzer-ia' ),
            'need_7_days'     => __( 'Se necesitan 7 días de datos nuevos para analizar tendencias…', 'sales-trend-analyzer-ia' ),
		     // translators: %s will be replaced by the time since last analysis, e.g. "2 días" or "3 hours"
            'last_analysis'   => __( 'Último Análisis: hace %s', 'sales-trend-analyzer-ia' ),
            'tab_top'         => __( 'Más Vendidos', 'sales-trend-analyzer-ia' ),
            'tab_least'       => __( 'Menos Vendidos', 'sales-trend-analyzer-ia' ),
            'tab_trend'       => __( 'Tendencia Actual', 'sales-trend-analyzer-ia' ),
            'tab_suggestions' => __( 'Sugerencias', 'sales-trend-analyzer-ia' ),
            'tab_geo'         => __( 'Distribución Geográfica', 'sales-trend-analyzer-ia' ),
            'tab_comp'        => __( 'Analisis de Competidores <span class="badge--soon">Pro</span>', 'sales-trend-analyzer-ia' ),
            'tab_forecast'    => __( 'Pronóstico de Ventas<span class="badge--soon">Pro</span>', 'sales-trend-analyzer-ia' ),
            'top_products'    => __( 'Productos Más Vendidos', 'sales-trend-analyzer-ia' ),
            'least_products'  => __( 'Productos Menos Vendidos', 'sales-trend-analyzer-ia' ),
            'table_name'      => __( 'Nombre', 'sales-trend-analyzer-ia' ),
            'table_qty'       => __( 'Cantidad', 'sales-trend-analyzer-ia' ),
            'table_rev'       => __( 'Ingresos', 'sales-trend-analyzer-ia' ),
            'table_avg'       => __( 'Precio Prom.', 'sales-trend-analyzer-ia' ),
            'customer_analysis'=> __( 'Análisis De Clientes Por Producto', 'sales-trend-analyzer-ia' ),
			  // translators: %s will be replaced by the product name
            'product_analysis'=> __( 'Análisis de %s', 'sales-trend-analyzer-ia' ),
            'location_analysis'=> __( 'Análisis por Ubicación', 'sales-trend-analyzer-ia' ),
            'other_trends'    => __( 'Otras Tendencias', 'sales-trend-analyzer-ia' ),
            'no_top_data'     => __( 'No hay datos de productos más vendidos.', 'sales-trend-analyzer-ia' ),
            'no_least_data'   => __( 'No hay datos de productos menos vendidos.', 'sales-trend-analyzer-ia' ),
            'trends_title'    => __( 'Tendencias', 'sales-trend-analyzer-ia' ),
            'no_trends'       => __( 'No se identificaron tendencias.', 'sales-trend-analyzer-ia' ),
            'suggestions_intro'=> __( 'A continuación, encontrarás recomendaciones:', 'sales-trend-analyzer-ia' ),
            'no_suggestions'  => __( 'No hay sugerencias disponibles.', 'sales-trend-analyzer-ia' ),
            'geo_sales_title' => __( 'Distribución Geográfica de Ventas', 'sales-trend-analyzer-ia' ),
            'sales_label'     => __( 'Ventas', 'sales-trend-analyzer-ia' ),
            'no_geo_data'     => __( 'No hay datos geográficos disponibles.', 'sales-trend-analyzer-ia' ),
            'day'             => __( 'día', 'sales-trend-analyzer-ia' ),
            'hour'            => __( 'hora', 'sales-trend-analyzer-ia' ),
            'minute'          => __( 'minuto', 'sales-trend-analyzer-ia' ),
            'few_seconds'     => __( 'unos segundos', 'sales-trend-analyzer-ia' ),
            'enter_api_key'   => __( 'Ingresar Clave API', 'sales-trend-analyzer-ia' ),
        ];
    }


public function analyze_trends() {
    check_ajax_referer( 'stai_analyze_trends_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Permisos insuficientes.', 'sales-trend-analyzer-ia' ) );
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
        wp_send_json_error( __( 'No se encontraron pedidos.', 'sales-trend-analyzer-ia' ) );
    }

    // 2) Inicializar estructuras
    $products              = []; // stats por producto
    $product_city_sales    = []; // [product_id][city] => qty
    $global_city_sales     = []; // [city] => qty

    // 3) Recorrer pedidos e ítems
    foreach ( $orders as $order ) {
        
        $city = $order->get_billing_city() ?: __( 'Sin ciudad', 'sales-trend-analyzer-ia' );

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
    $lang       = get_option( $this->language_option, 'en_US' );
    $prompt = '';
    if ( 'en_US' === $lang ) {
    $prompt  = "You have this list of products with their sales:\n\n"
             . $ai_json . "\n\n"
             . "Return ONLY a JSON in English with this EXACT structure:\n\n"
             . "{\n"
             . "  \"top_selling_products\": [ /* up to 10 */ ],\n"
             . "  \"least_selling_products\": [ /* at least 2 */ ],\n"
             . "  \"trends\": \"identify at least 2 short trends\",\n"
             . "  \"sugerencias\": [\"…\",\"…\",\"…\"],\n"
             . "  \"location_distribution\": [ /* ignore this, we will compute in PHP */ ]\n"
             . "}\n\n"
             . "For each product in top/least, add a `customer_analysis.other_trends` field with brief text. Nothing more.";
} else {
    $prompt  = "Tienes este listado de productos con sus ventas:\n\n"
             . $ai_json . "\n\n"
             . "Devuélveme solo un JSON en español con esta estructura EXACTA:\n\n"
             . "{\n"
             . "  \"top_selling_products\": [ /* hasta 10 */ ],\n"
             . "  \"least_selling_products\": [ /* al menos 2 */ ],\n"
             . "  \"trends\": \"identifica al menos 2 tendencias breves\",\n"
             . "  \"sugerencias\": [\"…\",\"…\",\"…\"],\n"
             . "  \"location_distribution\": [ /* ignore esto, lo calcularemos en PHP */ ]\n"
             . "}\n\n"
             . "En cada producto de top/least, añade un campo `customer_analysis.other_trends` con texto breve. Nada más.";
}

    // 6) Llamada a OpenAI
    $api_key = get_option( $this->api_key_option );
    if ( ! $api_key ) {
        wp_send_json_error( __( 'Clave API no configurada.', 'sales-trend-analyzer-ia' ) );
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
		// translators: %s will be replaced by the raw OpenAI API response body or error message

        wp_send_json_error( sprintf( __( 'Error API OpenAI: %s', 'sales-trend-analyzer-ia' ), $body ) );
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
	    // translators: %s will be replaced by json_last_error_msg(), e.g. "Syntax error"
	
        wp_send_json_error( sprintf( __( 'JSON inválido: %s', 'sales-trend-analyzer-ia' ), json_last_error_msg() ) );
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
    update_option( 'stai_analysis_data', [
        'data'      => $result,
        'timestamp' => time(),
    ], false );
    
    wp_send_json_success( get_option( 'stai_analysis_data' ) );
}




    public function get_analysis_data() {
        check_ajax_referer( 'stai_get_analysis_data_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permisos insuficientes.', 'sales-trend-analyzer-ia' ) );
        }
        $stored = get_option( 'stai_analysis_data' );
        if ( $stored ) {
            wp_send_json_success( $stored );
        } else {
            wp_send_json_error( __( 'No hay datos de análisis disponibles.', 'sales-trend-analyzer-ia' ) );
        }
    }
}
