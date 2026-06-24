<?php
header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    "content" => [
        [
            "text" => json_encode([
                "est_fiche_paiement" => true,
                "nom_sur_document" => "KOFFI",
                "prenom_sur_document" => "Estelle",
                "etablissement" => "UATM GASA Formation",
                "confiance" => "haute"
            ])
        ]
    ]
]);