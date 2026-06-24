// ── DONNÉES DE BASE ──
let notifications = [
    {
        id: 1,
        type: "bulletins",
        titre: "Nouveau bulletin disponible",
        message: "Votre bulletin du Semestre 1 — 2025/2026 est maintenant disponible.",
        temps: "Il y a 2 heures",
        lue: false
    },
    {
        id: 2,
        type: "notes",
        titre: "Nouvelles notes publiées",
        message: "Les notes de Algorithmique (S1) ont été publiées par Prof. Amoussou.",
        temps: "Il y a 5 heures",
        lue: false
    },
    {
        id: 3,
        type: "emploi",
        titre: "Changement d'emploi du temps",
        message: "Le cours de Programmation Web du vendredi a été déplacé au jeudi 14h.",
        temps: "Hier",
        lue: false
    },
    {
        id: 4,
        type: "admin",
        titre: "Annonce administrative",
        message: "Les inscriptions pour le semestre 2 sont ouvertes jusqu'au 30 janvier.",
        temps: "Il y a 2 jours",
        lue: true
    },
    {
        id: 5,
        type: "notes",
        titre: "Note modifiée",
        message: "Votre note de Bases de données a été mise à jour par Prof. Hounsou.",
        temps: "Il y a 3 jours",
        lue: true
    },
    {
        id: 6,
        type: "bulletins",
        titre: "Bulletin validé",
        message: "Votre bulletin du Semestre 2 — 2024/2025 a été validé par l'administration.",
        temps: "Il y a 1 semaine",
        lue: true
    }
];

let filtreActif = "toutes";

// ── FILTRER ──
function filtrer(filtre) {
    filtreActif = filtre;
    document.querySelectorAll(".filtre").forEach(btn => btn.classList.remove("active"));
    event.target.classList.add("active");
    afficherNotifications();
}

// ── AFFICHER NOTIFICATIONS ──
function afficherNotifications() {
    const container = document.getElementById("notifContainer");

    let liste = [...notifications];

    if (filtreActif === "non-lues") {
        liste = liste.filter(n => !n.lue);
    } else if (filtreActif !== "toutes") {
        liste = liste.filter(n => n.type === filtreActif);
    }

    if (liste.length === 0) {
        container.innerHTML = `<div class="vide">🔔 Aucune notification dans cette catégorie.</div>`;
        majStats();
        return;
    }

    container.innerHTML = liste.map(notif => {
        const iconeMap = {
            notes: { emoji: "📊", classe: "icone-notes", badge: "badge-notes", label: "Notes" },
            bulletins: { emoji: "📋", classe: "icone-bulletins", badge: "badge-bulletins", label: "Bulletin" },
            emploi: { emoji: "📅", classe: "icone-emploi", badge: "badge-emploi", label: "Emploi du temps" },
            admin: { emoji: "📢", classe: "icone-admin", badge: "badge-admin", label: "Annonce" }
        };

        const info = iconeMap[notif.type] || iconeMap.admin;

        return `
            <div class="notif-card ${notif.lue ? 'lue' : 'non-lue'}" onclick="marquerLu(${notif.id})">
                <div class="notif-icone ${info.classe}">${info.emoji}</div>
                <div class="notif-contenu">
                    <div class="notif-titre">${notif.titre}</div>
                    <div class="notif-message">${notif.message}</div>
                    <div class="notif-meta">
                        <span class="notif-temps">🕐 ${notif.temps}</span>
                        <span class="badge-type ${info.badge}">${info.label}</span>
                    </div>
                </div>
                <div class="notif-actions">
                    ${!notif.lue ? `<button class="btn-lire" onclick="event.stopPropagation(); marquerLu(${notif.id})">✓ Marquer lu</button>` : ''}
                    <button class="btn-supprimer-notif" onclick="event.stopPropagation(); supprimerNotif(${notif.id})">🗑️</button>
                </div>
                ${!notif.lue ? '<div class="point-nonlu"></div>' : ''}
            </div>
        `;
    }).join("");

    majStats();
}

// ── MARQUER COMME LU ──
function marquerLu(id) {
    const notif = notifications.find(n => n.id === id);
    if (notif && !notif.lue) {
        notif.lue = true;
        afficherNotifications();
        afficherToast("✅ Notification marquée comme lue !");
    }
}

// ── TOUT MARQUER COMME LU ──
function toutMarquerLu() {
    const nonLues = notifications.filter(n => !n.lue);
    if (nonLues.length === 0) {
        afficherToast("Toutes les notifications sont déjà lues !", "error");
        return;
    }
    notifications.forEach(n => n.lue = true);
    afficherNotifications();
    afficherToast("✅ Toutes les notifications marquées comme lues !");
}

// ── SUPPRIMER NOTIFICATION ──
function supprimerNotif(id) {
    if (!confirm("Supprimer cette notification ?")) return;
    notifications = notifications.filter(n => n.id !== id);
    afficherNotifications();
    afficherToast("✅ Notification supprimée !");
}

// ── STATS ──
function majStats() {
    const total = notifications.length;
    const nonLues = notifications.filter(n => !n.lue).length;
    const lues = notifications.filter(n => n.lue).length;

    document.getElementById("nbTotal").textContent = total;
    document.getElementById("nbNonLues").textContent = nonLues;
    document.getElementById("nbLues").textContent = lues;
}

// ── TOAST ──
function afficherToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast active" + (type === "error" ? " error" : "");
    setTimeout(() => toast.className = "toast", 3000);
}

// ── INIT ──
afficherNotifications();
