<?php
function getAvatar($user, $size = 36) {
    if (!empty($user['profile_photo']) &&
        file_exists($_SERVER['DOCUMENT_ROOT'] . '/intranet/' . $user['profile_photo'])) {
        return '<img src="/intranet/' . htmlspecialchars($user['profile_photo'], ENT_QUOTES, 'UTF-8') . '"
                style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;
                border-radius:50%;object-fit:cover;flex-shrink:0">';
    } else {
        $initials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
        $color = $user['avatar_color'] ?? '#2563EB';
        $fontSize = max(10, $size / 3);
        return '<div style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;
                border-radius:50%;background:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';
                display:flex;align-items:center;justify-content:center;
                font-size:' . (int)$fontSize . 'px;font-weight:500;
                color:#fff;flex-shrink:0">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
