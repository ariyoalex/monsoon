<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class SlugGenerator
{
    public static function generate(string $input): string
    {
        $slug = mb_strtolower($input, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\s\-_]/u', '', $slug);
        $slug = preg_replace('/[\s\-_]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug === '' ? 'untitled' : $slug;
    }

    public static function generateUnique(string $input, \mysqli $db, string $table = 'content_items', string $column = 'slug'): string
    {
        $slug = self::generate($input);
        $base = $slug;
        $counter = 1;

        while (self::slugExists($slug, $db, $table, $column)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private static function slugExists(string $slug, \mysqli $db, string $table, string $column): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) AS count FROM {$table} WHERE {$column} = ?");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0) > 0;
    }
}
