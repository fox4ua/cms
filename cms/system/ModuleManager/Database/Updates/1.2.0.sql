UPDATE menu_items SET is_active=1, module='ModuleManager', updated_at=NOW()
WHERE url='/admin/modules' AND menu_key='admin_sidebar';
UPDATE menu_items SET is_active=1, module='Menu', updated_at=NOW()
WHERE url='/admin/menu' AND menu_key='admin_sidebar';
UPDATE menu_items SET is_active=1, module='Settings', updated_at=NOW()
WHERE url='/admin/settings' AND menu_key='admin_sidebar';
