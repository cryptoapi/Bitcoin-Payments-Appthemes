<?php
/*
Plugin Name: 		GoUrl AppThemes - Bitcoin Payments for Classipress, Vantage, JobRoller, etc
Plugin URI: 		https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html
Description: 		Provides a <a href="https://gourl.io">GoUrl.io</a> Bitcoin/Altcoins Payment Gateway for all <a href="http://www.appthemes.com/themes/">AppThemes.com Premium Themes</a> - Classipress, Vantage, JobRoller, Clipper, Taskerr, HireBee, Ideas, Quality Control, etc. Support product prices in USD/EUR/etc and in Bitcoin/Altcoins directly; sends the amount straight to your business Bitcoin/Altcoin wallet. Convert your USD/EUR/etc prices to cryptocoins using Google/Bitstamp/Cryptsy Live Exchange Rates. Accept Bitcoin, Litecoin, Speedcoin, Dogecoin, Paycoin, Darkcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin payments online. No Chargebacks, Global, Secure. All in automatic mode.
Version: 			1.0.1
Author: 			GoUrl.io
Author URI: 		https://gourl.io
License: 			GPLv2
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: 	https://github.com/cryptoapi/Bitcoin-Payments-AppThemes
*/


if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly in wordpress

add_action( 'init', 'gourl_app_gateway_load', 1);


function gourl_app_gateway_load() 
{
	
	// AppThemes compatible product required
	if (!class_exists('APP_Gateway') || !current_theme_supports('app-payments')) return;

	define( 'GOURLAP', 'appthemes-gourl');
	
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
				$details = array('symbol' => $k, 'name' => __("Cryptocurrency", GOURLAP)." - ".__(ucfirst($v), GOURLAP) );
	
				APP_Currencies::add_currency( $k, $details );
			}
		}
	
		return true;
	}
	
	
	
	
	

	/*
	 *	3. Payment Gateway Appthemes Class 
	 */
	class APP_Gourl extends APP_Gateway
	{
		protected $options;
	
		private $payments 			= array();
		private $languages 			= array();
		private $coin_names			= array();
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
					'admin' 	=> __( 'Gourl Bitcoin', GOURLAP ),
					'dropdown' 	=> $title
			) );
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
			$description   = "<a href='https://gourl.io'><img style='float:left; margin-right:15px' src='".plugin_dir_url( __FILE__ )."gourlpayments.png'></a>";
			$description  .= __( '<a target="_blank" href="https://gourl.io/bitcoin-appthemes-classipress-jobroller-vantage-etc.html">Plugin Homepage &#187;</a>', GOURLAP ) . "<br>";
			$description  .= __( '<a target="_blank" href="https://github.com/cryptoapi/Bitcoin-Payments-Appthemes">Plugin on Github - 100% Free Open Source &#187;</a>', GOURLAP ) . "<br><br>";
	
	
			if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
			{
				if (true === version_compare(GOURL_VERSION, '1.2.7', '<'))
				{
					$description .= '<div class="error"><p>' .sprintf(__( '<b>Your GoUrl Bitcoin Gateway <a href="%s">Main Plugin</a> version is too old. Requires 1.2.7 or higher version. Please <a href="%s">update</a> to latest version.</b>  &#160; &#160; &#160; &#160; Information: &#160; <a href="https://gourl.io/bitcoin-wordpress-plugin.html">Plugin Homepage</a> &#160; &#160; &#160; <a href="https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/">WordPress.org Plugin Page</a>', GOURLAP ), GOURL_ADMIN.GOURL, $this->mainplugin_url).'</p></div>';
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
				$coins 	= "";
				$url	= $this->mainplugin_url;
				$url2	= $url;
				$url3	= $url;
				$text 	= __( '<b>Please install GoUrl Bitcoin Gateway WP Plugin &#187;</b>', GOURLAP );
	
				$description .= '<div class="error"><p>' .sprintf(__( '<b>You need to install GoUrl Bitcoin Gateway Main Plugin also. Go to - <a href="%s">Bitcoin Gateway plugin page</a></b> &#160; &#160; &#160; &#160; Information: &#160; <a href="https://gourl.io/bitcoin-wordpress-plugin.html">Plugin Homepage</a> &#160; &#160; &#160; <a href="https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/">WordPress.org Plugin Page</a> ', GOURLAP ), $this->mainplugin_url).'</p></div>';
			}
				
			$description .= __( 'If you use multiple stores/sites online, please create separate <a target="_blank" href="https://gourl.io/editrecord/coin_boxes/0">GoUrl Payment Box</a> (with unique payment box public/private keys) for each of your stores/websites. Do not use the same GoUrl Payment Box with the same public/private keys on your different websites/stores.', GOURLAP ).'<br/>';
			$description .= sprintf(__( 'Accept %s payments online in Appthemes Premium Themes - Classipress, Taskerr, HireBee, Vantage, Clipper, JobRoller, Ideas, Quality Control, etc.', GOURLAP), ($this->coin_names?ucwords(implode(", ", $this->coin_names)):"Bitcoin, Litecoin, Speedcoin, Dogecoin, Paycoin, Darkcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin")).'<br/>';
	
	
			$fields = array(
					array(
							'name'			=> 'title',
							'title'       	=> __( 'Title', GOURLAP ),
							'type'        	=> 'text',
							'default'     	=> __( 'Bitcoin/Altcoins', GOURLAP ),
							'desc' 			=> '<br>'.__( 'Payment method title that the customer will see on your checkout', GOURLAP )
					),
					array(
							'name'			=> 'defcoin',
							'title' 		=> __('PaymentBox Default Coin', GOURLAP ),
							'type' 			=> 'select',
							'values' 		=> $this->payments,
							'default' 		=> key($this->payments),
							'desc' 			=> '<br>'.sprintf(__( 'Default Coin in Crypto Payment Box. &#160; Activated Payments : <a href="%s">%s</a>', GOURLAP ), $url, $text)
					),
					array(
							'name'			=> 'deflang',
							'title' 		=> __('PaymentBox Language', GOURLAP ),
							'type' 			=> 'select',
							'values' 		=> $this->languages,
							'default' 		=> 'en',
							'desc' 			=> '<br>'.__("Default Crypto Payment Box Localisation", GOURLAP)
					),
					array(
							'name'			=> 'emultiplier',
							'title' 		=> '<br>'.__('Exchange Rate Multiplier', GOURLAP ),
							'type' 			=> 'text',
							'default' 		=> '1.00',
							'desc' 			=> '<br>'.sprintf(__('The system uses the multiplier rate with today LIVE cryptocurrency exchange rates (which are updated every 30 minutes) when the transaction is calculating from a fiat currency (e.g. USD, EUR, etc) to %s. <br />Example: <b>1.05</b> - will add an extra 5%% to the total price in bitcoin/altcoins, <b>0.85</b> - will be a 15%% discount for the price in bitcoin/altcoins. Default: 1.00 ', GOURLAP ), $coins)
					),
					array(
							'name'			=> 'iconwidth',
							'title'       	=> __( 'Icon Width', GOURLAP ),
							'type'        	=> 'text',
							'default'     	=> "60px",
							'desc' 			=> '<br>'.__( 'Cryptocoin icons width in "Select Payment Method". Default 60px. Allowed: 30..250px', GOURLAP )
					)
			);
	
	
			if (isset($_POST["gateways"]["gourl"]["title"]) && strip_tags($_POST["gateways"]["gourl"]["title"])) update_option(GOURLAP."title", strip_tags($_POST["gateways"]["gourl"]["title"]));
			
			
			$arr = array(
					array(
							'title' => $title."<br><br><div style='font-size:13px;font-weight:normal;'>".$description."</div><br>",
							'fields' => $fields,
					)
			);
	
			return $arr;
		}
	
	
		
	
	
		
		
	
		/*
		 * 3.5
		*/
		private function validate_options ($options)
		{
			$options["emultiplier"] = trim(str_replace("%", "", $options["emultiplier"]));
			$options["iconwidth"] 	= trim(str_replace("px", "", $options["iconwidth"]));
		
			if (!$options["title"]) 							$options["title"] = __('Bitcoin/Altcoins', GOURLAP);
			if (!isset($this->languages[$options["deflang"]])) 	$options["deflang"]		= 'en';
		
			if (!$options["emultiplier"] || !is_numeric($options["emultiplier"]) || $options["emultiplier"] < 0.01) $options["emultiplier"] = 1;
			if (!is_numeric($options["iconwidth"]) || $options["iconwidth"] < 30 || $options["iconwidth"] > 250) 	$options["iconwidth"] 	= 60;
		
			if ($options["defcoin"] && $this->payments && !isset($this->payments[$options["defcoin"]])) $options["defcoin"] = key($this->payments);
			elseif (!$this->payments)																	$options["defcoin"]	= '';
			elseif (!$options["defcoin"])																$options["defcoin"]	= key($this->payments);
		
			return $options;
		}
		
		
		
		
		
	
		/*
		 * 3.6 Output bitcoin/altcoins payment box
		*/
		public function process( $order, $options )
		{
			global $gourl;
	
			$options = $this->validate_options($options);
			
			$userID			= $order->get_author();
			$order_id 		= $order->get_id();
			$order_currency = $order->get_currency();
			$order_total	= $order->get_total();
	
			if (!$order || !$order_id || !$order_total) throw new Exception('The GoUrl payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!');

			if (appthemes_get_order($order_id)->get_status() == APPTHEMES_ORDER_FAILED)
			{
				echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
				echo "<div class='error'>". __( 'This order&rsquo;s status is &ldquo;Failed&rdquo; &mdash; it cannot be paid for. Please contact us if you need assistance.', GOURLAP )."</div>";
			}
			elseif (!class_exists('gourlclass') || !defined('GOURL') || !is_object($gourl))
			{
				echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
				echo "<div class='error'>".__( "Please try a different payment method. Admin need to install and activate wordpress plugin 'GoUrl Bitcoin Gateway' (https://gourl.io/bitcoin-wordpress-plugin.html) to accept Bitcoin/Altcoin Payments online", GOURLAP )."</div>";
			}
			elseif (!$this->payments || !$options["defcoin"] || true === version_compare(GOURL_VERSION, '1.2.7', '<') ||
					(array_key_exists($order_currency, $this->coin_names) && !array_key_exists($order_currency, $this->payments)))
			{
				echo '<h2>' . __( 'Information', GOURLAP ) . '</h2>' . PHP_EOL;
				echo  "<div class='error'>".sprintf(__( 'Sorry, but there was an error processing your order. Please try a different payment method or contact us if you need assistance. (GoUrl Bitcoin Plugin not configured - <b>%s</b> not activated)', GOURLAP ),(!$this->payments || !$options["defcoin"]?"GoUrl Bitcoin":ucfirst($this->coin_names[$order_currency])))."</div>";
			}
			else
			{
				$plugin			= "gourlappthemes";
				$amount 		= $order_total;
				$currency 		= $order_currency;
				$orderID		= "order" . $order_id;
				$period			= "NOEXPIRY";
				$language		= $options["deflang"];
				$coin 			= $this->coin_names[$options["defcoin"]];
				$affiliate_key 	= "gourl";
				$crypto			= array_key_exists($currency, $this->coin_names);
	
				if (!$userID) $userID = "guest";
	
	
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
					echo "<div class='error'>". sprintf(__( 'This order&rsquo;s amount is &ldquo;%s&rdquo; &mdash; it cannot be paid for. Please contact us if you need assistance.', GOURLAP ), $amount ." " . $currency)."</div>";
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
							echo "<div class='error'>".sprintf(__( 'Sorry, but there was an error processing your order. Please try later or use a different payment method. Cannot receive exchange rates for %s/USD from Google Finance', GOURLAP ), $currency)."</div>";
						}
						else $currency = "USD";
					}
					
					if (!$crypto) $amount = $amount * $options["emultiplier"];
					
					
					
					// Payment Box
					// ------------------
					if ($amount > 0)
					{
						// crypto payment gateway
						$result = $gourl->cryptopayments ($plugin, $amount, $currency, $orderID, $period, $language, $coin, $affiliate_key, $userID, $options["iconwidth"]);
	
						if (!$result["is_paid"]) echo '<h2>' . __( 'Pay Now', GOURLAP ) . '</h2>' . PHP_EOL;
						else echo "<br>";
	
						if ($result["error"]) echo "<div class='error'>".__( "Sorry, but there was an error processing your order. Please try a different payment method.", GOURLAP )."<br/>".$result["error"]."</div>";
						else
						{
							// display payment box or successful payment result
							echo $result["html_payment_box"];
	
							// payment received
							if ($result["is_paid"]) 
							{	
								echo "<div align='center'>" . sprintf( __('%s Payment ID: #%s', GOURLAP), ucfirst($result["coinname"]), $result["paymentID"]) . "</div><br>";
							}
						}
					}	
				}
			}
	
			echo "<br>";
	
			return true;
		}
	
	}


	/*
	 * 4. Initialize Gateway
	*/
	gourl_app_currency();
	appthemes_register_gateway( 'APP_Gourl' );
	
	
	
	
	

	/*
	 *  5. Instant Payment Notification Function - pluginname."_gourlcallback"
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
		// Security
    	if (!in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) return false;
    	if (strpos($order_id, "order") === 0) $order_id = substr($order_id, 5); else return false;
    	if (!$user_id || $payment_details["status"] != "payment_received") return false;
    	
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
    		$order->log(sprintf(__('%s Payment Received<br>%s<br><a href="%s">Payment id %s</a> &#160;<br>', GOURLAP), $coinName, $amount, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID));

    		update_post_meta( $order_id, 'coinname', 	$coinName);
    		update_post_meta( $order_id, 'amount', 		$payment_details["amount"] . " " . $payment_details["coinlabel"] );
    		update_post_meta( $order_id, 'userid', 		$payment_details["userID"] );
    		update_post_meta( $order_id, 'country', 	get_country_name($payment_details["usercountry"]) );
    		update_post_meta( $order_id, 'tx', 			$payment_details["tx"] );
    		update_post_meta( $order_id, 'confirmed', 	$confirmed );
    		update_post_meta( $order_id, 'details', 	$payment_details["paymentLink"] );
    	}
    	
    	
    	// b. Existing Payment confirmed (6+ confirmations)
    	if ($payment_details["is_confirmed"]) 
    	{
    		update_post_meta( $order_id, 'confirmed', $confirmed );
    		$order->log(sprintf(__('%s Payment id <a href="%s">%s</a> Confirmed<br>', GOURLAP), $coinName, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID));
    	}
    	
    	
    	// c. Change order status to completed    
    	if ($order->get_status() != APPTHEMES_ORDER_COMPLETED && $order->get_status() != APPTHEMES_ORDER_ACTIVATED) $order->complete();

    	
		return true;
	}
	
}

