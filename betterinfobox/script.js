/**
 * Better Infobox Plugin – Client-side behaviour
 *
 * Handles:
 *  - Tabbed image panels
 *  - Collapsible section toggle
 *  - Spoiler reveal on click
 */

document.addEventListener('DOMContentLoaded', function () {
  // -- Tabbed images -------------------------------------------------------
  document.querySelectorAll('.bib-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.closest('.bib-tabs');
      if (!group) return;

      var idx = btn.getAttribute('data-bib-tab');

      // Deactivate all buttons and hide all panels in this group
      group.querySelectorAll('.bib-tab-btn').forEach(function (b) {
        b.classList.remove('bib-tab-active');
      });
      group.querySelectorAll('.bib-tab-panel').forEach(function (p) {
        p.style.display = 'none';
      });

      // Activate clicked button and show its panel
      btn.classList.add('bib-tab-active');
      var panel = group.querySelector('.bib-tab-panel[data-bib-tab-panel="' + idx + '"]');
      if (panel) panel.style.display = '';
    });
  });

  // -- Collapsible sections ------------------------------------------------
  document.querySelectorAll('.bib-collapse-header').forEach(function (header) {
    header.addEventListener('click', function () {
      var targetId = header.getAttribute('data-bib-target');
      if (!targetId) return;

      var body = document.getElementById(targetId);
      if (!body) return;

      var isCollapsed = body.classList.contains('bib-collapsed');
      if (isCollapsed) {
        body.classList.remove('bib-collapsed');
        body.style.display = '';
        header.classList.add('bib-open');
      } else {
        body.classList.add('bib-collapsed');
        body.style.display = 'none';
        header.classList.remove('bib-open');
      }
    });
  });

  // -- Spoiler reveal on click ---------------------------------------------
  document.querySelectorAll('.bib-spoiler').forEach(function (el) {
    el.addEventListener('click', function () {
      el.classList.toggle('bib-revealed');
    });
  });
});
