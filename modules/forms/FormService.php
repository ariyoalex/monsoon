<?php

declare(strict_types=1);

namespace Monsoon\Modules\Forms;

use Monsoon\Kernel\Uuid;

final class FormService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $result = $this->db->query('SELECT * FROM forms ORDER BY name ASC');
        $forms = [];
        while ($row = $result->fetch_assoc()) {
            $row['fields'] = json_decode($row['fields'] ?? '[]', true);
            $row['settings'] = json_decode($row['settings'] ?? '{}', true);
            $forms[] = $row;
        }
        return $forms;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM forms WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['fields'] = json_decode($row['fields'] ?? '[]', true);
            $row['settings'] = json_decode($row['settings'] ?? '{}', true);
        }
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();
        $name = $data['name'] ?? '';
        $fields = json_encode($data['fields'] ?? []);
        $settings = json_encode($data['settings'] ?? []);
        $successMsg = $data['success_message'] ?? 'Thank you for your submission!';
        $redirectUrl = $data['redirect_url'] ?? null;
        $notifEmail = $data['notification_email'] ?? null;
        $honeypot = (int) ($data['honeypot_enabled'] ?? 1);
        $timeLimit = (int) ($data['time_limit_seconds'] ?? 5);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO forms (id, name, fields, settings, success_message, redirect_url, notification_email, honeypot_enabled, time_limit_seconds, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssssiiss', $id, $name, $fields, $settings, $successMsg, $redirectUrl, $notifEmail, $honeypot, $timeLimit, $now, $now);
        $stmt->execute();
        $stmt->close();

        return $this->findById($id);
    }

    public function update(string $id, array $data): ?array
    {
        $fields = [];
        $params = [];
        $types = '';

        foreach (['name', 'success_message', 'redirect_url', 'notification_email'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= 's';
            }
        }
        if (array_key_exists('fields', $data)) {
            $fields[] = 'fields = ?';
            $params[] = json_encode($data['fields']);
            $types .= 's';
        }
        if (array_key_exists('settings', $data)) {
            $fields[] = 'settings = ?';
            $params[] = json_encode($data['settings']);
            $types .= 's';
        }
        if (array_key_exists('honeypot_enabled', $data)) {
            $fields[] = 'honeypot_enabled = ?';
            $params[] = (int) $data['honeypot_enabled'];
            $types .= 'i';
        }
        if (array_key_exists('time_limit_seconds', $data)) {
            $fields[] = 'time_limit_seconds = ?';
            $params[] = (int) $data['time_limit_seconds'];
            $types .= 'i';
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = NOW()';
        $params[] = $id;
        $types .= 's';

        $sql = 'UPDATE forms SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM forms WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted;
    }

    public function submit(string $formId, array $data, string $ipAddress = '', string $userAgent = ''): array
    {
        $form = $this->findById($formId);
        if (!$form) {
            throw new \RuntimeException('Form not found.');
        }

        if ($form['honeypot_enabled'] && !empty($data['_hp_field'])) {
            throw new \RuntimeException('Spam detected.');
        }

        if (isset($data['_start_time'])) {
            $elapsed = time() - (int) $data['_start_time'];
            if ($elapsed < $form['time_limit_seconds']) {
                throw new \RuntimeException('Form submitted too quickly.');
            }
        }

        $id = Uuid::v4();
        $dataJson = json_encode($data);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO form_submissions (id, form_id, data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $id, $formId, $dataJson, $ipAddress, $userAgent, $now);
        $stmt->execute();
        $stmt->close();

        if (!empty($form['notification_email'])) {
            $this->sendNotification($form, $data, $ipAddress);
        }

        return ['id' => $id, 'form_id' => $formId, 'success' => true];
    }

    public function getSubmissions(string $formId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('sii', $formId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $row['data'] = json_decode($row['data'] ?? '{}', true);
            $submissions[] = $row;
        }
        $stmt->close();
        return $submissions;
    }

    public function getSubmissionCount(string $formId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM form_submissions WHERE form_id = ?');
        $stmt->bind_param('s', $formId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) ($row['count'] ?? 0);
    }

    public function deleteSubmission(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM form_submissions WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted;
    }

    public function exportCsv(string $formId): string
    {
        $submissions = $this->getSubmissions($formId, 10000);
        if (empty($submissions)) {
            return '';
        }

        $allKeys = [];
        foreach ($submissions as $sub) {
            foreach (array_keys($sub['data'] ?? []) as $key) {
                if (!in_array($key, $allKeys, true)) {
                    $allKeys[] = $key;
                }
            }
        }

        $csv = 'ID,Date,IP,' . implode(',', array_map(
            fn($k) => '"' . str_replace('"', '""', $k) . '"',
            $allKeys
        )) . "\n";

        foreach ($submissions as $sub) {
            $row = [
                $sub['id'],
                $sub['created_at'],
                $sub['ip_address'],
            ];
            foreach ($allKeys as $key) {
                $row[] = $sub['data'][$key] ?? '';
            }
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                $row
            )) . "\n";
        }

        return $csv;
    }

    private function sendNotification(array $form, array $data, string $ipAddress = ''): void
    {
        $to = $form['notification_email'];
        $subject = 'New submission: ' . $form['name'];
        $body = "New form submission received.\n\n";
        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }
            $body .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
        }
        $body .= "\nIP: " . ($ipAddress ?: 'unknown');

        $headers = ['Content-Type: text/plain; charset=UTF-8', 'X-Mailer: MonsoonCMS/1.0'];

        @mail($to, $subject, $body, $headers);
    }
}
