-- Hotfix: correct default admin password hash. Password: admin12345
UPDATE users
SET password_hash = '$argon2id$v=19$m=65536,t=4,p=2$WmUyb0xXZ3R4Y01PVzM0ag$spo1gyD61bBwGk37AAhq/RetQaRTIusnAeWKDNalb+E',
    updated_at = NOW()
WHERE email = 'admin@example.com'
  AND id = '11111111-1111-1111-1111-111111111111';

UPDATE user_password_history
SET password_hash = '$argon2id$v=19$m=65536,t=4,p=2$WmUyb0xXZ3R4Y01PVzM0ag$spo1gyD61bBwGk37AAhq/RetQaRTIusnAeWKDNalb+E'
WHERE user_id = '11111111-1111-1111-1111-111111111111';
