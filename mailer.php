<?php
require_once __DIR__ . '/config.php';

function sendMail(string $to, string $subject, string $body): bool {
    $headers  = 'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>' . "\r\n";
    $headers .= 'Reply-To: ' . FROM_EMAIL . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $result = mail($to, $subject, $body, $headers);

    if (!$result) {
        error_log("mail() failed: to=$to subject=$subject");
    }

    return $result;
}

function sendNewRequestNotification(array $request, string $userName): void {
    $subject = '[plot2pod] New podcast request #' . $request['id'];
    $body    = sprintf(
        "New request from %s\n\nType: %s\n\nContent:\n%s\n\nReview it in admin panel:\n%s/admin.php",
        $userName,
        $request['type'],
        $request['content'] ?? '(file upload — see uploads folder)',
        SITE_URL
    );

    sendMail(ADMIN_EMAIL, $subject, $body);
}

function sendDoneNotification(string $userEmail, string $userName, int $podcastId): void {
    $subject = '[plot2pod] Your podcast is ready!';
    $body    = sprintf(
        "Hi %s,\n\nGreat news — your podcast is ready to listen!\n\n%s/podcast.php?id=%d\n\nEnjoy,\nThe plot2pod team",
        $userName,
        SITE_URL,
        $podcastId
    );

    sendMail($userEmail, $subject, $body);
}
