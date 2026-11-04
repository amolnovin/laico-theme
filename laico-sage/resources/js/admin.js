/**
 * Laico Design — WordPress Admin Panel & CPT Meta Box Scripts (`resources/js/admin.js`).
 *
 * Powers:
 * - Single Media Library picker buttons (`.laico-pick-media` for Logo, Favicon, OG Image, Video, Poster)
 * - Multi-image Project Gallery picker (`#laico-pick-gallery`, `#laico-clear-gallery`, `.laico-remove-thumb`)
 */

(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    // 1. Single Media URL Picker (`.laico-pick-media`)
    document.querySelectorAll('.laico-pick-media').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.wp || !window.wp.media) return;

        var targetSelector = btn.getAttribute('data-target');
        var mediaType = btn.getAttribute('data-type') || 'image';
        var input = targetSelector ? document.querySelector(targetSelector) : null;
        if (!input) return;

        var frame = window.wp.media({
          title: mediaType === 'video' ? 'انتخاب فایل ویدیو' : 'انتخاب تصویر',
          button: { text: 'انتخاب و جایگذاری' },
          library: { type: mediaType },
          multiple: false,
        });

        frame.on('select', function () {
          var attachment = frame.state().get('selection').first().toJSON();
          if (attachment && attachment.url) {
            input.value = attachment.url;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        frame.open();
      });
    });

    // 2. Multi-Image Project Gallery Picker (`#laico-pick-gallery`)
    var galleryBtn = document.getElementById('laico-pick-gallery');
    var clearBtn = document.getElementById('laico-clear-gallery');
    var hiddenIds = document.getElementById('laico_gallery_ids');
    var previewBox = document.getElementById('laico-gallery-preview');

    function syncGalleryIdsFromDom() {
      if (!hiddenIds || !previewBox) return;
      var ids = [];
      previewBox.querySelectorAll('.laico-gallery-thumb[data-id]').forEach(function (el) {
        var id = parseInt(el.getAttribute('data-id'), 10);
        if (id > 0 && ids.indexOf(id) === -1) {
          ids.push(id);
        }
      });
      hiddenIds.value = ids.join(',');
    }

    if (previewBox) {
      previewBox.addEventListener('click', function (e) {
        var rm = e.target.closest('.laico-remove-thumb');
        if (!rm) return;
        e.preventDefault();
        var item = rm.closest('.laico-gallery-thumb');
        if (item) {
          item.remove();
          syncGalleryIdsFromDom();
        }
      });
    }

    if (clearBtn && previewBox && hiddenIds) {
      clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        previewBox.innerHTML = '';
        hiddenIds.value = '';
      });
    }

    if (galleryBtn && previewBox && hiddenIds) {
      galleryBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.wp || !window.wp.media) return;

        var frame = window.wp.media({
          title: 'انتخاب تصاویر گالری پروژه',
          button: { text: 'افزودن به گالری پروژه' },
          library: { type: 'image' },
          multiple: true,
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection');
          selection.each(function (attachment) {
            var data = attachment.toJSON();
            if (!data || !data.id) return;
            if (previewBox.querySelector('.laico-gallery-thumb[data-id="' + data.id + '"]')) {
              return;
            }
            var thumbUrl =
              (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) ||
              data.url;

            var div = document.createElement('div');
            div.className = 'laico-gallery-thumb';
            div.setAttribute('data-id', String(data.id));

            var img = document.createElement('img');
            img.src = thumbUrl;
            img.alt = '';

            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'laico-remove-thumb';
            rmBtn.setAttribute('aria-label', 'حذف');
            rmBtn.textContent = '×';

            div.appendChild(img);
            div.appendChild(rmBtn);
            previewBox.appendChild(div);
          });

          syncGalleryIdsFromDom();
        });

        frame.open();
      });
    }
  });
})();
