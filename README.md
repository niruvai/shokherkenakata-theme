# Shokher Kenakata – WordPress/WooCommerce theme

Custom theme for **shokherkenakata.com**: marketplace layout with big search, category bar,
hero slider, category product rows, floating cart, WhatsApp button and mobile bottom navigation.

## Install / update on cPanel
1. cPanel → **Git™ Version Control** → Create → Clone URL `https://github.com/niruvai/shokherkenakata-theme.git`,
   Repository Path `public_html/wp-content/themes/shokher`.
2. WordPress → Appearance → Themes → activate **Shokher Kenakata**.
3. To update after new commits: cPanel → Git™ Version Control → Manage → **Pull or Deploy → Update from Remote**.

## Settings
Appearance → Customize → **Shokher Theme** (colors, hotline, Facebook/WhatsApp, slider images, homepage sections, footer).
Menus: *Category Bar (Desktop)*, *Mobile Menu*, *Footer – Company*.

Demo product images are in `demo/img/` (products tagged `demo` can be deleted any time).

## Shokher Shop Manager plugin
Lives in `plugins/shokher-manager/`. cPanel **Deploy HEAD Commit** (uses `.cpanel.yml`) copies it to
`wp-content/plugins/shokher-manager/`. Then activate it under Plugins. Admin menu: **Shokher Shop**.
