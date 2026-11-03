<?php
declare(strict_types=1);

if (!defined('KIPA_API_BOOTSTRAP')) {
    http_response_code(404);
    exit;
}

const KIPA_PUBLIC_CONTENT = [
    'programs' => 'Chương trình', 'countries' => 'Quốc gia', 'schools' => 'Trường học',
    'study_program_details' => 'Hệ học du học', 'work_programs' => 'Việc làm quốc tế',
    'partnership_programs' => 'Hợp tác giáo dục',
    'partners' => 'Đối tác', 'courses' => 'Khóa học tiếng Hàn', 'teachers' => 'Giáo viên',
    'locations' => 'Cơ sở KIPA', 'posts' => 'Tin tức', 'pages' => 'Thông tin KIPA',
    'events' => 'Sự kiện', 'faqs' => 'Hỏi đáp', 'policies' => 'Chính sách',
    'testimonials' => 'Cảm nhận học viên', 'success_stories' => 'Câu chuyện học viên',
    'videos' => 'Video', 'activities' => 'Hoạt động',
];

function kipa_public_url(string $table, array $row): string
{
    $slug = $row['slug'] ?? (string)$row['id'];
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
        throw new RuntimeException('Invalid content slug in ' . $table);
    }
    return ($table === 'posts' ? '/tin-tuc/' : '/noi-dung/' . str_replace('_', '-', $table) . '/') . $slug . '/';
}

function kipa_public_link(string $url): string
{
    if (!preg_match('~^/(?!/)[^\s\\\\]*$~D', $url) && (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https')) {
        throw new RuntimeException('Unsafe public URL.');
    }
    return kipa_h($url);
}

function kipa_public_page(string $title, string $body, string $url, array $seo, string $navigation, array $branding = []): string
{
    $canonical = $seo['canonical_url'] ?? ('https://kipaviethan.edu.vn' . $url);
    if (str_starts_with($canonical, '/')) {
        $canonical = 'https://kipaviethan.edu.vn' . $canonical;
    }
    $description = (string)($seo['meta_description'] ?? 'Thông tin chương trình, khóa học và hoạt động của KIPA Việt Hàn.');
    $schema = '';
    if (!empty($seo['schema_json'])) {
        $schema = '<script type="application/ld+json">' . json_encode(
            json_decode($seo['schema_json'], true, 512, JSON_THROW_ON_ERROR),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        ) . '</script>';
    }
    if (!empty($seo['og_image'])) {
        $image = str_starts_with($seo['og_image'], '/') ? 'https://kipaviethan.edu.vn' . $seo['og_image'] : $seo['og_image'];
        $schema .= '<meta property="og:image" content="' . kipa_public_link($image) . '">';
    }
    $metaTitle = (string)(($seo['meta_title'] ?? '') ?: $title);
    $robots = (($seo['robots_index'] ?? 1) ? 'index' : 'noindex') . ',' . (($seo['robots_follow'] ?? 1) ? 'follow' : 'nofollow');
    $siteName = kipa_h((string)($branding['site_title'] ?? 'KIPA VIỆT HÀN'));
    $contact = '';
    if (!empty($branding['hotline'])) {
        $contact .= '<p>Hotline: ' . kipa_h($branding['hotline']) . '</p>';
    }
    if (!empty($branding['email'])) {
        $contact .= '<p>Email: ' . kipa_h($branding['email']) . '</p>';
    }
    return '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>' . kipa_h($metaTitle) . ' | KIPA Việt Hàn</title><meta name="description" content="' . kipa_h($description) .
        '"><meta name="robots" content="' . $robots . '"><link rel="canonical" href="' . kipa_public_link($canonical) .
        '"><meta property="og:title" content="' . kipa_h((string)(($seo['og_title'] ?? '') ?: $metaTitle)) .
        '"><meta property="og:description" content="' . kipa_h((string)(($seo['og_description'] ?? '') ?: $description)) .
        '"><meta property="og:url" content="' . kipa_public_link($canonical) . '">' . $schema .
        '<style>*{box-sizing:border-box}body{margin:0;color:#12263e;background:#f5f8fc;font:16px/1.75 system-ui,sans-serif}a{color:#0274bd}header,footer{background:#081d36;color:white;padding:22px max(5vw,16px)}header a,footer a{color:white}header{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}nav{display:flex;gap:18px;flex-wrap:wrap}main{max-width:1120px;padding:40px 20px;margin:auto;min-height:60vh}h1{font-size:clamp(28px,5vw,42px);line-height:1.25;color:#081d36}h2{font-size:23px}p{overflow-wrap:anywhere}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}.card,article{background:white;border:1px solid #dfe8f1;border-radius:16px;padding:24px;margin:18px 0}.card h2{margin-top:0}.eyebrow{color:#0274bd;font-weight:700}img{max-width:100%;height:auto;border-radius:12px}.cover{width:100%;max-height:420px;object-fit:cover}.button{display:inline-block;background:#ec1e1b;color:white;padding:10px 20px;border-radius:9px;text-decoration:none}.muted{color:#64748b;font-size:14px}dl{display:grid;grid-template-columns:minmax(100px,1fr) 3fr;gap:12px}dt{font-weight:bold}dd{margin:0;overflow-wrap:anywhere}</style></head><body><header><a href="/"><strong>' . $siteName . '</strong></a><nav><a href="/noi-dung/">Nội dung</a><a href="/tin-tuc/">Tin tức</a>' .
        $navigation . '<a href="/dang-ky-tu-van/">Đăng ký tư vấn</a></nav></header><main>' . ($branding['banner_html'] ?? '') . '<p class="eyebrow">KIPA · THÔNG TIN CHÍNH THỨC</p><h1>' .
        kipa_h($title) . '</h1>' . $body . '</main><footer><p>Công ty Cổ phần Hợp tác Quốc tế KIPA Việt Hàn</p>' . $contact . '<a href="/lien-he/">Liên hệ KIPA</a> · <a href="/chinh-sach-bao-mat/">Chính sách bảo mật</a></footer></body></html>';
}

function kipa_public_text(string $text): string
{
    $paragraphs = preg_split('/\n\s*\n/', trim($text));
    return implode('', array_map(fn($paragraph) => '<p>' . nl2br(kipa_h($paragraph)) . '</p>', $paragraphs));
}

function kipa_publish_row(PDO $db, string $table, array $row): string
{
    $body = '';
    $image = $row['featured_image'] ?? $row['thumbnail'] ?? $row['banner_image'] ?? $row['student_avatar'] ?? null;
    if ($image) {
        $body .= '<img class="cover" src="' . kipa_public_link($image) . '" alt="' . kipa_h(kipa_content_title($row)) . '">';
    }
    foreach (['short_description', 'excerpt', 'short_answer', 'summary', 'description', 'content', 'answer',
        'introduction', 'overview', 'objectives', 'benefits', 'job_description',
        'learning_objectives', 'course_content', 'entry_requirement', 'outcome',
        'story_content', 'review_content', 'achievement_description', 'transcript'] as $name) {
        if (!empty($row[$name])) {
            $body .= kipa_public_text((string)$row[$name]);
        }
    }
    if ($table === 'videos' && !empty($row['video_url'])) {
        $body .= '<p><a class="button" rel="noopener noreferrer" href="' . kipa_public_link($row['video_url']) . '">Xem video</a></p>';
    }
    $children = [
        'locations' => ['location_addresses' => ['full_address', 'address_line', 'province', 'country'], 'location_contacts' => ['contact_label', 'contact_value'], 'location_services' => ['service_name', 'short_description']],
        'courses' => ['course_roadmaps' => ['roadmap_name', 'description'], 'course_faqs' => ['question', 'answer'],
            'course_intakes' => ['intake_name', 'start_date', 'registration_deadline', 'schedule_description'],
            'course_fees' => ['fee_name', 'amount', 'currency_code', 'payment_type', 'description']],
        'schools' => ['school_programs' => ['program_name', 'education_level', 'admission_requirement', 'tuition_fee_min', 'tuition_fee_max', 'currency_code', 'official_source_url', 'last_verified_at']],
    ];
    foreach ($children[$table] ?? [] as $child => $fields) {
        $fk = ['locations' => 'location_id', 'courses' => 'course_id', 'schools' => 'school_id'][$table];
        $status = $child === 'course_intakes' ? "status IN ('upcoming','open')" : "status IN ('published','active')";
        $query = $db->prepare('SELECT * FROM ' . kipa_content_table($child) . " WHERE `$fk` = ? AND $status ORDER BY id");
        $query->execute([$row['id']]);
        foreach ($query->fetchAll() as $item) {
            $body .= '<section class="card">';
            foreach ($fields as $field) {
                if (!empty($item[$field])) {
                    $body .= '<p><strong>' . kipa_h(kipa_content_model()['tables'][$child]['fields'][$field]['label']) . ':</strong> ' .
                        nl2br(kipa_h((string)$item[$field])) . '</p>';
                }
            }
            if (in_array($table, ['study_program_details', 'work_programs'], true)) {
                $fk = $table === 'study_program_details' ? 'study_program_id' : 'work_program_id';
                $related = [
                    'program_requirements' => ['title', 'content', 'effective_from', 'effective_to', 'source_url', 'last_verified_at'],
                    'program_fees' => ['fee_name', 'amount_min', 'amount_max', 'currency_code', 'payment_frequency', 'is_estimated', 'source_url', 'effective_from', 'effective_to'],
                    'program_documents' => ['document_name', 'description', 'is_required', 'quantity', 'preparation_note'],
                    'program_process_steps' => ['step_number', 'title', 'description', 'expected_duration'],
                ];
                foreach ($related as $child => $fields) {
                    $query = $db->prepare('SELECT * FROM ' . kipa_content_table($child) . " WHERE `$fk` = ? AND status IN ('published','active') ORDER BY display_order, id");
                    $query->execute([$row['id']]);
                    foreach ($query->fetchAll() as $item) {
                        $body .= '<section class="card">';
                        foreach ($fields as $field) {
                            if ($item[$field] !== null && $item[$field] !== '') {
                                $label = kipa_content_model()['tables'][$child]['fields'][$field]['label'];
                                $value = kipa_content_model()['tables'][$child]['fields'][$field]['type'] === 'BOOLEAN'
                                    ? ((int)$item[$field] ? 'Có' : 'Không') : (string)$item[$field];
                                $body .= '<p><strong>' . kipa_h($label) . ':</strong> ' . nl2br(kipa_h($value)) . '</p>';
                            }
                        }
                        $body .= '</section>';
                    }
                }
            }
            $body .= '</section>';
        }
    }
    return '<article>' . $body . '</article><p><a class="button" href="/dang-ky-tu-van/?notes=' .
        rawurlencode('Tôi muốn tìm hiểu: ' . kipa_content_title($row)) . '">Nhận tư vấn</a></p>';
}

function kipa_publish_write(string $root, string $relative, string $content): void
{
    if (!preg_match('~^(?:[a-z0-9-]+/)*(?:index\.html|sitemap\.xml|\.htaccess|\.kipa-owned)$~D', $relative)) {
        throw new RuntimeException('Invalid generated path.');
    }
    $file = $root . '/' . $relative;
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Cannot create publication directory.');
    }
    if (file_put_contents($file, $content) !== strlen($content)) {
        throw new RuntimeException('Cannot write static publication.');
    }
}

function kipa_publish_remove(string $directory, string $private): void
{
    if (dirname($directory) !== $private || !preg_match('/^cms-(?:stage|backup)-[a-f0-9]{24}$/D', basename($directory))) {
        throw new RuntimeException('Refusing to remove an unmanaged directory.');
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        $ok = $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        if (!$ok) {
            throw new RuntimeException('Cannot remove temporary publication file.');
        }
    }
    if (!rmdir($directory)) {
        throw new RuntimeException('Cannot remove temporary publication directory.');
    }
}

function kipa_publish_redirects(PDO $db, string $rules): string
{
    $start = '# BEGIN KIPA CMS REDIRECTS';
    $end = '# END KIPA CMS REDIRECTS';
    if (substr_count($rules, $start) !== 1 || substr_count($rules, $end) !== 1) {
        throw new RuntimeException('Missing CMS redirect markers in hosting configuration.');
    }
    $rows = $db->query('SELECT * FROM kipa_redirects WHERE is_active = 1 ORDER BY id')->fetchAll();
    $graph = [];
    foreach ($rows as $row) {
        $input = [];
        foreach (kipa_content_model()['tables']['redirects']['fields'] as $name => $field) {
            if (!in_array($name, ['id', 'created_at', 'updated_at'], true) && $row[$name] !== null) {
                $input[$name] = (string)$row[$name];
            }
        }
        kipa_content_validate('redirects', $input);
        $graph[$row['source_url']] = $row['target_url'];
    }
    $block = $start . "\n";
    foreach ($rows as $row) {
        $target = $row['target_url'];
        $visited = [$row['source_url'] => true];
        while (isset($graph[kipa_redirect_node($target)])) {
            $node = kipa_redirect_node($target);
            if (isset($visited[$node])) {
                throw new RuntimeException('Redirect cycle detected.');
            }
            $visited[$node] = true;
            $target = $graph[$node];
        }
        $source = preg_quote(ltrim($row['source_url'], '/'), '~');
        $block .= 'RewriteRule ^' . $source . '$ ' . str_replace('$', '%24', $target) . ' [R=' . (int)$row['redirect_type'] . ",L,NE]\n";
    }
    if (strlen($block) > 65536) {
        throw new RuntimeException('Redirect configuration exceeds 64 KB.');
    }
    return preg_replace_callback('/' . preg_quote($start, '/') . '\n.*?' . preg_quote($end, '/') . '/s',
        fn() => $block . $end, $rules);
}

function kipa_publish(PDO $db, string $public): int
{
    $private = dirname($public) . '/kipa-private';
    if (!is_dir($private) || is_link($public) || is_link($private)) {
        throw new RuntimeException('Invalid publication storage.');
    }
    $lock = fopen($private . '/cms-publish.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('A publication is already running.');
    }
    $token = bin2hex(random_bytes(12));
    $stage = $private . '/cms-stage-' . $token;
    $backup = $private . '/cms-backup-' . $token;
    $swapped = [];
    $committed = false;
    $oldRules = null;
    $rulesChanged = false;
    try {
        if (!mkdir($stage, 0700) || !mkdir($backup, 0700)) {
            throw new RuntimeException('Cannot stage publication.');
        }
        $db->beginTransaction();
        $oldRules = file_get_contents($public . '/.htaccess');
        if ($oldRules === false) {
            throw new RuntimeException('Cannot read hosting configuration.');
        }
        $newRules = kipa_publish_redirects($db, $oldRules);
        if (file_put_contents($stage . '/root-htaccess', $newRules) !== strlen($newRules)
            || !chmod($stage . '/root-htaccess', 0644)) {
            throw new RuntimeException('Cannot stage redirect configuration.');
        }
        $files = 0;
        $items = 0;
        $links = '';
        $sitemap = [];
        $navigation = '';
        $branding = [];
        $settings = $db->query("SELECT setting_key, setting_value FROM kipa_site_settings WHERE is_public = 1")->fetchAll();
        foreach ($settings as $setting) {
            if (in_array($setting['setting_key'], ['site_title', 'hotline', 'email'], true)) {
                $branding[$setting['setting_key']] = $setting['setting_value'];
            }
        }
        $banners = $db->query("SELECT * FROM kipa_banners WHERE is_active = 1 AND position = 'cms_header'
            AND entity_id IS NULL AND (start_at IS NULL OR start_at <= UTC_TIMESTAMP())
            AND (end_at IS NULL OR end_at >= UTC_TIMESTAMP()) ORDER BY display_order, id")->fetchAll();
        $branding['banner_html'] = '';
        foreach ($banners as $banner) {
            $branding['banner_html'] .= '<section class="card">';
            if ($banner['image_desktop']) {
                $branding['banner_html'] .= '<img class="cover" src="' . kipa_public_link($banner['image_desktop']) . '" alt="' . kipa_h((string)$banner['image_alt']) . '">';
            }
            $branding['banner_html'] .= '<h2>' . kipa_h((string)$banner['title']) . '</h2>' . kipa_public_text((string)$banner['subtitle']);
            if ($banner['button_text'] && $banner['button_url']) {
                $branding['banner_html'] .= '<a class="button" href="' . kipa_public_link($banner['button_url']) . '">' . kipa_h($banner['button_text']) . '</a>';
            }
            $branding['banner_html'] .= '</section>';
        }
        $menus = $db->query("SELECT i.link_url, i.label FROM kipa_menu_items i JOIN kipa_menus m ON m.id = i.menu_id
            WHERE i.status IN ('published','active') AND m.status IN ('published','active') AND i.link_url IS NOT NULL ORDER BY i.display_order, i.id")->fetchAll();
        foreach ($menus as $item) {
            $navigation .= '<a href="' . kipa_public_link($item['link_url']) . '">' . kipa_h($item['label']) . '</a>';
        }
        foreach (KIPA_PUBLIC_CONTENT as $table => $label) {
            $statusField = $table === 'events' ? 'publication_status' : 'status';
            $where = $table === 'teachers' ? "status = 'active'" : "`$statusField` = 'published'";
            if ($table === 'partners') {
                $where .= " AND relationship_status = 'active'";
            }
            if (isset(kipa_content_model()['tables'][$table]['fields']['deleted_at'])) {
                $where .= ' AND deleted_at IS NULL';
            }
            if (in_array($table, ['videos', 'testimonials', 'success_stories'], true)) {
                $where .= ' AND consent_confirmed = 1';
            }
            if (isset(kipa_content_model()['tables'][$table]['fields']['published_at'])) {
                $where .= ' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP())';
            }
            $rows = $db->query('SELECT * FROM ' . kipa_content_table($table) . ' WHERE ' . $where . ' ORDER BY id DESC')->fetchAll();
            $cards = '';
            foreach ($rows as $row) {
                $url = kipa_public_url($table, $row);
                $query = $db->prepare('SELECT * FROM kipa_seo_metadata WHERE entity_type = ? AND entity_id = ?');
                $query->execute([$table, $row['id']]);
                $seo = $query->fetch() ?: [];
                $title = kipa_content_title($row);
                $html = kipa_public_page($title, kipa_publish_row($db, $table, $row), $url, $seo, $navigation, $branding);
                kipa_publish_write($stage, ltrim($url, '/') . 'index.html', $html);
                $cards .= '<section class="card"><h2><a href="' . kipa_h($url) . '">' . kipa_h($title) . '</a></h2></section>';
                if ($seo['robots_index'] ?? true) {
                    $sitemap[] = $url;
                }
                $files++;
                $items++;
                if ($files > 10000) {
                    throw new RuntimeException('Publication exceeds 10,000 pages.');
                }
            }
            $base = $table === 'posts' ? '/tin-tuc/' : '/noi-dung/' . str_replace('_', '-', $table) . '/';
            kipa_publish_write($stage, ltrim($base, '/') . 'index.html', kipa_public_page($label,
                $cards !== '' ? '<div class="grid">' . $cards . '</div>' : '<p>Nội dung đang được cập nhật.</p>',
                $base, ['robots_index' => count($rows) > 0, 'robots_follow' => true], $navigation, $branding));
            $links .= '<section class="card"><h2><a href="' . $base . '">' . kipa_h($label) . '</a></h2><p>' . count($rows) . ' nội dung</p></section>';
            if ($rows) {
                $sitemap[] = $base;
            }
            $files++;
        }
        kipa_publish_write($stage, 'noi-dung/index.html', kipa_public_page('Khám phá KIPA Việt Hàn', '<div class="grid">' . $links . '</div>', '/noi-dung/', ['robots_index' => $items > 0, 'robots_follow' => true], $navigation, $branding));
        if ($items > 0) {
            $sitemap[] = '/noi-dung/';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($sitemap as $url) {
            $xml .= '<url><loc>' . htmlspecialchars('https://kipaviethan.edu.vn' . $url, ENT_XML1, 'UTF-8') . '</loc></url>';
        }
        kipa_publish_write($stage, 'noi-dung/sitemap.xml', $xml . '</urlset>');
        foreach (['noi-dung', 'tin-tuc'] as $directory) {
            kipa_publish_write($stage, $directory . '/.kipa-owned', "KIPA-CMS-1\n");
            kipa_publish_write($stage, $directory . '/.htaccess', "Options -Indexes\n<FilesMatch \"^\\\\.\">\nRequire all denied\n</FilesMatch>\n");
            $target = $public . '/' . $directory;
            if (file_exists($target) && (is_link($target) || !is_file($target . '/.kipa-owned') || file_get_contents($target . '/.kipa-owned') !== "KIPA-CMS-1\n")) {
                throw new RuntimeException('Refusing to replace unmanaged directory: ' . $directory);
            }
        }
        $db->commit();
        foreach (['noi-dung', 'tin-tuc'] as $directory) {
            $target = $public . '/' . $directory;
            if (is_dir($target) && !rename($target, $backup . '/' . $directory)) {
                throw new RuntimeException('Cannot back up current publication.');
            }
            $swapped[] = $directory;
            if (!rename($stage . '/' . $directory, $target)) {
                throw new RuntimeException('Cannot activate publication.');
            }
        }
        if (!rename($stage . '/root-htaccess', $public . '/.htaccess')) {
            throw new RuntimeException('Cannot activate redirect configuration.');
        }
        $rulesChanged = true;
        $committed = true;
        return $files + 1;
    } finally {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (!$committed) {
            if ($rulesChanged && $oldRules !== null) {
                $restore = $private . '/cms-restore-' . $token;
                if (file_put_contents($restore, $oldRules) !== strlen($oldRules) || !rename($restore, $public . '/.htaccess')) {
                    throw new RuntimeException('Cannot restore hosting configuration.');
                }
            }
            foreach (array_reverse($swapped) as $directory) {
                $target = $public . '/' . $directory;
                if (is_dir($target) && !rename($target, $stage . '/' . $directory)) {
                    throw new RuntimeException('Publication rollback could not move new files.');
                }
                if (is_dir($backup . '/' . $directory) && !rename($backup . '/' . $directory, $target)) {
                    throw new RuntimeException('Publication rollback could not restore old files.');
                }
            }
        }
        foreach ([$stage, $backup] as $directory) {
            if (is_dir($directory)) {
                kipa_publish_remove($directory, $private);
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
