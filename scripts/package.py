#!/usr/bin/env python3
"""Build the installable plugin zip from an explicit runtime manifest.

Only files WordPress needs at runtime are packaged; tests, demo, docs, scripts
and Compose stay in the git repository and never reach a web server.
"""
from pathlib import Path
import hashlib
import re
import sys
import zipfile

root = Path(__file__).resolve().parent.parent
version = re.search(r'Version:\s*([\d.]+)', (root / 'snow3.php').read_text()).group(1)
out = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else root.parent / 'outputs'
out.mkdir(parents=True, exist_ok=True)
runtime = ['snow3.php', 'uninstall.php', 'includes/options.php', 'LICENSE.md', 'README.md',
           'css/Snow.css', 'js/snow3.js', 'img/Snow1.png', 'img/Snow2.png']
development = ('tests/', 'demo/', 'docs/', 'scripts/', 'compose.yaml')

name = f'Snow3-{version}.zip'
target = out / name
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in runtime:
        info = zipfile.ZipInfo('Snow3/' + path, (2026, 10, 19, 0, 0, 0))
        info.external_attr = 0o100644 << 16
        archive.writestr(info, (root / path).read_bytes(), compress_type=zipfile.ZIP_DEFLATED)
with zipfile.ZipFile(target) as archive:
    names = archive.namelist()
    assert archive.testzip() is None
    assert names == ['Snow3/' + path for path in runtime]
    leaked = [n for n in names if n[len('Snow3/'):].startswith(development)]
    assert not leaked, f'development files in plugin zip: {leaked}'
digest = hashlib.sha256(target.read_bytes()).hexdigest()
(out / 'SHA256SUMS').write_text(f'{digest}  {name}\n')
print(f'{name}: {len(runtime)} files, {target.stat().st_size} bytes')
