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

            <!-- Aviso discreto hover escudos -->
            <p style="font-size: 0.85rem; font-weight: 600; opacity: 0.75; letter-spacing: 0.5px; margin: -1rem 0 0; text-align: center;">
                <i class="fas fa-mouse-pointer" style="font-size: 0.8rem;"></i>&nbsp; Pasa el cursor sobre los escudos&hellip;
            </p>

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
            <p>1998 - <?php echo date('Y'); ?></p>
            <div style="margin-top: 2rem; max-width: 800px; font-size: 0.75rem; line-height: 1.6; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
                <?php $footerAdminEmail = isset($settings['admin_email']) && !empty($settings['admin_email']) ? $settings['admin_email'] : 'pablosalinas@moratalla-murcia.com'; ?>
                <p><strong>Protección de Datos:</strong> En cumplimiento de la normativa vigente en materia de protección de datos personales, le informamos que moratalla-murcia.com trata la información facilitada con el fin de gestionar la difusión cultural e histórica del proyecto. Puede ejercer sus derechos de acceso, rectificación, limitación y supresión de datos dirigiéndose al correo electrónico de contacto: <a href="mailto:<?php echo htmlspecialchars($footerAdminEmail); ?>" style="color: white; text-decoration: underline;"><?php echo htmlspecialchars($footerAdminEmail); ?></a>.</p>
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
            },
            on: {
                init: function () {
                    handleSwiperVideo(this);
                },
                slideChangeTransitionStart: function () {
                    handleSwiperVideo(this);
                }
            }
        });

        function handleSwiperVideo(swiperInstance) {
            // Pausar todos los vídeos
            const allVideos = swiperInstance.el.querySelectorAll('.banner-video-el');
            allVideos.forEach(v => {
                v.pause();
                v.currentTime = 0;
            });

            // Reproducir vídeo activo y pausar swiper
            const activeSlide = swiperInstance.slides[swiperInstance.activeIndex];
            if (!activeSlide) return;
            const video = activeSlide.querySelector('.banner-video-el');
            if (video) {
                swiperInstance.autoplay.stop();
                video.play().catch(e => console.log('Autoplay blocked:', e));
                video.onended = function() {
                    swiperInstance.slideNext();
                    swiperInstance.autoplay.start();
                };
            } else {
                swiperInstance.autoplay.start();
            }
        }

        // Banner Lightbox
        function openBannerModal(src, isVideo = false) {
            const modal = document.getElementById('bannerModal');
            const img = document.getElementById('bannerModalImg');
            const vid = document.getElementById('bannerModalVid');
            
            if (!isVideo && src) {
                const ext = src.split('?')[0].split('.').pop().toLowerCase();
                isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
            }
            
            if (isVideo) {
                if (img) img.style.display = 'none';
                if (vid) {
                    vid.src = src;
                    vid.style.display = 'block';
                    vid.load();
                    vid.play().catch(err => console.log('Autoplay blocked:', err));
                }
            } else {
                if (vid) {
                    vid.pause();
                    vid.style.display = 'none';
                }
                if (img) {
                    img.src = src;
                    img.style.display = 'block';
                }
            }
            modal.style.display = 'flex';
            if (typeof handleModalOpen === 'function') handleModalOpen();
        }

        function closeBannerModal(fromPopState = false) {
            const modal = document.getElementById('bannerModal');
            const vid = document.getElementById('bannerModalVid');
            if (vid) {
                vid.pause();
                vid.src = '';
            }
            modal.style.display = 'none';
            if (typeof handleModalClose === 'function') handleModalClose(fromPopState === true);
        }

        // Animación interactiva y aleatoria de logos
        const logos = document.querySelectorAll('img.main-site-logo, footer img[alt="Logo"]');
        const logoSources = ['uploads/theme/logo.jpg', 'uploads/theme/logo2.jpg'];
        let currentLogoIndex = 0; // Se empieza con el logo por defecto que está en HTML para evitar parpadeos al cargar

        logos.forEach(logo => {
            logo.style.transition = "opacity 0.4s ease-in-out";
            
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
            <div id="modalImageContainer"></div>
            <div id="modalImageCaption" style="display:none; text-align:center; padding: 0.8rem; background: var(--gray-100); color: var(--text-light); font-size: 0.9rem; border-bottom: 1px solid var(--gray-200); font-style: italic;"></div>
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
            <img id="newsCarouselImg" class="news-carousel-img" src="" alt="" style="transition: opacity 0.3s ease;">
            <div id="newsCarouselCaption" style="color: white; margin-top: 1.5rem; font-size: 1.1rem; text-align: center; max-width: 800px; text-shadow: 0 2px 4px rgba(0,0,0,0.8); min-height: 1.5rem;"></div>
            <div id="newsCarouselCounter" class="news-carousel-counter" style="position: absolute; top: -30px; right: 0;"></div>
            <!-- Indicador de Tiempo Visual -->
            <div style="position: absolute; bottom: -3.5rem; display: flex; gap: 0.5rem; color: white; opacity: 0.6; font-size: 0.9rem; left: 50%; transform: translateX(-50%); white-space: nowrap;">
                <i class="fas fa-play" style="font-size: 0.7rem; margin-top: 3px;"></i> Reproducción Automática (4s)
            </div>
        </div>
    </div>

    <script>
    // --- LÓGICA DE CONTROL DE HISTORIAL (BACK BUTTON) PARA MODALES ---
    let isModalOpen = false;
    
    // Limpiar estado sucio al recargar la página
    if (history.state && history.state.modalOpen) {
        history.replaceState(null, '', location.href);
    }

    function handleModalOpen() {
        if (!isModalOpen) {
            history.pushState({ modalOpen: true }, '', location.href);
            isModalOpen = true;
        }
    }

    function handleModalClose(fromPopState) {
        const c1 = document.getElementById('newsCarouselOverlay');
        const c2 = document.getElementById('newsDetailModal');
        const c3 = document.getElementById('bannerModal');
        
        const anyOpen = (c1 && c1.classList.contains('active')) ||
                        (c2 && c2.classList.contains('active')) ||
                        (c3 && c3.style.display === 'flex');
                        
        if (!anyOpen && isModalOpen && !fromPopState) {
            isModalOpen = false;
            history.back();
        }
    }

    window.addEventListener('popstate', function(event) {
        if (isModalOpen) {
            isModalOpen = false; // Evitar que las funciones de cierre llamen a history.back()
            
            // Cerrar todos los modales visualmente
            if (typeof closeNewsCarouselDirect === 'function') closeNewsCarouselDirect(true);
            if (typeof closeNewsModalDirect === 'function') closeNewsModalDirect(true);
            if (typeof closeBannerModal === 'function') closeBannerModal(true);
        }
    });
    // -----------------------------------------------------------------

    let currentCarouselImages = [];
    let currentCarouselIndex = 0;
    let newsCarouselInterval;

    function openNewsModal(data) {
        const modal = document.getElementById('newsDetailModal');
        const modalImageContainer = document.getElementById('modalImageContainer');
        const modalImageCaption = document.getElementById('modalImageCaption');
        const modalDate = document.getElementById('modalDate');
        const modalTitle = document.getElementById('modalTitle');
        const modalText = document.getElementById('modalText');
        const galleryContainer = document.getElementById('modalGalleryContainer');
        
        if (data.image) {
            const ext = data.image.split('?')[0].split('.').pop().toLowerCase();
            const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
            
            if (data.image.toLowerCase().endsWith('.pdf')) {
                modalImageContainer.innerHTML = '<iframe src="' + data.image + '" style="width:100%; height:60vh; border:none; border-radius: 8px 8px 0 0;"></iframe>';
            } else if (isVideo) {
                modalImageContainer.innerHTML = '<div style="position:relative; width:100%;"><video id="modalNewsVideo" class="news-modal-img" src="' + data.image + '" style="width:100%; max-height:60vh; object-fit:contain; background:#000; display:block;" controls autoplay loop muted></video>' +
                    '<div class="video-mute-btn" style="position:absolute; bottom:15px; right:15px; z-index:10; background:rgba(0,0,0,0.6); border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:white;" onclick="event.stopPropagation(); toggleVideoMute(this, document.getElementById(\'modalNewsVideo\'))">' +
                    '<i class="fas fa-volume-mute"></i>' +
                    '</div></div>';
            } else {
                modalImageContainer.innerHTML = '<img id="modalImage" class="news-modal-img" src="' + data.image + '" alt="' + data.title + '">';
            }
            modalImageContainer.style.display = 'block';
            
            if (data.image_caption && data.image_caption.trim() !== '') {
                modalImageCaption.textContent = data.image_caption;
                modalImageCaption.style.display = 'block';
            } else {
                modalImageCaption.style.display = 'none';
            }
        } else {
            modalImageContainer.style.display = 'none';
            modalImageCaption.style.display = 'none';
        }
        
        modalDate.innerHTML = (data.isEvent ? '<i class="fas fa-calendar-alt" style="color:var(--accent);"></i> Evento: ' : '<i class="fas fa-newspaper" style="color:var(--primary);"></i> Noticia: ') + data.date;
        modalTitle.textContent = data.title;
        
        // Remove old top carousel button if it exists
        const oldTopBtn = document.getElementById('modalTopCarouselBtn');
        if (oldTopBtn) oldTopBtn.remove();
        
        modalText.innerHTML = data.content;
        
        // Cargar galería
        galleryContainer.innerHTML = '';
        currentCarouselImages = []; // Para carrusel
        
        let hasGalleryFiles = false;
        let seenPathsCarousel = new Set();
        
        if (data.image) {
            const ext = data.image.split('.').pop().toLowerCase();
            const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
            const isPdf = data.image.toLowerCase().endsWith('.pdf');
            currentCarouselImages.push({src: data.image, caption: data.image_caption || '', is_video: isVideo, is_pdf: isPdf});
            seenPathsCarousel.add(data.image);
        }
        
        if (data.gallery && data.gallery.length > 0) {
            hasGalleryFiles = true;
            data.gallery.forEach(imgObj => {
                if (seenPathsCarousel.has(imgObj.image_path)) return;
                const ext = imgObj.image_path.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext) || imgObj.is_video == 1;
                const isPdf = imgObj.image_path.toLowerCase().endsWith('.pdf');
                currentCarouselImages.push({src: imgObj.image_path, caption: imgObj.caption || '', is_video: isVideo, is_pdf: isPdf});
                seenPathsCarousel.add(imgObj.image_path);
            });
        }
        
        if (hasGalleryFiles || currentCarouselImages.length > 1) { // Mostrar si hay más de 1 imagen, o si hay archivos extra
            let galleryHtml = '<h4 style="font-size:1.15rem; color:var(--primary); margin-top:2.5rem; margin-bottom:1rem; border-left:4px solid var(--accent); padding-left:0.6rem; font-weight:700;"><i class="fas fa-images"></i> Galería / Documentos</h4>';
            
            if (currentCarouselImages.length > 0) {
                galleryHtml += '<button class="btn-news-carousel" style="margin-bottom: 1.5rem;" onclick="openNewsCarousel(0)"><i class="fas fa-play"></i> Ver en Carrusel</button>';
                
                // Add a floating button at the bottom center of the screen for easy access at all times
                const topCarouselBtnHtml = '<div id="modalTopCarouselBtn" style="position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); z-index: 999999; display: flex; align-items: center; justify-content: center; pointer-events: none;"><button class="btn-news-carousel" style="padding: 0.8rem 1.8rem; font-size: 1.05rem; font-weight: bold; background: var(--primary); color: white; border: 2px solid rgba(255,255,255,0.9); border-radius: 30px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.4); transition: transform 0.2s, box-shadow 0.2s, background 0.2s; pointer-events: auto;" onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.5)\'; this.style.background=\'var(--primary-dark)\'" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 6px 20px rgba(0,0,0,0.4)\'; this.style.background=\'var(--primary)\'" onclick="openNewsCarousel(0)"><i class="fas fa-images"></i> Ver Carrusel (' + currentCarouselImages.length + ')</button></div>';
                
                // Append it to the modal container
                document.getElementById('newsDetailModal').insertAdjacentHTML('beforeend', topCarouselBtnHtml);
            }
            
            galleryHtml += '<div class="news-gallery-grid">';
            
            // Recorrer todos los archivos para mostrarlos (imágenes + PDFs + vídeos)
            let allFiles = [];
            let seenPathsThumbs = new Set();
            if (data.image) {
                allFiles.push({path: data.image, caption: data.image_caption || ''});
                seenPathsThumbs.add(data.image);
            }
            if (data.gallery) {
                data.gallery.forEach(g => {
                    if (!seenPathsThumbs.has(g.image_path)) {
                        allFiles.push({path: g.image_path, caption: g.caption || ''});
                        seenPathsThumbs.add(g.image_path);
                    }
                });
            }
            
            allFiles.forEach(file => {
                const ext = file.path.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
                let cIndex = currentCarouselImages.findIndex(img => img.src === file.path);
                if (file.path.toLowerCase().endsWith('.pdf')) {
                    galleryHtml += '<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 1rem; background:#fee2e2; color:#b91c1c; border-radius:8px; border: 1px solid #f87171; cursor:pointer;" onclick="openNewsCarousel(' + cIndex + ')"><i class="fas fa-file-pdf fa-2x"></i><span style="font-size:0.75rem; margin-top:0.5rem; text-align:center;">' + (file.caption ? file.caption : 'Ver PDF') + '</span></div>';
                } else if (isVideo) {
                    galleryHtml += '<div style="display:flex; flex-direction:column; gap:5px; position:relative;">' +
                        '<video class="news-gallery-thumb" src="' + file.path + '" style="background:#000; width:100%; height:80px; object-fit:cover; border-radius:8px; cursor:pointer;" onclick="openNewsCarousel(' + cIndex + ')" preload="metadata"></video>' +
                        '<div style="position:absolute; top:30px; left:50%; transform:translateX(-50%); color:white; font-size:1.5rem; pointer-events:none; opacity:0.8; text-shadow:0 1px 4px rgba(0,0,0,0.6);"><i class="fas fa-play-circle"></i></div>' +
                        (file.caption ? '<span style="font-size:0.75rem; color:var(--text-light); text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + file.caption + '">' + file.caption + '</span>' : '') +
                        '</div>';
                } else {
                    galleryHtml += '<div style="display:flex; flex-direction:column; gap:5px;"><img class="news-gallery-thumb" src="' + file.path + '" onclick="openNewsCarousel(' + cIndex + ')" alt="Imagen">' + (file.caption ? '<span style="font-size:0.75rem; color:var(--text-light); text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + file.caption + '">' + file.caption + '</span>' : '') + '</div>';
                }
            });
            
            galleryHtml += '</div>';
            galleryContainer.innerHTML = galleryHtml;
            galleryContainer.style.display = 'block';
        } else {
            galleryContainer.style.display = 'none';
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        handleModalOpen();
    }

    function closeNewsModal(event) {
        const modal = document.getElementById('newsDetailModal');
        if (event.target === modal) {
            closeNewsModalDirect();
        }
    }

    function closeNewsModalDirect(fromPopState = false) {
        const modal = document.getElementById('newsDetailModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
        const modalNewsVid = document.getElementById('modalNewsVideo');
        if (modalNewsVid) {
            modalNewsVid.pause();
            modalNewsVid.src = '';
        }
        handleModalClose(fromPopState === true);
    }

    // Funciones del Lightbox Carousel
    function openNewsCarousel(index) {
        if (currentCarouselImages.length === 0) return;
        currentCarouselIndex = index;
        document.getElementById('newsCarouselOverlay').classList.add('active');
        updateCarouselState();
        handleModalOpen();
    }

    function startNewsCarouselAutoplay() {
        clearInterval(newsCarouselInterval);
        newsCarouselInterval = setInterval(() => {
            nextNewsCarousel();
        }, 4000);
    }

    function updateCarouselState() {
        const container = document.querySelector('.news-carousel-container');
        const imgElement = document.getElementById('newsCarouselImg');
        const counterElement = document.getElementById('newsCarouselCounter');
        const captionElement = document.getElementById('newsCarouselCaption');
        
        let videoElement = document.getElementById('newsCarouselVid');
        let pdfElement = document.getElementById('newsCarouselPdf');
        
        const currentItem = currentCarouselImages[currentCarouselIndex];
        const ext = currentItem.src.split('.').pop().toLowerCase();
        const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext) || currentItem.is_video;
        const isPdf = ext === 'pdf' || currentItem.is_pdf;
        
        if (isVideo || isPdf) {
            clearInterval(newsCarouselInterval); // Pausar autoplay para vídeos y pdfs
        } else {
            startNewsCarouselAutoplay(); // Autoplay para imágenes
        }
        
        if (isVideo) {
            if (imgElement) imgElement.style.display = 'none';
            if (pdfElement) pdfElement.style.display = 'none';
            if (!videoElement) {
                videoElement = document.createElement('video');
                videoElement.id = 'newsCarouselVid';
                videoElement.className = 'news-carousel-img';
                videoElement.style.cssText = 'max-width: 90vw; max-height: 70vh; object-fit: contain; display: block; margin: 0 auto; border-radius: 8px; background: #000; outline: none;';
                videoElement.controls = true;
                videoElement.autoplay = true;
                videoElement.onended = function() {
                    nextNewsCarousel();
                };
                container.insertBefore(videoElement, captionElement);
            }
            videoElement.src = currentItem.src;
            videoElement.style.display = 'block';
            videoElement.style.opacity = 0;
            setTimeout(() => {
                videoElement.style.opacity = 1;
                captionElement.textContent = currentItem.caption;
                counterElement.textContent = (currentCarouselIndex + 1) + ' / ' + currentCarouselImages.length;
            }, 150);
        } else if (isPdf) {
            if (imgElement) imgElement.style.display = 'none';
            if (videoElement) {
                videoElement.pause();
                videoElement.style.display = 'none';
            }
            if (!pdfElement) {
                pdfElement = document.createElement('iframe');
                pdfElement.id = 'newsCarouselPdf';
                pdfElement.className = 'news-carousel-img';
                pdfElement.style.cssText = 'width: 80vw; max-width: 900px; height: 70vh; display: block; margin: 0 auto; border-radius: 8px; border: none; background: #fff;';
                container.insertBefore(pdfElement, captionElement);
            }
            pdfElement.src = currentItem.src;
            pdfElement.style.display = 'block';
            pdfElement.style.opacity = 0;
            setTimeout(() => {
                pdfElement.style.opacity = 1;
                captionElement.textContent = currentItem.caption;
                counterElement.textContent = (currentCarouselIndex + 1) + ' / ' + currentCarouselImages.length;
            }, 150);
        } else {
            if (videoElement) {
                videoElement.pause();
                videoElement.style.display = 'none';
            }
            if (pdfElement) pdfElement.style.display = 'none';
            if (imgElement) {
                imgElement.style.display = 'block';
                imgElement.style.opacity = 0;
                setTimeout(() => {
                    imgElement.src = currentItem.src;
                    captionElement.textContent = currentItem.caption;
                    imgElement.style.opacity = 1;
                    counterElement.textContent = (currentCarouselIndex + 1) + ' / ' + currentCarouselImages.length;
                }, 150);
            }
        }
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

    function closeNewsCarouselDirect(fromPopState = false) {
        document.getElementById('newsCarouselOverlay').classList.remove('active');
        clearInterval(newsCarouselInterval);
        const videoElement = document.getElementById('newsCarouselVid');
        if (videoElement) {
            videoElement.pause();
            videoElement.style.display = 'none';
        }
        const pdfElement = document.getElementById('newsCarouselPdf');
        if (pdfElement) {
            pdfElement.src = '';
            pdfElement.style.display = 'none';
        }
        handleModalClose(fromPopState === true);
    }
    
    // Navegación con teclado para carrusel de noticias
    document.addEventListener('keydown', (e) => {
        const overlay = document.getElementById('newsCarouselOverlay');
        if (overlay && overlay.classList.contains('active')) {
            if (e.key === 'ArrowRight') nextNewsCarousel();
            if (e.key === 'ArrowLeft') prevNewsCarousel();
            if (e.key === 'Escape') closeNewsCarouselDirect();
        }
    });
    </script>

    <!-- Modal para maximizar banners -->
    <div id="bannerModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100vw; height:100vh; background-color:rgba(0,0,0,0.9); align-items:center; justify-content:center;" onclick="closeBannerModal()">
        <span style="position:absolute; top:20px; right:40px; color:white; font-size:40px; font-weight:bold; cursor:pointer;" onclick="closeBannerModal()">&times;</span>
        <img id="bannerModalImg" src="" style="display:none; width: 80vw; height: 80vh; max-width: 900px; object-fit: contain; image-rendering: high-quality; border-radius: 12px; box-shadow: 0 10px 50px rgba(0,0,0,0.8); background: rgba(0,0,0,0.3);">
        <video id="bannerModalVid" src="" style="display:none; width: 80vw; height: 80vh; max-width: 900px; object-fit: contain; border-radius: 12px; box-shadow: 0 10px 50px rgba(0,0,0,0.8); background: rgba(0,0,0,0.3);" controls autoplay onclick="event.stopPropagation()"></video>
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
