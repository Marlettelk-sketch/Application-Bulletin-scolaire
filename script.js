/* =========================================================
   Application de bulletins scolaires — Logique front-end
   ⚠️ VERSION RECONNECTÉE AU BACKEND (api/*.php)
   Les anciennes données simulées (STUDENT, BULLETINS,
   EMPLOIS_DU_TEMPS) ont été retirées : tout vient maintenant
   de la base de données via fetch().
   ========================================================= */

let semestreSelectionne = "S2"; // par défaut, modifiable via le sélecteur

/* =========================================================
   Initialisation commune (toutes les pages internes)
   ========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    highlightActiveNav();
    initMenuToggle();
    initLoginForm();
    initRegisterForm();
    initEyeToggles(document);

    // Pages qui nécessitent d'être connecté : on vérifie la session
    // puis on charge les vraies données depuis le serveur.
    const pagesProtegees = ["dashboard", "bulletins", "profil", "emploi-du-temps"];
    if (pagesProtegees.includes(document.body.dataset.page)) {
        verifierSessionEtCharger();
    }

    initSemesterPicker();
    initSecureDownload();
});

/* ---------- Surligner le lien actif de la barre latérale ---------- */
function highlightActiveNav() {
    const page = document.body.dataset.page;
    if (!page) return;
    document.querySelectorAll(".sidebar nav a[data-nav]").forEach(a => {
        if (a.dataset.nav === page) a.classList.add("active");
    });
}

/* ---------- Menu mobile (hamburger) ---------- */
function initMenuToggle() {
    const btn = document.querySelector(".menu-toggle");
    const sidebar = document.querySelector(".sidebar");
    if (!btn || !sidebar) return;
    btn.addEventListener("click", () => sidebar.classList.toggle("open"));
    sidebar.querySelectorAll("a").forEach(a =>
        a.addEventListener("click", () => sidebar.classList.remove("open"))
    );
}

/* =========================================================
   SESSION : vérifier qui est connecté + remplir le prénom/nom
   ========================================================= */
async function verifierSessionEtCharger() {
    try {
        const reponse = await fetch("api/auth.php?action=verifier");
        const data = await reponse.json();

        if (!data.connecte) {
            window.location.href = "connexion.html";
            return;
        }

        remplirInfosUtilisateur(data);

        const page = document.body.dataset.page;
        if (page === "dashboard" || page === "bulletins") {
            await chargerFiliereEtNiveau();
            await renderNotes(semestreSelectionne);
        }
        if (page === "profil") {
            chargerProfilComplet();
            initProfilePage();
        }

    } catch (e) {
        console.error("Erreur de vérification de session :", e);
    }
}

/* ---------- Remplir le nom de l'étudiant dans la barre du haut ---------- */
function remplirInfosUtilisateur(data) {
    const initiales = (data.prenom?.[0] || "") + (data.nom?.[0] || "");

    document.querySelectorAll("[data-user-fullname]").forEach(el => {
        el.textContent = `${data.nom} ${data.prenom}`;
    });
    document.querySelectorAll("[data-user-firstname]").forEach(el => {
        el.textContent = data.prenom;
    });
    document.querySelectorAll("[data-user-initials]").forEach(el => {
        el.textContent = initiales.toUpperCase();
    });
}

/* ---------- Charger filière / niveau / option (carte dashboard) ---------- */
async function chargerFiliereEtNiveau() {
    try {
        const reponse = await fetch("api/utilisateurs.php?action=profil");
        const data = await reponse.json();
        if (data.erreur) return;

        document.querySelectorAll("[data-user-filiere]").forEach(el => {
            const infos = data.infos || {};
            el.textContent = `${infos.filiere || ''} - ${infos.niveau || ''}${infos.option_nom ? ' - ' + infos.option_nom : ''}`;
        });
    } catch (e) {
        console.error("Erreur chargement filière :", e);
    }
}

/* =========================================================
   SÉLECTEUR DE SEMESTRE
   ========================================================= */
function initSemesterPicker() {
    const params = new URLSearchParams(window.location.search);
    if (params.get("sem") === "s1") semestreSelectionne = "S1";

    const buttons = document.querySelectorAll(".semester-picker .seg-btn");
    if (!buttons.length) return;

    buttons.forEach(btn => {
        const semBtn = btn.dataset.sem === "s1" ? "S1" : "S2";
        btn.classList.toggle("active", semBtn === semestreSelectionne);

        btn.addEventListener("click", () => {
            semestreSelectionne = semBtn;
            buttons.forEach(b => b.classList.toggle("active", b === btn));
            renderNotes(semestreSelectionne);
        });
    });
}

/* =========================================================
   NOTES / BULLETIN (depuis api/notes.php)
   ========================================================= */
async function renderNotes(semestre) {
    const tbody = document.querySelector("#notesTableBody");
    const title = document.querySelector("#notesTitle");
    if (!tbody) return; // pas sur une page avec tableau de notes

    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#aaa;">Chargement…</td></tr>`;

    try {
        const reponse = await fetch(`api/notes.php?action=mes_notes&semestre=${semestre}`);
        const data = await reponse.json();

        if (data.erreur) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#aaa;">${data.erreur}</td></tr>`;
            return;
        }

        const labelSem = semestre === "S1" ? "Semestre 1" : "Semestre 2";
        if (title) title.textContent = `Notes récentes — ${labelSem}`;

        if (!data.notes || data.notes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#aaa;">Aucune note pour ce semestre.</td></tr>`;
        } else {
            const badgeClass = {
                "Bien": "bien", "Très bien": "tresbien",
                "Assez-bien": "assezbien", "Passable": "passable", "Insuffisant": "insuffisant"
            };

            tbody.innerHTML = data.notes.map(n => {
                const noteFmt = String(n.note).replace(".", ",");
                const noteClass = n.note < 10 ? "fail" : "pass";
                return `<tr>
                    <td>${n.matiere}</td>
                    <td class="note ${noteClass}">${noteFmt}</td>
                    <td>${n.coefficient}</td>
                    <td><span class="badge ${badgeClass[n.observation] || ''}">${n.observation}</span></td>
                </tr>`;
            }).join("");
        }

        const moyenneEl = document.querySelector("[data-moyenne]");
        if (moyenneEl) moyenneEl.textContent = data.moyenne !== null ? String(data.moyenne).replace(".", ",") : "—";

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#aaa;">Erreur de connexion au serveur.</td></tr>`;
    }
}

/* =========================================================
   AUTHENTIFICATION : Connexion
   ========================================================= */
function initLoginForm() {
    const form = document.querySelector("#loginForm");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const btn = form.querySelector("button[type='submit']");
        const email = form.querySelector("#loginEmail")?.value.trim();
        const motDePasse = form.querySelector("#loginPassword")?.value;
        const erreurEl = document.querySelector("#loginError");

        if (erreurEl) erreurEl.classList.remove("show");

        btn.textContent = "Connexion...";
        btn.disabled = true;

        try {
            const reponse = await fetch("api/auth.php?action=connexion", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, mot_de_passe: motDePasse })
            });
            const data = await reponse.json();

            if (data.erreur) {
                if (erreurEl) {
                    erreurEl.textContent = data.erreur;
                    erreurEl.classList.add("show");
                } else {
                    alert(data.erreur);
                }
                btn.textContent = "Se connecter";
                btn.disabled = false;
                return;
            }

            window.location.href = data.redirection || "tableau-de-bord.html";

        } catch (err) {
            if (erreurEl) {
                erreurEl.textContent = "Erreur de connexion au serveur.";
                erreurEl.classList.add("show");
            }
            btn.textContent = "Se connecter";
            btn.disabled = false;
        }
    });
}

/* =========================================================
   AUTHENTIFICATION : Inscription
   ========================================================= */
function initRegisterForm() {
    const form = document.querySelector("#registerForm");
    if (!form) return;

    const pwd = form.querySelector("#regPassword");
    const confirmPwd = form.querySelector("#regConfirm");
    const error = document.querySelector("#regError");
    const success = document.querySelector("#regSuccess");

    initEyeToggles(form);

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (pwd.value !== confirmPwd.value) {
            if (error) {
                error.textContent = "Les mots de passe ne correspondent pas.";
                error.classList.add("show");
            }
            return;
        }
        if (error) error.classList.remove("show");

        const nom = form.querySelector("#regNom")?.value.trim();
        const prenom = form.querySelector("#regPrenom")?.value.trim();
        const email = form.querySelector("#regEmail")?.value.trim();

        try {
            const reponse = await fetch("api/auth.php?action=inscription", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nom, prenom, email, mot_de_passe: pwd.value })
            });
            const data = await reponse.json();

            if (data.erreur) {
                if (error) {
                    error.textContent = data.erreur;
                    error.classList.add("show");
                } else {
                    alert(data.erreur);
                }
                return;
            }

            form.querySelectorAll("input, button").forEach(el => el.disabled = true);
            if (success) success.classList.remove("hidden");

            setTimeout(() => {
                window.location.href = "connexion.html";
            }, 2200);

        } catch (err) {
            if (error) {
                error.textContent = "Erreur de connexion au serveur.";
                error.classList.add("show");
            }
        }
    });
}

/* ---------- Afficher/cacher mot de passe (icône œil) ---------- */
function initEyeToggles(scope) {
    scope.querySelectorAll(".eye-toggle").forEach(eye => {
        eye.addEventListener("click", () => {
            const input = eye.previousElementSibling || document.getElementById(eye.dataset.target);
            if (!input) return;
            input.type = input.type === "password" ? "text" : "password";
            eye.classList.toggle("visible");
        });
    });
}

/* =========================================================
   PROFIL — chargement + mise à jour
   ========================================================= */
async function chargerProfilComplet() {
    try {
        const reponse = await fetch("api/utilisateurs.php?action=profil");
        const data = await reponse.json();
        if (data.erreur) return;

        const champs = {
            "#profilNom": data.nom,
            "#profilPrenom": data.prenom,
            "#profilEmail": data.email
        };
        Object.entries(champs).forEach(([selecteur, valeur]) => {
            const el = document.querySelector(selecteur);
            if (el) el.value = valeur || "";
        });

    } catch (e) {
        console.error("Erreur chargement profil :", e);
    }
}

function initProfilePage() {
    const form = document.querySelector("#profileForm");
    if (form && !form.dataset.bound) {
        form.dataset.bound = "1";
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const corps = {
                nom: form.querySelector("#profilNom")?.value.trim(),
                prenom: form.querySelector("#profilPrenom")?.value.trim(),
                email: form.querySelector("#profilEmail")?.value.trim()
            };

            try {
                const reponse = await fetch("api/utilisateurs.php?action=modifier_profil", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(corps)
                });
                const data = await reponse.json();

                showToast(data.erreur || "Profil mis à jour avec succès");
            } catch (err) {
                showToast("Erreur de connexion au serveur.");
            }
        });
    }

    const pwdForm = document.querySelector("#passwordForm");
    if (pwdForm && !pwdForm.dataset.bound) {
        pwdForm.dataset.bound = "1";
        initEyeToggles(pwdForm);
        const newPwd = pwdForm.querySelector("#newPassword");
        const confirmPwd = pwdForm.querySelector("#confirmNewPassword");
        const err = pwdForm.querySelector("#pwdError");

        pwdForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (newPwd.value !== confirmPwd.value) {
                if (err) err.classList.add("show");
                return;
            }
            if (err) err.classList.remove("show");

            try {
                const reponse = await fetch("api/utilisateurs.php?action=changer_mdp", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ nouveau_mot_de_passe: newPwd.value })
                });
                const data = await reponse.json();

                pwdForm.reset();
                showToast(data.erreur || "Mot de passe mis à jour avec succès");
            } catch (err2) {
                showToast("Erreur de connexion au serveur.");
            }
        });
    }
}

/* =========================================================
   TÉLÉCHARGEMENT DU BULLETIN
   ⚠️ Reconnexion partielle : la simulation de reconnaissance
   faciale / vérification de carte d'étudiant a été retirée
   (trop complexe pour le périmètre actuel d'un projet L2).
   Le téléchargement passe directement par telecharger.php,
   qui doit lui-même vérifier la session côté serveur.
   À ré-introduire plus tard si vous voulez garder cette
   fonctionnalité de vérification avancée.
   ========================================================= */
function initSecureDownload() {
    document.querySelectorAll("[data-telecharger-bulletin]").forEach(btn => {
        btn.addEventListener("click", () => {
            const sem = semestreSelectionne;
            window.location.href = `telecharger.php?semestre=${sem}`;
        });
    });
}

/* =========================================================
   Toast (notification flash en bas d'écran)
   ========================================================= */
function showToast(message) {
    let toast = document.querySelector(".toast-flash");
    if (!toast) {
        toast = document.createElement("div");
        toast.className = "toast-flash";
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 3000);
}
