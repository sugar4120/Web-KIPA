import json
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class ContentModelTests(unittest.TestCase):
    def test_normalized_plan_has_ten_groups_and_shared_entities(self):
        model = json.loads((ROOT / 'api/content-model.json').read_text(encoding='utf-8'))
        self.assertEqual(set(model['groups']), set('ABCDEFGHIJ'))
        self.assertEqual(len(model['tables']), 85)
        self.assertNotIn('language_courses', model['tables'])
        self.assertNotIn('school_countries', model['tables'])
        self.assertIn('establishment_year', model['tables']['schools']['fields'])
        self.assertIn('consent_confirmed', model['tables']['videos']['fields'])
        for name, table in model['tables'].items():
            with self.subTest(table=name):
                self.assertIn('id', table['fields'])
                if name != 'seo_metadata':
                    self.assertNotIn('seo_title', table['fields'])
                    self.assertNotIn('seo_description', table['fields'])
                for field in table['fields'].values():
                    if 'ref' in field:
                        self.assertIn(field['ref'], set(model['tables']) | {'admins', 'leads'})
        self.assertEqual(model['tables']['seo_metadata']['fields']['robots_index']['default'], 1)

    def test_sql_deliverable_matches_runtime_schema(self):
        generated = subprocess.check_output(
            ['php', str(ROOT / 'api/migrate.php'), '--print'], text=True, encoding='utf-8',
        )
        self.assertEqual(generated, (ROOT / 'backend/content-schema.sql').read_text(encoding='utf-8'))
        self.assertEqual(generated.count('CREATE TABLE IF NOT EXISTS'), 85)
        self.assertNotIn('DROP TABLE', generated)

    def test_private_model_and_migrations_are_not_public_endpoints(self):
        rules = (ROOT / 'api/.htaccess').read_text(encoding='utf-8')
        self.assertIn('cms\\.php$', rules)
        self.assertIn('json', rules)
        self.assertIn('Require all denied', rules)


if __name__ == '__main__':
    unittest.main()
