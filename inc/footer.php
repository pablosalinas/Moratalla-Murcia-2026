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
                <?php $footerAdminEmail = isset($settings['admin_email']) && !empty($settings['admin_email']) ? $settings['admin_email'] : 'info@moratalla-murcia.com'; ?>
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
        
        // Mobile Submenu Accordion Logic (+ / - circular button)
        const menuItemsWithDropdown = document.querySelectorAll('.nav-menu li');
        menuItemsWithDropdown.forEach(li => {
            const dropdown = Array.from(li.children).find(el => el.classList && el.classList.contains('dropdown'));
            if (dropdown) {
                li.classList.add('has-submenu');
                const toggleBtn = document.createElement('div');
                toggleBtn.className = 'mobile-submenu-toggle';
                toggleBtn.setAttribute('aria-label', 'Desplegar submenú');
                toggleBtn.innerHTML = '<i class="fas fa-plus"></i>';
                li.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    // Close sibling open submenus at the same level
                    const parentUl = li.parentElement;
                    if (parentUl) {
                        Array.from(parentUl.children).forEach(siblingLi => {
                            if (siblingLi !== li && siblingLi.classList.contains('active')) {
                                siblingLi.classList.remove('active');
                                const siblingIcon = siblingLi.querySelector(':scope > .mobile-submenu-toggle i');
                                if (siblingIcon) siblingIcon.className = 'fas fa-plus';
                            }
                        });
                    }
                    // Toggle current
                    const isActive = li.classList.toggle('active');
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.className = isActive ? 'fas fa-minus' : 'fas fa-plus';
                    }
                });
            }
        });

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
        const c4 = document.getElementById('globalSearchModal');
        
        const anyOpen = (c1 && c1.classList.contains('active')) ||
                        (c2 && c2.classList.contains('active')) ||
                        (c3 && c3.style.display === 'flex') ||
                        (c4 && c4.style.display === 'flex');
                        
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
            if (typeof closeGlobalSearchModal === 'function') closeGlobalSearchModal(true);
        }
    });
    // -----------------------------------------------------------------

    let currentCarouselImages = [];
    let currentCarouselIndex = 0;
    let newsCarouselInterval;

    function sanitizeNewsModalContent(html) {
        if (!html || typeof html !== 'string') return '';
        // 1. Eliminar cualquier etiqueta <style>...</style> para evitar que altere el layout global del sitio
        let clean = html.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
        // 2. Eliminar scripts por seguridad
        clean = clean.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
        // 3. Eliminar etiquetas link
        clean = clean.replace(/<link\b[^>]*>/gi, '');
        // 4. Eliminar doctype y etiquetas de página completa
        clean = clean.replace(/<!DOCTYPE[^>]*>/gi, '');
        clean = clean.replace(/<\/?(?:html|head|body)[^>]*>/gi, '');
        return clean;
    }

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
            
            const hasCaption = data.image_caption && data.image_caption.trim() !== '';
            const hasAuthor = data.image_author && data.image_author.trim() !== '';
            
            if (hasCaption || hasAuthor) {
                let captionHtml = '';
                if (hasCaption) {
                    captionHtml += '<span>' + data.image_caption + '</span>';
                }
                if (hasAuthor) {
                    if (hasCaption) captionHtml += ' &bull; ';
                    captionHtml += '<span style="font-weight: 600;">Autor/a: ' + data.image_author + '</span>';
                }
                modalImageCaption.innerHTML = captionHtml;
                modalImageCaption.style.display = 'block';
            } else {
                modalImageCaption.innerHTML = '';
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
        
        modalText.innerHTML = sanitizeNewsModalContent(data.content);
        
        // Cargar galería
        galleryContainer.innerHTML = '';
        currentCarouselImages = []; // Para carrusel
        
        let hasGalleryFiles = false;
        let seenPathsCarousel = new Set();
        
        if (data.image) {
            const ext = data.image.split('.').pop().toLowerCase();
            const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
            const isPdf = data.image.toLowerCase().endsWith('.pdf');
            currentCarouselImages.push({
                src: data.image, 
                caption: data.image_caption || '', 
                author: data.image_author || '', 
                is_video: isVideo, 
                is_pdf: isPdf
            });
            seenPathsCarousel.add(data.image);
        }
        
        if (data.gallery && data.gallery.length > 0) {
            hasGalleryFiles = true;
            data.gallery.forEach(imgObj => {
                if (seenPathsCarousel.has(imgObj.image_path)) return;
                const ext = imgObj.image_path.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext) || imgObj.is_video == 1;
                const isPdf = imgObj.image_path.toLowerCase().endsWith('.pdf');
                currentCarouselImages.push({
                    src: imgObj.image_path, 
                    caption: imgObj.caption || '', 
                    author: imgObj.author || '', 
                    is_video: isVideo, 
                    is_pdf: isPdf
                });
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
                allFiles.push({path: data.image, caption: data.image_caption || '', author: data.image_author || ''});
                seenPathsThumbs.add(data.image);
            }
            if (data.gallery) {
                data.gallery.forEach(g => {
                    if (!seenPathsThumbs.has(g.image_path)) {
                        allFiles.push({path: g.image_path, caption: g.caption || '', author: g.author || ''});
                        seenPathsThumbs.add(g.image_path);
                    }
                });
            }
            
            allFiles.forEach(file => {
                const ext = file.path.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(ext);
                let cIndex = currentCarouselImages.findIndex(img => img.src === file.path);
                
                let thumbLabel = '';
                if (file.caption) thumbLabel += file.caption;
                if (file.author) {
                    if (thumbLabel) thumbLabel += ' • ';
                    thumbLabel += 'Autor/a: ' + file.author;
                }

                if (file.path.toLowerCase().endsWith('.pdf')) {
                    galleryHtml += '<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 1rem; background:#fee2e2; color:#b91c1c; border-radius:8px; border: 1px solid #f87171; cursor:pointer;" onclick="openNewsCarousel(' + cIndex + ')"><i class="fas fa-file-pdf fa-2x"></i><span style="font-size:0.75rem; margin-top:0.5rem; text-align:center;">' + (thumbLabel ? thumbLabel : 'Ver PDF') + '</span></div>';
                } else if (isVideo) {
                    galleryHtml += '<div style="display:flex; flex-direction:column; gap:5px; position:relative;">' +
                        '<video class="news-gallery-thumb" src="' + file.path + '" style="background:#000; width:100%; height:80px; object-fit:cover; border-radius:8px; cursor:pointer;" onclick="openNewsCarousel(' + cIndex + ')" preload="metadata"></video>' +
                        '<div style="position:absolute; top:30px; left:50%; transform:translateX(-50%); color:white; font-size:1.5rem; pointer-events:none; opacity:0.8; text-shadow:0 1px 4px rgba(0,0,0,0.6);"><i class="fas fa-play-circle"></i></div>' +
                        (thumbLabel ? '<span style="font-size:0.75rem; color:var(--text-light); text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + thumbLabel.replace(/"/g, '&quot;') + '">' + thumbLabel + '</span>' : '') +
                        '</div>';
                } else {
                    galleryHtml += '<div style="display:flex; flex-direction:column; gap:5px;"><img class="news-gallery-thumb" src="' + file.path + '" onclick="openNewsCarousel(' + cIndex + ')" alt="Imagen">' + (thumbLabel ? '<span style="font-size:0.75rem; color:var(--text-light); text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + thumbLabel.replace(/"/g, '&quot;') + '">' + thumbLabel + '</span>' : '') + '</div>';
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
        if (modal) modal.classList.remove('active');

        // Restaurar estilos globales del body y html para asegurar respuesta total en cualquier monitor
        document.body.style.overflow = '';
        document.body.style.maxWidth = '';
        document.body.style.margin = '';
        document.body.style.padding = '';
        document.body.style.width = '';
        if (document.documentElement) {
            document.documentElement.style.maxWidth = '';
            document.documentElement.style.margin = '';
            document.documentElement.style.padding = '';
            document.documentElement.style.width = '';
        }

        const modalNewsVid = document.getElementById('modalNewsVideo');
        if (modalNewsVid) {
            modalNewsVid.pause();
            modalNewsVid.src = '';
        }

        // Limpiar el contenido del modal para evitar residuos en memoria o DOM
        const modalText = document.getElementById('modalText');
        if (modalText) modalText.innerHTML = '';
        const modalImageContainer = document.getElementById('modalImageContainer');
        if (modalImageContainer) modalImageContainer.innerHTML = '';
        const galleryContainer = document.getElementById('modalGalleryContainer');
        if (galleryContainer) galleryContainer.innerHTML = '';
        const oldTopBtn = document.getElementById('modalTopCarouselBtn');
        if (oldTopBtn) oldTopBtn.remove();

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
                let cap = currentItem.caption || '';
                if (currentItem.author && currentItem.author.trim() !== '') {
                    if (cap) cap += ' • ';
                    cap += 'Autor/a: ' + currentItem.author;
                }
                captionElement.textContent = cap;
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
                let cap = currentItem.caption || '';
                if (currentItem.author && currentItem.author.trim() !== '') {
                    if (cap) cap += ' • ';
                    cap += 'Autor/a: ' + currentItem.author;
                }
                captionElement.textContent = cap;
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
                    let cap = currentItem.caption || '';
                    if (currentItem.author && currentItem.author.trim() !== '') {
                        if (cap) cap += ' • ';
                        cap += 'Autor/a: ' + currentItem.author;
                    }
                    captionElement.textContent = cap;
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
    
    // Navegación con teclado para carrusel de noticias y modal de noticias
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('newsCarouselOverlay');
            if (overlay && overlay.classList.contains('active')) {
                closeNewsCarouselDirect();
                return;
            }
            const newsModal = document.getElementById('newsDetailModal');
            if (newsModal && newsModal.classList.contains('active')) {
                closeNewsModalDirect();
                return;
            }
        }
        const overlay = document.getElementById('newsCarouselOverlay');
        if (overlay && overlay.classList.contains('active')) {
            if (e.key === 'ArrowRight') nextNewsCarousel();
            if (e.key === 'ArrowLeft') prevNewsCarousel();
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

    <!-- TEXT-TO-SPEECH (TTS) LOGIC -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const ttsBtn = document.getElementById('tts-toggle');
        if (!ttsBtn) return;
        
        let ttsActive = localStorage.getItem('tts-active') === 'true';
        let synth = window.speechSynthesis;
        let voices = [];
        let ttsQueue = [];
        let isReading = false;
        
        // Cargar voces
        function loadVoices() {
            voices = synth.getVoices();
        }
        
        loadVoices();
        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = loadVoices;
        }
        
        function updateTTSBtnUI() {
            if (ttsActive) {
                ttsBtn.style.background = 'var(--primary)';
                ttsBtn.style.color = 'white';
                ttsBtn.innerHTML = '<i class="fas fa-volume-up"></i> <span class="hide-mobile">Detener Lectura</span>';
            } else {
                ttsBtn.style.background = 'none';
                ttsBtn.style.color = 'var(--primary)';
                ttsBtn.innerHTML = '<i class="fas fa-volume-up"></i> <span class="hide-mobile">Leer Voz Alta</span>';
            }
        }
        
        function getMaleSpanishVoice() {
            let esVoices = voices.filter(v => v.lang.startsWith('es'));
            
            // 1. Intentar encontrar nombres explícitamente masculinos conocidos
            let maleNames = ['pablo', 'diego', 'jorge', 'juan', 'carlos', 'alvaro', 'raul', 'antonio'];
            let maleVoice = esVoices.find(v => maleNames.some(name => v.name.toLowerCase().includes(name)));
            if (maleVoice) return maleVoice;
            
            // 2. Si no hay nombres masculinos, filtrar las que sabemos que son femeninas
            let femaleNames = ['helena', 'laura', 'monica', 'paulina', 'luciana', 'victoria', 'sabina', 'carmen', 'mia', 'isabel', 'conchita'];
            let unknownVoices = esVoices.filter(v => !femaleNames.some(name => v.name.toLowerCase().includes(name)));
            
            if (unknownVoices.length > 0) return unknownVoices[0]; // Retorna la primera que no sea claramente femenina
            
            // Fallback a cualquier voz en español si no hay más remedio (el sistema no tiene voces masculinas)
            if (esVoices.length > 0) return esVoices[0];
            
            // Si no hay español, devolver null
            return null;
        }
        
        function readNext() {
            if (!ttsActive || ttsQueue.length === 0) {
                isReading = false;
                return;
            }
            
            isReading = true;
            let text = ttsQueue.shift();
            
            // Skip empty text
            if (!text || text.trim() === '') {
                readNext();
                return;
            }
            
            let utterance = new SpeechSynthesisUtterance(text);
            
            let voice = getMaleSpanishVoice();
            if (voice) utterance.voice = voice;
            
            // Ajustar para voz de hombre natural y amena (sin alteraciones robóticas)
            utterance.pitch = 1.0; // Tono natural (evita el efecto metálico)
            utterance.rate = 1.0;  // Velocidad normal y fluida
            
            utterance.onend = () => {
                readNext();
            };
            
            utterance.onerror = (e) => {
                console.error("TTS Error:", e);
                readNext();
            };
            
            synth.speak(utterance);
        }
        
        function extractTextFromPage() {
            ttsQueue = [];
            
            // Extraer textos relevantes (Evitar menús, footers, etc si es posible)
            // Primero buscar el contenedor principal, si no, buscar en el body
            let mainContainer = document.querySelector('.main-content') || document.querySelector('.content-card');
            
            if (!mainContainer) {
                // Si no hay un contenedor claro, recopilamos de todos modos pero omitiendo el header/footer/nav
                mainContainer = document.body;
            }
            
            // Leer título de la página
            if (document.title) {
                ttsQueue.push(document.title.split('-')[0].trim());
            }
            
            // Seleccionar elementos de texto dentro del contenedor principal
            let elements = mainContainer.querySelectorAll('h1, h2, h3, h4, p, .news-card-title, .news-card-excerpt, .curiosidad-title, .curiosidad-desc');
            
            elements.forEach(el => {
                // Si es el body entero, evitar elementos dentro de header, footer, nav
                if (el.closest('header') || el.closest('footer') || el.closest('nav') || el.closest('#cookie-banner')) return;
                
                // Verificar que el elemento es visible
                if (el.offsetParent !== null && window.getComputedStyle(el).display !== 'none') {
                    let text = el.innerText || el.textContent;
                    if (text && text.trim().length > 0) {
                        ttsQueue.push(text.trim());
                    }
                }
            });
        }
        
        function startTTS() {
            synth.cancel(); // Detener cualquier lectura previa
            extractTextFromPage();
            if (ttsQueue.length > 0) {
                readNext();
            }
        }
        
        function stopTTS() {
            synth.cancel();
            ttsQueue = [];
            isReading = false;
        }
        
        ttsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            ttsActive = !ttsActive;
            localStorage.setItem('tts-active', ttsActive);
            updateTTSBtnUI();
            
            if (ttsActive) {
                // A veces el navegador requiere interacción del usuario para cargar voces o iniciar TTS
                if (voices.length === 0) loadVoices();
                startTTS();
            } else {
                stopTTS();
            }
        });
        
        // Inicializar estado UI
        updateTTSBtnUI();
        
        // Autostart si estaba activo y navegó a otra página (requiere que el navegador lo permita, a veces bloquean autoplay de TTS)
        if (ttsActive) {
            // Pequeño delay para asegurar que el DOM y voces cargaron
            setTimeout(() => {
                if (voices.length === 0) loadVoices();
                
                // Intento de inicio automático (puede fallar si el navegador requiere interacción)
                startTTS();
            }, 1000);
        }
        
        // Escuchar cambios en modales de noticias (para leer la noticia abierta)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.target.id === 'newsDetailModal' && mutation.target.classList.contains('active')) {
                    if (ttsActive) {
                        synth.cancel();
                        ttsQueue = [];
                        
                        let mTitle = document.getElementById('modalTitle');
                        let mText = document.getElementById('modalText');
                        
                        if (mTitle) ttsQueue.push(mTitle.innerText);
                        if (mText) ttsQueue.push(mText.innerText);
                        
                        readNext();
                    }
                }
            });
        });
        
        const newsModal = document.getElementById('newsDetailModal');
        if (newsModal) {
            observer.observe(newsModal, { attributes: true, attributeFilter: ['class'] });
        }
    });
    </script>

    <!-- BOTÓN FLOTANTE DE ACCESO RÁPIDO AL BUSCADOR GLOBAL -->
    <button type="button" id="floatingGlobalSearchBtn" class="gs-floating-trigger" onclick="openGlobalSearchModal()" title="Abrir Buscador Global (Ctrl+K)">
        <i class="fas fa-search"></i>
        <span>Buscador</span>
    </button>

    <!-- CUADRO DE DIÁLOGO MODAL DEL BUSCADOR GLOBAL -->
    <div id="globalSearchModal" class="gs-modal-overlay" style="display: none;" onclick="if(event.target===this) closeGlobalSearchModal()">
        <div class="gs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gsModalTitle">
            <div class="gs-modal-header">
                <div>
                    <h3 id="gsModalTitle"><i class="fas fa-search-location" style="color: #d4af37;"></i> Buscador Global · moratalla-murcia.com</h3>
                    <p>Localiza al instante noticias, eventos, asociaciones, artesanos, alojamientos, restauración, secciones, citas o información en Internet Global.</p>
                </div>
                <button type="button" class="gs-close-btn" onclick="closeGlobalSearchModal()" title="Cerrar (Esc)">&times;</button>
            </div>

            <div class="gs-modal-controls">
                <div class="gs-dialog-input-row">
                    <div class="gs-dialog-input-box">
                        <i class="fas fa-search"></i>
                        <input type="search" id="modalGlobalSearchInput" placeholder="Escribe aquí lo que buscas (ej: tambor, casa rural, restaurante...)" autocomplete="off" enterkeyhint="search" onkeydown="if(event.key==='Enter'){event.preventDefault(); this.blur(); executeGlobalSearch();}">
                        <button type="button" id="gsClearBtn" onclick="clearGlobalSearchInput()" style="display:none;" title="Limpiar texto">&times;</button>
                    </div>
                    <button type="button" onclick="document.getElementById('modalGlobalSearchInput').blur(); executeGlobalSearch();" class="gs-dialog-search-btn">
                        <i class="fas fa-search"></i> <span class="gs-btn-text">Buscar</span>
                    </button>
                    <button type="button" id="gsMobileFilterToggle" class="gs-mobile-filter-btn" onclick="toggleGsMobileFilters()" title="Mostrar/Ocultar opciones de filtrado" aria-expanded="false">
                        <i class="fas fa-sliders-h"></i> <span class="gs-filter-btn-label">Filtros</span>
                    </button>
                </div>

                <div id="gsCollapsibleFilters" class="gs-collapsible-filters">
                    <!-- Selección de Ámbito y Filtro de Palabras (sin letreros OR/AND, solo las aclaraciones) -->
                    <div class="gs-dialog-filter-bar">
                        <div class="gs-mode-box">
                            <span class="gs-mode-title"><i class="fas fa-globe"></i> Dónde buscar:</span>
                            <label class="gs-mode-option active" id="modalLblScopeWeb">
                                <input type="radio" name="modal_search_scope" value="web" checked onchange="syncSearchScope('web'); executeGlobalSearch();">
                                <span class="gs-check-mark">✓</span>
                                <span>🌐 <strong>Web moratalla-murcia</strong> <span class="gs-hide-xs">(Por defecto)</span></span>
                            </label>
                            <label class="gs-mode-option" id="modalLblScopeInternet">
                                <input type="radio" name="modal_search_scope" value="internet" onchange="syncSearchScope('internet'); executeGlobalSearch();">
                                <span class="gs-check-mark">✓</span>
                                <span>🌍 <strong>Internet Global</strong></span>
                            </label>
                            <label class="gs-mode-option" id="modalLblScopeBoth">
                                <input type="radio" name="modal_search_scope" value="both" onchange="syncSearchScope('both'); executeGlobalSearch();">
                                <span class="gs-check-mark">✓</span>
                                <span>🔄 <strong>Web + Internet</strong></span>
                            </label>
                        </div>

                        <div class="gs-mode-box">
                            <span class="gs-mode-title"><i class="fas fa-filter"></i> Filtro de palabras:</span>
                            <label class="gs-mode-option active" id="modalLblModeAnd">
                                <input type="radio" name="modal_search_mode" value="AND" checked onchange="syncSearchMode('AND'); executeGlobalSearch();">
                                <span class="gs-check-mark">✓</span>
                                <span>Contiene <u>todas</u> las palabras <span class="gs-hide-xs">(Por defecto)</span></span>
                            </label>
                            <label class="gs-mode-option" id="modalLblModeOr">
                                <input type="radio" name="modal_search_mode" value="OR" onchange="syncSearchMode('OR'); executeGlobalSearch();">
                                <span class="gs-check-mark">✓</span>
                                <span>Contiene <u>cualquiera</u> de las palabras</span>
                            </label>
                        </div>
                    </div>

                    <!-- Accesos rápidos dentro del diálogo -->
                    <div class="gs-quick-presets-row">
                        <span>Explorar rápido:</span>
                        <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'noticias')">📰 Noticias</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'eventos')">📅 Eventos</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Asociación', 'asociaciones')">🤝 Asociaciones</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Artesanía', 'artesanos')">🏺 Artesanos</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Casa Rural', 'alojamientos')">🛏️ Alojamientos</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Restaurante', 'restauracion')">🍽️ Restauración</button>
                        <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'citas')">💬 Citas</button>
                    </div>
                </div>

                <!-- Pestañas de filtrado por sección y contador -->
                <div class="gs-tabs-summary-row">
                    <div class="gs-category-tabs" id="gsCategoryTabs">
                        <button type="button" class="gs-tab active" data-group="all" onclick="setGlobalSearchGroup('all')">📋 Todos <span id="gsCount_all">0</span></button>
                        <button type="button" class="gs-tab" data-group="noticias" onclick="setGlobalSearchGroup('noticias')">📰 Noticias <span id="gsCount_noticias">0</span></button>
                        <button type="button" class="gs-tab" data-group="eventos" onclick="setGlobalSearchGroup('eventos')">📅 Eventos <span id="gsCount_eventos">0</span></button>
                        <button type="button" class="gs-tab" data-group="asociaciones" onclick="setGlobalSearchGroup('asociaciones')">🤝 Asociaciones <span id="gsCount_asociaciones">0</span></button>
                        <button type="button" class="gs-tab" data-group="artesanos" onclick="setGlobalSearchGroup('artesanos')">🏺 Artesanos <span id="gsCount_artesanos">0</span></button>
                        <button type="button" class="gs-tab" data-group="alojamientos" onclick="setGlobalSearchGroup('alojamientos')">🛏️ Alojamientos <span id="gsCount_alojamientos">0</span></button>
                        <button type="button" class="gs-tab" data-group="restauracion" onclick="setGlobalSearchGroup('restauracion')">🍽️ Restauración <span id="gsCount_restauracion">0</span></button>
                        <button type="button" class="gs-tab" data-group="citas" onclick="setGlobalSearchGroup('citas')">💬 Citas <span id="gsCount_citas">0</span></button>
                        <button type="button" class="gs-tab" data-group="paginas" onclick="setGlobalSearchGroup('paginas')">📄 Otras Secciones <span id="gsCount_paginas">0</span></button>
                        <button type="button" class="gs-tab" data-group="internet" onclick="setGlobalSearchGroup('internet')">🌍 Internet Global <span id="gsCount_internet">0</span></button>
                    </div>
                    <div id="gsSummaryCounter" class="gs-summary-counter"></div>
                </div>
            </div>

            <div class="gs-modal-body" id="gsResultsContainer">
                <div class="gs-empty-state">
                    <i class="fas fa-compass" style="font-size: 2.6rem; color: #2d6a4f; margin-bottom: 0.8rem;"></i>
                    <h4>Explora todo el contenido de Moratalla o busca en Internet Global</h4>
                    <p>Por defecto se busca dentro de <strong>moratalla-murcia.com</strong> mostrando los contenidos que incluyan <strong>todas las palabras</strong>. También puedes marcar <strong>cualquiera de las palabras</strong> o buscar en <strong>Internet Global</strong>.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Botón Flotante del Buscador Global */
        .gs-floating-trigger {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99990;
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: #ffffff;
            border: 2px solid #d4af37;
            border-radius: 50px;
            padding: 0.72rem 1.25rem;
            font-size: 0.92rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 24px rgba(27, 67, 50, 0.35);
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .gs-floating-trigger i {
            color: #fde68a;
            font-size: 1rem;
        }
        .gs-floating-trigger:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 28px rgba(27, 67, 50, 0.48);
        }

        /* Modal del Buscador Global */
        .gs-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
            z-index: 999990;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
        }
        .gs-modal-dialog {
            background: #ffffff;
            width: 100%;
            max-width: 960px;
            max-height: 88vh;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 2px solid #2d6a4f;
        }
        .gs-modal-header {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: #ffffff;
            padding: 1.15rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .gs-modal-header h3 {
            margin: 0;
            font-size: 1.22rem;
            font-weight: 800;
            color: #ffffff;
        }
        .gs-modal-header p {
            margin: 0.2rem 0 0 0;
            font-size: 0.83rem;
            color: rgba(255, 255, 255, 0.86);
        }
        .gs-close-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .gs-close-btn:hover {
            background: #ef4444;
        }
        .gs-modal-controls {
            padding: 1rem 1.4rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .gs-dialog-input-row {
            display: flex;
            gap: 10px;
            margin-bottom: 0.75rem;
        }
        .gs-dialog-input-box {
            flex: 1;
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 0 12px;
            gap: 10px;
            transition: border-color 0.2s;
        }
        .gs-dialog-input-box:focus-within {
            border-color: #2d6a4f;
        }
        .gs-dialog-input-box i {
            color: #2d6a4f;
            font-size: 1.05rem;
        }
        .gs-dialog-input-box input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0.7rem 0;
            font-size: 0.96rem;
            font-family: inherit;
            color: #0f172a;
            min-width: 0;
        }
        #gsClearBtn {
            background: #e2e8f0;
            border: none;
            color: #475569;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }
        .gs-dialog-search-btn {
            background: #1b4332;
            color: #ffffff;
            border: none;
            padding: 0 1.35rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }
        .gs-dialog-search-btn:hover {
            background: #2d6a4f;
        }
        .gs-dialog-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 0.65rem;
        }
        .gs-mode-box {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .gs-mode-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
        }
        .gs-mode-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 0.78rem;
            cursor: pointer;
            user-select: none;
        }
        .gs-mode-option input {
            display: none;
        }
        .gs-mode-option .gs-check-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 15px;
            height: 15px;
            border-radius: 4px;
            border: 1px solid #94a3b8;
            color: transparent;
            font-size: 0.7rem;
            font-weight: 900;
        }
        .gs-mode-option.active {
            border: 2px solid #1b4332;
            background: #ecfdf5;
            color: #1b4332;
            font-weight: 600;
        }
        .gs-mode-option.active .gs-check-mark {
            background: #1b4332;
            border-color: #1b4332;
            color: #ffffff;
        }
        .gs-summary-counter {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1b4332;
        }
        .gs-quick-presets-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 0.65rem;
        }
        .gs-quick-presets-row span {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
        }
        .gs-quick-presets-row button {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 3px 9px;
            border-radius: 14px;
            font-size: 0.74rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .gs-quick-presets-row button:hover {
            background: #2d6a4f;
            color: #ffffff;
            border-color: #2d6a4f;
        }
        .gs-mobile-filter-btn {
            display: none;
            background: #ffffff;
            color: #1b4332;
            border: 2px solid #cbd5e1;
            padding: 0 0.85rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .gs-mobile-filter-btn.active {
            background: #ecfdf5;
            border-color: #1b4332;
            color: #1b4332;
        }
        .gs-tabs-summary-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .gs-category-tabs {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .gs-tab {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.77rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .gs-tab span {
            background: #f1f5f9;
            color: #475569;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 0.71rem;
        }
        .gs-tab.active {
            background: #1b4332;
            border-color: #1b4332;
            color: #ffffff;
        }
        .gs-tab.active span {
            background: #d4af37;
            color: #1b4332;
        }
        .gs-modal-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 1.1rem 1.4rem;
            background: #ffffff;
        }
        .gs-empty-state {
            text-align: center;
            padding: 2.2rem 1rem;
            color: #64748b;
        }
        .gs-empty-state h4 {
            margin: 0 0 0.4rem 0;
            color: #1e293b;
            font-size: 1.08rem;
        }
        .gs-results-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }
        .gs-result-card {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            padding: 0.95rem 1.1rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        }
        .gs-result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(27, 67, 50, 0.1);
            border-color: #2d6a4f;
        }
        .gs-result-thumb {
            width: 76px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f1f5f9;
        }
        .gs-result-icon-box {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #1b4332;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .gs-result-content {
            flex: 1;
            min-width: 0;
        }
        .gs-result-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .gs-result-badge {
            background: #1b4332;
            color: #ffffff;
            font-size: 0.69rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 12px;
            letter-spacing: 0.4px;
        }
        .gs-result-sub {
            font-size: 0.77rem;
            color: #64748b;
            font-weight: 600;
        }
        .gs-result-title {
            margin: 0 0 4px 0;
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
            word-break: break-word;
        }
        .gs-result-snippet {
            margin: 0;
            font-size: 0.86rem;
            color: #475569;
            line-height: 1.5;
            word-break: break-word;
        }
        .gs-highlight {
            background: #fef08a;
            color: #1e293b;
            padding: 0 3px;
            border-radius: 3px;
            font-weight: 700;
        }

        /* Responsive para Tablets y Móviles */
        @media (max-width: 768px) {
            .gs-modal-overlay {
                padding: 0;
                align-items: stretch;
                justify-content: stretch;
            }
            .gs-modal-dialog {
                max-width: 100%;
                width: 100%;
                height: 100vh;
                height: 100dvh;
                max-height: 100vh;
                max-height: 100dvh;
                border-radius: 0;
                border: none;
            }
            .gs-modal-header {
                padding: 0.75rem 1rem;
                gap: 0.6rem;
            }
            .gs-modal-header h3 {
                font-size: 1rem;
                line-height: 1.25;
            }
            .gs-modal-header p {
                display: none;
            }
            .gs-close-btn {
                width: 34px;
                height: 34px;
                font-size: 1.35rem;
            }
            .gs-modal-controls {
                padding: 0.7rem 0.85rem 0.55rem;
            }
            .gs-dialog-input-row {
                gap: 6px;
                margin-bottom: 0.55rem;
            }
            .gs-dialog-input-box {
                padding: 0 10px;
                gap: 8px;
                border-radius: 10px;
            }
            .gs-dialog-input-box input {
                font-size: 16px; /* Evita zoom automático en iOS/Android */
                padding: 0.58rem 0;
            }
            .gs-dialog-search-btn {
                padding: 0 0.9rem;
                border-radius: 10px;
                font-size: 0.85rem;
            }
            .gs-mobile-filter-btn {
                display: inline-flex;
                border-radius: 10px;
                padding: 0 0.75rem;
            }
            .gs-collapsible-filters {
                display: none;
                padding-top: 0.25rem;
                margin-bottom: 0.45rem;
                border-top: 1px dashed #cbd5e1;
            }
            .gs-collapsible-filters.open {
                display: block;
            }
            .gs-dialog-filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
                margin-bottom: 0.55rem;
            }
            .gs-mode-box {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding-bottom: 2px;
            }
            .gs-mode-box::-webkit-scrollbar {
                display: none;
            }
            .gs-mode-title {
                flex-shrink: 0;
                font-size: 0.74rem;
            }
            .gs-mode-option {
                flex-shrink: 0;
                padding: 4px 8px;
                font-size: 0.74rem;
            }
            .gs-hide-xs {
                display: none;
            }
            .gs-quick-presets-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                margin-bottom: 0.25rem;
                padding-bottom: 2px;
            }
            .gs-quick-presets-row::-webkit-scrollbar {
                display: none;
            }
            .gs-quick-presets-row span,
            .gs-quick-presets-row button {
                flex-shrink: 0;
            }
            .gs-category-tabs {
                scrollbar-width: none;
                padding-bottom: 2px;
            }
            .gs-category-tabs::-webkit-scrollbar {
                display: none;
            }
            .gs-tab {
                padding: 4px 9px;
                font-size: 0.74rem;
            }
            .gs-summary-counter {
                font-size: 0.75rem;
                padding: 2px 2px 0;
            }
            .gs-modal-body {
                padding: 0.75rem 0.85rem 1.2rem;
            }
            .gs-empty-state {
                padding: 1.5rem 0.75rem;
            }
            .gs-empty-state h4 {
                font-size: 0.98rem;
            }
            .gs-empty-state p {
                font-size: 0.83rem;
            }
            .gs-results-list {
                gap: 0.6rem;
            }
            .gs-result-card {
                padding: 0.75rem 0.85rem;
                gap: 0.75rem;
                border-radius: 12px;
            }
            .gs-result-thumb {
                width: 60px;
                height: 52px;
                border-radius: 8px;
            }
            .gs-result-icon-box {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                font-size: 1.15rem;
            }
            .gs-result-badge {
                font-size: 0.64rem;
                padding: 2px 7px;
            }
            .gs-result-sub {
                font-size: 0.72rem;
            }
            .gs-result-title {
                font-size: 0.93rem;
            }
            .gs-result-snippet {
                font-size: 0.8rem;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .gs-floating-trigger {
                bottom: 16px;
                right: 16px;
                padding: 0.62rem 1rem;
                font-size: 0.84rem;
            }
        }

        @media (max-width: 480px) {
            .gs-dialog-search-btn .gs-btn-text {
                display: none;
            }
            .gs-dialog-search-btn {
                padding: 0 0.85rem;
            }
            .gs-mobile-filter-btn .gs-filter-btn-label {
                display: none;
            }
            .gs-mobile-filter-btn {
                padding: 0 0.75rem;
            }
        }
    </style>

    <script>
    let gsCurrentMode = 'AND';   // Por defecto: contiene todas las palabras
    let gsCurrentScope = 'web';  // Por defecto: busca en la web moratalla-murcia.com
    let gsCurrentGroup = 'all';
    let gsCachedResults = [];
    let gsCachedWords = [];
    let gsDebounceTimer = null;

    function toggleGsMobileFilters(forceState) {
        const panel = document.getElementById('gsCollapsibleFilters');
        const btn = document.getElementById('gsMobileFilterToggle');
        if (!panel || !btn) return;
        const isOpen = (typeof forceState === 'boolean') ? forceState : !panel.classList.contains('open');
        panel.classList.toggle('open', isOpen);
        btn.classList.toggle('active', isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function syncSearchMode(mode) {
        gsCurrentMode = (mode === 'OR') ? 'OR' : 'AND';

        document.querySelectorAll('input[name="modal_search_mode"]').forEach(r => {
            r.checked = (r.value === gsCurrentMode);
        });

        const modalOr = document.getElementById('modalLblModeOr');
        const modalAnd = document.getElementById('modalLblModeAnd');
        if (modalOr && modalAnd) {
            modalOr.classList.toggle('active', gsCurrentMode === 'OR');
            modalAnd.classList.toggle('active', gsCurrentMode === 'AND');
        }
    }

    function syncSearchScope(scope) {
        gsCurrentScope = (scope === 'internet' || scope === 'both') ? scope : 'web';

        document.querySelectorAll('input[name="modal_search_scope"]').forEach(r => {
            r.checked = (r.value === gsCurrentScope);
        });

        const modalScopes = {
            web: document.getElementById('modalLblScopeWeb'),
            internet: document.getElementById('modalLblScopeInternet'),
            both: document.getElementById('modalLblScopeBoth')
        };
        Object.keys(modalScopes).forEach(k => {
            const el = modalScopes[k];
            if (el) el.classList.toggle('active', k === gsCurrentScope);
        });

        if (gsCurrentScope === 'internet') {
            setGlobalSearchGroup('internet', false);
        } else if (gsCurrentScope === 'web' && gsCurrentGroup === 'internet') {
            setGlobalSearchGroup('all', false);
        }
    }

    function openGlobalSearchModal(initialQuery = '', initialGroup = 'all') {
        const modal = document.getElementById('globalSearchModal');
        const modalInput = document.getElementById('modalGlobalSearchInput');
        if (!modal) return;

        // Cerrar menú móvil si estuviera desplegado
        const navContainer = document.querySelector('.nav-container');
        if (navContainer && navContainer.classList.contains('active')) {
            navContainer.classList.remove('active');
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (typeof handleModalOpen === 'function') handleModalOpen();

        if (initialGroup) {
            setGlobalSearchGroup(initialGroup, false);
        }
        if (initialQuery !== '') {
            modalInput.value = initialQuery;
            executeGlobalSearch();
        } else if (modalInput.value.trim() !== '') {
            executeGlobalSearch();
        }
        // En móviles, enfocar el input solo si no se abrió mediante un acceso rápido con consulta predefinida
        if (initialQuery === '' || window.innerWidth > 768) {
            setTimeout(() => modalInput.focus(), 80);
        }
    }

    function closeGlobalSearchModal(fromPopState = false) {
        const modal = document.getElementById('globalSearchModal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
        if (typeof handleModalClose === 'function') handleModalClose(fromPopState === true);
    }

    function openGlobalSearchWithPreset(queryText, groupName) {
        syncSearchScope('web');
        if (window.innerWidth <= 768) {
            toggleGsMobileFilters(false);
        }
        openGlobalSearchModal(queryText, groupName);
    }

    function clearGlobalSearchInput() {
        const inp = document.getElementById('modalGlobalSearchInput');
        inp.value = '';
        document.getElementById('gsClearBtn').style.display = 'none';
        inp.focus();
    }

    function setGlobalSearchGroup(group, reRender = true) {
        gsCurrentGroup = group;
        document.querySelectorAll('#gsCategoryTabs .gs-tab').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-group') === group);
        });

        if (group === 'internet' && gsCurrentScope === 'web' && reRender) {
            syncSearchScope('both');
            gsCurrentGroup = 'internet';
            document.querySelectorAll('#gsCategoryTabs .gs-tab').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-group') === 'internet');
            });
            executeGlobalSearch();
            return;
        }

        if (reRender && gsCachedResults.length > 0) {
            renderGlobalSearchResults();
        }
    }

    function escapeGsHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function highlightSearchWords(text, words) {
        let safe = escapeGsHtml(text);
        if (!words || !words.length) return safe;
        words.forEach(w => {
            if (!w || w.length < 2) return;
            const escapedW = w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + escapedW + ')', 'gi');
            safe = safe.replace(regex, '<mark class="gs-highlight">$1</mark>');
        });
        return safe;
    }

    function executeGlobalSearch() {
        const inp = document.getElementById('modalGlobalSearchInput');
        const q = inp.value.trim();
        const clearBtn = document.getElementById('gsClearBtn');
        const container = document.getElementById('gsResultsContainer');
        const counterEl = document.getElementById('gsSummaryCounter');

        clearBtn.style.display = q.length > 0 ? 'inline-block' : 'none';

        if (q.length < 2) {
            container.innerHTML = '<div class="gs-empty-state"><i class="fas fa-keyboard" style="font-size:2.2rem; color:#2d6a4f; margin-bottom:0.6rem;"></i><h4>Escribe al menos 2 caracteres para buscar</h4><p>Por defecto busca en <strong>moratalla-murcia.com</strong> mostrando contenidos que incluyan <strong>todas las palabras</strong>.</p></div>';
            counterEl.textContent = '';
            return;
        }

        const loadingLabel = (gsCurrentScope === 'internet')
            ? 'Buscando en Internet Global...'
            : (gsCurrentScope === 'both' ? 'Buscando en moratalla-murcia.com y en Internet Global...' : 'Buscando en la web moratalla-murcia.com...');
        container.innerHTML = '<div class="gs-empty-state"><i class="fas fa-spinner fa-spin" style="font-size:2.2rem; color:#2d6a4f; margin-bottom:0.6rem;"></i><h4>' + loadingLabel + '</h4></div>';

        const url = 'search_ajax.php?q=' + encodeURIComponent(q)
            + '&mode=' + encodeURIComponent(gsCurrentMode)
            + '&scope=' + encodeURIComponent(gsCurrentScope);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="gs-empty-state">Error al realizar la búsqueda.</div>';
                    return;
                }
                gsCachedResults = data.results || [];
                gsCachedWords = data.words || [];
                const counts = data.counts || {};

                ['all', 'noticias', 'eventos', 'asociaciones', 'artesanos', 'alojamientos', 'restauracion', 'citas', 'paginas', 'internet'].forEach(k => {
                    const badge = document.getElementById('gsCount_' + k);
                    if (badge) badge.textContent = counts[k] || 0;
                });

                const scopeLabel = (data.scope === 'internet') ? 'Internet Global' : (data.scope === 'both' ? 'Web + Internet' : 'Web moratalla-murcia');
                const modeClarification = (data.mode === 'AND') ? 'Todas las palabras' : 'Cualquiera de las palabras';
                counterEl.innerHTML = 'Encontrados <strong>' + (data.total || 0) + '</strong> resultados en <strong>' + scopeLabel + '</strong> (' + modeClarification + ')';
                renderGlobalSearchResults();
            })
            .catch(() => {
                container.innerHTML = '<div class="gs-empty-state">No se pudo conectar con el buscador en este momento.</div>';
            });
    }

    function renderGlobalSearchResults() {
        const container = document.getElementById('gsResultsContainer');
        const filtered = gsCachedResults.filter((it, idx) => {
            it._idx = idx;
            if (gsCurrentGroup === 'all') return true;
            return it.group === gsCurrentGroup;
        });

        if (filtered.length === 0) {
            container.innerHTML = '<div class="gs-empty-state">' +
                '<i class="fas fa-search-minus" style="font-size:2.2rem; color:#94a3b8; margin-bottom:0.6rem;"></i>' +
                '<h4>No hay resultados en este apartado</h4>' +
                '<p>' + (gsCurrentMode === 'AND' ? 'Prueba marcando la opción <strong>Contiene cualquiera de las palabras</strong> o seleccionando la pestaña <strong>Todos</strong>.' : 'Prueba con otras palabras o pulsa en la pestaña <strong>Todos</strong>.') + '</p>' +
                '</div>';
            return;
        }

        container.innerHTML = '<div class="gs-results-list">' + filtered.map(it => {
            let mediaHtml = '';
            if (it.image && !/\.(mp4|webm|ogg|mov|pdf)$/i.test(it.image)) {
                mediaHtml = '<img src="' + escapeGsHtml(it.image) + '" class="gs-result-thumb" alt="" onerror="this.style.display=\'none\'">';
            } else {
                let iconInner = escapeGsHtml(it.icon || '📄');
                if (/\.(svg|png|jpg|jpeg|webp|gif)$/i.test(it.icon || '')) {
                    const iconSrc = (it.icon.indexOf('uploads/') === 0) ? it.icon : 'uploads/icons/' + it.icon;
                    iconInner = '<img src="' + escapeGsHtml(iconSrc) + '" style="width:28px; height:28px; object-fit:contain;">';
                } else if ((it.icon || '').indexOf('fa-') !== -1) {
                    iconInner = '<i class="' + escapeGsHtml(it.icon) + '"></i>';
                }
                mediaHtml = '<div class="gs-result-icon-box">' + iconInner + '</div>';
            }

            const titleHighlighted = highlightSearchWords(it.title || '', gsCachedWords);
            const snippetHighlighted = highlightSearchWords(it.snippet || '', gsCachedWords);
            const subtitleHtml = it.subtitle ? '<span class="gs-result-sub">' + escapeGsHtml(it.subtitle) + '</span>' : '';

            return '<div class="gs-result-card" onclick="openGlobalSearchResult(' + it._idx + ')">' +
                mediaHtml +
                '<div class="gs-result-content">' +
                    '<div class="gs-result-meta">' +
                        '<span class="gs-result-badge">' + escapeGsHtml(it.badge || 'Contenido') + '</span>' +
                        subtitleHtml +
                    '</div>' +
                    '<h4 class="gs-result-title">' + titleHighlighted + '</h4>' +
                    (snippetHighlighted ? '<p class="gs-result-snippet">' + snippetHighlighted + '</p>' : '') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function openGlobalSearchResult(idx) {
        const it = gsCachedResults[idx];
        if (!it) return;

        if (it.news_modal && typeof openNewsModal === 'function') {
            closeGlobalSearchModal();
            openNewsModal(it.news_modal);
            return;
        }
        if (it.group === 'citas' && !it.url) {
            if (navigator.clipboard && it.snippet) {
                navigator.clipboard.writeText(it.snippet);
                alert('Cita copiada al portapapeles:\n\n' + it.snippet);
            }
            return;
        }
        if (it.url) {
            if (it.external) {
                window.open(it.url, '_blank', 'noopener');
            } else {
                window.location.href = it.url;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalInp = document.getElementById('modalGlobalSearchInput');
        if (modalInp) {
            modalInp.addEventListener('input', function() {
                clearTimeout(gsDebounceTimer);
                gsDebounceTimer = setTimeout(executeGlobalSearch, 280);
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeGlobalSearchModal();
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openGlobalSearchModal();
            }
        });

        // Si se llega con ?buscar=... abrir el cuadro automáticamente
        const params = new URLSearchParams(window.location.search);
        if (params.has('buscar')) {
            const bVal = params.get('buscar') || '';
            // Evitar interferir con el filtro local de alojamientos.php o restaurantes.php salvo que esté vacío
            const path = window.location.pathname.toLowerCase();
            if (path.indexOf('alojamientos.php') === -1 && path.indexOf('restaurantes.php') === -1) {
                openGlobalSearchModal(bVal, 'all');
            }
        }
    });
    </script>
</body>
</html>
