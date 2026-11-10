#!/usr/bin/env python3
"""Revisión en Firefox del editor real, sin publicar campañas ni usar servicios externos.

Requiere PHP y Firefox. Ejecutar desde cualquier directorio:
    python3 tests/ui/crear_campana_editor_check.py
Las capturas quedan en un directorio temporal que se informa al terminar.
"""
import base64
import json
from pathlib import Path
import re
import socket
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]


def fixture(destination):
    # Solo prepara datos de vista. No inicia sesión ni modifica la aplicación o la base.
    code = '''<?php
$rutaAssets = %s; $rutaBase = '.'; $usuarioActual = null;
$errores = []; $csrfToken = 'prueba-local'; $clave = str_repeat('a', 64);
$categorias = [['id'=>1,'nombre'=>'Comunidad']];
$categoriasInsumos = [['id'=>1,'nombre'=>'Alimentos']];
$catalogo = ['Buenos Aires'=>['Haedo','La Plata']]; $sugerencias = [];
$entrada = []; $valor = static fn($campo) => $entrada[$campo] ?? '';
$tipoAyuda = 'insumos'; $filas = [['categoria'=>'','nombre'=>'','cantidad'=>'']];
$maxImagenes = 4; $tamanoMaximoImagen = 5242880; $tamanoMaximoTotal = 20971520; $textoTamanoImagen = '5 MB';
require %s;
''' % ("'" + (ROOT / 'public_php/assets').as_uri() + "'", "'" + str(ROOT / 'app/vistas/crear_campana.php').replace("'", "\\'") + "'")
    php = destination / 'vista.php'
    php.write_text(code)
    rendered = subprocess.check_output(['php', str(php)], cwd=ROOT, text=True)
    rendered = re.sub(r'<(?:link|script)[^>]*(?:href|src)="https://unpkg.com/[^>]*>(?:</script>)?', '', rendered)
    page = destination / 'editor.html'
    page.write_text(rendered)
    fallback = destination / 'sin-js.html'
    fallback.write_text(re.sub(r'<script\b[^>]*>.*?</script>', '', rendered, flags=re.S))
    return page, fallback


class Firefox:
    def __init__(self, folder):
        profile = folder / 'firefox'
        profile.mkdir()
        with socket.socket() as available:
            available.bind(('127.0.0.1', 0))
            port = available.getsockname()[1]
        (profile / 'user.js').write_text(f'user_pref("marionette.port", {port});')
        self.process = subprocess.Popen(['firefox', '--headless', '--no-remote', '--profile', str(profile), '--marionette'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        self.connection = None
        for _ in range(120):
            try:
                self.connection = socket.create_connection(('127.0.0.1', port), timeout=1)
                break
            except OSError:
                time.sleep(.25)
        if not self.connection:
            self.process.terminate()
            raise RuntimeError('No se pudo iniciar Firefox')
        self.connection.settimeout(30)
        self.receive()
        self.counter = 0
        self.command('WebDriver:NewSession', {'capabilities': {}})

    def receive(self):
        size = b''
        while not size.endswith(b':'):
            part = self.connection.recv(1)
            if not part:
                raise RuntimeError('Firefox cerró la conexión')
            size += part
        data = b''
        length = int(size[:-1])
        while len(data) < length:
            part = self.connection.recv(length - len(data))
            if not part:
                raise RuntimeError('Respuesta incompleta de Firefox')
            data += part
        return json.loads(data)

    def command(self, name, args):
        self.counter += 1
        data = json.dumps([0, self.counter, name, args]).encode()
        self.connection.sendall(str(len(data)).encode() + b':' + data)
        result = self.receive()
        if result[2]:
            raise RuntimeError(result[2])
        return result[3]

    def js(self, script):
        return self.command('WebDriver:ExecuteScript', {'script': script, 'args': []})['value']

    def wait_for(self, script):
        for _ in range(80):
            if self.js(script):
                return
            time.sleep(.05)
        raise AssertionError('No se cumplió: ' + script)

    def navigate(self, path):
        self.command('WebDriver:Navigate', {'url': path.as_uri()})

    def screenshot(self, path):
        result = self.command('WebDriver:TakeScreenshot', {'full': True})['value']
        path.write_bytes(base64.b64decode(result))

    def close(self):
        try:
            self.command('Marionette:Quit', {'flags': ['eForceQuit']})
        finally:
            self.connection.close()
            self.process.terminate()


def run():
    folder = Path(tempfile.mkdtemp(prefix='solidaria-editor-'))
    page, fallback = fixture(folder)
    browser = Firefox(folder)
    try:
        browser.command('WebDriver:SetWindowRect', {'width': 1440, 'height': 1100})
        browser.navigate(page)
        browser.wait_for("return !!document.querySelector('.crear-editor-listo')")
        assert browser.js("return [...document.querySelectorAll('[data-editor-panel]')].filter(e=>getComputedStyle(e).display!=='none').map(e=>e.dataset.editorPanel)") == ['historia']
        browser.screenshot(folder / 'escritorio-historia.png')
        browser.js("""
            document.getElementById('crear-nombre').value='<script>Mi campaña</script>';
            document.getElementById('crear-descripcion').value='Una historia que conserva sus datos al cambiar de sección.';
            document.getElementById('crear-categoria').value='1';
            document.getElementById('crear-nombre').dispatchEvent(new Event('input',{bubbles:true}));
        """)
        browser.wait_for("return document.querySelector('[data-previa-nombre]').textContent==='<script>Mi campaña</script>'")
        assert browser.js("return document.querySelector('[data-previa-nombre]').children.length===0")
        browser.js("document.querySelector('[data-editor-enlace=objetivos]').click()")
        assert browser.js("return getComputedStyle(document.getElementById('crear-objetivos')).display!=='none'")
        browser.js("document.getElementById('agregar-insumo').click()")
        assert browser.js("return document.querySelectorAll('#lista-insumos .crear-insumo-fila').length") == 2
        browser.js("document.querySelector('#lista-insumos [data-quitar-insumo]').click()")
        assert browser.js("return document.querySelectorAll('#lista-insumos .crear-insumo-fila').length") == 1
        browser.js("document.querySelector('[data-editor-enlace=historia]').click();document.getElementById('crear-tipo').value='dinero';document.getElementById('crear-tipo').dispatchEvent(new Event('change',{bubbles:true}))")
        browser.wait_for("return document.querySelector('[data-editor-enlace=entrega]').closest('li').hidden")
        assert browser.js("return document.getElementById('bloque-insumos').disabled && document.getElementById('bloque-ubicacion').disabled && !document.getElementById('bloque-dinero').disabled")
        browser.js("document.querySelector('[data-editor-enlace=revision]').click();document.getElementById('crear-publicar').click()")
        assert browser.js("return document.activeElement.id") == 'crear-objetivo'
        assert browser.js("return getComputedStyle(document.getElementById('crear-objetivos')).display!=='none'")
        browser.js("document.getElementById('crear-objetivo').value='1234.56';document.getElementById('crear-cbu').value='0000000000000000000001';document.getElementById('crear-objetivo').dispatchEvent(new Event('input',{bubbles:true}))")
        browser.wait_for("return document.querySelector('[data-previa-objetivo]').textContent.includes('1.234,56')")
        browser.js("document.querySelector('[data-editor-enlace=revision]').click()")
        browser.wait_for("return !document.querySelector('[data-editor-listo]').hidden")
        assert browser.js("return document.querySelector('[data-editor-faltantes]').children.length") == 0
        browser.screenshot(folder / 'escritorio-revision.png')
        assert browser.js("const data=new FormData(document.querySelector('.crear-formulario'));return data.has('cbu')&&!data.has('lat')&&![...data.keys()].some(k=>k.startsWith('insumos['))")
        # Las fotos usan el manejador real y la elección de portada debe reflejarse en el editor.
        browser.js("""
            document.querySelector('[data-editor-enlace=fotos]').click();
            const canvas=document.createElement('canvas');canvas.width=400;canvas.height=300;
            const context=canvas.getContext('2d');context.fillStyle='#f4b223';context.fillRect(0,0,400,300);
            const bytes=Uint8Array.from(atob(canvas.toDataURL('image/png').split(',')[1]),c=>c.charCodeAt(0));
            const transfer=new DataTransfer();transfer.items.add(new File([bytes],'primera.png',{type:'image/png'}));transfer.items.add(new File([bytes],'segunda.png',{type:'image/png'}));
            document.getElementById('crear-imagenes').files=transfer.files;
            document.getElementById('crear-imagenes').dispatchEvent(new Event('change',{bubbles:true}));
        """)
        browser.wait_for("return document.querySelectorAll('[data-previa] .imagen-previa:not(.saliendo)').length===2 && !document.querySelector('[data-previa-imagen]').hidden")
        first_cover = browser.js("return document.querySelector('[data-previa-imagen]').src")
        browser.js("document.querySelectorAll('.imagen-hacer-portada')[1].click()")
        browser.wait_for("return document.querySelector('[data-previa-imagen]').src!==" + json.dumps(first_cover))
        browser.js("document.querySelector('.imagen-quitar').click()")
        browser.wait_for("return document.getElementById('crear-imagenes').files.length===1")
        assert browser.js("return document.querySelector('[data-previa-nombre]').textContent") == '<script>Mi campaña</script>'
        # Cambiar a insumos exige otra vez la entrega, incluso si sus controles estaban deshabilitados.
        browser.js("document.querySelector('[data-editor-enlace=historia]').click();document.getElementById('crear-tipo').value='insumos';document.getElementById('crear-tipo').dispatchEvent(new Event('change',{bubbles:true}))")
        browser.wait_for("return !document.querySelector('[data-editor-enlace=entrega]').closest('li').hidden")
        browser.js("""
            const row=document.querySelector('#lista-insumos .crear-insumo-fila');
            row.querySelector('select').value='1';row.querySelector('[name$="[nombre]"]').value='Leche (litros)';row.querySelector('[name$="[cantidad]"]').value='12.5';
            document.getElementById('provincia').value='Buenos Aires';document.getElementById('provincia').dispatchEvent(new Event('change',{bubbles:true}));document.getElementById('localidad').value='Haedo';
            document.querySelector('[data-editor-enlace=revision]').click();document.getElementById('crear-publicar').click();
        """)
        assert browser.js("return document.activeElement.id") == 'buscar-direccion'
        assert browser.js("return !document.querySelector('[data-editor-panel=entrega]').hasAttribute('data-editor-inactivo')")
        browser.js("document.getElementById('latitud').value='-34.640';document.getElementById('longitud').value='-58.602';document.dispatchEvent(new Event('crear:entrega-actualizada'))")
        browser.wait_for("return document.querySelector('[data-editor-listo]').hidden===false")
        browser.js("document.querySelector('[data-editor-enlace=revision]').click()")
        # Interceptar el último envío: nunca enviar un POST desde esta prueba.
        browser.js("document.querySelector('form.crear-formulario').addEventListener('submit',e=>{window.publicacionValida=!e.defaultPrevented;e.preventDefault()});document.getElementById('crear-publicar').click()")
        assert browser.js('return window.publicacionValida') is True
        assert browser.js("return !document.getElementById('crear-publicar').disabled")
        assert browser.js("const data=new FormData(document.querySelector('.crear-formulario'));return !data.has('cbu')&&data.get('lat')==='-34.640'&&[...data.keys()].some(k=>k.startsWith('insumos['))&&data.getAll('imagenes[]').length===1")
        for width in [768, 1024, 1280, 1440]:
            browser.command('WebDriver:SetWindowRect', {'width': width, 'height': 1100})
            assert browser.js('return document.documentElement.scrollWidth<=innerWidth')
            if width < 1024:
                assert browser.js("return [...document.querySelectorAll('[data-editor-panel]')].every(e=>getComputedStyle(e).display!=='none')")
            else:
                assert browser.js("return [...document.querySelectorAll('[data-editor-panel]')].filter(e=>getComputedStyle(e).display!=='none').length") == 1
        # Un viewport de contenido real de 320 px evita el ancho mínimo de ventana de Firefox.
        browser.js("""
            const html=document.documentElement.outerHTML;
            const frame=document.createElement('iframe');frame.id='movil';frame.style='display:block;border:0;width:320px;height:1000px';frame.srcdoc=html;
            document.body.replaceChildren(frame);document.body.style='margin:0';
        """)
        browser.wait_for("return !!document.getElementById('movil').contentDocument.querySelector('.crear-editor-listo')")
        # srcdoc ya contiene la clase del editor; esperar también la ejecución de sus scripts.
        browser.wait_for("return !document.getElementById('movil').contentDocument.querySelector('[data-editor-panel=historia]').inert")
        mobile = "const d=document.getElementById('movil').contentDocument;const w=document.getElementById('movil').contentWindow;"
        assert browser.js(mobile + "return [...d.querySelectorAll('[data-editor-panel]')].filter(e=>w.getComputedStyle(e).display!=='none').map(e=>e.dataset.editorPanel)") == ['historia','fotos','objetivos','entrega','revision']
        assert browser.js(mobile + 'return d.documentElement.scrollWidth<=320')
        assert browser.js(mobile + "return w.getComputedStyle(d.querySelector('.crear-editor-menu')).display") == 'none'
        browser.screenshot(folder / 'movil-320.png')
        browser.navigate(fallback)
        assert browser.js("return !document.querySelector('form.crear-formulario').noValidate")
        assert browser.js("return [...document.querySelectorAll('[data-editor-panel]')].every(e=>getComputedStyle(e).display!=='none')")
        print('OK: navegación libre, datos conservados, vista previa segura, objetivos dinámicos, cambio de tipo, errores en secciones cerradas, revisión, portada/reordenamiento, envío válido interceptado, móvil vertical sin desbordamiento y alternativa sin JavaScript.')
        print('Capturas:', folder)
    finally:
        browser.close()


if __name__ == '__main__':
    run()
