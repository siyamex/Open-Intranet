<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;

final class FormRequest
{
    public const FIELD_TYPES = ['text', 'textarea', 'select', 'date', 'file', 'checkbox', 'section'];

    /**
     * Decode + sanitize a form's field definitions.
     *
     * @return array<int, array{id: string, type: string, label: string, required: bool, options: string[], condition: ?array{field: string, value: string}}>
     */
    public static function fields(array $form): array
    {
        $decoded = json_decode((string) $form['fields'], true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $i => $field) {
            if (!is_array($field) || !in_array($field['type'] ?? '', self::FIELD_TYPES, true)) {
                continue;
            }
            $condition = null;
            if (is_array($field['condition'] ?? null)
                && trim((string) ($field['condition']['field'] ?? '')) !== ''
                && trim((string) ($field['condition']['value'] ?? '')) !== '') {
                $condition = [
                    'field' => (string) $field['condition']['field'],
                    'value' => (string) $field['condition']['value'],
                ];
            }
            $out[] = [
                'id' => preg_match('/^f[0-9a-z]{1,20}$/', (string) ($field['id'] ?? '')) ? (string) $field['id'] : 'f' . $i,
                'type' => (string) $field['type'],
                'label' => mb_substr(trim((string) ($field['label'] ?? 'Field')), 0, 150),
                'required' => !empty($field['required']),
                'options' => array_values(array_filter(array_map(
                    static fn ($o): string => mb_substr(trim((string) $o), 0, 150),
                    (array) ($field['options'] ?? [])
                ), 'strlen')),
                'condition' => $condition,
            ];
        }
        return $out;
    }

    /**
     * Users who may act on a submission of this form.
     *
     * @return int[]
     */
    public static function approverIds(array $form, int $submitterId): array
    {
        switch ($form['approver_type']) {
            case 'user':
                return $form['approver_user_id'] !== null ? [(int) $form['approver_user_id']] : [];
            case 'role':
                if ($form['approver_role_id'] === null) {
                    return [];
                }
                return array_map('intval', array_column(DB::fetchAll(
                    "SELECT u.id FROM users u JOIN user_role ur ON ur.user_id = u.id
                     WHERE ur.role_id = ? AND u.status = 'active'",
                    [(int) $form['approver_role_id']]
                ), 'id'));
            default: // manager
                $managerId = DB::scalar(
                    "SELECT manager_id FROM users WHERE id = ?",
                    [$submitterId]
                );
                return $managerId !== null ? [(int) $managerId] : [];
        }
    }

    public static function isApprover(array $form, array $submission): bool
    {
        return in_array((int) Auth::id(), self::approverIds($form, (int) $submission['user_id']), true)
            || Auth::can('forms.manage');
    }

    public static function logEvent(int $submissionId, string $action, ?string $note = null): void
    {
        DB::insert('form_submission_events', [
            'submission_id' => $submissionId,
            'actor_id' => Auth::id(),
            'action' => $action,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
