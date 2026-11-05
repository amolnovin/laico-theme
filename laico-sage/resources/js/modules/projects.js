/* LAICO DESIGN — projects.html
   Search + filters + pagination (9 per page). Cards are plain HTML, so the page
   still lists every project when JavaScript is off. */
(function () {
  "use strict";

  var grid = document.querySelector("[data-prj-grid]");
  if (!grid) return;

  var PER_PAGE = parseInt(grid.getAttribute("data-per-page"), 10) || 9;
  var cards = Array.prototype.slice.call(grid.querySelectorAll("[data-prj-card]"));
  var empty = document.querySelector("[data-prj-empty]");
  var pager = document.querySelector("[data-prj-pager]");
  var search = document.querySelector("[data-prj-search]");
  var tabs = Array.prototype.slice.call(document.querySelectorAll("[data-category]"));
  var pills = Array.prototype.slice.call(document.querySelectorAll("[data-space-filter]"));
  var selects = {
    city: document.querySelector("[data-prj-select='city']"),
    year: document.querySelector("[data-prj-select='year']"),
    size: document.querySelector("[data-prj-select='size']")
  };
  var state = { category: "all", space: "all", q: "", page: 1 };

  var dict = {
    fa: { count: "{n} پروژه", prev: "قبلی", next: "بعدی", page: "صفحه" },
    en: { count: "{n} projects", prev: "Previous", next: "Next", page: "Page" }
  };

  function lang() { return document.documentElement.lang === "en" ? "en" : "fa"; }
  function t(k) { return dict[lang()][k]; }
  function digits(n) {
    return lang() === "fa" ? String(n).replace(/\d/g, function (d) { return "۰۱۲۳۴۵۶۷۸۹"[d]; }) : String(n);
  }
  function norm(x) {
    return (x || "").replace(/ي/g, "ی").replace(/ك/g, "ک").replace(/\s+/g, " ").trim().toLowerCase();
  }

  function translateControls() {
    var l = lang();
    document.querySelectorAll("option[data-fa]").forEach(function (o) { o.textContent = o.getAttribute("data-" + l); });
    document.querySelectorAll("[data-ph-fa]").forEach(function (el) { el.setAttribute("placeholder", el.getAttribute("data-ph-" + l)); });
    render("quiet");
  }

  function matches(card) {
    if (state.category !== "all" && card.getAttribute("data-category") !== state.category) return false;
    if (state.space !== "all" && card.getAttribute("data-space") !== state.space) return false;
    var city = selects.city ? selects.city.value : "all";
    var year = selects.year ? selects.year.value : "all";
    var size = selects.size ? selects.size.value : "all";
    if (city !== "all" && card.getAttribute("data-city") !== city) return false;
    if (year !== "all" && card.getAttribute("data-year") !== year) return false;
    if (size !== "all" && card.getAttribute("data-size") !== size) return false;
    var q = norm(state.q);
    if (q && norm(card.getAttribute("data-q")).indexOf(q) === -1) return false;
    return true;
  }

  function buildPager(pages) {
    if (!pager) return;
    pager.innerHTML = "";
    pager.hidden = pages <= 1;
    if (pages <= 1) return;
    function btn(label, page, opts) {
      var b = document.createElement("button");
      b.type = "button";
      b.textContent = label;
      if (opts && opts.cls) b.className = opts.cls;
      if (opts && opts.disabled) b.disabled = true;
      if (opts && opts.current) { b.classList.add("is-active"); b.setAttribute("aria-current", "page"); }
      if (opts && opts.aria) b.setAttribute("aria-label", opts.aria);
      b.addEventListener("click", function () { go(page); });
      pager.appendChild(b);
    }
    btn(t("prev"), state.page - 1, { cls: "pager-nav", disabled: state.page === 1 });
    for (var i = 1; i <= pages; i++) {
      btn(digits(i), i, { current: i === state.page, aria: t("page") + " " + digits(i) });
    }
    btn(t("next"), state.page + 1, { cls: "pager-nav", disabled: state.page === pages });
  }

  var firstRender = true;
  function shuffle(list) {
    if (!document.documentElement.classList.contains("mo-on")) return;
    list.forEach(function (c, i) {
      c.classList.remove("mo-m", "mo-in", "is-shuffle");
      c.style.setProperty("--s", i % 9);
    });
    void grid.offsetWidth;                       // restart the animation
    list.forEach(function (c) { c.classList.add("is-shuffle"); });
  }

  function render(resetPage) {
    if (resetPage === true) state.page = 1;
    var list = cards.filter(matches);
    var pages = Math.max(1, Math.ceil(list.length / PER_PAGE));
    if (state.page > pages) state.page = pages;
    var from = (state.page - 1) * PER_PAGE;
    var visible = list.slice(from, from + PER_PAGE);
    cards.forEach(function (c) { c.hidden = visible.indexOf(c) === -1; });
    if (!firstRender && resetPage !== "quiet") shuffle(visible);
    firstRender = false;
    grid.hidden = list.length === 0;
    if (empty) empty.hidden = list.length !== 0;
    document.querySelectorAll("[data-prj-count]").forEach(function (el) {
      el.textContent = t("count").replace("{n}", digits(list.length));
    });
    buildPager(pages);
  }

  function go(page) {
    state.page = page;
    render(false);
    var anchor = document.querySelector(".prj-results");
    if (anchor) anchor.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  if (search) {
    search.addEventListener("input", function () { state.q = search.value; render(true); });
    search.addEventListener("keydown", function (e) { if (e.key === "Enter") e.preventDefault(); });
  }
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      state.category = tab.getAttribute("data-category");
      tabs.forEach(function (b) { b.classList.toggle("is-active", b === tab); });
      render(true);
    });
  });
  pills.forEach(function (pill) {
    pill.addEventListener("click", function () {
      state.space = pill.getAttribute("data-space-filter");
      pills.forEach(function (b) { b.classList.toggle("is-active", b === pill); });
      render(true);
    });
  });
  Object.keys(selects).forEach(function (k) {
    if (selects[k]) selects[k].addEventListener("change", function () { render(true); });
  });
  document.querySelectorAll("[data-lang]").forEach(function (btn) {
    btn.addEventListener("click", function () { setTimeout(translateControls, 0); });
  });

  translateControls();
})();
