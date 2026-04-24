<?php
/**
 * OpenShelf Search Helper
 * Handles unified search logic and placeholder generation for PDO.
 */

/**
 * Generates SQL conditions and parameters for a multi-column search.
 * This function ensures unique placeholders are used, which is required
 * when PDO::ATTR_EMULATE_PREPARES is set to false.
 * 
 * @param string $search The search term
 * @param array $columns List of columns to search in
 * @param array &$where Array of WHERE conditions (passed by reference)
 * @param array &$params Array of PDO parameters (passed by reference)
 * @param string $tableAlias Optional table alias (e.g., 'b')
 */
function applySearchFilter($search, $columns, &$where, &$params, $tableAlias = '') {
    if (empty(trim($search))) return;

    $conditions = [];
    $searchVal = "%" . trim($search) . "%";
    $prefix = !empty($tableAlias) ? "{$tableAlias}." : "";
    
    // Use a unique suffix to avoid collision if this is called multiple times
    $suffix = count($params);

    foreach ($columns as $i => $column) {
        $placeholder = "search_{$suffix}_{$i}";
        
        // If column already has a prefix (contains a dot), don't add the tableAlias
        $fullColumn = (strpos($column, '.') !== false) ? $column : "{$prefix}{$column}";
        
        $conditions[] = "{$fullColumn} LIKE :{$placeholder}";
        $params[":{$placeholder}"] = $searchVal;
    }

    if (!empty($conditions)) {
        $where[] = "(" . implode(' OR ', $conditions) . ")";
    }
}

/**
 * Build the WHERE clause and parameters specifically for book filtering.
 * Shared between the main books page and the API.
 */
function prepareBookQuery($search, $selectedCategories, $availability, $tableAlias = 'b') {
    $where = ["1=1"];
    $params = [];

    $prefix = !empty($tableAlias) ? "{$tableAlias}." : "";

    if (!empty($availability)) {
        $where[] = "{$prefix}status = :availability";
        $params[':availability'] = $availability;
    }

    if (!empty($selectedCategories)) {
        $catPlaceholders = [];
        foreach ($selectedCategories as $i => $cat) {
            $key = ":cat$i";
            $catPlaceholders[] = $key;
            $params[$key] = $cat;
        }
        $where[] = "{$prefix}category IN (" . implode(',', $catPlaceholders) . ")";
    }

    // Use the search filter helper
    applySearchFilter($search, ['title', 'author', 'publisher', 'u.name'], $where, $params, $tableAlias);

    return [$where, $params];
}

/**
 * Get categories related to a given category for smarter suggestions.
 */
function getRelatedCategories($category) {
    $filePath = dirname(__DIR__) . '/data/category_relations.json';
    if (!file_exists($filePath)) return [];
    
    $mapping = json_decode(file_get_contents($filePath), true);
    if (!$mapping) return [];
    
    return $mapping[$category] ?? [];
}

/**
 * Suggest related books when a search has few results.
 * Strong search feature: uses categories, related categories, owners, and publishers.
 */
function getRelatedBooksForSearch($db, $search, $excludeIds = [], $limit = 6) {
    if (empty($search)) return [];
    
    $related = [];
    $excludeIdsStr = !empty($excludeIds) ? "AND b.id NOT IN (" . implode(',', array_map(fn($id) => $db->quote($id), $excludeIds)) . ")" : "";
    
    // Tier 1: Search for books by the same owners matched in search
    $stmt = $db->prepare("
        SELECT DISTINCT owner_id FROM books b 
        LEFT JOIN users u ON b.owner_id = u.id 
        WHERE u.name LIKE ? OR b.title LIKE ?
        LIMIT 2
    ");
    $stmt->execute(["%$search%", "%$search%"]);
    $ownerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($ownerIds)) {
        $placeholders = implode(',', array_fill(0, count($ownerIds), '?'));
        $sql = "SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar 
                FROM books b 
                LEFT JOIN users u ON b.owner_id = u.id 
                WHERE b.owner_id IN ($placeholders) AND b.status = 'available' $excludeIdsStr
                LIMIT ?";
        $stmt = $db->prepare($sql);
        foreach ($ownerIds as $i => $id) $stmt->bindValue($i + 1, $id);
        $stmt->bindValue(count($ownerIds) + 1, 3, PDO::PARAM_INT);
        $stmt->execute();
        $related = array_merge($related, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Tier 2: Search by Categories and Related Categories
    $stmt = $db->prepare("SELECT DISTINCT category FROM books WHERE category LIKE ? OR title LIKE ? LIMIT 2");
    $stmt->execute(["%$search%", "%$search%"]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $allCats = $categories;
    foreach ($categories as $cat) {
        $allCats = array_merge($allCats, getRelatedCategories($cat));
    }
    $allCats = array_unique($allCats);
    
    if (!empty($allCats) && count($related) < $limit) {
        $excludeNow = array_merge($excludeIds, array_column($related, 'id'));
        $excludeNowStr = !empty($excludeNow) ? "AND b.id NOT IN (" . implode(',', array_map(fn($id) => $db->quote($id), $excludeNow)) . ")" : "";
        
        $placeholders = implode(',', array_fill(0, count($allCats), '?'));
        $sql = "SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar 
                FROM books b 
                LEFT JOIN users u ON b.owner_id = u.id 
                WHERE b.category IN ($placeholders) AND b.status = 'available' $excludeNowStr
                ORDER BY b.views DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        foreach ($allCats as $i => $cat) $stmt->bindValue($i + 1, $cat);
        $stmt->bindValue(count($allCats) + 1, $limit - count($related), PDO::PARAM_INT);
        $stmt->execute();
        $related = array_merge($related, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Tier 3: Search by Publisher
    if (count($related) < $limit) {
        $excludeNow = array_merge($excludeIds, array_column($related, 'id'));
        $excludeNowStr = !empty($excludeNow) ? "AND b.id NOT IN (" . implode(',', array_map(fn($id) => $db->quote($id), $excludeNow)) . ")" : "";
        
        $sql = "SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar 
                FROM books b 
                LEFT JOIN users u ON b.owner_id = u.id 
                WHERE b.publisher LIKE ? AND b.status = 'available' $excludeNowStr
                ORDER BY b.views DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, "%$search%");
        $stmt->bindValue(2, $limit - count($related), PDO::PARAM_INT);
        $stmt->execute();
        $related = array_merge($related, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Fallback: Just random popular available books
    if (count($related) < 2) {
        $excludeNow = array_merge($excludeIds, array_column($related, 'id'));
        $excludeNowStr = !empty($excludeNow) ? "AND b.id NOT IN (" . implode(',', array_map(fn($id) => $db->quote($id), $excludeNow)) . ")" : "";
        
        $sql = "SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar 
                FROM books b 
                LEFT JOIN users u ON b.owner_id = u.id 
                WHERE b.status = 'available' $excludeNowStr
                ORDER BY b.views DESC, RAND() LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit - count($related), PDO::PARAM_INT);
        $stmt->execute();
        $related = array_merge($related, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Tag them as related
    foreach ($related as &$r) {
        $r['_match_type'] = 'related';
    }
    
    return array_slice($related, 0, $limit);
}


