<?php
// index.php
require_once 'moteur_facturation.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = "CDR-" . time();
    $appelant = $_POST['appelant'];
    $appele = $_POST['appele'];
    $service = $_POST['type_service'];
    $quantite = intval($_POST['quantite']);
    $date = date("Y-m-d H:i:s");

    $message = traiterCDR($session_id, $appelant, $appele, $service, $quantite, $date);
}

$stmt = $pdo->query("SELECT * FROM abonnes");
$abonnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT c.session_id, c.msisdn_appelant, c.type_service, c.quantite, c.statut, c.date_evenement, cf.montant_calcule, cf.id as facture_id 
        FROM cdrs c 
        LEFT JOIN consommations_facturees cf ON c.id = cf.cdr_id 
        ORDER BY c.id DESC";
$stmt = $pdo->query($sql);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Système de Facturation Réseau</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 950px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; }
        .btn-recharge { background: #17a2b8; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .btn-csv { background: #28a745; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
        button:hover { background: #0056b3; }
        .alert { padding: 10px; background: #e2e2e2; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .badge-facture { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; }
        .badge-rejete { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; }
        .btn-print { background: #6c757d; color: white; padding: 3px 8px; border-radius: 3px; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>Simulateur d'Événement Réseau (Génération de CDR)</h2>
        <a href="recharger.php" class="btn-recharge">+ Recharger un Solde</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert"><strong>Résultat :</strong> <?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Numéro Appelant (Client) :</label>
            <input type="text" name="appelant" value="+261340000001" required>
        </div>
        <div class="form-group">
            <label>Numéro Appelé / Destination :</label>
            <input type="text" name="appele" value="+261320000002">
        </div>
        <div class="form-group">
            <label>Type de Service :</label>
            <select name="type_service">
                <option value="VOIX">VOIX (durée en secondes)</option>
                <option value="SMS">SMS (nombre de messages)</option>
                <option value="DATA">DATA (volume en Mo)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Quantité (Secondes / Nb SMS / Mo) :</label>
            <input type="number" name="quantite" value="30" min="1" required>
        </div>
        <button type="submit">Envoyer et Facturer le CDR</button>
    </form>

    <hr style="margin: 30px 0;">

    <h3>1. État des Comptes Abonnés</h3>
    <table>
        <tr>
            <th>Nom</th>
            <th>Téléphone</th>
            <th>Type</th>
            <th>Solde Data (Mo)</th>
            <th>Solde Argent (MGA)</th>
        </tr>
        <?php foreach ($abonnes as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['nom']) ?></td>
                <td><?= htmlspecialchars($a['telephone']) ?></td>
                <td><?= htmlspecialchars($a['type_compte']) ?></td>
                <td><strong><?= htmlspecialchars($a['solde_data_mb']) ?> Mo</strong></td>
                <td><strong><?= number_format($a['solde_argent'], 2) ?> MGA</strong></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="header-flex">
        <h3>2. Historique des CDRs et Facturations</h3>
        <a href="exporter_csv.php" class="btn-csv">📥 Exporter en Excel (CSV)</a>
    </div>

    <table>
        <tr>
            <th>Session ID</th>
            <th>Appelant</th>
            <th>Service</th>
            <th>Quantité</th>
            <th>Montant</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php foreach ($historique as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['session_id']) ?></td>
                <td><?= htmlspecialchars($h['msisdn_appelant']) ?></td>
                <td><?= htmlspecialchars($h['type_service']) ?></td>
                <td><?= htmlspecialchars($h['quantite']) ?></td>
                <td><?= $h['montant_calcule'] !== null ? number_format($h['montant_calcule'], 2) . " MGA" : "-" ?></td>
                <td>
                    <?php if ($h['statut'] === 'FACTURE'): ?>
                        <span class="badge-facture">FACTURÉ</span>
                    <?php else: ?>
                        <span class="badge-rejete">REJETÉ</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($h['date_evenement']) ?></td>
                <td>
                    <?php if ($h['statut'] === 'FACTURE'): ?>
                        <a href="facture.php?id=<?= $h['facture_id'] ?>" target="_blank" class="btn-print">Voir Reçu</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>