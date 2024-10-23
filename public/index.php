<?php
require_once '../vendor/autoload.php';

// Connexion à la base de données
function getDbConnection() {
    $host = 'localhost';  // Remplace par tes informations de connexion
    $db   = 'morpion_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

// Initialisation du jeu dans la base de données
function initGame($gameId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        $grid = json_encode([
            [null, null, null],
            [null, null, null],
            [null, null, null]
        ]);
        $activePlayer = 'X';
        $status = 'en_attente';

        $stmt = $pdo->prepare("INSERT INTO games (id, grid, active_player, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$gameId, $grid, $activePlayer, $status]);
    }
}

// Assigner un joueur à la partie
function assignPlayer($gameId, $playerId) {
    $pdo = getDbConnection();

    // Vérifier si le joueur existe déjà dans le jeu (peu importe la partie)
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch();

    // Si le joueur existe déjà, on récupère son symbole
    if ($player) {
        return $player['symbol'];
    }

    // Si le joueur n'existe pas, on continue le processus d'assignation
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM players WHERE game_id = ?");
    $stmt->execute([$gameId]);
    $playerCount = $stmt->fetchColumn();

    if ($playerCount == 0) {
        $symbol = 'X';
        $status = 'attente_joueur_o';
    } elseif ($playerCount == 1) {
        $symbol = 'O';
        $status = 'en_cours';
    } else {
        return null; // Partie complète
    }

    // Insérer le joueur dans la base de données
    $stmt = $pdo->prepare("INSERT INTO players (id, game_id, symbol) VALUES (?, ?, ?)");
    $stmt->execute([$playerId, $gameId, $symbol]);

    // Mettre à jour le statut du jeu
    $stmt = $pdo->prepare("UPDATE games SET status = ? WHERE id = ?");
    $stmt->execute([$status, $gameId]);

    return $symbol;
}

// Vérifier si un joueur est spectateur
function isSpectator($gameId, $playerId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ? AND id = ?");
    $stmt->execute([$gameId, $playerId]);
    $player = $stmt->fetch();

    return !$player;
}

// Vérifier si le joueur peut jouer
function canPlay($gameId, $playerSymbol) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();

    return ($game['active_player'] === $playerSymbol && $game['status'] === 'en_cours');
}

// Mettre à jour l'état de la grille dans la base de données
function updateGameState($gameId, $grid, $activePlayer) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = ? WHERE id = ?");
    $stmt->execute([json_encode($grid), $activePlayer, $gameId]);
}

// Vérifier si un joueur a gagné
function checkVictory($grille, $joueur) {
    for ($i = 0; $i < 3; $i++) {
        if (($grille[$i][0] === $joueur && $grille[$i][1] === $joueur && $grille[$i][2] === $joueur) ||
            ($grille[0][$i] === $joueur && $grille[1][$i] === $joueur && $grille[2][$i] === $joueur)) {
            return true;
        }
    }
    return ($grille[0][0] === $joueur && $grille[1][1] === $joueur && $grille[2][2] === $joueur) ||
           ($grille[0][2] === $joueur && $grille[1][1] === $joueur && $grille[2][0] === $joueur);
}

// Vérifier si la grille est pleine
function isGridFull($grille) {
    foreach ($grille as $ligne) {
        if (in_array(null, $ligne)) {
            return false;
        }
    }
    return true;
}

// Réinitialiser la partie
function resetGame($gameId) {
    $pdo = getDbConnection();
    $grid = json_encode([
        [null, null, null],
        [null, null, null],
        [null, null, null]
    ]);
    $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = 'X', status = 'en_cours' WHERE id = ?");
    $stmt->execute([$grid, $gameId]);
}

$gameId = $_GET['gameId'] ?? null;
if (!$gameId) {
    
    setcookie('playerId', '', time() - 3600, "/");
    $gameId = uniqid('game_');
    header("Location: index.php?gameId=" . $gameId);
    exit;
}

initGame($gameId);

if (!isset($_COOKIE['playerId'])) {
    $playerId = uniqid('player_');
    setcookie('playerId', $playerId, time() + (86400 * 30), "/"); // Cookie valide pour 30 jours
} else {
    $playerId = $_COOKIE['playerId'];

}

$playerSymbol = assignPlayer($gameId, $playerId);
$isSpectateur = isSpectator($gameId, $playerId);
$peutJouer = !$isSpectateur && canPlay($gameId, $playerSymbol);

// Récupérer la partie depuis la base de données
$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$partie = $stmt->fetch();
$grille = json_decode($partie['grid'], true);

if (isset($_POST['position']) && $peutJouer) {
    list($ligne, $colonne) = explode(',', $_POST['position']);
    $ligne = (int)$ligne;
    $colonne = (int)$colonne;

    if ($ligne >= 0 && $ligne < 3 && $colonne >= 0 && $colonne < 3) {
        if ($grille[$ligne][$colonne] === null) {
            $grille[$ligne][$colonne] = $playerSymbol;
            $activePlayer = ($playerSymbol === 'X') ? 'O' : 'X';
            updateGameState($gameId, $grille, $activePlayer);
        }
    }
}

$gagnant = null;
if (checkVictory($grille, 'X')) {
    $gagnant = 'X';
    $stmt = $pdo->prepare("UPDATE games SET status = 'termine' WHERE id = ?");
    $stmt->execute([$gameId]);
} elseif (checkVictory($grille, 'O')) {
    $gagnant = 'O';
    $stmt = $pdo->prepare("UPDATE games SET status = 'termine' WHERE id = ?");
    $stmt->execute([$gameId]);
} elseif (isGridFull($grille)) {
    $stmt = $pdo->prepare("UPDATE games SET status = 'egalite' WHERE id = ?");
    $stmt->execute([$gameId]);
}

if (isset($_POST['reset']) && !$isSpectateur) {
    resetGame($gameId);
    $gagnant = null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Morpion Multijoueur</title>
    <?php if (!$peutJouer && $partie['status'] === 'en_cours'): ?>
    <meta http-equiv="refresh" content="2">
    <?php endif; ?>
    <style>
        @font-face {
            font-family: 'More Sugar';
            src: url('./font/more-sugar.thin.otf');
        }
        
        body {
            background: linear-gradient(90deg, rgba(0,125,103,1) 0%, rgba(65,159,92,1) 100%);
            color: white;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .left, .right {
            width: 40%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: start;
            align-items: center;
        }

        .left {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .title {
            font-size: 90px;
            margin-bottom: 20px;
            font-family: 'Open Sans', sans-serif;
            margin: 100px 0px 50px 0px;
        }
        .rules {
            background-color: #fff;
            border-radius: 40px;
            color: #36995e;
            padding: 50px;
            font-size: 22px;
            text-align: justify;
            font-weight: bold;
            width: 60%;
            font-family: 'More Sugar';
        }
        .grille {
            display: grid;
            grid-template-columns: repeat(3, 175px);
            gap: 5px;
            margin-bottom: 20px;
        }
        .case {
            width: 175px;
            height: 175px;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            cursor: pointer;
        }
        .case:disabled {
            cursor: not-allowed;
        }
        .gagnant {
            color: #ffffff;
            font-weight: bold;
        }
        .spectateur {
            color: red;
        }
        .reset-btn {
            padding: 10px 20px;
            margin-top: 20px;
        }
        .info {
            margin: 20px 0;
        }
        .attente {
            color: orange;
        }
        .egalite {
            color: blue;
        }

        .action{
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            width: 70%;
        }

        .action input{
            width: 45%;
            height: 50px;
            border: solid 5px #ffffff;
            background: #36995e;
            border-radius: 50px;
            padding: 10px;
            font-size: 20px;
            margin-right: 20px;
            color: #ffffff;
        }

        .action button{
            width: 45%;
            height: 70px;
            border: none;
            background: #36995e;
            border-radius: 50px;
            padding: 10px;
            font-size: 20px;
            margin-right: 20px;
            color: #ffffff;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="title">MorpiGame</div>
            <div class="rules">
                Sur une grille carrée de 3 lignes et 3 colonnes. L'objectif est d'aligner 3 symboles identiques.<br><br>
                Les deux joueurs doivent remplir, à tour de rôle, une case de la grille avec le symbole qui leur est attribué, généralement O et X.<br><br>
                Celui qui aligne, le premier, 3 symboles identiques, a gagné.
            </div>
            <div class="action">
                <input type="text" readonly value="<?php echo (htmlspecialchars($gameId)); ?>">
                <button> Share Game Link </button>
            </div>
        </div>
        <div class="right">
            <?php if ($gagnant): ?>
                <h1 class="gagnant">Le joueur <?php echo htmlspecialchars($gagnant); ?> a gagné !</h1>
            <?php elseif ($partie['status'] === 'egalite'): ?>
                <h1 class="egalite">Match nul !</h1>
            <?php else: ?>
                <h1>Tour du joueur <?php echo htmlspecialchars($partie['active_player']); ?></h1>
            <?php endif; ?>
            <?php if ($partie['status'] === 'attente_joueur_o'): ?>
                <p class="attente">En attente d'un deuxième joueur... Partagez cette URL pour inviter quelqu'un !</p>
            <?php endif; ?>
            <form method="POST">
                <div class="grille">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <?php for ($j = 0; $j < 3; $j++): ?>
                        <button class="case" type="submit" name="position" value="<?php echo $i . ',' . $j; ?>" <?php echo $peutJouer && $grille[$i][$j] === null ? '' : 'disabled'; ?>>
                            <?php echo htmlspecialchars($grille[$i][$j] ?? ''); ?>
                        </button>
                    <?php endfor; ?>
                <?php endfor; ?>
                </div>

                <?php if ($gagnant || $partie['status'] === 'egalite'): ?>
                    <button class="reset-btn" type="submit" name="reset">Réinitialiser la partie</button>
                <?php endif; ?>
            </form>
            <?php if ($isSpectateur): ?>
                <p class="spectateur">Vous êtes spectateur - la partie est complète</p>
            <?php else: ?>
                <p>Vous jouez les <strong><?php echo htmlspecialchars($playerSymbol); ?></strong></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        setInterval(function() {
            location.reload();
        }, 1000);
    </script>
</body>
</html>
