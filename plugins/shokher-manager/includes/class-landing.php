<?php
/**
 * Product landing pages (/offer/<slug>/) with a one-page Cash-on-Delivery order form.
 */
defined( 'ABSPATH' ) || exit;

final class Shokher_Landing {

	const PT = 'sk_landing';

	/** Meta fields: key => [ label, type ] */
	public static function fields() {
		return array(
			'product'   => array( 'প্রোডাক্ট *', 'product' ),
			'badge'     => array( 'উপরের ছোট লেখা (ব্যাজ)', 'text' ),
			'headline'  => array( 'বড় শিরোনাম *', 'text' ),
			'sub'       => array( 'শিরোনামের নিচের লেখা', 'textarea' ),
			'cta'       => array( 'বাটনের লেখা', 'text' ),
			'benefits'  => array( 'সুবিধাগুলো (প্রতি লাইনে একটা)', 'textarea' ),
			'steps'     => array( 'কীভাবে ব্যবহার করবেন (প্রতি লাইনে একটা ধাপ)', 'textarea' ),
			'video'     => array( 'YouTube ভিডিও লিংক (ঐচ্ছিক)', 'text' ),
			'reviews'   => array( 'কাস্টমার রিভিউ (প্রতি লাইনে: নাম | রিভিউ)', 'textarea' ),
			'faq'       => array( 'প্রশ্নোত্তর (প্রতি লাইনে: প্রশ্ন | উত্তর)', 'textarea' ),
			'images'    => array( 'ছবি (খালি রাখলে প্রোডাক্টের ছবি)', 'images' ),
			'old_price' => array( 'আগের দাম দেখাতে চাইলে (৳, ঐচ্ছিক)', 'text' ),
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_ajax_skm_landing_order', array( __CLASS__, 'place_order' ) );
		add_action( 'wp_ajax_nopriv_skm_landing_order', array( __CLASS__, 'place_order' ) );
		add_action( 'admin_post_skm_save_landing', array( __CLASS__, 'save' ) );
	}

	public static function register() {
		register_post_type( self::PT, array(
			'labels'              => array( 'name' => 'Landing pages', 'singular_name' => 'Landing page' ),
			'public'              => true,
			'show_ui'             => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'offer', 'with_front' => false ),
			'supports'            => array( 'title' ),
		) );
		if ( get_option( 'skm_rewrite_ver' ) !== SKM_VER ) {
			flush_rewrite_rules( false );
			update_option( 'skm_rewrite_ver', SKM_VER );
		}
	}

	public static function get( $post_id, $key ) {
		return get_post_meta( $post_id, '_skl_' . $key, true );
	}

	private static function lines( $txt ) {
		return array_values( array_filter( array_map( 'trim', explode( "\n", (string) $txt ) ) ) );
	}

	private static function pairs( $txt ) {
		$out = array();
		foreach ( self::lines( $txt ) as $l ) {
			$p     = array_map( 'trim', explode( '|', $l, 2 ) );
			$out[] = array( $p[0], $p[1] ?? '' );
		}
		return $out;
	}

	/** Purchasable options for the form: [ [id, label, price, regular] ]. */
	public static function options( WC_Product $p ) {
		$opts = array();
		if ( $p->is_type( 'variable' ) ) {
			foreach ( $p->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( $v && $v->is_purchasable() && $v->is_in_stock() ) {
					$opts[] = array(
						'id'      => $vid,
						'label'   => implode( ', ', array_values( $v->get_variation_attributes( false ) ) ),
						'price'   => (float) $v->get_price(),
						'regular' => (float) $v->get_regular_price(),
					);
				}
			}
		} elseif ( $p->is_purchasable() && $p->is_in_stock() ) {
			$opts[] = array( 'id' => $p->get_id(), 'label' => $p->get_name(), 'price' => (float) $p->get_price(), 'regular' => (float) $p->get_regular_price() );
		}
		return $opts;
	}

	/** Delivery cost per district code, from WooCommerce shipping zones. */
	public static function delivery_table() {
		$out    = array();
		$states = WC()->countries->get_states( 'BD' );
		if ( ! is_array( $states ) ) {
			return $out;
		}
		foreach ( array_keys( $states ) as $code ) {
			$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'BD', 'state' => $code, 'postcode' => '', 'city' => '' ) ) );
			$cost = null;
			$free = null;
			foreach ( $zone->get_shipping_methods( true ) as $m ) {
				if ( 'flat_rate' === $m->id && null === $cost ) {
					$cost = (float) $m->get_option( 'cost' );
				}
				if ( 'free_shipping' === $m->id ) {
					$free = (float) $m->get_option( 'min_amount' );
				}
			}
			$out[ $code ] = array( 'cost' => (float) $cost, 'free' => $free, 'zone' => $zone->get_zone_name() );
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Frontend                                                            */
	/* ------------------------------------------------------------------ */

	public static function template( $tpl ) {
		if ( is_singular( self::PT ) ) {
			return __DIR__ . '/landing-template.php';
		}
		return $tpl;
	}

	public static function place_order() {
		$err = function ( $m ) {
			wp_send_json_error( array( 'message' => $m ) );
		};
		if ( ! check_ajax_referer( 'skm_landing', 'nonce', false ) ) {
			$err( 'পেজটা রিফ্রেশ করে আবার চেষ্টা করুন।' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['website'] ) ) { // Honeypot.
			$err( 'Error' );
		}
		$lid   = isset( $_POST['landing'] ) ? absint( $_POST['landing'] ) : 0;
		$opt   = isset( $_POST['option'] ) ? absint( $_POST['option'] ) : 0;
		$qty   = isset( $_POST['qty'] ) ? max( 1, min( 20, absint( $_POST['qty'] ) ) ) : 1;
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? preg_replace( '/\D/', '', wp_unslash( $_POST['phone'] ) ) : '';
		$addr  = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
		$state = isset( $_POST['district'] ) ? sanitize_text_field( wp_unslash( $_POST['district'] ) ) : '';
		$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		// phpcs:enable
		if ( 0 === strpos( $phone, '880' ) ) {
			$phone = substr( $phone, 2 );
		}
		if ( ! $name || ! $addr || ! $state ) {
			$err( 'নাম, ঠিকানা আর জেলা দিন।' );
		}
		if ( ! preg_match( '/^01[3-9]\d{8}$/', $phone ) ) {
			$err( 'সঠিক ১১ সংখ্যার মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।' );
		}
		$landing = get_post( $lid );
		$parent  = $landing && self::PT === $landing->post_type ? wc_get_product( (int) self::get( $lid, 'product' ) ) : null;
		if ( ! $parent ) {
			$err( 'প্রোডাক্ট পাওয়া যায়নি।' );
		}
		$valid = wp_list_pluck( self::options( $parent ), 'id' );
		if ( ! in_array( $opt, $valid, true ) ) {
			$err( 'এই প্যাকটা এখন স্টকে নেই।' );
		}
		$item = wc_get_product( $opt );
		if ( $item->managing_stock() && ! $item->has_enough_stock( $qty ) ) {
			$err( 'দুঃখিত, এত পিস স্টকে নেই।' );
		}
		$states = WC()->countries->get_states( 'BD' );
		if ( ! isset( $states[ $state ] ) ) {
			$err( 'জেলা বাছাই করুন।' );
		}
		$lock = 'skm_lo_' . md5( $phone . '|' . $opt );
		if ( get_transient( $lock ) ) {
			$err( 'এই নম্বর থেকে একটু আগেই অর্ডার হয়েছে। আমরা শীঘ্রই কল করবো।' );
		}

		$order = wc_create_order( array( 'created_via' => 'shokher-landing' ) );
		if ( is_wp_error( $order ) ) {
			$err( 'অর্ডার নেওয়া যায়নি, আবার চেষ্টা করুন।' );
		}
		$order->add_product( $item, $qty );
		$parts = preg_split( '/\s+/', $name, 2 );
		$addr_fields = array(
			'first_name' => $parts[0],
			'last_name'  => $parts[1] ?? '',
			'address_1'  => $addr,
			'city'       => $states[ $state ],
			'state'      => $state,
			'country'    => 'BD',
			'phone'      => $phone,
		);
		$order->set_address( $addr_fields, 'billing' );
		unset( $addr_fields['phone'] );
		$order->set_address( $addr_fields, 'shipping' );
		$order->set_customer_note( $note );

		$table    = self::delivery_table();
		$row      = $table[ $state ] ?? array( 'cost' => 0, 'free' => null, 'zone' => '' );
		$subtotal = (float) $item->get_price() * $qty;
		$is_free  = null !== $row['free'] && $row['free'] > 0 && $subtotal >= $row['free'];
		$ship     = new WC_Order_Item_Shipping();
		$ship->set_method_title( $is_free ? 'Free Delivery (Offer)' : 'Home Delivery (' . $row['zone'] . ')' );
		$ship->set_method_id( $is_free ? 'free_shipping' : 'flat_rate' );
		$ship->set_total( $is_free ? 0 : $row['cost'] );
		$order->add_item( $ship );

		$gws = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		if ( isset( $gws['cod'] ) ) {
			$order->set_payment_method( $gws['cod'] );
		} else {
			$order->set_payment_method( 'cod' );
			$order->set_payment_method_title( 'Cash on Delivery' );
		}
		$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
		$order->set_customer_user_agent( wc_get_user_agent() );
		if ( is_user_logged_in() ) {
			$order->set_customer_id( get_current_user_id() );
		}
		$order->update_meta_data( '_sk_landing_id', $lid );
		$order->calculate_totals( false );
		$order->save();
		wc_reduce_stock_levels( $order->get_id() );
		$order->update_status( 'processing', 'ল্যান্ডিং পেজ থেকে অর্ডার: ' . get_the_title( $lid ) );
		set_transient( $lock, 1, 120 );

		wp_send_json_success( array(
			'redirect' => $order->get_checkout_order_received_url(),
			'value'    => (float) $order->get_total(),
			'order'    => $order->get_id(),
		) );
	}

	/* ------------------------------------------------------------------ */
	/* Admin                                                               */
	/* ------------------------------------------------------------------ */

	public static function admin_tab() {
		$id = isset( $_GET['lp'] ) ? absint( $_GET['lp'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		if ( null !== $id && isset( $_GET['lp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::admin_form( $id );
			return;
		}
		$pages = get_posts( array( 'post_type' => self::PT, 'post_status' => array( 'publish', 'draft' ), 'numberposts' => 100 ) );
		?>
		<p><a class="skm-btn" href="<?php echo esc_url( Shokher_Manager::url( 'landing', array( 'lp' => 0 ) ) ); ?>">➕ নতুন ল্যান্ডিং পেজ</a></p>
		<p class="skm-muted">প্রতিটা ল্যান্ডিং পেজে একটা প্রোডাক্ট থাকে, আর পেজের নিচেই অর্ডার ফর্ম (ক্যাশ অন ডেলিভারি)। লিংকটা ফেসবুক অ্যাডে দিন। অর্ডারগুলো "অর্ডার" ট্যাবে আসবে।</p>
		<div class="skm-panel skm-p0">
			<table class="skm-table">
				<thead><tr><th>পেজ</th><th>প্রোডাক্ট</th><th>লিংক</th><th>অর্ডার</th><th></th></tr></thead>
				<tbody>
				<?php
				if ( ! $pages ) {
					echo '<tr><td colspan="5" class="skm-empty">এখনো কোনো ল্যান্ডিং পেজ নেই।</td></tr>';
				}
				foreach ( $pages as $pg ) :
					$prod = wc_get_product( (int) self::get( $pg->ID, 'product' ) );
					$cnt  = count( wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'meta_key' => '_sk_landing_id', 'meta_value' => $pg->ID ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
					$link = get_permalink( $pg );
					?>
					<tr>
						<td><b><?php echo esc_html( $pg->post_title ); ?></b><?php echo 'draft' === $pg->post_status ? ' <span class="skm-pill is-yellow">লুকানো</span>' : ''; ?></td>
						<td><?php echo $prod ? esc_html( $prod->get_name() ) : '—'; ?></td>
						<td><input type="text" class="skm-in skm-link" readonly value="<?php echo esc_attr( $link ); ?>" onclick="this.select()"></td>
						<td><b><?php echo (int) $cnt; ?></b></td>
						<td class="skm-row-actions">
							<a class="skm-btn skm-btn-sm" href="<?php echo esc_url( Shokher_Manager::url( 'landing', array( 'lp' => $pg->ID ) ) ); ?>">✎ এডিট</a>
							<a class="skm-btn skm-btn-ghost skm-btn-sm" href="<?php echo esc_url( $link ); ?>" target="_blank">↗ দেখুন</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function admin_form( $id ) {
		$pg       = $id ? get_post( $id ) : null;
		$products = wc_get_products( array( 'limit' => 200, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
		$v        = function ( $k ) use ( $id ) {
			return $id ? self::get( $id, $k ) : '';
		};
		$imgs     = array_filter( array_map( 'absint', explode( ',', (string) $v( 'images' ) ) ) );
		?>
		<form class="skm-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'skm_save_landing' ); ?>
			<input type="hidden" name="action" value="skm_save_landing">
			<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<div class="skm-form-grid">
				<div class="skm-col-main">
					<div class="skm-panel">
						<h2><?php echo $pg ? 'ল্যান্ডিং পেজ এডিট' : 'নতুন ল্যান্ডিং পেজ'; ?></h2>
						<label class="skm-field"><span>পেজের নাম (শুধু আপনার জন্য) *</span><input type="text" name="title" required value="<?php echo esc_attr( $pg ? $pg->post_title : '' ); ?>" placeholder="যেমন: Hair Oil Comb Offer"></label>
						<label class="skm-field"><span>লিংকের শেষ অংশ</span><input type="text" name="slug" value="<?php echo esc_attr( $pg ? $pg->post_name : '' ); ?>" placeholder="hair-oil-comb"><small class="skm-muted"><?php echo esc_html( home_url( '/offer/' ) ); ?>…</small></label>
						<?php
						foreach ( self::fields() as $k => $f ) :
							if ( 'images' === $f[1] ) {
								continue;
							}
							?>
							<label class="skm-field"><span><?php echo esc_html( $f[0] ); ?></span>
								<?php if ( 'product' === $f[1] ) : ?>
									<select name="f[<?php echo esc_attr( $k ); ?>]" required>
										<option value="">— প্রোডাক্ট বাছুন —</option>
										<?php foreach ( $products as $p ) : ?>
											<option value="<?php echo (int) $p->get_id(); ?>" <?php selected( (int) $v( $k ), $p->get_id() ); ?>><?php echo esc_html( $p->get_name() ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php elseif ( 'textarea' === $f[1] ) : ?>
									<textarea name="f[<?php echo esc_attr( $k ); ?>]" rows="<?php echo in_array( $k, array( 'sub' ), true ) ? 2 : 5; ?>"><?php echo esc_textarea( $v( $k ) ); ?></textarea>
								<?php else : ?>
									<input type="text" name="f[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $v( $k ) ); ?>">
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="skm-col-side">
					<div class="skm-panel">
						<h2>ছবি</h2>
						<p class="skm-muted">খালি রাখলে প্রোডাক্টের ছবিগুলোই দেখাবে। প্রথম ছবিটা উপরে বড় করে দেখাবে।</p>
						<ul class="skm-imgs" data-skm-imgs>
							<?php foreach ( $imgs as $iid ) : ?>
								<li data-id="<?php echo (int) $iid; ?>"><?php echo wp_get_attachment_image( $iid, 'thumbnail' ); ?><button type="button" class="skm-x" data-skm-img-del>✕</button></li>
							<?php endforeach; ?>
						</ul>
						<input type="hidden" name="f[images]" value="<?php echo esc_attr( implode( ',', $imgs ) ); ?>" data-skm-imgs-input>
						<button type="button" class="skm-btn skm-btn-ghost skm-w100" data-skm-img-add>📷 ছবি যোগ করুন</button>
					</div>
					<div class="skm-panel">
						<label class="skm-field"><span>অবস্থা</span>
							<select name="status">
								<option value="publish" <?php selected( ! $pg || 'publish' === $pg->post_status ); ?>>✅ চালু</option>
								<option value="draft" <?php selected( $pg && 'draft' === $pg->post_status ); ?>>📝 লুকানো</option>
							</select>
						</label>
						<button type="submit" class="skm-btn skm-btn-lg skm-w100">💾 সেভ করুন</button>
						<?php if ( $pg ) : ?>
							<a class="skm-btn skm-btn-ghost skm-w100" href="<?php echo esc_url( get_permalink( $pg ) ); ?>" target="_blank">পেজ দেখুন ↗</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	public static function save() {
		if ( ! current_user_can( SKM_CAP ) || ! check_admin_referer( 'skm_save_landing' ) ) {
			wp_die( 'Not allowed' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$slug   = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$status = isset( $_POST['status'] ) && 'draft' === $_POST['status'] ? 'draft' : 'publish';
		$f      = isset( $_POST['f'] ) ? (array) wp_unslash( $_POST['f'] ) : array();
		// phpcs:enable
		$data = array( 'post_type' => self::PT, 'post_title' => $title ? $title : 'Landing page', 'post_status' => $status );
		if ( $slug ) {
			$data['post_name'] = $slug;
		}
		if ( $id ) {
			$data['ID'] = $id;
			$id         = wp_update_post( $data );
		} else {
			$id = wp_insert_post( $data );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			wp_safe_redirect( Shokher_Manager::url( 'landing', array( 'skm_msg' => 'error' ) ) );
			exit;
		}
		foreach ( self::fields() as $k => $def ) {
			$val = $f[ $k ] ?? '';
			if ( 'product' === $def[1] ) {
				$val = absint( $val );
			} elseif ( 'images' === $def[1] ) {
				$val = implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $val ) ) ) );
			} elseif ( 'textarea' === $def[1] ) {
				$val = sanitize_textarea_field( $val );
			} elseif ( 'video' === $k ) {
				$val = esc_url_raw( $val );
			} else {
				$val = sanitize_text_field( $val );
			}
			update_post_meta( $id, '_skl_' . $k, $val );
		}
		wp_safe_redirect( Shokher_Manager::url( 'landing', array( 'lp' => $id, 'skm_msg' => 'saved' ) ) );
		exit;
	}

	/** Helper for template. */
	public static function data( $id ) {
		$prod = wc_get_product( (int) self::get( $id, 'product' ) );
		$imgs = array_filter( array_map( 'absint', explode( ',', (string) self::get( $id, 'images' ) ) ) );
		if ( ! $imgs && $prod ) {
			$imgs = array_filter( array_merge( array( $prod->get_image_id() ), $prod->get_gallery_image_ids() ) );
		}
		$yt = '';
		if ( preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([\w-]{11})~', (string) self::get( $id, 'video' ), $m ) ) {
			$yt = $m[1];
		}
		return array(
			'product'  => $prod,
			'options'  => $prod ? self::options( $prod ) : array(),
			'images'   => array_values( $imgs ),
			'badge'    => self::get( $id, 'badge' ),
			'headline' => self::get( $id, 'headline' ),
			'sub'      => self::get( $id, 'sub' ),
			'cta'      => self::get( $id, 'cta' ) ? self::get( $id, 'cta' ) : 'এখনই অর্ডার করুন',
			'benefits' => self::lines( self::get( $id, 'benefits' ) ),
			'steps'    => self::lines( self::get( $id, 'steps' ) ),
			'reviews'  => self::pairs( self::get( $id, 'reviews' ) ),
			'faq'      => self::pairs( self::get( $id, 'faq' ) ),
			'youtube'  => $yt,
			'old'      => (float) preg_replace( '/[^0-9.]/', '', (string) self::get( $id, 'old_price' ) ),
		);
	}
}

Shokher_Landing::init();
