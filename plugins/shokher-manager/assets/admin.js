/* Shokher Shop Manager admin scripts */
jQuery(function ($) {
  'use strict';

  /* ---------- Discount preview ---------- */
  function num(v) { return parseFloat(String(v || '').replace(/[^0-9.]/g, '')) || 0; }
  function updDiscount() {
    var p = num($('input[name=price]').val()), s = num($('input[name=sale]').val());
    $('[data-skm-discount]').text(p && s && s < p ? '-' + Math.round(100 - s / p * 100) + '%' : '—');
  }
  $(document).on('input', 'input[name=price], input[name=sale]', updDiscount);
  updDiscount();

  /* ---------- Packs ---------- */
  $('[data-skm-packs-toggle]').on('change', function () {
    var on = this.checked;
    $('[data-skm-packs]').prop('hidden', !on);
    $('[data-skm-simple]').prop('hidden', on);
  });
  $('[data-skm-pack-add]').on('click', function () {
    $('[data-skm-pack-rows]').append(
      '<tr><td><input type="hidden" name="pack_id[]" value="0"><input type="text" name="pack_name[]" placeholder="যেমন: 3 Pieces"></td>' +
      '<td><input type="text" inputmode="decimal" name="pack_price[]"></td>' +
      '<td><input type="text" inputmode="decimal" name="pack_sale[]"></td>' +
      '<td><button type="button" class="skm-x" data-skm-pack-del aria-label="মুছুন">✕</button></td></tr>'
    );
  });
  $(document).on('click', '[data-skm-pack-del]', function () {
    if ($('[data-skm-pack-rows] tr').length > 1) { $(this).closest('tr').remove(); }
  });

  /* ---------- Images ---------- */
  var $imgs = $('[data-skm-imgs]'), $imgsIn = $('[data-skm-imgs-input]'), frame;
  function syncImgs() {
    $imgsIn.val($imgs.children('li').map(function () { return $(this).data('id'); }).get().join(','));
  }
  if ($imgs.length) {
    $imgs.sortable({ update: syncImgs, tolerance: 'pointer' });
  }
  $('[data-skm-img-add]').on('click', function (e) {
    e.preventDefault();
    if (!frame) {
      frame = wp.media({ title: 'প্রোডাক্টের ছবি বাছুন', button: { text: 'ছবি যোগ করুন' }, library: { type: 'image' }, multiple: 'add' });
      frame.on('select', function () {
        frame.state().get('selection').each(function (att) {
          var a = att.toJSON();
          if ($imgs.children('[data-id="' + a.id + '"]').length) return;
          var src = (a.sizes && (a.sizes.thumbnail || a.sizes.medium) || a).url;
          $imgs.append('<li data-id="' + a.id + '"><img src="' + src + '" alt=""><button type="button" class="skm-x" data-skm-img-del aria-label="সরান">✕</button></li>');
        });
        syncImgs();
      });
    }
    frame.open();
  });
  $(document).on('click', '[data-skm-img-del]', function () { $(this).closest('li').remove(); syncImgs(); });

  /* ---------- Inline product row save ---------- */
  $(document).on('click', '[data-skm-save-row]', function () {
    var $tr = $(this).closest('tr'), $b = $(this);
    var data = { action: 'skm_save_row', nonce: SKM.nonce, id: $tr.data('id') };
    $tr.find('.skm-in').each(function () { data[this.name] = $(this).val(); });
    $b.prop('disabled', true).text('…');
    $.post(SKM.ajax, data).done(function (r) {
      $tr.removeClass('is-error').addClass(r && r.success ? 'is-saved' : 'is-error');
      setTimeout(function () { $tr.removeClass('is-saved'); }, 1500);
    }).fail(function () { $tr.addClass('is-error'); })
      .always(function () { $b.prop('disabled', false).text('✔'); });
  });
  $(document).on('keydown', '.skm-products .skm-in', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).closest('tr').find('[data-skm-save-row]').trigger('click'); }
  });

  /* ---------- Order status ---------- */
  $(document).on('change', '[data-skm-order-status]', function () {
    var $s = $(this), $tr = $s.closest('tr');
    $s.prop('disabled', true);
    $.post(SKM.ajax, { action: 'skm_order_status', nonce: SKM.nonce, id: $tr.data('id'), status: $s.val() })
      .done(function (r) { $tr.addClass(r && r.success ? 'is-saved' : 'is-error'); setTimeout(function () { $tr.removeClass('is-saved'); }, 1500); })
      .fail(function () { $tr.addClass('is-error'); })
      .always(function () { $s.prop('disabled', false); });
  });
});
