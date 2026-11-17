/* ParcScope - comportements côté navigateur (V0). Aucune dépendance. */
(function () {
  'use strict';

  // --- Fermeture des messages flash ---
  document.querySelectorAll('.flash-x').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.flash').remove();
    });
  });
  document.querySelectorAll('.flash.success').forEach(function (el) {
    setTimeout(function () { if (el.isConnected) { el.remove(); } }, 6000);
  });

  // --- Confirmation avant action destructive : <form data-confirm="..."> ---
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        ev.preventDefault();
      }
    });
  });

  // --- Onglets de la fiche machine (mémorisés dans l'ancre #...) ---
  var tabs = document.querySelectorAll('[data-tab]');
  var panels = document.querySelectorAll('[data-panel]');
  if (tabs.length) {
    var show = function (name) {
      var known = false;
      tabs.forEach(function (t) { if (t.dataset.tab === name) { known = true; } });
      if (!known) { name = tabs[0].dataset.tab; }
      tabs.forEach(function (t) {
        var on = t.dataset.tab === name;
        t.classList.toggle('on', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    };
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        show(t.dataset.tab);
        history.replaceState(null, '', '#' + t.dataset.tab);
      });
    });
    show(location.hash.replace('#', ''));
    window.addEventListener('hashchange', function () { show(location.hash.replace('#', '')); });
  }
})();
