<?php

declare(strict_types=1);

require __DIR__ . '/config.php';


$groups = db()
    ->query("
        SELECT id, name
        FROM groups
        ORDER BY id
    ")
    ->fetchAll();


function get_standings(int $groupId): array
{
    $pdo = db();


    $statement = $pdo->prepare("
        SELECT id, name
        FROM teams
        WHERE group_id = ?
        ORDER BY id
    ");

    $statement->execute([
        $groupId
    ]);


    $teams = $statement->fetchAll();


    $standings = [];


    foreach ($teams as $team) {

        $standings[$team['id']] = [

            'name' => $team['name'],

            'played' => 0,

            'wins' => 0,

            'draws' => 0,

            'losses' => 0,

            'gf' => 0,

            'ga' => 0,

            'gd' => 0,

            'points' => 0
        ];
    }


    $statement = $pdo->prepare("
        SELECT
            home_team_id,
            away_team_id,
            home_score,
            away_score

        FROM matches

        WHERE group_id = ?

        AND phase = 'group'

        AND home_score IS NOT NULL

        AND away_score IS NOT NULL
    ");

    $statement->execute([
        $groupId
    ]);


    foreach ($statement as $match) {

        $homeId =
            (int)$match['home_team_id'];

        $awayId =
            (int)$match['away_team_id'];

        $homeScore =
            (int)$match['home_score'];

        $awayScore =
            (int)$match['away_score'];


        if (
            !isset(
                $standings[$homeId],
                $standings[$awayId]
            )
        ) {
            continue;
        }


        $standings[$homeId]['played']++;

        $standings[$awayId]['played']++;


        $standings[$homeId]['gf'] +=
            $homeScore;

        $standings[$homeId]['ga'] +=
            $awayScore;


        $standings[$awayId]['gf'] +=
            $awayScore;

        $standings[$awayId]['ga'] +=
            $homeScore;


        if ($homeScore > $awayScore) {

            $standings[$homeId]['wins']++;

            $standings[$awayId]['losses']++;

            $standings[$homeId]['points'] += 3;

        } elseif ($awayScore > $homeScore) {

            $standings[$awayId]['wins']++;

            $standings[$homeId]['losses']++;

            $standings[$awayId]['points'] += 3;

        } else {

            $standings[$homeId]['draws']++;

            $standings[$awayId]['draws']++;

            $standings[$homeId]['points']++;

            $standings[$awayId]['points']++;
        }
    }


    foreach ($standings as &$team) {

        $team['gd'] =
            $team['gf'] - $team['ga'];
    }

    unset($team);


    usort(
        $standings,
        function ($a, $b) {

            if ($a['points'] !== $b['points']) {

                return
                    $b['points']
                    <=>
                    $a['points'];
            }


            if ($a['gd'] !== $b['gd']) {

                return
                    $b['gd']
                    <=>
                    $a['gd'];
            }


            if ($a['gf'] !== $b['gf']) {

                return
                    $b['gf']
                    <=>
                    $a['gf'];
            }


            return strcmp(
                $a['name'],
                $b['name']
            );
        }
    );


    return $standings;
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

<title>Mijn Toernooi</title>

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


<section class="hero">

<h1>
    Mijn Toernooi
</h1>

<p>
    5 groepen van 5 teams.
    De top 3 kwalificeert rechtstreeks.
    De nummers 4 spelen play-offs.
</p>

</section>


<div class="group-grid">


<?php foreach ($groups as $group): ?>


<?php

$table =
    get_standings(
        (int)$group['id']
    );

?>


<section class="card">


<h2>
    <?= e($group['name']) ?>
</h2>


<table>

<thead>

<tr>

<th>#</th>

<th>Team</th>

<th>GS</th>

<th>W</th>

<th>G</th>

<th>V</th>

<th>DS</th>

<th>P</th>

</tr>

</thead>


<tbody>


<?php foreach ($table as $position => $team): ?>


<tr class="<?=
    $position < 3
        ? 'qualified'
        : (
            $position === 3
                ? 'playoff'
                : 'out'
        )
?>">


<td>
    <?= $position + 1 ?>
</td>


<td>
    <?= e($team['name']) ?>
</td>


<td>
    <?= $team['played'] ?>
</td>


<td>
    <?= $team['wins'] ?>
</td>


<td>
    <?= $team['draws'] ?>
</td>


<td>
    <?= $team['losses'] ?>
</td>


<td>
    <?= $team['gd'] ?>
</td>


<td>
    <strong>
        <?= $team['points'] ?>
    </strong>
</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


</section>


<?php endforeach; ?>


</div>


</main>

</body>

</html>
