<?php
/*
Plugin Name: Listeo QR Handler
Plugin URI: https://www.kleise.gr
Description: QR Codes, Check-in and Google Calendar Functionalities
Version: 1.0
Author: Chris Mask
Author URI:
License:
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Setup the ajax calls
add_action( 'wp_ajax_add_checkin', 'rdmrj_ajax_add_checkin' );
add_action( 'wp_ajax_nopriv_add_checkin', 'rdmrj_ajax_add_checkin' );

function rdmrj_ajax_add_checkin(){
    global $wpdb;
    $parms = $_POST['check'];
    $parms = urldecode($parms);
	$parms = str_replace(" ", "+", $parms);
    $parmsclear = openssl_decrypt($parms, "AES-128-ECB", 'wehavethiskey');
    $userid = strtok( $parmsclear, '-');
    $orderid = strtok ( '- ');
    $table = $wpdb->prefix . 'bookings_calendar';
    $data = array(
      'checked_in' => 1,
    );
    $format = array('%s', '%s', '%s');
    $result = $wpdb->update($table, $data, array('order_id' => $orderid));
    echo json_encode($result);
    wp_die();
}

    // Activation
    // We need just one table with 3 fields. It'll always be tidy so at this
    // version while the first users are testing it we don't have a corresponding
    // deactivate. This is because somebody might want to later reactivate without
    // losing any checkins that have been made. If people adopt this plugin I will
    // revisit this but this table is going to be so small that it will never be a
    // problem if it remains
    // register_activation_hook( __FILE__, 'rdmrj_listeo_qrhandler_activate');
    // function rdmrj_listeo_qrhandler_activate(){
    //     global $wpdb;

    //     $collate = '';
    //     if ( $wpdb->has_cap( 'collation' ) ) {
    //         if ( ! empty( $wpdb->charset ) ) {
    //             $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
    //         }
    //         if ( ! empty( $wpdb->collate ) ) {
    //             $collate .= " COLLATE $wpdb->collate";
    //         }
    //     }

    //     require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    //     /**
    //      * Table for checkins
    //      */
    //     $sql = "
    //     CREATE TABLE {$wpdb->prefix}rdmrj_checkins (
    //       id bigint(20) UNSIGNED NOT NULL auto_increment,
    //       user_id bigint(20) NOT NULL,
    //       order_id bigint(20) NOT NULL,
    //       PRIMARY KEY  (id)
    //     ) $collate;
    //     ";

    //     dbDelta( $sql );
    // }

   // The plugin requires that a shortcode is placed on a page located at /checkin
   // We could provide an admin screen so that site owners can choose their own destination
   // page but this is proof of concept so not bothering at this stage
   add_shortcode( 'rdmrj_event_checkin', 'rdmrj_event_checkin' );

   // We get the order and customer IDs as a parameter from the url
   // (always /checkin) and we display the customer's name or email and the name of the
   // event they are booked on and display them, along with a "checkin" button. When the
   // button is pressed we create a record in the checkin table after first checking that
   // one doesn't already exist
   function rdmrj_event_checkin(){

    // get the url
    $wither = $_GET['check'];
    // Unencrypt the params passed
    $wither = str_replace(" ", "+", $wither);
    $parms = openssl_decrypt($wither, "AES-128-ECB", 'wehavethiskey');
    $userid = strtok( $parms, '-');
    $orderid = strtok ( '- ');
    $user_data = get_userdata($userid);
    $order = wc_get_order($orderid);

    error_log($parms);
    error_log(serialize($order));
    if(!$order){
      return;
    }
    $theitems = $order->get_items();
    $product = reset($theitems)->get_product();

    global $wpdb;
    $current_user = wp_get_current_user();
	$thebookingid = get_post_meta($orderid, 'booking_id', true);
    $booking_data = $wpdb -> get_row( 'SELECT * FROM `'  . $wpdb->prefix .  'bookings_calendar` WHERE `id`=' . esc_sql( $thebookingid ), 'ARRAY_A' );

    if($current_user && isset($current_user->ID) && $booking_data['owner_id'] == $current_user->ID)
	{
    if( $booking_data['checked_in'] != 0){
        // checkin found to exist so get the details for display

        ?>
<div class="main-check-container" style="margin-top:20px;">
                	<div class="check-container">
                		<div class="check-background">
                			<svg viewBox="0 0 65 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                				<path d="M7 25L27.3077 44L58.5 7" stroke="white" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
                			</svg>
                		</div>
                	</div>
                </div>
			<h3 style="text-align:center; margin-top:5px;"><?php esc_html_e('Checked-In','listeo'); ?>!</h3>
            <div class="list-box-listing bookings" style="max-width: 500px;text-align:center;margin:0px;margin-left: auto;margin-right: auto;margin-top:25px;padding: 15px; background: #f6f6f6; border-radius:20px;">
        <?php booking_order_details($orderid); ?>
        </div>
        <?php
    } else {
 	// If Check-In hasnt happened.
    ?>

<div class="notification woo-summary" style="background:white;margin-top:0px;padding: 10px;">


    <form method="POST" action="#" class="form-inline" style="">
    <button class="button" id="savebtn" style="margin-bottom:20px; background:#6de6a6;margin-top:-50px;" type="button">Check In <i class="fa fa-check" style="font-size:15px;color:white;margin-left:6px; padding-right: 0px;"></i> </button>
		<input type="hidden" name="complete" id="complete" value="<?php echo str_replace(" ", "+", $_GET['check']); ?>" />
		<div class="list-box-listing bookings" style="max-width: 500px;text-align:center;margin:0px;margin-left: auto;margin-right: auto;padding: 15px; background: #f6f6f6; border-radius:20px;">
		<?php booking_order_details($orderid); ?>
		</div>
    </form>
</div>
<script>

var doneonce = 0;
var check = document.getElementById("complete").value;
  jQuery("#savebtn").click(function(){
    if (doneonce > 0){
        alert("more");
        return;
    };

    jQuery.ajax({
        url: "../../wp-admin/admin-ajax.php?action=add_checkin",
        method: "POST",
        data: {
          check: check,
        },
        error: function(response){
            alert(response);
        },
        success: function (response) {
          if (response == 1){
            //alert("Check-in completed");
            var thebtn = document.getElementById('savebtn');
            thebtn.disabled = true;
			location.reload();
          } else if (response == 'false'){
            alert("here with a false " + response);
          } else {
            alert("Call support, unexpected error " + response);
          }
      },
    });
});

</script>

<?php
   }
	}else{ ?>
		<h4 style="text-align:center; margin-top:25px;"><?php esc_html_e('Only the owner of this listing can check you in','listeo'); ?></h4>
		<?php
	}
}

// We won't add anything to the order meta as it already contains all we need, so we are going
// to simply hook into the email notification of woocommerce and display the QR code on the
// emails that go out to the customer and admin. We only want codes to be added to bookings
// and not any other kind of orders that the site might process, so we check that the product
// being dealt with has the product category of Listeo booking assigned to it
function rdmrj_action_woocommerce_email( $order, $sent_to_admin, $plain_text, $email ){

    // Before we do anything we check how many products are on this order
    // as a booking will always have only one. If we find anything other than one
    // we quit now
    $theitems = $order->get_items();
    if(count($theitems) != 1){
        return;
    }

    // Now check that the product being ordered is a Listeo booking and exit if
    // that's not the case
    $product = reset($theitems)->get_product();
    $product_category_ids  = $product->get_category_ids();
    $myterm = get_term_by('id', reset($product_category_ids), 'product_cat');
    if( $myterm->name != 'Listeo booking' ){
        error_log("Exiting because not a listeo booking");
        return;
    }

    // Still here so we grab the necessary ids
    $theuserid = $order->get_user_id();
    $theorderid = $order->id;

    // We generate the QR to call the checkin page using orderid and userid as params but we encrypt them so that
    // we have an unreadable string to cut the chances of tampering
    $urlparams = $theuserid . '-' . $theorderid;
    $urlparamsencrypted = openssl_encrypt($urlparams, "AES-128-ECB", 'wehavethiskey');
    $urlfriendly = urlencode($urlparamsencrypted);
    $urlfriendly = str_replace("+", "%2B", $urlfriendly);
    ?>
	<a href="<?php echo getGoogleCalendarLink($booking_data ); ?>" class="button" style="font-weight: 600;border-radius: 50px;border: 1px solid #2e2e2e;box-shadow: 0px 0px 9px 0px black;color: #2e2e2e;background: #656565;padding: 8px 10px;"><?php esc_html_e('Add to','listeo_core'); ?><img style="height:18px;margin-left:4px;"src="<?php echo plugin_dir_url( __FILE__ ) . 'googlecalendar.png'; ?>"/></a>
    <div class="order_data_column">
        <h4><?php esc_html_e('You can present this QR Code at your arrival.','listeo'); ?></h4>
        <?php
            echo '<img title=" Present this code when you arrive at the event" src="https://chart.googleapis.com/chart?chs=300x300&chco=656565,121212&chf=bg,s,121212&choe=UTF-8&cht=qr&chl=' . get_site_url() . '/checkin/?check=' . $urlfriendly . '"  />';
        ?>
    </div>
<?php

}
add_action( 'woocommerce_email_after_order_table', 'rdmrj_action_woocommerce_email', 10, 4 );

// Function that prints Booking data, Google Calendar and QR Code out of Woocommerce Order
function booking_order_details($orderid) {
    global $wpdb;
    $order = wc_get_order($orderid);
    $userid = $order->get_user_id();
    $user_data = get_userdata($userid);

	$theitems = $order->get_items();
    if(count($theitems) != 1){
        return;
    }
    // Now check that the product being ordered is a Listeo booking and exit if
    // that's not the case
    $product = reset($theitems)->get_product();
    $product_category_ids  = $product->get_category_ids();
    $myterm = get_term_by('id', reset($product_category_ids), 'product_cat');
    if( $myterm->name != 'Listeo booking' ){
        error_log("Exiting because not a listeo booking");
        return;
    }
    $thebookingid = get_post_meta($orderid, 'booking_id', true);
    $booking_data = $wpdb -> get_row( 'SELECT * FROM `'  . $wpdb->prefix .  'bookings_calendar` WHERE `id`=' . esc_sql( $thebookingid ), 'ARRAY_A' );
	$current_user = wp_get_current_user();

	?>
	<div class="listing-item-container">
			<div class="inner" style="transform: unset;">
				<?php if(is_checkout() && !empty( is_wc_endpoint_url('order-received') ) ) {
					if($current_user && isset($current_user->ID) && $userid = $order->get_user_id() != $current_user->ID){ return;}
				$image = wp_get_attachment_image_src(get_post_thumbnail_id($booking_data['listing_id']), 'large', false);
		 		 ?><div class="listing-item" style="margin-bottom: 20px;">
				<img src="<?php echo $image[0]; ?>" alt="">
				</div>
				<?php echo get_avatar($booking_data['owner_id'],56, '', '', array('extra_attr'=>'style="border-radius:8px;"'));?>
				<h3 id="title" >
		<a href="<?php echo get_permalink($booking_data['listing_id']); ?>" style="color: #6a6a6a;"><?php echo get_the_title($booking_data['listing_id']); ?> </a> </h3>
					<?php } else {?>
				<img src="<?php echo get_avatar_url($userid);  ?>" style="border-radius:8px;margin-top:5px;"width="50" height="50">
					<h3 id="title" style="margin-top:15px;margin-bottom:15px;font-size:18px;">
					<a href="<?php echo get_permalink($userid); ?>" style="color: #6a6a6a;"><?php echo $user_data->first_name .' '.$user_data->last_name; ?> </a> </h3>

				<div class="inner-booking-list">
					<h5><?php esc_html_e('Listing:', 'listeo_core'); ?></h5>
					<ul class="booking-list">
						<li class="highlighted" id="details">
							<?php printf(get_the_title($booking_data['listing_id'])) ?>
						</li>
					</ul>
				</div>
				<?php  } ?>

				<div class="inner-booking-list">
					<h5><?php esc_html_e('Booking Date:', 'listeo_core'); ?></h5>
					<ul class="booking-list" style="color: #6a6a6a; font-weight:400px;">
						<?php
						//get post type to show proper date
						$listing_type = get_post_meta($booking_data['listing_id'],'_listing_type', true);

						if($listing_type == 'rental') { ?>
							<li class="highlighted" id="date"><?php echo date_i18n(get_option( 'date_format' ), strtotime($booking_data['date_start'])); ?> - <?php echo date_i18n(get_option( 'date_format' ), strtotime($booking_data['date_end'])); ?></li>

						<?php }
							else if($listing_type == 'service') {
						?>
							<li class="highlighted" id="date">
								<?php echo date_i18n(get_option( 'date_format' ), strtotime($booking_data['date_start'])); ?> <?php esc_html_e('at','listeo_core'); ?>
								<?php
									$time_start = date_i18n(get_option( 'time_format' ), strtotime($booking_data['date_start']));
									$time_end = date_i18n(get_option( 'time_format' ), strtotime($booking_data['date_end']));?>

								<?php echo esc_html($time_start); ?> <?php if($time_start != $time_end) echo '- '.$time_end; ?></li>

						<?php } else {
							//event ?>
							<li class="highlighted" id="date">
							<?php
							$meta_value = get_post_meta($booking_data['listing_id'],'_event_date',true);
							$meta_value_date = explode(' ', $meta_value,2);

							$meta_value_date[0] = str_replace('/','-',$meta_value_date[0]);
							$meta_value = date_i18n(get_option( 'date_format' ), strtotime($meta_value_date[0]));


							//echo strtotime(end($meta_value_date));
							//echo date( get_option( 'time_format' ), strtotime(end($meta_value_date)));
							if( isset($meta_value_date[1]) ) {
								$time = str_replace('-','',$meta_value_date[1]);
								$meta_value .= esc_html__(' at ','listeo_core');
								$meta_value .= date_i18n(get_option( 'time_format' ), strtotime($time));
							} echo $meta_value;

							$meta_value = get_post_meta($booking_data['listing_id'],'_event_date_end',true);
							if(isset($meta_value) && !empty($meta_value))  :

							$meta_value_date = explode(' ', $meta_value,2);

							$meta_value_date[0] = str_replace('/','-',$meta_value_date[0]);
							$meta_value = date_i18n(get_option( 'date_format' ), strtotime($meta_value_date[0]));


							//echo strtotime(end($meta_value_date));
							//echo date( get_option( 'time_format' ), strtotime(end($meta_value_date)));
							if( isset($meta_value_date[1]) ) {
								$time = str_replace('-','',$meta_value_date[1]);
								$meta_value .= esc_html__(' at ','listeo_core');
								$meta_value .= date_i18n(get_option( 'time_format' ), strtotime($time));

							} echo ' - '.$meta_value; ?>
							<?php endif; ?>
							</li>
						<?php }
						 ?>

					</ul>
				</div>
				<?php $details = json_decode($booking_data['comment']);
				if (
				 	(isset($details->childrens) && $details->childrens > 0)
				 	||
				 	(isset($details->adults) && $details->adults > 0)
				 	||
				 	(isset($details->tickets) && $details->tickets > 0)
				) { ?>
				<div class="inner-booking-list">
					<h5><?php esc_html_e('Booking Details:', 'listeo_core'); ?></h5>
					<ul class="booking-list">
						<li class="highlighted" id="details">
						<?php if( isset($details->childrens) && $details->childrens > 0) : ?>
							<?php printf( _n( '%d Child', '%s Children', $details->childrens, 'listeo_core' ), $details->childrens ) ?>
						<?php endif; ?>
						<?php if( isset($details->adults)  && $details->adults > 0) : ?>
							<?php printf( _n( '%d Guest', '%s Guests', $details->adults, 'listeo_core' ), $details->adults ) ?>
						<?php endif; ?>
						<?php if( isset($details->tickets)  && $details->tickets > 0) : ?>
							<?php printf( _n( '%d Ticket', '%s Tickets', $details->tickets, 'listeo_core' ), $details->tickets ) ?>
						<?php endif; ?>
						</li>
					</ul>
				</div>
				<?php } ?>

				<?php $friendly_address = get_post_meta( $booking_data['listing_id'], '_friendly_address', true );


				$currency_abbr = get_option( 'listeo_currency' );
				$currency_postion = get_option( 'listeo_currency_postion' );
				$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);
				$decimals = get_option('listeo_number_decimals',2);

				if($booking_data['price']): ?>
				<div class="inner-booking-list">
					<h5><?php esc_html_e('Price:', 'listeo_core'); ?></h5>
					<ul class="booking-list">
						<li class="highlighted" id="price">
							<?php if($currency_postion == 'before') { echo $currency_symbol.' '; }  ?>
							<?php
							if(is_numeric($booking_data['price'])){
							 	echo number_format_i18n($booking_data['price'],$decimals);
							} else {
								echo esc_html($booking_data['price']);
							};

							 ?>
							<?php if($currency_postion == 'after') { echo ' '.$currency_symbol; }  ?></li>
					</ul>
				</div>
				<?php endif; ?>

				<?php if( isset($details->service) && !empty($details->service)) : ?>
					<div class="inner-booking-list">
						<h5><?php esc_html_e('Extra Services:', 'listeo_core'); ?></h5>
						<ul class="booking-list">
						<li class="highlighted" id="details">
							<?php echo listeo_get_extra_services_html($details->service); //echo wpautop( $details->service); ?>
						</li>
						</ul>
					</div>
				<?php endif; ?>
				<?php if( isset($details->message) && !empty($details->message)) : ?>
					<div class="inner-booking-list">
						<h5><?php esc_html_e('Message:', 'listeo_core'); ?></h5>
						<?php echo esc_html(stripslashes($details->message)); ?>
					</div>
				<?php endif; ?>
				<?php if(!empty($order->get_payment_method_title())) : ?>
					<div class="inner-booking-list">
						<h5><?php esc_html_e('Payment method:', 'listeo'); ?></h5>
						<ul class="booking-list">
						<li class="highlighted" id="details">
							<?php echo esc_html($order->get_payment_method_title()); ?>
						</li>
						</ul>
					</div>
				<?php endif; ?>


	<?php

	// Show QR Code only on Thank you Page (Not Check-In page)
	if ( is_checkout() && !empty( is_wc_endpoint_url('order-received') ) ) {
    // We generate the QR to call the checkin page using orderid and userid as params but we encrypt them so that
    // we have an unreadable string to cut the chances of tampering
    $urlparams = $userid . '-' . $orderid;
    $urlparamsencrypted = openssl_encrypt($urlparams, "AES-128-ECB", 'wehavethiskey');
    $urlfriendly = urlencode($urlparamsencrypted);
    $urlfriendly = str_replace("+", "%2B", $urlfriendly);
    ?>
	<a href="<?php echo getGoogleCalendarLink($booking_data ); ?>" class="button" style="border-radius:50px;border: 1px solid grey;background:#eeeeee!important;color:grey"><?php esc_html_e('Add to','listeo_core'); ?><img style="height:18px;margin-left:4px;"src="<?php echo plugin_dir_url( __FILE__ ) . 'googlecalendar.png'; ?>"/></a>
    <div class="order_data_column">
        <h4 style="margin-top: 30px;margin-bottom: 0px;color: #777;; font-size: 15px;"><?php esc_html_e('You can present this QR Code at your arrival.','listeo'); ?></h4>
        <?php
            echo '<img title=" Present this QR Code when you arrive at the event" src="https://chart.googleapis.com/chart?chs=300x300&chco=444444,f6f6f6&chf=bg,s,f6f6f6&choe=UTF-8&cht=qr&chl=' . get_site_url() . '/checkin/?check=' . $urlfriendly . '"  />';
        ?>
   			 </div>
			</div>
		</div>
		<?php

	}
}
add_action( 'woocommerce_thankyou', 'booking_order_details', 10, 1);

// Add to Google Calendar Link
function getGoogleCalendarLink($booking_data ) {
		$title = get_the_title($booking_data['listing_id']);
		//$start_date  = str_replace( '-', '', get_post_meta( $id_product, '_start_date_picker', true ) );
		$start_date  =	date("Ymd", strtotime($booking_data['date_start']));
		//$start_time  = str_replace( ':', '', get_post_meta( $id_product, '_start_time_picker', true ) );
		$start_time  = date("THis", strtotime($booking_data['date_start']));
		//$end_date    = str_replace( '-', '', get_post_meta( $id_product, '_end_date_picker', true ) );
		//if($time_start != $time_end)
		$end_date    = date("Ymd", strtotime($booking_data['date_end']));
		//$end_time    = str_replace( ':', '', get_post_meta( $id_product, '_end_time_picker', true ) );
		$end_time    = 	date("THis", strtotime($booking_data['date_end']));
		$start_time  = str_replace( 'UTC', '', $start_time );
		$end_time   = str_replace( 'UTC', '', $end_time );
		if($start_date==$end_date&&$start_time>$end_time){
			$end_date    = date("Ymd", strtotime($booking_data['date_end']. ' + 1 day'));
		}
		//$bookingdetails = json_decode($booking_data['comment']);
		//$description = listeo_get_extra_services_html($bookingdetails->service);
		$direction   = str_replace( ' ', '+', get_post_meta( $booking_data['listing_id'], '_friendly_address', true ));
		$text        = '';
		if ( ! empty( $title ) ) {
			$text = '&text=' . $title;
		}

		$dates = '';
		if ( ! empty( $start_date ) & ! empty( $end_date ) ) {
			$start_time = ( strlen( $start_time ) <= 3 ) ? '0' . $start_time : $start_time;
			$end_time   = ( strlen( $end_time ) <= 3 ) ? '0' . $end_time : $end_time;

			if($start_date==$end_date&&$start_time==$end_time){
				$dates = '&dates=' . $start_date . 'T' . $start_time;
			}
			elseif ( ! empty( $start_time ) & ! empty( $end_time ) ) {
				$dates = '&dates=' . $start_date . 'T' . $start_time . '/' . $end_date . 'T' . $end_time . '';
			}
			else {
				$dates = '&dates=' . $start_date . '/' . $end_date;
			}
		}

		$details = '';
		if ( ! empty( $description ) ) {
			$details = '&details=' . $description;
		}

		$location = '';
		if ( ! empty( $direction ) ) {
			$location = '&location=' . $direction;
		}

		$link = 'intent://calendar.google.com/calendar/render?action=TEMPLATE' . $text . $dates . $details . $location . '&sf=true&output=xml#Intent;scheme=https;package=com.google.android.calendar;end';

		return $link;

		}


?>
