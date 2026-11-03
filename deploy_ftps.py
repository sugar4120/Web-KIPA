#!/usr/bin/env python3
"""Upload only the built public website using certificate-verified explicit FTPS."""

import argparse
import os
import ssl
from ftplib import FTP_TLS, error_perm
from pathlib import Path, PurePosixPath

from build import PAGES_CONFIG, PUBLIC_ASSET_SUFFIXES


def public_files(directory):
    root = Path(directory).resolve()
    expected = {cfg['file'] for cfg in PAGES_CONFIG}
    expected.update(
        f"{cfg['slug']}/index.html"
        for cfg in PAGES_CONFIG if cfg['slug'] and cfg['slug'] != '404'
    )
    expected.update({
        '.htaccess', 'robots.txt', 'sitemap.xml', 'Logo.jpg',
        'api/.htaccess', 'api/admin.php', 'api/bootstrap.php',
        'api/cleanup.php', 'api/submit.php',
    })
    if not root.is_dir():
        raise ValueError(f'Build directory does not exist: {root}')
    files = []
    found = set()
    for path in root.rglob('*'):
        if path.is_symlink():
            raise ValueError(f'Symbolic links cannot be deployed: {path}')
        if not path.is_file():
            continue
        relative = path.relative_to(root).as_posix()
        is_asset = relative.startswith('assets/') and path.suffix.lower() in PUBLIC_ASSET_SUFFIXES
        if relative not in expected and not is_asset:
            raise ValueError(f'Unexpected file in public build: {relative}')
        found.add(relative)
        files.append(path)
    if expected - found:
        raise ValueError(f'Incomplete public build: {", ".join(sorted(expected - found))}')
    return sorted(files, key=lambda p: (
        3 if p.relative_to(root).as_posix() == '.htaccess'
        else 2 if p.name == '.htaccess'
        else 1 if p.suffix in {'.html', '.php'}
        else 0,
        p.relative_to(root).as_posix(),
    ))


def required_setting(name):
    value = os.environ.get(name, '')
    if not value.strip():
        raise ValueError(f'Missing required environment variable: {name}')
    return value


def remote_root(value):
    if not value.startswith('/') or any(c in value for c in '\r\n\0\\'):
        raise ValueError('FTP_REMOTE_DIR must be an absolute FTP path.')
    if any(part in {'.', '..'} for part in value.split('/')):
        raise ValueError('FTP_REMOTE_DIR cannot contain traversal segments.')
    return PurePosixPath(value)


def ensure_directory(ftp, directory, created):
    if directory in created:
        return
    try:
        ftp.cwd(str(directory))
    except error_perm as error:
        if not str(error).startswith('550'):
            raise
        if directory.parent == directory:
            raise
        ensure_directory(ftp, directory.parent, created)
        ftp.mkd(str(directory))
        ftp.cwd(str(directory))
    created.add(directory)


def deploy(directory):
    source = Path(directory).resolve()
    files = public_files(source)
    host = required_setting('FTP_HOST').strip()
    user = required_setting('FTP_USER')
    password = required_setting('FTP_PASSWORD')
    destination = remote_root(required_setting('FTP_REMOTE_DIR'))
    port = int(os.environ.get('FTP_PORT', '21'))
    if not 1 <= port <= 65535:
        raise ValueError('FTP_PORT must be between 1 and 65535.')

    with FTP_TLS(context=ssl.create_default_context(), timeout=30) as ftp:
        ftp.connect(host, port)
        ftp.login(user, password)
        ftp.prot_p()
        ftp.set_pasv(True)
        created = set()
        for path in files:
            target = destination / path.relative_to(source).as_posix()
            ensure_directory(ftp, target.parent, created)
            with path.open('rb') as stream:
                ftp.storbinary(f'STOR {target}', stream)
            print(f'Uploaded {path.relative_to(source).as_posix()}')
    print(f'Deployed {len(files)} public files. No remote files were deleted.')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('directory', nargs='?', default='dist')
    args = parser.parse_args()
    deploy(args.directory)


if __name__ == '__main__':
    main()
