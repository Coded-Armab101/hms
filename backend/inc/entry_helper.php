<?php
/**
 * entry_helper.php
 * Shared helper for creating/updating/fetching unified entries (prescriptions, lab results, notes, etc.)
 * Usage: include this file from pages that already have a valid $mysqli connection and user session.
 */

if (!function_exists('create_entry')) {
    /**
     * Create an entry record.
     * @param mysqli $mysqli
     * @param string $entity_type
     * @param int|null $entity_id
     * @param string|null $title
     * @param string $content
     * @param int|null $author_id
     * @param string|null $author_name
     * @param string|null $author_role
     * @return int|false inserted entry_id or false on failure
     */
    function create_entry($mysqli, $entity_type, $entity_id, $title, $content, $author_id = null, $author_name = null, $author_role = null)
    {
        $sql = "INSERT INTO entries (entity_type, entity_id, title, content, author_id, author_name, author_role) VALUES (?,?,?,?,?,?,?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('sisssis', $entity_type, $entity_id, $title, $content, $author_id, $author_name, $author_role);
        if ($stmt->execute()) {
            $id = $mysqli->insert_id;
            $stmt->close();
            return $id;
        }
        $stmt->close();
        return false;
    }
}

if (!function_exists('update_entry')) {
    /**
     * Update an existing entry. Simple ownership check included: allow if $user_id === author_id.
     * Admin/privileged check must be implemented by caller if needed.
     * @param mysqli $mysqli
     * @param int $entry_id
     * @param string|null $title
     * @param string $content
     * @param int|null $user_id
     * @return bool
     */
    function update_entry($mysqli, $entry_id, $title, $content, $user_id = null)
    {
        // Check ownership
        if ($user_id !== null) {
            $check = $mysqli->prepare("SELECT author_id FROM entries WHERE entry_id = ?");
            if (!$check) return false;
            $check->bind_param('i', $entry_id);
            $check->execute();
            $res = $check->get_result();
            $row = $res->fetch_assoc();
            $check->close();
            if (!$row) return false;
            if ((int)$row['author_id'] !== (int)$user_id) {
                // Not the owner; caller should implement role-based override
                return false;
            }
        }

        $sql = "UPDATE entries SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE entry_id = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $title, $content, $entry_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('get_entries_by_entity')) {
    /**
     * Fetch entries for a given entity_type and entity_id
     * @param mysqli $mysqli
     * @param string $entity_type
     * @param int|null $entity_id
     * @param int $limit
     * @return array
     */
    function get_entries_by_entity($mysqli, $entity_type, $entity_id = null, $limit = 100)
    {
        if ($entity_id === null) {
            $sql = "SELECT * FROM entries WHERE entity_type = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param('si', $entity_type, $limit);
        } else {
            $sql = "SELECT * FROM entries WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param('sii', $entity_type, $entity_id, $limit);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('get_entry')) {
    function get_entry($mysqli, $entry_id)
    {
        $stmt = $mysqli->prepare("SELECT * FROM entries WHERE entry_id = ?");
        if (!$stmt) return null;
        $stmt->bind_param('i', $entry_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}

if (!function_exists('delete_entry')) {
    function delete_entry($mysqli, $entry_id, $user_id = null)
    {
        // Optional ownership check can be added by caller
        $stmt = $mysqli->prepare("DELETE FROM entries WHERE entry_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $entry_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('can_edit_entry')) {
    /**
     * Lightweight permission check: owner can edit. Extend with role checks in callers.
     */
    function can_edit_entry($mysqli, $entry_id, $user_id, $user_role = null)
    {
        if ($user_role === 'admin' || $user_role === 'superuser') return true;
        $row = get_entry($mysqli, $entry_id);
        if (!$row) return false;
        return ((int)$row['author_id'] === (int)$user_id);
    }
}

?>
