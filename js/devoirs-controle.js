// ── DONNÉES ──
let listeDevoirs = [];
let listeControleContinu = [];
let ongletActif = "devoirs";
let indexModification = null;

// ── ONGLETS ──
function afficherOnglet(onglet) {
    ongletActif = onglet;

    document.getElementById("section-devoirs").style.display = onglet === "devoirs" ? "block" : "none";
    document.getElementById("section-controles").style.display = onglet === "controles" ? "block" : "none";

    document.querySelectorAll(".tab").forEach((tab, i) => {
        tab.classList.toggle("active", (i === 0 && onglet === "devoirs") || (i === 1 && onglet === "controles"));
    });
}

// ── OUVRIR MODAL ──
function ouvrirModal(type, index = null) {
    indexModification = index;
    document.getElementById("modalType").value = type;
    document.getElementById("modalTitre").textContent = (index !== null ? "✏️ Modifier" : "➕ Ajouter") + " " + (type === "devoir" ? "un devoir" : "un contrôle");

    // Pré-remplir si modification
    if (index !== null) {
        const item = type === "devoir" ? listeDevoirs[index] : listeControleContinu[index];
        document.getElementById("modalMatiere").value = item.matiere;
        document.getElementById("modalDate").value = item.date;
        document.getElementById("modalHeure").value = item.heure;
        document.getElementById("modalSalle").value = item.salle;
        document.getElementById("modalSemestre").value = item.semestre;
    } else {
        document.getElementById("modalMatiere").value = "";
        document.getElementById("modalDate").value = "";
        document.getElementById("modalHeure").value = "";
        document.getElementById("modalSalle").value = "";
        document.getElementById("modalSemestre").value = "S1";
    }

    document.getElementById("modal").classList.add("active");
}

// ── FERMER MODAL ──
function fermerModal() {
    document.getElementById("modal").classList.remove("active");
    indexModification = null;
}

// Fermer si clic en dehors
document.getElementById("modal").addEventListener("click", function(e) {
    if (e.target === this) fermerModal();
});

// ── SAUVEGARDER ──
function sauvegarder() {
    const type = document.getElementById("modalType").value;
    const matiere = document.getElementById("modalMatiere").value.trim();
    const date = document.getElementById("modalDate").value;
    const heure = document.getElementById("modalHeure").value;
    const salle = document.getElementById("modalSalle").value.trim();
    const semestre = document.getElementById("modalSemestre").value;

    if (!matiere || !date) {
        afficherToast("Matière et date sont obligatoires !", "error");
        return;
    }

    const item = { matiere, date, heure, salle, semestre };

    if (type === "devoir") {
        if (indexModification !== null) {
            listeDevoirs[indexModification] = item;
            afficherToast("✅ Devoir modifié !");
        } else {
            listeDevoirs.push(item);
            afficherToast("✅ Devoir ajouté !");
        }
        afficherDevoirs();
    } else {
        if (indexModification !== null) {
            listeControleContinu[indexModification] = item;
            afficherToast("✅ Contrôle modifié !");
        } else {
            listeControleContinu.push(item);
            afficherToast("✅ Contrôle ajouté !");
        }
        afficherControle();
    }

    fermerModal();
    majStats();
}

// ── STATUT D'UN ÉVÉNEMENT ──
function getStatut(dateStr) {
    const aujourd = new Date();
    aujourd.setHours(0, 0, 0, 0);
    const date = new Date(dateStr);
    date.setHours(0, 0, 0, 0);

    if (date < aujourd) return { label: "Passé", classe: "badge-passe" };
    if (date.getTime() === aujourd.getTime()) return { label: "Aujourd'hui !", classe: "badge-aujourdhui" };
    return { label: "À venir", classe: "badge-avenir" };
}

// ── FORMATER DATE ──
function formaterDate(dateStr) {
    if (!dateStr) return "—";
    const d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
}

// ── AFFICHER DEVOIRS ──
function afficherDevoirs() {
    const container = document.getElementById("devoirsContainer");

    if (listeDevoirs.length === 0) {
        container.innerHTML = `<p class="vide">Aucun devoir enregistré pour l'instant.</p>`;
        return;
    }

    const tries = [...listeDevoirs].map((d, i) => ({ ...d, index: i })).sort((a, b) => new Date(a.date) - new Date(b.date));

    container.innerHTML = tries.map(item => {
        const statut = getStatut(item.date);
        return `
            <div class="event-card">
                <div class="event-card-header devoir">
                    <h3>📄 ${item.matiere}</h3>
                    <p>${item.semestre}</p>
                </div>
                <div class="event-card-body">
                    <span class="badge-statut ${statut.classe}">${statut.label}</span>
                    <div class="event-info">
                        <span><strong>📅 Date :</strong> ${formaterDate(item.date)}</span>
                        <span><strong>🕐 Heure :</strong> ${item.heure || "—"}</span>
                        <span><strong>🏫 Salle :</strong> ${item.salle || "—"}</span>
                    </div>
                    <div class="event-actions">
                        <button class="btn-modifier" onclick="ouvrirModal('devoir', ${item.index})">✏️ Modifier</button>
                        <button class="btn-supprimer" onclick="supprimerDevoir(${item.index})">🗑️ Supprimer</button>
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

// ── AFFICHER CONTRÔLES ──
function afficherControle() {
    const container = document.getElementById("controlesContainer");

    if (listeControleContinu.length === 0) {
        container.innerHTML = `<p class="vide">Aucun contrôle enregistré pour l'instant.</p>`;
        return;
    }

    const tries = [...listeControleContinu].map((e, i) => ({ ...e, index: i })).sort((a, b) => new Date(a.date) - new Date(b.date));

    container.innerHTML = tries.map(item => {
        const statut = getStatut(item.date);
        return `
            <div class="event-card">
                <div class="event-card-header controle">
                    <h3>📋 ${item.matiere}</h3>
                    <p>${item.semestre}</p>
                </div>
                <div class="event-card-body">
                    <span class="badge-statut ${statut.classe}">${statut.label}</span>
                    <div class="event-info">
                        <span><strong>📅 Date :</strong> ${formaterDate(item.date)}</span>
                        <span><strong>🕐 Heure :</strong> ${item.heure || "—"}</span>
                        <span><strong>🏫 Salle :</strong> ${item.salle || "—"}</span>
                    </div>
                    <div class="event-actions">
                        <button class="btn-modifier" onclick="ouvrirModal('controle', ${item.index})">✏️ Modifier</button>
                        <button class="btn-supprimer" onclick="supprimercontrole(${item.index})">🗑️ Supprimer</button>
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

// ── SUPPRIMER DEVOIR ──
function supprimerDevoir(index) {
    if (!confirm("Supprimer ce devoir ?")) return;
    listeDevoirs.splice(index, 1);
    afficherDevoirs();
    majStats();
    afficherToast("✅ Devoir supprimé !");
}

// ── SUPPRIMER CONTRÔLE ──
function supprimercontrole(index) {
    if (!confirm("Supprimer ce contrôle ?")) return;
    listeControleContinu.splice(index, 1);
    afficherControle();
    majStats();
    afficherToast("✅ Contrôle supprimé !");
}

// ── STATS ──
function majStats() {
    const aujourd = new Date();
    aujourd.setHours(0, 0, 0, 0);

    const devoirsAvenir = listeDevoirs.filter(d => new Date(d.date) >= aujourd).length;
    const controlesAvenir = listeControleContinu.filter(e => new Date(e.date) >= aujourd).length;

    const aujourdhui = [...listeDevoirs, ...listeControleContinu].filter(item => {
        const d = new Date(item.date);
        d.setHours(0, 0, 0, 0);
        return d.getTime() === aujourd.getTime();
    }).length;

    document.getElementById("nbDevoirs").textContent = devoirsAvenir;
    document.getElementById("nbControles").textContent = controlesAvenir;
    document.getElementById("nbAujourdhui").textContent = aujourdhui;
}

// ── TOAST ──
function afficherToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast active" + (type === "error" ? " error" : "");
    setTimeout(() => toast.className = "toast", 3000);
}

// ── INIT ──
afficherDevoirs();
afficherControle();
