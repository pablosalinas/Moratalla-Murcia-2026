CREATE TABLE IF NOT EXISTS `celebrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `html_content` TEXT,
    `css_content` TEXT,
    `js_content` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `celebrations` (`name`, `is_active`, `html_content`, `css_content`, `js_content`) 
SELECT 'Mundial España 2026', 1, 
'<div id="spain-banner-container">
    <div class="spain-flag-banner">
        ESPAÑA, campeona del mundo 2026 🏆🇪🇸
    </div>
</div>',
'#spain-banner-container {
    position: fixed;
    top: 20%;
    left: 0;
    width: 100%;
    z-index: 9999;
    pointer-events: none;
    display: flex;
    justify-content: center;
    transform: translateX(-150%);
    animation: flyAcross 15s linear infinite;
}
.spain-flag-banner {
    background: linear-gradient(to bottom, #aa151b 0%, #aa151b 28%, #f1bf00 28%, #f1bf00 72%, #aa151b 72%, #aa151b 100%);
    color: white;
    font-size: clamp(1.5rem, 5vw, 3.5rem);
    font-weight: 900;
    padding: clamp(15px, 3vw, 30px) clamp(20px, 5vw, 60px);
    text-shadow: 3px 3px 6px rgba(0,0,0,0.6), -1px -1px 2px rgba(170, 21, 27, 0.8);
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    animation: waveEffect 1.5s ease-in-out infinite alternate;
    white-space: normal;
    text-align: center;
    max-width: 90vw;
}
@keyframes flyAcross {
    0% { transform: translateX(-150vw); }
    100% { transform: translateX(150vw); }
}
@keyframes waveEffect {
    0% { transform: translateY(0) rotate(-3deg); border-radius: 15px 40px 15px 40px; }
    100% { transform: translateY(-20px) rotate(3deg); border-radius: 40px 15px 40px 15px; }
}
@media (max-width: 768px) {
    #spain-banner-container { top: 30%; }
    @keyframes flyAcross {
        0% { transform: translateX(-150vw); }
        100% { transform: translateX(150vw); }
    }
}',
'if (typeof confetti === "undefined") {
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
    script.onload = runSpainConfetti;
    document.head.appendChild(script);
} else {
    runSpainConfetti();
}

function runSpainConfetti() {
    var duration = 5 * 1000;
    var animationEnd = Date.now() + duration;
    var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10000 };

    function randomInRange(min, max) { return Math.random() * (max - min) + min; }

    var interval = setInterval(function() {
      var timeLeft = animationEnd - Date.now();
      if (timeLeft <= 0) { return clearInterval(interval); }
      var particleCount = 50 * (timeLeft / duration);
      confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }, colors: ["#aa151b", "#f1bf00"] }));
      confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }, colors: ["#aa151b", "#f1bf00"] }));
    }, 250);
}'
WHERE NOT EXISTS (SELECT 1 FROM `celebrations` WHERE name = 'Mundial España 2026');
