// ── DONNÉES DE DÉMONSTRATION ──
// (À remplacer plus tard par les vraies données venant du backend PHP)

const dataUE = [
    { nom: "UE1 — Informatique Fondamentale", type: "UE" },
    { nom: "UE2 — Mathématiques Appliquées", type: "UE" },
    { nom: "Algorithmique", type: "Matière" },
    { nom: "Programmation Web", type: "Matière" },
    { nom: "Bases de données", type: "Matière" }
];

const dataDevoirs = [
    { titre: "Devoir Algorithmique", matiere: "Algorithmique", date: "2026-06-22", type: "Devoir" },
    { titre: "Devoir Programmation Web", matiere: "Programmation Web", date: "2026-06-25", type: "Devoir" }
];

const dataExamens = [
    { titre: "Examen Bases de données", matiere: "Bases de données", date: "2026-07-02", type: "Examen" }
];

const dataNotifications = [
    { titre: "Nouveau bulletin disponible", type: "Notification" },
    { titre: "Nouvelles notes publiées", type: "Notification" },
    { titre: "Changement d'emploi du temps", type: "Notification" }
];

let documents = [
    { nom: "Cours - Algorithmique Chap.3", categorie: "cours", taille: "1.2 Mo", date: "12 Juin 2026" },
    { nom: "TD - Programmation Web n°2", categorie: "td", taille: "450 Ko", date: "10 Juin 2026" },
    { nom: "Attestation de scolarité", categorie: "administratif", taille: "120 Ko", date: "5 Juin 2026" },
    { nom: "Cours - Bases de données Chap.1", categorie: "cours", taille: "980 Ko", date: "3 Juin 2026" },
    { nom: "TD - Algorithmique n°4", categorie: "td", taille: "310 Ko", date: "1 Juin 2026" },
    { nom: "Certificat de scolarité S1", categorie: "administratif", taille: "150 Ko", date: "28 Mai 2026" }
];

let filtreDocActif = "tous";

// ── RECHERCHE GLOBALE ──
function rechercherGlobal(valeur) {
    const resultatsSection = document.getElementById("resultatsRecherche");
    const contenuNormal = document.getElementById("contenuNormal");
    const container = document.getElementById("resultatsContainer");

    if (!valeur.trim()) {
        resultatsSection.style.display = "none";
        contenuNormal.style.display = "block";
        return;
    }

    resultatsSection.style.display = "block";
    contenuNormal.style.display = "none";

    const terme = valeur.toLowerCase();
    let resultats = [];

    // Recherche dans UE/Matières
    dataUE.forEach(item => {
        if (item.nom.toLowerCase().includes(terme)) {
            resultats.push({ categorie: item.type, titre: item.nom, details: "" });
        }
    });

    // Recherche dans devoirs
    dataDevoirs.forEach(item => {
        if (item.titre.toLowerCase().includes(terme) || item.matiere.toLowerCase().includes(terme)) {
            resultats.push({ categorie: "Devoir", titre: item.titre, details: "Échéance : " + formaterDate(item.date) });
        }
    });

    // Recherche dans examens
    dataExamens.forEach(item => {
        if (item.titre.toLowerCase().includes(terme) || item.matiere.toLowerCase().includes(terme)) {
            resultats.push({ categorie: "Examen", titre: item.titre, details: "Date : " + formaterDate(item.date) });
        }
    });

    // Recherche dans documents
    documents.forEach(doc => {
        if (doc.nom.toLowerCase().includes(terme)) {
            resultats.push({ categorie: "Document", titre: doc.nom, details: doc.taille + " — " + doc.date });
        }
    });

    if (resultats.length === 0) {
        container.innerHTML = `<p class="vide-bloc">Aucun résultat trouvé pour "${valeur}".</p>`;
        return;
    }

    container.innerHTML = resultats.map(r => `
        <div class="resultat-item">
            <div style="flex:1;">
                <span class="resultat-categorie">${r.categorie}</span>
                <div class="resultat-titre">${r.titre}</div>
                ${r.details ? `<div class="resultat-details">${r.details}</div>` : ''}
            </div>
        </div>
    `).join("");
}

// ── FORMATER DATE ──
function formaterDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR", { day: "numeric", month: "long", year: "numeric" });
}

// ── PROCHAINS ÉVÉNEMENTS ──
function afficherProchainsEvenements() {
    const container = document.getElementById("prochainsEvenements");
    const evenements = [
        ...dataDevoirs.map(d => ({ ...d, icone: "📝", classe: "icone-devoir" })),
        ...dataExamens.map(e => ({ ...e, icone: "📋", classe: "icone-examen" }))
    ].sort((a, b) => new Date(a.date) - new Date(b.date));

    if (evenements.length === 0) {
        container.innerHTML = `<p class="vide-bloc">Aucun événement à venir.</p>`;
        return;
    }

    container.innerHTML = evenements.map(e => `
        <div class="item-ligne">
            <div class="item-icone ${e.classe}">${e.icone}</div>
            <div class="item-texte">
                <div class="item-titre">${e.titre}</div>
                <div class="item-sous">${e.matiere} — ${formaterDate(e.date)}</div>
            </div>
        </div>
    `).join("");
}

// ── ACTIVITÉ RÉCENTE ──
function afficherActiviteRecente() {
    const container = document.getElementById("activiteRecente");
    const activites = [
        { icone: "🔔", classe: "icone-notif", titre: "Nouveau bulletin disponible", sous: "Il y a 2 heures" },
        { icone: "💬", classe: "icone-message", titre: "Nouveau message de Prof. Amoussou", sous: "Il y a 5 heures" },
        { icone: "📝", classe: "icone-devoir", titre: "Devoir Algorithmique ajouté", sous: "Hier" }
    ];

    container.innerHTML = activites.map(a => `
        <div class="item-ligne">
            <div class="item-icone ${a.classe}">${a.icone}</div>
            <div class="item-texte">
                <div class="item-titre">${a.titre}</div>
                <div class="item-sous">${a.sous}</div>
            </div>
        </div>
    `).join("");
}

// ── FILTRER DOCUMENTS ──
function filtrerDoc(categorie) {
    filtreDocActif = categorie;
    document.querySelectorAll(".filtre-doc").forEach(btn => btn.classList.remove("active"));
    event.target.classList.add("active");
    afficherDocuments();
}

// ── AFFICHER DOCUMENTS ──
function afficherDocuments() {
    const container = document.getElementById("documentsContainer");

    let liste = documents;
    if (filtreDocActif !== "tous") {
        liste = documents.filter(d => d.categorie === filtreDocActif);
    }

    if (liste.length === 0) {
        container.innerHTML = `<p class="vide-bloc">Aucun document dans cette catégorie.</p>`;
        return;
    }

    const iconesMap = {
        cours: { emoji: "📘", classe: "doc-cours" },
        td: { emoji: "📝", classe: "doc-td" },
        administratif: { emoji: "📄", classe: "doc-administratif" }
    };

    container.innerHTML = liste.map(doc => {
        const info = iconesMap[doc.categorie];
        return `
            <div class="document-card">
                <div class="doc-icone ${info.classe}">${info.emoji}</div>
                <div class="doc-details">
                    <div class="doc-nom">${doc.nom}</div>
                    <div class="doc-meta">${doc.taille} • ${doc.date}</div>
                </div>
                <button class="btn-telecharger" onclick="telechargerDoc('${doc.nom}')">📥 Télécharger</button>
            </div>
        `;
    }).join("");
}

// ── TÉLÉCHARGER DOCUMENT ──
function telechargerDoc(nom) {
    afficherToast("📥 Téléchargement de \"" + nom + "\" lancé !");
    // Ici plus tard : lien réel vers le fichier PHP qui gère le téléchargement
}

// ── TOAST ──
function afficherToast(message) {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast active";
    setTimeout(() => toast.className = "toast", 3000);
}

// ── INIT ──
afficherProchainsEvenements();
afficherActiviteRecente();
afficherDocuments();
