// ── DONNÉES ──
let listeUE = [];
let ueSelectionnee = null;

// ── ÉLÉMENTS DOM ──
const ueForm = document.getElementById("ueForm");
const matiereForm = document.getElementById("matiereForm");
const ueContainer = document.getElementById("ueContainer");
const ueSelectionneeLabel = document.getElementById("ueSelectionneeLabel");

// ── AJOUTER UNE UE ──
ueForm.addEventListener("submit", function(e) {
    e.preventDefault();

    const code = document.getElementById("codeUE").value.trim();
    const nom = document.getElementById("nomUE").value.trim();
    const niveau = document.getElementById("niveauUE").value;
    const semestre = document.getElementById("semestreUE").value;

    if (!code || !nom) {
        afficherToast("Veuillez remplir tous les champs !", "error");
        return;
    }

    listeUE.push({
        code,
        nom,
        niveau,
        semestre,
        matieres: []
    });

    afficherUE();
    ueForm.reset();
    afficherToast("✅ UE ajoutée avec succès !");
});

// ── AJOUTER UNE MATIÈRE ──
matiereForm.addEventListener("submit", function(e) {
    e.preventDefault();

    if (ueSelectionnee === null) {
        afficherToast("Sélectionnez d'abord une UE en cliquant sur 'Sélectionner' !", "error");
        return;
    }

    const nom = document.getElementById("nomMatiere").value.trim();
    const coefficient = document.getElementById("coefficient").value;
    const credits = document.getElementById("credits").value;
    const professeur = document.getElementById("professeur").value.trim();

    if (!nom || !coefficient || !credits || !professeur) {
        afficherToast("Veuillez remplir tous les champs de la matière !", "error");
        return;
    }

    listeUE[ueSelectionnee].matieres.push({
        nom,
        coefficient,
        credits,
        professeur
    });

    afficherUE();
    matiereForm.reset();
    afficherToast("✅ Matière ajoutée avec succès !");
});

// ── AFFICHER TOUTES LES UE ──
function afficherUE() {
    ueContainer.innerHTML = "";

    if (listeUE.length === 0) {
        ueContainer.innerHTML = `<p class="vide">Aucune UE enregistrée pour l'instant.</p>`;
        majStats();
        return;
    }

    listeUE.forEach((ue, index) => {
        const estSelectionnee = ueSelectionnee === index;

        ueContainer.innerHTML += `
            <div class="ue-card">
                <div class="ue-card-header">
                    <h3>${ue.code} — ${ue.nom}</h3>
                    <p>${ue.niveau || ""} ${ue.semestre ? "| " + ue.semestre : ""} | ${ue.matieres.length} matière(s)</p>
                </div>
                <div class="ue-card-body">
                    <div class="actions">
                        <button class="btn-select" onclick="selectionnerUE(${index})" style="${estSelectionnee ? 'background:#1E73E8;color:white;' : ''}">
                            ${estSelectionnee ? "✅ Sélectionnée" : "🎯 Sélectionner"}
                        </button>
                        <button class="btn-modifier" onclick="modifierUE(${index})">✏️ Modifier</button>
                        <button class="btn-supprimer" onclick="supprimerUE(${index})">🗑️ Supprimer</button>
                    </div>

                    <div id="matieres-${index}">
                        ${ue.matieres.length === 0
                            ? '<p style="font-size:12px;color:#aaa;">Aucune matière ajoutée.</p>'
                            : ue.matieres.map((m, i) => `
                                <div class="matiere">
                                    <strong>${m.nom}</strong>
                                    <p>Coefficient : ${m.coefficient} | Crédits : ${m.credits}</p>
                                    <p>Professeur : ${m.professeur}</p>
                                    <button class="btn-supprimer" onclick="supprimerMatiere(${index}, ${i})">
                                        🗑️ Supprimer
                                    </button>
                                </div>
                            `).join("")
                        }
                    </div>
                </div>
            </div>
        `;
    });

    majStats();
}

// ── SÉLECTIONNER UNE UE ──
function selectionnerUE(index) {
    ueSelectionnee = index;
    ueSelectionneeLabel.textContent = "UE sélectionnée : " + listeUE[index].code + " — " + listeUE[index].nom;
    afficherUE();
}

// ── MODIFIER UNE UE ──
function modifierUE(index) {
    const nouveauCode = prompt("Nouveau code UE :", listeUE[index].code);
    if (nouveauCode === null) return;

    const nouveauNom = prompt("Nouveau nom UE :", listeUE[index].nom);
    if (nouveauNom === null) return;

    listeUE[index].code = nouveauCode.trim() || listeUE[index].code;
    listeUE[index].nom = nouveauNom.trim() || listeUE[index].nom;

    afficherUE();
    afficherToast("✅ UE modifiée avec succès !");
}

// ── SUPPRIMER UNE UE ──
function supprimerUE(index) {
    if (!confirm("Voulez-vous vraiment supprimer cette UE et toutes ses matières ?")) return;

    listeUE.splice(index, 1);

    if (ueSelectionnee === index) {
        ueSelectionnee = null;
        ueSelectionneeLabel.textContent = "Aucune UE sélectionnée";
    } else if (ueSelectionnee > index) {
        ueSelectionnee--;
    }

    afficherUE();
    afficherToast("✅ UE supprimée !");
}

// ── SUPPRIMER UNE MATIÈRE ──
function supprimerMatiere(indexUE, indexMatiere) {
    if (!confirm("Supprimer cette matière ?")) return;
    listeUE[indexUE].matieres.splice(indexMatiere, 1);
    afficherUE();
    afficherToast("✅ Matière supprimée !");
}

// ── MISE À JOUR DES STATS ──
function majStats() {
    let totalMatieres = 0;
    listeUE.forEach(ue => totalMatieres += ue.matieres.length);
    document.getElementById("nbUE").textContent = listeUE.length;
    document.getElementById("nbMatieres").textContent = totalMatieres;
}

// ── TOAST ──
function afficherToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast active" + (type === "error" ? " error" : "");
    setTimeout(() => toast.className = "toast", 3000);
}
