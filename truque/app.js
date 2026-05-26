// ==========================================
// TRUQUE - GAME ENGINE AND UI HANDLER
// ==========================================

// ==========================================
// VOICE ANNOUNCEMENT ENGINE
// ==========================================

// Variables de configuración
let voiceEnabled = true;
let voiceVolume  = 1.0;
let availableVoices = [];

// Claves localStorage
const LS_VOICE_NAME    = 'truque_voice_name';
const LS_VOICE_VOLUME  = 'truque_voice_volume';
const LS_VOICE_ENABLED = 'truque_voice_enabled';

// Frases del juego — mayúsculas en sílabas tónicas para énfasis
const VOICE_LINES = {
    'envido':      ['¡ENVIDO!', '¡Tiro el ENVIDO!', '¡ENVIDO, compañero!'],
    'envido-mas':  ['¡ENVIDO más!', '¡Y yo MÁS!', '¡ENVIDO, subo!'],
    'quique':      ['¡QUINQUÉ!', '¡Cinco chinas, QUINQUÉ!', '¡Al QUINQUÉ!'],
    'falta':       ['¡La FALTA!', '¡La FALTA entera, a por TODO!', '¡La FALTA, no me tiembla el PULSO!'],
    'quiero':      ['¡QUIERO!', '¡QUIERO, venga!', '¡ACEPTO, vamos ALLÁ!'],
    'no-quiero':   ['¡NO quiero!', '¡PASO, no quiero!', '¡NO quiero, siguiente!'],
    'truco':       ['¡TRUCO!', '¡TRUCO, compañero!', '¡TRUCO, te lo CANTO!'],
    'retruco':     ['¡RETRUCO!', '¡RETRUCO, y van TRES!', '¡RETRUCO, APRIETA!'],
    'renueve':     ['¡RENUEVE!', '¡RENUEVE, subo la APUESTA!', '¡RENUEVE, nueve CHINAS!'],
    'redoce':      ['¡REDOCE!', '¡REDOCE, doce CHINAS!', '¡REDOCE, a lo GRANDE!'],
    'requince':    ['¡REQUINCE!', '¡REQUINCE, quince CHINAS!', '¡REQUINCE, esto se pone SERIO!'],
    'rejuego':     ['¡REJUEGO!', '¡Al REJUEGO, todo o NADA!', '¡REJUEGO, a FALTA entera!'],
    'gana-mano':   ['¡La mano es MÍA!', '¡BAZA ganada!', '¡Para MÍ!'],
    'pierde-mano': ['¡BAZA perdida!', '¡Te la LLEVAS!'],
    'victoria':    ['¡VICTORIA! ¡Soy el AMO!', '¡He GANADO la partida!', '¡CINCUENTA chinas, soy el REY del truque!'],
    'derrota':     ['¡MALDITA sea, has GANADO!', '¡ENHORABUENA, bien JUGADO!'],
};

function getRandomLine(key) {
    const lines = VOICE_LINES[key];
    if (!lines) return null;
    return lines[Math.floor(Math.random() * lines.length)];
}

// Devuelve solo las voces en español (incluyendo las de Microsoft)
// Devuelve todas las voces disponibles (se pueden añadir filtros si se desea)
function getVoiceList() {
    return availableVoices.sort((a, b) => a.name.localeCompare(b.name));
}

// Obtiene la voz activa: primero la guardada en LS, luego Pablo, luego primera española
function getActiveVoice() {
    const voices    = window.speechSynthesis.getVoices();  // siempre fresco
    const savedName = localStorage.getItem(LS_VOICE_NAME);

    if (savedName) {
        const found = voices.find(v => v.name === savedName);
        if (found) return found;
    }
    // Default: Pablo
    return voices.find(v => v.name === 'Microsoft Pablo - Spanish (Spain)')
        || voices.find(v => v.name.toLowerCase().includes('pablo'))
        || voices.find(v => v.lang === 'es-ES')
        || voices.find(v => v.lang.startsWith('es'))
        || voices[0]
        || null;
}

// Actualiza UI del botón toggle
function updateToggleUI() {
    const btn  = document.getElementById('btn-voice-toggle');
    const icon = document.getElementById('voice-toggle-icon');
    if (btn) {
        btn.classList.toggle('voice-off', !voiceEnabled);
        btn.title = voiceEnabled ? 'Voz activada (clic para desactivar)' : 'Voz desactivada (clic para activar)';
    }
    if (icon) icon.className = voiceEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
}

// Rellena el selector con las voces disponibles y marca la guardada
function populateVoiceSelector() {
    const sel = document.getElementById('voice-select');
    if (!sel) return;
    sel.innerHTML = '';
    const list      = getVoiceList();
    const savedName = localStorage.getItem(LS_VOICE_NAME);
    // Si no hay preferencia guardada, Pablo es el default
    const defaultName = savedName
        || availableVoices.find(v => v.name === 'Microsoft Pablo - Spanish (Spain)')?.name
        || availableVoices.find(v => v.name.toLowerCase().includes('pablo'))?.name
        || (list[0]?.name ?? '');

    list.forEach(v => {
        const opt = document.createElement('option');
        opt.value       = v.name;
        opt.textContent = `${v.name} (${v.lang})`;
        if (v.name === defaultName) opt.selected = true;
        sel.appendChild(opt);
    });
}

// Carga voces y restaura todas las preferencias
function loadVoices() {
    availableVoices = window.speechSynthesis.getVoices();
    if (!availableVoices.length) return; // aún no han cargado
    populateVoiceSelector();
    restoreVoicePreferences();
}

// Restaura volumen y estado on/off desde localStorage
function restoreVoicePreferences() {
    const savedVolume  = localStorage.getItem(LS_VOICE_VOLUME);
    const savedEnabled = localStorage.getItem(LS_VOICE_ENABLED);

    if (savedVolume !== null) {
        voiceVolume = parseFloat(savedVolume);
        const slider = document.getElementById('voice-volume-slider');
        if (slider) slider.value = voiceVolume;
        const label  = document.getElementById('voice-volume-label');
        if (label)  label.textContent = Math.round(voiceVolume * 100) + '%';
    }
    if (savedEnabled !== null) {
        voiceEnabled = savedEnabled === 'true';
        updateToggleUI();
    }
}

// ─── FUNCIÓN PRINCIPAL DE VOZ ───────────────────────────────────────────────
// CRÍTICO: Chrome ignora voz y volumen si speak() va justo tras cancel().
// El setTimeout de 50ms da tiempo al motor TTS para limpiar el estado.
function speakAnnouncement(text, options = {}) {
    if (!voiceEnabled) return;
    if (!window.speechSynthesis) return;

    window.speechSynthesis.cancel();

    setTimeout(() => {
        const voice     = getActiveVoice();   // voz fresca en cada llamada
        const utterance = new SpeechSynthesisUtterance(text);

        utterance.volume = voiceVolume;
        utterance.rate   = options.rate  ?? 1.05;
        utterance.pitch  = options.pitch ?? 1.9;  // alto = enérgico

        if (voice) {
            utterance.voice = voice;
            utterance.lang  = voice.lang; // debe coincidir con la voz para que Chrome la respete
        } else {
            utterance.lang = 'es-ES';
        }

        flashVoiceIndicator();
        window.speechSynthesis.speak(utterance);
    }, 50);
}
// ────────────────────────────────────────────────────────────────────────────

function speakAction(key) {
    const line = getRandomLine(key);
    if (line) speakAnnouncement(line);
}

function cantarMarcador() {
    const p2Name = gameMode === 'pvc' ? 'la computadora' : 'el jugador dos';
    const mitad = Math.floor(metaChinas / 2);
    
    const getScoreText = (score) => {
        if (score === 0) return null;
        if (score <= mitad) return `${score} malas`;
        return `${score - mitad} buenas`;
    };

    const p1Text = getScoreText(p1Score);
    const p2Text = getScoreText(p2Score);

    if (!p1Text && !p2Text) return; // Si ambos están a cero, no canta nada

    const t1 = p1Text ? `Jugador uno: ${p1Text}.` : 'Jugador uno a cero.';
    const t2 = p2Text ? `${p2Name}: ${p2Text}.` : `${p2Name} a cero.`;

    const text = `Marcador. ${t1} ${t2}`;
    speakAnnouncement(text, { rate: 0.95, pitch: 1.6 });
}

// Toggle on/off — persiste en localStorage
function toggleVoice() {
    voiceEnabled = !voiceEnabled;
    localStorage.setItem(LS_VOICE_ENABLED, voiceEnabled);
    updateToggleUI();
    if (voiceEnabled) speakAnnouncement('¡VOZ activada!');
}

// Cambiar voz — guarda en localStorage y prueba inmediatamente
function selectSpecificVoice(voiceName) {
    localStorage.setItem(LS_VOICE_NAME, voiceName);
    speakAnnouncement('¡TRUCO!');
}

// Cambiar volumen — guarda en localStorage y prueba con debounce
let _volTestTimer = null;
function setVoiceVolume(val) {
    voiceVolume = parseFloat(val);
    localStorage.setItem(LS_VOICE_VOLUME, voiceVolume);
    const label = document.getElementById('voice-volume-label');
    if (label) label.textContent = Math.round(voiceVolume * 100) + '%';
    clearTimeout(_volTestTimer);
    _volTestTimer = setTimeout(() => speakAnnouncement('¡TRUCO!'), 400);
}

// Flash visual en el botón Cantar
function flashVoiceIndicator() {
    const btn = document.getElementById('btn-cantar');
    if (!btn) return;
    btn.classList.add('voice-flash');
    setTimeout(() => btn.classList.remove('voice-flash'), 600);
}

// Inicialización del motor de voz
function initVoiceEngine() {
    if (!window.speechSynthesis) {
        const panel = document.getElementById('voice-panel');
        if (panel) panel.style.display = 'none';
        return;
    }
    // Las voces se cargan asíncronamente en Chrome — escuchar el evento
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }
    loadVoices(); // intento síncrono (funciona en Firefox y Safari)

    // Warmup silencioso — desbloquea el contexto de audio en Chrome
    const warmup = new SpeechSynthesisUtterance(' ');
    warmup.volume = 0;
    window.speechSynthesis.speak(warmup);
}

// ==========================================
// END VOICE ENGINE




// ==========================================
// PROTECCIÓN ANTI-COPIA
// ==========================================
(function () {
    // Bloquear copy, cut, paste y menú contextual
    ['copy', 'cut', 'paste', 'contextmenu'].forEach(evt => {
        document.addEventListener(evt, e => e.preventDefault(), true);
    });
    // Bloquear selección de texto por teclado (Ctrl+A, etc.)
    document.addEventListener('keydown', e => {
        if (e.ctrlKey && ['a','c','x','v','u','s'].includes(e.key.toLowerCase())) {
            e.preventDefault();
        }
    }, true);
    // Desactivar selección de texto via CSS
    document.documentElement.style.userSelect = 'none';
    document.documentElement.style.webkitUserSelect = 'none';
})();

// --- Game State Constants & Variables ---
let metaChinas = 50; // Meta de chinas para ganar
let deck = [];
let p1Hand = [];
let p2Hand = [];
let guiaCard = null;
let gameMode = 'pvc'; // 'pvc' (vs CPU) or 'pvp' (vs Player 2 Local)
let gameHistory = [];
let handCount = 0;

function changeMetaChinas(value) {
    metaChinas = parseInt(value);
    resetGame();
}

// Replay State
let replayHandIndex = 0;
let replayStepIndex = 0;
let replayInterval = null;
let replaySpeed = 2000;

// Scores
let p1Score = 0;
let p2Score = 0;

// Hand State
let manoPlayer = 1; // 1 or 2 (alternates each hand)
let activePlayer = 1; // whose turn it is to act/play
let enviteState = 'none'; // 'none', 'envido', 'envido-yo-tambien', 'quique', 'falta', 'accepted', 'declined', 'passed'
let enviteChinas = 0;
let enviteProposer = 0;
let enviteChinasPending = 0; // chinas in play during negotiation
let enviteChinasPrevious = 1;
let customEnvidoValue = 2;
let envitePointsCalculated = false;
let p1EnviteScore = 0;
let p2EnviteScore = 0;

// Truque State
let truqueLevel = 0; // 0 (base=1), 1 (truco=3), 2 (retruco=6), 3 (renueve=9), 4 (redoce=12), 5 (requince=15), 6 (rejuego=falta)
let truqueChinasPending = 1; // starts at 1 if no truco is called
let truqueState = 'none'; // 'none', 'truco', 'retruco', 'renueve', 'redoce', 'requince', 'rejuego', 'accepted', 'declined'
let truqueProposer = 0;

// Trick (Baza) State
let currentTrick = 0; // 0 (Primeras), 1 (Segundas), 2 (Terceras)
let p1PlayedCard = null;
let p2PlayedCard = null;
let p1PlayedTaped = false;
let p2PlayedTaped = false;
let trickWinners = []; // 1, 2, or 'parda' (tie)
let currentTrickStarter = 1;
let selectTaped = false;

// Local Multiplayer Privacy Screen State
let pvpScreenActive = false;

// --- Envite Helper ---
function isEnviteActive() {
    return (enviteState !== 'accepted' && enviteState !== 'declined' && enviteState !== 'passed') &&
           (currentTrick === 0) &&
           !(p1PlayedCard && p2PlayedCard);
}

// --- Suit SVG Generator ---
function getSuitSvg(suit) {
    if (suit === 'oro') {
        return `
        <svg viewBox="0 0 100 100" class="suit-icon">
            <circle cx="50" cy="50" r="42" fill="url(#oroGrad)" stroke="#c39b22" stroke-width="3"/>
            <circle cx="50" cy="50" r="32" fill="none" stroke="#e5a90a" stroke-width="2" stroke-dasharray="4 2"/>
            <circle cx="50" cy="50" r="12" fill="url(#oroCenterGrad)" stroke="#b78a07" stroke-width="1.5"/>
            <path d="M50,15 L50,30 M50,70 L50,85 M15,50 L30,50 M70,50 L85,50 M25,25 L36,36 M64,64 L75,75 M75,25 L64,36 M36,62 L25,75" stroke="#b78a07" stroke-width="2.5" stroke-linecap="round"/>
            <defs>
                <linearGradient id="oroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffe875" />
                    <stop offset="50%" stop-color="#f1c40f" />
                    <stop offset="100%" stop-color="#d4af37" />
                </linearGradient>
                <linearGradient id="oroCenterGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#f1c40f" />
                    <stop offset="100%" stop-color="#b78a07" />
                </linearGradient>
            </defs>
        </svg>`;
    } else if (suit === 'copa') {
        return `
        <svg viewBox="0 0 100 100" class="suit-icon">
            <path d="M25,20 C25,50 75,50 75,20 C75,17 25,17 25,20 Z" fill="url(#copaGrad)" stroke="#b33939" stroke-width="3"/>
            <path d="M25,20 L75,20" stroke="#f1c40f" stroke-width="2" fill="none"/>
            <path d="M44,45 L44,72 C44,72 50,75 56,72 L56,45 Z" fill="url(#copaStemGrad)" stroke="#b33939" stroke-width="2.5"/>
            <ellipse cx="50" cy="52" rx="14" ry="5.5" fill="#f1c40f" stroke="#d4af37" stroke-width="1.5"/>
            <path d="M28,75 C28,68 72,68 72,75 C72,83 28,83 28,75 Z" fill="url(#copaGrad)" stroke="#b33939" stroke-width="2.5"/>
            <defs>
                <linearGradient id="copaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#ffe082" />
                    <stop offset="40%" stop-color="#ffb300" />
                    <stop offset="100%" stop-color="#ff6f00" />
                </linearGradient>
                <linearGradient id="copaStemGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#ffd54f" />
                    <stop offset="100%" stop-color="#ff8f00" />
                </linearGradient>
            </defs>
        </svg>`;
    } else if (suit === 'espada') {
        return `
        <svg viewBox="0 0 100 100" class="suit-icon">
            <path d="M30,70 L87,13 C88.5,11.5 88.5,9.5 87,8 C85.5,6.5 83.5,6.5 82,8 L25,65 Z" fill="url(#steelGrad)" stroke="#2c3e50" stroke-width="2"/>
            <line x1="28" y1="67" x2="85" y2="10" stroke="#7f8c8d" stroke-width="1.5"/>
            <rect x="23" y="62" width="46" height="7" rx="3.5" fill="#f1c40f" stroke="#d4af37" stroke-width="1.5" transform="rotate(-45 46 65.5)"/>
            <rect x="30" y="69" width="8" height="18" rx="2" fill="#c0392b" stroke="#7f1d1d" stroke-width="1.5" transform="rotate(-45 34 78)"/>
            <circle cx="23" cy="89" r="6" fill="#f1c40f" stroke="#d4af37" stroke-width="1.5"/>
            <path d="M23,94 C18,97 12,100 15,102 C18,104 22,99 23,94 Z" fill="#e74c3c"/>
            <defs>
                <linearGradient id="steelGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#ffffff" />
                    <stop offset="50%" stop-color="#cbd5e1" />
                    <stop offset="100%" stop-color="#64748b" />
                </linearGradient>
            </defs>
        </svg>`;
    } else if (suit === 'basto') {
        return `
        <svg viewBox="0 0 100 100" class="suit-icon">
            <path d="M16,84 C21,88 27,85 30,81 L86,25 C89.5,21.5 88,14.5 81,16.5 L25,72 C21,75.5 12,79 16,84 Z" fill="url(#woodGrad)" stroke="#4e342e" stroke-width="2"/>
            <circle cx="38" cy="62" r="5" fill="#4e342e"/>
            <circle cx="56" cy="44" r="6" fill="#4e342e"/>
            <circle cx="74" cy="26" r="5" fill="#4e342e"/>
            <circle cx="32" cy="74" r="4" fill="#4e342e"/>
            <path d="M56,38 C50,30 57,24 61,28 C63,30 60.5,36 56,38 Z" fill="#2ecc71" stroke="#27ae60" stroke-width="1"/>
            <path d="M38,56 C32,48 39,42 43,46 C45,48 42.5,54 38,56 Z" fill="#2ecc71" stroke="#27ae60" stroke-width="1"/>
            <path d="M70,30 C76,26 80,32 76,36 C74,38 72,32 70,30 Z" fill="#2ecc71" stroke="#27ae60" stroke-width="1"/>
            <defs>
                <linearGradient id="woodGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#b5651d" />
                    <stop offset="50%" stop-color="#cd853f" />
                    <stop offset="100%" stop-color="#5c2e0b" />
                </linearGradient>
            </defs>
        </svg>`;
    }
    return '';
}

// --- Initialize Deck ---
function createDeck() {
    const suits = ['oro', 'copa', 'espada', 'basto'];
    const numbers = [1, 2, 3, 4, 5, 6, 7, 10, 11, 12]; // 10=Sota, 11=Caballo, 12=Rey
    deck = [];
    let idCounter = 0;
    
    for (const suit of suits) {
        for (const number of numbers) {
            deck.push({
                suit: suit,
                number: number,
                id: `card-${idCounter++}`
            });
        }
    }
}

// --- Shuffle Deck ---
function shuffleDeck() {
    for (let i = deck.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [deck[i], deck[j]] = [deck[j], deck[i]];
    }
}

// --- UI rendering helpers ---
function getCardLabel(number) {
    if (number === 10) return 'Sota';
    if (number === 11) return 'Cab';
    if (number === 12) return 'Rey';
    return number.toString();
}

function getSuitNameSpanish(suit) {
    if (suit === 'oro') return 'OROS';
    if (suit === 'copa') return 'COPAS';
    if (suit === 'espada') return 'ESPADAS';
    if (suit === 'basto') return 'BASTOS';
    return suit.toUpperCase();
}

function getCardNameSpanish(number, suit) {
    let name = number.toString();
    if (number === 1) name = 'As';
    else if (number === 10) name = 'Sota';
    else if (number === 11) name = 'Caballo';
    else if (number === 12) name = 'Rey';
    return `${name} de ${getSuitNameSpanish(suit)}`;
}

function getSuitSymbol(suit) {
    if (suit === 'oro') return 'O';
    if (suit === 'copa') return 'C';
    if (suit === 'espada') return 'E';
    if (suit === 'basto') return 'B';
    return '';
}

function getSuitColorClass(suit) {
    return `suit-${suit}`;
}

// --- Pinta Borders SVG Generator ---
function getPintaBorderSvg(suit) {
    if (suit === 'oro') {
        return `<svg class="card-pinta-border" viewBox="0 0 90 135"><rect x="4" y="4" width="82" height="127" fill="none" stroke="#222" stroke-width="1.5"/></svg>`;
    }
    if (suit === 'copa') {
        return `<svg class="card-pinta-border" viewBox="0 0 90 135"><path d="M41,4 L4,4 L4,131 L41,131 M49,4 L86,4 L86,131 L49,131" fill="none" stroke="#222" stroke-width="1.5"/></svg>`;
    }
    if (suit === 'espada') {
        return `<svg class="card-pinta-border" viewBox="0 0 90 135"><path d="M4,131 L4,4 L28,4 M34,4 L56,4 M62,4 L86,4 L86,131 M62,131 L86,131 M34,131 L56,131 M4,131 L28,131" fill="none" stroke="#222" stroke-width="1.5"/></svg>`;
    }
    if (suit === 'basto') {
        return `<svg class="card-pinta-border" viewBox="0 0 90 135"><path d="M4,131 L4,4 L21,4 M27,4 L42,4 M48,4 L63,4 M69,4 L86,4 L86,131 M86,131 L69,131 M63,131 L48,131 M42,131 L27,131 M21,131 L4,131" fill="none" stroke="#222" stroke-width="1.5"/></svg>`;
    }
    return '';
}

// --- Court Card Figures SVG Generator ---
function getFigureSvg(number, suit) {
    const suitSvg = getSuitSvg(suit);
    
    // Choose dynamic colors based on suit
    let primaryColor = '#2980b9'; // Blue for Espada
    let accentColor = '#e74c3c'; // Red
    
    if (suit === 'copa') {
        primaryColor = '#c0392b'; // Dark Red
        accentColor = '#f39c12'; // Orange/Gold
    } else if (suit === 'oro') {
        primaryColor = '#f1c40f'; // Yellow/Gold
        accentColor = '#27ae60'; // Green
    } else if (suit === 'basto') {
        primaryColor = '#27ae60'; // Green
        accentColor = '#8e44ad'; // Purple
    }

    if (number === 12) { // REY (King)
        let heldItem = '';
        if (suit === 'oro') {
            heldItem = `
            <circle cx="70" cy="70" r="10" fill="url(#oroGrad)" stroke="#c39b22" stroke-width="1.5"/>
            <circle cx="70" cy="70" r="7" fill="none" stroke="#e5a90a" stroke-width="1" stroke-dasharray="2 1"/>
            <circle cx="70" cy="70" r="3" fill="#f1c40f"/>
            <circle cx="70" cy="70" r="4.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'copa') {
            heldItem = `
            <path d="M62,54 C62,68 78,68 78,54 Z" fill="url(#copaGrad)" stroke="#b33939" stroke-width="1.5"/>
            <line x1="70" y1="64" x2="70" y2="74" stroke="#ffb300" stroke-width="2.5"/>
            <path d="M64,74 L76,74" stroke="#b33939" stroke-width="2"/>
            <circle cx="70" cy="70" r="4.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'espada') {
            heldItem = `
            <path d="M72,32 L76,82 L70,82 Z" fill="url(#steelGrad)" stroke="#2c3e50" stroke-width="1.5"/>
            <line x1="64" y1="78" x2="82" y2="78" stroke="#f1c40f" stroke-width="2"/>
            <circle cx="73" cy="84" r="2.5" fill="#f1c40f"/>
            <circle cx="73" cy="78" r="4.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'basto') {
            heldItem = `
            <path d="M63,46 L70,80 C70,80 67,82 64,80 L59,48 Z" fill="url(#woodGrad)" stroke="#4e342e" stroke-width="1.5"/>
            <circle cx="61" cy="56" r="2.5" fill="#2ecc71"/>
            <circle cx="65" cy="74" r="4.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        }

        return `
        <div class="figure-container rey-figure">
            <svg viewBox="0 0 100 100" class="figure-svg">
                <defs>
                    <linearGradient id="oroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffe875" /><stop offset="100%" stop-color="#d4af37" />
                    </linearGradient>
                    <linearGradient id="copaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#ffe082" /><stop offset="100%" stop-color="#ff6f00" />
                    </linearGradient>
                    <linearGradient id="steelGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#ffffff" /><stop offset="100%" stop-color="#64748b" />
                    </linearGradient>
                    <linearGradient id="woodGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#b5651d" /><stop offset="100%" stop-color="#5c2e0b" />
                    </linearGradient>
                </defs>
                <!-- Royal Robes -->
                <path d="M20,90 C20,55 35,40 50,40 C65,40 80,55 80,90 Z" fill="${primaryColor}" stroke="#1f3a60" stroke-width="2"/>
                <path d="M50,40 L50,90" stroke="#f1c40f" stroke-width="3"/>
                <!-- Royal Mantle collar -->
                <path d="M35,45 C42,52 58,52 65,45 C65,45 50,55 35,45 Z" fill="${accentColor}"/>
                <!-- King Face -->
                <circle cx="50" cy="33" r="12" fill="#fed330" stroke="#2d3436" stroke-width="2"/>
                <!-- Beard -->
                <path d="M42,38 C42,48 58,48 58,38 Z" fill="#f5f6fa" stroke="#dcdde1" stroke-width="1.5"/>
                <!-- Crown -->
                <path d="M36,23 L40,13 L50,20 L60,13 L64,23 Z" fill="#f1c40f" stroke="#d4af37" stroke-width="2"/>
                <circle cx="40" cy="12" r="1.5" fill="#e74c3c"/>
                <circle cx="50" cy="19" r="1.5" fill="#2ecc71"/>
                <circle cx="60" cy="12" r="1.5" fill="#e74c3c"/>
                <!-- Held Item -->
                ${heldItem}
            </svg>
            <div class="figure-suit-badge">${suitSvg}</div>
        </div>`;
    }
    
    if (number === 11) { // CABALLO (Knight)
        let heldItem = '';
        if (suit === 'oro') {
            heldItem = `
            <circle cx="56" cy="24" r="6" fill="url(#oroGrad)" stroke="#c39b22" stroke-width="1"/>
            <circle cx="56" cy="24" r="4.5" fill="none" stroke="#e5a90a" stroke-width="0.5" stroke-dasharray="1 1"/>
            <circle cx="50" cy="28" r="2" fill="#fed330" stroke="#2d3436" stroke-width="0.75"/>`;
        } else if (suit === 'copa') {
            heldItem = `
            <path d="M52,18 C52,25 60,25 60,18 Z" fill="url(#copaGrad)" stroke="#b33939" stroke-width="1"/>
            <line x1="56" y1="22" x2="56" y2="28" stroke="#ffb300" stroke-width="1.5"/>
            <circle cx="54" cy="28" r="2" fill="#fed330" stroke="#2d3436" stroke-width="0.75"/>`;
        } else if (suit === 'espada') {
            heldItem = `
            <path d="M54,12 L58,26 L55,26 Z" fill="url(#steelGrad)" stroke="#2c3e50" stroke-width="1"/>
            <line x1="50" y1="24" x2="60" y2="24" stroke="#f1c40f" stroke-width="1"/>
            <circle cx="55" cy="24" r="2" fill="#fed330" stroke="#2d3436" stroke-width="0.75"/>`;
        } else if (suit === 'basto') {
            heldItem = `
            <path d="M53,14 L58,26 C58,26 56,27 54,26 L51,15 Z" fill="url(#woodGrad)" stroke="#4e342e" stroke-width="1"/>
            <circle cx="52" cy="24" r="2" fill="#fed330" stroke="#2d3436" stroke-width="0.75"/>`;
        }

        return `
        <div class="figure-container caballo-figure">
            <svg viewBox="0 0 100 100" class="figure-svg">
                <defs>
                    <linearGradient id="oroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffe875" /><stop offset="100%" stop-color="#d4af37" />
                    </linearGradient>
                    <linearGradient id="copaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#ffe082" /><stop offset="100%" stop-color="#ff6f00" />
                    </linearGradient>
                    <linearGradient id="steelGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#ffffff" /><stop offset="100%" stop-color="#64748b" />
                    </linearGradient>
                    <linearGradient id="woodGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#b5651d" /><stop offset="100%" stop-color="#5c2e0b" />
                    </linearGradient>
                </defs>
                <!-- Horse body jumping -->
                <path d="M20,60 C25,45 45,40 55,42 C65,43 75,35 80,45 C82,49 76,55 74,58 C70,62 55,65 45,75 C38,82 32,85 25,80 C18,75 16,68 20,60 Z" fill="#ecf0f1" stroke="#2d3436" stroke-width="2"/>
                <!-- Horse head & Mane -->
                <path d="M60,42 C65,30 72,15 82,22 C90,28 85,42 80,45 C75,35 70,35 60,42 Z" fill="#ecf0f1" stroke="#2d3436" stroke-width="2"/>
                <!-- Saddle -->
                <path d="M42,48 C46,45 54,45 58,48 L56,58 C52,60 46,60 44,58 Z" fill="${primaryColor}" stroke="#1f3a60" stroke-width="1.5"/>
                <!-- Knight Rider sitting -->
                <path d="M40,48 C40,35 48,35 48,48 L46,56 L42,56 Z" fill="${accentColor}" stroke="#2d3436" stroke-width="1.2"/>
                <circle cx="44" cy="30" r="5" fill="#fed330" stroke="#2d3436" stroke-width="1.2"/>
                <!-- Horse legs (front) -->
                <path d="M72,55 L82,68" stroke="#2d3436" stroke-width="4" stroke-linecap="round"/>
                <path d="M76,53 L88,62" stroke="#2d3436" stroke-width="4" stroke-linecap="round"/>
                <!-- Horse legs (back) -->
                <path d="M26,75 L15,88" stroke="#2d3436" stroke-width="4" stroke-linecap="round"/>
                <!-- Eye -->
                <circle cx="78" cy="28" r="1.5" fill="#000"/>
                <!-- Held Item -->
                ${heldItem}
            </svg>
            <div class="figure-suit-badge">${suitSvg}</div>
        </div>`;
    }

    if (number === 10) { // SOTA (Jack)
        let heldItem = '';
        let shieldElement = `<circle cx="50" cy="50" r="8" fill="${accentColor}" stroke-width="1"/>`; // default shield
        if (suit === 'oro') {
            shieldElement = `
            <!-- Shield is a Gold Coin -->
            <circle cx="50" cy="50" r="11" fill="url(#oroGrad)" stroke="#c39b22" stroke-width="2"/>
            <circle cx="50" cy="50" r="8" fill="none" stroke="#e5a90a" stroke-width="1" stroke-dasharray="2 1"/>
            <circle cx="50" cy="50" r="3" fill="#f1c40f"/>`;
            heldItem = `
            <line x1="68" y1="20" x2="68" y2="95" stroke="#f1c40f" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="68" cy="18" r="3" fill="#f1c40f"/>
            <circle cx="68" cy="78" r="3.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'copa') {
            heldItem = `
            <path d="M62,35 C62,45 74,45 74,35 Z" fill="url(#copaGrad)" stroke="#b33939" stroke-width="1.2"/>
            <line x1="68" y1="42" x2="68" y2="50" stroke="#ffb300" stroke-width="2"/>
            <circle cx="68" cy="46" r="3.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'espada') {
            heldItem = `
            <path d="M67,20 L70,80 L66,80 Z" fill="url(#steelGrad)" stroke="#2c3e50" stroke-width="1.5"/>
            <line x1="61" y1="76" x2="75" y2="76" stroke="#f1c40f" stroke-width="2"/>
            <circle cx="68" cy="80" r="3.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        } else if (suit === 'basto') {
            heldItem = `
            <path d="M64,25 L70,75 C70,75 67,77 64,75 L60,28 Z" fill="url(#woodGrad)" stroke="#4e342e" stroke-width="1.5"/>
            <circle cx="62" cy="38" r="2" fill="#2ecc71"/>
            <circle cx="65" cy="70" r="3.5" fill="#fed330" stroke="#2d3436" stroke-width="1"/>`;
        }

        return `
        <div class="figure-container sota-figure">
            <svg viewBox="0 0 100 100" class="figure-svg">
                <defs>
                    <linearGradient id="oroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#ffe875" /><stop offset="100%" stop-color="#d4af37" />
                    </linearGradient>
                    <linearGradient id="copaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#ffe082" /><stop offset="100%" stop-color="#ff6f00" />
                    </linearGradient>
                    <linearGradient id="steelGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#ffffff" /><stop offset="100%" stop-color="#64748b" />
                    </linearGradient>
                    <linearGradient id="woodGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#b5651d" /><stop offset="100%" stop-color="#5c2e0b" />
                    </linearGradient>
                </defs>
                <!-- Cape -->
                <path d="M32,32 C20,40 18,80 25,90 L42,90 Z" fill="${primaryColor}" stroke="#2d3436" stroke-width="1.5"/>
                <!-- Armor / Torso -->
                <path d="M35,32 L65,32 L60,75 L40,75 Z" fill="#bdc3c7" stroke="#2d3436" stroke-width="2"/>
                <!-- Legs -->
                <rect x="42" y="75" width="6" height="20" fill="#7f8c8d" stroke="#2d3436" stroke-width="1.5"/>
                <rect x="52" y="75" width="6" height="20" fill="#7f8c8d" stroke="#2d3436" stroke-width="1.5"/>
                <!-- Shield / Guard symbol -->
                ${shieldElement}
                <!-- Head & Helmet -->
                <circle cx="50" cy="22" r="10" fill="#fed330" stroke="#2d3436" stroke-width="2"/>
                <path d="M40,18 L60,18 C60,18 55,8 50,8 C45,8 40,18 40,18 Z" fill="#7f8c8d" stroke="#2d3436" stroke-width="2"/>
                <!-- Held Item -->
                ${heldItem}
            </svg>
            <div class="figure-suit-badge">${suitSvg}</div>
        </div>`;
    }
    return '';
}

// --- Card Center HTML Generator based on Pips/Figure ---
function getCardCenterHtml(number, suit) {
    const svg = getSuitSvg(suit);
    if (number === 1) {
        return `<div class="card-grid-container grid-1">${svg}</div>`;
    }
    if (number === 2) {
        return `<div class="card-grid-container grid-2">${svg}${svg}</div>`;
    }
    if (number === 3) {
        return `<div class="card-grid-container grid-3">${svg}${svg}${svg}</div>`;
    }
    if (number === 4) {
        return `<div class="card-grid-container grid-4">${svg}${svg}${svg}${svg}</div>`;
    }
    if (number === 5) {
        return `<div class="card-grid-container grid-5">
            ${svg}${svg}${svg}${svg}
            <div class="center-pip-wrapper">${svg}</div>
        </div>`;
    }
    if (number === 6) {
        return `<div class="card-grid-container grid-6">${svg}${svg}${svg}${svg}${svg}${svg}</div>`;
    }
    if (number === 7) {
        return `<div class="card-grid-container grid-7">
            ${svg}${svg}${svg}${svg}${svg}${svg}
            <div class="center-pip-wrapper">${svg}</div>
        </div>`;
    }
    if (number === 10 || number === 11 || number === 12) {
        return getFigureSvg(number, suit);
    }
    return '';
}

function createCardElement(card, isOpponent = false, onClickHandler = null) {
    const isGuia = !isOpponent && guiaCard && card.suit === guiaCard.suit && [1, 5, 12, 11, 10].includes(card.number);
    
    const cardDiv = document.createElement('div');
    cardDiv.className = 'card';
    cardDiv.id = card.id;

    if (isOpponent) {
        cardDiv.classList.add('back');
        return cardDiv;
    }

    // Add tooltip attribute for visible cards
    cardDiv.setAttribute('data-tooltip', getCardNameSpanish(card.number, card.suit));

    if (isGuia) {
        cardDiv.classList.add('guia-card');
    }

    // Set the inner pinta border
    cardDiv.innerHTML = getPintaBorderSvg(card.suit);

    // Header corner
    const topCorner = document.createElement('div');
    topCorner.className = `card-corner top ${getSuitColorClass(card.suit)}`;
    
    const labelSpan = document.createElement('span');
    labelSpan.innerText = getCardLabel(card.number);
    topCorner.appendChild(labelSpan);
    
    cardDiv.appendChild(topCorner);

    // Center Illustration (Grid of pips or Figure)
    const centerDiv = document.createElement('div');
    centerDiv.className = 'card-center';
    centerDiv.innerHTML = getCardCenterHtml(card.number, card.suit);
    cardDiv.appendChild(centerDiv);

    // Bottom corner
    const bottomCorner = document.createElement('div');
    bottomCorner.className = `card-corner bottom ${getSuitColorClass(card.suit)}`;
    
    const labelSpan2 = document.createElement('span');
    labelSpan2.innerText = getCardLabel(card.number);
    bottomCorner.appendChild(labelSpan2);
    
    cardDiv.appendChild(bottomCorner);

    if (onClickHandler) {
        cardDiv.setAttribute('tabindex', '0');
        cardDiv.addEventListener('click', () => onClickHandler(card));
        cardDiv.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                cardDiv.click();
            }
        });
    }

    return cardDiv;
}

// --- Logger helper ---
function addLog(message, type = 'system') {
    const logBox = document.getElementById('log-messages');
    const msgDiv = document.createElement('div');
    msgDiv.className = `log-msg ${type}`;
    msgDiv.innerText = message;
    logBox.appendChild(msgDiv);
    logBox.scrollTop = logBox.scrollHeight;

    // Update Última Acción in the UI
    const ultimaAccionSpan = document.getElementById('val-ultima-accion');
    if (ultimaAccionSpan && (type === 'p1' || type === 'p2' || type === 'pvc')) {
        ultimaAccionSpan.innerText = message;
    }

    // Save to active hand history logs with state snapshot
    if (typeof gameHistory !== 'undefined' && gameHistory.length > 0) {
        const activeHand = gameHistory[gameHistory.length - 1];
        activeHand.logs.push({
            timestamp: new Date().toLocaleTimeString(),
            message: message,
            state: {
                p1Hand: p1Hand ? p1Hand.map(c => c ? { ...c } : null) : [],
                p2Hand: p2Hand ? p2Hand.map(c => c ? { ...c } : null) : [],
                p1PlayedCard: p1PlayedCard ? { ...p1PlayedCard } : null,
                p2PlayedCard: p2PlayedCard ? { ...p2PlayedCard } : null,
                p1PlayedTaped: typeof p1PlayedTaped !== 'undefined' ? p1PlayedTaped : false,
                p2PlayedTaped: typeof p2PlayedTaped !== 'undefined' ? p2PlayedTaped : false,
                trickWinners: typeof trickWinners !== 'undefined' ? [...trickWinners] : [],
                enviteState: typeof enviteState !== 'undefined' ? enviteState : 'none',
                enviteChinasPending: typeof enviteChinasPending !== 'undefined' ? enviteChinasPending : 0,
                truqueLevel: typeof truqueLevel !== 'undefined' ? truqueLevel : 0,
                truqueChinasPending: typeof truqueChinasPending !== 'undefined' ? truqueChinasPending : 1,
                activePlayer: typeof activePlayer !== 'undefined' ? activePlayer : 1
            }
        });
    }
}

// --- Initialize / Reset Round ---
function startNewHand() {
    createDeck();
    shuffleDeck();

    p1Hand = [deck.pop(), deck.pop(), deck.pop()];
    p2Hand = [deck.pop(), deck.pop(), deck.pop()];
    guiaCard = deck.pop(); // The guide card
    
    p1EnviteScore = calculateEnviteScore(p1Hand);
    p2EnviteScore = calculateEnviteScore(p2Hand);

    handCount++;
    const handInfo = {
        handNumber: handCount,
        manoPlayer: manoPlayer,
        guiaCard: { number: guiaCard.number, suit: guiaCard.suit },
        p1InitialHand: p1Hand.map(c => ({ number: c.number, suit: c.suit })),
        p2InitialHand: p2Hand.map(c => ({ number: c.number, suit: c.suit })),
        logs: [],
        finalScores: null
    };
    gameHistory.push(handInfo);

    // Reset Hand variables
    enviteState = 'none';
    enviteChinas = 0;
    enviteChinasPending = 0;
    enviteChinasPrevious = 1;
    enviteProposer = 0;
    envitePointsCalculated = false;

    truqueLevel = 0;
    truqueChinasPending = 1;
    truqueState = 'none';
    truqueProposer = 0;

    currentTrick = 0;
    p1PlayedCard = null;
    p2PlayedCard = null;
    p1PlayedTaped = false;
    p2PlayedTaped = false;
    trickWinners = [];
    currentTrickStarter = manoPlayer;
    activePlayer = currentTrickStarter;

    selectTaped = false;
    updateTaparButtonUI();

    // Reset UI
    document.getElementById('played-card-p1').innerHTML = '';
    document.getElementById('played-card-p2').innerHTML = '';
    
    // Clear played cards styling
    document.getElementById('played-card-p1').className = 'slot-card-container';
    document.getElementById('played-card-p2').className = 'slot-card-container';

    // Show Guia Card
    const guiaContainer = document.getElementById('guia-card-container');
    guiaContainer.innerHTML = '';
    guiaContainer.appendChild(createCardElement(guiaCard, false));

    // Log hand start
    addLog(`--- Nueva mano. El jugador ${manoPlayer} es MANO. ---`, 'system');
    addLog(`La carta Guía es el ${getCardNameSpanish(guiaCard.number, guiaCard.suit)}.`, 'system');

    // Setup turns
    if (gameMode === 'pvp') {
        pvpScreenActive = true;
        showPrivacyScreen(`Turno del Jugador ${activePlayer}`, `Pasa el dispositivo al Jugador ${activePlayer}. Pulsa revelar para ver tus cartas.`);
    } else {
        renderHands();
        startEnvitePhase();
    }
}

// --- Start Envite Phase ---
function startEnvitePhase() {
    enviteState = 'none';
    updateStatusBar(`Fase de Envite. Turno de Jugador ${activePlayer}.`);
    updateActionButtons();
    
    // Trigger CPU action if CPU goes first
    if (gameMode === 'pvc' && activePlayer === 2) {
        setTimeout(cpuEnviteTurn, 1500);
    }
}

// --- Calculate Envite Points for a Card ---
function getCardEnviteValue(card) {
    const isGuiaSuit = card.suit === guiaCard.suit;
    if (isGuiaSuit) {
        if (card.number === 1) return 12;
        if (card.number === 5) return 11;
        if (card.number === 12) return 10;
        if (card.number === 11) return 9;
        if (card.number === 10) return 8;
        // Remaining guia suit cards nominal
        return card.number;
    } else {
        // Normal cards
        if (card.number === 1) return 1;
        if ([10, 11, 12].includes(card.number)) return 0; // standard face cards are 0
        return card.number;
    }
}

// --- Calculate Hand Envite Score ---
function calculateEnviteScore(hand) {
    const values = hand.map(getCardEnviteValue);
    // Sort descending
    values.sort((a, b) => b - a);
    // Sum two highest values + 20
    return values[0] + values[1] + 20;
}

// --- Render Hands in DOM ---
function renderHands(hideAll = false) {
    const p1Container = document.getElementById('player-hand');
    const p2Container = document.getElementById('opponent-hand');

    p1Container.innerHTML = '';
    p2Container.innerHTML = '';

    // Player 1 Hand
    p1Hand.forEach(card => {
        if (card) {
            const isOpponentCard = (gameMode === 'pvp' && activePlayer !== 1);
            const cardEl = createCardElement(card, isOpponentCard, isOpponentCard ? null : playerPlayCard);
            // Disable click if it's not Player 1's turn or they have already played a card in this trick
            if (activePlayer !== 1 || p1PlayedCard) {
                cardEl.classList.add('disabled');
            }
            p1Container.appendChild(cardEl);
        }
    });

    // Player 2 / CPU Hand
    p2Hand.forEach(card => {
        if (card) {
            if (gameMode === 'pvc') {
                p2Container.appendChild(createCardElement(card, true));
            } else {
                // PvP Local
                const isOpponentCard = (activePlayer !== 2);
                const cardEl = createCardElement(card, isOpponentCard, isOpponentCard ? null : playerPlayCard);
                // Disable click if it's not Player 2's turn or they have already played a card in this trick
                if (activePlayer !== 2 || p2PlayedCard) {
                    cardEl.classList.add('disabled');
                }
                p2Container.appendChild(cardEl);
            }
        }
    });

    // Hide cards for privacy screen transitions
    if (hideAll) {
        p1Container.innerHTML = '<div style="font-style:italic; color:#aaa;">Cartas ocultas</div>';
        p2Container.innerHTML = '<div style="font-style:italic; color:#aaa;">Cartas ocultas</div>';
    }

    // Display envite values in helper panel (if calculated/visible)
    if (envitePointsCalculated) {
        document.getElementById('val-p1-envite-pts').innerText = p1EnviteScore;
        document.getElementById('val-p2-envite-pts').innerText = p2EnviteScore;
    } else {
        if (gameMode === 'pvc') {
            document.getElementById('val-p1-envite-pts').innerText = p1EnviteScore;
        } else {
            document.getElementById('val-p1-envite-pts').innerText = '?';
        }
        document.getElementById('val-p2-envite-pts').innerText = '?';
    }
}

// --- Update Status Message ---
function updateStatusBar(msg) {
    document.getElementById('status-message').innerText = msg;
}

// --- Toggle Tapar State ---
function toggleTapar() {
    selectTaped = !selectTaped;
    updateTaparButtonUI();
}

function updateTaparButtonUI() {
    const btn = document.getElementById('btn-tapar');
    if (selectTaped) {
        btn.innerText = "Tapar Carta: SÍ";
        btn.style.background = "linear-gradient(135deg, #7f8c8d 0%, #34495e 100%)";
    } else {
        btn.innerText = "Tapar Carta: NO";
        btn.style.background = "rgba(255,255,255,0.05)";
    }
}

// --- Update Button Visibility based on Turn & State ---
function updateActionButtons() {
    const btnEnvido = document.getElementById('btn-envido');
    const btnQuique = document.getElementById('btn-quique');
    const btnFalta = document.getElementById('btn-falta');
    const btnTruco = document.getElementById('btn-truco');
    const btnQuiero = document.getElementById('btn-quiero');
    const btnNoQuiero = document.getElementById('btn-no-quiero');
    const btnTapar = document.getElementById('btn-tapar');
    const btnMazo = document.getElementById('btn-ir-al-mazo');

    // Disable all by default
    btnEnvido.disabled = true;
    btnQuique.disabled = true;
    btnFalta.disabled = true;
    btnTruco.disabled = true;
    btnQuiero.disabled = true;
    btnNoQuiero.disabled = true;
    btnTapar.disabled = true;
    btnMazo.disabled = true;

    // Local PvP turn check: if activePlayer is not Player 1, P1 buttons are disabled.
    // However, since it's local PvP, the controls panel is shared.
    // So the active player (whether P1 or P2) uses these buttons.
    // If it's PvP and the screen is active, wait until screen is dismissed.
    if (pvpScreenActive) return;

    // Envite Phase active
    const inEnvite = isEnviteActive();
    
    if (inEnvite) {
        btnMazo.disabled = false; // can fold anytime
        
        if (enviteState === 'none') {
            // First speaker can bid (passing is done by playing a card)
            btnEnvido.disabled = false;
            btnEnvido.innerText = "Envido (2)";
            btnQuique.disabled = false;
            btnQuique.innerText = "Quinqué (5)";
            btnFalta.disabled = false;
            btnFalta.innerText = "La Falta";
            
            // Paso button is not needed, keep it disabled
            btnNoQuiero.disabled = true;
            btnNoQuiero.innerText = "No Quiero";
        } else {
            // There is a pending bet
            btnQuiero.disabled = false;
            btnNoQuiero.disabled = false;
            btnNoQuiero.innerText = "No Quiero";

            const maxScore = Math.max(p1Score, p2Score);
            const faltaChinas = (metaChinas - maxScore);

            // Allow custom raise if we haven't reached Falta yet
            if (enviteChinasPending < faltaChinas) {
                btnEnvido.disabled = false;
                btnEnvido.innerText = "Envido Más";
            }
            
            // Allow Quinqué if current bet is less than 5
            if (enviteChinasPending < 5) {
                btnQuique.disabled = false;
                btnQuique.innerText = "Quinqué (5)";
            }

            // Always allow raising to Falta (if current bet is not already Falta)
            if (enviteChinasPending < faltaChinas) {
                btnFalta.disabled = false;
                btnFalta.innerText = "La Falta";
            }
        }
    } else {
        // Truque Phase active (Envite resolved)
        // Enable Tapar card only in 2nd and 3rd round
        if (currentTrick > 0) {
            btnTapar.disabled = false;
        }

        btnMazo.disabled = false;

        // Bidding Truque (Truco, Retruco, Renueve, Redoce, Requince, Rejuego)
        if (truqueState !== 'declined') {
            const hasOpponentPosedBet = (truqueProposer !== activePlayer && ['truco', 'retruco', 'renueve', 'redoce', 'requince', 'rejuego'].includes(truqueState));
            const isAcceptedAndNotProposer = (truqueState === 'accepted' && truqueProposer !== activePlayer);
            
            if (hasOpponentPosedBet) {
                btnQuiero.disabled = false;
                btnNoQuiero.disabled = false;
                btnNoQuiero.innerText = "No Quiero";
            }

            // Can raise if no bet, answering opponent's bet, OR continuing the escalation after an accepted bet
            const canRaise = (truqueState === 'none' || hasOpponentPosedBet || isAcceptedAndNotProposer);
            if (canRaise) {
                if (truqueLevel === 0) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Truco (3)";
                } else if (truqueLevel === 1) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Retruco (6)";
                } else if (truqueLevel === 2) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Renueve (9)";
                } else if (truqueLevel === 3) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Redoce (12)";
                } else if (truqueLevel === 4) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Requince (15)";
                } else if (truqueLevel === 5) {
                    btnTruco.disabled = false;
                    btnTruco.innerText = "Rejuego (Falta)";
                }
            }
        }
    }
}

// --- Privacy Screen Handling for Local Multiplayer ---
function showPrivacyScreen(title, text) {
    renderHands(true); // Hide hands
    document.getElementById('privacy-title').innerText = title;
    document.getElementById('privacy-text').innerText = text;
    document.getElementById('privacy-screen').classList.add('active');
    updateActionButtons();
}

function revealPrivacyScreen() {
    document.getElementById('privacy-screen').classList.remove('active');
    pvpScreenActive = false;
    renderHands();
    
    const inEnvite = isEnviteActive();
    if (inEnvite) {
        updateStatusBar(`Fase de Envite. Turno de Jugador ${activePlayer}.`);
        updateActionButtons();
    } else {
        updateStatusBar(`Fase de Truque. Juega una carta Jugador ${activePlayer}.`);
        updateActionButtons();
    }
}

function switchPvPTurn(nextPlayer, actionType = 'play') {
    activePlayer = nextPlayer;
    pvpScreenActive = true;
    let message = "";
    if (actionType === 'envite') {
        message = `Responde a la apuesta o decide el Envite.`;
    } else if (actionType === 'truque_bet') {
        message = `Responde al cante de Truque.`;
    } else {
        message = `Juega una de tus cartas en la mesa.`;
    }
    showPrivacyScreen(`Turno de Jugador ${activePlayer}`, `Pasa el dispositivo al Jugador ${activePlayer}. ${message}`);
}

// --- Player Actions Handler ---
function handleAction(action) {
    if (pvpScreenActive) return;
    
    const inEnvite = isEnviteActive();
    
    if (inEnvite) {
        executeEnviteAction(activePlayer, action);
    } else {
        executeTruqueAction(activePlayer, action);
    }
}

// ==========================================
// ENVITE PHASE LOGIC
// ==========================================

function getParText(chinas) {
    if (chinas === 2) return ' - Envido';
    if (chinas === 5) return ' - Quinqué';
    if (chinas % 2 === 0) {
        const pares = chinas / 2;
        return ` - ${pares} ${pares === 1 ? 'par' : 'pares'}`;
    }
    return '';
}

function executeEnviteAction(player, action, customChinas = null) {
    if (!isEnviteActive() && action !== 'retirarse') return;

    const opponent = player === 1 ? 2 : 1;
    const opponentName = opponent === 1 ? 'Jugador 1' : (gameMode === 'pvc' ? 'Computadora' : 'Jugador 2');
    const playerName = player === 1 ? 'Jugador 1' : (gameMode === 'pvc' ? 'Computadora' : 'Jugador 2');

    // Close custom envido selector UI if active
    const selector = document.getElementById('envido-selector');
    if (selector) selector.style.display = 'none';
    const actBtns = document.querySelector('.action-buttons');
    if (actBtns) actBtns.style.display = 'flex';

    // Conversión automática a Falta si la apuesta cubre lo que le queda al líder
    const maxScore = Math.max(p1Score, p2Score);
    const faltaChinas = (metaChinas - maxScore);
    if (action === 'envido' || action === 'quique') {
        const bidVal = action === 'quique' ? 5 : (customChinas || 2);
        if (bidVal >= faltaChinas) {
            action = 'falta';
        }
    }

    if (action === 'envido') {
        const bidVal = customChinas || 2;
        if (enviteState === 'none') {
            enviteState = 'envido';
            enviteChinasPrevious = 1;
            enviteChinasPending = bidVal;
            enviteProposer = player;
            addLog(`${playerName} canta ENVIDO (${enviteChinasPending} china${enviteChinasPending === 1 ? '' : 's'}${getParText(enviteChinasPending)}).`, 'action');
            speakAnnouncement(`¡Envido! ${enviteChinasPending} china${enviteChinasPending === 1 ? '' : 's'}`);
            changeTurnEnvite(opponent);
        } else {
            enviteState = 'envido-yo-tambien';
            enviteChinasPrevious = enviteChinasPending;
            enviteChinasPending = bidVal;
            enviteProposer = player;
            addLog(`${playerName} dice ENVIDO MÁS (${enviteChinasPending} china${enviteChinasPending === 1 ? '' : 's'}${getParText(enviteChinasPending)}).`, 'action');
            speakAnnouncement(`¡Envido más! ${enviteChinasPending} chinas`);
            changeTurnEnvite(opponent);
        }
    } else if (action === 'quique') {
        const bidVal = 5;
        enviteState = 'quique';
        enviteChinasPrevious = enviteState === 'none' ? 1 : enviteChinasPending;
        enviteChinasPending = bidVal;
        enviteProposer = player;
        addLog(`${playerName} canta QUINQUÉ (5 chinas).`, 'action');
        speakAnnouncement('¡Quinqué!');
        changeTurnEnvite(opponent);
    } else if (action === 'falta') {
        enviteState = 'falta';
        const maxScore = Math.max(p1Score, p2Score);
        const faltaChinas = (metaChinas - maxScore);
        enviteChinasPrevious = enviteState === 'none' ? 1 : enviteChinasPending;
        enviteChinasPending = faltaChinas;
        enviteProposer = player;
        addLog(`${playerName} envida LA FALTA (${enviteChinasPending} chinas).`, 'action');
        speakAnnouncement('¡La falta!');
        changeTurnEnvite(opponent);
    } else if (action === 'quiero') {
        addLog(`${playerName} dice QUIERO.`, 'action');
        speakAction('quiero');
        enviteState = 'accepted';
        enviteChinas = enviteChinasPending;
        resolveEnvite();
    } else if (action === 'no-quiero') {
        if (enviteState === 'none') {
            // First player passed
            addLog(`${playerName} pasa.`, 'action');
            speakAction('no-quiero');
            if (player === manoPlayer) {
                // Opponent gets a turn to bid
                changeTurnEnvite(opponent);
            } else {
                // Both passed
                addLog(`Fase de Envite desierta.`, 'system');
                enviteState = 'passed';
                enviteChinas = 0;
                startTruquePhase();
            }
        } else {
            // Folded to a bet
            addLog(`${playerName} dice NO QUIERO.`, 'action');
            speakAction('no-quiero');
            enviteState = 'declined';
            
            // Winner gets previous bet (or 1 china if it was the initial bet)
            const wonChinas = enviteChinasPrevious;

            addLog(`El Envite se cierra. El proponente gana ${wonChinas} china${wonChinas === 1 ? '' : 's'}.`, 'system');
            awardChinas(opponent, wonChinas);
            startTruquePhase();
        }
    } else if (action === 'retirarse') {
        addLog(`${playerName} se va al mazo. Pierde el Envite y la mano.`, 'system');
        // Fold whole hand. Opponent gets 1 china from Envite + 1 from Truque
        awardChinas(opponent, 2);
        endHand();
    }
}

function changeTurnEnvite(nextPlayer) {
    if (gameMode === 'pvp') {
        switchPvPTurn(nextPlayer, 'envite');
    } else {
        activePlayer = nextPlayer;
        updateStatusBar(`Fase de Envite. Turno de ${activePlayer === 1 ? 'Jugador 1' : 'Computadora'}.`);
        updateActionButtons();
        if (activePlayer === 2) {
            setTimeout(cpuEnviteTurn, 1500);
        }
    }
}

// --- Envite Resolution ---
function resolveEnvite() {
    addLog(`El Envite ha sido aceptado por ${enviteChinas} chinas. Se resolverá al finalizar la mano.`, 'system');
    startTruquePhase();
}

// ==========================================
// TRUQUE PHASE LOGIC
// ==========================================

function startTruquePhase() {
    updateTaparButtonUI();
    // Starter of the truque is the hand's Mano
    currentTrickStarter = manoPlayer;

    // Check if someone has already played a card to pass or fold Envite
    if (p1PlayedCard || p2PlayedCard) {
        activePlayer = p1PlayedCard ? 2 : 1;
        const activeName = activePlayer === 1 ? 'Jugador 1' : (gameMode === 'pvc' ? 'la Computadora' : 'Jugador 2');
        updateStatusBar(`Fase de Truque. Juega una carta ${activeName}.`);
        renderHands();
        updateActionButtons();
        
        if (gameMode === 'pvp') {
            switchPvPTurn(activePlayer, 'play');
        } else {
            if (activePlayer === 2) {
                setTimeout(cpuTruqueTurn, 1500);
            }
        }
    } else {
        activePlayer = currentTrickStarter;

        updateStatusBar(`Fase de Truque. Juega una carta Jugador ${activePlayer}.`);
        renderHands();
        updateActionButtons();

        if (gameMode === 'pvp') {
            pvpScreenActive = true;
            showPrivacyScreen(`Turno de Jugador ${activePlayer}`, `Pasa el dispositivo. Juega tu primera carta.`);
        } else {
            if (activePlayer === 2) {
                setTimeout(cpuTruqueTurn, 1500);
            }
        }
    }
}

function executeTruqueAction(player, action) {
    const opponent = player === 1 ? 2 : 1;
    const opponentName = opponent === 1 ? 'Jugador 1' : (gameMode === 'pvc' ? 'Computadora' : 'Jugador 2');
    const playerName = player === 1 ? 'Jugador 1' : (gameMode === 'pvc' ? 'Computadora' : 'Jugador 2');

    if (action === 'truco' || action === 'escalar-truque') {
        if (truqueLevel === 0) action = 'truco';
        else if (truqueLevel === 1) action = 'retruco';
        else if (truqueLevel === 2) action = 'renueve';
        else if (truqueLevel === 3) action = 'redoce';
        else if (truqueLevel === 4) action = 'requince';
        else if (truqueLevel === 5) action = 'rejuego';
    }

    if (action === 'truco') {
        truqueLevel = 1;
        truqueChinasPending = 3;
        truqueState = 'truco';
        truqueProposer = player;
        addLog(`${playerName} canta TRUCO (3 chinas).`, 'action');
        speakAction('¡Truco!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'retruco') {
        truqueLevel = 2;
        truqueChinasPending = 6;
        truqueState = 'retruco';
        truqueProposer = player;
        addLog(`${playerName} canta RETRUCO (6 chinas).`, 'action');
        speakAction('¡Retruco!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'renueve') {
        truqueLevel = 3;
        truqueChinasPending = 9;
        truqueState = 'renueve';
        truqueProposer = player;
        addLog(`${playerName} canta RENUEVE (9 chinas).`, 'action');
        speakAction('¡Renueve!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'redoce') {
        truqueLevel = 4;
        truqueChinasPending = 12;
        truqueState = 'redoce';
        truqueProposer = player;
        addLog(`${playerName} canta REDOCE (12 chinas).`, 'action');
        speakAction('¡Redoce!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'requince') {
        truqueLevel = 5;
        truqueChinasPending = 15;
        truqueState = 'requince';
        truqueProposer = player;
        addLog(`${playerName} canta REQUINCE (15 chinas).`, 'action');
        speakAction('¡Requince!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'rejuego') {
        truqueLevel = 6;
        const maxScore = Math.max(p1Score, p2Score);
        truqueChinasPending = metaChinas - maxScore;
        truqueState = 'rejuego';
        truqueProposer = player;
        addLog(`${playerName} canta REJUEGO (Todas las chinas).`, 'action');
        speakAction('¡Rejuego!', player);
        changeTurnTruqueBet(opponent);
    } else if (action === 'quiero') {
        addLog(`${playerName} dice QUIERO al Truque. Se jugará por ${truqueChinasPending} chinas.`, 'action');
        speakAction('quiero');
        truqueState = 'accepted';
        
        // Return to normal playing turn
        activePlayer = currentTrickStarter;
        if (p1PlayedCard && activePlayer === 1) activePlayer = 2;
        if (p2PlayedCard && activePlayer === 2) activePlayer = 1;
        
        if (gameMode === 'pvp') {
            switchPvPTurn(activePlayer, 'play');
        } else {
            updateStatusBar(`Juego reanudado. Juega una carta ${activePlayer === 1 ? 'Jugador 1' : 'Computadora'}.`);
            updateActionButtons();
            if (activePlayer === 2) {
                setTimeout(cpuTruqueTurn, 1500);
            }
        }
    } else if (action === 'no-quiero') {
        // Decline Truque fold
        addLog(`${playerName} dice NO QUIERO. Se retira del Truque.`, 'action');
        speakAction('no-quiero');
        truqueState = 'declined';
        
        // Winner gets previous level's chinas
        let wonChinas = 1; // base if Truco declined
        if (truqueLevel === 2) wonChinas = 3; // declined Retruco
        if (truqueLevel === 3) wonChinas = 6; // declined Renueve
        if (truqueLevel === 4) wonChinas = 9; // declined Redoce
        if (truqueLevel === 5) wonChinas = 12; // declined Requince
        if (truqueLevel === 6) wonChinas = 15; // declined Rejuego
        
        addLog(`La mano finaliza. El proponente gana ${wonChinas} chinas.`, 'system');
        awardChinas(opponent, wonChinas);
        endHand();
    } else if (action === 'retirarse') {
        addLog(`${playerName} se retira al mazo. El rival gana la mano.`, 'system');
        awardChinas(opponent, truqueLevel > 0 ? truqueChinasPending - 2 : 1);
        endHand();
    }
}

function changeTurnTruqueBet(nextPlayer) {
    if (gameMode === 'pvp') {
        switchPvPTurn(nextPlayer, 'truque_bet');
    } else {
        activePlayer = nextPlayer;
        updateStatusBar(`Respuesta al Truque. Turno de ${activePlayer === 1 ? 'Jugador 1' : 'Computadora'}.`);
        updateActionButtons();
        if (activePlayer === 2) {
            setTimeout(cpuTruqueTurn, 1500);
        }
    }
}

// --- Physical Card Play Helper ---
function executeCardPlay(player, card) {
    if (player === 1) {
        if (p1PlayedCard) return;
        p1PlayedCard = card;
        p1PlayedTaped = selectTaped;
        p1Hand = p1Hand.filter(c => c.id !== card.id);
        
        // Show card in played slot
        const slot = document.getElementById('played-card-p1');
        slot.innerHTML = '';
        const cardEl = createCardElement(card, false);
        if (selectTaped) {
            cardEl.classList.add('taped');
        }
        slot.appendChild(cardEl);
        
        addLog(`Jugador 1 juega ${selectTaped ? 'carta tapada' : getCardNameSpanish(card.number, card.suit)}.`, 'player');
        
        // Reset Tapar state for next round
        selectTaped = false;
        updateTaparButtonUI();
    } else if (player === 2) {
        if (p2PlayedCard) return;
        p2PlayedCard = card;
        p2PlayedTaped = selectTaped;
        p2Hand = p2Hand.filter(c => c.id !== card.id);
        
        // Show card in played slot
        const slot = document.getElementById('played-card-p2');
        slot.innerHTML = '';
        const cardEl = createCardElement(card, false);
        if (selectTaped) {
            cardEl.classList.add('taped');
        }
        slot.appendChild(cardEl);
        
        const opponentLogName = gameMode === 'pvp' ? 'Jugador 2' : 'Computadora';
        addLog(`${opponentLogName} juega ${selectTaped ? 'carta tapada' : getCardNameSpanish(card.number, card.suit)}.`, 'cpu');
        
        // Reset Tapar state for next round
        selectTaped = false;
        updateTaparButtonUI();
    }
    renderHands();
}

// --- Player Card Clicking ---
function playerPlayCard(card) {
    if (pvpScreenActive) return;
    
    const inEnvite = isEnviteActive();
    const player = activePlayer;
    const opponent = player === 1 ? 2 : 1;
    const playerName = player === 1 ? 'Jugador 1' : (gameMode === 'pvp' ? 'Jugador 2' : 'Computadora');
    const opponentName = opponent === 1 ? 'Jugador 1' : (gameMode === 'pvp' ? 'Jugador 2' : 'Computadora');

    if (inEnvite) {
        if (enviteState === 'none') {
            // Implicit pass
            addLog(`${playerName} pasa el Envite (al jugar carta).`, 'action');
            if (player === manoPlayer) {
                // Opponent still has voice to bid
                executeCardPlay(player, card);
                changeTurnEnvite(opponent);
                return;
            } else {
                // Both have passed, so Envite is passed (desierta)
                addLog(`Fase de Envite desierta.`, 'system');
                enviteState = 'passed';
                enviteChinas = 0;
                executeCardPlay(player, card);
                checkTrickFinished();
                return;
            }
        } else {
            // Implicit fold (No Quiero to the pending bet)
            addLog(`${playerName} dice NO QUIERO (al jugar carta) al Envite.`, 'action');
            enviteState = 'declined';
            
            // Winner gets previous bet (or 1 china if it was the initial bet)
            const wonChinas = enviteChinasPrevious;
            addLog(`El Envite se cierra. ${opponentName} gana ${wonChinas} china${wonChinas === 1 ? '' : 's'}.`, 'system');
            
            executeCardPlay(player, card);
            awardChinas(opponent, wonChinas);
            
            // If the game didn't end (which checkTrickFinished might be needed if it continues)
            if (p1Score < metaChinas && p2Score < metaChinas) {
                checkTrickFinished();
            }
            return;
        }
    }

    // Normal play (Envite resolved)
    executeCardPlay(player, card);
    checkTrickFinished();
}

// --- Get Card Power for Truque ---
function getCardPower(card) {
    if (!card) return -1;
    
    const isGuiaSuit = card.suit === guiaCard.suit;
    if (isGuiaSuit) {
        if (card.number === 1) return 40;  // As de guía
        if (card.number === 5) return 39;  // 5 de guía
        if (card.number === 12) return 38; // Rey de guía
        if (card.number === 11) return 37; // Caballo de guía
        if (card.number === 10) return 36; // Sota de guía
    }
    
    // Special high cards
    if (card.number === 1 && card.suit === 'espada') return 35; // As de espadas
    if (card.number === 1 && card.suit === 'basto') return 34;  // As de bastos
    if (card.number === 7 && card.suit === 'espada') return 33; // 7 de espadas
    if (card.number === 7 && card.suit === 'oro') return 32;    // 7 de oros
    
    // Normals
    if (card.number === 3) return 31;
    if (card.number === 2) return 30;
    if (card.number === 1) return 29; // As of Copas/Oros
    if (card.number === 12) return 28; // Other kings
    if (card.number === 11) return 27; // Other horses
    if (card.number === 10) return 26; // Other jacks
    if (card.number === 7) return Math.floor(metaChinas / 2);  // Other sevens
    if (card.number === 6) return 24;
    if (card.number === 5) return 23;
    if (card.number === 4) return 22;

    return 0;
}

// --- Check Trick Conclusion ---
function checkTrickFinished() {
    if (p1PlayedCard && p2PlayedCard) {
        // Both played, evaluate trick winner
        setTimeout(resolveTrick, 1500);
    } else {
        // Move to the next player
        const next = activePlayer === 1 ? 2 : 1;
        if (gameMode === 'pvp') {
            switchPvPTurn(next, 'play');
        } else {
            activePlayer = next;
            updateStatusBar(`Fase de Truque. Juega una carta ${activePlayer === 1 ? 'Jugador 1' : 'Computadora'}.`);
            renderHands();
            updateActionButtons();
            if (activePlayer === 2) {
                setTimeout(cpuTruqueTurn, 1500);
            }
        }
    }
}

function resolveTrick() {
    const pow1 = p1PlayedTaped ? -1 : getCardPower(p1PlayedCard);
    const pow2 = p2PlayedTaped ? -1 : getCardPower(p2PlayedCard);

    let winner = 'parda';
    if (pow1 > pow2) {
        winner = 1;
    } else if (pow2 > pow1) {
        winner = 2;
    }

    trickWinners.push(winner);
    
    // Log winner
    const trickNames = ["PRIMERAS", "SEGUNDAS", "TERCERAS"];
    const p2Name = gameMode === 'pvc' ? 'La Computadora' : 'Jugador 2';
    
    if (winner === 'parda') {
        addLog(`La ronda de ${trickNames[currentTrick]} queda PARDA (empate).`, 'system');
    } else {
        const winName = winner === 1 ? 'Jugador 1' : p2Name;
        addLog(`¡${winName} gana la ronda de ${trickNames[currentTrick]}!`, 'system');
    }

    // Update sub scoreboard
    document.getElementById('val-p1-bazas').innerText = trickWinners.filter(w => w === 1).length;
    document.getElementById('val-p2-bazas').innerText = trickWinners.filter(w => w === 2).length;

    // Reset played cards for UI
    p1PlayedCard = null;
    p2PlayedCard = null;
    p1PlayedTaped = false;
    p2PlayedTaped = false;

    document.getElementById('played-card-p1').innerHTML = '';
    document.getElementById('played-card-p2').innerHTML = '';

    // Check hand winner
    checkHandWinner(winner);
}

function checkHandWinner(trickWinner) {
    const w1 = trickWinners.filter(w => w === 1).length;
    const w2 = trickWinners.filter(w => w === 2).length;
    const pardas = trickWinners.filter(w => w === 'parda').length;

    const p2Name = gameMode === 'pvc' ? 'La Computadora' : 'Jugador 2';

    // 1. If someone wins 2 tricks, they win the Truque
    if (w1 === 2) {
        addLog(`¡Jugador 1 gana el Truque de la mano por bazas (${w1} de 3)!`, 'system');
        awardChinas(1, truqueChinasPending);
        endHand();
        return;
    }
    if (w2 === 2) {
        addLog(`¡${p2Name} gana el Truque de la mano por bazas (${w2} de 3)!`, 'system');
        awardChinas(2, truqueChinasPending);
        endHand();
        return;
    }

    // 2. Tie in "Primeras" (pardas) -> skip "Segundas", go straight to "Terceras"
    if (currentTrick === 0 && trickWinner === 'parda') {
        addLog(`Al quedar pardas en PRIMERAS, se pasa directamente a TERCERAS para resolver la mano.`, 'system');
        currentTrick = 2; // Jump to trick 3
        currentTrickStarter = manoPlayer; // Mano starts
        activePlayer = currentTrickStarter;
    } else {
        // Normal progression
        currentTrick++;
        // Winner of trick starts the next trick. If it was parda, starter of previous trick starts again.
        if (trickWinner !== 'parda') {
            currentTrickStarter = trickWinner;
        }
        activePlayer = currentTrickStarter;
    }

    // Check if we reached the end of 3 tricks
    if (currentTrick >= 3) {
        // Decide winner based on overall state
        // This only executes if we played 3 tricks or had pardas at some point and finished
        let finalWinner = 0;
        
        if (w1 > w2) {
            finalWinner = 1;
        } else if (w2 > w1) {
            finalWinner = 2;
        } else {
            // Equal tricks (e.g. 1 win each and a parda, or all pardas)
            // Rules:
            // - If first was won by A, second by B, and third is parda -> A wins (winner of first wins)
            // - If first was parda, second won by A, third won by B -> winner of second wins
            // - If all pardas -> Mano wins
            const firstTrick = trickWinners[0];
            const secondTrick = trickWinners[1];
            const thirdTrick = trickWinners[2];

            if (firstTrick !== 'parda') {
                finalWinner = firstTrick; // Winner of first wins
            } else if (secondTrick && secondTrick !== 'parda') {
                finalWinner = secondTrick;
            } else if (thirdTrick && thirdTrick !== 'parda') {
                finalWinner = thirdTrick;
            } else {
                finalWinner = manoPlayer; // all pardas -> Mano wins
                addLog(`Todas las rondas quedaron pardas. Gana el jugador por ser MANO.`, 'system');
            }
        }

        const winName = finalWinner === 1 ? 'Jugador 1' : p2Name;
        addLog(`¡${winName} gana el Truque de la mano con ${truqueChinasPending} chinas!`, 'system');
        awardChinas(finalWinner, truqueChinasPending);
        endHand();
    } else {
        // Next Trick Turn Setup
        updateStatusBar(`Ronda de ${currentTrick === 1 ? 'Segundas' : 'Terceras'}. Turno de Jugador ${activePlayer}.`);
        if (gameMode === 'pvp') {
            switchPvPTurn(activePlayer, 'play');
        } else {
            renderHands();
            updateActionButtons();
            if (activePlayer === 2) {
                setTimeout(cpuTruqueTurn, 1500);
            }
        }
    }
}

// ==========================================
// POINT AWARDING & END OF HAND LOGIC
// ==========================================

function awardChinas(player, amount) {
    if (player === 1) {
        p1Score += amount;
        if (p1Score > metaChinas) p1Score = metaChinas;
    } else {
        p2Score += amount;
        if (p2Score > metaChinas) p2Score = metaChinas;
    }

    updateScoreboardUI();

    // Check Win Condition
    if (p1Score >= metaChinas) {
        showGameOverModal('¡Victoria de Jugador 1!', '🏆', `Has logrado vencer alcanzando las ${metaChinas} chinas.`);
        speakAnnouncement(`¡Fin del juego! ¡Has ganado la partida con ${metaChinas} chinas!`);
    } else if (p2Score >= metaChinas) {
        const p2Name = gameMode === 'pvc' ? 'La Computadora' : 'Jugador 2';
        showGameOverModal(`¡Victoria de ${p2Name}!`, '💀', `El oponente ha ganado la partida con ${metaChinas} chinas.`);
        speakAnnouncement(`¡Fin del juego! ${p2Name} ha ganado la partida con ${metaChinas} chinas.`);
    }
}

function updateScoreboardUI() {
    document.getElementById('val-p1-score').innerText = p1Score;
    document.getElementById('val-p2-score').innerText = p2Score;

    // Split scores into Malas (0-25) and Buenas (25-50)
    const p1Malas = Math.min(Math.floor(metaChinas / 2), p1Score);
    const p1Buenas = Math.max(0, p1Score - Math.floor(metaChinas / 2));
    const p2Malas = Math.min(Math.floor(metaChinas / 2), p2Score);
    const p2Buenas = Math.max(0, p2Score - Math.floor(metaChinas / 2));

    document.getElementById('txt-p1-malas').innerText = p1Malas;
    document.getElementById('txt-p1-buenas').innerText = p1Buenas;
    document.getElementById('txt-p2-malas').innerText = p2Malas;
    document.getElementById('txt-p2-buenas').innerText = p2Buenas;

    // Fill Dots UI
    fillDotTrack('track-p1-malas', p1Malas, 'active-p1');
    fillDotTrack('track-p1-buenas', p1Buenas, 'active-p1');
    fillDotTrack('track-p2-malas', p2Malas, 'active-p2');
    fillDotTrack('track-p2-buenas', p2Buenas, 'active-p2');
}

function fillDotTrack(trackId, activeCount, activeClass) {
    const track = document.getElementById(trackId);
    track.innerHTML = '';
    for (let i = 0; i < Math.floor(metaChinas / 2); i++) {
        const dot = document.createElement('div');
        dot.className = 'china-dot';
        if (i < activeCount) {
            dot.classList.add(activeClass);
        }
        track.appendChild(dot);
    }
}

function endHand() {
    // Record final scores of the hand for history
    if (typeof gameHistory !== 'undefined' && gameHistory.length > 0) {
        gameHistory[gameHistory.length - 1].finalScores = {
            p1Score: p1Score,
            p2Score: p2Score
        };
    }

    // If the Envite was accepted, resolve it now!
    if (enviteState === 'accepted') {
        envitePointsCalculated = true;
        renderHands();

        const p1Pts = p1EnviteScore;
        const p2Pts = p2EnviteScore;
        const p2Name = gameMode === 'pvc' ? 'La Computadora' : 'Jugador 2';
        
        addLog(`--- Resolución del Envite ---`, 'system');
        addLog(`Jugador 1 tiene ${p1Pts} puntos de Envite.`, 'player');
        addLog(`${p2Name} tiene ${p2Pts} puntos de Envite.`, 'cpu');

        let winner = 0;
        if (p1Pts > p2Pts) {
            winner = 1;
        } else if (p2Pts > p1Pts) {
            winner = 2;
        } else {
            // Tie goes to Mano
            winner = manoPlayer;
            addLog(`¡Empate de puntos! Gana el jugador por ser MANO.`, 'system');
        }

        const winnerName = winner === 1 ? 'Jugador 1' : p2Name;
        addLog(`¡${winnerName} gana el Envite con ${winner === 1 ? p1Pts : p2Pts} puntos y se lleva ${enviteChinas} chinas!`, 'system');
        
        awardChinas(winner, enviteChinas);
        
        // We set enviteState to resolved so we don't resolve it again
        enviteState = 'resolved';
    }

    // Check if game is over
    if (p1Score >= metaChinas || p2Score >= metaChinas) return;

    // Alternate Mano for next hand
    manoPlayer = manoPlayer === 1 ? 2 : 1;
    
    // Clear bazas indicators
    document.getElementById('val-p1-bazas').innerText = '0';
    document.getElementById('val-p2-bazas').innerText = '0';

    updateStatusBar(`Fin de la mano. Repartiendo siguiente mano...`);
    setTimeout(startNewHand, 3000);
}

// ==========================================
// COMPUTER AI DECISION ENGINE
// ==========================================

function cpuEnviteTurn() {
    if (enviteState === 'accepted' || enviteState === 'declined' || enviteState === 'passed') return;

    const cpuPts = p2EnviteScore;
    const maxScore = Math.max(p1Score, p2Score);
    const faltaChinas = (metaChinas - maxScore);
    
    // AI Strategy parameters
    const random = Math.random();
    
    if (enviteState === 'none') {
        if (cpuPts >= 38) {
            // Excellent score, bid custom Envido, Quinqué or Falta
            if (cpuPts >= 41 && random > 0.4) {
                executeEnviteAction(2, 'falta');
            } else if (random > 0.7 && faltaChinas >= 4) {
                executeEnviteAction(2, 'envido', 4); // 2 pares
            } else if (random > 0.4 && faltaChinas >= 5) {
                executeEnviteAction(2, 'quique'); // Quinqué (5)
            } else {
                executeEnviteAction(2, 'envido', 2); // Envido (2)
            }
        } else if (cpuPts >= 33 && random > 0.6) {
            // Good score, bid Envido
            executeEnviteAction(2, 'envido', 2);
        } else if (random > 0.92) {
            // Bluff Envido (8% chance)
            addLog("La Computadora decide meter un embuste...", 'system');
            executeEnviteAction(2, 'envido', 2);
        } else {
            // Pass Envite by playing a card
            p2Hand.sort((a,b) => getCardPower(b) - getCardPower(a));
            const cardToPlay = p2Hand[0];
            playerPlayCard(cardToPlay);
        }
    } else {
        // Player has made a bet. CPU must accept, fold, or raise.
        const pending = enviteChinasPending;

        if (cpuPts >= 38) {
            // Accept always, raise sometimes
            if (pending < faltaChinas && random > 0.5) {
                const raiseVal = Math.min(pending + 2, faltaChinas);
                executeEnviteAction(2, 'envido', raiseVal);
            } else {
                executeEnviteAction(2, 'quiero');
            }
        } else if (cpuPts >= 33) {
            // Medium-high, accept small bets, fold to large bets
            if (pending > 4) {
                if (pending === faltaChinas) {
                    if (random > 0.85) executeEnviteAction(2, 'quiero');
                    else executeEnviteAction(2, 'no-quiero');
                } else {
                    if (random > 0.7) executeEnviteAction(2, 'quiero');
                    else executeEnviteAction(2, 'no-quiero');
                }
            } else {
                executeEnviteAction(2, 'quiero');
            }
        } else {
            // Low score. Mostly fold, very rare bluff raise
            if (pending <= 2 && random > 0.96) {
                const raiseVal = Math.min(pending + 2, faltaChinas);
                executeEnviteAction(2, 'envido', raiseVal); // Bluff raise
            } else {
                executeEnviteAction(2, 'no-quiero');
            }
        }
    }
}

function cpuTruqueTurn() {
    // Ensure Envite is resolved
    const inEnvite = isEnviteActive();
    if (inEnvite) return;

    if (truqueState === 'declined') return;

    // AI is asked to make a Truque bet response
    const hasOpponentPosedBet = (truqueProposer === 1 && ['truco', 'retruco', 'renueve', 'redoce', 'requince', 'rejuego'].includes(truqueState));
    
    // Evaluate Hand Strength for Truque
    const cpuCardPowers = p2Hand.map(getCardPower);
    cpuCardPowers.sort((a,b) => b - a);
    const bestCpuPower = cpuCardPowers[0] || -1;
    const avgCpuPower = cpuCardPowers.reduce((a,b) => a+b, 0) / (p2Hand.length || 1);

    const random = Math.random();

    if (hasOpponentPosedBet) {
        // Opponent (Player 1) has bid Truco or similar. CPU must Quiero, No Quiero, or Raise.
        // Power reference: Max power is 40 (As Guía), high standard is 35 (As Espadas), average is around 26-28.
        if (bestCpuPower >= 35 || (avgCpuPower >= 29 && p2Hand.length >= 2)) {
            // Strong hand
            if (bestCpuPower >= 38 && random > 0.5) {
                // Raise to next level!
                raiseTruqueLevelCpu();
            } else {
                executeTruqueAction(2, 'quiero');
            }
        } else if (avgCpuPower >= 26) {
            // Medium hand. Accept Truco, fold to high levels.
            if (truqueLevel === 1) {
                executeTruqueAction(2, 'quiero');
            } else {
                if (random > 0.7) executeTruqueAction(2, 'quiero');
                else executeTruqueAction(2, 'no-quiero');
            }
        } else {
            // Weak hand. Fold, or very rare bluff
            if (random > 0.94) {
                executeTruqueAction(2, 'quiero');
            } else {
                executeTruqueAction(2, 'no-quiero');
            }
        }
        return;
    }

    // CPU is playing a card or singing Truco
    // If CPU has extremely good cards and hasn't called Truco yet, it might call it!
    if (truqueState === 'none' && truqueLevel === 0 && bestCpuPower >= 35 && random > 0.4) {
        executeTruqueAction(2, 'truco');
        return;
    }
    // High level raises
    if (truqueState === 'accepted' && truqueProposer === 1 && bestCpuPower >= 37 && random > 0.6) {
        raiseTruqueLevelCpu();
        return;
    }

    // Actually play a card
    let cardToPlay = null;
    let tapeIt = false;

    // CPU plays card based on whether player has already played in this trick
    if (p1PlayedCard) {
        // Player has played. CPU knows what card to beat.
        const targetPower = p1PlayedTaped ? -1 : getCardPower(p1PlayedCard);
        
        // Find if we have cards that can beat targetPower
        const winningCards = p2Hand.filter(c => getCardPower(c) > targetPower);
        const losingCards = p2Hand.filter(c => getCardPower(c) <= targetPower);

        if (winningCards.length > 0) {
            // We can win. Strategy: play the smallest winning card to conserve high cards
            winningCards.sort((a,b) => getCardPower(a) - getCardPower(b));
            cardToPlay = winningCards[0];
        } else {
            // We cannot win. Strategy: discard our lowest card (throw it away)
            p2Hand.sort((a,b) => getCardPower(a) - getCardPower(b));
            cardToPlay = p2Hand[0];
            
            // In 2nd or 3rd round, CPU might "tapar" (play face down) a losing card to hide information
            if (currentTrick > 0 && random > 0.4) {
                tapeIt = true;
            }
        }
    } else {
        // CPU plays first. Strategy:
        if (currentTrick === 0) {
            // First round (Primeras) - play medium strength card or highest card to grab control
            p2Hand.sort((a,b) => getCardPower(b) - getCardPower(a));
            cardToPlay = p2Hand[0]; // Play highest to win first round
        } else {
            // Play lowest card or tap it
            p2Hand.sort((a,b) => getCardPower(a) - getCardPower(b));
            cardToPlay = p2Hand[0];
            if (random > 0.5) tapeIt = true;
        }
    }

    // Play the card in DOM and state
    if (cardToPlay) {
        p2PlayedCard = cardToPlay;
        p2PlayedTaped = tapeIt;
        p2Hand = p2Hand.filter(c => c.id !== cardToPlay.id);
        
        const slot = document.getElementById('played-card-p2');
        slot.innerHTML = '';
        const cardEl = createCardElement(cardToPlay, false); // CPU card is revealed when played
        if (tapeIt) {
            cardEl.classList.add('taped');
        }
        slot.appendChild(cardEl);
        
        addLog(`La Computadora juega ${tapeIt ? 'carta tapada' : getCardNameSpanish(cardToPlay.number, cardToPlay.suit)}.`, 'cpu');

        checkTrickFinished();
    }
}

function raiseTruqueLevelCpu() {
    if (truqueLevel === 0) executeTruqueAction(2, 'truco');
    else if (truqueLevel === 1) executeTruqueAction(2, 'retruco');
    else if (truqueLevel === 2) executeTruqueAction(2, 'renueve');
    else if (truqueLevel === 3) executeTruqueAction(2, 'redoce');
    else if (truqueLevel === 4) executeTruqueAction(2, 'requince');
    else if (truqueLevel === 5) executeTruqueAction(2, 'rejuego');
}

// ==========================================
// GAME STATE MANAGEMENT (MODALS & SETUP)
// ==========================================

function changeGameMode(mode) {
    gameMode = mode;
    
    // Update labels in HTML
    const nameOpponent = document.getElementById('lbl-opponent-name');
    const labelPlayedP2 = document.getElementById('lbl-played-p2');
    const lblP2Score = document.getElementById('lbl-p2-score');
    
    const lblP2MalasTitle = document.getElementById('lbl-p2-malas-title');
    const lblP2BuenasTitle = document.getElementById('lbl-p2-buenas-title');
    const lblP2NameBazas = document.getElementById('lbl-p2-name-bazas');

    if (mode === 'pvp') {
        nameOpponent.innerText = "Jugador 2";
        labelPlayedP2.innerText = "Jugador 2";
        lblP2Score.innerText = "Jugador 2";
        lblP2MalasTitle.innerText = "MALAS J2 (0-${Math.floor(metaChinas/2)})";
        lblP2BuenasTitle.innerText = "BUENAS J2 (${Math.floor(metaChinas/2)}-${metaChinas})";
        lblP2NameBazas.innerText = "Jugador 2";
    } else {
        nameOpponent.innerText = "Computadora";
        labelPlayedP2.innerText = "CPU";
        lblP2Score.innerText = "Computadora";
        lblP2MalasTitle.innerText = "MALAS CPU (0-${Math.floor(metaChinas/2)})";
        lblP2BuenasTitle.innerText = "BUENAS CPU (${Math.floor(metaChinas/2)}-${metaChinas})";
        lblP2NameBazas.innerText = "P2/CPU";
    }

    addLog(`Modo de juego cambiado a: ${mode === 'pvp' ? 'Jugador contra Jugador' : 'Jugador contra Computadora'}.`, 'system');
    resetGame(true);
}

function resetGame(fullReset = false) {
    if (fullReset) {
        p1Score = 0;
        p2Score = 0;
        manoPlayer = 1;
        gameHistory = [];
        handCount = 0;
        
        // Reset logs
        document.getElementById('log-messages').innerHTML = '<div class="log-msg system">Nueva partida de Truque iniciada.</div>';
    }

    document.getElementById('modal-game-over').classList.remove('active');
    
    updateScoreboardUI();
    startNewHand();
}

// --- Modals Controls ---
function openRulesModal() {
    document.getElementById('modal-rules').classList.add('active');
}

function closeRulesModal() {
    document.getElementById('modal-rules').classList.remove('active');
}

function showGameOverModal(title, icon, desc) {
    document.getElementById('game-over-icon').innerText = icon;
    document.getElementById('game-over-title').innerText = title;
    document.getElementById('game-over-desc').innerText = desc;
    document.getElementById('modal-game-over').classList.add('active');
    
    // Reproducir himno de Freddie Mercury si está disponible
    try {
        const anthem = new Audio('champions.mp3');
        if (typeof voiceVolume !== 'undefined') anthem.volume = voiceVolume;
        anthem.play().catch(e => console.log('Himno no reproducido:', e));
    } catch (e) {
        console.log('Error al intentar reproducir el himno:', e);
    }
}

// --- Custom Envido Selector Handlers ---
function openEnvidoSelector() {
    const maxScore = Math.max(p1Score, p2Score);
    const faltaChinas = (metaChinas - maxScore);

    // Initial value:
    // If no bet: start at 2
    // If raising: start at enviteChinasPending + 2 (but cap at La Falta)
    if (enviteState === 'none') {
        customEnvidoValue = 2;
    } else {
        customEnvidoValue = Math.min(enviteChinasPending + 2, faltaChinas);
        if (customEnvidoValue <= enviteChinasPending) {
            customEnvidoValue = faltaChinas;
        }
    }

    document.getElementById('envido-max-val').innerText = faltaChinas;
    
    // Disable chips that are below the minimum allowed
    const minVal = enviteState === 'none' ? 2 : (enviteChinasPending + 1);
    document.querySelectorAll('.envido-chip').forEach(chip => {
        const v = parseInt(chip.dataset.val);
        chip.disabled = (v < minVal || v > faltaChinas);
    });
    
    // Hide action buttons, show custom selector
    document.querySelector('.action-buttons').style.display = 'none';
    document.getElementById('envido-selector').style.display = 'flex';
    
    updateCustomEnvidoUI();
}

function setEnvidoChinas(val) {
    const maxScore = Math.max(p1Score, p2Score);
    const faltaChinas = (metaChinas - maxScore);
    const minVal = enviteState === 'none' ? 2 : (enviteChinasPending + 1);
    customEnvidoValue = Math.min(Math.max(val, minVal), faltaChinas);
    updateCustomEnvidoUI();
}

function adjustEnvidoChinas(diff) {
    const maxScore = Math.max(p1Score, p2Score);
    const faltaChinas = (metaChinas - maxScore);
    
    const minVal = enviteState === 'none' ? 2 : (enviteChinasPending + 1);
    
    let newVal = customEnvidoValue + diff;
    if (newVal < minVal) newVal = minVal;
    if (newVal > faltaChinas) newVal = faltaChinas;
    
    customEnvidoValue = newVal;
    updateCustomEnvidoUI();
}

function updateCustomEnvidoUI() {
    // Update number display
    const numEl = document.getElementById('envido-chinas-num');
    const labelEl = document.getElementById('envido-selected-chinas');
    if (numEl) numEl.innerText = customEnvidoValue;
    
    let label = `china${customEnvidoValue === 1 ? '' : 's'}`;
    if (customEnvidoValue === 2) label = 'chinas (Envido)';
    else if (customEnvidoValue === 5) label = 'chinas (Quinqué)';
    else if (customEnvidoValue % 2 === 0) {
        const pares = customEnvidoValue / 2;
        label = `chinas (${pares} ${pares === 1 ? 'par' : 'pares'})`;
    }
    if (labelEl) labelEl.innerText = label;
    
    // Highlight active chip
    document.querySelectorAll('.envido-chip').forEach(chip => {
        chip.classList.toggle('active', parseInt(chip.dataset.val) === customEnvidoValue);
    });
}

function confirmCustomEnvido() {
    // Hide custom selector, show action buttons
    document.getElementById('envido-selector').style.display = 'none';
    document.querySelector('.action-buttons').style.display = 'flex';
    
    // Call the original action handler with the custom value!
    executeEnviteAction(activePlayer, 'envido', customEnvidoValue);
}

function cancelCustomEnvido() {
    // Hide custom selector, show action buttons
    document.getElementById('envido-selector').style.display = 'none';
    document.querySelector('.action-buttons').style.display = 'flex';
}

// speakAnnouncement y cantarMarcador definidas en el bloque VOICE ENGINE (inicio del archivo)



// --- Download Game History Report ---
function downloadGameHistory() {
    if (gameHistory.length === 0) {
        alert('No hay historial de partida registrado aún.');
        return;
    }

    let text = `==================================================\n`;
    text += `HISTORIAL DE LA PARTIDA DE TRUQUE\n`;
    text += `Fecha: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}\n`;
    text += `Modo de juego: ${gameMode === 'pvc' ? 'Jugador vs Computadora' : 'Jugador vs Jugador'}\n`;
    text += `Puntuación Final: Jugador 1: ${p1Score} | ${gameMode === 'pvc' ? 'Computadora' : 'Jugador 2'}: ${p2Score}\n`;
    text += `==================================================\n\n`;

    gameHistory.forEach(hand => {
        text += `--------------------------------------------------\n`;
        text += `MANO Nº ${hand.handNumber}\n`;
        text += `--------------------------------------------------\n`;
        text += `• Dador/Mano: El Jugador ${hand.manoPlayer} es MANO.\n`;
        text += `• Carta Guía: ${getCardNameSpanish(hand.guiaCard.number, hand.guiaCard.suit)}\n`;
        
        const p1Cards = hand.p1InitialHand.map(c => `${getCardNameSpanish(c.number, c.suit)}`).join(', ');
        text += `• Cartas Jugador 1: ${p1Cards}\n`;
        
        const p2Label = gameMode === 'pvc' ? 'Computadora' : 'Jugador 2';
        const p2Cards = hand.p2InitialHand.map(c => `${getCardNameSpanish(c.number, c.suit)}`).join(', ');
        text += `• Cartas ${p2Label}: ${p2Cards}\n\n`;
        
        text += `Desarrollo de la mano:\n`;
        hand.logs.forEach(log => {
            text += `  [${log.timestamp}] ${log.message}\n`;
        });
        
        if (hand.finalScores) {
            text += `\n• Puntuación al finalizar la mano: Jugador 1: ${hand.finalScores.p1Score} | ${p2Label}: ${hand.finalScores.p2Score}\n`;
        }
        text += `\n`;
    });

    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `historial_truque_${new Date().toISOString().slice(0,10)}_${new Date().toTimeString().slice(0,8).replace(/:/g,'-')}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// --- Replay Modal Functions ---
function openReplayModal() {
    if (gameHistory.length === 0) {
        alert('No hay historial de partida registrado para reproducir.');
        return;
    }
    
    // Stop active replay if any
    stopReplayInterval();

    const modal = document.getElementById('modal-replay');
    modal.classList.add('active');
    
    // Populate select
    const select = document.getElementById('replay-hand-select');
    select.innerHTML = '';
    gameHistory.forEach((hand, idx) => {
        const opt = document.createElement('option');
        opt.value = idx;
        opt.innerText = `Mano ${hand.handNumber}`;
        select.appendChild(opt);
    });
    
    // Load first hand
    loadReplayHand(0);
}

function closeReplayModal() {
    stopReplayInterval();
    document.getElementById('modal-replay').classList.remove('active');
}

function loadReplayHand(handIdx) {
    stopReplayInterval();
    replayHandIndex = parseInt(handIdx);
    replayStepIndex = 0;
    
    const hand = gameHistory[replayHandIndex];
    if (!hand) return;
    
    // Update logs list
    const logsContainer = document.getElementById('replay-logs-container');
    logsContainer.innerHTML = '';
    
    hand.logs.forEach((log, idx) => {
        const item = document.createElement('div');
        item.className = 'replay-log-item';
        item.id = `replay-log-item-${idx}`;
        item.innerText = log.message;
        item.addEventListener('click', () => selectReplayStep(idx));
        logsContainer.appendChild(item);
    });
    
    // Score info
    const scoreInfo = document.getElementById('replay-score-info');
    const p2Label = gameMode === 'pvc' ? 'CPU' : 'Jugador 2';
    if (hand.finalScores) {
        scoreInfo.innerText = `Resultado Mano: J1: ${hand.finalScores.p1Score} | ${p2Label}: ${hand.finalScores.p2Score}`;
    } else {
        scoreInfo.innerText = 'Mano en curso...';
    }
    
    selectReplayStep(0);
}

function selectReplayStep(stepIdx) {
    replayStepIndex = stepIdx;
    const hand = gameHistory[replayHandIndex];
    if (!hand || !hand.logs[replayStepIndex]) return;
    
    // Highlight log item
    const items = document.querySelectorAll('.replay-log-item');
    items.forEach(it => it.classList.remove('active'));
    
    const activeItem = document.getElementById(`replay-log-item-${replayStepIndex}`);
    if (activeItem) {
        activeItem.classList.add('active');
        activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    
    // Get state at this step
    const state = hand.logs[replayStepIndex].state;
    if (!state) return;
    
    // Save current active game's guiaCard
    const tempGuia = guiaCard;
    // Set global to the replay's guiaCard for styling
    guiaCard = hand.guiaCard;
    
    // Render Guia card
    const guiaContainer = document.getElementById('replay-card-guia');
    guiaContainer.innerHTML = '';
    if (hand.guiaCard) {
        guiaContainer.appendChild(createCardElement(hand.guiaCard, false));
    }
    
    // Render hands
    const p1Container = document.getElementById('replay-hand-p1');
    const p2Container = document.getElementById('replay-hand-p2');
    p1Container.innerHTML = '';
    p2Container.innerHTML = '';
    
    state.p1Hand.forEach(card => {
        if (card) {
            p1Container.appendChild(createCardElement(card, false));
        }
    });
    
    state.p2Hand.forEach(card => {
        if (card) {
            // Replay reveals CPU cards as face up to analyze strategy!
            p2Container.appendChild(createCardElement(card, false));
        }
    });
    
    // Render played cards
    const playedP1 = document.getElementById('replay-played-p1');
    const playedP2 = document.getElementById('replay-played-p2');
    playedP1.innerHTML = '';
    playedP2.innerHTML = '';
    
    if (state.p1PlayedCard) {
        const cardEl = createCardElement(state.p1PlayedCard, false);
        if (state.p1PlayedTaped) {
            cardEl.classList.add('taped');
        }
        playedP1.appendChild(cardEl);
    }
    if (state.p2PlayedCard) {
        const cardEl = createCardElement(state.p2PlayedCard, false);
        if (state.p2PlayedTaped) {
            cardEl.classList.add('taped');
        }
        playedP2.appendChild(cardEl);
    }
    
    // Restore original guiaCard
    guiaCard = tempGuia;
}

function replayFirstStep() {
    selectReplayStep(0);
}

function replayLastStep() {
    const hand = gameHistory[replayHandIndex];
    if (hand) {
        selectReplayStep(hand.logs.length - 1);
    }
}

function replayPrevStep() {
    if (replayStepIndex > 0) {
        selectReplayStep(replayStepIndex - 1);
    }
}

function replayNextStep() {
    const hand = gameHistory[replayHandIndex];
    if (hand && replayStepIndex < hand.logs.length - 1) {
        selectReplayStep(replayStepIndex + 1);
    } else {
        stopReplayInterval();
    }
}

function toggleReplayPlay() {
    const btn = document.getElementById('btn-replay-play');
    if (replayInterval) {
        stopReplayInterval();
    } else {
        btn.innerHTML = '<i class="fas fa-pause"></i>';
        replayInterval = setInterval(replayNextStep, replaySpeed);
    }
}

function stopReplayInterval() {
    const btn = document.getElementById('btn-replay-play');
    if (replayInterval) {
        clearInterval(replayInterval);
        replayInterval = null;
    }
    if (btn) {
        btn.innerHTML = '<i class="fas fa-play"></i>';
    }
}

function adjustReplaySpeed(val) {
    replaySpeed = parseInt(val);
    if (replayInterval) {
        stopReplayInterval();
        toggleReplayPlay(); // restart with new speed
    }
}

// --- Bootstrap ---
window.onload = () => {
    resetGame(true);
    initVoiceEngine();

    // Add backdrop click listeners to modals for premium UX close on blur click
    const modals = ['modal-rules', 'modal-replay'];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    if (id === 'modal-rules') closeRulesModal();
                    if (id === 'modal-replay') closeReplayModal();
                }
            });
        }
    });
};

// --- Global Tooltip Logic ---
document.addEventListener('DOMContentLoaded', () => {
    const tooltip = document.createElement('div');
    tooltip.id = 'global-tooltip';
    tooltip.className = 'global-tooltip';
    document.body.appendChild(tooltip);

    document.addEventListener('mouseover', (e) => {
        const card = e.target.closest('.card:not(.back)');
        if (card && card.hasAttribute('data-tooltip')) {
            const title = card.getAttribute('data-tooltip');
            tooltip.innerText = title;
            tooltip.classList.add('active');
            
            const rect = card.getBoundingClientRect();
            // Posicionar justo encima de la carta
            tooltip.style.left = (rect.left + rect.width / 2) + 'px';
            tooltip.style.top = (rect.top - 10) + 'px';
        } else {
            tooltip.classList.remove('active');
        }
    });

    document.addEventListener('mouseout', (e) => {
        const card = e.target.closest('.card:not(.back)');
        if (card) {
            tooltip.classList.remove('active');
        }
    });
});

