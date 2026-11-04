<?php
declare(strict_types=1);

/**
 * Seed verified KIPA locations into Group E database tables.
 * Safe and idempotent: can be executed repeatedly without duplicating rows.
 */

function kipa_seed_locations(PDO $db, string $jsonPath = ''): array
{
    if ($jsonPath === '') {
        $jsonPath = __DIR__ . '/locations.json';
    }
    if (!is_file($jsonPath)) {
        throw new RuntimeException('Locations seed JSON file not found: ' . $jsonPath);
    }
    $locations = json_decode((string)file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
    $seeded = [];

    $db->beginTransaction();
    try {
        $locStmt = $db->prepare(
            'INSERT INTO `kipa_locations`
             (`location_name`, `slug`, `location_code`, `location_type`, `short_description`, `introduction`, `main_image`, `display_order`, `is_featured`, `is_main_office`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
             `location_name` = VALUES(`location_name`),
             `location_code` = VALUES(`location_code`),
             `location_type` = VALUES(`location_type`),
             `short_description` = VALUES(`short_description`),
             `introduction` = VALUES(`introduction`),
             `main_image` = VALUES(`main_image`),
             `display_order` = VALUES(`display_order`),
             `is_featured` = VALUES(`is_featured`),
             `is_main_office` = VALUES(`is_main_office`),
             `status` = VALUES(`status`),
             `updated_at` = NOW()'
        );

        $getLocStmt = $db->prepare('SELECT `id` FROM `kipa_locations` WHERE `slug` = ? LIMIT 1');

        $addrStmt = $db->prepare(
            'INSERT INTO `kipa_location_addresses`
             (`location_id`, `address_type`, `address_line`, `ward`, `district`, `province`, `country`, `full_address`, `is_primary`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $contactStmt = $db->prepare(
            'INSERT INTO `kipa_location_contacts`
             (`location_id`, `contact_type`, `contact_label`, `contact_value`, `contact_url`, `is_primary`, `display_order`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $hoursStmt = $db->prepare(
            'INSERT INTO `kipa_location_hours`
             (`location_id`, `day_of_week`, `open_time`, `close_time`, `is_closed`, `note`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $serviceStmt = $db->prepare(
            'INSERT INTO `kipa_location_services`
             (`location_id`, `service_name`, `service_code`, `slug`, `short_description`, `display_order`, `status`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $seoStmt = $db->prepare(
            'INSERT INTO `kipa_seo_metadata`
             (`entity_type`, `entity_id`, `meta_title`, `meta_description`, `canonical_url`, `og_title`, `og_description`, `og_image`, `robots_index`, `robots_follow`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
             `meta_title` = VALUES(`meta_title`),
             `meta_description` = VALUES(`meta_description`),
             `canonical_url` = VALUES(`canonical_url`),
             `og_title` = VALUES(`og_title`),
             `og_description` = VALUES(`og_description`),
             `og_image` = VALUES(`og_image`),
             `updated_at` = NOW()'
        );

        foreach ($locations as $loc) {
            $isFeatured = ($loc['is_main_office'] ?? 0) ? 1 : 0;
            $locStmt->execute([
                $loc['name'],
                $loc['slug'],
                $loc['code'],
                $loc['type'],
                $loc['description'],
                $loc['description'],
                $loc['image'],
                $loc['display_order'],
                $isFeatured,
                $loc['is_main_office'],
                $loc['status'],
            ]);

            $getLocStmt->execute([$loc['slug']]);
            $locationId = (int)$getLocStmt->fetchColumn();
            if ($locationId <= 0) {
                throw new RuntimeException('Failed to retrieve location ID for ' . $loc['slug']);
            }

            // Clean previous child rows to ensure idempotent update
            $db->prepare('DELETE FROM `kipa_location_addresses` WHERE `location_id` = ?')->execute([$locationId]);
            $db->prepare('DELETE FROM `kipa_location_contacts` WHERE `location_id` = ?')->execute([$locationId]);
            $db->prepare('DELETE FROM `kipa_location_hours` WHERE `location_id` = ?')->execute([$locationId]);
            $db->prepare('DELETE FROM `kipa_location_services` WHERE `location_id` = ?')->execute([$locationId]);

            // Insert primary address
            $country = in_array($loc['city_key'], ['han-quoc', 'uc'], true) ? ($loc['city_key'] === 'han-quoc' ? 'Hàn Quốc' : 'Úc') : 'Việt Nam';
            $addrStmt->execute([
                $locationId,
                'office',
                $loc['address'],
                '',
                '',
                $loc['city_key'],
                $country,
                $loc['address'],
                1,
                'active',
            ]);

            // Insert phone contact
            $contactStmt->execute([
                $locationId,
                'phone',
                'Hotline',
                $loc['phone'],
                'tel:' . $loc['phone_raw'],
                1,
                1,
                'active',
            ]);

            // Insert business hours (Mon - Sat, 7:00 - 17:00)
            for ($day = 1; $day <= 6; $day++) {
                $hoursStmt->execute([
                    $locationId,
                    $day,
                    '07:00:00',
                    '17:00:00',
                    0,
                    'Thứ 2 – Thứ 7',
                ]);
            }

            // Insert services
            if (!empty($loc['services'])) {
                foreach ($loc['services'] as $idx => $serviceName) {
                    $serviceSlug = $loc['slug'] . '-dv-' . ($idx + 1);
                    $serviceCode = $loc['code'] . '-S' . ($idx + 1);
                    $serviceStmt->execute([
                        $locationId,
                        $serviceName,
                        $serviceCode,
                        $serviceSlug,
                        $serviceName . ' tại ' . $loc['name'],
                        $idx + 1,
                        'active',
                    ]);
                }
            }

            // Insert/Update SEO Metadata for this location
            $metaTitle = $loc['name'] . ' | Hệ Thống Cơ Sở KIPA Việt Hàn';
            $canonicalUrl = '/noi-dung/locations/' . $loc['slug'] . '/';
            $seoStmt->execute([
                'locations',
                $locationId,
                $metaTitle,
                $loc['description'],
                $canonicalUrl,
                $metaTitle,
                $loc['description'],
                $loc['image'],
            ]);

            $seeded[] = [
                'id' => $locationId,
                'slug' => $loc['slug'],
                'name' => $loc['name'],
            ];
        }

        $db->commit();
        return $seeded;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}
