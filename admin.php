<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

require_admin();


$statement = db()->query("
    SELECT

        matches.*,

        groups.name AS group_name,

        home.name AS home_name,

        away.name AS away_name

    FROM matches

    LEFT JOIN groups
        ON groups.id = matches.group_id

    LEFT JOIN teams home
        ON home.id = matches.home_team_id

    LEFT JOIN teams away
        ON away.id = matches.away_team_id

    WHERE matches.phase = 'group'

    ORDER BY
        matches.group_id,
        matches.match_datetime IS NULL,
        matches.match_datetime,
        matches.id
");


$matches =
    $statement->fetchAll();

?>
<!DOCTYPE html>

<html lang="nl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Admin</title>

<link
    rel="stylesheet"
    href="style.css"
>

</head>


<body>


<header>

<div class="wrap nav">

<strong>
    Toernooi Admin
</strong>


<nav>

<a href="index.php">
    Publieke stand
</a>

<a href="matches.php">
    Wedstrijden
</a>

<a href="logout.php">
    Uitloggen
</a>

</nav>

</div>

</header>


<main class="wrap">


<section class="hero">

<h1>
    Wedstrijden beheren
</h1>

<p>
    Alleen admins kunnen hier
    scores en datums wijzigen.
</p>

</section>


<?php if (isset($_GET['saved'])): ?>

<div class="success">

Wedstrijd opgeslagen.

</div>

<?php endif; ?>


<section class="card">


<div class="admin-list">


<?php foreach ($matches as $match): ?>


<form
    class="admin-match"
    method="post"
    action="save_match.php"
>


<input
    type="hidden"
    name="csrf"
    value="<?= e(csrf_token()) ?>"
>


<input
    type="hidden"
    name="match_id"
    value="<?= (int)$match['id'] ?>"
>


<div class="match-info">

<strong>
    <?= e($match['group_name']) ?>
</strong>

<span>

<?= e($match['home_name']) ?>

vs

<?= e($match['away_name']) ?>

</span>

</div>


<label>

Datum en tijd

<input
    type="datetime-local"
    name="match_datetime"

    value="<?=
        $match['match_datetime']
            ? date(
                'Y-m-d\TH:i',
                strtotime(
                    $match['match_datetime']
                )
            )
            : ''
    ?>"
>

</label>


<label>

Thuis

<input
    type="number"
    min="0"
    name="home_score"

    value="<?=
        $match['home_score'] === null
            ? ''
            : (int)$match['home_score']
    ?>"
>

</label>


<label>

Uit

<input
    type="number"
    min="0"
    name="away_score"

    value="<?=
        $match['away_score'] === null
            ? ''
            : (int)$match['away_score']
    ?>"
>

</label>


<button
    class="button"
    type="submit"
>

Opslaan

</button>


</form>


<?php endforeach; ?>


</div>


</section>


</main>

</body>

</html>
