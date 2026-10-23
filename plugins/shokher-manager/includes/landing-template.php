<?php
/**
 * Landing page template (standalone, mobile-first).
 */
defined( 'ABSPATH' ) || exit;

$lid = get_queried_object_id();
$d   = Shokher_Landing::data( $lid );
$p   = $d['product'];
$opt = $d['options'];
$min = $opt ? min( wp_list_pluck( $opt, 'price' ) ) : 0;
$old = $d['old'];
if ( ! $old && $opt ) {
	$old = (float) $opt[0]['regular'] > (float) $opt[0]['price'] ? (float) $opt[0]['regular'] : 0;
}
$areas    = Shokher_Landing::areas();
$hotline  = function_exists( 'shokher_opt' ) ? shokher_opt( 'hotline' ) : '';
$wa       = function_exists( 'shokher_opt' ) ? shokher_opt( 'whatsapp' ) : '';
$cur      = html_entity_decode( get_woocommerce_currency_symbol() );
$fmt      = function ( $n ) use ( $cur ) {
	return $cur . number_format_i18n( (float) $n );
};
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ( $d['images'] ) : ?><meta property="og:image" content="<?php echo esc_url( wp_get_attachment_image_url( $d['images'][0], 'large' ) ); ?>"><?php endif; ?>
<meta property="og:title" content="<?php echo esc_attr( $d['headline'] ? $d['headline'] : get_the_title() ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@500;700;800&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
:root{--y:#FFC107;--k:#111;--r:#E53935;--g:#16a34a;--bg:#f6f6f7;--line:#ececec}
body.skl-body{margin:0;background:var(--bg);font-family:"Hind Siliguri",Poppins,system-ui,sans-serif;color:#222;font-size:17px;line-height:1.6;padding-bottom:80px}
.skl-body *{box-sizing:border-box}
.skl-body img{max-width:100%;height:auto;display:block}
.skl-wrap{max-width:980px;margin:0 auto;padding:0 14px}
.skl-top{background:#fff;box-shadow:0 1px 0 var(--line);position:sticky;top:0;z-index:50}
.admin-bar .skl-top{top:32px}
.skl-top .skl-wrap{display:flex;align-items:center;justify-content:space-between;min-height:64px}
.skl-top img{max-height:52px;width:auto}
.skl-call{display:inline-flex;align-items:center;gap:6px;background:var(--k);color:#fff!important;text-decoration:none;border-radius:999px;padding:8px 16px;font-weight:700;font-size:15px}
.skl-hero{padding:22px 0 10px}
.skl-hero-grid{display:grid;grid-template-columns:1.05fr 1fr;gap:26px;align-items:center}
.skl-badge{display:inline-block;background:var(--r);color:#fff;border-radius:999px;padding:4px 14px;font-weight:700;font-size:15px}
.skl-h1{font-size:38px;line-height:1.25;margin:12px 0 8px;color:var(--k);font-weight:700}
.skl-sub{font-size:18px;color:#444;margin:0 0 16px}
.skl-price{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin:6px 0 16px}
.skl-price b{font-size:38px;color:var(--r);font-family:Poppins,sans-serif;font-weight:800}
.skl-price del{color:#999;font-size:22px}
.skl-price span{background:#fff3c4;color:#8a6500;font-weight:700;border-radius:8px;padding:2px 10px;font-size:15px}
.skl-cta{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--y);color:var(--k)!important;border:0;border-radius:14px;padding:15px 28px;font-weight:700;font-size:20px;text-decoration:none;box-shadow:0 8px 20px rgba(255,193,7,.45);cursor:pointer;font-family:inherit;animation:sklpulse 2s infinite}
.skl-cta:hover{background:var(--k);color:#fff!important}
@keyframes sklpulse{0%,100%{transform:scale(1)}50%{transform:scale(1.03)}}
.skl-hero-img{background:#fff;border-radius:20px;padding:10px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.skl-hero-img img{border-radius:14px;width:100%}
.skl-trust{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:18px 0 0}
.skl-trust div{background:#fff;border-radius:12px;padding:10px;text-align:center;font-weight:600;font-size:14px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.skl-trust i{font-style:normal;font-size:22px;display:block}
.skl-sec{background:#fff;border-radius:20px;padding:24px;margin:18px 0;box-shadow:0 2px 12px rgba(0,0,0,.05)}
.skl-sec h2{text-align:center;font-size:26px;margin:0 0 16px;color:var(--k)}
.skl-sec h2::after{content:"";display:block;width:60px;height:4px;border-radius:4px;background:var(--y);margin:8px auto 0}
.skl-benefits{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:10px}
.skl-benefits li{display:flex;gap:10px;align-items:flex-start;background:#fafafa;border-radius:12px;padding:12px 14px;font-weight:500}
.skl-benefits li::before{content:"✓";flex:none;width:26px;height:26px;border-radius:50%;background:var(--g);color:#fff;display:grid;place-items:center;font-weight:800;font-size:14px;margin-top:1px}
.skl-gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.skl-gallery img{border-radius:14px;aspect-ratio:1/1;object-fit:cover;width:100%}
.skl-steps{list-style:none;margin:0;padding:0;counter-reset:s;display:grid;gap:10px}
.skl-steps li{counter-increment:s;display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-bottom:1px dashed var(--line)}
.skl-steps li::before{content:counter(s);flex:none;width:34px;height:34px;border-radius:50%;background:var(--k);color:var(--y);display:grid;place-items:center;font-weight:800;font-family:Poppins}
.skl-video{position:relative;padding-top:56.25%;border-radius:14px;overflow:hidden;background:#000}
.skl-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.skl-reviews{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.skl-review{background:#fffbea;border-radius:14px;padding:14px}
.skl-review .st{color:#f5a623;letter-spacing:2px}
.skl-review b{display:block;margin-top:6px}
.skl-faq details{border-bottom:1px solid var(--line);padding:12px 0}
.skl-faq summary{font-weight:700;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:10px}
.skl-faq summary::after{content:"+";font-size:22px;line-height:1;color:var(--r)}
.skl-faq details[open] summary::after{content:"–"}
.skl-faq p{margin:8px 0 0;color:#444}
/* Order form */
.skl-order{border:3px solid var(--y);scroll-margin-top:84px}
.skl-order h2{margin-bottom:6px}
.skl-order .skl-note{text-align:center;color:#555;margin:0 0 16px}
.skl-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.skl-opts{display:grid;gap:10px;margin-bottom:12px}
.skl-opt{display:flex;align-items:center;gap:12px;border:2px solid var(--line);border-radius:14px;padding:12px 14px;cursor:pointer;background:#fff}
.skl-opt input{width:20px;height:20px;accent-color:var(--k)}
.skl-opt.is-on{border-color:var(--k);background:#fffbea}
.skl-opt .n{flex:1;font-weight:700}
.skl-opt .pr{font-family:Poppins;font-weight:800;color:var(--r)}
.skl-opt .pr del{color:#999;font-weight:500;font-size:.8em;margin-left:4px}
.skl-qty{display:flex;align-items:center;gap:10px;margin:6px 0 14px}
.skl-qty button{width:40px;height:40px;border-radius:10px;border:2px solid var(--k);background:#fff;font-size:20px;font-weight:800;cursor:pointer}
.skl-qty input{width:60px;height:40px;text-align:center;border:2px solid var(--line);border-radius:10px;font-size:18px;font-weight:700}
.skl-f{display:block;margin-bottom:12px}
.skl-f span{display:block;font-weight:600;margin-bottom:4px}
.skl-f input,.skl-f select,.skl-f textarea{width:100%;border:2px solid #ddd;border-radius:12px;padding:12px 14px;font-size:17px;font-family:inherit;background:#fff}
.skl-f input:focus,.skl-f select:focus,.skl-f textarea:focus{border-color:var(--k);outline:none}
.skl-sum{background:#fafafa;border-radius:14px;padding:12px 14px;margin:6px 0 14px}
.skl-sum div{display:flex;justify-content:space-between;padding:4px 0}
.skl-sum .tot{border-top:2px dashed #ddd;margin-top:6px;padding-top:8px;font-size:20px;font-weight:800}
.skl-sum .tot b{color:var(--r);font-family:Poppins}
.skl-submit{width:100%;animation:none}
.skl-areas{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.skl-area{display:flex;align-items:center;gap:8px;border:2px solid var(--line);border-radius:12px;padding:10px 12px;cursor:pointer;background:#fff;font-weight:600}
.skl-area input{width:18px;height:18px;accent-color:var(--k)}
.skl-area b{display:block;color:var(--r);font-family:Poppins}
.skl-area.is-on{border-color:var(--k);background:#fffbea}
.skl-f.is-err input,.skl-f.is-err textarea{border-color:var(--r)!important;background:#fff5f5}
.skl-qty button{color:var(--k)}
.skl-msg{display:none;margin-top:10px;padding:10px 12px;border-radius:10px;background:#fde2e1;color:#b91c1c;font-weight:600}
.skl-cod{text-align:center;color:#555;font-size:15px;margin-top:8px}
.skl-hp{position:absolute;left:-9999px}
.skl-foot{text-align:center;color:#777;font-size:14px;padding:20px 0}
.skl-foot a{color:#555}
.skl-sticky{position:fixed;left:0;right:0;bottom:0;z-index:60;background:#fff;box-shadow:0 -4px 20px rgba(0,0,0,.1);padding:10px 12px;display:flex;gap:10px;align-items:center}
.skl-sticky .skl-cta{flex:1;padding:12px;font-size:18px;animation:none}
.skl-sticky .wa{flex:none;width:50px;height:50px;border-radius:14px;background:#25D366;color:#fff;display:grid;place-items:center;font-size:22px;text-decoration:none}
@media(min-width:861px){.skl-sticky{display:none}body.skl-body{padding-bottom:0}}
@media(max-width:860px){
  .skl-hero-grid,.skl-form-grid{grid-template-columns:1fr}
  .skl-hero-img{order:-1}
  .skl-h1{font-size:28px}.skl-price b{font-size:32px}
  .skl-trust{grid-template-columns:repeat(2,1fr)}
  .skl-benefits,.skl-reviews{grid-template-columns:1fr}
  .skl-gallery{grid-template-columns:repeat(2,1fr)}
  .skl-sec{padding:18px 14px;border-radius:16px}
  .admin-bar .skl-top{top:0}
}
</style>
</head>
<body <?php body_class( 'skl-body' ); ?>>
<?php wp_body_open(); ?>
<header class="skl-top"><div class="skl-wrap">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php
	if ( has_custom_logo() ) {
		echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'medium', false, array( 'alt' => get_bloginfo( 'name' ) ) );
	} elseif ( function_exists( 'shokher_logo' ) ) {
		echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/img/logo.png' ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
	?></a>
	<?php if ( $hotline ) : ?><a class="skl-call" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $hotline ) ); ?>">📞 <?php echo esc_html( $hotline ); ?></a><?php endif; ?>
</div></header>

<main class="skl-wrap">
<?php if ( ! $p || ! $opt ) : ?>
	<section class="skl-sec"><h2>দুঃখিত!</h2><p style="text-align:center">এই প্রোডাক্টটা এখন স্টকে নেই। আরও প্রোডাক্ট দেখতে <a href="<?php echo esc_url( home_url( '/' ) ); ?>">আমাদের সাইটে যান</a>।</p></section>
<?php else : ?>
	<section class="skl-hero">
		<div class="skl-hero-grid">
			<div>
				<?php if ( $d['badge'] ) : ?><span class="skl-badge"><?php echo esc_html( $d['badge'] ); ?></span><?php endif; ?>
				<h1 class="skl-h1"><?php echo esc_html( $d['headline'] ? $d['headline'] : $p->get_name() ); ?></h1>
				<?php if ( $d['sub'] ) : ?><p class="skl-sub"><?php echo esc_html( $d['sub'] ); ?></p><?php endif; ?>
				<div class="skl-price">
					<b><?php echo esc_html( $fmt( $min ) ); ?></b>
					<?php if ( $old > $min ) : ?><del><?php echo esc_html( $fmt( $old ) ); ?></del><span><?php echo (int) round( 100 - $min / $old * 100 ); ?>% ছাড়</span><?php endif; ?>
				</div>
				<a class="skl-cta" href="#order">🛒 <?php echo esc_html( $d['cta'] ); ?></a>
			</div>
			<?php if ( $d['images'] ) : ?>
				<div class="skl-hero-img"><?php echo wp_get_attachment_image( $d['images'][0], 'large', false, array( 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?></div>
			<?php endif; ?>
		</div>
		<div class="skl-trust">
			<div><i>💵</i>ক্যাশ অন ডেলিভারি</div>
			<div><i>🚚</i>সারা বাংলাদেশে ডেলিভারি</div>
			<div><i>✅</i>দেখে বুঝে নিন</div>
			<div><i>🔁</i>সহজ এক্সচেঞ্জ</div>
		</div>
	</section>

	<?php if ( $d['benefits'] ) : ?>
	<section class="skl-sec">
		<h2>কেন এটা নেবেন?</h2>
		<ul class="skl-benefits"><?php foreach ( $d['benefits'] as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul>
		<p style="text-align:center;margin:18px 0 0"><a class="skl-cta" href="#order">🛒 <?php echo esc_html( $d['cta'] ); ?></a></p>
	</section>
	<?php endif; ?>

	<?php if ( count( $d['images'] ) > 1 ) : ?>
	<section class="skl-sec">
		<h2>ছবিতে দেখুন</h2>
		<div class="skl-gallery"><?php foreach ( array_slice( $d['images'], 1 ) as $img ) { echo wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'lazy' ) ); } ?></div>
	</section>
	<?php endif; ?>

	<?php if ( $d['youtube'] ) : ?>
	<section class="skl-sec">
		<h2>ভিডিওতে দেখুন</h2>
		<div class="skl-video"><iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $d['youtube'] ); ?>" title="Video" loading="lazy" allowfullscreen></iframe></div>
	</section>
	<?php endif; ?>

	<?php if ( $d['steps'] ) : ?>
	<section class="skl-sec">
		<h2>কীভাবে ব্যবহার করবেন</h2>
		<ol class="skl-steps"><?php foreach ( $d['steps'] as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?></ol>
	</section>
	<?php endif; ?>

	<?php if ( $d['reviews'] ) : ?>
	<section class="skl-sec">
		<h2>কাস্টমাররা যা বলছেন</h2>
		<div class="skl-reviews"><?php foreach ( $d['reviews'] as $r ) : ?><div class="skl-review"><div class="st">★★★★★</div><?php echo esc_html( $r[1] ); ?><b>— <?php echo esc_html( $r[0] ); ?></b></div><?php endforeach; ?></div>
	</section>
	<?php endif; ?>

	<section class="skl-sec skl-order" id="order">
		<h2>অর্ডার করতে নিচের ফর্মটি পূরণ করুন</h2>
		<p class="skl-note">কোনো অগ্রিম টাকা লাগবে না – প্রোডাক্ট হাতে পেয়ে টাকা দিন।</p>
		<form id="skl-form" novalidate>
			<input type="hidden" name="action" value="skm_landing_order">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'skm_landing' ) ); ?>">
			<input type="hidden" name="landing" value="<?php echo (int) $lid; ?>">
			<input class="skl-hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
			<div class="skl-form-grid">
				<div>
					<?php if ( count( $opt ) > 1 ) : ?><b style="display:block;margin-bottom:6px">প্যাক বাছাই করুন</b><?php endif; ?>
					<div class="skl-opts">
						<?php foreach ( $opt as $i => $o ) : ?>
							<label class="skl-opt <?php echo 0 === $i ? 'is-on' : ''; ?>">
								<input type="radio" name="option" value="<?php echo (int) $o['id']; ?>" data-price="<?php echo esc_attr( $o['price'] ); ?>" <?php checked( 0 === $i ); ?>>
								<span class="n"><?php echo esc_html( $o['label'] ); ?></span>
								<span class="pr"><?php echo esc_html( $fmt( $o['price'] ) ); ?><?php if ( $o['regular'] > $o['price'] ) : ?><del><?php echo esc_html( $fmt( $o['regular'] ) ); ?></del><?php endif; ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<b>পরিমাণ</b>
					<div class="skl-qty"><button type="button" data-q="-1" aria-label="কমান">−</button><input type="number" name="qty" value="1" min="1" max="20" inputmode="numeric" aria-label="পরিমাণ"><button type="button" data-q="1" aria-label="বাড়ান">+</button></div>
					<div class="skl-sum">
						<div><span>প্রোডাক্টের দাম</span><span data-sum="sub">—</span></div>
						<div><span>ডেলিভারি চার্জ</span><span data-sum="ship">জেলা বাছাই করুন</span></div>
						<div class="tot"><span>মোট</span><b data-sum="tot">—</b></div>
					</div>
				</div>
				<div>
					<label class="skl-f"><span>আপনার নাম *</span><input type="text" name="name" autocomplete="name" required placeholder="আপনার নাম লিখুন"></label>
					<label class="skl-f"><span>মোবাইল নম্বর *</span><input type="tel" name="phone" autocomplete="tel" inputmode="numeric" required placeholder="01XXXXXXXXX"></label>
					<label class="skl-f"><span>সম্পূর্ণ ঠিকানা *</span><textarea name="address" rows="2" required placeholder="বাসা/রোড, এলাকা, থানা, জেলা"></textarea></label>
					<div class="skl-f"><span>ডেলিভারি এলাকা</span>
						<div class="skl-areas">
							<label class="skl-area"><input type="radio" name="area" value="inside"> <span>ঢাকার ভিতরে<b><?php echo esc_html( $fmt( $areas['inside']['cost'] ) ); ?></b></span></label>
							<label class="skl-area is-on"><input type="radio" name="area" value="outside" checked> <span>ঢাকার বাইরে<b><?php echo esc_html( $fmt( $areas['outside']['cost'] ) ); ?></b></span></label>
						</div>
					</div>
					<label class="skl-f"><span>কিছু বলার থাকলে (ঐচ্ছিক)</span><input type="text" name="note"></label>
					<button type="submit" class="skl-cta skl-submit">✅ অর্ডার কনফার্ম করুন</button>
					<div class="skl-msg" role="alert"></div>
					<p class="skl-cod">💵 ক্যাশ অন ডেলিভারি · অর্ডারের পর আমরা কল করে কনফার্ম করবো</p>
				</div>
			</div>
		</form>
	</section>

	<?php if ( $d['faq'] ) : ?>
	<section class="skl-sec skl-faq">
		<h2>সাধারণ প্রশ্ন</h2>
		<?php foreach ( $d['faq'] as $f ) : ?><details><summary><?php echo esc_html( $f[0] ); ?></summary><p><?php echo esc_html( $f[1] ); ?></p></details><?php endforeach; ?>
	</section>
	<?php endif; ?>
<?php endif; ?>
</main>

<footer class="skl-foot">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a></footer>

<?php if ( $p && $opt ) : ?>
<div class="skl-sticky">
	<button type="button" class="skl-cta" id="skl-go">🛒 অর্ডার করুন – <span data-sum="go"><?php echo esc_html( $fmt( $min ) ); ?></span></button>
	<?php if ( $wa ) : ?><a class="wa" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $wa ) ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">💬</a><?php endif; ?>
</div>
<script>
(function(){
  var f=document.getElementById('skl-form'); if(!f) return;
  var areas=<?php echo wp_json_encode( $areas ); ?>, cur=<?php echo wp_json_encode( $cur ); ?>, pid=<?php echo (int) $p->get_id(); ?>;
  var msg=f.querySelector('.skl-msg'), btn=f.querySelector('.skl-submit'), qty=f.querySelector('[name=qty]'), go=document.getElementById('skl-go'), busy=false;
  function fm(n){return cur+Math.round(n).toLocaleString('en-IN');}
  function price(){var r=f.querySelector('[name=option]:checked');return r?parseFloat(r.getAttribute('data-price'))||0:0;}
  function area(){var r=f.querySelector('[name=area]:checked');return r?r.value:'outside';}
  function calc(){
    var q=Math.max(1,Math.min(20,parseInt(qty.value,10)||1)); qty.value=q;
    var sub=price()*q, s=areas[area()], c=s?((s.free&&s.free>0&&sub>=s.free)?0:s.cost):0;
    f.querySelector('[data-sum=sub]').textContent=fm(sub)+(q>1?' ('+q+'টি)':'');
    f.querySelector('[data-sum=ship]').textContent=c===0?'ফ্রি':fm(c);
    f.querySelector('[data-sum=tot]').textContent=fm(sub+c);
    var g=document.querySelector('[data-sum=go]'); if(g) g.textContent=fm(sub+c);
    if(!busy) btn.textContent='✅ অর্ডার কনফার্ম করুন – '+fm(sub+c);
    return sub+c;
  }
  function phone(){return (f.phone.value||'').replace(/\D/g,'').replace(/^880/,'0').replace(/^88/,'');}
  function check(){
    f.querySelectorAll('.skl-f.is-err').forEach(function(el){el.classList.remove('is-err');});
    var bad=null, text='';
    if(!f.name.value.trim()){bad=f.name;text='আপনার নাম লিখুন।';}
    else if(!/^01[3-9]\d{8}$/.test(phone())){bad=f.phone;text='সঠিক ১১ সংখ্যার মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।';}
    else if(!f.address.value.trim()){bad=f.address;text='ঠিকানা লিখুন।';}
    if(bad){ bad.closest('.skl-f').classList.add('is-err'); msg.textContent=text; msg.style.display='block'; bad.scrollIntoView({behavior:'smooth',block:'center'}); setTimeout(function(){bad.focus({preventScroll:true});},350); return false; }
    msg.style.display='none'; return true;
  }
  function send(){
    if(busy||!check()) return;
    busy=true; btn.disabled=true; btn.textContent='অর্ডার হচ্ছে…'; if(go){go.disabled=true;go.textContent='অর্ডার হচ্ছে…';}
    var fd=new FormData(f); fd.set('phone',phone());
    function fail(t){ busy=false; msg.textContent=t; msg.style.display='block'; btn.disabled=false; if(go){go.disabled=false;go.innerHTML='🛒 অর্ডার করুন – <span data-sum="go"></span>';} calc(); msg.scrollIntoView({behavior:'smooth',block:'center'}); }
    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){
      if(r&&r.success){ window.location.href=r.data.redirect; }
      else{ fail((r&&r.data&&r.data.message)||'অর্ডার নেওয়া যায়নি, আবার চেষ্টা করুন।'); }
    }).catch(function(){ fail('ইন্টারনেট সমস্যা, আবার চেষ্টা করুন।'); });
  }
  f.addEventListener('change',function(e){
    if(e.target.name==='option'){f.querySelectorAll('.skl-opt').forEach(function(l){l.classList.toggle('is-on',l.contains(e.target));});}
    if(e.target.name==='area'){f.querySelectorAll('.skl-area').forEach(function(l){l.classList.toggle('is-on',l.contains(e.target));});}
    calc();
  });
  f.addEventListener('input',function(e){ var w=e.target.closest('.skl-f'); if(w) w.classList.remove('is-err'); if(e.target===qty) calc(); });
  f.querySelectorAll('[data-q]').forEach(function(b){b.addEventListener('click',function(){qty.value=(parseInt(qty.value,10)||1)+parseInt(b.getAttribute('data-q'),10);calc();});});
  f.addEventListener('submit',function(e){e.preventDefault();send();});
  if(go) go.addEventListener('click',send);
  calc();
  var ic=false;
  f.addEventListener('focusin',function(){ if(!ic&&window.fbq){ic=true;fbq('track','InitiateCheckout',{content_ids:[String(pid)],content_type:'product',value:calc(),currency:'BDT'});} });
  if(window.fbq){ fbq('track','ViewContent',{content_ids:[String(pid)],content_type:'product',value:price(),currency:'BDT'}); }
})();
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
