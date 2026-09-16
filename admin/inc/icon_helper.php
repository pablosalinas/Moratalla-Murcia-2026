<?php
// admin/inc/icon_helper.php

/**
 * Retorna todos los iconos personalizados (.svg, .png, etc.) de uploads/icons/
 */
function getUploadedCustomIcons() {
    $dir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    
    $icons = [];
    $allowed = ['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'];
    $files = @scandir($dir);
    if ($files) {
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $icons[] = $f;
            }
        }
    }
    return $icons;
}

/**
 * Retorna lista consolidada de opciones para selectores de iconos
 */
function getConsolidatedIconOptions(PDO $pdo, array $extraUsed = []) {
    $defaultIcons = [
        '📄' => '📄 Página genérica',
        'ℹ️' => 'ℹ️ Información',
        '🏛️' => '🏛️ Patrimonio / Historia',
        '🌳' => '🌳 Naturaleza',
        '📷' => '📷 Fotografía',
        '🎵' => '🎵 Música',
        '⚽' => '⚽ Deportes',
        '⛪' => '⛪ Iglesia',
        '✝️' => '✝️ Religión',
        '📰' => '📰 Noticias',
        '👥' => '👥 Asociaciones',
        '📖' => '📖 Cultura / Lectura',
        '🕰️' => '🕰️ Historia (Reloj)',
        '🎥' => '🎥 Vídeo',
        '🖼️' => '🖼️ Galería',
        '🗺️' => '🗺️ Mapa / Rutas',
        '⭐' => '⭐ Destacado',
        '❤️' => '❤️ Favorito',
        '🍽️' => '🍽️ Gastronomía',
        '🛏️' => '🛏️ Alojamiento',
        '🏀' => '🏀 Baloncesto',
        '🏺' => '🏺 Artesanía',
        '🧺' => '🧺 Esparto',
        '🎨' => '🎨 Pintura',
        '🚴' => '🚴 Ciclismo',
        '🚗' => '🚗 Automóvil',
        '🏫' => '🏫 Escuelas',
        '🎒' => '🎒 Colegios',
        '🎓' => '🎓 Institutos',
        '🏢' => '🏢 Servicios Municipales',
        '✉️' => '✉️ Contacto',
        '🧳' => '🧳 Turismo',
        '🍻' => '🍻 Bares y Restaurantes',
        '🏰' => '🏰 Castillo',
        '🪨' => '🪨 Arte Rupestre',
        '⛰️' => '⛰️ Montes y Montañas',
        '🛤️' => '🛤️ Rutas',
        '🥁' => '🥁 Tambor',
        '🐂' => '🐂 Tauromaquia',
        '🎉' => '🎉 Fiestas / Celebraciones'
    ];

    $all = $defaultIcons;

    // Agregar iconos subidos (.svg, .png, etc.)
    $uploadedIcons = getUploadedCustomIcons();
    foreach ($uploadedIcons as $uFile) {
        $cleanName = pathinfo($uFile, PATHINFO_FILENAME);
        $cleanName = str_replace(['_', '-'], ' ', $cleanName);
        $all[$uFile] = "🖼️ Archivo: {$cleanName} (" . pathinfo($uFile, PATHINFO_EXTENSION) . ")";
    }

    // Agregar iconos extra usados en base de datos
    foreach ($extraUsed as $uIcon) {
        if (!empty($uIcon) && !isset($all[$uIcon])) {
            $all[$uIcon] = $uIcon;
        }
    }

    // Ordenar alfabéticamente
    uasort($all, function($a, $b) {
        $isArchA = (strpos($a, '🖼️ Archivo:') === 0);
        $isArchB = (strpos($b, '🖼️ Archivo:') === 0);
        if ($isArchA && !$isArchB) return -1;
        if (!$isArchA && $isArchB) return 1;

        $textA = trim(mb_substr($a, mb_strpos($a, ' ') !== false ? mb_strpos($a, ' ') : 0));
        $textB = trim(mb_substr($b, mb_strpos($b, ' ') !== false ? mb_strpos($b, ' ') : 0));
        $search  = ['Á','É','Í','Ó','Ú','á','é','í','ó','ú'];
        $replace = ['A','E','I','O','U','a','e','i','o','u'];
        $textA = str_replace($search, $replace, $textA);
        $textB = str_replace($search, $replace, $textB);
        return strcasecmp($textA, $textB);
    });

    return $all;
}

/**
 * Procesa la subida de un archivo de icono (.svg o .png)
 * @return string|false Nombre del archivo subido o false si no hubo subida
 */
function handleIconUpload($fileKey = 'icon_file', &$error = null) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    $file = $_FILES[$fileKey];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo de icono (código " . $file['error'] . ").";
        return false;
    }

    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'];

    if (!in_array($ext, $allowed)) {
        $error = "Formato de icono no admitido. Solo se admiten archivos .svg, .png, .webp o .jpg.";
        return false;
    }

    // Limpieza de nombre
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    if (empty($cleanBase)) $cleanBase = 'icon';
    $finalName = $cleanBase . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

    $targetDir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    $targetPath = $targetDir . '/' . $finalName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $finalName;
    } else {
        $error = "No se pudo mover el archivo de icono subido a la carpeta uploads/icons/.";
        return false;
    }
}

/**
 * Renderiza el widget completo de selección y subida de iconos para formularios de administración
 */
function renderIconPickerField($currentIcon, $iconOptions, $inputName = 'icon', $inputId = 'adminIconInput', $helpText = 'Puedes elegir un icono de la lista, escribir un emoji/clase de FontAwesome, o subir directamente un archivo .svg o .png') {
    $currentIcon = trim((string)$currentIcon);
    $isImg = preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $currentIcon);
    $imgSrc = '';
    if ($isImg) {
        $imgSrc = (strpos($currentIcon, 'uploads/') === 0 || strpos($currentIcon, 'http') === 0) ? '../' . $currentIcon : '../uploads/icons/' . $currentIcon;
    }
    
    $uniqueId = preg_replace('/[^a-zA-Z0-9]/', '', $inputId);
    ?>
    <div class="icon-picker-widget" id="widget_<?php echo $uniqueId; ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.2rem;">
        <input type="hidden" name="<?php echo htmlspecialchars($inputName); ?>" id="<?php echo htmlspecialchars($inputId); ?>" value="<?php echo htmlspecialchars($currentIcon); ?>">

        <!-- Fila de Selección y Preview -->
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 0.8rem;">
            <!-- Caja de Vista Previa -->
            <div id="preview_<?php echo $uniqueId; ?>" style="width: 44px; height: 44px; border-radius: 8px; background: white; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <?php if ($isImg): ?>
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 24px; height: 24px; object-fit: contain;">
                <?php elseif (strpos($currentIcon, 'fa-') !== false): ?>
                    <?php $pfx = (strpos($currentIcon, 'fas ') === false && strpos($currentIcon, 'far ') === false && strpos($currentIcon, 'fab ') === false) ? 'fas ' : ''; ?>
                    <i class="<?php echo $pfx . htmlspecialchars($currentIcon); ?>"></i>
                <?php else: ?>
                    <span><?php echo !empty($currentIcon) ? htmlspecialchars($currentIcon) : '❓'; ?></span>
                <?php endif; ?>
            </div>

            <!-- Desplegable -->
            <div style="flex: 1; min-width: 240px;">
                <select id="select_<?php echo $uniqueId; ?>" style="width: 100%; padding: 0.75rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white; cursor: pointer;">
                    <?php
                    $found = false;
                    foreach ($iconOptions as $val => $lbl) {
                        $sel = ($val === $currentIcon) ? 'selected' : '';
                        if ($sel) $found = true;
                        echo "<option value=\"" . htmlspecialchars($val) . "\" {$sel}>" . htmlspecialchars($lbl) . "</option>";
                    }
                    if (!$found && !empty($currentIcon)) {
                        echo "<option value=\"" . htmlspecialchars($currentIcon) . "\" selected>" . htmlspecialchars($currentIcon) . " (Actual)</option>";
                    }
                    ?>
                    <option value="_custom_">✏️ Escribir Emoji / Clase FontAwesome...</option>
                </select>
            </div>
        </div>

        <!-- Input para modo manual -->
        <div id="customDiv_<?php echo $uniqueId; ?>" style="display: none; margin-bottom: 0.8rem;">
            <div style="display: flex; gap: 8px;">
                <input type="text" id="customInput_<?php echo $uniqueId; ?>" placeholder="Ej: 🍕 o fas fa-star" value="<?php echo htmlspecialchars($currentIcon); ?>" style="flex: 1; padding: 0.7rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                <button type="button" id="applyCustom_<?php echo $uniqueId; ?>" class="btn" style="background: var(--primary); color: white; padding: 0.7rem 1rem; border-radius: 8px;"><i class="fas fa-check"></i> Aplicar</button>
            </div>
        </div>

        <!-- Fila de subida rápida de archivo .svg o .png -->
        <div style="background: white; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #475569;">
                <i class="fas fa-cloud-upload-alt" style="color: var(--primary); font-size: 1.1rem;"></i>
                <span><strong>¿Subir icono nuevo?</strong> Archivos <code>.svg</code> o <code>.png</code>:</span>
            </div>
            <div>
                <input type="file" name="icon_file" id="file_<?php echo $uniqueId; ?>" accept=".svg,.png,.webp,.jpg,.jpeg" style="font-size: 0.82rem; color: #64748b;">
            </div>
        </div>

        <small style="color: #64748b; display: block; margin-top: 0.5rem; font-size: 0.8rem;"><?php echo htmlspecialchars($helpText); ?></small>
    </div>

    <script>
    (function() {
        const sel = document.getElementById('select_<?php echo $uniqueId; ?>');
        const mainInput = document.getElementById('<?php echo $inputId; ?>');
        const customDiv = document.getElementById('customDiv_<?php echo $uniqueId; ?>');
        const customInput = document.getElementById('customInput_<?php echo $uniqueId; ?>');
        const applyBtn = document.getElementById('applyCustom_<?php echo $uniqueId; ?>');
        const preview = document.getElementById('preview_<?php echo $uniqueId; ?>');
        const fileInput = document.getElementById('file_<?php echo $uniqueId; ?>');

        function updatePreview(val) {
            val = (val || '').trim();
            if (!val) {
                preview.innerHTML = '❓';
                return;
            }
            if (val.match(/\.(svg|png|jpg|jpeg|webp|gif)$/i)) {
                let src = (val.indexOf('uploads/') === 0 || val.indexOf('http') === 0) ? '../' + val : '../uploads/icons/' + val;
                preview.innerHTML = '<img src="' + src + '" style="width:24px; height:24px; object-fit:contain;">';
            } else if (val.indexOf('fa-') !== -1) {
                let pfx = (val.indexOf('fas ') === -1 && val.indexOf('far ') === -1 && val.indexOf('fab ') === -1) ? 'fas ' : '';
                preview.innerHTML = '<i class="' + pfx + val + '"></i>';
            } else {
                preview.innerHTML = '<span>' + val + '</span>';
            }
        }

        if (sel) {
            sel.addEventListener('change', function() {
                if (this.value === '_custom_') {
                    customDiv.style.display = 'block';
                    customInput.focus();
                } else {
                    customDiv.style.display = 'none';
                    mainInput.value = this.value;
                    updatePreview(this.value);
                }
            });

            if (sel.value === '_custom_') {
                customDiv.style.display = 'block';
            }
        }

        if (applyBtn && customInput) {
            applyBtn.addEventListener('click', function() {
                const val = customInput.value.trim();
                if (val) {
                    mainInput.value = val;
                    let exists = false;
                    for (let i = 0; i < sel.options.length; i++) {
                        if (sel.options[i].value === val) {
                            sel.selectedIndex = i;
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        const opt = new Option(val + ' (Personalizado)', val, true, true);
                        sel.insertBefore(opt, sel.options[sel.options.length - 1]);
                    }
                    updatePreview(val);
                    customDiv.style.display = 'none';
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const f = this.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" style="width:24px; height:24px; object-fit:contain;">';
                    };
                    reader.readAsDataURL(f);
                }
            });
        }
    })();
    </script>
    <?php
}