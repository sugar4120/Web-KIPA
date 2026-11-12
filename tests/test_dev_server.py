import http.client
import threading
import unittest
from http.server import HTTPServer

from dev_server import KipaDevHandler


class DevServerTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.server = HTTPServer(('127.0.0.1', 0), KipaDevHandler)
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True)
        cls.thread.start()

    @classmethod
    def tearDownClass(cls):
        cls.server.shutdown()
        cls.thread.join()
        cls.server.server_close()

    def request(self, path, method='GET'):
        connection = http.client.HTTPConnection(*self.server.server_address, timeout=5)
        try:
            connection.request(method, path)
            response = connection.getresponse()
            return response.status, dict(response.getheaders()), response.read()
        finally:
            connection.close()

    def test_admin_routes_open_hosted_backend(self):
        for path in ['/api/admin.php', '/api/cms.php']:
            for method in ['GET', 'HEAD']:
                with self.subTest(path=path, method=method):
                    status, headers, body = self.request(path, method)
                    self.assertEqual(status, 302)
                    self.assertEqual(headers['Location'], 'https://kipaviethan.edu.vn' + path)
                    self.assertEqual(body, b'')

    def test_query_parameters_are_not_forwarded_to_hosting(self):
        status, headers, _ = self.request('/api/admin.php?password=not-a-real-password')
        self.assertEqual(status, 302)
        self.assertEqual(headers['Location'], 'https://kipaviethan.edu.vn/api/admin.php')

    def test_php_sources_are_never_downloaded(self):
        for path in [
            '/api/bootstrap.php', '/api/submit.php', '/backend/admin_account.php',
            '/api/%62ootstrap.php', '/api/../api/admin.php', '/api/admin.php/',
        ]:
            with self.subTest(path=path):
                status, _, body = self.request(path)
                self.assertEqual(status, 403)
                self.assertNotIn(b'<?php', body)

    def test_paths_outside_website_are_rejected(self):
        self.assertEqual(self.request('/../kipa-private/config.txt')[0], 403)

    def test_website_and_login_link_remain_available(self):
        status, headers, body = self.request('/dang-ky-tu-van/')
        self.assertEqual(status, 200)
        self.assertIn('text/html', headers['Content-Type'])
        self.assertIn(b'href="/api/admin.php"', body)


if __name__ == '__main__':
    unittest.main()
