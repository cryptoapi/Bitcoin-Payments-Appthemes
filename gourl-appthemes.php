<?php
/*
Plugin Name: 		GoUrl AppThemes - Bitcoin Payments for Classipress, Vantage, JobRoller, etc
Plugin URI: 		https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html
Description: 		Provides a <a href="https://gourl.io">GoUrl.io</a> Bitcoin/Altcoin Payment Gateway for all <a href="http://www.appthemes.com/themes/">AppThemes.com Premium Themes</a> - Classipress, Vantage, JobRoller, Clipper, Taskerr, HireBee, Ideas, Quality Control, etc. Support bitcoin/altcoins Escrow Payments; product prices in USD/EUR/etc and in cryptocoins directly; sends the amount straight to your business Bitcoin/Altcoin wallet. Convert your USD/EUR/etc prices to cryptocoins using Google/Bitstamp/Cryptsy Live Exchange Rates. Accept Bitcoin, Litecoin, Paycoin, Dogecoin, Dash, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin, Peercoin payments online.
Version: 			1.1.1
Author: 			GoUrl.io
Author URI: 		https://gourl.io
License: 			GPLv2
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: 	https://github.com/cryptoapi/Bitcoin-Payments-AppThemes
*/


if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

if (!function_exists('gourl_app_gateway_load') && !function_exists('gourl_app_load_textdomain')) // Exit if duplicate
{

	// Load Gateway
	add_action( 'init', 'gourl_app_gateway_load', 1);
	// Localization
	add_action( 'plugins_loaded', 'gourl_app_load_textdomain' );
	
	
	DEFINE( 'GOURLAP', 'gourl-appthemes');

	
	
	function gourl_app_load_textdomain()
	{
		load_plugin_textdomain( GOURLAP, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	
	
	function gourl_app_gateway_load() 
	{

		
	// AppThemes compatible product required
	if (!class_exists('APP_Gateway') || !current_theme_supports('app-payments')) return;
	
	// Filters
	add_filter( 'plugin_action_links', 	'gourl_app_action_links', 10, 2 );

	
	
	/*
	 *	1. Plugin Links 
	*/
	function gourl_app_action_links($links, $file)
	{
		static $this_plugin;
	
		if (false === isset($this_plugin) || true === empty($this_plugin)) {
			$this_plugin = plugin_basename(__FILE__);
		}
	
		if ($file == $this_plugin) {
			$settings_link = '<a href="'.admin_url('admin.php?page=app-payments-settings&tab=gourl').'">'.__( 'Settings', GOURLAP ).'</a>';
			array_unshift($links, $settings_link);
				
			if (defined('GOURL'))
			{
				$unrecognised_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=unrecognised').'">'.__( 'Unrecognised', GOURLAP ).'</a>';
				array_unshift($links, $unrecognised_link);
				$payments_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=gourlappthemes').'">'.__( 'Payments', GOURLAP ).'</a>';
				array_unshift($links, $payments_link);
			}
		}
	
		return $links;
	}
	
	
	

	/*
	 *	2. Add currencies
	*/
	function gourl_app_currency()
	{
		global $gourl;
	
		if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
		{
			$arr = $gourl->coin_names();
	
			foreach ($arr as $k => $v)
			{
				$details = array('symbol' => $k, 'name' => __('Cryptocurrency', GOURLAP )." - ".__(ucfirst($v), GOURLAP ) );
	
				APP_Currencies::add_currency( $k, $details );
			}
			
			__( 'Bitcoin', GOURLAP );  // use in translation
		}
	
		return true;
	}
	
	
	
	
	

	/*******************************************
	 * 
	 *	3. Payment Gateway Appthemes Class
	 *
	********************************************/
	
	class APP_Gourl extends APP_Gateway
	{
		protected $options;
	
		private $payments 			= array();
		private $languages 			= array();
		private $coin_names			= array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'XPY' => 'paycoin', 'DOGE' => 'dogecoin', 'DASH' => 'dash', 'SPD' => 'speedcoin', 'RDD' => 'reddcoin', 'POT' => 'potcoin', 'FTC' => 'feathercoin', 'VTC' => 'vertcoin', 'VRC' => 'vericoin', 'PPC' => 'peercoin');
		private $mainplugin_url		= "";
	
		
		
	
		/*
		 * 3.1
		*/
		public function __construct()
		{
			$this->mainplugin_url = admin_url("plugin-install.php?tab=search&type=term&s=GoUrl+Bitcoin+Payment+Gateway+Downloads");
			
			$title = trim(get_option(GOURLAP."title"));
			if (!$title) $title = __( 'Bitcoin/Altcoins', GOURLAP );
			
			parent::__construct( 'gourl', array(
					'admin' 	=> __( 'GoUrl Bitcoin/Altcoins', GOURLAP ),
					'dropdown' 	=> $title
			) );
			
			
			if (is_admin() && isset($_GET["page"]) && $_GET["page"] == "app-payments-settings" && isset($_GET["tab"]) && $_GET["tab"] == "gourl" && strpos($_SERVER["SCRIPT_NAME"], "admin.php"))
			{
				add_action( 'admin_footer_text', array(&$this, 'admin_footer_text'), 25);
			}
					
		}
	
		
		
		
		/*
		 * 3.2
		*/
		public function create_form( $order, $options ){ }
	
		
		

		/*
		 * 3.3
		*/
		public function form()
		{
			global $gourl;
	
			$title   	   = __( 'GoUrl Bitcoin/Altcoins', GOURLAP );
			
			$description   = "<a target='_blank' href='https://gourl.io/'><img border='0' style='float:left; margin-right:15px' src='".plugin_dir_url( __FILE__ )."gourlpayments.png'></a>";
			$description  .= "<a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html'>".__( 'Plugin Homepage', GOURLAP )."</a> &#160;&amp;&#160; <a target='_blank' href='https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html#screenshot'>".__( 'screenshots', GOURLAP )." &#187;</a><br>";
			$description  .= "<a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Payments-Appthemes'>".__( 'Plugin on Github - 100% Free Open Source', GOURLAP )." &#187;</a><br><br>";
	
			if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
			{
				if (true === version_compare(GOURL_VERSION, '1.3.2', '<'))
				{
					$description .= '<div class="error"><p><b>' .sprintf(__( "Your GoUrl Bitcoin Gateway <a href='%s'>Main Plugin</a> version is too old. Requires 1.3.2 or higher version. Please <a href='%s'>update</a> to latest version.", GOURLAP ), GOURL_ADMIN.GOURL, $this->mainplugin_url)."</b> &#160; &#160; &#160; &#160; " .
							__( 'Information', GOURLAP ) . ": &#160; <a href='https://gourl.io/bitcoin-wordpress-plugin.html'>".__( 'Main Plugin Homepage', GOURLAP )."</a> &#160; &#160; &#160; " .
							"<a href='https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/'>".__( 'WordPress.org Plugin Page', GOURLAP )."</a></p></div>";
						
				}
				else
				{
					$this->payments 			= $gourl->payments(); 		// Activated Payments
					$this->coin_names			= $gourl->coin_names(); 	// All Coins
					$this->languages			= $gourl->languages(); 		// All Languages
				}
					
				$coins 	= implode(", ", $this->payments);
				$url	= GOURL_ADMIN.GOURL."settings";
				$url2	= GOURL_ADMIN.GOURL."payments&s=gourlappthemes";
				$url3	= GOURL_ADMIN.GOURL;
				$text 	= ($coins) ? $coins : __( '- Please setup -', GOURLAP );
			}
			else
			{
				$description .= '<div class="error"><p><b>' .
						sprintf(__( "You need to install GoUrl Bitcoin Gateway Main Plugin also. Go to - <a href='%s'>Automatic installation</a> or <a href='%s'>Manual</a>.", GOURLAP ), $this->mainplugin_url, "https://gourl.io/bitcoin-wordpress-plugin.html") . "</b> &#160; &#160; &#160; &#160; " .
						__( 'Information', GOURLAP ) . ": &#160; &#160;<a href='https://gourl.io/bitcoin-wordpress-plugin.html'>".__( 'Main Plugin Homepage', GOURLAP )."</a> &#160; &#160; &#160; <a href='https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/'>" .
						__( 'WordPress.org Plugin Page', GOURLAP ) . "</a></p></div>";
				
				$coins 	= "";
				$url	= $this->mainplugin_url;
				$url2	= $url;
				$url3	= $url;
				$text 	= '<b>'.__( 'Please install GoUrl Bitcoin Gateway WP Plugin', GOURLAP ).' &#187;</b>';
			}
				
			$description  .= "<b>" . __( "Secure payments with virtual currency. <a target='_blank' href='https://bitcoin.org/'>What is Bitcoin?</a>", GOURLAP ) . '</b><br>';
			$description  .= sprintf(__( 'Accept %s payments online in Appthemes Premium Themes - Classipress, Taskerr, HireBee, Vantage, Clipper, JobRoller, Ideas, Quality Control, etc.', GOURLAP ), __( ucwords(implode(", ", $this->coin_names)), GOURLAP )).'<br>';
			$description  .= sprintf(__( "If you use multiple stores/sites online, please create separate <a target='_blank' href='%s'>GoUrl Payment Box</a> (with unique payment box public/private keys) for each of your stores/websites. Do not use the same GoUrl Payment Box with the same public/private keys on your different websites/stores.", GOURLAP ), "https://gourl.io/editrecord/coin_boxes/0") . '<br><br>';
				
			$fields = array(
					array(
							'name'			=> 'title',
							'title'       	=> __( 'Title', GOURLAP ),
							'type'        	=> 'text',
							'default'     	=> __( 'Bitcoin/Altcoins', GOURLAP ),
							'desc' 			=> '<p>'.__( 'Payment method title that the customer will see on your checkout', GOURLAP ).'</p>'
					),
					array(
							'name'			=> 'defcoin',
							'title' 		=> __('PaymentBox Default Coin', GOURLAP ),
							'type' 			=> 'select',
							'values' 		=> $this->payments,
							'default' 		=> key($this->payments),
							'desc' 			=> '<p>'.sprintf(__( "Default Coin in Crypto Payment Box. &#160; Activated Payments : <a href='%s'>%s</a>", GOURLAP ), $url, $text).'</p>'
					),
					array(
							'name'			=> 'deflang',
							'title' 		=> __('PaymentBox Language', GOURLAP ),
							'type' 			=> 'select',
							'values' 		=> $this->languages,
							'default' 		=> 'en',
							'desc' 			=> '<p>'.__("Default Crypto Payment Box Localisation", GOURLAP).'</p>'
					),
					array(
							'name'			=> 'emultiplier',
							'title' 		=> '<br>'.__('Exchange Rate Multiplier', GOURLAP ),
							'type' 			=> 'text',
							'default' 		=> '1.00',
							'desc' 			=> '<p>'.__('The system uses the multiplier rate with today LIVE cryptocurrency exchange rates (which are updated every 30 minutes) when the transaction is calculating from a fiat currency (e.g. USD, EUR, etc) to cryptocurrency. <br> Example: <b>1.05</b> - will add an extra 5% to the total price in bitcoin/altcoins, <b>0.85</b> - will be a 15% discount for the price in bitcoin/altcoins. Default: 1.00 ', GOURLAP ).'</p>'
					),
					array(
							'name'			=> 'iconwidth',
							'title'       	=> __( 'Icons Size', GOURLAP ),
							'type'        	=> 'text',
							'default'     	=> "80px",
							'desc' 			=> '<p>'.__( "Cryptocoin icons size in 'Select Payment Method' that the customer will see on your checkout. Default 60px. Allowed: 30..250px", GOURLAP ).'</p>'
					),
					array(
							'name'			=> 'gourlinfo',
							'title'       	=> __( 'PaymentBox Style', GOURLAP ),
							'type'        	=> 'custom',
							'render' 		=> array( $this, 'style_notes')
					)
			);
	
	
			if (isset($_POST["gateways"]["gourl"]["title"]) && strip_tags($_POST["gateways"]["gourl"]["title"])) update_option(GOURLAP."title", strip_tags($_POST["gateways"]["gourl"]["title"]));
			
			
			$arr = array(
					array(
							'title' => $title."<br><br><div style='font-size:13px;font-weight:normal;'>".$description."</div><br>",
							'fields' => $fields
					)
			);
	
			return $arr;
		}
	
		
	
		/*
		 *	3.4
		*/
		public function style_notes()
		{
			if (!defined('GOURL_ADMIN')) return "";
			
			$notes = sprintf(__( "Payment Box <a href='%s'>sizes</a> and border <a href='%s'>shadow</a> you can change <a href='%s'>here &#187;</a>", GOURLAP ), "https://gourl.io/images/global/sizes.png", "https://gourl.io/images/global/styles.png", GOURL_ADMIN.GOURL."settings#gourlpeercoinprivate_key") . "<br><br>" . 
					 sprintf(__( "If you want to use GoUrl AppThemes Bitcoin Gateway plugin in a language other than English, see the page <a href='%s'>Languages and Translations</a>", GOURLAP ), "https://gourl.io/languages.html");
		
			return $notes;
		}
				

		
		/*
		 * 3.5
		*/
		public function process( $order, $options )
		{
			gourl_app_process ($order, $options, false);
	
			return true;
		}
		
		
		/*
		 * 3.6
		*/
		public function admin_footer_text()
		{
			return sprintf( __( "If you like <b>GoUrl AppThemes Bitcoin Gateway</b> please leave us a %s rating on %s. A huge thank you from GoUrl in advance!", GOURLAP ), "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-appthemes-bitcoin-payments-classipress-vantage-jobroller?filter=5#postform' target='_blank'>&#9733;&#9733;&#9733;&#9733;&#9733;</a>", "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-appthemes-bitcoin-payments-classipress-vantage-jobroller?filter=5#postform' target='_blank'>WordPress.org</a>");
		}
		
		
	}  // end APP_Gourl class


	
	
	
	

	
	
	
	
	
	/*******************************************
	 * 
	 *	4. GOURL ESCROW 
	 *
	********************************************/
function appthemes_init_gourl_escrow()
{

	// Escrow required
	if (!function_exists('appthemes_is_escrow_enabled') || !appthemes_is_escrow_enabled()) return true;
		
	class APP_Escrow_Gourl extends APP_Gourl implements APP_Escrow_Payment_Processor 
	{
		
		/*
		 *	4.1 
		*/
		public function supports( $service = 'instant' )
		{
			switch ( $service ) 
			{
				case 'escrow':
					return true;
					break;
				default:
					return parent::supports( $service );
					break;
			}
			
			return true;
		}
		
		
		
		/*
		 *	4.2 
		*/
		public function form()
		{
		
			$fields = parent::form();

			if (function_exists('appthemes_is_escrow_enabled') && appthemes_is_escrow_enabled())
			{	
				$fields[] = array
					(
						'title' => '<br><br>'.__( 'GoUrl Escrow (optional)', GOURLAP ),
						'fields' => array(
							array(
									'title' 		=> '',
									'name' 			=> 'escrowinfo',
									'type' 			=> 'custom',
									'render' 		=> array( $this, 'escrow_notes' )
							),
							array(
									'title' 		=> __( 'Admin Email', GOURLAP ),
									'name' 			=> 'adminemail',
									'default' 		=> '',
									'type' 			=> 'text',
									'desc' 			=> '<p>'.sprintf( __( 'Your email address. You will receive email notifications when need to forward escrow funds from your wallet to the seller or to refund the buyer. You will need to make payments from your wallet/s manually.' )).'</p>'
							),
							array(
									'title' 		=> '<br>'.__('Escrow - Exchange Rate Multiplier', GOURLAP ),
									'name'			=> 'emultiplier2',
									'default' 		=> '1.00',
									'type' 			=> 'text',
									'desc' 			=> '<p>'.__('The system uses the multiplier rate with today LIVE cryptocurrency exchange rates when the escrow funds amount is calculating from a fiat currency (e.g. USD, EUR, etc). <br>Example: <b>1.05</b> - will add an extra 5% to the total amount in bitcoin/altcoins, <b>0.85</b> - will be a 15% discount for the amount in bitcoin/altcoins. Default: 1.00', GOURLAP ).'</p>'
							)
						)
					);
			}	
		
			return apply_filters( 'appthemes_gourl_escrow_settings_form', $fields );
		}
		
		
			
		/*
		 *	4.3 
		*/
		public function escrow_notes()
		{
			$notes = sprintf(__( "<strong>Important:</strong> Gourl Bitcoin/Altcoin Gateway does not fully support Escrow. You can do the following - activate GoUrl Escrow on <a href='%s'>escrow tab</a>, collect the escrow funds from the buyers in a similar way as normal payments (standard commission fees apply). GoUrl will forward all payments to your wallet address/es. if a user has sent a wrong payment for an escrow order, please send payment back to the user from your wallet and asking the user to make a correct payment (appthemes doesn't allow manually approve escrow payments). When the project/etc completed, this addon will send you email notifications that you need to send the payment to the seller (or make refund to the buyer) from your own cryptocoin wallet manually.", GOURLAP ), admin_url('admin.php?page=app-payments-settings&tab=escrow'));
				
			return $notes;
		}		
		
	
		
		/*
		 *	4.4 
		*/
		public function process_escrow( APP_Escrow_Order $order, array $options ) 
		{
			gourl_app_process ($order, $options, true);
			
			$order_id = $order->get_id();
			
			if (!$order->get_data('escrow_name'))
			{
				$buyer = get_userdata($order->get_author());
				
				$txt  = sprintf(__('Awaiting %s ...', GOURLAP), $order->get_description()) . "<br>";
				$txt .= sprintf(__("Buyer - <a href='%s'>user%s - %s</a> &#160; (need to pay escrow, %s)", GOURLAP), admin_url("user-edit.php?user_id=").$buyer->ID, $buyer->ID, $buyer->user_login, $order->get_total() . " "  . $order->get_currency()) . "<br>";
				$sellers = $order->get_receivers();
				foreach ($sellers as $k => $v)
				{
					$seller = get_userdata($k);
					$txt .= sprintf(__("Seller - <a href='%s'>user%s - %s</a> &#160; (agreed on %s)", GOURLAP), admin_url("user-edit.php?user_id=").$seller->ID, $seller->ID, $seller->user_login, $v . " " . $order->get_currency()) . "<br>";
				}
				$txt = substr($txt, 0, -4);
				
				$order->log($txt, 'waiting');
						
				$order->add_data( 'escrow_name', $order->get_description() );
			}
				
			return true;
		}
		
	

		/*
		 *	4.5
		*/
		public function get_details( APP_Escrow_Order $order, array $options ) 	{ }
		
		
		
		/*
		 *	4.6
		*/
		public function complete_escrow( APP_Escrow_Order $order, array $options ) 
		{
			
			if (!($order && is_object($order) && $order->get_gateway() == "gourl" && $order->is_escrow())) return true;
			
			$order_id = $order->get_id();
				
			$txt =  "<b>".__( "Project Completed", GOURLAP )."</b>";
			
			if ($order->get_data('escrow_received') == 'yes')
			{
				$txt .= "<br>" . __( "You need to complete escrow - send funds from your wallet to the Seller manually", GOURLAP ) . "<br>";
				$txt .= app_gourl_escrow_wallets($order->get_data('coinname'), $order);
				$order->log ($txt, 'complete');

				$url = admin_url("post.php?post=".$order_id."&action=edit");
				$body  = "<div style='font-size: 12px; margin: 5px; color: #333333; line-height: 17px; font-family: Verdana, Arial, Helvetica'>";
				$body .= __("Hello,", GOURLAP) . "<br><br>\n";
				$body .= sprintf( __("You need to complete <a href='%s'>Escrow #%s</a> - send funds from your wallet to the Seller manually.", GOURLAP), $url, $order_id) . "<br><br>\n";
				$body .= "&#160; &#160; " . sprintf(__( "Escrow <a href='%s'>#%s</a> Log -", GOURLAP), $url, $order_id);
				$body .= "<br><br><table cellspacing='1' cellpadding='10' style='font-size: 12px; margin: 5px 15px; padding: 5px; background-color: #eeeeee; color: #333333; line-height: 17px; font-family: Verdana, Arial, Helvetica'>\n";
						
				$log = new APP_Post_Log( $order_id );
				$arr = $log->get_log();
				foreach ($arr as $row)
					if ($row["time"] && $row["message"]) $body .= "<tr style='background-color:#ffffff;'><td width='150'>".$row["time"]."</td><td>".$row["message"]."</td></tr>\n";
				
				$body .= "</table>";
				$body .= "</div>";
				
				if (filter_var($options["adminemail"], FILTER_VALIDATE_EMAIL)) wp_mail($options["adminemail"], "ESCROW #".$order_id.": Completed Project - need to send escrow funds to the Seller", $body, array('Content-type: text/html'));
			}
			else $order->log ($txt, 'complete');
				
			return true;
		}
		

		
		
		/*
		 *	4.7
		*/
		public function fail_escrow( APP_Escrow_Order $order, array $options )
		{
				
			if (!($order && is_object($order) && $order->get_gateway() == "gourl" && $order->is_escrow())) return true;
				
			$order_id = $order->get_id();
		
			$txt =  "<b>".__( "Project Failed", GOURLAP )."</b>";
				
			if ($order->get_data('escrow_received') == 'yes')
			{
				$txt .= "<br>" . __( "You need to REFUND escrow - send funds back from your wallet to the Buyer manually", GOURLAP ) . "<br><br>";
				$txt .= app_gourl_escrow_wallets($order->get_data('coinname'), $order, 'buyer');
				$order->log ($txt, 'failed');
		
				$url = admin_url("post.php?post=".$order_id."&action=edit");
				$body  = "<div style='font-size: 12px; margin: 5px; color: #333333; line-height: 17px; font-family: Verdana, Arial, Helvetica'>\n";
				$body .= __("Hello,", GOURLAP) . "<br><br>\n";
				$body .= sprintf( __("You need to REFUND <a href='%s'>Escrow #%s</a> - send funds back from your wallet to the Buyer manually.", GOURLAP), $url, $order_id) . "<br><br>\n";
				$body .= "&#160; &#160;  " . sprintf(__( "Escrow <a href='%s'>#%s</a> Log -", GOURLAP), $url, $order_id);
				$body .= "<br><br><table cellspacing='1' cellpadding='10' style='font-size: 12px; margin: 5px 15px; padding: 5px; background-color: #eeeeee; color: #333333; line-height: 17px; font-family: Verdana, Arial, Helvetica'>\n";
		
				$log = new APP_Post_Log( $order_id );
				$arr = $log->get_log();
				foreach ($arr as $row)
					if ($row["time"] && $row["message"]) $body .= "<tr style='background-color:#ffffff;'><td width='150'>".$row["time"]."</td><td>".$row["message"]."</td></tr>\n";
		
				$body .= "</table>";
				$body .= "</div>";
		
				if (filter_var($options["adminemail"], FILTER_VALIDATE_EMAIL)) wp_mail($options["adminemail"], "ESCROW #".$order_id.": Failed Project - need to send escrow funds back to the Buyer", $body, array('Content-type: text/html'));
			}
			else $order->log ($txt, 'failed');
		
			return true;
		}

		
		
		/*
		 *	4.8
		*/
		public function user_form() 
		{
			global $gourl; 
	
			if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl) && true === version_compare(GOURL_VERSION, '1.3', '>='))
			{
				$payments 	= $gourl->payments(); 		// Activated Payments
				$www		= $gourl->coin_www(); 		// Websites
				
			}
			else return array();
				
			$fields = array();
			foreach ($payments as $v)
			{
				$fields[] = array(
								'title' => '<div style="white-space:nowrap">'.__( $v.' Address', GOURLAP )."</div>",
								'type'  => 'text',
								'name'  => 'escrow_addr_'.strtolower($v),
								'extra' => array( 'class' => 'text regular-text'),
								'desc'  => sprintf(__( "%s transfers will be made to this your %s address (if buyer pays you in <a target='_blank' href='%s'>%s</a>)", GOURLAP ), $v, $v, $www[strtolower($v)], strtolower($v).'s' ),
							);
			}
			
			$arr = array
					(
						'title' 	=> __( 'Cryptocoin Escrow Information', GOURLAP ),
						'fields' 	=> $fields
					);
	
			return $arr;
		}
	

	
	} // end APP_Escrow_Gourl class

	// register escrow gateway
	appthemes_register_gateway( 'APP_Escrow_Gourl' );
	
	
	
	// manual hirebee theme fix
	// ------------------------------
	
	add_action('parse_request', 'app_gourl_escrow_parse_request');
	add_action('hrb_before_workspace_project_details', 'app_gourl_escrow_pay');
	
	
	// redirect to fund transfer page (hirebee not redirect correctly)
	function app_gourl_escrow_parse_request()
	{
		if (stripos($_SERVER["REQUEST_URI"], "/transfer-funds/") === 0 && isset($_GET['oid']) && intval($_GET['oid']))
		{
			$order = appthemes_get_order( intval($_GET['oid']) );
			if ($order && is_object($order) && $order->get_gateway() == "gourl" && $order->is_escrow())
			{
				$url = $order->get_return_url();
				if ($url && !stripos($url, $_SERVER["REQUEST_URI"])) { wp_redirect($url); die; }
			}
		}
		
		return true;
	}
	
	// publish 'transfer funds' button on workspace page
	function app_gourl_escrow_pay() 
	{
		$order = (get_the_ID()) ? hrb_get_pending_order_for(get_the_ID()) : "";
		
		if ($order && is_object($order) && $order->is_escrow() && $order->get_author() == get_current_user_id() && $order->get_status() == "tr_pending")
			echo '<a href="'.($order->get_gateway()=="gourl" ? $order->get_return_url() : site_url("transfer-funds/?oid=".$order->get_id())).'"><span class="label right project-status">'.__( 'Transfer Funds Now &#187;', GOURLAP ).'</span></a>';
		
		return true;
	}
	
	// -------------------------
}
	


	/*
	 * 5.
	*/
	function app_gourl_escrow_wallets($coinName, $order, $type = "seller") 
	{
		global $gourl;
		
		if (!$order || !is_object($order)) return "";

		$txt 		= "";
		$coinName	= strtolower($coinName);
		$www 		= (is_object($gourl)) ? $gourl->coin_chain() : array();
		$users 		= ($type == "buyer") ? array($order->get_author() => $order->get_total()) : $order->get_receivers();
		
		foreach ($users as $k => $v)
		{
			$addr = get_user_option('escrow_addr_'.$coinName, $k); // seller wallet address
			$order->add_data( 'user'.$k.'_escrow_addr_'.$coinName, $addr );
				
			if (!$addr) 						$addr = "- no -";
			elseif (strlen($addr) < 26 || strlen($addr) > 35) 	$addr = "- invalid -";
			elseif (isset($www[$coinName])) 	$addr = "<a target='_blank' href='" . $www[$coinName] . (stripos($www[$coinName],'cryptoid.info')?'address.dws?':'address/') . $addr . "'>" . $addr . "</a>";
			$txt .= sprintf(__("%s - <a href='%s'>user%s</a> - provided %s wallet address: &#160; %s", GOURLAP), ($type == "buyer"?"Buyer":"Seller"), admin_url("user-edit.php?user_id=").$k, $k, ucfirst($coinName), $addr) . "<br>";
		}
		
		$txt = substr($txt, 0, -4);
		
		return $txt;
	}



	/*
	 *  6. Instant Payment Notification Function - pluginname."_gourlcallback"
	*
	*  This function will appear every time by GoUrl Bitcoin Gateway when a new payment from any user is received successfully.
	*  Function gets user_ID - user who made payment, current order_ID (the same value as you provided to bitcoin payment gateway),
	*  payment details as array and box status.
	*
	*  The function will automatically appear for each new payment usually two times :
	*  a) when a new payment is received, with values: $box_status = cryptobox_newrecord, $payment_details[is_confirmed] = 0
	*  b) and a second time when existing payment is confirmed (6+ confirmations) with values: $box_status = cryptobox_updated, $payment_details[is_confirmed] = 1.
	*
	*  But sometimes if the payment notification is delayed for 20-30min, the payment/transaction will already be confirmed and the function will
	*  appear once with values: $box_status = cryptobox_newrecord, $payment_details[is_confirmed] = 1
	*
	*  Payment_details example - https://gourl.io/images/plugin2.png
	*  Read more - https://gourl.io/affiliates.html#wordpress
	*/
	function gourlappthemes_gourlcallback ($user_id, $order_id, $payment_details, $box_status)
	{
		
		// orders with two types: order... or escrow...
		$escrow = (strpos($order_id, "escrow") === 0) ? true : false;
	
		// Security
		if (!in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) return false;
		if (!$user_id || $payment_details["status"] != "payment_received") return false;
		 
		if (strpos($order_id, "order") === 0) $order_id = substr($order_id, 5);
		elseif (strpos($order_id, "escrow") === 0) $order_id = substr($order_id, 6);
		else return false;
	
		 
		$order = appthemes_get_order($order_id);
		if (!$order) return false;
	
		 
		// Callback Values
		$coinName 	= ucfirst($payment_details["coinname"]);
		$amount		= $payment_details["amount"] . " " . $payment_details["coinlabel"] . "&#160; ( $" . $payment_details["amountusd"] . " )";
		$payID		= $payment_details["paymentID"];
		$confirmed	= ($payment_details["is_confirmed"]) ? __('Yes', GOURLAP) : __('No', GOURLAP);
		 
		 
		// a. New Payment Received
		if ($box_status == "cryptobox_newrecord")
		{
	
			if ($escrow)
			{
				$item = $order->get_item();
				$order->log(sprintf(__("<b>Escrow Received in %s from Buyer</b><br>Amount - %s<br>Escrow for Project - <a href='%s'>%s</a><br><a href='%s'>Payment id %s</a>", GOURLAP), $coinName."s", $amount, $item["post"]->guid, $item["post"]->post_title, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID), 'received');
				$order->add_data( 'escrow_received', 'yes' );
				$order->log(app_gourl_escrow_wallets($coinName, $order));
				
			}
			else
			{
				$order->log(sprintf(__("<b>%s Payment Received</b><br>%s<br><a href='%s'>Payment id %s</a>", GOURLAP), $coinName, $amount, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID), 'received');
			}
	
			$order->add_data( 'coinname', 	$coinName);
			$order->add_data( 'amount', 	$payment_details["amount"] . " " . $payment_details["coinlabel"] );
			$order->add_data( 'userid', 	$payment_details["userID"] );
			$order->add_data( 'country', 	get_country_name($payment_details["usercountry"]) );
			$order->add_data( 'tx', 		$payment_details["tx"] );
			$order->add_data( 'confirmed', 	$confirmed );
			$order->add_data( 'details', 	$payment_details["paymentLink"] );
		}
		 
		 
		// b. Existing Payment confirmed (6+ confirmations)
		if ($payment_details["is_confirmed"])
		{
			$order->add_data( 'confirmed', $confirmed );
			$order->log(sprintf(__("%s Payment id <a href='%s'>%s</a> Confirmed", GOURLAP), $coinName, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID), 'confirmed');
		}
		 
		 
		// c. Change order status to completed for orders and PAID for escrow
		if ($escrow) { if ($order->get_status() == APPTHEMES_ORDER_PENDING) $order->paid(); }
		else 		 { if ($order->get_status() != APPTHEMES_ORDER_COMPLETED && $order->get_status() != APPTHEMES_ORDER_ACTIVATED) $order->complete(); }
	
		 
		return true;
	}
	

	
	
	
	
	/*
	 *	7. Cryptocoin payment box
	*/
	function gourl_app_process ( $order, $options, $escrow = false )
	{
		global $gourl;

		
		if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl) && true === version_compare(GOURL_VERSION, '1.3', '>='))
		{
			$payments 	= $gourl->payments(); 		// Activated Payments
			$coin_names	= $gourl->coin_names(); 	// All Coins
			$languages	= $gourl->languages(); 		// All Languages
		}
		else $payments = $coin_names = $languages = array();
		
		
		if (!isset($options["emultiplier2"])) 	$options["emultiplier2"] = 1;
		if (!isset($options["adminemail"])) 	$options["adminemail"] = "";
		
		
		// validate options
		// -----------------
		$options["emultiplier"]  = trim(str_replace("%", "", 	$options["emultiplier"]));
		$options["emultiplier2"] = trim(str_replace("%", "", 	$options["emultiplier2"]));
		$options["iconwidth"] 	 = trim(str_replace("px", "", 	$options["iconwidth"]));
		
		if (!$options["title"]) 																					$options["title"] 	= __('Bitcoin/Altcoins', GOURLAP);
		if (!isset($languages[$options["deflang"]])) 																$options["deflang"]	= 'en';
		
		if (!$options["emultiplier"]  || !is_numeric($options["emultiplier"])  || $options["emultiplier"] < 0.01)  	$options["emultiplier"]  = 1;
		if (!$options["emultiplier2"] || !is_numeric($options["emultiplier2"]) || $options["emultiplier2"] < 0.01) 	$options["emultiplier2"] = 1;
		if (!is_numeric($options["iconwidth"]) || $options["iconwidth"] < 30 || $options["iconwidth"] > 250) 		$options["iconwidth"] 	 = 80;
		
		if ($options["defcoin"] && $payments && !isset($payments[$options["defcoin"]])) 							$options["defcoin"] = key($payments);
		elseif (!$payments)																							$options["defcoin"]	= '';
		elseif (!$options["defcoin"])																				$options["defcoin"]	= key($payments);
		
		if (!filter_var($options["adminemail"], FILTER_VALIDATE_EMAIL)) 											$options["adminemail"] = "";
		// -----------------
		
		
		$userID			= $order->get_author();
		$order_id 		= $order->get_id();
		$order_currency = $order->get_currency();
		$order_total	= $order->get_total();

		if (!$order || !$order_id || !$order_total || ($escrow && !$order->is_escrow()))
		{
			echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
			echo "<div class='notice error alert-box'>". sprintf(__( 'The GoUrl payment plugin was called to process a payment but could not retrieve the order details for orderID %s. Cannot continue!', GOURLAP ), $order_id)."</div>";
		}
		elseif (appthemes_get_order($order_id)->get_status() == APPTHEMES_ORDER_FAILED)
		{
			echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
			echo "<div class='notice error alert-box'>". __( "This order's status is 'Failed' - it cannot be paid for. Please contact us if you need assistance.", GOURLAP )."</div>";
		}
		elseif (!class_exists('gourlclass') || !defined('GOURL') || !is_object($gourl))
		{
			echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
			echo "<div class='notice error alert-box'>".sprintf(__( "Please try a different payment method. Admin need to install and activate wordpress plugin <a href='%s'>GoUrl Bitcoin Gateway for Wordpress</a> to accept Bitcoin/Altcoin Payments online.", GOURLAP), "https://gourl.io/bitcoin-wordpress-plugin.html")."</div>";
		}
		elseif (!$payments || !$options["defcoin"] || true === version_compare(GOURL_VERSION, '1.3.2', '<') ||
				(array_key_exists($order_currency, $coin_names) && !array_key_exists($order_currency, $payments)))
		{
			echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
			echo  "<div class='notice error alert-box'>".sprintf(__( 'Sorry, but there was an error processing your order. Please try a different payment method or contact us if you need assistance (GoUrl Bitcoin Plugin not configured / %s not activated).', GOURLAP ), (!$payments || !$options["defcoin"] || !isset($coin_names[$order_currency]) ? "GoUrl Bitcoin" : ucfirst($coin_names[$order_currency])))."</div>";
		}
		else
		{
			$plugin			= "gourlappthemes";
			$amount 		= $order_total;
			$currency 		= $order_currency;
			$orderID		= ($escrow ? "escrow" : "order") . $order_id;
			$period			= "NOEXPIRY";
			$language		= $options["deflang"];
			$coin 			= $coin_names[$options["defcoin"]];
			$affiliate_key 	= "gourl";
			$crypto			= array_key_exists($currency, $coin_names);
	
			if (!$userID && !$escrow) $userID = "guest";
	
	
			if (!$userID)
			{
				echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
				echo "<div align='center'><a href='".wp_login_url(get_permalink())."'>
						<img style='border:none;box-shadow:none;' title='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURLAP )."' vspace='10'
						src='".$gourl->box_image()."' border='0'></a></div>";
			}
			elseif ($amount <= 0)
			{
				echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
				echo "<div class='notice error alert-box'>". sprintf(__( "This order's amount is '%s' - it cannot be paid for. Please contact us if you need assistance.", GOURLAP ), $amount ." " . $currency)."</div>";
			}
			else
			{
					
				// Exchange (optional)
				// --------------------
				if ($currency != "USD" && !$crypto)
				{
					$amount = gourl_convert_currency($currency, "USD", $amount);
						
					if ($amount <= 0)
					{
						echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
						echo "<div class='notice error alert-box'>".sprintf(__( "Sorry, but there was an error processing your order. Please try later or use a different payment method. Cannot receive exchange rates for %s/USD from Google Finance", GOURLAP ), $currency)."</div>";
					}
					else $currency = "USD";
				}
					
				if (!$crypto) $amount = $amount * ($escrow ? $options["emultiplier2"] : $options["emultiplier"]);
					
					
					
				// Payment Box
				// ------------------
				if ($amount > 0)
				{
					// crypto payment gateway
					$result = $gourl->cryptopayments ($plugin, $amount, $currency, $orderID, $period, $language, $coin, $affiliate_key, $userID, $options["iconwidth"]);
	
					if (!$result["is_paid"]) echo '<h2> &#160; ' . __( ($escrow ? 'Escrow. ' : '') . 'Pay Now -', GOURLAP ) . '</h2><br>' . PHP_EOL;
					else echo "<br><br>";
	
					if ($result["error"]) echo "<div class='notice error alert-box'>".__( "Sorry, but there was an error processing your order. Please try a different payment method.", GOURLAP )."<br>".$result["error"]."</div>";
					else
					{
						// display payment box or successful payment result
						echo $result["html_payment_box"];
	
						// payment received
						if ($result["is_paid"])
						{
							echo "<div align='center'>";
							echo sprintf( __('%s Payment ID: #%s', GOURLAP), ucfirst($result["coinname"]), $result["paymentID"])."<br><br><br><br><br>";
							echo "<a href='".$order->get_return_url()."' class='button'>".__( "Continue", GOURLAP )."</a><br><br><br>";
							echo "</div>";
						}
					}
				}
			}
		}
	
		echo "<br><br>";
	
		return true;
	}


	
	/*
	 * 8. Initialize
	*/
	gourl_app_currency();
	appthemes_register_gateway( 'APP_Gourl' );
	add_action( 'init', 'appthemes_init_gourl_escrow', 15 );



	} // end gourl_app_gateway_load()                

} 
