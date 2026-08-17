<?php

declare(strict_types=1);

require __DIR__ . '/config.php';


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
        matches.match_datetime IS NULL,
        matches.match_datetime,
        matches.id
");


$matches =
    $statement->fetchAll();


$grouped = [];


foreach ($matches as $match) {

    $grouped[
        $match['group_name']
    ][] = $match;
}

?>
<!DOCTYPE html>

<html lang="nl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Wedstrijden</title>

<link
    rel="stylesheet"
    href="style.css"
>

</head>


<body>


<header>

<div class="wrap nav">

<strong>
    Mijn Toernooi
</strong>


<nav>

<a href="index.php">
    Stand
</a>

<a href="matches.php">
    Wedstrijden
</a>

<?php if (is_admin()): ?>

<a href="admin.php">
    Admin
</a>

<a href="logout.php">
    Uitloggen
</a>

<?php else: ?>

<a href="login.php">
    Admin login
</a>

<?php endif; ?>

</nav>

</div>

</header>


<main class="wrap">


<h1>
    Wedstrijden
</h1>


<?php foreach (
    $grouped as $groupName => $groupMatches
): ?>


<section class="card">


<h2>
    <?= e($groupName) ?>
</h2>


<table>

<thead>

<tr>

<th>Datum</th>

<th>Thuis</th>

<th>Score</th>

<th>Uit</th>

<th>Status</th>

</tr>

</thead>


<tbody>


<?php foreach ($groupMatches as $match): ?>


<tr>


<td>

<?php if ($match['match_datetime']): ?>

<?= date(
    'd-m-Y H:i',
    strtotime(
        $match['match_datetime']
    )
) ?>

<?php else: ?>

Nog niet gepland

<?php endif; ?>

</td>


<td>
    <?= e($match['home_name']) ?>
</td>


<td>

<?php if ($match['home_score'] !== null): ?>

<?= (int)$match['home_score'] ?>
-
<?= (int)$match['away_score'] ?>

<?php else: ?>

-

<?php endif; ?>

</td>


<td>
    <?= e($match['away_name']) ?>
</td>


<td>

<?php if (
    $match['status'] === 'played'
): ?>

Gespeeld

<?php else: ?>

Gepland

<?php endif; ?>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


</section>


<?php endforeach; ?>


</main>

</body>

</html>
