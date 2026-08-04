var header = document.getElementById('siteHeader');
window.addEventListener('scroll', function(){
  header.classList.toggle('scrolled', window.scrollY > 12);
});

var burger = document.getElementById('burgerBtn');
var mobileMenu = document.getElementById('mobileMenu');
burger.addEventListener('click', function(){
  mobileMenu.classList.toggle('open');
  burger.innerHTML = mobileMenu.classList.contains('open') ? '<i class="ti ti-x"></i>' : '<i class="ti ti-menu-2"></i>';
});
document.querySelectorAll('#mobileMenu a').forEach(function(a){
  a.addEventListener('click', function(){
    mobileMenu.classList.remove('open');
    burger.innerHTML = '<i class="ti ti-menu-2"></i>';
  });
});

var io = new IntersectionObserver(function(entries){
  entries.forEach(function(e){
    if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); }
  });
}, { threshold: 0.15 });
document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });

// ── Formulario de contacto ──
var contactForm = document.getElementById('contactForm');
if(contactForm){
  var submitBtn = document.getElementById('contactSubmitBtn');
  var msgBox = document.getElementById('contactFormMsg');

  contactForm.addEventListener('submit', function(e){
    e.preventDefault();

    var formData = new FormData(contactForm);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando…';
    msgBox.className = 'form-msg';

    fetch('contact-handler.php', {
      method: 'POST',
      body: formData
    })
    .then(function(res){ return res.json(); })
    .then(function(data){
      if(data.success){
        msgBox.textContent = '✓ Mensaje enviado. Te contactaremos pronto.';
        msgBox.className = 'form-msg show ok';
        contactForm.reset();
      } else {
        msgBox.textContent = data.error || 'No se pudo enviar el mensaje. Intenta de nuevo o escríbenos por WhatsApp.';
        msgBox.className = 'form-msg show error';
      }
    })
    .catch(function(){
      msgBox.textContent = 'Error de conexión. Intenta de nuevo o escríbenos por WhatsApp.';
      msgBox.className = 'form-msg show error';
    })
    .finally(function(){
      submitBtn.disabled = false;
      submitBtn.textContent = 'Enviar mensaje';
    });
  });
}