"""Pruebas HTTP locales de cabeceras y sesiones. Usa la base preparada; no escribe datos ni envía correo."""
import http.cookiejar
import json
from pathlib import Path
import secrets
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[2]


def preparar_router(directory, fixture):
    """La autenticación de prueba solo existe en este router temporal, fuera de public_php."""
    router = directory / 'router.php'
    source = '''<?php
    $raiz = ROOT;
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path === FIXTURE) {
        require_once $raiz . '/app/seguridad/Csrf.php';
        session_start();
        if (($_GET['modo'] ?? '') === 'iniciar') {
            require_once $raiz . '/config/conexion.php';
            $pdo = conectarBd();
            $sql = ($_GET['rol'] ?? '') === 'admin'
                ? 'SELECT id FROM usuarios WHERE admin = 1 ORDER BY id LIMIT 1'
                : "SELECT owner_id FROM camp WHERE monetaria = 0 AND estado = 'activa' ORDER BY id LIMIT 1";
            $id = (int) $pdo->query($sql)->fetchColumn();
            if (!$id) { http_response_code(500); echo 'Faltan datos de demostración para las pruebas.'; return; }
            $_SESSION = ['usuario_id' => $id, 'ultima_actividad' => time() - (int) ($_GET['edad'] ?? 0)];
        }
        header('Content-Type: application/json');
        echo json_encode(['autenticado' => isset($_SESSION['usuario_id']), 'csrf' => Csrf::generar(),
            'ultima_actividad' => $_SESSION['ultima_actividad'] ?? null]);
        return;
    }
    if (is_file($raiz . '/public_php' . $path)) return false;
    require $raiz . '/public_php/index.php';
    '''.replace('ROOT', json.dumps(str(ROOT))).replace('FIXTURE', json.dumps(fixture))
    router.write_text(source)
    return router


class SinRedireccion(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def comprobar_http(base, fixture):
    jar = http.cookiejar.CookieJar()
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), SinRedireccion())

    def request(path, post=None, headers=None):
        payload = urllib.parse.urlencode(post).encode() if post is not None else None
        req = urllib.request.Request(base + path, data=payload, headers=headers or {})
        try:
            response = client.open(req, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.headers, response.read().decode()

    def seed(age=0, role='usuario'):
        return json.loads(request(fixture + '?modo=iniciar&edad=' + str(age) + '&rol=' + role)[2])

    for route, expected in [('contacto', 200), ('ingresar', 200), ('registro', 200), ('preguntas_frecuentes', 200),
                            ('admin_usuarios', 401), ('admin_mensajes', 401), ('salir', 405), ('no_existe', 404)]:
        status, headers, _ = request('/index.php?ruta=' + route)
        assert status == expected, (route, status)
        assert headers.get('X-Content-Type-Options') == 'nosniff', route
        assert headers.get('X-Frame-Options') == 'DENY', route
        assert headers.get('Referrer-Policy') == 'strict-origin-when-cross-origin', route
        assert headers.get('X-Powered-By') is None, route
        csp = headers.get('Content-Security-Policy', '')
        assert "frame-ancestors 'none'" in csp and "object-src 'none'" in csp and "form-action 'self'" in csp, route
        assert "'unsafe-inline'" not in csp.split('script-src ', 1)[1].split(';', 1)[0], route
    print('OK: cabeceras en HTML, JSON, 404 y 405; scripts inline y framing bloqueados.', flush=True)

    seed(590)
    assert request('/index.php?ruta=contacto')[0] == 200
    state = json.loads(request(fixture)[2])
    assert state['autenticado'] and time.time() - state['ultima_actividad'] < 5
    print('OK: una petición antes del límite renueva la actividad.', flush=True)

    old = seed(600)
    cookie = next(c.value for c in jar if c.name == 'PHPSESSID')
    status, headers, _ = request('/index.php?ruta=actualizar_perfil', {
        'csrf_token': old['csrf'], 'nombre': 'No debe guardarse', 'apellido': '',
    })
    assert status == 303 and headers.get('Location', '').endswith('?ruta=ingresar')
    state = json.loads(request(fixture)[2])
    assert not state['autenticado'] and state['csrf'] != old['csrf']
    assert next(c.value for c in jar if c.name == 'PHPSESSID') != cookie
    assert 'Tu sesión venció después de 10 minutos' in request('/index.php?ruta=ingresar')[2]
    assert 'Tu sesión venció después de 10 minutos' not in request('/index.php?ruta=ingresar')[2]
    print('OK: el POST vencido se detiene; cambian ID y CSRF y se muestra el aviso de ingreso.', flush=True)

    old = seed(600, 'admin')
    status, headers, body = request('/index.php?ruta=admin_password', {
        'id_usuario': '1', 'password': 'NoSeDebeGuardar123', 'confirmacion': 'NoSeDebeGuardar123', 'csrf_token': old['csrf'],
    }, {'Accept': 'application/json'})
    assert status == 401 and 'application/json' in headers.get('Content-Type', '')
    assert '10 minutos' in json.loads(body)['error']
    assert not json.loads(request(fixture)[2])['autenticado']
    print('OK: una escritura administrativa con sesión vencida devuelve JSON 401.', flush=True)

    seed()
    for route in ['crear_campana', 'perfil', 'mis_campanas', 'mis_donaciones']:
        status, _, _ = request('/index.php?ruta=' + route)
        assert status == 200, (route, status)
    seed(role='admin')
    assert request('/index.php?ruta=administracion')[0] == 200
    print('OK: navegación autenticada de campañas, perfil y administración.', flush=True)


def main():
    with tempfile.TemporaryDirectory(prefix='todosparauno-http-seguridad-') as name:
        directory = Path(name)
        fixture = '/prueba-' + secrets.token_hex(16)
        router = preparar_router(directory, fixture)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        with (directory / 'php.log').open('w') as log:
            server = subprocess.Popen(['php', '-d', 'session.save_path=' + str(directory), '-S', '127.0.0.1:' + str(port),
                                       '-t', str(ROOT / 'public_php'), str(router)], cwd=ROOT, stdout=log, stderr=log)
            try:
                for attempt in range(60):
                    try:
                        urllib.request.urlopen(f'http://127.0.0.1:{port}/index.php?ruta=ingresar', timeout=2).close()
                        break
                    except urllib.error.URLError:
                        time.sleep(.1)
                comprobar_http(f'http://127.0.0.1:{port}', fixture)
            finally:
                server.terminate()
                server.wait(timeout=5)


if __name__ == '__main__':
    main()
