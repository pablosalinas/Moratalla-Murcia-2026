import re
import json

file_path = r"C:\Users\User\.gemini\antigravity\brain\9fb3039f-fa58-40a1-9ac5-5375526e1e2f\.system_generated\steps\316\content.md"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# Split by "## " to get sections
sections = content.split("## ")
accommodations = []

for section in sections[1:]:
    lines_raw = section.split("\n")
    if not lines_raw:
        continue
    name = lines_raw[0].strip()
    
    if name.startswith("Contáctanos") or name.startswith("Accesibilidad") or name.startswith("Política"):
        continue
    if "C. Constitución" in name or "Sede electrónica" in name:
        continue
    if name in ["Instagram", "Facebook", "X-twitter", "Youtube", "Sede electrónica", "Soy Turista", "Soy de Moratalla", "Soy Empresa", "Ayuntamiento", "Contacto"]:
        continue

    bullets = []
    current_bullet = []
    
    for line in lines_raw[1:]:
        line_stripped = line.strip()
        if line_stripped.startswith("##") or line_stripped.startswith("[C. Constitución"):
            break
        if line_stripped.startswith("-"):
            if current_bullet:
                bullets.append(" ".join(current_bullet).strip())
                current_bullet = []
            content_after = line_stripped[1:].strip()
            if content_after:
                current_bullet.append(content_after)
        else:
            if line_stripped and not line_stripped.startswith("[Descubre más]"):
                current_bullet.append(line_stripped)
                
    if current_bullet:
        bullets.append(" ".join(current_bullet).strip())

    address = bullets[0] if len(bullets) > 0 else ""
    phone = bullets[1] if len(bullets) > 1 else ""
    
    address = address.replace("  ", " ").strip()
    phone = phone.replace("  ", " ").strip()
    
    tel1 = ""
    tel2 = ""
    if phone:
        parts = re.split(r'\s*-\s*|\s*/\s*', phone)
        if len(parts) > 1:
            tel1 = parts[0].strip()
            tel2 = parts[1].strip()
        else:
            tel1 = phone.strip()

    es_pedania = 0
    poblacion = "Moratalla"
    
    address_upper = address.upper()
    if "INAZARES" in address_upper:
        es_pedania = 1
        poblacion = "Inazares"
    elif "CALAR DE LA SANTA" in address_upper:
        es_pedania = 1
        poblacion = "Calar de la Santa"
    elif "MAZUZA" in address_upper:
        es_pedania = 1
        poblacion = "Mazuza"
    elif "SAN JUAN" in address_upper:
        es_pedania = 1
        poblacion = "San Juan"
    elif "SABINAR" in address_upper or "EL SABINAR" in address_upper:
        es_pedania = 1
        poblacion = "El Sabinar"
    elif "BENIZAR" in address_upper:
        es_pedania = 1
        poblacion = "Benízar"
    elif "LA ROGATIVA" in address_upper:
        es_pedania = 1
        poblacion = "La Rogativa"

    zip_code = "30440"
    for code in ["30413", "30410", "30441", "30442"]:
        if code in address:
            zip_code = code
            break

    accommodations.append({
        "nombre": name,
        "calle": address,
        "poblacion": poblacion,
        "es_pedania": es_pedania,
        "codigo_postal": zip_code,
        "telefono1": tel1,
        "telefono2": tel2
    })

# Now generate SQL insertion
sql_lines = [
    "-- Migración 086: Carga inicial de alojamientos extraídos",
    "-- Fecha: 2026-06-18",
    "",
    "SET FOREIGN_KEY_CHECKS = 0;",
    "TRUNCATE TABLE `alojamientos`;",
    "SET FOREIGN_KEY_CHECKS = 1;",
    ""
]

for idx, acc in enumerate(accommodations):
    name_esc = acc['nombre'].replace("'", "''")
    calle_esc = acc['calle'].replace("'", "''")
    pob_esc = acc['poblacion'].replace("'", "''")
    tel1_esc = acc['telefono1'].replace("'", "''")
    tel2_esc = acc['telefono2'].replace("'", "''")
    cp = acc['codigo_postal']
    es_p = acc['es_pedania']
    sort_order = idx * 10
    
    sql_lines.append(
        f"INSERT INTO `alojamientos` (`nombre`, `calle`, `poblacion`, `es_pedania`, `codigo_postal`, `telefono1`, `telefono2`, `sort_order`, `is_visible`) "
        f"VALUES ('{name_esc}', '{calle_esc}', '{pob_esc}', {es_p}, '{cp}', '{tel1_esc}', '{tel2_esc}', {sort_order}, 1);"
    )

sql_content = "\n".join(sql_lines) + "\n"

output_sql_path = r"c:\xampp_2023\htdocs\Moratalla-Murcia-2026\migrations\086_data_alojamientos.sql"
with open(output_sql_path, "w", encoding="utf-8") as out_f:
    out_f.write(sql_content)

print(f"SQL file written successfully to {output_sql_path}")
print(f"Total entries: {len(accommodations)}")
