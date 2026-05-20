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
            <p><a href="admin/index.php" style="color: inherit; text-decoration: none; outline: none; -webkit-tap-highlight-color: transparent; position: relative; z-index: 999; display: inline-block; padding: 20px; margin: -20px;">&copy;</a> Pablo Salinas Marín</p>
            <p>www.moratalla-murcia.com</p>
            <p>1998 - 2026</p>
            <div style="margin-top: 2rem; max-width: 800px; font-size: 0.75rem; line-height: 1.6; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
                <p><strong>Protección de Datos:</strong> En cumplimiento de la normativa vigente en materia de protección de datos personales, le informamos que moratalla-murcia.com trata la información facilitada con el fin de gestionar la difusión cultural e histórica del proyecto. Puede ejercer sus derechos de acceso, rectificación, limitación y supresión de datos dirigiéndose al correo electrónico de contacto: <a href="mailto:pablosalinas@moratalla-murcia.com" style="color: white; text-decoration: underline;">pablosalinas@moratalla-murcia.com</a>.</p>
            </div>
        </div>
    </footer>

    <!-- Banner de Cookies -->
    <div id="cookie-banner" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #ffffff; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); z-index: 999999; padding: 1.5rem 0; border-top: 3px solid var(--primary);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; gap: 2rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h4 style="margin-bottom: 0.5rem; color: var(--primary); font-weight: 700;">Aviso de Cookies</h4>
                <p style="font-size: 0.9rem; color: var(--text); line-height: 1.4;">Utilizamos cookies para mejorar su experiencia en moratalla-murcia.com. Al continuar navegando, acepta nuestra política de cookies y el tratamiento de datos personales.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="accept-cookies" class="btn-nav btn-nav-home" style="padding: 0.8rem 1.8rem; border-radius: 12px; cursor: pointer; font-size: 0.9rem;">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        // Lógica del Banner de Cookies
        const cookieBanner = document.getElementById('cookie-banner');
        const acceptBtn = document.getElementById('accept-cookies');

        if (!localStorage.getItem('cookies-accepted')) {
            setTimeout(() => {
                cookieBanner.style.display = 'block';
                cookieBanner.style.animation = 'slideUp 0.5s ease-out';
            }, 1000);
        }

        acceptBtn.addEventListener('click', () => {
            localStorage.setItem('cookies-accepted', 'true');
            cookieBanner.style.opacity = '0';
            setTimeout(() => {
                cookieBanner.style.display = 'none';
            }, 500);
        });

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

        // Banner Lightbox
        function openBannerModal(src) {
            document.getElementById('bannerModalImg').src = src;
            document.getElementById('bannerModal').style.display = 'flex';
        }

        // Animación interactiva y aleatoria de logos
        const logos = document.querySelectorAll('img.main-site-logo, footer img[alt="Logo"]');
        const logoSources = ['uploads/theme/logo.jpg', 'uploads/theme/logo2.jpg'];
        let currentLogoIndex = Math.floor(Math.random() * 2); // Aleatorio al cargar

        logos.forEach(logo => {
            logo.style.transition = "opacity 0.4s ease-in-out";
            logo.src = logoSources[currentLogoIndex];
            
            // Fallback: si logo2.jpg aún no existe, usar el original
            logo.onerror = function() {
                if (this.src.includes('logo2.jpg')) {
                    this.src = 'uploads/theme/logo.jpg';
                }
            };

            // Interacción: Cambiar de logo al pasar el ratón
            logo.addEventListener('mouseenter', () => {
                logo.style.opacity = 0;
                setTimeout(() => {
                    let nextSrc = logo.src.includes('logo2.jpg') ? 'uploads/theme/logo.jpg' : 'uploads/theme/logo2.jpg';
                    logo.src = nextSrc;
                    logo.style.opacity = 1;
                }, 400);
            });
        });

        // Alternancia automática cada 10 segundos
        setInterval(() => {
            currentLogoIndex = (currentLogoIndex + 1) % 2;
            logos.forEach(logo => {
                logo.style.opacity = 0;
                setTimeout(() => {
                    logo.src = logoSources[currentLogoIndex];
                    logo.style.opacity = 1;
                }, 400);
            });
        }, 10000);
    </script>

    <!-- Modal para Detalles de Noticias/Eventos -->
    <div id="newsDetailModal" class="news-modal" onclick="closeNewsModal(event)">
        <div class="news-modal-content">
            <button class="news-modal-close" onclick="closeNewsModalDirect()"><i class="fas fa-times"></i></button>
            <div id="modalImageContainer">
                <img id="modalImage" class="news-modal-img" src="" alt="">
            </div>
            <div class="news-modal-body">
                <div id="modalDate" class="news-modal-date"></div>
                <h2 id="modalTitle" class="news-modal-title"></h2>
                <div id="modalText" class="news-modal-text"></div>
                
                <!-- Galería de imágenes adicionales -->
                <div id="modalGalleryContainer" style="display: none;"></div>
            </div>
        </div>
    </div>

    <!-- Lightbox Carousel para Noticias -->
    <div id="newsCarouselOverlay" class="news-carousel-overlay" onclick="closeNewsCarousel(event)">
        <button class="news-carousel-close" onclick="closeNewsCarouselDirect()"><i class="fas fa-times"></i></button>
        <button class="news-carousel-btn prev" onclick="prevNewsCarousel()"><i class="fas fa-chevron-left"></i></button>
        <button class="news-carousel-btn next" onclick="nextNewsCarousel()"><i class="fas fa-chevron-right"></i></button>
        <div class="news-carousel-container">
            <img id="newsCarouselImg" class="news-carousel-img" src="" alt="">
            <div id="newsCarouselCounter" class="news-carousel-counter"></div>
        </div>
    </div>

    <script>
    let currentCarouselImages = [];
    let currentCarouselIndex = 0;

    function openNewsModal(data) {
        const modal = document.getElementById('newsDetailModal');
        const modalImageContainer = document.getElementById('modalImageContainer');
        const modalImage = document.getElementById('modalImage');
        const modalDate = document.getElementById('modalDate');
        const modalTitle = document.getElementById('modalTitle');
        const modalText = document.getElementById('modalText');
        const galleryContainer = document.getElementById('modalGalleryContainer');
        
        if (data.image) {
            modalImage.src = data.image;
            modalImage.alt = data.title;
            modalImageContainer.style.display = 'block';
        } else {
            modalImageContainer.style.display = 'none';
        }
        
        modalDate.innerHTML = (data.isEvent ? '<i class="fas fa-calendar-alt" style="color:var(--accent);"></i> Evento: ' : '<i class="fas fa-newspaper" style="color:var(--primary);"></i> Noticia: ') + data.date;
        modalTitle.textContent = data.title;
        modalText.innerHTML = data.content;
        
        // Cargar galería
        galleryContainer.innerHTML = '';
        currentCarouselImages = [];
        
        if (data.image) {
            currentCarouselImages.push(data.image);
        }
        
        if (data.gallery && data.gallery.length > 0) {
            data.gallery.forEach(img => {
                currentCarouselImages.push(img);
            });
        }
        
        if (currentCarouselImages.length > 0) {
            let galleryHtml = '<h4 style="font-size:1.15rem; color:var(--primary); margin-top:2.5rem; margin-bottom:1rem; border-left:4px solid var(--accent); padding-left:0.6rem; font-weight:700;"><i class="fas fa-images"></i> Galería de Imágenes</h4>';
            
            // Botón opcional para iniciar carrusel
            galleryHtml += '<button class="btn-news-carousel" style="margin-bottom: 1.5rem;" onclick="openNewsCarousel(0)"><i class="fas fa-play"></i> Ver en Carrusel</button>';
            
            galleryHtml += '<div class="news-gallery-grid">';
            currentCarouselImages.forEach((img, idx) => {
                // Mostrar las imágenes de la galería
                galleryHtml += '<img class="news-gallery-thumb" src="' + img + '" onclick="openNewsCarousel(' + idx + ')" alt="Imagen de galería">';
            });
            galleryHtml += '</div>';
            
            galleryContainer.innerHTML = galleryHtml;
            galleryContainer.style.display = 'block';
        } else {
            galleryContainer.style.display = 'none';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeNewsModal(event) {
        const modal = document.getElementById('newsDetailModal');
        if (event.target === modal) {
            closeNewsModalDirect();
        }
    }

    function closeNewsModalDirect() {
        const modal = document.getElementById('newsDetailModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Funciones del Lightbox Carousel
    function openNewsCarousel(index) {
        if (currentCarouselImages.length === 0) return;
        currentCarouselIndex = index;
        updateCarouselState();
        document.getElementById('newsCarouselOverlay').classList.add('active');
    }

    function updateCarouselState() {
        const imgElement = document.getElementById('newsCarouselImg');
        const counterElement = document.getElementById('newsCarouselCounter');
        
        imgElement.src = currentCarouselImages[currentCarouselIndex];
        counterElement.textContent = (currentCarouselIndex + 1) + ' / ' + currentCarouselImages.length;
    }

    function prevNewsCarousel() {
        if (currentCarouselImages.length === 0) return;
        currentCarouselIndex = (currentCarouselIndex - 1 + currentCarouselImages.length) % currentCarouselImages.length;
        updateCarouselState();
    }

    function nextNewsCarousel() {
        if (currentCarouselImages.length === 0) return;
        currentCarouselIndex = (currentCarouselIndex + 1) % currentCarouselImages.length;
        updateCarouselState();
    }

    function closeNewsCarousel(event) {
        const overlay = document.getElementById('newsCarouselOverlay');
        if (event.target === overlay) {
            closeNewsCarouselDirect();
        }
    }

    function closeNewsCarouselDirect() {
        document.getElementById('newsCarouselOverlay').classList.remove('active');
    }
    </script>

    <!-- Modal para maximizar banners -->
    <div id="bannerModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100vw; height:100vh; background-color:rgba(0,0,0,0.9); align-items:center; justify-content:center; cursor:zoom-out;" onclick="this.style.display='none'">
        <span style="position:absolute; top:20px; right:40px; color:white; font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
        <img id="bannerModalImg" src="" style="max-width:95vw; max-height:90vh; object-fit:contain; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
    </div>

    <!-- PROTECCION ANTI-COPIA DE IMAGENES -->
    <script>
    document.addEventListener('contextmenu', function(e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
