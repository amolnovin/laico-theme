/**
 * Laico Design — WordPress Admin Panel & CPT Meta Box Scripts (`resources/js/admin.js`).
 *
 * Powers:
 * - Single Media Library picker buttons (`.laico-pick-media` for Logo, Favicon, OG Image, Video, Poster)
 * - About Page Media Cards (`.laico-pick-media-card` & `.laico-reset-media-card` with live preview + attachment ID)
 * - Multi-image Project Gallery picker (`#laico-pick-gallery`, `#laico-clear-gallery`, `.laico-remove-thumb`)
 * - Live English fields toggle (`#laico_enable_english`) & About Page template detection
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

    // 2. About Page Rich Media Cards (`.laico-pick-media-card` & `.laico-reset-media-card`)
    document.querySelectorAll('[data-media-card]').forEach(function (card) {
      var idInput = card.querySelector('[data-media-id]');
      var urlInput = card.querySelector('[data-media-url]');
      var altInput = card.querySelector('[data-media-alt]');
      var previewImg = card.querySelector('.laico-media-card-preview img');
      var pickBtn = card.querySelector('.laico-pick-media-card');
      var resetBtn = card.querySelector('.laico-reset-media-card');

      if (urlInput && previewImg) {
        urlInput.addEventListener('change', function () {
          var val = urlInput.value.trim();
          if (idInput) idInput.value = '';
          previewImg.src = val || previewImg.getAttribute('data-default-src') || '';
        });
      }

      if (pickBtn) {
        pickBtn.addEventListener('click', function (e) {
          e.preventDefault();
          if (!window.wp || !window.wp.media) return;

          var frame = window.wp.media({
            title: 'انتخاب تصویر',
            button: { text: 'استفاده از این تصویر' },
            library: { type: 'image' },
            multiple: false,
          });

          frame.on('select', function () {
            var data = frame.state().get('selection').first().toJSON();
            if (!data || !data.url) return;
            if (idInput) idInput.value = data.id ? String(data.id) : '';
            if (urlInput) urlInput.value = data.url;
            if (altInput && !altInput.value.trim() && data.alt) {
              altInput.value = data.alt;
            }
            if (previewImg) {
              var previewSrc =
                (data.sizes && data.sizes.medium_large && data.sizes.medium_large.url) ||
                (data.sizes && data.sizes.large && data.sizes.large.url) ||
                data.url;
              previewImg.src = previewSrc;
            }
          });

          frame.open();
        });
      }

      if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
          e.preventDefault();
          if (idInput) idInput.value = '';
          if (urlInput) urlInput.value = '';
          if (previewImg) {
            previewImg.src = previewImg.getAttribute('data-default-src') || '';
          }
        });
      }
    });

    // 3. Live Toggle for English Settings Fields (`#laico_enable_english`)
    var enToggle = document.getElementById('laico_enable_english');
    if (enToggle) {
      var syncEnFields = function () {
        var on = !!enToggle.checked;
        document.querySelectorAll('.laico-en-only-field').forEach(function (el) {
          el.style.display = on ? '' : 'none';
        });
      };
      enToggle.addEventListener('change', syncEnFields);
      syncEnFields();
    }

    // 4. Automatically reveal `#laico_about_page_meta` when editing the About page or choosing its template
    var aboutBox = document.getElementById('laico_about_page_meta');
    if (aboutBox) {
      var syncAboutBoxVisibility = function (tpl, slug) {
        var isAbout =
          aboutBox.classList.contains('is-about-page-active') ||
          slug === 'about' ||
          (tpl && String(tpl).indexOf('template-about') !== -1);
        aboutBox.classList.toggle('is-about-page-hidden', !isAbout);
      };

      var classicTpl = document.getElementById('page_template');
      if (classicTpl) {
        classicTpl.addEventListener('change', function () {
          syncAboutBoxVisibility(classicTpl.value, '');
        });
      }

      if (window.wp && window.wp.data && window.wp.data.subscribe && window.wp.data.select) {
        var lastStateKey = '';
        window.wp.data.subscribe(function () {
          var editor = window.wp.data.select('core/editor');
          if (!editor || typeof editor.getEditedPostAttribute !== 'function') return;
          var tpl = editor.getEditedPostAttribute('template') || '';
          var slug = editor.getEditedPostAttribute('slug') || '';
          var key = tpl + '|' + slug;
          if (key !== lastStateKey) {
            lastStateKey = key;
            syncAboutBoxVisibility(tpl, slug);
          }
        });
      }
    }

    // 5. Multi-Image Project Gallery Picker (`#laico-pick-gallery`)
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
