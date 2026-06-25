<?php
if (!function_exists('formatUploadBytes')) {
    function formatUploadBytes($bytes) {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 2) . ' ' . $units[$index];
    }
}

function cleanKeyword($keyword) {
    $keyword = strtolower(trim($keyword));
    $keyword = preg_replace('/[^a-z0-9\s-]/', '', $keyword);
    $keyword = preg_replace('/\s+/', ' ', $keyword);
    return $keyword;
}

function extractReadableText($binaryData) {
    if ($binaryData === null || $binaryData === '') {
        return '';
    }

    $sample = substr($binaryData, 0, 700000);
    $text = preg_replace('/[^A-Za-z0-9\s-]/', ' ', $sample);
    return preg_replace('/\s+/', ' ', $text);
}

function shouldReadFileBytesForKeywords($fileType) {
    $fileType = strtolower(trim((string) $fileType));
    $readableTypes = ['txt', 'csv', 'tsv', 'html', 'htm', 'xml', 'json', 'md'];
    return in_array($fileType, $readableTypes, true);
}

function extractKeywordsFromText($text, $limit = 15) {
    $stopWords = [
        'the', 'and', 'for', 'with', 'that', 'this', 'from', 'you', 'your', 'are', 'was', 'were', 'will', 'can', 'has', 'have',
        'not', 'but', 'all', 'any', 'our', 'their', 'they', 'them', 'his', 'her', 'she', 'him', 'its', 'into', 'about', 'over',
        'under', 'then', 'than', 'when', 'what', 'which', 'while', 'where', 'how', 'why', 'pdf', 'mp4', 'blob', 'data', 'file',
        'asset', 'assets', 'video', 'document', 'page', 'pages', 'http', 'https', 'www', 'com',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ihdr', 'idat', 'iend'
    ];

    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $counts = [];

    foreach ($words as $word) {
        $word = trim($word, '-');
        if (
            strlen($word) < 4 ||
            is_numeric($word) ||
            in_array($word, $stopWords, true) ||
            !preg_match('/^[a-z][a-z-]*[a-z]$/', $word) ||
            !preg_match('/[aeiou]/', $word)
        ) {
            continue;
        }
        $counts[$word] = ($counts[$word] ?? 0) + 1;
    }

    arsort($counts);
    return array_slice($counts, 0, $limit, true);
}

function tagExists($conn, $keyword) {
    $sql = "SELECT tag_id FROM tags WHERE LOWER(keyword) = LOWER(?) LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

function ensureTag($conn, $keyword) {
    $keyword = cleanKeyword($keyword);
    if ($keyword === '' || !isUsefulKeyword($keyword)) {
        return null;
    }

    $selectSql = "SELECT tag_id FROM tags WHERE LOWER(keyword) = LOWER(?) LIMIT 1";
    if ($stmt = $conn->prepare($selectSql)) {
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            return (string) $row['tag_id'];
        }
    }

    $insertSql = "INSERT INTO tags (keyword) VALUES (?)";
    if ($stmt = $conn->prepare($insertSql)) {
        $stmt->bind_param("s", $keyword);
        if ($stmt->execute()) {
            $tagId = (string) $conn->insert_id;
            $stmt->close();
            return $tagId;
        }
        $stmt->close();
    }

    return null;
}

function attachTagToAsset($conn, $assetId, $tagId) {
    if ($assetId === null || $assetId === '' || $tagId === null || $tagId === '') {
        return false;
    }

    $assetId = (string) $assetId;
    $tagId = (string) $tagId;
    $sql = "INSERT IGNORE INTO asset_tags (asset_id, tag_id) VALUES (?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $assetId, $tagId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    return false;
}

function isUsefulKeyword($keyword) {
    $keyword = cleanKeyword($keyword);
    $stopWords = [
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ihdr', 'idat', 'iend',
        'pdf', 'mp4', 'blob', 'data', 'file', 'asset', 'assets'
    ];

    return strlen($keyword) >= 4 &&
        !is_numeric($keyword) &&
        !in_array($keyword, $stopWords, true) &&
        preg_match('/^[a-z][a-z-]*[a-z]$/', $keyword) &&
        preg_match('/[aeiou]/', $keyword);
}

function saveKeywordsAsTags($conn, $keywords, $assetId = null) {
    $savedCount = 0;

    foreach ($keywords as $keyword => $count) {
        $keyword = cleanKeyword($keyword);
        if ($keyword === '' || !isUsefulKeyword($keyword)) {
            continue;
        }

        $alreadyExists = tagExists($conn, $keyword);
        $tagId = ensureTag($conn, $keyword);

        if ($tagId !== null && !$alreadyExists) {
            $savedCount++;
        }

        if ($tagId !== null && $assetId !== null && $assetId !== '') {
            attachTagToAsset($conn, $assetId, $tagId);
        }
    }

    return $savedCount;
}

function getAssetTags($conn, $assetId) {
    $tags = [];
    $sql = "SELECT t.keyword
            FROM asset_tags atg
            JOIN tags t ON t.tag_id = atg.tag_id
            WHERE atg.asset_id = ?
            ORDER BY t.keyword";

    if ($stmt = $conn->prepare($sql)) {
        $assetId = (string) $assetId;
        $stmt->bind_param("s", $assetId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && $row = $result->fetch_assoc()) {
            $tags[] = $row['keyword'];
        }
        $stmt->close();
    }

    return $tags;
}

function extractKeywordsFromAssetData($title, $fileName, $fileType, $fileData, $limit = 15) {
    $sourceText = $title . ' ' . $fileName;

    if (shouldReadFileBytesForKeywords($fileType)) {
        $sourceText .= ' ' . extractReadableText($fileData);
    }

    return extractKeywordsFromText($sourceText, $limit);
}

function buildSearchText($title, $fileName, $fileType, $description, $fileData) {
    $sourceText = $title . ' ' . $fileName . ' ' . $fileType . ' ' . $description;

    if (shouldReadFileBytesForKeywords($fileType)) {
        $sourceText .= ' ' . extractReadableText($fileData);
    }

    return trim(preg_replace('/\s+/', ' ', $sourceText));
}
?>
