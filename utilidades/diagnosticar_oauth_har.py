#!/usr/bin/env python3
"""Lee un HAR local y emite solo campos permitidos para diagnosticar OAuth.

Uso: python3 utilidades/diagnosticar_oauth_har.py archivo.har
No hace solicitudes de red ni modifica el HAR. No compartir el HAR original.
"""
import json
import sys
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

APP_ID = "6921561117397304"
RETORNOS = (
    "https://todospara.uno/prueba-mp/retorno.php",
    "https://todospara.uno/prueba-mp/",
)
HOSTS = {
    "auth.mercadopago.com", "auth.mercadopago.com.ar",
    "www.mercadopago.com", "www.mercadopago.com.ar",
}


def resumir_url(url, profundidad=0):
    """Nunca devuelve la URL original, parámetros arbitrarios o IDs de sesión."""
    if profundidad > 3 or not isinstance(url, str):
        return None
    try:
        u = urlsplit(url)
        if u.hostname not in HOSTS:
            return None
        q = parse_qs(u.query, keep_blank_values=True)
    except ValueError:
        return None
    ruta = u.path
    if ruta.startswith("/session/replicator/"):
        ruta = "/session/replicator/[omitido]"
    elif ruta not in ("/authorization", "/login/identification", "/login", "/authorization/"):
        ruta = "[otra ruta]"
    resultado = {"host": u.hostname, "ruta": ruta}
    if u.path.rstrip("/") == "/authorization":
        retorno = q.get("redirect_uri", [])
        cliente = q.get("client_id", [])
        resultado["oauth"] = {
            "client_id_presente": bool(cliente),
            "client_id_actual_exacto": cliente == [APP_ID],
            "redirect_uri_presente": bool(retorno),
            "redirect_uri_original_exacto": retorno == [RETORNOS[0]],
            "redirect_uri_alternativo_exacto": retorno == [RETORNOS[1]],
            "redirect_uri_longitudes": [len(v) for v in retorno],
            "redirect_uri_contiene_porcentaje_tras_decodificar": any("%" in v for v in retorno),
            "response_type_code": q.get("response_type") == ["code"],
            "state_presente_no_vacio": bool(q.get("state", [""])[0]),
            "challenge_presente_no_vacio": bool(q.get("code_challenge", [""])[0]),
            "challenge_longitudes": [len(v) for v in q.get("code_challenge", [])],
            "metodo_s256": q.get("code_challenge_method") == ["S256"],
            "campos_duplicados": [k for k in (
                "client_id", "redirect_uri", "response_type", "state",
                "code_challenge", "code_challenge_method", "platform_id",
            ) if len(q.get(k, [])) > 1],
        }
    for campo in ("go", "redirect_url", "return_url", "continue"):
        for valor in q.get(campo, [])[:2]:
            hijo = resumir_url(valor, profundidad + 1)
            if hijo:
                resultado.setdefault("continuaciones", []).append(hijo)
    return resultado


def analizar(har):
    filas = []
    for entrada in har.get("log", {}).get("entries", []):
        peticion = entrada.get("request", {})
        resumen = resumir_url(peticion.get("url", ""))
        if resumen is not None:
            estado = entrada.get("response", {}).get("status")
            filas.append({"orden": len(filas) + 1,
                          "estado": estado if isinstance(estado, int) else None,
                          "solicitud": resumen})
    return {"solicitudes": filas,
            "limites": "Solo estructura de URLs y estados. No verifica el backend de MP, "
                       "la identidad de la cuenta ni el contenido del error. "
                       "Los enlaces anidados no son solicitudes independientes."}


def main():
    if len(sys.argv) != 2:
        print("Uso: python3 utilidades/diagnosticar_oauth_har.py archivo.har", file=sys.stderr)
        return 2
    try:
        contenido = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8-sig"))
        if not isinstance(contenido, dict) or not isinstance(contenido.get("log"), dict):
            raise ValueError
        resultado = analizar(contenido)
    except (OSError, ValueError, TypeError):
        print("No se pudo leer un HAR válido. No se imprime su contenido.", file=sys.stderr)
        return 1
    print(json.dumps(resultado, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
