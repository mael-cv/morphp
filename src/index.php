<?php
require_once __DIR__ . '/vendor/autoload.php';

// Initialisation des variables
$partie = [
    [null, null, null],
    [null, null, null],
    [null, null, null]
];
$joueur = 'X'; // Le joueur qui commence

// Traitement du coup joué
if (isset($_POST['ligne']) && isset($_POST['colonne'])) {
    $ligne = $_POST['ligne'];
    $colonne = $_POST['colonne'];
    
    if ($partie[$ligne][$colonne] === null) {
        $partie[$ligne][$colonne] = $joueur;
        $joueur = ($joueur === 'X') ? 'O' : 'X';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Morpion</title>
    <style>
        .grille {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 5px;
        }
        .case {
            width: 100px;
            height: 100px;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }
    </style>
</head>
<body>
    <h1>Morpion - Tour du joueur <?php echo $joueur; ?></h1>
    <form method="POST">
        <div class="grille">
            <?php for($i = 0; $i < 3; $i++): ?>
                <?php for($j = 0; $j < 3; $j++): ?>
                    <button type="submit" class="case" name="ligne" value="<?php echo $i; ?>">
                        <?php echo $partie[$i][$j] ?? ''; ?>
                        <input type="hidden" name="colonne" value="<?php echo $j; ?>">
                    </button>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </form>
</body>
</html>