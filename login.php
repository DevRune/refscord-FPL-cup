<?php

declare(strict_types=1);

require __DIR__ . '/config.php';


if (is_admin()) {

    header('Location: admin.php');

    exit;
}


$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();


    $username =
        trim($_POST['username'] ?? '');

    $password =
        $_POST['password'] ?? '';


    if (
        $username === ADMIN_USER &&
        password_verify(
            $password,
            ADMIN_PASSWORD_HASH
        )
    ) {

        session_regenerate_id(true);

        $_SESSION['admin'] = true;

        header('Location: admin.php');

        exit;
    }


    $error =
        'Ongeldige gebruikersnaam of wachtwoord.';
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

<title>Admin login</title>

<link
    rel="stylesheet"
    href="style.css"
>

</head>


<body>


<main class="auth">


<section class="card">


<h1>
    Admin login
</h1>


<?php if ($error): ?>

<div class="error">

<?= e($error) ?>

</div>

<?php endif; ?>


<form method="post">


<input
    type="hidden"
    name="csrf"
    value="<?= e(csrf_token()) ?>"
>


<label>

Gebruikersnaam

<input
    type="text"
    name="username"
    required
>

</label>


<label>

Wachtwoord

<input
    type="password"
    name="password"
    required
>

</label>


<button
    class="button"
    type="submit"
>

Inloggen

</button>


</form>


</section>


</main>

</body>

</html>
