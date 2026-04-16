<?php
/**
 * Notification & Matching Functions for Lost & Found System
 */

/**
 * Create a notification for a user
 */
function createNotification($user_id, $title, $message, $type = 'system', $related_item_id = null, $related_user_id = null, $action_url = null) {
    global $pdo;
    
    try {
        error_log(">>> createNotification called: user_id=$user_id, type=$type, title=$title, related_item_id=$related_item_id");
        
        // Validate inputs
        if (empty($user_id)) {
            error_log("ERROR: createNotification called with empty user_id");
            return false;
        }
        
        // Combine title and message for the message field if title exists
        $full_message = !empty($title) ? "$title\n$message" : $message;
        
        // Try to insert with all fields first, then fallback if columns don't exist
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_item_id, related_user_id, action_url, created_at, is_read)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, FALSE)
            ");
            
            $result = $stmt->execute([
                $user_id,
                $title,
                $message,
                $type,
                $related_item_id,
                $related_user_id,
                $action_url
            ]);
        } catch (PDOException $e) {
            // Fallback: table doesn't have all columns, use basic structure
            error_log("Full INSERT failed, trying basic columns: " . $e->getMessage());
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, message, type, is_read, created_at)
                VALUES (?, ?, ?, FALSE, CURRENT_TIMESTAMP)
            ");
            
            $result = $stmt->execute([
                $user_id,
                $full_message,
                $type
            ]);
        }
        
        if ($result) {
            $notif_id = $pdo->lastInsertId();
            error_log("<<< SUCCESS: Notification ID=$notif_id created for user_id=$user_id");
        } else {
            error_log("<<< FAILED: Could not create notification for user_id=$user_id");
        }
        
        return $result;
    } catch(PDOException $e) {
        error_log("ERROR in createNotification for user $user_id: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        return false;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch(PDOException $e) {
        error_log("Error getting notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get recent notifications for a user
 */
function getRecentNotifications($user_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch(PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = FALSE");
        return $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate similarity score between two items
 * Uses keyword matching, category similarity, and location proximity
 */
function calculateMatchScore($item1, $item2) {
    $score = 0;
    $max_score = 100;
    
    // 1. Keyword Matching (35 points max - MORE AGGRESSIVE now)
    $keywords1 = strtolower($item1['title'] . ' ' . $item1['description']);
    $keywords2 = strtolower($item2['title'] . ' ' . $item2['description']);
    
    // Split into words and calculate overlap
    $words1 = array_filter(preg_split('/[\s,\-\.]+/', $keywords1));
    $words2 = array_filter(preg_split('/[\s,\-\.]+/', $keywords2));
    
    // Remove common stop words
    $stop_words = ['the', 'a', 'an', 'at', 'and', 'or', 'but', 'in', 'on', 'by', 'with', 'to', 'for', 'of', 'is', 'was', 'are', 'be'];
    $words1 = array_diff($words1, $stop_words);
    $words2 = array_diff($words2, $stop_words);
    
    if (!empty($words1) && !empty($words2)) {
        // Exact word matches
        $common_words = array_intersect($words1, $words2);
        
        // Partial word matches (e.g., "watch" in "watches")
        $partial_matches = 0;
        foreach ($words1 as $w1) {
            foreach ($words2 as $w2) {
                if (strlen($w1) >= 3 && strlen($w2) >= 3) {
                    // Check if one contains the other (even partially)
                    if (strpos($w2, $w1) !== false || strpos($w1, $w2) !== false) {
                        $partial_matches++;
                    }
                    // Fuzzy match using Levenshtein
                    else if (levenshtein($w1, $w2) <= 2) {
                        $partial_matches += 0.5;
                    }
                }
            }
        }
        
        // Calculate keyword similarity (more generous scoring)
        $exact_boost = count($common_words) * 4;  // 4 points per exact match
        $partial_boost = $partial_matches * 2;    // 2 points per partial match
        
        $keyword_points = min(35, $exact_boost + $partial_boost);
        $score += $keyword_points;
        error_log("MATCH_DEBUG: Item {$item1['id']} vs {$item2['id']} - Keyword score: $keyword_points (exact: " . count($common_words) . ", partial: $partial_matches)");
    }
    
    // 2. Category Matching (30 points max - MORE LENIENT now)
    if (strtolower($item1['category']) === strtolower($item2['category'])) {
        $score += 30;
        error_log("MATCH_DEBUG: Category EXACT match: {$item1['category']} = {$item2['category']} (+30)");
    } elseif (similarCategories($item1['category'], $item2['category'])) {
        $score += 25; // More generous partial match (was 20)
        error_log("MATCH_DEBUG: Category SIMILAR match: {$item1['category']} ~ {$item2['category']} (+25)");
    } else {
        error_log("MATCH_DEBUG: Category NO match: {$item1['category']} != {$item2['category']}");
    }
    
    // 3. Location Proximity (20 points max)
    if (strtolower($item1['location']) === strtolower($item2['location'])) {
        $score += 20;
        error_log("MATCH_DEBUG: Location EXACT match: {$item1['location']} = {$item2['location']} (+20)");
    } elseif (proximateLocations($item1['location'], $item2['location'])) {
        $score += 10; // Partial match for nearby locations
        error_log("MATCH_DEBUG: Location PROXIMATE match: {$item1['location']} ~ {$item2['location']} (+10)");
    } else {
        error_log("MATCH_DEBUG: Location NO match: {$item1['location']} != {$item2['location']}");
    }
    
    // 4. Date Proximity (10 points max)
    $date1 = strtotime($item1['created_at']);
    $date2 = strtotime($item2['created_at']);
    $days_diff = abs($date1 - $date2) / (60 * 60 * 24);
    
    if ($days_diff <= 7) { // Items within 7 days
        $score += 10;
        error_log("MATCH_DEBUG: Date WITHIN 7 DAYS: $days_diff days difference (+10)");
    } elseif ($days_diff <= 30) { // Items within 30 days
        $score += 5;
        error_log("MATCH_DEBUG: Date WITHIN 30 DAYS: $days_diff days difference (+5)");
    } else {
        error_log("MATCH_DEBUG: Date TOO FAR: $days_diff days difference (0)");
    }
    
    $final_score = min($max_score, $score);
    error_log("MATCH_DEBUG: FINAL SCORE for Item {$item1['id']} vs {$item2['id']}: $final_score/100");
    
    return $final_score;
}

/**
 * Check if two categories are similar
 */
function similarCategories($cat1, $cat2) {
    $cat1 = strtolower($cat1);
    $cat2 = strtolower($cat2);
    
    // Similar category pairs
    $similar_pairs = [
        ['electronics', 'phone', 'laptop', 'computer'],
        ['bags', 'backpack', 'luggage'],
        ['clothing', 'clothes', 'apparel'],
        ['documents', 'id', 'card', 'wallet'],
        ['accessories', 'keys', 'watch', 'jewelry', 'watches', 'sports watch', 'sports-watch'],
    ];
    
    foreach ($similar_pairs as $group) {
        if (in_array($cat1, $group) && in_array($cat2, $group)) {
            return true;
        }
    }
    
    // Additional lenient matching: if both contain "watch", they're similar
    if ((strpos($cat1, 'watch') !== false) && (strpos($cat2, 'watch') !== false)) {
        return true;
    }
    
    return false;
}

/**
 * Check if two locations are proximate/nearby
 */
function proximateLocations($loc1, $loc2) {
    $loc1 = strtolower($loc1);
    $loc2 = strtolower($loc2);
    
    // Nearby location pairs at ANU
    $nearby_pairs = [
        ['library', 'library building', 'main library'],
        ['student center', 'student lounge', 'cafeteria'],
        ['campus', 'main campus', 'anu campus'],
        ['corridor', 'hallway', 'passage'],
        ['parking', 'parking lot', 'parking area'],
    ];
    
    foreach ($nearby_pairs as $group) {
        if (in_array($loc1, $group) && in_array($loc2, $group)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Automatically match lost and found items
 * Called when a new item is reported
 */
function autoMatchItems($new_item_id) {
    global $pdo;
    
    try {
        error_log("=== AUTO MATCHING STARTED FOR ITEM $new_item_id ===");
        
        // Get the new item
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$new_item_id]);
        $new_item = $stmt->fetch();
        
        if (!$new_item) {
            error_log("ERROR: Item $new_item_id not found!");
            return false;
        }
        
        error_log("NEW ITEM: ID={$new_item['id']}, Title={$new_item['title']}, Type={$new_item['type']}, Category={$new_item['category']}, Location={$new_item['location']}, Status={$new_item['status']}");
        
        // Determine what type to search for (opposite type)
        $search_type = $new_item['type'] === 'lost' ? 'found' : 'lost';
        $search_status = ['open', 'reported'];
        
        error_log("SEARCHING FOR: type=$search_type, statuses=[open, reported], excluding user_id={$new_item['user_id']} and item_id=$new_item_id");
        
        // Find similar items of opposite type - NO USER RESTRICTION for better matching
        $stmt = $pdo->prepare("
            SELECT * FROM items 
            WHERE type = ? 
            AND status IN (?, ?)
            AND id != ?
        ");
        
        $stmt->execute([$search_type, $search_status[0], $search_status[1], $new_item_id]);
        $similar_items = $stmt->fetchAll();
        
        error_log("FOUND " . count($similar_items) . " candidates to check");
        
        $matches_created = 0;
        
        foreach ($similar_items as $comparable_item) {
            error_log("\n--- CHECKING CANDIDATE: ID={$comparable_item['id']}, Title={$comparable_item['title']}, Type={$comparable_item['type']}, Category={$comparable_item['category']}, Location={$comparable_item['location']}, Status={$comparable_item['status']} ---");
            
            $match_score = calculateMatchScore($new_item, $comparable_item);
            
            error_log("MATCH SCORE RESULT: $match_score/100");
            
            // LOWERED THRESHOLD: Only 30% score required for match notification
            if ($match_score >= 30) {
                error_log("✅ MATCH ACCEPTED! Score ($match_score) >= Threshold (30)");
                
                // Determine match type
                $keyword_match = $match_score >= 15;
                $category_match = strtolower($new_item['category']) === strtolower($comparable_item['category']);
                $location_match = strtolower($new_item['location']) === strtolower($comparable_item['location']);
                $date_match = abs(strtotime($new_item['created_at']) - strtotime($comparable_item['created_at'])) <= (7 * 24 * 60 * 60);
                
                error_log("Match types - Keyword: $keyword_match, Category: $category_match, Location: $location_match, Date: $date_match");
                
                // Insert into item_matches table
                $stmt = $pdo->prepare("
                    INSERT INTO item_matches (lost_item_id, found_item_id, match_score, keyword_match, category_match, location_match, date_match)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE match_score = VALUES(match_score), keyword_match = VALUES(keyword_match), category_match = VALUES(category_match), location_match = VALUES(location_match), date_match = VALUES(date_match)
                ");
                
                if ($new_item['type'] === 'lost') {
                    $stmt->execute([
                        $new_item_id,
                        $comparable_item['id'],
                        $match_score,
                        $keyword_match ? 1 : 0,
                        $category_match ? 1 : 0,
                        $location_match ? 1 : 0,
                        $date_match ? 1 : 0
                    ]);
                    error_log("Inserted match: lost_item_id=$new_item_id, found_item_id={$comparable_item['id']}");
                } else {
                    $stmt->execute([
                        $comparable_item['id'],
                        $new_item_id,
                        $match_score,
                        $keyword_match ? 1 : 0,
                        $category_match ? 1 : 0,
                        $location_match ? 1 : 0,
                        $date_match ? 1 : 0
                    ]);
                    error_log("Inserted match: lost_item_id={$comparable_item['id']}, found_item_id=$new_item_id");
                }
                
                $matches_created++;
                
                // CRITICAL: Only notify the LOST item reporter - NEVER the found item reporter
                // Determine which item is lost and which is found
                if ($new_item['type'] === 'lost' && $comparable_item['type'] === 'found') {
                    // New=Lost, Comparable=Found → Notify lost reporter
                    error_log("CASE 1: new_item is LOST (id={$new_item['id']}, user={$new_item['user_id']}), comparable_item is FOUND (id={$comparable_item['id']}, user={$comparable_item['user_id']})");
                    error_log("ACTION: Call sendMatchNotification(lost_reporter_id={$new_item['user_id']}, found_item_id={$comparable_item['id']}, score=$match_score)");
                    sendMatchNotification($new_item['user_id'], $comparable_item['id'], $match_score);
                } else if ($new_item['type'] === 'found' && $comparable_item['type'] === 'lost') {
                    // New=Found, Comparable=Lost → Notify lost reporter  
                    error_log("CASE 2: new_item is FOUND (id={$new_item['id']}, user={$new_item['user_id']}), comparable_item is LOST (id={$comparable_item['id']}, user={$comparable_item['user_id']})");
                    error_log("ACTION: Call sendMatchNotification(lost_reporter_id={$comparable_item['user_id']}, found_item_id={$new_item_id}, score=$match_score)");
                    sendMatchNotification($comparable_item['user_id'], $new_item_id, $match_score);
                } else {
                    error_log("WARNING: Both items have same type - new_item type={$new_item['type']}, comparable_item type={$comparable_item['type']}");
                }
            } else {
                error_log("❌ MATCH REJECTED! Score ($match_score) < Threshold (30)");
            }
        }
        
        error_log("=== AUTO MATCHING COMPLETED: $matches_created matches created for item $new_item_id ===\n");
        
        return $matches_created;
    } catch(PDOException $e) {
        error_log("ERROR: Auto-matching failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send match notification to a user
 * ONLY notifies lost item reporter when a found item matches
 * Only sends ONE notification per unique match
 */
function sendMatchNotification($lost_reporter_id, $found_item_id, $match_score) {
    global $pdo;
    
    try {
        error_log("=== sendMatchNotification START ===");
        error_log("Parameters: lost_reporter_id=$lost_reporter_id, found_item_id=$found_item_id, match_score=$match_score");
        
        // Verify parameters are not empty
        if (empty($lost_reporter_id)) {
            error_log("ERROR: lost_reporter_id is empty!");
            return false;
        }
        if (empty($found_item_id)) {
            error_log("ERROR: found_item_id is empty!");
            return false;
        }
        
        // Verify the found item exists and IS a found item
        $stmt = $pdo->prepare("SELECT id, title, category, location, type, user_id FROM items WHERE id = ?");
        $stmt->execute([$found_item_id]);
        $found_item = $stmt->fetch();
        
        if (!$found_item) {
            error_log("ERROR: Found item $found_item_id does not exist in database");
            return false;
        }
        
        if ($found_item['type'] !== 'found') {
            error_log("ERROR: Item $found_item_id has type='{$found_item['type']}', expected 'found'. It was reported by user_id={$found_item['user_id']}");
            return false;
        }
        
        error_log("✓ Found item verified: ID={$found_item['id']}, Title={$found_item['title']}, Reported by user={$found_item['user_id']}");
        
        // CRITICAL: Do NOT send notification to the person who FOUND the item
        if ($lost_reporter_id == $found_item['user_id']) {
            error_log("BLOCKING: Would have sent notification to the FINDER (user {$found_item['user_id']}) - this is explicitly prohibited!");
            return true; // Don't create notification, but return success
        }
        
        // Verify the lost reporter exists
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
        $stmt->execute([$lost_reporter_id]);
        $lost_user = $stmt->fetch();
        
        if (!$lost_user) {
            error_log("ERROR: Lost reporter user_id=$lost_reporter_id does not exist in users table");
            return false;
        }
        
        error_log("✓ Lost reporter verified: user_id={$lost_user['id']}, name={$lost_user['name']}");
        
        // Check if we already notified this user about this specific found item
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND type = 'match' AND related_item_id = ?
        ");
        $stmt->execute([$lost_reporter_id, $found_item_id]);
        $check = $stmt->fetch();
        
        if ($check['count'] > 0) {
            error_log("SKIP: Duplicate - user_id=$lost_reporter_id already has match notification for item $found_item_id");
            return true;
        }
        
        error_log("✓ No duplicate found, creating notification");
        
        $title = "Possible Match Found! 🎉";
        $message = "We found a '{$found_item['category']}' - '{$found_item['title']}' that matches your lost item! Someone reported finding this item at {$found_item['location']}. Review it now to see if it's yours!";
        $action_url = BASE_URL . '/search.php?view_item=' . $found_item_id;
        
        // Create the notification for the LOST reporter
        $result = createNotification($lost_reporter_id, $title, $message, 'match', $found_item_id, null, $action_url);
        
        if ($result) {
            error_log("✓✓✓ SUCCESS: Notification created for user_id=$lost_reporter_id about found_item_id=$found_item_id");
        } else {
            error_log("✗✗✗ FAILED: Could not create notification for user_id=$lost_reporter_id about found_item_id=$found_item_id");
        }
        
        error_log("=== sendMatchNotification END ===\n");
        return $result;
    } catch(PDOException $e) {
        error_log("ERROR in sendMatchNotification: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify user when their lost item is claimed
 */
function notifyItemClaimed($item_id, $claimer_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, title FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$claimer_id]);
        $claimer = $stmt->fetch();
        
        $title = "Item Claimed! 📦";
        $message = "{$claimer['name']} has claimed your lost item: {$item['title']}. Please review their claim to verify authenticity.";
        
        return createNotification($item['user_id'], $title, $message, 'claim', $item_id, $claimer_id, BASE_URL . '/admin/claims.php');
    } catch(PDOException $e) {
        error_log("Error notifying item claimed: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify user when their claim is verified
 */
function notifyClaimVerified($claim_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.user_id, i.title, i.id as item_id 
            FROM claims c 
            JOIN items i ON c.item_id = i.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();
        
        if (!$claim) {
            return false;
        }
        
        $title = "Claim Verified! ✅";
        $message = "Congratulations! Your claim for '{$claim['title']}' has been verified and approved. You can now collect your item from ANU security.";
        
        return createNotification($claim['user_id'], $title, $message, 'verification', $claim['item_id'], null, BASE_URL . '/dashboard.php');
    } catch(PDOException $e) {
        error_log("Error notifying claim verified: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify user when their lost item is recovered
 */
function notifyItemRecovered($item_id, $recovery_details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, title FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        $title = "Item Recovered! 🎊";
        $message = "Great news! Your lost item '{$item['title']}' has been recovered! Please contact ANU security to arrange collection.";
        
        return createNotification($item['user_id'], $title, $message, 'recovery', $item_id, null, BASE_URL . '/dashboard.php');
    } catch(PDOException $e) {
        error_log("Error notifying item recovered: " . $e->getMessage());
        return false;
    }
}

