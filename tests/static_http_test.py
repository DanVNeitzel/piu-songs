"""Serve the exported site under a repository prefix, without any PHP runtime."""
import functools
import http.server
import json
from pathlib import Path
import threading
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser

ROOT = Path(__file__).resolve().parents[1] / "_site"
PREFIX = "/piu-songs/"

class PublicHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, *_):
        pass

    def do_GET(self):
        if not self.path.startswith(PREFIX):
            self.send_error(404)
            return
        self.path = "/" + self.path[len(PREFIX):]
        super().do_GET()

class Assets(HTMLParser):
    def __init__(self):
        super().__init__()
        self.paths = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == "script" and "src" in values:
            self.paths.append(values["src"])
        if tag == "link" and values.get("rel") == "stylesheet":
            self.paths.append(values["href"])

assert (ROOT / "index.html").is_file(), "Run php export_static.php --media-base=PUBLIC_URL first"
server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), functools.partial(PublicHandler, directory=str(ROOT)))
thread = threading.Thread(target=server.serve_forever, daemon=True)
thread.start()
base = f"http://127.0.0.1:{server.server_port}{PREFIX}"
try:
    html = urllib.request.urlopen(base).read().decode("utf-8-sig")
    assert 'data-mode="static"' in html and ".php" not in html
    parser = Assets()
    parser.feed(html)
    for asset in parser.paths:
        assert not asset.startswith("/"), "Asset path must preserve the repository prefix"
        assert urllib.request.urlopen(urllib.parse.urljoin(base, asset)).status == 200
    data = json.load(urllib.request.urlopen(base + "library-public.json"))
    assert data["version"] == 1 and data["published"]
    assert data["stats"]["total_songs"] > 0 and data["library"]
    assert data["media_base_url"].startswith(("https://", "http://"))
    catalog = json.load(urllib.request.urlopen(base + "songs.json"))
    for channel in catalog["children"][0]["children"]:
        for song in channel["children"]:
            for file in song.get("children", []):
                assert file["path"].startswith(data["media_base_url"]), file["path"]
    assert b'data-mode="static"' in urllib.request.urlopen(base + "regenerate_songs.html").read()
    for private in ["files_config.json", "index.php", "api_generate_songs.php", "app/config.php", ".runtime/metadata.json"]:
        try:
            urllib.request.urlopen(base + private)
            raise AssertionError(f"Private file was exported: {private}")
        except urllib.error.HTTPError as error:
            assert error.code == 404
    size = sum(file.stat().st_size for file in ROOT.rglob("*") if file.is_file())
    assert size < 1024**3, "Export exceeds the GitHub Pages size limit"
    print(f"PASS: static HTTP, repository prefix, assets, public snapshot ({data['stats']['total_songs']} songs), external media, private files excluded ({size / 1024**2:.1f} MB)")
finally:
    server.shutdown()
    server.server_close()
    thread.join()
