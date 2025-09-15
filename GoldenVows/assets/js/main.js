// EverAfter Wedding - Interactions
(function(){
  // AOS init
  if (window.AOS) AOS.init({ once: true, offset: 80, duration: 700, easing: 'ease-out' });

  // Dynamic year
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Mobile nav
  const nav = document.getElementById('nav');
  const ham = document.getElementById('hamburger');
  if (ham && nav){
    ham.addEventListener('click', ()=> nav.classList.toggle('show'));
    nav.querySelectorAll('a').forEach(a=> a.addEventListener('click', ()=> nav.classList.remove('show')));
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', (e)=>{
      const href = a.getAttribute('href');
      if (href && href.length > 1){
        e.preventDefault();
        document.querySelector(href)?.scrollIntoView({ behavior:'smooth', block:'start' });
        history.pushState(null, '', href);
      }
    });
  });

  // Testimonials slider
  const slider = document.querySelector('.slider');
  if (slider){
    const track = slider.querySelector('.slides');
    const slides = [...slider.querySelectorAll('.slide')];
    const prev = slider.querySelector('.prev');
    const next = slider.querySelector('.next');
    const dotsWrap = slider.querySelector('.slider-dots');
    let index = 0; // page index
    let autoplayId = null;

    function getPer(){
      const v = parseInt(getComputedStyle(slider).getPropertyValue('--per'));
      return isNaN(v) || v < 1 ? 1 : v;
    }
    function getPageCount(){
      const per = getPer();
      return Math.max(1, Math.ceil(slides.length / per));
    }
    
    // Dots (per page)
    let dots = [];
    function buildDots(){
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      dots = Array.from({length: getPageCount()}, (_, i) => {
        const b = document.createElement('button');
        b.setAttribute('aria-label', `Halaman ${i+1}`);
        b.setAttribute('role', 'tab');
        b.addEventListener('click', ()=> go(i));
        dotsWrap.appendChild(b);
        return b;
      });
    }
    buildDots();
    // Ensure initial dot reflects page 1
    // (update() will also set it, but do it here in case of delayed layout)
    if (dots[0]) dots[0].setAttribute('aria-selected', 'true');

    function update(){
      const pageWidth = slider.clientWidth; // each page equals viewport width
      const offset = index * pageWidth;
      track.style.transform = `translateX(-${offset}px)`;
      dots.forEach((d,i)=> d.setAttribute('aria-selected', i===index ? 'true' : 'false'));
    }
    function go(n){
      const pages = getPageCount();
      index = (n + pages) % pages;
      update();
    }
    // Initial layout after fonts/images
    window.requestAnimationFrame(update);
    function handleResize(){
      const before = dots.length;
      const after = getPageCount();
      if (before !== after){ buildDots(); index = Math.min(index, after-1); }
      update();
    }
    window.addEventListener('resize', handleResize);
    prev?.addEventListener('click', ()=> go(index-1));
    next?.addEventListener('click', ()=> go(index+1));

    // auto-play
    function startAutoplay(){
      stopAutoplay();
      autoplayId = setInterval(()=> go(index+1), 5200);
    }
    function stopAutoplay(){ if (autoplayId) clearInterval(autoplayId); autoplayId = null; }
    startAutoplay();

    // Pause on hover
    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);

    // Swipe support
    let startX = 0, deltaX = 0, isSwiping = false;
    const threshold = 40; // px
    track.addEventListener('touchstart', (e)=>{
      startX = e.touches[0].clientX; deltaX = 0; isSwiping = true; stopAutoplay();
    }, {passive:true});
    track.addEventListener('touchmove', (e)=>{
      if (!isSwiping) return; deltaX = e.touches[0].clientX - startX;
    }, {passive:true});
    track.addEventListener('touchend', ()=>{
      if (!isSwiping) return; isSwiping = false;
      if (Math.abs(deltaX) > threshold){ deltaX < 0 ? go(index+1) : go(index-1); }
      startAutoplay();
    });
    update();
  }

  // Lightbox (minimal)
  const galleryLinks = document.querySelectorAll('.lightbox');
  if (galleryLinks.length){
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.88);display:none;align-items:center;justify-content:center;z-index:50;padding:24px;';
    overlay.innerHTML = '<img alt="preview" style="max-width:96%;max-height:92%;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5)"/><button aria-label="Tutup" style="position:absolute;top:16px;right:16px;width:44px;height:44px;border-radius:50%;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);color:#fff;font-size:18px;cursor:pointer">✕</button>';
    const img = overlay.querySelector('img');
    const closeBtn = overlay.querySelector('button');
    closeBtn.addEventListener('click', ()=> overlay.style.display='none');
    overlay.addEventListener('click', (e)=>{ if(e.target===overlay) overlay.style.display='none'; });
    document.body.appendChild(overlay);
    galleryLinks.forEach(a=> a.addEventListener('click', (e)=>{
      e.preventDefault();
      img.src = a.getAttribute('href');
      overlay.style.display='flex';
    }));
  }

  // Back to top
  const btt = document.getElementById('backToTop');
  if (btt){
    window.addEventListener('scroll', ()=>{
      const show = window.scrollY > 600;
      btt.classList.toggle('show', show);
    });
    btt.addEventListener('click', ()=> window.scrollTo({ top:0, behavior:'smooth' }));
  }

  // Floating petals
  const petalsWrap = document.getElementById('petals');
  if (petalsWrap){
    const COUNT = 24;
    for (let i=0;i<COUNT;i++){
      const p = document.createElement('div');
      p.className = 'petal' + (i%3===0 ? ' p2' : i%3===1 ? ' p3' : '');
      p.style.left = (Math.random()*100).toFixed(2) + '%';
      const fall = (8 + Math.random()*9).toFixed(2);
      const sway = (3 + Math.random()*4).toFixed(2);
      const delay = (Math.random()*8).toFixed(2);
      p.style.animation = `fall ${fall}s linear ${delay}s infinite, sway ${sway}s ease-in-out ${delay}s infinite alternate`;
      petalsWrap.appendChild(p);
    }
  }

  // Mock contact form submission
  const submit = document.getElementById('submit-contact');
  const form = document.querySelector('.contact-form');
  submit?.addEventListener('click', ()=>{
    if (!form) return;
    const data = Object.fromEntries(new FormData(form).entries());
    console.log('Contact form data:', data);
    submit.disabled = true; submit.textContent = 'Mengirim...';
    setTimeout(()=>{
      alert('Terima kasih! Kami akan menghubungi Anda segera.');
      submit.disabled = false; submit.textContent = 'Kirim';
      form.reset();
    }, 1000);
  });
})();
