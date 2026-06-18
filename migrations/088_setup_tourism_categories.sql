-- Migración 088: Configuración de categorías de Turismo
-- Fecha: 2026-06-18

INSERT INTO categories (name, slug, is_visible, sort_order)
SELECT 'Turismo', 'turismo', 1, 10
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Turismo');

INSERT INTO categories (name, slug, parent_id, is_visible, sort_order)
SELECT 'Bares y Restaurantes', 'bares-y-restaurantes', id, 1, 1
FROM categories
WHERE name = 'Turismo'
  AND NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Bares y Restaurantes');

UPDATE categories 
SET parent_id = (SELECT id FROM (SELECT id FROM categories WHERE name = 'Turismo') as t)
WHERE name = 'Bares y Restaurantes';

INSERT INTO categories (name, slug, parent_id, is_visible, sort_order)
SELECT 'Alojamientos', 'alojamientos', id, 1, 2
FROM categories
WHERE name = 'Turismo'
  AND NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Alojamientos');

UPDATE categories 
SET parent_id = (SELECT id FROM (SELECT id FROM categories WHERE name = 'Turismo') as t)
WHERE name = 'Alojamientos';
