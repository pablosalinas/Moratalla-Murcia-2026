    <footer style="background: var(--primary); color: white; padding: 4rem 0; margin-top: 5rem;">
        <!-- Fila de Escudos y Logo Centrada -->
        <div class="container" style="display: flex; flex-direction: column; align-items: center; gap: 3rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 3rem; margin-bottom: 2rem;">
            
            <div class="footer-shields" style="display: flex; gap: 1.5rem; align-items: flex-start; justify-content: center; flex-wrap: wrap;">
                <!-- EU -->
                <div style="text-align: center; width: 80px; cursor: pointer;" 
                     onmouseenter="document.getElementById('audio_eu').play()" 
                     onmouseleave="document.getElementById('audio_eu').pause(); document.getElementById('audio_eu').currentTime = 0;">
                    <audio id="audio_eu" src="assets/audio/himno_eu.mp3" preload="auto"></audio>
                    <div style="background: white; padding: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; height: 80px; margin-bottom: 0.8rem;">
                        <img src="uploads/theme/escudo_eu.png" alt="UE" style="max-height: 60px; width: auto;">
                    </div>
                    <small style="display: block; opacity: 0.7; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">U. Europea</small>
                </div>
                <!-- España -->
                <div style="text-align: center; width: 80px; cursor: pointer;" 
                     onmouseenter="document.getElementById('audio_espana').play()" 
                     onmouseleave="document.getElementById('audio_espana').pause(); document.getElementById('audio_espana').currentTime = 0;">
                    <audio id="audio_espana" src="assets/audio/himno_espana.mp3" preload="auto"></audio>
                    <div style="background: white; padding: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; height: 80px; margin-bottom: 0.8rem;">
                        <img src="uploads/theme/escudo_espana_real.jpg" alt="España" style="max-height: 60px; width: auto; clip-path: inset(0 4% 0 4%);">
                    </div>
                    <small style="display: block; opacity: 0.7; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">España</small>
                </div>
                <!-- Región de Murcia -->
                <div style="text-align: center; width: 80px; cursor: pointer;" 
                     onmouseenter="document.getElementById('audio_murcia').play()" 
                     onmouseleave="document.getElementById('audio_murcia').pause(); document.getElementById('audio_murcia').currentTime = 0;">
                    <audio id="audio_murcia" src="assets/audio/himno_murcia.mp3" preload="auto"></audio>
                    <div style="background: white; padding: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; height: 80px; margin-bottom: 0.8rem;">
                        <img src="uploads/theme/escudo_murcia.png" alt="Región de Murcia" style="max-height: 60px; width: auto;">
                    </div>
                    <small style="display: block; opacity: 0.7; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">R. de Murcia</small>
                </div>
                <!-- Moratalla -->
                <div style="text-align: center; width: 80px; cursor: pointer;" 
                     onmouseenter="document.getElementById('audio_moratalla').play()" 
                     onmouseleave="document.getElementById('audio_moratalla').pause(); document.getElementById('audio_moratalla').currentTime = 0;">
                    <audio id="audio_moratalla" src="assets/audio/himno_moratalla.mp3" preload="auto"></audio>
                    <div style="background: white; padding: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; height: 80px; margin-bottom: 0.8rem;">
                        <img src="uploads/theme/escudo_moratalla_real.gif" alt="Moratalla" style="max-height: 60px; width: auto;">
                    </div>
                    <small style="display: block; opacity: 0.7; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">Moratalla</small>
                </div>
                <!-- Logo de la web -->
                <div style="text-align: center; width: 140px; cursor: pointer;" 
                     onmouseenter="document.getElementById('audio_web').play()" 
                     onmouseleave="document.getElementById('audio_web').pause(); document.getElementById('audio_web').currentTime = 0;">
                    <audio id="audio_web" src="assets/audio/a_moratalla.mp3" preload="auto"></audio>
                    <div style="background: white; padding: 5px; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; height: 80px; margin-bottom: 0.8rem;">
                        <img src="uploads/theme/logo.jpg" alt="Logo" style="max-height: 70px; width: auto;">
                    </div>
                    <small style="display: block; opacity: 0.7; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">moratalla-murcia.com</small>
                </div>
            </div>

            <!-- Redes Sociales debajo de los escudos -->
            <div style="display: flex; gap: 2.5rem; justify-content: center; font-size: 1.5rem;">
                <a href="#" style="color: white; opacity: 0.8;"><i class="fab fa-facebook"></i></a>
                <a href="#" style="color: white; opacity: 0.8;"><i class="fab fa-instagram"></i></a>
                <a href="#" style="color: white; opacity: 0.8;"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <!-- Copyright final -->
        <div class="container" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; font-size: 0.85rem; opacity: 0.7; text-align: center;">
            <p>&copy; Pablo Salinas Marín</p>
            <p>www.moratalla-murcia.com</p>
            <p>1998 - 2026</p>
        </div>
    </footer>

    <script>
        // Mobile Toggle Menu
        const toggleBtn = document.getElementById('menu-toggle');
        const navContainer = document.querySelector('.nav-container');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                navContainer.classList.toggle('active');
            });
        }

        // Swiper Banner Initialization
        const swiper = new Swiper('.main-banner-swiper', {
            loop: true,
            autoplay: {
                delay: <?php echo (int)$bannerSpeed; ?>,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                // when window width is >= 320px
                320: {
                    slidesPerView: 1.1,
                    spaceBetween: 10,
                    centeredSlides: true
                },
                // when window width is >= 768px
                768: {
                    slidesPerView: 2.2,
                    spaceBetween: 20,
                    centeredSlides: false
                },
                // when window width is >= 1024px
                1024: {
                    slidesPerView: 5,
                    spaceBetween: 15,
                    centeredSlides: true
                }
            }
        });
    </script>
</body>
</html>
