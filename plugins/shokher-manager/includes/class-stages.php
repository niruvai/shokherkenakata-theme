<?php
/**
 * Order stages: Received → Confirmed → Packing → Shipped → Delivered (+ Cancelled / Returned).
 */
defined( 'ABSPATH' ) || exit;

final class Shokher_Stages {

	const COURIERS = array( 'Steadfast', 'Pathao', 'RedX', 'Sundarban', 'SA Paribahan', 'eCourier', 'Paperfly', 'Own delivery', 'Other' );

	/** Main pipeline. key => [ wc statuses, bn label, en label, icon, next-button bn ] */
	public static function steps() {
		return array(
			'received'  => array( 'statuses' => array( 'pending', 'on-hold', 'processing' ), 'bn' => 'অর্ডার রিসিভড', 'en' => 'Order received', 'icon' => '🧾', 'next' => '' ),
			'confirmed' => array( 'statuses' => array( 'sk-confirmed' ), 'bn' => 'কনফার্মড', 'en' => 'Confirmed', 'icon' => '📞', 'next' => '📞 কনফার্ম করুন' ),
			'packing'   => array( 'statuses' => array( 'sk-packing' ), 'bn' => 'প্যাকিং', 'en' => 'Packing', 'icon' => '📦', 'next' => '📦 প্যাকিং শুরু' ),
			'shipped'   => array( 'statuses' => array( 'sk-shipped' ), 'bn' => 'কুরিয়ারে পাঠানো', 'en' => 'Shipped', 'icon' => '🚚', 'next' => '🚚 কুরিয়ারে দিন' ),
			'delivered' => array( 'statuses' => array( 'completed' ), 'bn' => 'ডেলিভারড', 'en' => 'Delivered', 'icon' => '✅', 'next' => '✅ ডেলিভারড' ),
		);
	}

	/** Board filters (top counters). key => [ statuses, bn label, css ] */
	public static function filters() {
		return array(
			'new'       => array( array( 'processing', 'pending' ), 'নতুন অর্ডার', 'is-new' ),
			'verify'    => array( array( 'on-hold' ), 'পেমেন্ট যাচাই', 'is-verify' ),
			'confirmed' => array( array( 'sk-confirmed' ), 'কনফার্মড', 'is-confirmed' ),
			'packing'   => array( array( 'sk-packing' ), 'প্যাকিং', 'is-packing' ),
			'shipped'   => array( array( 'sk-shipped' ), 'কুরিয়ারে', 'is-shipped' ),
			'delivered' => array( array( 'completed' ), 'ডেলিভারড', 'is-delivered' ),
			'cancelled' => array( array( 'cancelled', 'sk-returned', 'refunded', 'failed' ), 'বাতিল / রিটার্ন', 'is-cancelled' ),
		);
	}

	public static function bn_status( $status ) {
		$map = array(
			'pending'      => 'পেমেন্ট বাকি',
			'on-hold'      => 'পেমেন্ট যাচাই বাকি',
			'processing'   => 'নতুন অর্ডার',
			'sk-confirmed' => 'কনফার্মড',
			'sk-packing'   => 'প্যাকিং হচ্ছে',
			'sk-shipped'   => 'কুরিয়ারে পাঠানো',
			'completed'    => 'ডেলিভারড',
			'cancelled'    => 'বাতিল',
			'sk-returned'  => 'রিটার্ন',
			'refunded'     => 'রিফান্ড',
			'failed'       => 'ব্যর্থ',
		);
		return $map[ $status ] ?? wc_get_order_status_name( $status );
	}

	public static function active_statuses() {
		return array( 'pending', 'on-hold', 'processing', 'sk-confirmed', 'sk-packing', 'sk-shipped' );
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_statuses' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'report_statuses' ) );
		add_filter( 'woocommerce_analytics_actionable_order_statuses', array( __CLASS__, 'report_statuses' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( __CLASS__, 'cancellable' ) );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'log_change' ), 10, 4 );
		add_action( 'woocommerce_order_status_sk-returned', array( __CLASS__, 'restock' ) );
		add_action( 'wp_ajax_skm_order_update', array( __CLASS__, 'ajax_update' ) );
		add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'customer_timeline' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_status_colors' ) );
	}

	public static function register() {
		$defs = array(
			'wc-sk-confirmed' => 'Confirmed',
			'wc-sk-packing'   => 'Packing',
			'wc-sk-shipped'   => 'Shipped',
			'wc-sk-returned'  => 'Returned',
		);
		foreach ( $defs as $k => $label ) {
			register_post_status( $k, array(
				'label'                     => $label,
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ), // phpcs:ignore WordPress.WP.I18n
			) );
		}
	}

	public static function add_statuses( $st ) {
		$out = array();
		foreach ( $st as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'wc-processing' === $k ) {
				$out['wc-sk-confirmed'] = 'Confirmed';
				$out['wc-sk-packing']   = 'Packing';
				$out['wc-sk-shipped']   = 'Shipped';
			}
		}
		$out['wc-sk-returned'] = 'Returned';
		return $out;
	}

	public static function report_statuses( $s ) {
		if ( ! is_array( $s ) ) {
			return $s;
		}
		return array_values( array_unique( array_merge( $s, array( 'sk-confirmed', 'sk-packing', 'sk-shipped' ) ) ) );
	}

	public static function cancellable( $s ) {
		return array_merge( (array) $s, array( 'sk-confirmed' ) );
	}

	public static function restock( $order_id ) {
		$o = wc_get_order( $order_id );
		if ( $o && function_exists( 'wc_increase_stock_levels' ) ) {
			wc_increase_stock_levels( $o );
		}
	}

	public static function log_change( $id, $from, $to, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$log   = (array) $order->get_meta( '_sk_stage_log' );
		$log[] = array( 's' => $to, 't' => time(), 'u' => get_current_user_id() );
		$order->update_meta_data( '_sk_stage_log', array_slice( array_filter( $log ), -30 ) );
		$order->save_meta_data();
	}

	/** Pipeline index of an order (or -1 for cancelled/returned). */
	public static function index_of( $status ) {
		$i = 0;
		foreach ( self::steps() as $s ) {
			if ( in_array( $status, $s['statuses'], true ) ) {
				return $i;
			}
			$i++;
		}
		return -1;
	}

	private static function log_time( WC_Order $o, $statuses ) {
		$t = 0;
		foreach ( (array) $o->get_meta( '_sk_stage_log' ) as $row ) {
			if ( is_array( $row ) && in_array( $row['s'] ?? '', $statuses, true ) ) {
				$t = (int) $row['t'];
			}
		}
		return $t;
	}

	/* ------------------------------------------------------------------ */
	/* Admin board                                                         */
	/* ------------------------------------------------------------------ */

	public static function render_board() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$f = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : 'active';
		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		// phpcs:enable
		$filters = self::filters();

		echo '<div class="skm-stagebar">';
		$active_count = 0;
		foreach ( $filters as $k => $def ) {
			$c = count( wc_get_orders( array( 'limit' => -1, 'status' => $def[0], 'return' => 'ids', 'type' => 'shop_order' ) ) );
			if ( ! in_array( $k, array( 'delivered', 'cancelled' ), true ) ) {
				$active_count += $c;
			}
			printf(
				'<a class="skm-stage %s %s" href="%s"><b>%d</b><span>%s</span></a>',
				esc_attr( $def[2] ),
				$k === $f ? 'is-active' : '',
				esc_url( Shokher_Manager::url( 'orders', array( 'stage' => $k ) ) ),
				(int) $c,
				esc_html( $def[1] )
			);
		}
		echo '</div>';
		?>
		<form class="skm-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( Shokher_Manager::SLUG ); ?>"><input type="hidden" name="tab" value="orders">
			<a class="skm-btn <?php echo 'active' === $f && ! $q ? '' : 'skm-btn-ghost'; ?>" href="<?php echo esc_url( Shokher_Manager::url( 'orders' ) ); ?>">চলমান সব (<?php echo (int) $active_count; ?>)</a>
			<a class="skm-btn <?php echo 'all' === $f ? '' : 'skm-btn-ghost'; ?>" href="<?php echo esc_url( Shokher_Manager::url( 'orders', array( 'stage' => 'all' ) ) ); ?>">সব অর্ডার</a>
			<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="অর্ডার নম্বর / ফোন / নাম…">
			<button class="skm-btn skm-btn-ghost">খুঁজুন</button>
		</form>
		<p class="skm-muted">প্রতিটা অর্ডারে নিচের হলুদ বাটন চাপলে পরের ধাপে যাবে। কুরিয়ারের নাম, ট্র্যাকিং নম্বর আর নোট লিখে "সেভ" চাপুন। কাস্টমার "Track Order" পেজে ধাপগুলো দেখতে পাবে, কিন্তু আপনার নোট দেখবে না।</p>
		<?php
		$args = array( 'limit' => 60, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' );
		if ( 'all' !== $f ) {
			$args['status'] = isset( $filters[ $f ] ) ? $filters[ $f ][0] : self::active_statuses();
		}
		if ( $q ) {
			$num = absint( ltrim( $q, '#' ) );
			if ( $num && wc_get_order( $num ) ) {
				$orders = array( wc_get_order( $num ) );
			} else {
				unset( $args['status'] );
				$args['limit'] = 200;
				$orders        = array_filter( wc_get_orders( $args ), function ( $o ) use ( $q ) {
					$hay = strtolower( $o->get_billing_phone() . ' ' . $o->get_formatted_billing_full_name() . ' ' . $o->get_billing_email() );
					return false !== strpos( $hay, strtolower( $q ) );
				} );
			}
		} else {
			$orders = wc_get_orders( $args );
		}

		echo '<div class="skm-ocards">';
		if ( ! $orders ) {
			echo '<div class="skm-panel skm-empty">এই তালিকায় কোনো অর্ডার নেই।</div>';
		}
		foreach ( $orders as $o ) {
			if ( $o instanceof WC_Order ) {
				echo self::card( $o ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		echo '</div>';
	}

	public static function card( WC_Order $o ) {
		$status = $o->get_status();
		$idx    = self::index_of( $status );
		$steps  = self::steps();
		$keys   = array_keys( $steps );
		$phone  = $o->get_billing_phone();
		$states = WC()->countries->get_states( 'BD' );
		$dist   = is_array( $states ) && isset( $states[ $o->get_billing_state() ] ) ? $states[ $o->get_billing_state() ] : $o->get_billing_state();
		$addr   = trim( implode( ', ', array_filter( array( $o->get_billing_address_1(), $o->get_billing_city(), $dist ) ) ) );
		$courier = $o->get_meta( '_sk_courier' );
		$track   = $o->get_meta( '_sk_tracking' );
		$note    = $o->get_meta( '_sk_admin_note' );
		$trx     = array();
		foreach ( $o->get_meta_data() as $m ) {
			$d = $m->get_data();
			if ( is_scalar( $d['value'] ) && '' !== (string) $d['value'] && preg_match( '/(trans|trx|txn|acc_no|account_no|sender)/i', $d['key'] ) && 0 !== strpos( $d['key'], '_sk_' ) ) {
				$trx[] = ( preg_match( '/(trans|trx|txn)/i', $d['key'] ) ? 'TrxID' : 'নম্বর' ) . ': <b>' . esc_html( $d['value'] ) . '</b>';
			}
		}
		$closed = -1 === $idx;
		ob_start();
		?>
		<div class="skm-ocard <?php echo $closed ? 'is-closed' : ''; ?> st-<?php echo esc_attr( $status ); ?>" data-id="<?php echo (int) $o->get_id(); ?>">
			<div class="skm-ocard-head">
				<div>
					<a class="skm-oid" href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a>
					<span class="skm-badge st-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::bn_status( $status ) ); ?></span>
					<small class="skm-muted"><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'd M Y, h:i A' ) : '' ); ?></small>
				</div>
				<div class="skm-ototal"><?php echo wp_kses_post( wc_price( $o->get_total(), array( 'currency' => $o->get_currency() ) ) ); ?></div>
			</div>

			<div class="skm-ocard-body">
				<div class="skm-ocol">
					<div class="skm-oname"><?php echo esc_html( $o->get_formatted_billing_full_name() ); ?></div>
					<?php if ( $phone ) : ?><a class="skm-ophone" href="tel:<?php echo esc_attr( $phone ); ?>">📞 <?php echo esc_html( $phone ); ?></a><?php endif; ?>
					<?php if ( $addr ) : ?><div class="skm-oaddr">📍 <?php echo esc_html( $addr ); ?></div><?php endif; ?>
					<?php if ( $o->get_customer_note() ) : ?><div class="skm-onote">💬 <?php echo esc_html( $o->get_customer_note() ); ?></div><?php endif; ?>
				</div>
				<div class="skm-ocol">
					<?php foreach ( $o->get_items() as $it ) : ?>
						<div class="skm-oitem"><?php echo esc_html( $it->get_name() ); ?> <b>× <?php echo (int) $it->get_quantity(); ?></b></div>
					<?php endforeach; ?>
					<div class="skm-muted"><?php echo esc_html( $o->get_shipping_method() ); ?></div>
				</div>
				<div class="skm-ocol">
					<div><b><?php echo esc_html( $o->get_payment_method_title() ); ?></b></div>
					<?php if ( $trx ) : ?><div class="skm-trx"><?php echo wp_kses_post( implode( '<br>', array_unique( $trx ) ) ); ?></div><?php endif; ?>
				</div>
			</div>

			<?php if ( ! $closed ) : ?>
			<ol class="skm-steps">
				<?php
				$i = 0;
				foreach ( $steps as $key => $s ) :
					$t = self::log_time( $o, $s['statuses'] );
					?>
					<li class="<?php echo $i < $idx ? 'is-done' : ( $i === $idx ? 'is-current' : '' ); ?>">
						<span class="skm-dot"><?php echo esc_html( $s['icon'] ); ?></span>
						<span class="skm-step-l"><?php echo esc_html( $s['bn'] ); ?></span>
						<?php if ( $t && $i <= $idx ) : ?><small><?php echo esc_html( wp_date( 'd M, h:i A', $t ) ); ?></small><?php endif; ?>
					</li>
					<?php
					$i++;
				endforeach;
				?>
			</ol>
			<?php endif; ?>

			<div class="skm-ocard-foot">
				<div class="skm-oship">
					<select data-f="courier" class="skm-in">
						<option value="">কুরিয়ার বাছুন</option>
						<?php foreach ( self::COURIERS as $c ) : ?>
							<option <?php selected( $courier, $c ); ?>><?php echo esc_html( $c ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" data-f="tracking" class="skm-in" value="<?php echo esc_attr( $track ); ?>" placeholder="ট্র্যাকিং / কনসাইনমেন্ট নং">
					<input type="text" data-f="note" class="skm-in skm-in-note" value="<?php echo esc_attr( $note ); ?>" placeholder="নিজের নোট (কাস্টমার দেখবে না)">
					<button type="button" class="skm-btn skm-btn-ghost skm-btn-sm" data-skm-o-save>💾 সেভ</button>
				</div>
				<div class="skm-oacts">
					<?php
					if ( 'on-hold' === $status ) {
						echo '<span class="skm-hint">TrxID মিলিয়ে কনফার্ম করুন →</span>';
					}
					if ( ! $closed && $idx < count( $keys ) - 1 ) {
						$next = $steps[ $keys[ $idx + 1 ] ];
						printf( '<button type="button" class="skm-btn" data-skm-o-status="%s">%s</button>', esc_attr( 'wc-' . $next['statuses'][0] ), esc_html( $next['next'] ) );
					}
					if ( ! $closed && 'completed' !== $status ) {
						echo '<button type="button" class="skm-btn skm-btn-danger skm-btn-sm" data-skm-o-status="wc-cancelled" data-confirm="অর্ডারটা বাতিল করবেন?">✕ বাতিল</button>';
					}
					if ( in_array( $status, array( 'sk-shipped', 'completed' ), true ) ) {
						echo '<button type="button" class="skm-btn skm-btn-danger skm-btn-sm" data-skm-o-status="wc-sk-returned" data-confirm="প্রোডাক্ট রিটার্ন এসেছে? স্টকে ফেরত যোগ হবে।">↩ রিটার্ন</button>';
					}
					if ( $closed ) {
						echo '<button type="button" class="skm-btn skm-btn-ghost skm-btn-sm" data-skm-o-status="wc-processing" data-confirm="অর্ডারটা আবার চালু করবেন?">↺ আবার চালু</button>';
					}
					?>
					<select class="skm-in skm-in-more" data-skm-o-select title="সরাসরি স্ট্যাটাস বদলান">
						<option value="">অন্য ধাপ…</option>
						<?php foreach ( wc_get_order_statuses() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( self::bn_status( substr( $k, 3 ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function ajax_update() {
		check_ajax_referer( 'skm_ajax', 'nonce' );
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( 'not allowed', 403 );
		}
		$o = isset( $_POST['id'] ) ? wc_get_order( absint( $_POST['id'] ) ) : null;
		if ( ! $o ) {
			wp_send_json_error( 'missing' );
		}
		$courier = isset( $_POST['courier'] ) ? sanitize_text_field( wp_unslash( $_POST['courier'] ) ) : null;
		$track   = isset( $_POST['tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking'] ) ) : null;
		$note    = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : null;
		if ( null !== $courier ) {
			$o->update_meta_data( '_sk_courier', $courier );
		}
		if ( null !== $track ) {
			$o->update_meta_data( '_sk_tracking', $track );
		}
		if ( null !== $note ) {
			$o->update_meta_data( '_sk_admin_note', $note );
		}
		$o->save();

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		if ( $status && array_key_exists( $status, wc_get_order_statuses() ) && 'wc-' . $o->get_status() !== $status ) {
			$msg = 'ধাপ বদলানো হয়েছে: ' . self::bn_status( substr( $status, 3 ) );
			if ( 'wc-sk-shipped' === $status && $o->get_meta( '_sk_courier' ) ) {
				$msg .= ' (' . $o->get_meta( '_sk_courier' ) . ( $o->get_meta( '_sk_tracking' ) ? ' – ' . $o->get_meta( '_sk_tracking' ) : '' ) . ')';
			}
			$o->update_status( substr( $status, 3 ), $msg, true );
		}
		$o = wc_get_order( $o->get_id() );
		wp_send_json_success( array( 'html' => self::card( $o ) ) );
	}

	public static function admin_status_colors() {
		echo '<style>.order-status.status-sk-confirmed{background:#e0ecff;color:#1d4ed8}.order-status.status-sk-packing{background:#fff3c4;color:#8a6500}.order-status.status-sk-shipped{background:#e6e0ff;color:#5b21b6}.order-status.status-sk-returned{background:#fde2e1;color:#b91c1c}</style>';
	}

	/* ------------------------------------------------------------------ */
	/* Customer timeline (Track Order, My Account → View order, Thank you) */
	/* ------------------------------------------------------------------ */

	public static function customer_timeline( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$status = $order->get_status();
		$idx    = self::index_of( $status );
		echo '<section class="sk-track">';
		if ( -1 === $idx ) {
			echo '<p class="sk-track-closed">Order status: <b>' . esc_html( wc_get_order_status_name( $status ) ) . '</b></p></section>';
			return;
		}
		echo '<ol class="sk-track-steps">';
		$i = 0;
		foreach ( self::steps() as $s ) {
			$t = self::log_time( $order, $s['statuses'] );
			printf(
				'<li class="%s"><span class="sk-track-dot">%s</span><span class="sk-track-l">%s</span>%s</li>',
				$i < $idx ? 'is-done' : ( $i === $idx ? 'is-current' : '' ),
				$i <= $idx ? '✓' : (int) ( $i + 1 ),
				esc_html( $s['en'] ),
				$t && $i <= $idx ? '<small>' . esc_html( wp_date( 'd M, h:i A', $t ) ) . '</small>' : ''
			);
			$i++;
		}
		echo '</ol>';
		$courier = $order->get_meta( '_sk_courier' );
		$track   = $order->get_meta( '_sk_tracking' );
		if ( $courier || $track ) {
			echo '<p class="sk-track-courier">🚚 Courier: <b>' . esc_html( $courier ) . '</b>' . ( $track ? ' · Tracking no: <b>' . esc_html( $track ) . '</b>' : '' ) . '</p>';
		}
		echo '</section>';
		?>
		<style>
		.sk-track{background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin:0 0 20px}
		.sk-track-steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(5,1fr);position:relative}
		.sk-track-steps li{text-align:center;position:relative;font-size:13px;color:#888}
		.sk-track-steps li::before{content:"";position:absolute;top:17px;left:-50%;width:100%;height:4px;background:#eee;z-index:0}
		.sk-track-steps li:first-child::before{display:none}
		.sk-track-steps li.is-done::before,.sk-track-steps li.is-current::before{background:var(--sk-primary,#FFC107)}
		.sk-track-dot{position:relative;z-index:1;display:grid;place-items:center;width:38px;height:38px;margin:0 auto 6px;border-radius:50%;background:#eee;font-weight:800;color:#666}
		.sk-track-steps li.is-done .sk-track-dot{background:var(--sk-primary,#FFC107);color:#111}
		.sk-track-steps li.is-current .sk-track-dot{background:#111;color:var(--sk-primary,#FFC107);box-shadow:0 0 0 5px rgba(255,193,7,.35)}
		.sk-track-steps li.is-current .sk-track-l,.sk-track-steps li.is-done .sk-track-l{color:#111;font-weight:700}
		.sk-track-l{display:block;line-height:1.3}
		.sk-track-steps small{display:block;color:#999;font-size:11px}
		.sk-track-courier{margin:14px 0 0;background:#fffbea;border-radius:10px;padding:10px 12px}
		@media(max-width:600px){.sk-track-steps{grid-template-columns:1fr;gap:10px}.sk-track-steps li{display:flex;align-items:center;gap:10px;text-align:left}.sk-track-steps li::before{display:none}.sk-track-dot{margin:0;flex:none}}
		</style>
		<?php
	}
}

Shokher_Stages::init();
