<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

require_admin();

verify_csrf();


$matchId =
    (int)($_POST['match_id'] ?? 0);


if ($matchId <= 0) {

    http_response_code(400);

    exit('Ongeldige wedstrijd.');
}


$homeRaw =
    $_POST['home_score'] ?? '';

$awayRaw =
    $_POST['away_score'] ?? '';

$dateRaw =
    trim(
        $_POST['match_datetime'] ?? ''
    );


$homeScore =
    $homeRaw === ''
        ? null
        : max(0, (int)$homeRaw);


$awayScore =
    $awayRaw === ''
        ? null
        : max(0, (int)$awayRaw);


$date = null;


if ($dateRaw !== '') {

    $datetime =
        DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $dateRaw
        );


    if (!$datetime) {

        http_response_code(400);

        exit('Ongeldige datum.');
    }


    $date =
        $datetime->format(
            'Y-m-d H:i:s'
        );
}


$status =
    (
        $homeScore !== null &&
        $awayScore !== null
    )
        ? 'played'
        : 'scheduled';


$statement = db()->prepare("
    UPDATE matches

    SET
        home_score = ?,
        away_score = ?,
        match_datetime = ?,
        status = ?,
        updated_at = CURRENT_TIMESTAMP

    WHERE
        id = ?

        AND phase = 'group'
");


$statement->execute([
    $homeScore,
    $awayScore,
    $date,
    $status,
    $matchId
]);


header(
    'Location: admin.php?saved=1'
);

exit;
