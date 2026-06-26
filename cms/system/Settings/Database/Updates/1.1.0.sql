-- Settings 1.1.0: configurable security headers.
INSERT INTO system_settings (setting_group,setting_key,setting_label,setting_value,field_type,field_options,description,is_public,is_system,sort_order,is_required,min_value,max_value,validation_rule,is_secret,created_at,updated_at) VALUES
('security','security_csp','Content Security Policy','default-src ''self''; img-src ''self'' data:; style-src ''self'' ''unsafe-inline''; script-src ''self''; font-src ''self'' data:; connect-src ''self''; frame-ancestors ''self''; base-uri ''self''; form-action ''self''','textarea',NULL,'Основная CSP политика. Обязательно должна содержать default-src.',0,1,10,1,NULL,NULL,'csp',0,NOW(),NOW()),
('security','security_csp_report_only','CSP Report-Only','0','checkbox',NULL,'Не блокировать нарушения CSP.',0,1,20,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_enabled','HSTS','1','checkbox',NULL,'Отправлять HSTS только при HTTPS.',0,1,30,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_max_age','HSTS max-age','31536000','number',NULL,'Срок HSTS в секундах.',0,1,40,0,300,63072000,NULL,0,NOW(),NOW()),
('security','security_hsts_include_subdomains','HSTS includeSubDomains','1','checkbox',NULL,'Применять HSTS к поддоменам.',0,1,50,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_preload','HSTS preload','0','checkbox',NULL,'Добавлять preload только после проверки домена.',0,1,60,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_frame_options','X-Frame-Options','SAMEORIGIN','select','{"DENY":"DENY","SAMEORIGIN":"SAMEORIGIN"}','Защита от clickjacking.',0,1,70,1,NULL,NULL,'header',0,NOW(),NOW()),
('security','security_referrer_policy','Referrer-Policy','strict-origin-when-cross-origin','text',NULL,'Политика Referer.',0,1,80,1,NULL,NULL,'header',0,NOW(),NOW()),
('security','security_permissions_policy','Permissions-Policy','camera=(), microphone=(), geolocation=()','textarea',NULL,'Разрешения браузерных API.',0,1,90,1,NULL,NULL,'header',0,NOW(),NOW())
ON DUPLICATE KEY UPDATE setting_label=VALUES(setting_label),field_type=VALUES(field_type),field_options=VALUES(field_options),description=VALUES(description),is_system=1,sort_order=VALUES(sort_order),is_required=VALUES(is_required),min_value=VALUES(min_value),max_value=VALUES(max_value),validation_rule=VALUES(validation_rule),updated_at=NOW();
