/* LAICO DESIGN — interactions, language, form */
(function () {
  "use strict";

  var dict = {
    fa: {
      "nav-home": "صفحه اصلی",
      "nav-services": "خدمات",
      "nav-projects": "پروژه‌ها",
      "nav-journal": "مجله",
      "nav-about": "درباره ما",
      "nav-contact": "تماس با ما",
      "cta": "درخواست مشاوره",
      "menu": "منو",
      "close": "بستن",
      "hero-kicker": "معماری داخلی • سیستم آشپزخانه لوکس",
      "hero-1": "از ایده تا اجرا با لایکو",
      "hero-2": "دیزاین",
      "hero-lede": "لایکو دیزاین؛ طراحی و اجرای آشپزخانه، پذیرایی و اتاق، حمام و فضاهای کافه-رستوران با زبان مینیمال و متریال صادق.",
      "col-eye": "۰۱ — مجموعه‌ها",
      "col-title": "چهار فضا، یک استاندارد ساخت",
      "col-all": "همه مجموعه‌ها",
      "col-kitchen": "آشپزخانه",
      "col-living": "پذیرایی و اتاق",
      "col-bath": "حمام",
      "col-cafe": "کافه و رستوران",
      "prj-eye": "۰۲ — پروژه‌های منتخب",
      "prj-title": "نمونه کارها، امضای کیفیت",
      "prj-all": "آرشیو کامل",
      "tag-kitchen": "آشپزخانه",
      "tag-interior": "معماری داخلی",
      "tag-cafe": "کافه و رستوران",
      "tag-bath": "حمام",
      "p-aftab": "پنت‌هاوس آفتاب",
      "p-haraz": "ویلای هراز",
      "p-acajou": "کافه اکاژو",
      "p-darya": "خانه دریا",
      "m-aftab": "۳۱۰ مترمربع • ۱۴۰۳",
      "m-haraz": "۴۲۰ مترمربع • ۱۴۰۴",
      "m-acajou": "۱۸۰ مترمربع • ۱۴۰۴",
      "m-darya": "۱۶۰ مترمربع • ۱۴۰۳",
      "why-eye": "۰۳ — استودیو",
      "why-quote": "چرا لایکو دیزاین؟ چون از اولین اسکچ تا آخرین پیچ نصب، یک تیم و یک استاندارد پشت کار شماست؛ متریال صادق، اجرای دقیق و تعهدی که سال‌ها بعد از تحویل هم همراه شما می‌ماند.",
      "st-projects": "پروژه اجراشده",
      "st-years": "سال تجربه",
      "st-brands": "برند اروپایی",
      "sys-eye": "۰۴ — سیستم‌ها و متریال",
      "sys-title": "بدون دستگیره، بدون درز اضافی",
      "sys-body": "سیستم‌های آشپزخانه، کمد و پذیرایی لایکو بر پایه پروفیل‌های آلومینیومی، روکش‌های لاک مات و صفحات سنگ طبیعی طراحی می‌شوند؛ با تلورانس اجرایی زیر یک میلی‌متر.",
      "spec-finish": "روکش",
      "spec-finish-v": "لاک مات، چوب راش، گرس porcelain",
      "spec-hw": "سخت‌افزار",
      "spec-hw-v": "لولای آرام‌بند، ریل فول بلوم",
      "spec-w": "ضمانت",
      "spec-w-v": "۱۰ سال ساخت، ۲ سال خدمات",
      "sh-eye": "۰۵ — شو روم",
      "sh-title": "متریال و کیفیت را لمس کنید",
      "sh-body": "بازدید از شو روم با هماهنگی قبلی؛ همراه با مشاوره فنی و انتخاب پالت متریال.",
      "addr": "آدرس",
      "addr-v": "آمل، دریای ۲۰",
      "hours": "ساعات",
      "hours-v": "شنبه تا پنجشنبه، ۱۰ تا ۱۹",
      "phone": "تماس",
      "form-eye": "۰۶ — شروع پروژه",
      "form-1": "چطور می‌توانیم",
      "form-2": "همکاری کنیم؟",
      "form-body": "فرم را پر کنید؛ تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.",
      "f-name": "نام و نام خانوادگی",
      "f-phone": "شماره تماس",
      "f-type": "نوع پروژه",
      "f-note": "توضیح کوتاه",
      "f-note-ph": "متراژ، شهر، بازه زمانی",
      "about-ph-note": "متن پیام را بنویسید..",
      "f-send": "ارسال درخواست",
      "f-ok-title": "درخواست شما ثبت شد.",
      "f-ok-body": "تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.",
      "err-name": "نام را بنویسید.",
      "err-phone": "شماره تماس معتبر وارد کنید.",
      "err-note": "چند خط درباره فضا بنویسید.",
      "opt-kitchen": "آشپزخانه",
      "opt-living": "پذیرایی و اتاق",
      "opt-bath": "حمام",
      "opt-cafe": "کافه و رستوران",
      "opt-wardrobe": "کمد و سیستم",
      "opt-full": "بازسازی کامل",
      "foot-about": "مجموعه تخصصی معماری داخلی و سیستم لوکس آشپزخانه.",
      "foot-col": "مجموعه‌ها",
      "foot-studio": "استودیو",
      "foot-contact": "تماس",
      "foot-about-us": "درباره ما",
      "foot-process": "فرآیند کار",
      "foot-journal": "ژورنال",
      "foot-collab": "همکاری",
      "foot-material": "متریال",
      "foot-ig": "اینستاگرام",
      "foot-wa": "واتس‌اپ",
      "copy": "© ۱۴۰۵ لایکو دیزاین — تمام حقوق محفوظ است",
      "privacy": "حریم خصوصی",
      "cookies": "کوکی‌ها",
      "filter-all": "همه",
      "next": "پروژه بعد",
      "book": "رزرو بازدید",
      "read": "ادامه"
    },
    en: {
      "nav-home": "Home",
      "nav-services": "Services",
      "nav-projects": "Projects",
      "nav-journal": "Journal",
      "nav-about": "About",
      "nav-contact": "Contact",
      "cta": "Book a consultation",
      "menu": "Menu",
      "close": "Close",
      "hero-kicker": "Interior architecture • Luxury kitchen system",
      "hero-1": "From the idea to the last screw,",
      "hero-2": "with Laico Design",
      "hero-lede": "Laico Design plans and builds kitchens, living rooms, baths, and café spaces in a minimal language, with honest materials.",
      "col-eye": "01 — Collections",
      "col-title": "Four rooms, one standard of making",
      "col-all": "All collections",
      "col-kitchen": "Kitchen",
      "col-living": "Living",
      "col-bath": "Bath",
      "col-cafe": "Café & restaurant",
      "prj-eye": "02 — Selected work",
      "prj-title": "Projects, signed by quality",
      "prj-all": "Full archive",
      "tag-kitchen": "Kitchen",
      "tag-interior": "Interior",
      "tag-cafe": "Café",
      "tag-bath": "Bath",
      "p-aftab": "Penthouse Aftab",
      "p-haraz": "Villa Haraz",
      "p-acajou": "Café Acajou",
      "p-darya": "House Darya",
      "m-aftab": "310 m² • 2024",
      "m-haraz": "420 m² • 2025",
      "m-acajou": "180 m² • 2025",
      "m-darya": "160 m² • 2024",
      "why-eye": "03 — Studio",
      "why-quote": "Why Laico? Because one team and one standard stay with the work, from the first sketch to the last screw. Honest materials, precise installation, and a commitment that remains years after handover.",
      "st-projects": "Completed projects",
      "st-years": "Years of practice",
      "st-brands": "European brands",
      "sys-eye": "04 — Systems & materials",
      "sys-title": "No handles. No extra joints.",
      "sys-body": "Laico kitchen, wardrobe, and living systems are built on aluminium profiles, matte lacquer, and natural stone, with an installation tolerance under one millimetre.",
      "spec-finish": "Finish",
      "spec-finish-v": "Matte lacquer, beech, gres porcelain",
      "spec-hw": "Hardware",
      "spec-hw-v": "Soft-close hinge, Blum full-extension runner",
      "spec-w": "Warranty",
      "spec-w-v": "10 years on making, 2 years of service",
      "sh-eye": "05 — Showroom",
      "sh-title": "Touch the material.",
      "sh-body": "Showroom visits are by appointment, with a technical consultation and a material palette.",
      "addr": "Address",
      "addr-v": "Amol, Darya 20",
      "hours": "Hours",
      "hours-v": "Saturday to Thursday, 10–19",
      "phone": "Phone",
      "form-eye": "06 — Start a project",
      "form-1": "How should we",
      "form-2": "work together?",
      "form-body": "Send the form. The design team will call within 48 working hours to set the first meeting.",
      "f-name": "Full name",
      "f-phone": "Phone",
      "f-type": "Project type",
      "f-note": "A short note",
      "f-note-ph": "Area, city, timeline",
      "about-ph-note": "Write your message..",
      "f-send": "Send request",
      "f-ok-title": "Your request is in.",
      "f-ok-body": "The design team will call within 48 working hours to arrange the first meeting.",
      "err-name": "Please enter your name.",
      "err-phone": "Enter a valid phone number.",
      "err-note": "Tell us a little about the space.",
      "opt-kitchen": "Kitchen",
      "opt-living": "Living",
      "opt-bath": "Bath",
      "opt-cafe": "Café & restaurant",
      "opt-wardrobe": "Wardrobe system",
      "opt-full": "Full renovation",
      "foot-about": "A studio for interior architecture and luxury kitchen systems.",
      "foot-col": "Collections",
      "foot-studio": "Studio",
      "foot-contact": "Contact",
      "foot-about-us": "About",
      "foot-process": "Process",
      "foot-journal": "Journal",
      "foot-collab": "Collaborate",
      "foot-material": "Materials",
      "foot-ig": "Instagram",
      "foot-wa": "WhatsApp",
      "copy": "© 2026 Laico Design — All rights reserved",
      "privacy": "Privacy",
      "cookies": "Cookies",
      "filter-all": "All",
      "next": "Next project",
      "book": "Book a visit",
      "read": "Read"
    }
  };

  if (window.laicoConfig && window.laicoConfig.i18n) {
    if (window.laicoConfig.i18n.fa) {
      Object.keys(window.laicoConfig.i18n.fa).forEach(function (k) { dict.fa[k] = window.laicoConfig.i18n.fa[k]; });
    }
    if (window.laicoConfig.i18n.en) {
      Object.keys(window.laicoConfig.i18n.en).forEach(function (k) { dict.en[k] = window.laicoConfig.i18n.en[k]; });
    }
  }

  var langSwitcherEnabled = !!(window.laicoConfig && window.laicoConfig.enableLangSwitcher);

  function lang() {
    if (!langSwitcherEnabled) return "fa";
    return document.documentElement.lang === "en" ? "en" : "fa";
  }

  function t(key) {
    var pack = dict[lang()] || dict.fa;
    return pack[key] != null ? pack[key] : (dict.fa[key] || "");
  }

  function apply(language) {
    document.documentElement.lang = language;
    document.documentElement.dir = language === "en" ? "ltr" : "rtl";
    try { localStorage.setItem("laico-lang", language); } catch (e) {}
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var key = el.getAttribute("data-i18n");
      if (dict[language] && dict[language][key] != null) el.textContent = dict[language][key];
    });
    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-placeholder");
      if (dict[language] && dict[language][key] != null) el.setAttribute("placeholder", dict[language][key]);
    });
    document.querySelectorAll("[data-i18n-aria]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-aria");
      if (dict[language] && dict[language][key] != null) el.setAttribute("aria-label", dict[language][key]);
    });
    document.querySelectorAll("[data-alt-en]").forEach(function (el) {
      el.setAttribute("alt", language === "en" ? el.getAttribute("data-alt-en") : (el.getAttribute("data-alt-fa") || el.getAttribute("alt")));
    });
    document.querySelectorAll("[data-lang]").forEach(function (btn) {
      btn.classList.toggle("is-active", btn.getAttribute("data-lang") === language);
    });
    var en = language === "en";
    document.querySelectorAll(".lang-fa").forEach(function (el) { el.hidden = en; });
    document.querySelectorAll(".lang-en").forEach(function (el) { el.hidden = !en; });
    document.title = language === "en"
      ? (document.body.getAttribute("data-title-en") || "Laico Design")
      : (document.body.getAttribute("data-title-fa") || "لایکو دیزاین");
  }

  /* language */
  document.querySelectorAll("[data-lang]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      apply(btn.getAttribute("data-lang"));
    });
  });

  /* header scroll */
  var header = document.querySelector("[data-header]");
  function onScroll() {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 12);
  }
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  /* mobile menu */
  var burger = document.querySelector("[data-burger]");
  function setMenu(open) {
    if (!header) return;
    header.classList.toggle("is-open", open);
    document.body.style.overflow = open ? "hidden" : "";
    if (burger) burger.setAttribute("aria-expanded", open ? "true" : "false");
  }
  if (burger) {
    burger.addEventListener("click", function () {
      setMenu(!header.classList.contains("is-open"));
    });
  }
  document.querySelectorAll(".nav-panel a").forEach(function (a) {
    a.addEventListener("click", function () { setMenu(false); });
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") setMenu(false);
  });
  window.addEventListener("resize", function () {
    if (window.innerWidth > 1180) setMenu(false);
  });

  /* active nav */
  var page = document.body.getAttribute("data-page");
  if (page) {
    document.querySelectorAll("[data-nav]").forEach(function (a) {
      var on = a.getAttribute("data-nav") === page;
      a.classList.toggle("is-active", on);
      if (on) a.setAttribute("aria-current", "page");
    });
  }

  /* counts */
  var counted = false;
  function runCounts() {
    if (counted) return;
    var nodes = document.querySelectorAll("[data-count]");
    if (!nodes.length) return;
    var first = nodes[0];
    var rect = first.getBoundingClientRect();
    if (rect.top > window.innerHeight * 0.92) return;
    counted = true;
    nodes.forEach(function (el) {
      var target = parseInt(el.getAttribute("data-count"), 10) || 0;
      var prefix = el.getAttribute("data-prefix") || "";
      var start = performance.now();
      var dur = 900;
      function frame(now) {
        var p = Math.min(1, (now - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + Math.round(target * eased);
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    });
  }
  window.addEventListener("scroll", runCounts, { passive: true });
  runCounts();

  /* custom select */
  document.querySelectorAll("[data-select]").forEach(function (root) {
    var btn = root.querySelector(".select-btn");
    var menu = root.querySelector(".select-menu");
    var valueEl = root.querySelector(".select-value");
    var hidden = root.querySelector("input[type=hidden]");
    if (!btn || !menu) return;
    function close() {
      menu.hidden = true;
      root.classList.remove("is-open");
      btn.setAttribute("aria-expanded", "false");
    }
    function open() {
      menu.hidden = false;
      root.classList.add("is-open");
      btn.setAttribute("aria-expanded", "true");
    }
    btn.addEventListener("click", function () {
      if (menu.hidden) open(); else close();
    });
    menu.querySelectorAll("button").forEach(function (opt) {
      opt.addEventListener("click", function () {
        var key = opt.getAttribute("data-i18n");
        valueEl.textContent = opt.textContent;
        valueEl.setAttribute("data-i18n", key || "");
        if (hidden) hidden.value = opt.getAttribute("data-value");
        menu.querySelectorAll("button").forEach(function (b) {
          b.setAttribute("aria-selected", b === opt ? "true" : "false");
        });
        close();
      });
    });
    document.addEventListener("click", function (e) {
      if (!root.contains(e.target)) close();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") close();
    });
  });

  /* project filters */
  document.querySelectorAll("[data-filter]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var key = btn.getAttribute("data-filter");
      document.querySelectorAll("[data-filter]").forEach(function (b) {
        b.classList.toggle("is-active", b === btn);
      });
      document.querySelectorAll("[data-kind]").forEach(function (card) {
        var show = key === "all" || card.getAttribute("data-kind") === key;
        card.classList.toggle("is-hidden", !show);
      });
    });
  });

  function normalizeDigits(str) {
    return String(str || "")
      .replace(/[۰-۹]/g, function (d) { return String("۰۱۲۳۴۵۶۷۸۹".indexOf(d)); })
      .replace(/[٠-٩]/g, function (d) { return String("٠١٢٣٤٥٦٧٨٩".indexOf(d)); });
  }

  /* forms — Server-backed REST API + Admin-AJAX fallback for WordPress */
  document.querySelectorAll("[data-form]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (form.classList.contains("is-submitting")) return;

      var language = lang();
      var ok = true;

      var prevAlert = form.querySelector(".form-server-error");
      if (prevAlert) prevAlert.remove();

      function setError(name, msgOrKey, isRaw) {
        var field = form.querySelector('[data-field="' + name + '"]');
        if (!field) return;
        var err = field.querySelector(".field-error");
        if (msgOrKey) {
          field.classList.add("is-invalid");
          if (err) err.textContent = isRaw ? msgOrKey : t(msgOrKey);
          ok = false;
        } else {
          field.classList.remove("is-invalid");
          if (err) err.textContent = "";
        }
      }

      var name = ((form.querySelector('[name="name"]') || {}).value || "").trim();
      var rawPhone = ((form.querySelector('[name="phone"]') || {}).value || "").trim();
      var note = ((form.querySelector('[name="note"]') || {}).value || "").trim();
      var type = ((form.querySelector('[name="type"]') || {}).value || "").trim();
      var sourcePage = ((form.querySelector('[name="source_page"]') || {}).value || document.title || "").trim();
      var honeypot = ((form.querySelector('[name="website_url"]') || {}).value || "").trim();

      var digits = normalizeDigits(rawPhone).replace(/\D/g, "");
      if (digits.indexOf("0098") === 0) digits = digits.slice(4);
      else if (digits.indexOf("98") === 0 && digits.length >= 11) digits = digits.slice(2);
      else if (digits.indexOf("0") === 0) digits = digits.slice(1);

      setError("name", name.length < 2 ? "err-name" : "");
      setError("phone", digits.length < 8 ? "err-phone" : "");
      setError("note", note.length < 4 ? "err-note" : "");

      // WP Math Captcha extraction & validation (when rendered inside form)
      var wpmcWrap = form.querySelector(".wpmc-wrap");
      var wpmcAnswerEl = wpmcWrap ? wpmcWrap.querySelector('[name="wpmc_answer"]') : null;
      var wpmcTokenEl = wpmcWrap ? wpmcWrap.querySelector('[name="wpmc_token"]') : null;
      var wpmcHpEl = wpmcWrap ? wpmcWrap.querySelector('[name="wpmc_website"]') : null;
      var wpmcAnswer = wpmcAnswerEl ? normalizeDigits(wpmcAnswerEl.value).trim() : "";

      if (wpmcWrap) {
        if (!wpmcAnswer || !/^-?\d+$/.test(wpmcAnswer)) {
          setError(
            "captcha",
            language === "en" ? "Please enter the math security answer." : "لطفاً پاسخ عبارت امنیتی را به عدد وارد کنید.",
            true
          );
        } else {
          setError("captcha", "");
        }
      }

      if (!ok) {
        var bad = form.querySelector(".is-invalid input, .is-invalid textarea");
        if (bad) bad.focus();
        return;
      }

      var cfg = window.laicoConfig || {};
      var payload = {
        name: name,
        phone: "+98" + digits,
        type: type,
        note: note,
        source_page: sourcePage,
        source_url: window.location.href,
        lang: language,
        website_url: honeypot,
        _laico_nonce: cfg.nonce || ""
      };

      if (wpmcWrap) {
        payload.wpmc_answer = wpmcAnswer;
        payload.wpmc_token = wpmcTokenEl ? wpmcTokenEl.value : "";
        payload.wpmc_website = wpmcHpEl ? wpmcHpEl.value : "";
      }

      function showSuccess(titleText, bodyText) {
        form.hidden = true;
        var done = form.parentElement.querySelector("[data-success]");
        if (done) {
          done.hidden = false;
          var h = done.querySelector("h3");
          var p = done.querySelector("p");
          if (h) h.textContent = titleText || t("f-ok-title");
          if (p) p.textContent = bodyText || t("f-ok-body");
        }
      }

      function showServerError(msg) {
        var box = document.createElement("div");
        box.className = "form-server-error";
        box.setAttribute("role", "alert");
        box.textContent = msg;
        form.appendChild(box);
      }

      if (!cfg.restUrl && !cfg.ajaxUrl) {
        showSuccess();
        return;
      }

      form.classList.add("is-submitting");

      function sendViaAjaxFallback() {
        if (!cfg.ajaxUrl) {
          return Promise.reject(new Error("No endpoint"));
        }
        var fd = new URLSearchParams();
        fd.append("action", "laico_submit_form");
        Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
        return fetch(cfg.ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          body: fd.toString()
        }).then(function (r) { return r.json(); });
      }

      var reqPromise = cfg.restUrl
        ? fetch(cfg.restUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
              "Content-Type": "application/json",
              "X-Laico-Nonce": cfg.nonce || ""
            },
            body: JSON.stringify(payload)
          }).then(function (res) {
            if (res.status === 404 || res.status === 405 || res.status === 401 || res.status === 403) {
              return sendViaAjaxFallback();
            }
            return res.json();
          }).catch(function () {
            return sendViaAjaxFallback();
          })
        : sendViaAjaxFallback();

      reqPromise
        .then(function (data) {
          form.classList.remove("is-submitting");
          if (data && data.ok) {
            showSuccess(data.title, data.message);
            return;
          }
          if (data && data.errors) {
            Object.keys(data.errors).forEach(function (fieldKey) {
              setError(fieldKey, data.errors[fieldKey], true);
            });
          }
          if (wpmcWrap && data && (data.captcha_refresh || (data.errors && data.errors.captcha))) {
            var refreshBtn = wpmcWrap.querySelector(".wpmc-refresh");
            if (refreshBtn && data.captcha_refresh) {
              refreshBtn.click();
            }
            if (wpmcAnswerEl) {
              wpmcAnswerEl.focus();
            }
          }
          if (!(data && data.errors && data.errors.captcha && Object.keys(data.errors).length === 1)) {
            showServerError((data && data.message) || (language === "en" ? "Submission failed. Please try again." : "خطا در ثبت درخواست. لطفاً دوباره تلاش کنید."));
          }
        })
        .catch(function () {
          form.classList.remove("is-submitting");
          showServerError(language === "en" ? "Network error. Please call us directly." : "خطا در ارتباط با سرور. لطفاً با شمارهٔ دفتر تماس بگیرید.");
        });
    });
  });

  /* Helper: check whether an internal path should skip prefetch / transition loader */
  function isExcludedInternalUrl(urlObj) {
    var path = urlObj.pathname || "";
    var cfg = window.laicoConfig || {};
    var prefixes = Array.isArray(cfg.excludePaths) ? cfg.excludePaths : ["/wp-admin", "/wp-login.php", "/wp-json"];
    for (var i = 0; i < prefixes.length; i++) {
      if (prefixes[i] && path.indexOf(prefixes[i]) === 0) return true;
    }
    if (/\.(pdf|zip|rar|7z|mp4|mp3|docx?|xlsx?|pptx?)($|\?)/i.test(path)) return true;
    return false;
  }

  /* Centered Page-Transition Loading Indicator (`#laico-page-loader`) */
  (function () {
    var loader = document.getElementById("laico-page-loader");
    var cfg = window.laicoConfig || {};
    if (!loader || cfg.pageLoader === false) return;

    var safetyTimer = 0;
    function showLoader() {
      loader.classList.add("is-visible");
      loader.setAttribute("aria-hidden", "false");
      clearTimeout(safetyTimer);
      safetyTimer = setTimeout(hideLoader, 8000);
    }
    function hideLoader() {
      clearTimeout(safetyTimer);
      loader.classList.remove("is-visible");
      loader.setAttribute("aria-hidden", "true");
    }

    hideLoader();
    window.addEventListener("pageshow", hideLoader);
    window.addEventListener("popstate", hideLoader);
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") hideLoader();
    });

    document.addEventListener("click", function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target && e.target.closest ? e.target.closest("a[href]") : null;
      if (!a) return;
      if (
        a.hasAttribute("download") ||
        a.hasAttribute("data-no-loader") ||
        a.hasAttribute("data-gallery-item") ||
        (a.target && a.target !== "_self")
      ) {
        return;
      }

      var rawHref = a.getAttribute("href") || "";
      if (!rawHref || rawHref.charAt(0) === "#" || /^(mailto:|tel:|javascript:|sms:|whatsapp:)/i.test(rawHref)) {
        return;
      }

      var dest;
      try {
        dest = new URL(a.href, window.location.href);
      } catch (err) {
        return;
      }

      if (dest.origin !== window.location.origin) return;
      if (isExcludedInternalUrl(dest)) return;

      // Same-page hash navigation should scroll smoothly without showing full-page loader
      if (dest.pathname === window.location.pathname && dest.search === window.location.search && dest.hash) {
        return;
      }
      if (dest.href === window.location.href) {
        return;
      }

      showLoader();
    });
  })();

  /* Speculative hover prefetching for internal navigation (when enabled in Optimization settings) */
  if (window.laicoConfig && window.laicoConfig.prefetch) {
    var prefetched = {};
    document.addEventListener("mouseover", function (e) {
      var a = e.target && e.target.closest ? e.target.closest("a[href]") : null;
      if (!a) return;
      var href = a.href;
      if (!href || prefetched[href] || href.indexOf(window.location.origin) !== 0) return;
      if (href.indexOf("#") !== -1) return;
      try {
        var u = new URL(href, window.location.href);
        if (isExcludedInternalUrl(u)) return;
      } catch (err) {
        return;
      }
      var conn = navigator.connection;
      if (conn && (conn.saveData || /2g/.test(conn.effectiveType || ""))) return;
      prefetched[href] = true;
      var link = document.createElement("link");
      link.rel = "prefetch";
      link.href = href;
      document.head.appendChild(link);
    }, { passive: true });
  }

  /* smooth scrolling: CSS handles anchors (scroll-behavior), this adds eased
     wheel scrolling for mouse wheels only (trackpads/touch stay native) */
  (function () {
    var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduce) return;
    var target = 0, current = 0, raf = 0, lastSet = -1;
    function maxY() { return document.documentElement.scrollHeight - window.innerHeight; }
    function jump(y) {
      lastSet = Math.round(y);
      window.scrollTo({ top: y, left: 0, behavior: "instant" });
    }
    function step() {
      if (lastSet >= 0 && Math.abs(window.scrollY - lastSet) > 3) { raf = 0; return; }   // user took over
      current += (target - current) * 0.14;
      if (Math.abs(target - current) < 0.6) { current = target; jump(current); raf = 0; return; }
      jump(current);
      raf = requestAnimationFrame(step);
    }
    window.addEventListener("wheel", function (e) {
      if (e.ctrlKey || e.defaultPrevented) return;
      if (e.deltaMode === 0 && Math.abs(e.deltaY) < 50) return;            // trackpad
      if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
      if (document.body.style.overflow === "hidden") return;                // menu / lightbox open
      var el = e.target;
      while (el && el !== document.body && el.nodeType === 1) {
        var st = window.getComputedStyle(el);
        if (/(auto|scroll)/.test(st.overflowY) && el.scrollHeight > el.clientHeight) return;
        el = el.parentElement;
      }
      e.preventDefault();
      if (!raf) { target = window.scrollY; current = window.scrollY; }
      var dy = e.deltaMode === 1 ? e.deltaY * 32 : e.deltaY;
      target = Math.max(0, Math.min(maxY(), target + dy));
      if (!raf) raf = requestAnimationFrame(step);
    }, { passive: false });
  })();

  /* apply saved language after nodes exist */
  var saved = "fa";
  if (langSwitcherEnabled) {
    try { saved = localStorage.getItem("laico-lang") || document.documentElement.lang || "fa"; } catch (e) {}
    if (saved !== "fa" && saved !== "en") saved = "fa";
    apply(saved);
  } else {
    try { localStorage.removeItem("laico-lang"); } catch (e) {}
    document.documentElement.lang = "fa";
    document.documentElement.dir = "rtl";
    document.querySelectorAll(".lang-fa").forEach(function (el) { el.hidden = false; });
    document.querySelectorAll(".lang-en").forEach(function (el) { el.hidden = true; });
  }
})();
