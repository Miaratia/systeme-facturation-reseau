<?php
// exporter_csv.php
require_once 'db.php';

// Nom du fichier téléchargé
$filename = "historique_cdrs_" . date('Y-m-d_H-i') . ".csv";

// Entêtes HTTP pour forcer le téléchargement du fichier CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Ouvrir le flux de sortie PHP
$output = fopen('php://output', 'w');

// Ligne d'en-tête du fichier CSV (Colonnes)
fputcsv($output, ['Session ID', 'Appelant', 'Service', 'Quantite', 'Montant (MGA)', 'Statut', 'Date Evenement'], ';');

// Récupération des données depuis MySQL
$sql = "SELECT c.session_id, c.msisdn_appelant, c.type_service, c.quantite, cf.montant_calcule, c.statut, c.date_evenement
        FROM cdrs c
        LEFT JOIN consommations_facturees cf ON c.id = cf.cdr_id
        ORDER BY c.id DESC";

$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['session_id'],
        $row['msisdn_appelant'],
        $row['type_service'],
        $row['quantite'],
        $row['montant_calcule'] !== null ? $row['montant_calcule'] : '0.00',
        $row['statut'],
        $row['date_evenement']
    ], ';');
}

fclose($output);
exit();
?>