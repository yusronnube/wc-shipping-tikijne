<?php 

    class WC_Shipping_Tikijne extends WC_Shipping_Method
	{	
		public  $jneclass;
		public  $shipping_cost;
	  	public  $shipping_kecamatan;	
		public  $shipping_city;
		public  $popup_message;
		public  $min_allow_fs;
		public  $total_cart;
		public  $is_free_shipping;
		
		public function __construct(){
			$this -> id = 'wc_shipping_tikijne';
			$this -> method_title = __('JNE');
			$this -> method_description = __('Shipping Method using Tiki JNE for Indonesia Marketplace');
			$this -> enabled = 'yes';
			$this -> title = 'JNE';
			$this -> is_free_shipping = false;
			$this -> init();			
		}


		public function create_cek_resi_page(){
                        global $user_ID;

                        $pageckresi = get_page_by_title( 'cekresi','page' );
                        if(!is_null($pageckresi))
                          return;

                        $page['post_type']    = 'page';
                        //$page['post_content'] = 'Put your page content here';
                        $page['post_parent']  = 0;
                        $page['post_author']  = $user_ID;
                        $page['post_status']  = 'publish';
                        $page['post_title']   = 'cekresi';
                        $page = apply_filters('epeken_add_new_page', $page, 'teams');

                    $pageid = wp_insert_post ($page);
                    if ($pageid == 0) { /* Add Page Failed */ }

                }

                public function add_cek_resi_page_to_prim_menu(){
                        $menu_name = 'primary';
                        $locations = get_nav_menu_locations();

			if(!isset($locations) || !is_array($locations))
				return;
		
			if(!array_key_exists($menu_name,$locations))
				return;

                        $menu_id = $locations[ $menu_name ] ;
                        $menu_object = wp_get_nav_menu_object($menu_id);

                        if(!$menu_object){
                                return;
                        }
                        $menu_items = wp_get_nav_menu_items($menu_object->term_id);
                        $is_menu_exist = false;
                        foreach ( (array) $menu_items as $key => $menu_item ) {
                                $post_title = $menu_item->post_title;
                                if ($post_title === "Cek Resi JNE"){
                                        $is_menu_exist = true;
                                        break;
                                }
                        }

                        if($is_menu_exist){
                                return;
                        }

                        $url = get_permalink( get_page_by_title( 'cekresi','page' ) );
                        if($url) {
                        wp_update_nav_menu_item($menu_object->term_id, 0, array(
                                'menu-item-title' =>  __('Cek Resi JNE'),
                                'menu-item-url' =>  $url,
                                'menu-item-status' => 'publish')
                                );
                        }

                }

		public function delete_cek_resi(){
                        $menu_name = 'primary';
                        $locations = get_nav_menu_locations();

                        if(!isset($locations) || !is_array($locations))
                                return;

                        if(!array_key_exists($menu_name,$locations))
                                return;

                        $menu_id = $locations[ $menu_name ] ;
                        $menu_object = wp_get_nav_menu_object($menu_id);

                        if(!$menu_object){
                                return;
                        }
                        $menu_items = wp_get_nav_menu_items($menu_object->term_id);
                        $is_menu_exist = false;
                        foreach ( (array) $menu_items as $key => $menu_item ) {
                                $post_title = $menu_item->post_title;
                                if ($post_title === "Cek Resi JNE"){
                                        $is_menu_exist = true;
                                        wp_delete_post($menu_item->ID,true);
                                }
                        }

                        $page = get_page_by_title( 'cekresi','page' ) ;
                        wp_delete_post($page->ID,true);
                }


		public function activate(){
			global $wpdb;
			if(file_exists(EPEKEN_JNE_TARIF) === false){
                                copy(EPEKEN_ORI_JNE_TARIF,EPEKEN_JNE_TARIF);
                        }
                        $enable_cekresi = $this -> settings['enable_cekresi_page'];
                        if($enable_cekresi === 'yes') {
                                $this->create_cek_resi_page();
                                $this->add_cek_resi_page_to_prim_menu();
                        }else{
                                $this -> delete_cek_resi();
                        }

			add_action ('admin_enqueue_scripts',array(&$this,'register_jne_plugin'));
			$table = 'wp_jne_tariff';
			$checked_table = $wpdb->get_var("SHOW TABLES LIKE '".$table."'");
			$is_creating_db = get_option('wp_jne_db_install','false');
			
			if($is_creating_db === 'false') {
				add_option('wp_jne_db_install','0','','yes');
				$is_creating_db = '0';
			} 
			
			if($checked_table !== $table ) 			
			{

				$charset_collate = $wpdb->get_charset_collate();
				$query = 'create table '.$table.' ( kota_kabupaten varchar(50), 
									kecamatan varchar(50), 
									tarif_regular integer, 
									etd_regular varchar(10)
								    ) '.$charset_collate.';';
				 $wpdb -> get_var($query);
				 update_option('wp_jne_db_install','1');
				 $is_creating_db = '1';
				 add_action ('admin_enqueue_scripts',array(&$this,'load_jne_tariff')); 
				 
				  	 
			}
		
			$numrow = $wpdb->get_var('select count(*) from wp_jne_tariff');
                        if ($numrow === '0'){
                                 update_option('wp_jne_db_install','1');
                                 $is_creating_db = '1';
                                 add_action ('admin_enqueue_scripts',array(&$this,'load_jne_tariff'));
                        }

			
			if($is_creating_db === '1'){
				 $this -> popup_message = "Please wait while waiting JNE tariff is being loaded. You may perform another activity while it is loading. It may take view minutes.";
				 add_action ('woocommerce_settings_start',array(&$this,'div_loading'));
			}
		}

		public function div_loading(){
			?>
			<div id="div_load_trf" style='position: fixed; margin: 0 auto; top: 50%; left: 50%; width: 300px; height: 100px; background-color: #FFFFFF; border-radius: 10px;z-index: 9999;border-style: solid; border-color: #F1F1F1;'>
                                        <p style='margin: 10px;'>Message from&nbsp;<a href="http://www.epeken.com" target="_blank">epeken</a><br>
			<?php echo $this->popup_message; ?>
					</p>
                                        <p style='position: relative; float: left; top: -80px; left: 120px; z-index: -1;'><img src='<?php echo plugins_url('assets/load.gif',__FILE__); ?>'</p> 
			<script language='javascript'>
				setTimeout("location.reload(true);",10000);
			</script>
                        </div>
			<?php
		}

		public function writelog($logstr){
			$logdir = plugin_dir_path( __FILE__ )."/log/";
			$sesid = session_id();
			$logfile = fopen ($logdir.$sesid.".log","a");
			$now = date("Y-m-d H:i:s");
			fwrite($logfile,$now.":".$logstr."\n");
			fclose($logfile);
		}

		 public function reset_user_address() {
                                global $current_user;
                                get_currentuserinfo();
                                update_user_meta($current_user -> ID,'billing_city','');
                                update_user_meta($current_user -> ID,'shipping_city','');
                                update_user_meta($current_user -> ID,'billing_address_1','');
                                update_user_meta($current_user -> ID,'shipping_address_1','');
                                update_user_meta($current_user -> ID,'billing_address_2','');
                                update_user_meta($current_user -> ID,'shipping_address_2','');
                }

		public function popup(){

			?>
			<div  id="div_epeken_popup" style='position: fixed; margin: 0 auto; top: 50%; left: 40%; width: 300px; height: 100px; background-color: #EEEEEE; border-radius: 10px;z-index: 9999;border-style: solid; border-color: #F1F1F1;display: none;'>
                                        <p style='margin: 10px;'>Message from&nbsp;<a href="http://www.epeken.com" target="_blank">epeken</a><br>
                        <?php echo $this->popup_message; ?>
                                        </p>
                                        <p style='position: relative; float: left; top: -50px; left: 120px; z-index: -1;'><img src='<?php echo plugins_url('assets/load.gif',__FILE__); ?>'</p>
                        </div>
			<?php	
		}

		public function load_jne_tariff(){
                                 $ajax_url = admin_url('admin-ajax.php');
				 wp_enqueue_script('ajax_load_jne_tariff',plugins_url('/js/jne_load_tariff.js',__FILE__), array('jquery'));
				 wp_localize_script( 'ajax_load_jne_tariff', 'PT_Ajax', array(
        				'ajaxurl'       => $ajax_url
    				 ));
		}

		public function register_jne_plugin(){
                                 $ajax_url = admin_url('admin-ajax.php');
				 wp_enqueue_script('ajax_epeken_register',plugins_url('/js/register.js',__FILE__), array('jquery'));
				 wp_localize_script( 'ajax_epeken_register', 'PT_Ajax', array(
        				'ajaxurl'       => $ajax_url
    				 ));
		}


		public function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
					// Save settings in admin if you have any defined, when save button in admin setting screen is clicked
					add_action('woocommerce_update_options_shipping_' . $this->id,array(&$this, 'process_admin_options'));
					// To display new shipping method in woocommerce shipping menu
					add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
					//add_action('woocommerce_update_options_payment_gateways',array(&$this, 'process_admin_options'));
					$this -> popup_message = "Please wait while loading kecamatan";
       					add_action('woocommerce_before_checkout_billing_form',array(&$this, 'popup'));
					//add_action('woocommerce_before_checkout_billing_form',array(&$this, 'reset_user_address'));
					add_action('woocommerce_checkout_process',array(&$this,'reset_user_address'));
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_update_data_tarif' ) );
					$this -> activate();
		}

/**
 * Initialise Gateway Settings Form Fields
 */
	public function init_form_fields() {
     			$this->form_fields = array(
							'enabled' => array(
                                                        'title'                 => __( 'Enable/Disable', 'woocommerce' ),
                                                        'type'                  => 'checkbox',
                                                        'label'                 => __( 'Enable this shipping method', 'woocommerce' ),
                                                        'default'               => 'yes',
                                                	),
                                                'freeship' => array(
                                                        'title' => __('Nominal Belanja Minimum (Rupiah), Dapat gratis ongkir (Biarkan 0 jika ingin free shipping disabled.)','woocommerce'),
                                                        'type'  => 'text',
                                                        'default' => '0',
                                                 ),
						 'enable_cekresi_page' => array(
                                                        'title' => __('Enable Cek Resi JNE appears in main Menu'),
                                                        'type' => 'checkbox',
                                                        'label' => __('Enable/Disable Cek Resi JNE Page'),
                                                        'default' => 'no'
                                                ),
						 'form_upload_tarif' => array(
                                                        'type' => 'form_upload_tarif',
                                                ),
     				);
	} // End init_form_fields()


   // Our hooked in function - $fields is passed via the filter!
	public function admin_options() {
 		?>
 		<h2><?php _e('Epeken JNE Shipping Settings','woocommerce'); ?></h2>
		 <table class="form-table">
		 <?php $this->generate_settings_html(); ?>
		 </table> <?php
 	}

	
	public function generate_form_upload_tarif_html(){
                ob_start();
                ?>
                <tr>
                <th scope="row" class="titledesc">Data Tarif</th>
                 <td>
                  Data tarif tersimpan dalam file yang Anda bisa download di <a href="<?php echo EPEKEN_URL_JNE_TARIF;  ?>">sini</a>, untuk kemudian bisa diedit untuk menambahkan, atau mengganti informasi tarif jne. kemudian menguploadnya kembali untuk melalui form ini.
                 <div style="position: relative; float: left; margin-top: 20px;">
                  Pilih file jne_tariff.csv dari local folder : <input type="file" name="woocommerce_epeken_jne_tariff" id="woocommerce_epeken_jne_tariff" style="width: 300px;"><input name="save" class="button-primary help_tip" data-tip="Klik untuk melakukan timpa data tarif" class="button-primary" type="submit" value="Timpa Data Tarif">
                 </div>
                 </td>
                </tr>
                <?php
                return ob_get_clean();
        }


        public function get_jne_class_value(){
		/*
                $postdata = explode('&',$_POST['post_data']);
                $jneclasspost = '';
                foreach ($postdata as $value) {
                        if (strpos($value,'order_comments') !== FALSE) {
                                $jneclasspost = $value; 
                                $jneclassar = explode('=',$jneclasspost);
                                $jneclasspost = $jneclassar[1]; 
                                break;
                        }
                }*/
       	         $this -> jneclass = 'REGULAR';//$jneclasspost;
        }                       

	public function get_checkout_post_data($itemdata){
		$post_data = isset($_POST['post_data']) ? $_POST['post_data'] : '';
		$postdata = explode('&',$post_data);
		$post_data_ret = '';
		foreach ($postdata as $value) {
                        if (strpos($value,$itemdata) !== FALSE) {
                                $post_data_ret = $value;
                                $ar = explode('=',$post_data_ret);
                                $post_data_ret = $ar[1];
                                break;
                        }
                }
		$post_data_ret = str_replace('+',' ',$post_data_ret);
		return $post_data_ret;
	}
		
	public function set_shipping_cost() {
			global $wpdb;
                        $sql = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_%"';
                        $wpdb->query($sql);

			 $wooversion = $this -> epeken_get_woo_version_number();
                        $wooversion = substr($wooversion, 0,3);

                        $post_action = '';
                        $val_post_action = '';
                        if ($wooversion > 2.3) {
                          $post_action = isset($_GET['wc-ajax']) ? $_GET['wc-ajax'] : '';
                          $val_post_action = 'update_order_review';
                        } else {
                          $post_action = isset($_POST['action']) ? $_POST['action'] : '';
                          $val_post_action = 'woocommerce_update_order_review';
                        }


                        //$post_action = isset($_POST['action']) ? $_POST['action'] : ''; //obsolete since wc 2.4
                        //  if($post_action === 'woocommerce_update_order_review')      { //obsolete since wc 2.4
                        if ($post_action === $val_post_action)      {
				$this -> get_jne_class_value();
				$isshippedifadr = $this -> get_checkout_post_data('ship_to_different_address');
                                        if($isshippedifadr === '1'){ 
                                         $this -> shipping_kecamatan = $this -> get_checkout_post_data('shipping_address_2');
					 $this -> shipping_city = $this -> get_checkout_post_data('shipping_city');
                                        }else{
					 $this -> shipping_city = $this -> get_checkout_post_data('billing_city');
                                         $this -> shipping_kecamatan = $this -> get_checkout_post_data('billing_address_2');
                                        }
				 $this -> title = $this -> title .' '.$this->jneclass;	
			   }else{
				  $this -> jneclass = 'REGULAR';//sanitize_text_field($_POST['order_comments']);
				   $post_shipping_city = isset($_POST['shipping_city']) ? $_POST['shipping_city'] : '';
				   $post_billing_city = isset($_POST['billing_city']) ? $_POST['billing_city'] : '';
				   if(!empty($post_shipping_city))	{
				     $this -> shipping_city = sanitize_text_field($post_shipping_city);
				   } else {
				     $this -> shipping_city = sanitize_text_field($post_billing_city);
				   }
				   $post_shipping_address_2 = isset($_POST['shipping_address_2']) ? $_POST['shipping_address_2'] : '';	
                                   if(!empty($post_shipping_address_2))  {
                                     $this -> shipping_kecamatan = sanitize_text_field($post_shipping_address_2);
                                   } else {
				     $post_billing_address_2 = isset($_POST['billing_address_2']) ? $_POST['billing_address_2'] : '';
                                     $this -> shipping_kecamatan = sanitize_text_field($post_billing_address_2);
                                   }   
		 	   }
			   $this -> shipping_cost = get_tarif($this -> shipping_city, $this -> shipping_kecamatan, $this -> jneclass);
			        if ($this -> shipping_cost == 0 && $this -> jneclass !== 'REGULAR' && $this -> jneclass !== ""   && !empty($this -> jneclass)
						&& $this -> shipping_city !== '' && $this -> shipping_kecamatan !== ''){
					?><script language="javascript"> 
						  alert ('Biaya pegiriman tidak ditemukan, pilihan kelas JNE dialihkan ke JNE REGULAR');
							var val = 'REGULAR';
						        var sel = document.getElementById('order_comments');
							var opts = sel.options;
							for(var opt, j = 0; opt = opts[j]; j++) {
    							    if(opt.value == val) {
           							sel.selectedIndex = j;
            							break;
        						    }
    							}
					      </script>
					<?php
					$this -> jneclass = 'REGULAR';
					$this -> shipping_cost = get_tarif($this -> shipping_city, $this -> shipping_kecamatan, $this -> jneclass);
					$this -> title = 'JNE REGULAR';
				}
	}

	public function get_cart_total() {
                global $woocommerce;
                $price = 0;
                foreach($woocommerce -> cart -> get_cart() as $value){
                        $product_data = $value['data'];
                        $price = $price + (floatval($value['quantity']) * floatval($product_data -> price));
                }
                return $price;
        }


	public function calculate_shipping( $package ) {	
		$this -> set_shipping_cost();
		$this -> if_total_got_free_shipping();

		if($this -> is_free_shipping){
			 $rate = array(
                        'id' => $this -> id,
                        'label' => $this -> title,
                        'cost' => 0 
                        );
                        $this->add_rate($rate);   
			return;
		}		

		if ($this -> shipping_cost > 0) {
		$rate = array(
			'id' => $this -> id,
			'label' => $this -> title,
			'cost' =>  $this -> shipping_cost 
			//'calc_tax' => 'per_item'
			);
			// Register the rate
			$this->add_rate($rate);	
		}
			
	}

	public function if_total_got_free_shipping(){
		global $woocommerce;
		//$this -> total_cart = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );
		//$this -> total_cart = $this->total_cart*1000;
		$this -> total_cart = $this -> get_cart_total();
		$this -> min_allow_fs  = floatval($this -> settings['freeship']);
		if ($this -> min_allow_fs == 0){
			$this -> min_allow_fs = false;
			return;
		}
                if ($this->total_cart >= $this->min_allow_fs)
                {
                        $this -> is_free_shipping = true;
                }else{
                        $this -> is_free_shipping = false;
                }
	}

	public function process_update_data_tarif() {
                include_once 'tools/update_tarif.php';
        }

        public function admin_error($message) {
        $class = "error";
        echo"<div class=\"$class\"> <p>$message</p></div>";
        }
        public function epeken_get_woo_version_number() {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it 
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
                return $plugin_folder[$plugin_file]['Version'];

        } else {
        // Otherwise return null
                return NULL;
        }
        }


	}	// End Class WC_Shipping_Tikijne

?>
