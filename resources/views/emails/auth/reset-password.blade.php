<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablece tu contraseña</title>
</head>
<body style="margin: 0; padding: 32px; background: #f4f6f8; color: #1f2937; font-family: Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 8px;">
        <h1 style="margin-top: 0; font-size: 24px;">Restablece tu contraseña</h1>
        <p>Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>
        <p style="margin: 28px 0;">
            <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 20px; border-radius: 6px; background: #2563eb; color: #ffffff; text-decoration: none;">
                Crear nueva contraseña
            </a>
        </p>
        <p>Este enlace caduca en {{ config('auth.passwords.users.expire') }} minutos y solo puede utilizarse una vez.</p>
        <p>Si no solicitaste el cambio, ignora este mensaje.</p>
    </div>
</body>
</html>
