<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Bienvenido a CheckMate</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; background-color: #f3f4f6; padding: 24px;">
    <table role="presentation" width="100%" style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="padding: 24px;">
                <h1 style="font-size: 20px; margin-bottom: 16px;">¡Bienvenido a CheckMate, {{ $user->fullName() }}!</h1>
                <p style="font-size: 14px; line-height: 1.5;">
                    Se creó tu cuenta en el sistema de control de asistencia. Usa las siguientes
                    credenciales para iniciar sesión por primera vez:
                </p>
                <table role="presentation" style="width: 100%; margin: 16px 0; background: #f3f4f6; border-radius: 6px;">
                    <tr>
                        <td style="padding: 12px 16px; font-size: 14px;">
                            <strong>Correo:</strong> {{ $user->email }}<br>
                            <strong>Contraseña temporal:</strong> {{ $temporaryPassword }}
                        </td>
                    </tr>
                </table>
                <p style="font-size: 13px; color: #6b7280; line-height: 1.5;">
                    Por seguridad, cambia tu contraseña en cuanto inicies sesión.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
