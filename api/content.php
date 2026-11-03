<?php
declare(strict_types=1);

if (!defined('KIPA_API_BOOTSTRAP')) {
    http_response_code(404);
    exit;
}

function kipa_content_model(): array
{
    static $model;
    if ($model === null) {
        $json = file_get_contents(__DIR__ . '/content-model.json');
        if ($json === false) {
            throw new RuntimeException('Content model is missing.');
        }
        $model = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
    return $model;
}

function kipa_content_table(string $name): string
{
    if (!isset(kipa_content_model()['tables'][$name]) && !in_array($name, ['leads', 'admins'], true)) {
        throw new InvalidArgumentException('Bảng dữ liệu không hợp lệ.');
    }
    return '`kipa_' . $name . '`';
}

function kipa_content_ready(PDO $db): bool
{
    $query = $db->query("SHOW TABLES LIKE 'kipa_content_state'");
    if ($query->fetchColumn() === false) {
        return false;
    }
    $state = $db->query('SELECT schema_version, model_hash FROM kipa_content_state WHERE id = 1' .
        ($db->inTransaction() ? ' LOCK IN SHARE MODE' : ''))->fetch();
    if (!$state) {
        return false;
    }
    if ((int)$state['schema_version'] !== 1 || $state['model_hash'] !== hash_file('sha256', __DIR__ . '/content-model.json')) {
        throw new RuntimeException('Content schema does not match the deployed model.');
    }
    return true;
}

function kipa_content_title(array $row): string
{
    foreach (['name', 'title', 'course_name', 'location_name', 'page_title', 'event_name',
        'question', 'story_title', 'student_name', 'program_name', 'partner_name', 'partnership_name', 'activity_name',
        'teacher_name', 'author_name', 'full_name', 'setting_key', 'label'] as $key) {
        if (isset($row[$key]) && $row[$key] !== '') {
            return (string)$row[$key];
        }
    }
    return '#' . ($row['id'] ?? '');
}

function kipa_redirect_node(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null || in_array(strtolower($host), ['kipaviethan.edu.vn', 'www.kipaviethan.edu.vn'], true)) {
        return (string)(parse_url($url, PHP_URL_PATH) ?: '/');
    }
    return $url;
}

function kipa_content_validate(string $table, array $input): array
{
    $model = kipa_content_model()['tables'][$table] ?? null;
    if (!$model) {
        throw new InvalidArgumentException('Bảng dữ liệu không hợp lệ.');
    }
    $values = [];
    foreach ($input as $name => $value) {
        if (!isset($model['fields'][$name]) || in_array($name, ['id', 'created_at', 'updated_at', 'legacy_lead_id'], true)) {
            throw new InvalidArgumentException('Trường dữ liệu không được hỗ trợ: ' . $name);
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException('Định dạng dữ liệu không hợp lệ.');
        }
    }
    foreach ($model['fields'] as $name => $field) {
        if (in_array($name, ['id', 'created_at', 'updated_at', 'legacy_lead_id'], true)) {
            continue;
        }
        $value = trim($input[$name] ?? (string)($field['default'] ?? ''));
        if ($value === '') {
            if ($field['required'] && !array_key_exists('default', $field)) {
                throw new InvalidArgumentException('Vui lòng nhập: ' . $field['label']);
            }
            $values[$name] = array_key_exists('default', $field) ? (string)$field['default'] : null;
            continue;
        }
        $type = $field['type'];
        if (isset($field['choices']) && !in_array($value, $field['choices'], true)) {
            throw new InvalidArgumentException('Lựa chọn không hợp lệ: ' . $field['label']);
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException('Dữ liệu chứa ký tự điều khiển.');
        }
        if (preg_match('/^(?:VAR)?CHAR\((\d+)\)$/', $type, $match) && kipa_text_length($value) > (int)$match[1]) {
            throw new InvalidArgumentException('Trường quá dài: ' . $field['label']);
        }
        if (strlen($value) > 200000) {
            throw new InvalidArgumentException('Nội dung vượt quá 200 KB.');
        }
        if ($type === 'BOOLEAN' && !in_array($value, ['0', '1'], true)) {
            throw new InvalidArgumentException('Giá trị đúng/sai không hợp lệ.');
        }
        if (preg_match('/^(BIGINT|SMALLINT|TINYINT|INT)/', $type)) {
            if (!preg_match('/^-?\d+$/D', $value) || filter_var($value, FILTER_VALIDATE_INT) === false
                || (str_contains($type, 'UNSIGNED') && (int)$value < 0)) {
                throw new InvalidArgumentException('Số nguyên không hợp lệ: ' . $field['label']);
            }
            $limit = str_starts_with($type, 'SMALLINT') ? 32767 : (str_starts_with($type, 'TINYINT') ? 127 : (str_starts_with($type, 'INT') ? 2147483647 : PHP_INT_MAX));
            if ((int)$value > $limit || (int)$value < -$limit - 1) {
                throw new InvalidArgumentException('Số nguyên vượt giới hạn: ' . $field['label']);
            }
        }
        if (preg_match('/^DECIMAL\((\d+),\s*(\d+)\)$/', $type, $match)) {
            if (!preg_match('/^-?(\d+)(?:\.(\d+))?$/D', $value, $number)
                || strlen(ltrim($number[1], '0')) > (int)$match[1] - (int)$match[2]
                || strlen($number[2] ?? '') > (int)$match[2]) {
                throw new InvalidArgumentException('Số thập phân không hợp lệ: ' . $field['label']);
            }
        }
        if (($name === 'rating' && ((float)$value < 1 || (float)$value > 5))
            || ($name === 'latitude' && abs((float)$value) > 90)
            || ($name === 'longitude' && abs((float)$value) > 180)) {
            throw new InvalidArgumentException('Giá trị vượt khoảng hợp lệ: ' . $field['label']);
        }
        if (in_array($type, ['DATE', 'DATETIME', 'TIMESTAMP', 'TIME'], true)) {
            $value = str_replace('T', ' ', $value);
            if (($type === 'DATETIME' || $type === 'TIMESTAMP') && strlen($value) === 16) {
                $value .= ':00';
            }
            if ($type === 'TIME' && strlen($value) === 5) {
                $value .= ':00';
            }
            $format = $type === 'DATE' ? 'Y-m-d' : ($type === 'TIME' ? 'H:i:s' : 'Y-m-d H:i:s');
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if (!$date || $date->format($format) !== $value) {
                throw new InvalidArgumentException('Ngày giờ không hợp lệ: ' . $field['label']);
            }
        }
        if ($type === 'JSON') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $error) {
                throw new InvalidArgumentException('JSON không hợp lệ: ' . $field['label'], 0, $error);
            }
            if ($name === 'schema_json' && !is_array($decoded)) {
                throw new InvalidArgumentException('Schema JSON phải là object hoặc mảng.');
            }
        }
        if ($name === 'slug' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value)) {
            throw new InvalidArgumentException('Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang.');
        }
        if (($name === 'email' || str_ends_with($name, '_email')) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email không hợp lệ.');
        }
        if (str_ends_with($name, '_url') || in_array($name, ['thumbnail', 'banner_image', 'avatar', 'logo', 'featured_image', 'og_image', 'image_desktop', 'image_mobile', 'image_tablet'], true)) {
            if (!preg_match('~^/(?!/)[^\s\\\\]*$~D', $value) && (!filter_var($value, FILTER_VALIDATE_URL) || parse_url($value, PHP_URL_SCHEME) !== 'https')) {
                throw new InvalidArgumentException('Liên kết phải là HTTPS hoặc đường dẫn nội bộ: ' . $name);
            }
        }
        $values[$name] = $value;
    }
    foreach ($values as $name => $value) {
        if ($value !== null && str_ends_with($name, '_min')) {
            $max = substr($name, 0, -4) . '_max';
            if (isset($values[$max]) && (float)$value > (float)$values[$max]) {
                throw new InvalidArgumentException('Giá trị tối thiểu không được lớn hơn tối đa.');
            }
            foreach ([['effective_from', 'effective_to'], ['start_date', 'end_date'], ['start_datetime', 'end_datetime'], ['start_at', 'end_at']] as [$start, $end]) {
                if (isset($values[$start], $values[$end]) && $values[$start] > $values[$end]) {
                    throw new InvalidArgumentException('Ngày kết thúc phải từ ngày bắt đầu trở đi.');
                }
                if (isset($model['one_of']) && count(array_filter($model['one_of'], fn($name) => isset($values[$name]))) !== 1) {
                    throw new InvalidArgumentException('Chọn đúng một đối tượng: du học, việc làm hoặc khóa học.');
                }
            }
        }
    }
    if ($table === 'site_settings' && preg_match('/pass|secret|token|api.?key|credential|dsn/i', (string)($values['setting_key'] ?? ''))) {
        throw new InvalidArgumentException('Không lưu mật khẩu, token hoặc khóa API trong cấu hình website.');
    }
    if ($table === 'redirects') {
        if (!preg_match('~^/[a-zA-Z0-9/_.-]*$~D', (string)$values['source_url'])
            || preg_match('~/(?:\.|\.\.)(?:/|$)~', (string)$values['source_url'])
            || !in_array((int)$values['redirect_type'], [301, 302, 307, 308], true)
            || $values['source_url'] === kipa_redirect_node((string)$values['target_url'])) {
            throw new InvalidArgumentException('Chuyển hướng không hợp lệ hoặc tự trỏ về chính nó.');
        }
        if (str_starts_with((string)$values['source_url'], '/api/') || str_starts_with((string)$values['source_url'], '/wp-')) {
            throw new InvalidArgumentException('Không chuyển hướng các đường dẫn quản trị hoặc WordPress đang được bảo vệ.');
        }
    }
    if (isset($values['entity_type'], $values['entity_id'])) {
        $entity = $values['entity_type'];
        $aliases = ['page' => 'pages', 'post' => 'posts', 'program' => 'programs',
            'country' => 'countries', 'school' => 'schools', 'course' => 'courses',
            'location' => 'locations', 'video' => 'videos', 'event' => 'events'];
        $values['entity_type'] = $aliases[$entity] ?? $entity;
        kipa_content_table($values['entity_type']);
    }
    return $values;
}

function kipa_content_save(PDO $db, string $table, array $input, ?int $id): int
{
    $values = kipa_content_validate($table, $input);
    $fields = kipa_content_model()['tables'][$table]['fields'];
    if ($table === 'redirects' && $values['is_active'] === '1') {
        $redirects = $db->query('SELECT id, source_url, target_url FROM kipa_redirects WHERE is_active = 1')->fetchAll();
        $graph = [];
        foreach ($redirects as $redirect) {
            if ((int)$redirect['id'] !== $id) {
                $graph[$redirect['source_url']] = kipa_redirect_node($redirect['target_url']);
            }
        }
        $graph[$values['source_url']] = kipa_redirect_node($values['target_url']);
        foreach (array_keys($graph) as $start) {
            $visited = [];
            $node = $start;
            while (isset($graph[$node])) {
                if (isset($visited[$node])) {
                    throw new InvalidArgumentException('Chuyển hướng tạo vòng lặp.');
                }
                $visited[$node] = true;
                $node = $graph[$node];
            }
        }
    }
    foreach ($fields as $name => $field) {
        if (isset($field['ref']) && isset($values[$name])) {
            if ($field['ref'] === $table && (int)$values[$name] === $id) {
                throw new InvalidArgumentException('Bản ghi không thể tự liên kết với chính nó.');
            }
            $query = $db->prepare('SELECT id FROM ' . kipa_content_table($field['ref']) . ' WHERE id = ?');
            $query->execute([$values[$name]]);
            if (!$query->fetchColumn()) {
                throw new InvalidArgumentException('Không tìm thấy bản ghi liên kết: ' . $field['label']);
            }
            if ($name === 'parent_id' && $id !== null) {
                $parent = (int)$values[$name];
                $visited = [$id => true];
                while ($parent !== 0) {
                    if (isset($visited[$parent])) {
                        throw new InvalidArgumentException('Liên kết cấp cha tạo vòng lặp.');
                    }
                    $visited[$parent] = true;
                    $ancestor = $db->prepare('SELECT parent_id FROM ' . kipa_content_table($table) . ' WHERE id = ?');
                    $ancestor->execute([$parent]);
                    $parent = (int)$ancestor->fetchColumn();
                }
            }
        }
    }
    if (isset($values['entity_type'], $values['entity_id'])) {
        $query = $db->prepare('SELECT id FROM ' . kipa_content_table($values['entity_type']) . ' WHERE id = ?');
        $query->execute([$values['entity_id']]);
        if (!$query->fetchColumn()) {
            throw new InvalidArgumentException('Nội dung được tham chiếu không tồn tại.');
        }
    }
    if (isset($fields['updated_at'])) {
        $values['updated_at'] = gmdate('Y-m-d H:i:s');
    }
    if ($id === null && isset($fields['created_at'])) {
        $values['created_at'] = gmdate('Y-m-d H:i:s');
    }
    $names = array_keys($values);
    if ($id === null) {
        $sql = 'INSERT INTO ' . kipa_content_table($table) . ' (`' . implode('`,`', $names) . '`) VALUES (' .
            implode(',', array_fill(0, count($names), '?')) . ')';
        $query = $db->prepare($sql);
        $query->execute(array_values($values));
        return (int)$db->lastInsertId();
    }
    $sql = 'UPDATE ' . kipa_content_table($table) . ' SET ' .
        implode(',', array_map(fn($name) => '`' . $name . '` = ?', $names)) . ' WHERE id = ?';
    $query = $db->prepare($sql);
    $query->execute([...array_values($values), $id]);
    return $id;
}

function kipa_content_capture(PDO $db, array $lead): void
{
    $fields = json_decode($lead['details_json'], true, 512, JSON_THROW_ON_ERROR);
    $type = $lead['form_type'];
    $table = $type === 'contact' ? 'contact_messages' : ($type === 'korean_course' ? 'course_registrations' : 'consultation_requests');
    $values = [
        'legacy_lead_id' => $lead['id'], 'full_name' => $lead['contact_name'],
        'phone' => $lead['phone'], 'email' => $lead['email'],
        'privacy_consent' => 1, 'consent_at' => $lead['consented_at'],
        'source_page' => $lead['source_page'], 'status' => 'new',
        'submitted_at' => $lead['created_at'], 'created_at' => $lead['created_at'],
        'updated_at' => $lead['created_at'],
    ];
    $code = 'WEB-' . $lead['id'];
    $values[$table === 'consultation_requests' ? 'request_code' : ($table === 'contact_messages' ? 'message_code' : 'registration_code')] = $code;
    $message = implode("\n", array_filter([
        $lead['program'] ? 'Nhu cầu: ' . $lead['program'] : '',
        $fields['message'] ?? $fields['notes'] ?? $fields['proposal'] ?? '',
    ]));
    $values[$table === 'course_registrations' ? 'note' : 'message'] = $message;
    if ($table === 'consultation_requests') {
        $values['province'] = $fields['area'] ?? null;
        $values['preferred_contact_time'] = $fields['contact_time'] ?? null;
    }
    $query = $db->prepare('INSERT INTO ' . kipa_content_table($table) . ' (`' . implode('`,`', array_keys($values)) .
        '`) VALUES (' . implode(',', array_fill(0, count($values), '?')) . ')');
    $query->execute(array_values($values));
    $record = (int)$db->lastInsertId();
    $query = $db->prepare('INSERT INTO kipa_form_submissions
        (submission_code, form_type, form_record_id, form_name, source_page, submitted_at,
         submission_status, created_at, legacy_lead_id, payload_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $query->execute([$code, $table, $record, $type, $lead['source_page'], $lead['created_at'], 'success',
        $lead['created_at'], $lead['id'], $lead['details_json']]);
}

function kipa_content_cleanup(PDO $db): int
{
    if (!kipa_content_ready($db)) {
        return 0;
    }
    $count = 0;
    foreach (['consultation_requests', 'contact_messages', 'course_registrations', 'event_registrations'] as $table) {
        $count += $db->exec('DELETE FROM ' . kipa_content_table($table) . ' WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    }
    $count += $db->exec('DELETE FROM kipa_form_submissions WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    return $count;
}
