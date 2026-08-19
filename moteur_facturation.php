<?php
// moteur_facturation.php
require_once 'db.php';

function traiterCDR($session_id, $msisdn_appelant, $msisdn_appele, $type_service, $quantite, $date_evenement) {
    global $pdo;

    try {
        // 1. Enregistrer le CDR dans la base
        $stmt = $pdo->prepare("INSERT INTO cdrs (session_id, msisdn_appelant, msisdn_appele, type_service, quantite, date_evenement) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$session_id, $msisdn_appelant, $msisdn_appele, $type_service, $quantite, $date_evenement]);
        $cdr_id = $pdo->lastInsertId();

        // 2. Chercher l'abonné
        $stmt = $pdo->prepare("SELECT * FROM abonnes WHERE telephone = ?");
        $stmt->execute([$msisdn_appelant]);
        $abonne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$abonne) {
            $pdo->prepare("UPDATE cdrs SET statut = 'REJETE' WHERE id = ?")->execute([$cdr_id]);
            return "❌ Erreur : Numéro abonné inconnu ($msisdn_appelant). CDR Rejeté.";
        }

        // 3. Récupérer le tarif unitaire
        $stmt = $pdo->prepare("SELECT prix_unitaire FROM tarifs WHERE type_service = ?");
        $stmt->execute([$type_service]);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tarif) {
            return "❌ Erreur : Tarif non défini pour le service $type_service.";
        }

        $montant_total = 0;

        // 4. Logique spécifique pour la DATA (Gestion du forfait Mo)
        if ($type_service === 'DATA') {
            $mo_disponibles = $abonne['solde_data_mb'];

            if ($mo_disponibles >= $quantite) {
                // Le forfait Mo couvre toute la consommation
                $nouveaux_mo = $mo_disponibles - $quantite;
                $pdo->prepare("UPDATE abonnes SET solde_data_mb = ? WHERE id = ?")->execute([$nouveaux_mo, $abonne['id']]);
                $montant_total = 0.00; // Gratuit car déduit du forfait
            } else {
                // Les Mo gratuits couvrent une partie, le reste est payé en argent
                $mo_restants_a_payer = $quantite - $mo_disponibles;
                $montant_total = $mo_restants_a_payer * $tarif['prix_unitaire'];

                if ($abonne['type_compte'] === 'PREPAYE' && $abonne['solde_argent'] < $montant_total) {
                    $pdo->prepare("UPDATE cdrs SET statut = 'REJETE' WHERE id = ?")->execute([$cdr_id]);
                    return "⚠️ Solde insuffisant pour {$abonne['nom']} (Mo épuisés, argent insuffisant).";
                }

                // Débit du reste des Mo et du solde argent
                $nouveau_solde_argent = $abonne['solde_argent'] - $montant_total;
                $pdo->prepare("UPDATE abonnes SET solde_data_mb = 0, solde_argent = ? WHERE id = ?")->execute([$nouveau_solde_argent, $abonne['id']]);
            }
        } 
        // 5. Logique pour VOIX et SMS
        else {
            $montant_total = $quantite * $tarif['prix_unitaire'];

            if ($abonne['type_compte'] === 'PREPAYE') {
                if ($abonne['solde_argent'] < $montant_total) {
                    $pdo->prepare("UPDATE cdrs SET statut = 'REJETE' WHERE id = ?")->execute([$cdr_id]);
                    return "⚠️ Solde insuffisant pour {$abonne['nom']}. Coût : $montant_total MGA / Solde : {$abonne['solde_argent']} MGA.";
                }

                $nouveau_solde = $abonne['solde_argent'] - $montant_total;
                $pdo->prepare("UPDATE abonnes SET solde_argent = ? WHERE id = ?")->execute([$nouveau_solde, $abonne['id']]);
            }
        }

        // 6. Enregistrer la consommation facturée
        $stmt = $pdo->prepare("INSERT INTO consommations_facturees (cdr_id, abonne_id, montant_calcule) VALUES (?, ?, ?)");
        $stmt->execute([$cdr_id, $abonne['id'], $montant_total]);

        // 7. Marquer le CDR comme facturé
        $pdo->prepare("UPDATE cdrs SET statut = 'FACTURE' WHERE id = ?")->execute([$cdr_id]);

        return "✅ Facturation réussie pour {$abonne['nom']} ! Montant débité : " . number_format($montant_total, 2) . " MGA.";

    } catch (Exception $e) {
        return "❌ Erreur de traitement : " . $e->getMessage();
    }
}
?>