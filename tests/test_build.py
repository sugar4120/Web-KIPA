import contextlib
import io
import json
import re
import tempfile
import unittest
import xml.etree.ElementTree as ET
from html.parser import HTMLParser
from pathlib import Path

import build
from deploy_ftps import public_files


class FormLabelParser(HTMLParser):
    VOID_ELEMENTS = {
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link',
        'meta', 'param', 'source', 'track', 'wbr',
    }

    def __init__(self):
        super().__init__()
        self.labels_by_for = set()
        self.controls = []
        self.label_depth = 0
        self.hidden_depth = 0
        self.stack = []

    def handle_starttag(self, tag, raw_attrs):
        attrs = dict(raw_attrs)
        if tag == 'label':
            self.label_depth += 1
            if attrs.get('for'):
                self.labels_by_for.add(attrs['for'])
        if tag in {'input', 'select', 'textarea'}:
            self.controls.append({
                'tag': tag,
                'type': attrs.get('type', '').lower(),
                'id': attrs.get('id'),
                'nested_label': self.label_depth > 0,
                'aria_label': attrs.get('aria-label') or attrs.get('aria-labelledby'),
                'title': attrs.get('title'),
                'hidden': (
                    self.hidden_depth > 0
                    or 'hidden' in attrs
                    or attrs.get('aria-hidden', '').lower() == 'true'
                ),
                'line': self.getpos()[0],
            })
        if tag not in self.VOID_ELEMENTS:
            hidden = attrs.get('aria-hidden', '').lower() == 'true'
            self.stack.append((tag, hidden))
            self.hidden_depth += int(hidden)

    def handle_endtag(self, tag):
        for index in range(len(self.stack) - 1, -1, -1):
            open_tag, hidden = self.stack.pop()
            self.hidden_depth -= int(hidden)
            if open_tag == tag:
                if tag == 'label':
                    self.label_depth -= 1
                break


class BuildTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory()
        cls.output = Path(cls.temp.name).resolve() / 'site'
        with contextlib.redirect_stdout(io.StringIO()):
            build.build_site(cls.output)

    @classmethod
    def tearDownClass(cls):
        cls.temp.cleanup()

    def test_all_pages_have_one_layout_and_round_trip_without_growth(self):
        header = build.load_component('header.html')
        footer = build.load_component('footer.html')
        for cfg in build.PAGES_CONFIG:
            with self.subTest(page=cfg['file']):
                path = self.output / cfg['file']
                html = path.read_text(encoding='utf-8')
                self.assertEqual(html.count('<!-- MAIN PAGE CONTENT -->'), 1)
                self.assertEqual(html.count('<!-- SITE HEADER -->'), 1)
                self.assertEqual(html.count('<!-- SITE FOOTER -->'), 1)
                self.assertEqual(len(re.findall(r'<header\b', html)), 1)
                self.assertEqual(len(re.findall(r'<footer\b', html)), 1)
                self.assertEqual(len(re.findall(r'<main\b', html)), 1)
                body = build.extract_body_content(path)
                self.assertEqual(build.render_full_html(cfg, body, header, footer), html)
                if cfg['slug'] and cfg['slug'] != '404':
                    self.assertEqual(
                        (self.output / cfg['slug'] / 'index.html').read_bytes(),
                        path.read_bytes(),
                    )

    def test_nested_legacy_layout_keeps_only_page_content(self):
        cfg = build.PAGES_CONFIG[0]
        content = '<section id="original">Original content</section>'
        inner = build.render_full_html(cfg, content, '<header>old</header>', '<footer>old</footer>')
        legacy_body = re.search(r'<body[^>]*>(.*?)</body>', inner, re.DOTALL).group(1)
        outer = build.render_full_html(cfg, legacy_body, '<header>new</header>', '<footer>new</footer>')
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / 'legacy.html'
            path.write_text(outer, encoding='utf-8')
            self.assertEqual(build.extract_body_content(path), content)

    def test_jsonld_entities_and_course_visibility(self):
        for cfg in build.PAGES_CONFIG:
            with self.subTest(page=cfg['file']):
                html = (self.output / cfg['file']).read_text(encoding='utf-8')
                scripts = re.findall(
                    r'<script type="application/ld\+json">\s*(.*?)</script>',
                    html, re.DOTALL,
                )
                self.assertEqual(len(scripts), 1)
                data = json.loads(scripts[0])
                self.assertEqual(data['@context'], 'https://schema.org')
                organization = data['@graph'][0]
                self.assertEqual(organization['@type'], ['Organization', 'EducationalOrganization'])
                self.assertNotIn('legalName', organization)
                courses = [node for node in data['@graph'] if node['@type'] == 'Course']
                self.assertEqual(len(courses), 5 if cfg['slug'] == 'tieng-han' else 0)
                for course in courses:
                    self.assertIn(course['name'], build.extract_body_content(self.output / cfg['file']))
                    self.assertEqual(course['provider']['@id'], organization['@id'])
                    self.assertNotIn('offers', course)
                breadcrumbs = [
                    node for node in data['@graph'] if node['@type'] == 'BreadcrumbList'
                ]
                self.assertEqual(bool(breadcrumbs), bool(cfg['slug'] and cfg['slug'] not in build.NOINDEX_SLUGS))
                if breadcrumbs:
                    self.assertEqual(breadcrumbs[0]['itemListElement'][-1]['item'], build.page_url(cfg))

    def test_metadata_escaping_and_json_script_safety(self):
        cfg = dict(build.PAGES_CONFIG[0], title='KIPA " & <test>', desc='A "quoted" description')
        html = build.render_full_html(cfg, '<p>page</p>', '', '')
        self.assertIn('<title>KIPA &quot; &amp; &lt;test&gt;</title>', html)
        cfg['slug'] = 'test'
        cfg['title'] = '</script><script>alert(1)</script>'
        data = build.structured_data(cfg)
        self.assertNotIn('</script>', data)
        self.assertEqual(json.loads(data)['@graph'][1]['itemListElement'][1]['name'], cfg['title'])

    def test_sitemap_covers_only_indexable_pages(self):
        tree = ET.parse(self.output / 'sitemap.xml')
        urls = [
            node.text
            for node in tree.findall('{*}url/{*}loc')
        ]
        expected = [
            build.page_url(cfg) for cfg in build.PAGES_CONFIG
            if cfg['slug'] not in build.NOINDEX_SLUGS
        ]
        self.assertCountEqual(urls, expected)
        self.assertEqual(len(urls), len(expected))
        self.assertEqual(len(urls), 20)
        for cfg in build.PAGES_CONFIG:
            html = (self.output / cfg['file']).read_text(encoding='utf-8')
            robots = 'noindex, follow' if cfg['slug'] in build.NOINDEX_SLUGS else 'index, follow'
            self.assertIn(f'<meta name="robots" content="{robots}">', html)
            self.assertIn(f'<link rel="canonical" href="{build.page_url(cfg)}">', html)

    def test_staging_excludes_private_source_files(self):
        names = {path.relative_to(self.output).as_posix() for path in public_files(self.output)}
        for private in [
            '.env', '.env.example', '.git/config', '.github/copilot-instructions.md',
            'KIPA - SEO Plan.xlsx', 'deploy_ftp.py', 'build.py', 'README.md', '.cpanel.yml',
            'backend/schema.sql',
        ]:
            self.assertNotIn(private, names)
        self.assertIn('.htaccess', names)
        self.assertIn('api/submit.php', names)
        self.assertIn('api/admin.php', names)
        self.assertIn('api/bootstrap.php', names)
        self.assertIn('api/cleanup.php', names)
        self.assertIn('api/.htaccess', names)
        self.assertIn('Logo.jpg', names)
        self.assertTrue(any(name.startswith('assets/') for name in names))

    def test_all_consultation_forms_require_explicit_consent_and_use_backend(self):
        forms = {
            'index.html': 'home_consultation',
            'dang-ky-tu-van.html': 'consultation',
            'lien-he.html': 'contact',
            'tieng-han.html': 'korean_course',
            'xuat-khau-lao-dong.html': 'labor',
            'hop-tac-quoc-te.html': 'partnership',
        }
        for filename, form_type in forms.items():
            with self.subTest(page=filename):
                html = (Path(build.BASE_DIR) / filename).read_text(encoding='utf-8')
                matches = re.findall(
                    rf'<form\b(?=[^>]*data-kipa-form="{form_type}")[^>]*>(.*?)</form>',
                    html, re.DOTALL,
                )
                self.assertEqual(len(matches), 1)
                form = matches[0]
                self.assertRegex(form, r'<input\b(?=[^>]*name="consent")(?=[^>]*required)')
                self.assertNotRegex(
                    form,
                    r'<input\b(?=[^>]*name="consent")[^>]*\bchecked\b',
                )
                self.assertRegex(form, r'name="website"')
                self.assertIn('data-form-status', form)
                self.assertNotRegex(
                    html,
                    rf'<form\b(?=[^>]*data-kipa-form="{form_type}")[^>]*\bonsubmit=',
                )
        header = (Path(build.BASE_DIR) / 'header.html').read_text(encoding='utf-8')
        self.assertIn('/assets/js/kipa-forms.js', header)

    def test_form_controls_have_accessible_names(self):
        failures = []
        for path in self.output.glob('*.html'):
            parser = FormLabelParser()
            parser.feed(path.read_text(encoding='utf-8'))
            for control in parser.controls:
                if control['hidden'] or control['type'] == 'hidden':
                    continue
                if not (
                    control['nested_label']
                    or control['aria_label']
                    or control['title']
                    or (control['id'] and control['id'] in parser.labels_by_for)
                ):
                    failures.append(
                        f"{path.name}:{control['line']} {control['tag']}#{control['id'] or '(no id)'}"
                    )
        self.maxDiff = None
        self.assertEqual(failures, [], 'Form controls need an associated label: ' + ', '.join(failures))

    def test_tailwind_is_built_locally_for_every_page(self):
        css_path = Path(build.BASE_DIR) / 'assets/css/kipa.css'
        self.assertTrue(css_path.is_file(), 'Compile the local Tailwind stylesheet before building.')
        for path in self.output.glob('*.html'):
            html = path.read_text(encoding='utf-8')
            self.assertIn('/assets/css/kipa.css', html, f'{path.name}: missing local stylesheet')
            self.assertNotIn('cdn.tailwindcss.com', html, f'{path.name}: runtime Tailwind CDN remains')

    def test_rejects_nonempty_staging_directory(self):
        with self.assertRaisesRegex(ValueError, 'must be empty'):
            build.build_site(self.output)


if __name__ == '__main__':
    unittest.main()
