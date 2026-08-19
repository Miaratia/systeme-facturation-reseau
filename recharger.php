<?php
// recharger.php
require_once 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telephone = $_POST['telephone'];
    $montant = floatval($_POST['montant']);

    if ($montant > 0) {
        $stmt = $pdo->prepare("UPDATE abonnes SET solde_argent = solde_argent + ? WHERE telephone = ?");
        $stmt->execute([$montant, $telephone]);
        $message = "✅ Solde rechargé avec succès de " . number_format($montant, 2) . " MGA !";
    } else {
        $message = "❌ Veuillez entrer un montant valide.";
    }
}

$stmt = $pdo->query("SELECT * FROM abonnes WHERE type_compte = 'PREPAYE'");
$abonnes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recharger un Compte</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; width: 100%; }
        button:hover { background: #0056b3; }
        .alert { padding: 10px; background: #e2e2e2; border-radius: 4px; margin-bottom: 20px; }
        a { text-decoration: none; color: #007bff; font-weight: bold; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php">← Retour au tableau de bord</a>
    <h2>Rechargement de Compte Prépayé</h2>

    <?php if (!empty($message)): ?>
        <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Choisir l'Abonné :</label>
            <select name="telephone" required>
                <?php foreach ($abonnes as $a): ?>
                    <option value="<?= htmlspecialchars($a['telephone']) ?>">
                        <?= htmlspecialchars($a['nom']) ?> (<?= htmlspecialchars($a['telephone']) ?>) - Solde : <?= number_format($a['solde_argent'], 2) ?> MGA
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Montant de la recharge (MGA) :</label>
            <input type="number" name="montant" value="2000" min="100" step="100" required>
        </div>
        <button type="submit">Effectuer la Recharge</button>
    </form>
</div>

</body>
</html>