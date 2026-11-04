import json
import re
import unittest
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent


class LocationsIntegrationTests(unittest.TestCase):
    def setUp(self):
        self.json_path = BASE_DIR / 'backend' / 'locations.json'
        self.co_so_html = (BASE_DIR / 'co-so.html').read_text(encoding='utf-8')

    def test_locations_seed_file_integrity(self):
        self.assertTrue(self.json_path.is_file(), 'backend/locations.json must exist.')
        data = json.loads(self.json_path.read_text(encoding='utf-8'))
        self.assertEqual(len(data), 7, 'Must have exactly 7 verified locations.')

        valid_cities = {'hai-phong', 'hai-duong', 'quang-ninh', 'hcm', 'han-quoc', 'uc'}
        slugs = set()
        codes = set()

        for item in data:
            slug = item['slug']
            self.assertNotIn(slug, slugs, f'Duplicate slug: {slug}')
            slugs.add(slug)

            code = item['code']
            self.assertNotIn(code, codes, f'Duplicate code: {code}')
            codes.add(code)

            self.assertIn(item['city_key'], valid_cities, f'Invalid city_key: {item["city_key"]}')
            self.assertTrue(item['name'], 'Name must not be empty.')
            self.assertTrue(item['address'], 'Address must not be empty.')
            self.assertTrue(item['phone'], 'Phone must not be empty.')
            self.assertTrue(item['hours'], 'Hours must not be empty.')
            self.assertTrue(item['services'], 'Services must not be empty.')

            # Check image existence
            img_path = BASE_DIR / item['image'].lstrip('/')
            self.assertTrue(img_path.is_file(), f'Referenced image must exist: {item["image"]}')

    def test_co_so_html_matches_canonical_locations(self):
        data = json.loads(self.json_path.read_text(encoding='utf-8'))
        for item in data:
            slug = item['slug']
            city = item['city_key']
            # Must find card with data-slug and data-city
            self.assertIn(
                f'data-slug="{slug}"',
                self.co_so_html,
                f'co-so.html must contain card with data-slug="{slug}"',
            )
            self.assertIn(
                f'data-city="{city}"',
                self.co_so_html,
                f'co-so.html must contain card with data-city="{city}"',
            )

    def test_co_so_responsive_layout_and_css_rules(self):
        # Sticky sidebar top offset must be at least 80px to avoid sticky header collision
        self.assertRegex(
            self.co_so_html,
            r'top:\s*(?:9[0-9]|1[0-9]{2})px\s*!important',
            'Sticky map sidebar must have top >= 90px to prevent header collision.',
        )
        # Touch scrolling container
        self.assertIn('filter-scroll-container', self.co_so_html)
        self.assertIn('-webkit-overflow-scrolling: touch', self.co_so_html)
        # Address word break to prevent mobile horizontal overflow
        self.assertIn('break-words', self.co_so_html)


if __name__ == '__main__':
    unittest.main()

