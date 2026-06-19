const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');

// Create a new PDF document
const doc = new PDFDocument({ margin: 50, size: 'A4' });

// Pipe to a file
const pdfPath = path.join(__dirname, 'reglas_truque.pdf');
const stream = fs.createWriteStream(pdfPath);
doc.pipe(stream);

// Styling constants
const PRIMARY_COLOR = '#1e3f20'; // Dark forest green
const SECONDARY_COLOR = '#a3843b'; // Old Gold
const TEXT_COLOR = '#333333';
const LIGHT_GREY = '#f4f4f4';

// Helper function to draw a line separator
function drawSeparator() {
    doc.moveDown(0.5);
    doc.strokeColor('#dddddd').lineWidth(1).moveTo(50, doc.y).lineTo(545, doc.y).stroke();
    doc.moveDown(0.8);
}

// Title Section
doc.fillColor(PRIMARY_COLOR);
doc.font('Helvetica-Bold').fontSize(22).text('REGLAS Y CONFIGURACIÓN DEL TRUQUE', { align: 'center' });
doc.fontSize(11).fillColor(SECONDARY_COLOR).text('Baraja Española | Resumen y Definiciones Oficiales', { align: 'center' });
doc.moveDown(1.5);

// General Description
doc.fillColor(TEXT_COLOR).font('Helvetica-Oblique').fontSize(10);
doc.text(
    'El Truque es un juego de naipes de origen tradicional que consta de 2 fases diferenciadas (Envite y Truque). ' +
    'Se juega normalmente a 50 chinas (puntos) divididas en dos series: las primeras 25 se llaman MALAS y las últimas 25 se llaman BUENAS. ' +
    'Pueden jugar 2 jugadores o 4 jugadores (en parejas de 2 contra 2). El engaño, el farol ("embuste") y la comunicación no verbal por señas son fundamentales.',
    { align: 'justify', lineGap: 3 }
);

drawSeparator();

// Section 1: Reparto y Guía
doc.fillColor(PRIMARY_COLOR).font('Helvetica-Bold').fontSize(14).text('1. REPARTO DE CARTAS Y LA "GUÍA"');
doc.moveDown(0.5);
doc.fillColor(TEXT_COLOR).font('Helvetica').fontSize(10);
doc.text(
    '- Cada jugador recibe 3 cartas de la baraja española (de 40 cartas).\n' +
    '- Al finalizar el reparto, se pone una carta boca arriba llamada la "Guía", la cual determina el palo de triunfo (el palo guía) para esa mano.\n' +
    '- Si el dador (el que reparte) coloca su propia última carta boca arriba en su montón, esto se conoce como "Bailarse". Esto significa que esa carta es la Guía y que además "envida" directamente la apuesta inicial del Envite.',
    { lineGap: 3 }
);

drawSeparator();

// Section 2: El Envite
doc.fillColor(PRIMARY_COLOR).font('Helvetica-Bold').fontSize(14).text('2. PRIMERA FASE: EL ENVITE');
doc.moveDown(0.5);
doc.fillColor(TEXT_COLOR).font('Helvetica').fontSize(10);
doc.text(
    'El Envite consiste en sumar los puntos de las dos cartas de mayor valor de la mano (sumando 20 puntos adicionales a esta suma). ' +
    'El palo de la carta "Guía" define el valor especial de las cartas en esta fase:\n\n' +
    '   • As de la Guía: 12 puntos              • 7 (normal): 7 puntos\n' +
    '   • 5 de la Guía: 11 puntos               • 6 (normal): 6 puntos\n' +
    '   • Rey de la Guía: 10 puntos             • 5 (normal): 5 puntos\n' +
    '   • Caballo de la Guía: 9 puntos          • 4 (normal): 4 puntos\n' +
    '   • Sota de la Guía: 8 puntos             • 3 (normal): 3 puntos\n' +
    '   • Resto de cartas: Su valor nominal     • 2 (normal): 2 puntos\n' +
    '   • As (no guía): 1 punto                 • El resto de cartas guía no listadas vale su número.\n\n' +
    'Fórmula: Puntos = Carta 1 (más alta) + Carta 2 (segunda más alta) + 20. (Máximo posible: 43 puntos, conocido como "El Amo").\n\n' +
    'Apuestas del Envite:\n' +
    '   - ENVIDO: Apuesta inicial de 2 chinas o cualquier cantidad (chinas individuales o pares, ej: 2 pares = 4 chinas) hasta el máximo de La Falta.\n' +
    '   - ENVIDO MÁS: Permite subir la apuesta existente indicando una cantidad mayor.\n' +
    '   - QUINQUÉ: Apuesta directa de 5 chinas.\n' +
    '   - LA FALTA: Apuesta el número de chinas necesarias para ganar la partida por parte de quien vaya liderando.\n\n' +
    'Si nadie envida, la fase queda desierta. En caso de empate en puntos de envite, gana el jugador que es "Mano" (el más a la derecha del dador).',
    { lineGap: 3 }
);

// Add a page break for clean formatting
doc.addPage();

// Section 3: El Truque
doc.fillColor(PRIMARY_COLOR).font('Helvetica-Bold').fontSize(14).text('3. SEGUNDA FASE: EL TRUQUE');
doc.moveDown(0.5);
doc.fillColor(TEXT_COLOR).font('Helvetica').fontSize(10);
doc.text(
    'El Truque consiste en jugar las 3 cartas en la mesa a lo largo de 3 rondas sucesivas ("Primeras", "Segundas" y "Terceras"). ' +
    'El jugador o pareja que logre ganar 2 de las 3 rondas gana el Truque de la mano.\n\n' +
    'Reglas de juego de las cartas:\n' +
    '   - En "Primeras" (1ª ronda), todas las cartas se muestran boca arriba obligatoriamente.\n' +
    '   - Si hay un empate en la primera ronda, las cartas quedan "Pardas". Se salta la segunda ronda y se va directo a "Terceras". El que gane la tercera ronda gana el Truque.\n' +
    '   - En la segunda y tercera ronda, se permite "tapar" la carta (jugarla boca abajo) para despistar al adversario o forzar apuestas.\n\n' +
    'Escala de Apuestas del Truque (Deben cantarse correlativamente):\n' +
    '   1. TRUCO: Vale 3 chinas.\n' +
    '   2. RETRUCO: Vale 6 chinas.\n' +
    '   3. RENUEVE: Vale 9 chinas.\n' +
    '   4. REDOCE: Vale 12 chinas.\n' +
    '   5. REQUINCE: Vale 15 chinas.\n' +
    '   6. REJUEGO: Todas las chinas que le falten al que más tenga para ganar la partida.\n\n' +
    'Un jugador no puede subir su propia apuesta a menos que el rival la haya incrementado previamente.',
    { lineGap: 3 }
);

drawSeparator();

// Jerarquía de Cartas en el Truque
doc.fillColor(PRIMARY_COLOR).font('Helvetica-Bold').fontSize(14).text('4. JERARQUÍA DE CARTAS EN EL TRUQUE');
doc.moveDown(0.5);

doc.fillColor(TEXT_COLOR).font('Helvetica').fontSize(9.5);
const listLeft = [
    '1. As del palo de la Guía (Carta más alta)',
    '2. 5 del palo de la Guía',
    '3. Rey del palo de la Guía',
    '4. Caballo del palo de la Guía',
    '5. Sota del palo de la Guía',
    '6. As de Espadas (Ancho de Espadas)',
    '7. As de Bastos (Ancho de Bastos)',
    '8. 7 de Espadas',
    '9. 7 de Oros',
    '10. Los 3 (de todos los palos)'
];

const listRight = [
    '11. Los 2 (de todos los palos)',
    '12. As de Oros y As de Copas (Botas)',
    '13. Resto de Reyes',
    '14. Resto de Caballos',
    '15. Resto de Sotas',
    '16. Resto de 7 (Copas y Bastos)',
    '17. Los 6',
    '18. Resto de 5',
    '19. Los 4 (Carta más baja)'
];

let startY = doc.y;
doc.text(listLeft.join('\n'), 60, startY, { width: 230, lineGap: 4 });
doc.text(listRight.join('\n'), 300, startY, { width: 230, lineGap: 4 });

doc.y = startY + 180; // Move cursor past the columns

drawSeparator();

// Footer / Astucia
doc.fillColor(PRIMARY_COLOR).font('Helvetica-Bold').fontSize(12).text('FILOSOFÍA Y ESTRATEGIA DEL JUEGO');
doc.moveDown(0.3);
doc.fillColor(TEXT_COLOR).font('Helvetica-Oblique').fontSize(9.5).text(
    'En el Truque, el engaño y el "embuste" son parte del reglamento. Si un jugador o pareja abandona la mano ' +
    'porque cree que el oponente tiene cartas superiores (o ante un farol exitoso), el oponente se lleva la apuesta ' +
    'existente en la mesa sin necesidad de mostrar sus cartas, lo cual protege la estrategia de farol del jugador.',
    { align: 'justify', lineGap: 2.5 }
);

// End the PDF stream
doc.end();

stream.on('finish', () => {
    console.log('PDF creado exitosamente en reglas_truque.pdf');
});
