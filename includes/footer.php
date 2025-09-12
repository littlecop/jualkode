    </div>
  </main>
  <footer class="border-t border-slate-200/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-sm text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
      <p>© <?= date('Y') ?> Store Code Market. All rights reserved.</p>
      <p class="opacity-80">Built with TailwindCSS + PHP (PDO).</p>
    </div>
  </footer>
  <script>
    // Mobile menu toggle + 3D tilt binding
    (function(){
      // Mobile menu toggle
      const btn = document.getElementById('mobileMenuBtn');
      const panel = document.getElementById('mobileMenu');
      if (btn && panel) {
        const closeMenu = () => { panel.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); };
        const openMenu = () => { panel.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); };
        btn.addEventListener('click', () => {
          const expanded = btn.getAttribute('aria-expanded') === 'true';
          expanded ? closeMenu() : openMenu();
        });
        // Close on link click
        panel.querySelectorAll('a').forEach(a=> a.addEventListener('click', closeMenu));
        // Close on outside click
        document.addEventListener('click', (e)=>{
          if (!panel.contains(e.target) && !btn.contains(e.target)) closeMenu();
        });
      }

      if (typeof window.bindTilt !== 'function') {
        window.bindTilt = function(){
          const cards = document.querySelectorAll('.card-tilt');
          cards.forEach(card => {
            let rect;
            card.addEventListener('mouseenter', ()=>{ rect = card.getBoundingClientRect(); });
            card.addEventListener('mousemove', (e)=>{
              if (!rect) rect = card.getBoundingClientRect();
              const cx = rect.left + rect.width/2;
              const cy = rect.top + rect.height/2;
              const dx = (e.clientX - cx) / (rect.width/2);
              const dy = (e.clientY - cy) / (rect.height/2);
              card.style.transform = `perspective(800px) rotateX(${(-dy*4).toFixed(2)}deg) rotateY(${(dx*6).toFixed(2)}deg) translateY(-4px)`;
            });
            card.addEventListener('mouseleave', ()=>{ card.style.transform = ''; });
          });
        }
      }
      try { window.bindTilt(); } catch(_){ }
    })();
  </script>
</body>
</html>
