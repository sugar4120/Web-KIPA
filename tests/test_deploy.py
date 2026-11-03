import contextlib
import io
import re
import ssl
import tempfile
import unittest
from ftplib import error_perm
from pathlib import Path, PurePosixPath
from unittest.mock import MagicMock, patch

import build
import deploy_ftps


class DeploymentTests(unittest.TestCase):
    def test_rejects_private_file_and_incomplete_build(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory)
            (path / '.env').write_text('PRIVATE')
            with self.assertRaisesRegex(ValueError, 'Unexpected file'):
                deploy_ftps.public_files(path)
            (path / '.env').unlink()
            with self.assertRaisesRegex(ValueError, 'Incomplete public build'):
                deploy_ftps.public_files(path)

    def test_remote_path_validation(self):
        for invalid in ['public_html', '/public_html/../private', '/public_html\r\nDELE index.html']:
            with self.subTest(path=invalid), self.assertRaises(ValueError):
                deploy_ftps.remote_root(invalid)
        self.assertEqual(deploy_ftps.remote_root('/public_html/'), PurePosixPath('/public_html'))

    def test_directory_permissions_are_not_silently_ignored(self):
        ftp = MagicMock()
        ftp.cwd.side_effect = error_perm('530 Not logged in')
        with self.assertRaises(error_perm):
            deploy_ftps.ensure_directory(ftp, PurePosixPath('/public_html'), set())
        ftp.mkd.assert_not_called()

    def test_upload_uses_verified_tls_and_never_deletes_remote_files(self):
        with tempfile.TemporaryDirectory() as directory:
            source = Path(directory) / 'site'
            with contextlib.redirect_stdout(io.StringIO()):
                build.build_site(source)
            with patch.dict('os.environ', {
                'FTP_HOST': 'ftp.example.com', 'FTP_USER': 'test-user',
                'FTP_PASSWORD': 'test-only', 'FTP_REMOTE_DIR': '/public_html',
            }, clear=True), patch('deploy_ftps.FTP_TLS') as factory:
                ftp = factory.return_value.__enter__.return_value
                with contextlib.redirect_stdout(io.StringIO()):
                    deploy_ftps.deploy(source)
                context = factory.call_args.kwargs['context']
                self.assertEqual(context.verify_mode, ssl.CERT_REQUIRED)
                self.assertTrue(context.check_hostname)
                ftp.connect.assert_called_once_with('ftp.example.com', 21)
                ftp.prot_p.assert_called_once()
                self.assertEqual(ftp.storbinary.call_count, len(deploy_ftps.public_files(source)))
                uploaded = [call.args[0] for call in ftp.storbinary.call_args_list]
                self.assertEqual(uploaded[-1], 'STOR /public_html/.htaccess')
                ftp.delete.assert_not_called()
                ftp.rmd.assert_not_called()
                ftp.rename.assert_not_called()

    def test_upload_failure_does_not_report_success(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory).resolve() / 'index.html'
            path.write_text('test', encoding='utf-8')
            with patch.dict('os.environ', {
                'FTP_HOST': 'ftp.example.com', 'FTP_USER': 'test-user',
                'FTP_PASSWORD': 'test-only', 'FTP_REMOTE_DIR': '/public_html',
            }, clear=True), patch('deploy_ftps.public_files', return_value=[path]), patch('deploy_ftps.FTP_TLS') as factory:
                ftp = factory.return_value.__enter__.return_value
                ftp.storbinary.side_effect = error_perm('550 Permission denied')
                output = io.StringIO()
                with contextlib.redirect_stdout(output), self.assertRaises(error_perm):
                    deploy_ftps.deploy(directory)
                self.assertNotIn('Deployed', output.getvalue())

    def test_static_hosting_pauses_legacy_php_without_deleting_it(self):
        rewrite_rules = [
            line.strip()
            for line in (Path(build.BASE_DIR) / '.htaccess').read_text(encoding='utf-8').splitlines()
            if line.strip().startswith('RewriteRule')
        ]
        self.assertIn(r'RewriteRule ^(?!api/).*\.php$ - [F,L,NC]', rewrite_rules)
        rule = next(line for line in rewrite_rules if r'(?!api/)' in line)
        expression = re.compile(rule.split()[1], re.IGNORECASE)
        for path in ['wp-login.php', 'wp-admin/admin-ajax.php', 'wp-config.php', 'index_wp_backup.php']:
            with self.subTest(path=path):
                self.assertIsNotNone(expression.search(path))
        for path in ['api/submit.php', 'index.html', 'wp-content/uploads/logo.jpg']:
            with self.subTest(path=path):
                self.assertIsNone(expression.search(path))

    def test_hidden_rollback_files_are_not_publicly_served(self):
        htaccess = (Path(build.BASE_DIR) / '.htaccess').read_text(encoding='utf-8')
        self.assertIn('<FilesMatch "^\\.ht">', htaccess)
        self.assertIn('Require all denied', htaccess)


if __name__ == '__main__':
    unittest.main()
