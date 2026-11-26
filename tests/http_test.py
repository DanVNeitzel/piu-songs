"""Run against: php -S 127.0.0.1:8765 router.php (no real catalog publication)."""
import http.cookiejar
import json
from pathlib import Path
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8765"
ROOT = Path(__file__).resolve().parents[1]
cookies = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))

def request(path, data=None, token=None, opener=client):
    headers = {"X-CSRF-Token": token} if token else {}
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        response = opener.open(urllib.request.Request(BASE + path, body, headers), timeout=180)
    except urllib.error.HTTPError as response:
        return response.code, response.read()
    return response.status, response.read()

before = (ROOT / "songs.json").read_bytes()
assert request("/files_config.json")[0] == 403
assert request("/.runtime/metadata.json")[0] == 403
assert request("/app/config.php")[0] == 403
assert request("/api_generate_songs.php")[0] == 401
assert request("/generate_difficulty_zone.php")[0] == 401
assert b'name="p"' in request("/index.php?do=emptytrash")[1]
password = json.loads((ROOT / "files_config.json").read_text(encoding="utf-8-sig")).get("SENHA", "abc123")
code, body = request("/index.php", {"p": password})
assert code == 200 and b"window.piuClient" in body, (code, body[:120])
assert b"assets/manager-3.js" in body and b"assets/manager.css" in body
code, body = request("/api_generate_songs.php")
assert code == 200
token = json.loads(body)["xsrf"]
assert request("/index.php?do=emptytrash")[0] == 405
assert request("/index.php", {"do": "emptytrash"})[0] == 403
assert request("/api_generate_songs.php", {"action": "publish"})[0] == 403
assert request("/api_generate_songs.php", {"action": "publish"}, token)[0] == 400
assert request("/index.php?do=folderinfo&folder=../")[0] == 403
assert request("/index.php", {"do": "mkdir", "name": "../outside", "file": ".", "xsrf": token})[0] == 400
assert request("/index.php", {"do": "createfile", "name": "../blocked.PHP", "file": ".", "xsrf": token})[0] == 400
code, body = request("/index.php?do=list&file=Songs")
assert code == 200 and isinstance(json.loads(body), dict), body[:100]
code, body = request("/api_generate_songs.php", {"action": "preview"}, token)
result = json.loads(body)
assert code == 200 and result["success"] and not result["published"], result.get("error")
assert result["stats"]["total_songs"] > 0 and len(result["fingerprint"]) == 64
assert result["stats"]["cache_hits"] > 0
assert request("/api_generate_songs.php", {"action": "publish", "fingerprint": "0" * 64}, token)[0] == 409
assert (ROOT / "songs.json").read_bytes() == before, "HTTP tests changed the published catalog"
print(f"PASS: HTTP login, protected paths, CSRF, POST enforcement, listing, preview ({result['stats']['total_songs']} songs), stale publication rejected")
