<?php

declare(strict_types=1);

session_start();

const ADMIN_USER = 'admin';

/*
 * Genereer zelf een wachtwoordhash met:
 *
 * php -r "echo password_hash('JOUW_WACHTWOORD', PASSWORD_DEFAULT), PHP_EOL;"
 *
 * Zet de gegenereerde hash hieronder.
 */

const ADMIN_PASSWORD_HASH = '$2y$10$w4n0hXj7rVqYQ6s9L3uB4e8jK2mP5tR1cF6aN9xZ0qW3sD7hG8kLm';


function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $dataDirectory = __DIR__ . '/data';

        if (!is_dir($dataDirectory)) {
            mkdir($dataDirectory, 0775, true);
        }

        $databaseFile =
            $dataDirectory . '/tournament.db';

        $pdo = new PDO(
            'sqlite:' . $databaseFile
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        $pdo->exec('PRAGMA foreign_keys = ON');

        initializeDatabase($pdo);
    }

    return $pdo;
}


function initializeDatabase(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            name TEXT NOT NULL,

            FOREIGN KEY (group_id)
            REFERENCES groups(id)
            ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,

            group_id INTEGER,

            phase TEXT NOT NULL DEFAULT 'group',

            home_team_id INTEGER,
            away_team_id INTEGER,

            home_score INTEGER,
            away_score INTEGER,

            match_datetime TEXT,

            status TEXT NOT NULL DEFAULT 'scheduled',

            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,

            FOREIGN KEY (group_id)
            REFERENCES groups(id)
            ON DELETE SET NULL,

            FOREIGN KEY (home_team_id)
            REFERENCES teams(id)
            ON DELETE SET NULL,

            FOREIGN KEY (away_team_id)
            REFERENCES teams(id)
            ON DELETE SET NULL
        )
    ");

    /*
     * Alleen de eerste keer data aanmaken.
     */

    $count =
        (int)$pdo
            ->query("SELECT COUNT(*) FROM groups")
            ->fetchColumn();

    if ($count > 0) {
        return;
    }


    $pdo->beginTransaction();

    try {

        $groupStatement =
            $pdo->prepare("
                INSERT INTO groups (name)
                VALUES (?)
            ");

        $teamStatement =
            $pdo->prepare("
                INSERT INTO teams (
                    group_id,
                    name
                )
                VALUES (?, ?)
            ");


        $matchStatement =
            $pdo->prepare("
                INSERT INTO matches (
                    group_id,
                    phase,
                    home_team_id,
                    away_team_id
                )
                VALUES (?, 'group', ?, ?)
            ");


        for ($groupNumber = 1; $groupNumber <= 5; $groupNumber++) {

            $groupName =
                'Groep ' .
                chr(64 + $groupNumber);

            $groupStatement->execute([
                $groupName
            ]);

            $groupId =
                (int)$pdo->lastInsertId();


            $teamIds = [];


            for ($teamNumber = 1; $teamNumber <= 5; $teamNumber++) {

                $teamName =
                    'Team ' .
                    chr(64 + $groupNumber) .
                    $teamNumber;

                $teamStatement->execute([
                    $groupId,
                    $teamName
                ]);

                $teamIds[] =
                    (int)$pdo->lastInsertId();
            }


            /*
             * 5 teams betekent 10 wedstrijden.
             */

            for ($i = 0; $i < 5; $i++) {

                for ($j = $i + 1; $j < 5; $j++) {

                    $matchStatement->execute([
                        $groupId,
                        $teamIds[$i],
                        $teamIds[$j]
                    ]);
                }
            }
        }

        $pdo->commit();

    } catch (Throwable $e) {

        $pdo->rollBack();

        throw $e;
    }
}


function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}


function require_admin(): void
{
    if (!is_admin()) {

        header('Location: login.php');

        exit;
    }
}


function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {

        $_SESSION['csrf'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}


function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';

    if (
        empty($_SESSION['csrf']) ||
        !hash_equals(
            $_SESSION['csrf'],
            $token
        )
    ) {

        http_response_code(419);

        exit('Ongeldige CSRF-token.');
    }
}
