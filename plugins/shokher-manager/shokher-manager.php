<?php
/**
 * Plugin Name: Shokher Shop Manager
 * Description: সহজ দোকান ম্যানেজার – এক পেজে প্রোডাক্ট আপলোড, দাম/অফার/স্টক এডিট, অর্ডার দেখা ও স্ট্যাটাস বদল, ডেলিভারি চার্জ ও ফ্রি ডেলিভারি অফার।
 * Version: 1.1.0
 * Author: Shokher Kenakata
 * Requires Plugins: woocommerce
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Text Domain: shokher-manager
 */

defined( 'ABSPATH' ) || exit;

define( 'SKM_VER', '1.1.0' );
define( 'SKM_URL', plugin_dir_url( __FILE__ ) );
define( 'SKM_CAP', 'edit_products' );

require_once __DIR__ . '/includes/class-stages.php';

final class Shokher_Manager {

	const SLUG = 'sk-shop';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_skm_save_product', array( __CLASS__, 'save_product' ) );
		add_action( 'admin_post_skm_save_delivery', array( __CLASS__, 'save_delivery' ) );
		add_action( 'admin_post_skm_delete_demo', array( __CLASS__, 'delete_demo' ) );
		add_action( 'wp_ajax_skm_save_row', array( __CLASS__, 'ajax_save_row' ) );
		add_action( 'wp_ajax_skm_order_status', array( __CLASS__, 'ajax_order_status' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $l ) {
			array_unshift( $l, '<a href="' . esc_url( self::url() ) . '">Open</a>' );
			return $l;
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Helpers                                                           */
	/* ---------------------------------------------------------------- */

	public static function url( $tab = 'dashboard', $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	private static function money( $v ) {
		return wc_price( (float) $v );
	}

	private static function notice() {
		if ( empty( $_GET['skm_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$msgs = array(
			'saved'    => array( 'success', 'প্রোডাক্ট সেভ হয়েছে ✔' ),
			'delivery' => array( 'success', 'ডেলিভারি সেটিংস সেভ হয়েছে ✔' ),
			'demo'     => array( 'success', 'ডেমো প্রোডাক্ট মুছে ফেলা হয়েছে ✔' ),
			'error'    => array( 'error', 'কিছু একটা সমস্যা হয়েছে, আবার চেষ্টা করুন।' ),
			'noname'   => array( 'error', 'প্রোডাক্টের নাম আর দাম দিতে হবে।' ),
		);
		$k = sanitize_key( wp_unslash( $_GET['skm_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $msgs[ $k ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible skm-notice"><p>%s</p></div>', esc_attr( $msgs[ $k ][0] ), esc_html( $msgs[ $k ][1] ) );
		}
	}

	private static function price_in( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$v = isset( $_POST[ $key ] ) ? wc_clean( wp_unslash( $_POST[ $key ] ) ) : '';
		$v = preg_replace( '/[^0-9.]/', '', (string) $v );
		return '' === $v ? '' : wc_format_decimal( $v );
	}

	/* ---------------------------------------------------------------- */
	/* Menu & assets                                                     */
	/* ---------------------------------------------------------------- */

	public static function menu() {
		add_menu_page( 'Shokher Shop', 'Shokher Shop', SKM_CAP, self::SLUG, array( __CLASS__, 'render' ), 'dashicons-store', 3 );
		add_submenu_page( self::SLUG, 'ড্যাশবোর্ড', 'ড্যাশবোর্ড', SKM_CAP, self::SLUG, array( __CLASS__, 'render' ) );
		add_submenu_page( self::SLUG, 'নতুন প্রোডাক্ট', '➕ নতুন প্রোডাক্ট', SKM_CAP, self::SLUG . '&tab=add', '__return_null' );
		add_submenu_page( self::SLUG, 'সব প্রোডাক্ট', 'সব প্রোডাক্ট', SKM_CAP, self::SLUG . '&tab=products', '__return_null' );
		add_submenu_page( self::SLUG, 'অর্ডার', 'অর্ডার', SKM_CAP, self::SLUG . '&tab=orders', '__return_null' );
		add_submenu_page( self::SLUG, 'ডেলিভারি ও অফার', 'ডেলিভারি ও অফার', SKM_CAP, self::SLUG . '&tab=delivery', '__return_null' );
	}

	public static function admin_bar( $bar ) {
		if ( ! current_user_can( SKM_CAP ) ) {
			return;
		}
		$bar->add_node( array( 'id' => 'skm', 'title' => '🛍 Shokher Shop', 'href' => self::url() ) );
		$bar->add_node( array( 'id' => 'skm-add', 'parent' => 'skm', 'title' => '➕ নতুন প্রোডাক্ট', 'href' => self::url( 'add' ) ) );
		$bar->add_node( array( 'id' => 'skm-orders', 'parent' => 'skm', 'title' => 'অর্ডার', 'href' => self::url( 'orders' ) ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'skm', SKM_URL . 'assets/admin.css', array(), SKM_VER );
		wp_enqueue_script( 'skm', SKM_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SKM_VER, true );
		wp_localize_script( 'skm', 'SKM', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'skm_ajax' ),
		) );
	}

	/* ---------------------------------------------------------------- */
	/* Page shell                                                        */
	/* ---------------------------------------------------------------- */

	public static function render() {
		if ( ! current_user_can( SKM_CAP ) ) {
			wp_die( 'Not allowed' );
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="wrap"><h1>Shokher Shop</h1><p>WooCommerce চালু থাকতে হবে।</p></div>';
			return;
		}
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification
		$tabs = array(
			'dashboard' => 'ড্যাশবোর্ড',
			'add'       => '➕ নতুন প্রোডাক্ট',
			'products'  => 'সব প্রোডাক্ট',
			'orders'    => 'অর্ডার',
			'delivery'  => 'ডেলিভারি ও অফার',
		);
		echo '<div class="wrap skm">';
		echo '<div class="skm-head"><h1>🛍 Shokher Shop</h1><a class="skm-btn skm-btn-ghost" href="' . esc_url( home_url( '/' ) ) . '" target="_blank">সাইট দেখুন ↗</a></div><hr class="wp-header-end">';
		self::notice();
		echo '<nav class="skm-tabs">';
		foreach ( $tabs as $k => $label ) {
			printf( '<a class="%s" href="%s">%s</a>', $k === $tab || ( 'edit' === $tab && 'add' === $k ) ? 'is-active' : '', esc_url( self::url( $k ) ), esc_html( $label ) );
		}
		echo '</nav><div class="skm-body">';
		switch ( $tab ) {
			case 'add':
			case 'edit':
				self::tab_form();
				break;
			case 'products':
				self::tab_products();
				break;
			case 'orders':
				self::tab_orders();
				break;
			case 'delivery':
				self::tab_delivery();
				break;
			default:
				self::tab_dashboard();
		}
		echo '</div></div>';
	}

	/* ---------------------------------------------------------------- */
	/* Dashboard                                                         */
	/* ---------------------------------------------------------------- */

	private static function sales_since( $ts ) {
		$orders = wc_get_orders( array(
			'limit'        => -1,
			'status'       => array( 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-pending', 'wc-sk-confirmed', 'wc-sk-packing', 'wc-sk-shipped' ),
			'date_created' => '>=' . $ts,
			'return'       => 'objects',
		) );
		$sum = 0;
		foreach ( $orders as $o ) {
			$sum += (float) $o->get_total();
		}
		return array( count( $orders ), $sum );
	}

	private static function tab_dashboard() {
		$tz      = wp_timezone();
		$today   = ( new DateTime( 'today', $tz ) )->getTimestamp();
		$month   = ( new DateTime( 'first day of this month 00:00', $tz ) )->getTimestamp();
		list( $tc, $ts ) = self::sales_since( $today );
		list( $mc, $ms ) = self::sales_since( $month );
		$pending = count( wc_get_orders( array( 'limit' => -1, 'status' => Shokher_Stages::active_statuses(), 'return' => 'ids', 'type' => 'shop_order' ) ) );
		$low_ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_manage_stock', 'value' => 'yes' ),
				array( 'key' => '_stock', 'value' => 3, 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		) );
		$low     = array_filter( array_map( 'wc_get_product', $low_ids ) );
		$cnt     = wp_count_posts( 'product' );
		$total_p = (int) $cnt->publish + (int) $cnt->draft + (int) $cnt->private;
		?>
		<div class="skm-cards">
			<div class="skm-card"><span>আজকের অর্ডার</span><b><?php echo (int) $tc; ?></b><small><?php echo wp_kses_post( self::money( $ts ) ); ?></small></div>
			<div class="skm-card"><span>এই মাসের বিক্রি</span><b><?php echo wp_kses_post( self::money( $ms ) ); ?></b><small><?php echo (int) $mc; ?> টি অর্ডার</small></div>
			<div class="skm-card skm-card-warn"><span>চলমান অর্ডার (ডেলিভারি বাকি)</span><b><?php echo (int) $pending; ?></b><small><a href="<?php echo esc_url( self::url( 'orders' ) ); ?>">অর্ডার দেখুন →</a></small></div>
			<div class="skm-card"><span>মোট প্রোডাক্ট</span><b><?php echo (int) $total_p; ?></b><small><a href="<?php echo esc_url( self::url( 'products' ) ); ?>">সব প্রোডাক্ট →</a></small></div>
		</div>

		<div class="skm-grid2">
			<div class="skm-panel">
				<h2>দ্রুত কাজ</h2>
				<div class="skm-quick">
					<a class="skm-btn" href="<?php echo esc_url( self::url( 'add' ) ); ?>">➕ নতুন প্রোডাক্ট যোগ করুন</a>
					<a class="skm-btn skm-btn-ghost" href="<?php echo esc_url( self::url( 'orders' ) ); ?>">📦 অর্ডার দেখুন</a>
					<a class="skm-btn skm-btn-ghost" href="<?php echo esc_url( self::url( 'delivery' ) ); ?>">🚚 ডেলিভারি চার্জ / ফ্রি ডেলিভারি</a>
					<a class="skm-btn skm-btn-ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_coupon' ) ); ?>">🏷 কুপন</a>
					<a class="skm-btn skm-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-reports' ) ); ?>">📊 সেলস রিপোর্ট</a>
					<a class="skm-btn skm-btn-ghost" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">🎨 থিম / ব্যানার</a>
				</div>
			</div>
			<div class="skm-panel">
				<h2>স্টক কম (৩ বা তার কম)</h2>
				<?php if ( ! $low ) : ?>
					<p class="skm-muted">সব প্রোডাক্টের স্টক ঠিক আছে 👍</p>
				<?php else : ?>
					<ul class="skm-list">
						<?php foreach ( $low as $p ) : ?>
							<li><a href="<?php echo esc_url( self::url( 'edit', array( 'id' => $p->get_id() ) ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a><span class="skm-pill <?php echo $p->get_stock_quantity() <= 0 ? 'is-red' : 'is-yellow'; ?>"><?php echo (int) $p->get_stock_quantity(); ?> পিস</span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php
		self::orders_table( 8, '', true );
	}

	/* ---------------------------------------------------------------- */
	/* Add / edit form                                                   */
	/* ---------------------------------------------------------------- */

	private static function cat_checkboxes( $selected, $parent = 0, $depth = 0 ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $parent, 'orderby' => 'menu_order' ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}
		foreach ( $terms as $t ) {
			printf(
				'<label class="skm-cat skm-depth-%d"><input type="checkbox" name="cats[]" value="%d" %s> %s</label>',
				(int) $depth,
				(int) $t->term_id,
				checked( in_array( (int) $t->term_id, $selected, true ), true, false ),
				esc_html( $t->name )
			);
			self::cat_checkboxes( $selected, $t->term_id, $depth + 1 );
		}
	}

	private static function tab_form() {
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$p       = $id ? wc_get_product( $id ) : null;
		$is_var  = $p && $p->is_type( 'variable' );
		$packs   = array();
		$foreign = false;
		if ( $is_var ) {
			$attrs = $p->get_attributes();
			if ( count( $attrs ) !== 1 || ! isset( $attrs['pack'] ) ) {
				$foreign = true;
			}
			foreach ( $p->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( ! $v ) {
					continue;
				}
				$packs[] = array(
					'id'    => $vid,
					'name'  => $v->get_attribute( 'pack' ),
					'price' => $v->get_regular_price(),
					'sale'  => $v->get_sale_price(),
				);
			}
		}
		$img_ids = $p ? array_filter( array_merge( array( $p->get_image_id() ), $p->get_gallery_image_ids() ) ) : array();
		$cats    = $p ? array_map( 'intval', $p->get_category_ids() ) : array();
		$tags    = $p ? implode( ', ', wp_list_pluck( get_the_terms( $id, 'product_tag' ) ?: array(), 'name' ) ) : '';
		$short   = $p ? trim( wp_strip_all_tags( str_replace( array( '</li>', '<br>', '<br />' ), "\n", $p->get_short_description() ) ) ) : '';
		?>
		<?php if ( $foreign ) : ?>
			<div class="notice notice-warning"><p>এই প্রোডাক্টে বিশেষ অপশন আছে যা এখানে এডিট করা যায় না। <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">WooCommerce এডিটরে খুলুন</a>।</p></div>
			<?php
			return;
		endif;
		?>
		<form class="skm-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'skm_save_product' ); ?>
			<input type="hidden" name="action" value="skm_save_product">
			<input type="hidden" name="id" value="<?php echo (int) $id; ?>">

			<div class="skm-form-grid">
				<div class="skm-col-main">
					<div class="skm-panel">
						<h2><?php echo $p ? 'প্রোডাক্ট এডিট' : 'নতুন প্রোডাক্ট'; ?></h2>
						<label class="skm-field"><span>প্রোডাক্টের নাম *</span>
							<input type="text" name="name" required value="<?php echo esc_attr( $p ? $p->get_name() : '' ); ?>" placeholder="যেমন: Transparent Hair Oil Applicator Comb">
						</label>

						<div class="skm-row3" data-skm-simple <?php echo $is_var ? 'hidden' : ''; ?>>
							<label class="skm-field"><span>দাম (৳) *</span><input type="text" inputmode="decimal" name="price" value="<?php echo esc_attr( $p && ! $is_var ? $p->get_regular_price() : '' ); ?>" placeholder="850"></label>
							<label class="skm-field"><span>অফার দাম (৳)</span><input type="text" inputmode="decimal" name="sale" value="<?php echo esc_attr( $p && ! $is_var ? $p->get_sale_price() : '' ); ?>" placeholder="খালি রাখলে অফার নেই"></label>
							<div class="skm-field"><span>ছাড়</span><output class="skm-discount" data-skm-discount>—</output></div>
						</div>

						<div class="skm-packs">
							<label class="skm-switch"><input type="checkbox" name="use_packs" value="1" data-skm-packs-toggle <?php checked( $is_var ); ?>> <span>একাধিক প্যাক/সাইজ আছে (যেমন ১ পিস / ২ পিস, বা S / M / L)</span></label>
							<div class="skm-packs-box" data-skm-packs <?php echo $is_var ? '' : 'hidden'; ?>>
								<table class="skm-table skm-packs-table">
									<thead><tr><th>অপশনের নাম</th><th>দাম (৳)</th><th>অফার দাম (৳)</th><th></th></tr></thead>
									<tbody data-skm-pack-rows>
										<?php
										if ( ! $packs ) {
											$packs = array( array( 'id' => 0, 'name' => '1 Piece', 'price' => '', 'sale' => '' ), array( 'id' => 0, 'name' => '2 Pieces', 'price' => '', 'sale' => '' ) );
										}
										foreach ( $packs as $pk ) :
											?>
											<tr>
												<td><input type="hidden" name="pack_id[]" value="<?php echo (int) $pk['id']; ?>"><input type="text" name="pack_name[]" value="<?php echo esc_attr( $pk['name'] ); ?>"></td>
												<td><input type="text" inputmode="decimal" name="pack_price[]" value="<?php echo esc_attr( $pk['price'] ); ?>"></td>
												<td><input type="text" inputmode="decimal" name="pack_sale[]" value="<?php echo esc_attr( $pk['sale'] ); ?>"></td>
												<td><button type="button" class="skm-x" data-skm-pack-del aria-label="মুছুন">✕</button></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<button type="button" class="skm-btn skm-btn-ghost skm-btn-sm" data-skm-pack-add>+ আরেকটা অপশন</button>
							</div>
						</div>

						<label class="skm-field"><span>ছোট বিবরণ (প্রতি লাইনে একটা পয়েন্ট – দামের নিচে দেখাবে)</span>
							<textarea name="short" rows="4" placeholder="Applies oil directly to hair roots&#10;Metal ball teeth for scalp massage"><?php echo esc_textarea( $short ); ?></textarea>
						</label>
						<div class="skm-field"><span>বিস্তারিত বিবরণ</span>
							<?php
							wp_editor( $p ? $p->get_description() : '', 'skm_desc', array(
								'textarea_name' => 'desc',
								'textarea_rows' => 9,
								'media_buttons' => false,
								'teeny'         => true,
							) );
							?>
						</div>
					</div>
				</div>

				<div class="skm-col-side">
					<div class="skm-panel">
						<h2>ছবি</h2>
						<p class="skm-muted">প্রথম ছবিটাই মূল ছবি। টেনে সাজাতে পারবেন।</p>
						<ul class="skm-imgs" data-skm-imgs>
							<?php foreach ( $img_ids as $iid ) : ?>
								<li data-id="<?php echo (int) $iid; ?>"><?php echo wp_get_attachment_image( $iid, 'thumbnail' ); ?><button type="button" class="skm-x" data-skm-img-del aria-label="সরান">✕</button></li>
							<?php endforeach; ?>
						</ul>
						<input type="hidden" name="images" value="<?php echo esc_attr( implode( ',', $img_ids ) ); ?>" data-skm-imgs-input>
						<button type="button" class="skm-btn skm-btn-ghost skm-w100" data-skm-img-add>📷 ছবি যোগ করুন</button>
					</div>

					<div class="skm-panel">
						<h2>ক্যাটাগরি</h2>
						<div class="skm-cats"><?php self::cat_checkboxes( $cats ); ?></div>
						<p><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>" target="_blank">+ নতুন ক্যাটাগরি</a></p>
					</div>

					<div class="skm-panel">
						<h2>স্টক ও অন্যান্য</h2>
						<label class="skm-field"><span>স্টকে কত পিস আছে</span><input type="number" min="0" name="stock" value="<?php echo esc_attr( $p && $p->managing_stock() ? (int) $p->get_stock_quantity() : '' ); ?>" placeholder="খালি = হিসাব রাখবে না"></label>
						<label class="skm-field"><span>SKU / কোড (ঐচ্ছিক)</span><input type="text" name="sku" value="<?php echo esc_attr( $p ? $p->get_sku() : '' ); ?>"></label>
						<label class="skm-field"><span>ট্যাগ (কমা দিয়ে)</span><input type="text" name="tags" value="<?php echo esc_attr( $tags ); ?>" placeholder="hair, oil, comb"></label>
						<label class="skm-switch"><input type="checkbox" name="featured" value="1" <?php checked( $p && $p->is_featured() ); ?>> <span>⭐ ফিচার্ড প্রোডাক্ট</span></label>
						<label class="skm-field"><span>অবস্থা</span>
							<select name="status">
								<option value="publish" <?php selected( ! $p || 'publish' === $p->get_status() ); ?>>✅ সাইটে দেখাবে (Publish)</option>
								<option value="draft" <?php selected( $p && 'draft' === $p->get_status() ); ?>>📝 ড্রাফট (লুকানো)</option>
							</select>
						</label>
					</div>

					<div class="skm-actions">
						<button type="submit" class="skm-btn skm-btn-lg skm-w100">💾 সেভ করুন</button>
						<?php if ( $p ) : ?>
							<a class="skm-btn skm-btn-ghost skm-w100" href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank">সাইটে দেখুন ↗</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	public static function save_product() {
		if ( ! current_user_can( SKM_CAP ) || ! check_admin_referer( 'skm_save_product' ) ) {
			wp_die( 'Not allowed' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$id        = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$use_packs = ! empty( $_POST['use_packs'] );
		$price     = self::price_in( 'price' );

		$pack_names = isset( $_POST['pack_name'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['pack_name'] ) ) : array();
		$pack_ids   = isset( $_POST['pack_id'] ) ? array_map( 'absint', (array) $_POST['pack_id'] ) : array();
		$pack_price = isset( $_POST['pack_price'] ) ? array_map( 'wc_clean', wp_unslash( (array) $_POST['pack_price'] ) ) : array();
		$pack_sale  = isset( $_POST['pack_sale'] ) ? array_map( 'wc_clean', wp_unslash( (array) $_POST['pack_sale'] ) ) : array();
		$packs      = array();
		foreach ( $pack_names as $i => $pn ) {
			$pp = preg_replace( '/[^0-9.]/', '', (string) ( $pack_price[ $i ] ?? '' ) );
			if ( '' === trim( $pn ) || '' === $pp ) {
				continue;
			}
			$packs[] = array(
				'id'    => $pack_ids[ $i ] ?? 0,
				'name'  => trim( $pn ),
				'price' => wc_format_decimal( $pp ),
				'sale'  => wc_format_decimal( preg_replace( '/[^0-9.]/', '', (string) ( $pack_sale[ $i ] ?? '' ) ) ),
			);
		}
		if ( '' === $name || ( $use_packs ? ! $packs : '' === $price ) ) {
			wp_safe_redirect( self::url( $id ? 'edit' : 'add', array( 'id' => $id, 'skm_msg' => 'noname' ) ) );
			exit;
		}

		$existing = $id ? wc_get_product( $id ) : null;
		$want     = $use_packs ? 'variable' : 'simple';
		if ( $existing && $existing->get_type() !== $want ) {
			if ( 'simple' === $want ) {
				foreach ( $existing->get_children() as $vid ) {
					wp_delete_post( $vid, true );
				}
			}
			wp_set_object_terms( $id, $want, 'product_type' );
			$existing = null;
		}
		$p = $existing ? $existing : ( 'variable' === $want ? new WC_Product_Variable( $id ) : new WC_Product_Simple( $id ) );

		$p->set_name( $name );
		$status = isset( $_POST['status'] ) && 'draft' === $_POST['status'] ? 'draft' : 'publish';
		$p->set_status( $status );
		$p->set_featured( ! empty( $_POST['featured'] ) );
		$p->set_description( isset( $_POST['desc'] ) ? wp_kses_post( wp_unslash( $_POST['desc'] ) ) : '' );

		$short = isset( $_POST['short'] ) ? sanitize_textarea_field( wp_unslash( $_POST['short'] ) ) : '';
		$lines = array_filter( array_map( 'trim', explode( "\n", $short ) ) );
		$p->set_short_description( $lines ? '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $lines ) ) . '</li></ul>' : '' );

		$sku = isset( $_POST['sku'] ) ? wc_clean( wp_unslash( $_POST['sku'] ) ) : '';
		try {
			$p->set_sku( $sku );
		} catch ( Exception $e ) { // Duplicate SKU – ignore.
			unset( $e );
		}

		$stock = isset( $_POST['stock'] ) ? trim( wc_clean( wp_unslash( $_POST['stock'] ) ) ) : '';
		if ( '' === $stock ) {
			$p->set_manage_stock( false );
			$p->set_stock_status( 'instock' );
		} else {
			$p->set_manage_stock( true );
			$p->set_stock_quantity( (int) $stock );
		}

		$cats = isset( $_POST['cats'] ) ? array_map( 'absint', (array) $_POST['cats'] ) : array();
		$p->set_category_ids( $cats );

		$imgs = isset( $_POST['images'] ) ? array_values( array_filter( array_map( 'absint', explode( ',', wc_clean( wp_unslash( $_POST['images'] ) ) ) ) ) ) : array();
		$p->set_image_id( $imgs ? $imgs[0] : '' );
		$p->set_gallery_image_ids( array_slice( $imgs, 1 ) );

		if ( 'simple' === $want ) {
			$sale = self::price_in( 'sale' );
			$p->set_regular_price( $price );
			$p->set_sale_price( ( '' !== $sale && (float) $sale < (float) $price ) ? $sale : '' );
		} else {
			$attr = new WC_Product_Attribute();
			$attr->set_name( 'Pack' );
			$attr->set_options( wp_list_pluck( $packs, 'name' ) );
			$attr->set_visible( true );
			$attr->set_variation( true );
			$p->set_attributes( array( $attr ) );
			$p->set_default_attributes( array( 'pack' => $packs[0]['name'] ) );
		}
		// phpcs:enable

		$id = $p->save();

		$tags = isset( $_POST['tags'] ) ? array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['tags'] ) ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_set_object_terms( $id, $tags, 'product_tag' );

		if ( 'variable' === $want ) {
			$keep = array();
			foreach ( $packs as $k => $pk ) {
				$v = $pk['id'] ? wc_get_product( $pk['id'] ) : null;
				if ( ! $v || ! $v->is_type( 'variation' ) || $v->get_parent_id() !== $id ) {
					$v = new WC_Product_Variation();
					$v->set_parent_id( $id );
				}
				$v->set_attributes( array( 'pack' => $pk['name'] ) );
				$v->set_regular_price( $pk['price'] );
				$v->set_sale_price( ( '' !== $pk['sale'] && (float) $pk['sale'] < (float) $pk['price'] ) ? $pk['sale'] : '' );
				$v->set_menu_order( $k );
				$v->set_manage_stock( false );
				$v->set_status( 'publish' );
				$keep[] = $v->save();
			}
			foreach ( wc_get_product( $id )->get_children() as $vid ) {
				if ( ! in_array( $vid, $keep, true ) ) {
					wp_delete_post( $vid, true );
				}
			}
			WC_Product_Variable::sync( $id );
			wc_delete_product_transients( $id );
		}

		wp_safe_redirect( self::url( 'edit', array( 'id' => $id, 'skm_msg' => 'saved' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------- */
	/* Products list                                                     */
	/* ---------------------------------------------------------------- */

	private static function tab_products() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$cat   = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : '';
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable
		$args = array( 'limit' => 20, 'page' => $paged, 'paginate' => true, 'status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'date', 'order' => 'DESC' );
		if ( $s ) {
			$args['s'] = $s;
		}
		if ( $cat ) {
			$args['category'] = array( $cat );
		}
		$res = wc_get_products( $args );
		?>
		<form class="skm-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>"><input type="hidden" name="tab" value="products">
			<input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="প্রোডাক্ট খুঁজুন…">
			<?php
			wc_product_dropdown_categories( array( 'show_option_none' => 'সব ক্যাটাগরি', 'option_none_value' => '', 'selected' => $cat, 'hide_empty' => 0, 'name' => 'cat' ) );
			?>
			<button class="skm-btn skm-btn-ghost">খুঁজুন</button>
			<a class="skm-btn" href="<?php echo esc_url( self::url( 'add' ) ); ?>">➕ নতুন প্রোডাক্ট</a>
		</form>
		<p class="skm-muted">দাম, অফার দাম বা স্টক বদলে <b>✔</b> চাপুন – সাথে সাথে সেভ হবে।</p>
		<div class="skm-panel skm-p0">
		<table class="skm-table skm-products">
			<thead><tr><th></th><th>প্রোডাক্ট</th><th>দাম ৳</th><th>অফার ৳</th><th>স্টক</th><th>অবস্থা</th><th></th></tr></thead>
			<tbody>
			<?php
			if ( ! $res->products ) {
				echo '<tr><td colspan="7" class="skm-empty">কোনো প্রোডাক্ট নেই।</td></tr>';
			}
			foreach ( $res->products as $p ) :
				$var = $p->is_type( 'variable' );
				?>
				<tr data-id="<?php echo (int) $p->get_id(); ?>">
					<td class="skm-thumb"><?php echo $p->get_image( array( 48, 48 ) ); // phpcs:ignore ?></td>
					<td><a class="skm-pname" href="<?php echo esc_url( self::url( 'edit', array( 'id' => $p->get_id() ) ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
						<small><?php echo wp_kses_post( wc_get_product_category_list( $p->get_id() ) ); ?></small></td>
					<?php if ( $var ) : ?>
						<td colspan="2"><?php echo wp_kses_post( $p->get_price_html() ); ?><br><small class="skm-muted">প্যাকের দাম এডিট পেজে</small></td>
					<?php else : ?>
						<td><input type="text" inputmode="decimal" class="skm-in" name="price" value="<?php echo esc_attr( $p->get_regular_price() ); ?>"></td>
						<td><input type="text" inputmode="decimal" class="skm-in" name="sale" value="<?php echo esc_attr( $p->get_sale_price() ); ?>" placeholder="—"></td>
					<?php endif; ?>
					<td><input type="number" min="0" class="skm-in skm-in-sm" name="stock" value="<?php echo $p->managing_stock() ? (int) $p->get_stock_quantity() : ''; ?>" placeholder="∞"></td>
					<td>
						<select class="skm-in" name="status">
							<option value="publish" <?php selected( $p->get_status(), 'publish' ); ?>>চালু</option>
							<option value="draft" <?php selected( $p->get_status(), 'draft' ); ?>>লুকানো</option>
						</select>
					</td>
					<td class="skm-row-actions">
						<button type="button" class="skm-btn skm-btn-sm" data-skm-save-row title="সেভ">✔</button>
						<a class="skm-btn skm-btn-ghost skm-btn-sm" href="<?php echo esc_url( self::url( 'edit', array( 'id' => $p->get_id() ) ) ); ?>" title="এডিট">✎</a>
						<a class="skm-btn skm-btn-ghost skm-btn-sm" href="<?php echo esc_url( get_permalink( $p->get_id() ) ); ?>" target="_blank" title="দেখুন">↗</a>
						<a class="skm-btn skm-btn-danger skm-btn-sm" href="<?php echo esc_url( get_delete_post_link( $p->get_id() ) ); ?>" onclick="return confirm('প্রোডাক্টটা ট্র্যাশে পাঠাবেন?');" title="মুছুন">🗑</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
		if ( $res->max_num_pages > 1 ) {
			echo '<div class="skm-pages">';
			for ( $i = 1; $i <= $res->max_num_pages; $i++ ) {
				printf( '<a class="%s" href="%s">%d</a>', $i === $paged ? 'is-active' : '', esc_url( self::url( 'products', array( 'paged' => $i, 's' => $s, 'cat' => $cat ) ) ), (int) $i );
			}
			echo '</div>';
		}
	}

	public static function ajax_save_row() {
		check_ajax_referer( 'skm_ajax', 'nonce' );
		if ( ! current_user_can( SKM_CAP ) ) {
			wp_send_json_error( 'not allowed', 403 );
		}
		$p = isset( $_POST['id'] ) ? wc_get_product( absint( $_POST['id'] ) ) : null;
		if ( ! $p ) {
			wp_send_json_error( 'missing' );
		}
		if ( ! $p->is_type( 'variable' ) && isset( $_POST['price'] ) ) {
			$price = self::price_in( 'price' );
			$sale  = self::price_in( 'sale' );
			if ( '' !== $price ) {
				$p->set_regular_price( $price );
			}
			$p->set_sale_price( ( '' !== $sale && (float) $sale < (float) $p->get_regular_price() ) ? $sale : '' );
		}
		$stock = isset( $_POST['stock'] ) ? trim( wc_clean( wp_unslash( $_POST['stock'] ) ) ) : '';
		if ( '' === $stock ) {
			$p->set_manage_stock( false );
		} else {
			$p->set_manage_stock( true );
			$p->set_stock_quantity( (int) $stock );
		}
		if ( isset( $_POST['status'] ) ) {
			$p->set_status( 'draft' === $_POST['status'] ? 'draft' : 'publish' );
		}
		$p->save();
		wp_send_json_success();
	}

	/* ---------------------------------------------------------------- */
	/* Orders                                                            */
	/* ---------------------------------------------------------------- */

	private static function trx_info( WC_Order $o ) {
		$out = array();
		foreach ( $o->get_meta_data() as $m ) {
			$d = $m->get_data();
			if ( is_scalar( $d['value'] ) && preg_match( '/(trans|trx|txn|acc_no|account_no|sender)/i', $d['key'] ) && '' !== (string) $d['value'] ) {
				$label = preg_match( '/(trans|trx|txn)/i', $d['key'] ) ? 'TrxID' : 'নম্বর';
				$out[] = $label . ': <b>' . esc_html( $d['value'] ) . '</b>';
			}
		}
		return implode( '<br>', array_unique( $out ) );
	}

	private static function orders_table( $limit, $status, $compact = false ) {
		$args = array( 'limit' => $limit, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' );
		if ( $status ) {
			$args['status'] = array( 'wc-' . $status );
		}
		$orders   = wc_get_orders( $args );
		$statuses = wc_get_order_statuses();
		$bn       = array(
			'wc-pending'    => 'পেমেন্ট বাকি',
			'wc-processing' => 'নতুন / প্রসেসিং',
			'wc-on-hold'    => 'যাচাই বাকি (bKash/Nagad)',
			'wc-completed'  => 'ডেলিভারি সম্পন্ন',
			'wc-cancelled'  => 'বাতিল',
			'wc-refunded'   => 'রিফান্ড',
			'wc-failed'     => 'ব্যর্থ',
		);
		?>
		<div class="skm-panel skm-p0">
			<?php if ( $compact ) : ?><h2 class="skm-pad">সাম্প্রতিক অর্ডার</h2><?php endif; ?>
			<table class="skm-table skm-orders">
				<thead><tr><th>অর্ডার</th><th>কাস্টমার</th><th>প্রোডাক্ট</th><th>মোট</th><th>পেমেন্ট</th><th>স্ট্যাটাস</th></tr></thead>
				<tbody>
				<?php
				if ( ! $orders ) {
					echo '<tr><td colspan="6" class="skm-empty">এখনো কোনো অর্ডার নেই।</td></tr>';
				}
				foreach ( $orders as $o ) :
					if ( ! $o instanceof WC_Order ) {
						continue;
					}
					$items = array();
					foreach ( $o->get_items() as $it ) {
						$items[] = esc_html( $it->get_name() ) . ' × ' . (int) $it->get_quantity();
					}
					$phone = $o->get_billing_phone();
					$addr  = trim( implode( ', ', array_filter( array( $o->get_billing_address_1(), $o->get_billing_city(), WC()->countries->get_states( 'BD' )[ $o->get_billing_state() ] ?? $o->get_billing_state() ) ) ) );
					?>
					<tr data-id="<?php echo (int) $o->get_id(); ?>">
						<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><b>#<?php echo esc_html( $o->get_order_number() ); ?></b></a><br><small class="skm-muted"><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'd M, h:i A' ) : '' ); ?></small></td>
						<td><b><?php echo esc_html( $o->get_formatted_billing_full_name() ); ?></b>
							<?php if ( $phone ) : ?><br><a href="tel:<?php echo esc_attr( $phone ); ?>">📞 <?php echo esc_html( $phone ); ?></a><?php endif; ?>
							<?php if ( ! $compact && $addr ) : ?><br><small><?php echo esc_html( $addr ); ?></small><?php endif; ?>
						</td>
						<td><small><?php echo wp_kses_post( implode( '<br>', $items ) ); ?></small></td>
						<td><b><?php echo wp_kses_post( wc_price( $o->get_total(), array( 'currency' => $o->get_currency() ) ) ); ?></b><br><small class="skm-muted"><?php echo esc_html( $o->get_shipping_method() ); ?></small></td>
						<td><?php echo esc_html( $o->get_payment_method_title() ); ?><?php $t = self::trx_info( $o ); echo $t ? '<br><small>' . wp_kses_post( $t ) . '</small>' : ''; ?></td>
						<td>
							<select class="skm-in" data-skm-order-status>
								<?php foreach ( $statuses as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( 'wc-' . $o->get_status(), $k ); ?>><?php echo esc_html( Shokher_Stages::bn_status( substr( $k, 3 ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function tab_orders() {
		Shokher_Stages::render_board();
	}

	public static function ajax_order_status() {
		check_ajax_referer( 'skm_ajax', 'nonce' );
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( 'not allowed', 403 );
		}
		$o      = isset( $_POST['id'] ) ? wc_get_order( absint( $_POST['id'] ) ) : null;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		if ( ! $o || ! array_key_exists( $status, wc_get_order_statuses() ) ) {
			wp_send_json_error( 'bad' );
		}
		$o->update_status( substr( $status, 3 ), 'Shokher Shop Manager থেকে বদলানো হয়েছে।', true );
		wp_send_json_success();
	}

	/* ---------------------------------------------------------------- */
	/* Delivery & offer                                                  */
	/* ---------------------------------------------------------------- */

	private static function shipping_methods() {
		$out = array();
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return $out;
		}
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			foreach ( $z['shipping_methods'] as $m ) {
				$out[] = array( 'zone' => $z['zone_name'], 'm' => $m );
			}
		}
		return $out;
	}

	private static function tab_delivery() {
		$methods = self::shipping_methods();
		$demo    = get_term_by( 'slug', 'demo', 'product_tag' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'skm_save_delivery' ); ?>
			<input type="hidden" name="action" value="skm_save_delivery">
			<div class="skm-grid2">
				<div class="skm-panel">
					<h2>🚚 ডেলিভারি চার্জ</h2>
					<?php
					$any = false;
					foreach ( $methods as $row ) :
						$m = $row['m'];
						if ( 'flat_rate' !== $m->id ) {
							continue;
						}
						$any = true;
						?>
						<label class="skm-field"><span><?php echo esc_html( $row['zone'] ); ?> (৳)</span>
							<input type="text" inputmode="decimal" name="cost[<?php echo (int) $m->instance_id; ?>]" value="<?php echo esc_attr( $m->get_option( 'cost' ) ); ?>">
						</label>
					<?php endforeach; ?>
					<?php if ( ! $any ) : ?><p class="skm-muted">কোনো ডেলিভারি জোন পাওয়া যায়নি। WooCommerce → Settings → Shipping থেকে জোন বানান।</p><?php endif; ?>
				</div>
				<div class="skm-panel">
					<h2>🎁 ফ্রি ডেলিভারি অফার</h2>
					<?php
					$free = array_values( array_filter( $methods, function ( $r ) {
						return 'free_shipping' === $r['m']->id;
					} ) );
					if ( $free ) :
						$on  = false;
						$min = '';
						foreach ( $free as $r ) {
							$on  = $on || 'yes' === $r['m']->enabled;
							$min = $min ? $min : $r['m']->get_option( 'min_amount' );
						}
						?>
						<label class="skm-switch skm-switch-big"><input type="checkbox" name="free_on" value="1" <?php checked( $on ); ?>> <span>অফার চালু</span></label>
						<label class="skm-field"><span>কত টাকার বেশি অর্ডারে ফ্রি (৳)</span><input type="text" inputmode="decimal" name="free_min" value="<?php echo esc_attr( $min ); ?>"></label>
						<p class="skm-muted">চালু থাকলে এই টাকার বেশি অর্ডারে কাস্টমার চেকআউটে "Free Delivery" বেছে নিতে পারবে। ফ্রি ডেলিভারির কুপনও কাজ করবে।</p>
					<?php else : ?>
						<p class="skm-muted">ফ্রি ডেলিভারি মেথড পাওয়া যায়নি। WooCommerce → Settings → Shipping-এ প্রতিটা জোনে "Free shipping" যোগ করুন।</p>
					<?php endif; ?>
				</div>
			</div>
			<p><button class="skm-btn skm-btn-lg">💾 সেভ করুন</button></p>
		</form>

		<div class="skm-panel">
			<h2>💳 পেমেন্ট</h2>
			<p>bKash / Nagad নম্বর বা ক্যাশ অন ডেলিভারি বদলাতে: <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>">পেমেন্ট সেটিংস খুলুন →</a></p>
		</div>

		<div class="skm-panel skm-danger-zone">
			<h2>🧹 ডেমো প্রোডাক্ট</h2>
			<?php $cnt = $demo ? (int) $demo->count : 0; ?>
			<p><?php echo $cnt ? sprintf( 'এখন %d টা ডেমো প্রোডাক্ট আছে (ট্যাগ: demo)।', (int) $cnt ) : 'কোনো ডেমো প্রোডাক্ট নেই।'; ?></p>
			<?php if ( $cnt ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('সব ডেমো প্রোডাক্ট ট্র্যাশে যাবে। নিশ্চিত?');">
					<?php wp_nonce_field( 'skm_delete_demo' ); ?>
					<input type="hidden" name="action" value="skm_delete_demo">
					<button class="skm-btn skm-btn-danger">সব ডেমো প্রোডাক্ট মুছুন</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function save_delivery() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'skm_save_delivery' ) ) {
			wp_die( 'Not allowed' );
		}
		global $wpdb;
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$costs   = isset( $_POST['cost'] ) ? (array) wp_unslash( $_POST['cost'] ) : array();
		$free_on = ! empty( $_POST['free_on'] );
		$min     = self::price_in( 'free_min' );
		// phpcs:enable
		foreach ( self::shipping_methods() as $row ) {
			$m   = $row['m'];
			$key = 'woocommerce_' . $m->id . '_' . (int) $m->instance_id . '_settings';
			$opt = get_option( $key, array() );
			if ( 'flat_rate' === $m->id && isset( $costs[ $m->instance_id ] ) ) {
				$opt['cost'] = wc_format_decimal( preg_replace( '/[^0-9.]/', '', (string) $costs[ $m->instance_id ] ) );
				update_option( $key, $opt );
			}
			if ( 'free_shipping' === $m->id ) {
				if ( '' !== $min ) {
					$opt['min_amount'] = $min;
				}
				update_option( $key, $opt );
				$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'is_enabled' => $free_on ? 1 : 0 ), array( 'instance_id' => (int) $m->instance_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		wp_safe_redirect( self::url( 'delivery', array( 'skm_msg' => 'delivery' ) ) );
		exit;
	}

	public static function delete_demo() {
		if ( ! current_user_can( 'delete_products' ) || ! check_admin_referer( 'skm_delete_demo' ) ) {
			wp_die( 'Not allowed' );
		}
		$ids = wc_get_products( array( 'tag' => array( 'demo' ), 'limit' => -1, 'return' => 'ids', 'status' => array( 'publish', 'draft', 'private' ) ) );
		foreach ( $ids as $id ) {
			wp_trash_post( $id );
		}
		wp_safe_redirect( self::url( 'delivery', array( 'skm_msg' => 'demo' ) ) );
		exit;
	}
}

add_action( 'plugins_loaded', array( 'Shokher_Manager', 'init' ) );
