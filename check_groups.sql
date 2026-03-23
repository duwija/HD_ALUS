-- Check existing groups in database
SELECT id, name, type, `group`, created_at 
FROM map_layers 
WHERE type = 'group' OR name LIKE '%GROUP%'
ORDER BY id DESC;

-- If no groups exist, create sample groups
-- Uncomment these lines if you need to create test groups:

-- INSERT INTO map_layers (name, type, coordinates, color, icon, weight, opacity, description, created_at, updated_at)
-- VALUES 
-- ('INI GROUP LAYER', 'group', '[]', '#3388ff', 'pin', 3, 0.6, 'Group untuk testing', NOW(), NOW()),
-- ('GROUP POLYLINE', 'group', '[]', '#3388ff', 'pin', 3, 0.6, 'Group untuk polyline', NOW(), NOW());
