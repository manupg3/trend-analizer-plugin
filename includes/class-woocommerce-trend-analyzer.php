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
            'Trend Analyzer',
            'Trend Analyzer',
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
            'Configuración de OpenAI',
            null,
            'trend-analyzer'
        );
        add_settings_field(
            'wta_api_key',
            'Clave de API de OpenAI',
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
        $placeholder = $api_key ? 'Clave API configurada' : 'Introduce tu clave API de OpenAI';

        echo '<input type="password" name="' . esc_attr( $this->api_key_option ) . '" value="" size="50" placeholder="' . esc_attr( $placeholder ) . '" id="wta-api-key-field" />';
        echo ' <button type="button" class="button" id="wta-clear-api-key-button">Clear</button>';
        if ( $api_key ) {
            echo '<p>La clave API está configurada. Ingresa una nueva clave para <strong>actualizarla</strong> o pulsa "Clear" para eliminarla.</p>';
        } else {
            echo '<p>No hay ninguna clave API configurada actualmente. Ingresa tu clave API para configurarla.</p>';
        }
        // Modal Clear
        echo '
        <div id="wta-clear-api-key-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:9999;">
          <div style="background:#fff;margin:10% auto;padding:20px;border-radius:4px;width:320px;text-align:center;">
            <p>¿Estás seguro de que deseas eliminar la clave API?</p>
            <button id="wta-confirm-clear" class="button button-primary">Sí</button>
            <button id="wta-cancel-clear"  class="button">No</button>
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
        $connection_status = $api_key ? 'Conectado' : 'No conectado';
        
        
        
        ?>
        <div class="wrap container-dashboard-analyzer">
          <h1>Sales Trend Analyzer – IA</h1>
          <details>
            <summary>
              Configuración
              <?php if ( $connection_status === 'Conectado' ): ?>
                <span class="conection-ok"><?php echo $connection_status; ?></span>
              <?php else: ?>
                <span class="conection-error"><?php echo $connection_status; ?></span>
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
            <h2>Dashboard</h2>
            <p>Obtén análisis detallados y personalizados sobre las tendencias actuales en tu tienda</p>
            <div class="button-container">
              <button id="wta-analyze-trends-button" class="button button-primary">Analizar Tendencias</button>
              <a class="obtener-pro-version" href="#">Obtener Pro Version</a>
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
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true );
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
    }


public function analyze_trends() {
    check_ajax_referer( 'wta_analyze_trends_nonce', 'nonce' );
    
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
        wp_send_json_error( 'No se encontraron pedidos.' );
    }

    // 2) Inicializar estructuras
    $products              = []; // stats por producto
    $product_city_sales    = []; // [product_id][city] => qty
    $global_city_sales     = []; // [city] => qty

    // 3) Recorrer pedidos e ítems
    foreach ( $orders as $order ) {
        
        $city = $order->get_billing_city() ?: 'Sin ciudad';

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

    // 5) Prompt para OpenAI
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

    // 6) Llamada a OpenAI
    $api_key = get_option( $this->api_key_option );
    if ( ! $api_key ) {
        wp_send_json_error( 'Clave API no configurada.' );
    }
    $resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => wp_json_encode([
            'model'    => 'gpt-4o',
            'messages' => [
                [ 'role'=>'system','content'=>'Eres un asistente que responde SOLO JSON.' ],
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
        wp_send_json_error( "Error API OpenAI: $body" );
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
        wp_send_json_error( 'JSON inválido: ' . json_last_error_msg() );
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
        $stored = get_option( 'wta_analysis_data' );
        if ( $stored ) {
            wp_send_json_success( $stored );
        } else {
            wp_send_json_error( 'No hay datos de análisis disponibles.' );
        }
    }
}
