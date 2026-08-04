<?php
/**
 * contact-handler.php
 * Recibe el formulario de contacto de index.html (#contactForm) vía POST
 * (fetch/AJAX desde js/main.js) y envía un correo a info@novabridge.com.co.
 *
 * No requiere base de datos ni dependencias externas — usa mail() nativo de PHP,
 * disponible por defecto en el hosting de cPanel.
 */

header('Content-Type: application/json; charset=utf-8');

// ── Solo aceptar POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// ── Correo de destino ──
$destinatario = 'info@novabridge.com.co';

// ── Honeypot anti-spam: si este campo oculto viene lleno, es un bot.
//    Respondemos "éxito" falso para que el bot no reintente, pero no enviamos nada. ──
if (!empty($_POST['empresa_web'])) {
    echo json_encode(['success' => true]);
    exit;
}

// ── Helper: limpiar y sanear texto de entrada ──
function limpiar($valor) {
    $valor = trim($valor ?? '');
    // Evitar inyección de cabeceras de correo (header injection)
    $valor = str_replace(["\r", "\n"], ' ', $valor);
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$nombre   = limpiar($_POST['nombre'] ?? '');
$empresa  = limpiar($_POST['empresa'] ?? '');
$correo   = limpiar($_POST['correo'] ?? '');
$telefono = limpiar($_POST['telefono'] ?? '');
$area     = limpiar($_POST['area'] ?? '');
$mensaje  = trim($_POST['mensaje'] ?? ''); // el mensaje se escapa solo al insertarlo en el HTML del correo

// ── Validaciones mínimas ──
$errores = [];
if ($nombre === '') $errores[] = 'nombre';
if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'correo';
if ($mensaje === '') $errores[] = 'mensaje';

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Completa correctamente: ' . implode(', ', $errores)]);
    exit;
}

$mensajeHtml = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

// ── Cuerpo del correo ──
$asunto = 'Nuevo contacto desde novabridge.com.co' . ($area ? " — $area" : '');

$cuerpo = "
<html><body style='font-family:Arial,sans-serif;font-size:14px;color:#0A1628;'>
<h2 style='color:#0B4F7A;'>Nuevo mensaje desde el formulario de contacto</h2>
<table cellpadding='6' cellspacing='0'>
<tr><td><strong>Nombre:</strong></td><td>{$nombre}</td></tr>
<tr><td><strong>Empresa:</strong></td><td>" . ($empresa !== '' ? $empresa : '—') . "</td></tr>
<tr><td><strong>Correo:</strong></td><td>{$correo}</td></tr>
<tr><td><strong>Teléfono:</strong></td><td>" . ($telefono !== '' ? $telefono : '—') . "</td></tr>
<tr><td><strong>Área de interés:</strong></td><td>" . ($area !== '' ? $area : '—') . "</td></tr>
</table>
<p><strong>Mensaje:</strong><br>{$mensajeHtml}</p>
<hr>
<p style='font-size:11px;color:#888;'>Enviado desde el formulario de contacto de novabridge.com.co el " . date('d/m/Y H:i') . " (hora del servidor)</p>
</body></html>
";

// ── Cabeceras ──
// From: se usa una dirección del propio dominio (mejora la entregabilidad —
// muchos servidores marcan como spam correos que dicen venir "From" de un
// dominio externo). Reply-To: el correo de la persona que escribió, para
// que al responder el correo vaya directo a ella.
$headers   = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/html; charset=UTF-8';
$headers[] = 'From: Nova Bridge Web <no-reply@novabridge.com.co>';
$headers[] = "Reply-To: {$nombre} <{$correo}>";

$enviado = mail($destinatario, $asunto, $cuerpo, implode("\r\n", $headers));

if ($enviado) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo. Intenta por WhatsApp mientras lo revisamos.']);
}
