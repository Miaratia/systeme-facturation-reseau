<?php
// facture.php
require_once 'db.php';

if (!isset($_GET['id'])) {
    die("ID de facture non spécifié.");
}

$facture_id = intval($_GET['id']);

// Récupérer les détails complets de la facture, du CDR et de l'abonné
$sql = "SELECT cf.id as facture_num, cf.montant_calcule, cf.date_facturation,
               c.session_id, c.type_service, c.quantite, c.msisdn_appele,
               a.nom, a.telephone, a.type_compte, a.solde_argent
        FROM consommations_facturees cf
        JOIN cdrs c ON cf.cdr_id = c.id
        JOIN abonnes a ON cf.abonne_id = a.id
        WHERE cf.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$facture_id]);
$facture = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facture) {
    die("Facture introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #<?= $facture['facture_num'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; margin: 40px; background-color: #f4f4f9; }
        .invoice-card { max-width: 450px; margin: auto; background: white; padding: 25px; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 15px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .bold { font-weight: bold; }
        .btn-container { text-align: center; margin-top: 20px; }
        .btn-print { background: #007bff; color: white; padding: 8px 15px; border: none; cursor: pointer; font-weight: bold; }
        @media print {
            .btn-container { display: none; }
            body { margin: 0; background: white; }
            .invoice-card { border: none; box-shadow: none; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="center">
        <h2>ADS 360 TELECOM</h2>
        <p>Reçu de Facturation Réseau</p>
    </div>

    <div class="line"></div>

    <div class="row">
        <span>N° Facture :</span>
        <span class="bold">FAC-<?= str_pad($facture['facture_num'], 6, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="row">
        <span>Session ID :</span>
        <span><?= htmlspecialchars($facture['session_id']) ?></span>
    </div>
    <div class="row">
        <span>Date :</span>
        <span><?= htmlspecialchars($facture['date_facturation']) ?></span>
    </div>

    <div class="line"></div>

    <div class="row">
        <span>Abonné :</span>
        <span class="bold"><?= htmlspecialchars($facture['nom']) ?></span>
    </div>
    <div class="row">
        <span>Téléphone :</span>
        <span><?= htmlspecialchars($facture['telephone']) ?></span>
    </div>
    <div class="row">
        <span>Type d'offre :</span>
        <span><?= htmlspecialchars($facture['type_compte']) ?></span>
    </div>

    <div class="line"></div>

    <div class="row">
        <span>Service consommé :</span>
        <span class="bold"><?= htmlspecialchars($facture['type_service']) ?></span>
    </div>
    <div class="row">
        <span>Volume / Durée :</span>
        <span><?= htmlspecialchars($facture['quantite']) ?></span>
    </div>
    <div class="row">
        <span>Destination :</span>
        <span><?= htmlspecialchars($facture['msisdn_appele']) ?></span>
    </div>

    <div class="line"></div>

    <div class="row" style="font-size: 1.1em;">
        <span class="bold">MONTANT DÉBITÉ :</span>
        <span class="bold"><?= number_format($facture['montant_calcule'], 2) ?> MGA</span>
    </div>
    <div class="row">
        <span>Solde restant :</span>
        <span><?= number_format($facture['solde_argent'], 2) ?> MGA</span>
    </div>

    <div class="line"></div>

    <div class="center" style="font-size: 0.85em; margin-top: 15px;">
        <p>Merci pour votre confiance !</p>
    </div>
</div>

<div class="btn-container">
    <button onclick="window.print()" class="btn-print">🖨️ Imprimer ce reçu</button>
    <a href="index.php" style="margin-left: 15px;">Retour</a>
</div>

</body>
</html>