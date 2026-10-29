/**
 * Main JavaScript for KIPA Theme
 */
document.addEventListener('DOMContentLoaded', function () {
  // Sticky header shadow
  var header = document.querySelector('.kipa-header');
  window.addEventListener('scroll', function () {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // Mobile Menu Toggle
  var mobileToggle = document.querySelector('.mobile-toggle');
  var navMenu = document.querySelector('.kipa-nav');

  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', function () {
      var isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
      mobileToggle.setAttribute('aria-expanded', !isExpanded);
      if (navMenu.style.display === 'flex') {
        navMenu.style.display = 'none';
      } else {
        navMenu.style.display = 'flex';
        navMenu.style.flexDirection = 'column';
        navMenu.style.position = 'absolute';
        navMenu.style.top = '100%';
        navMenu.style.left = '0';
        navMenu.style.right = '0';
        navMenu.style.background = '#ffffff';
        navMenu.style.padding = '20px';
        navMenu.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
      }
    });
  }
});
