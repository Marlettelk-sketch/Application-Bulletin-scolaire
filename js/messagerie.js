// ── DONNÉES ──
let conversations = [
    {
        id: 1,
        nom: "Administration",
        type: "admin",
        role: "Service administratif",
        nonLus: 2,
        messages: [
            { texte: "Bonjour, votre dossier d'inscription a été validé.", envoye: false, heure: "09:10" },
            { texte: "Merci beaucoup pour l'information !", envoye: true, heure: "09:15" },
            { texte: "N'oubliez pas de finaliser le paiement avant le 30.", envoye: false, heure: "09:20" }
        ]
    },
    {
        id: 2,
        nom: "Prof. Amoussou",
        type: "prof",
        role: "Enseignant — Algorithmique",
        nonLus: 1,
        messages: [
            { texte: "Bonjour, avez-vous des questions sur le dernier devoir ?", envoye: false, heure: "Hier, 14:30" },
            { texte: "Oui, je voulais savoir si on peut utiliser des fonctions récursives.", envoye: true, heure: "Hier, 14:45" },
            { texte: "Bien sûr, c'est même recommandé pour cet exercice.", envoye: false, heure: "Hier, 15:00" }
        ]
    },
    {
        id: 3,
        nom: "Prof. Koudou",
        type: "prof",
        role: "Enseignant — Programmation Web",
        nonLus: 0,
        messages: [
            { texte: "Le cours de vendredi est déplacé à jeudi 14h.", envoye: false, heure: "Lun, 10:00" },
            { texte: "Très bien, merci pour l'info.", envoye: true, heure: "Lun, 10:05" }
        ]
    }
];

let conversationActive = null;

// ── AFFICHER LISTE CONVERSATIONS ──
function afficherConversations(filtre = "") {
    const container = document.getElementById("conversationsListe");

    let liste = conversations;
    if (filtre) {
        liste = conversations.filter(c => c.nom.toLowerCase().includes(filtre.toLowerCase()));
    }

    if (liste.length === 0) {
        container.innerHTML = `<p style="text-align:center;color:#aaa;padding:20px;font-size:13px;">Aucune conversation trouvée.</p>`;
        return;
    }

    container.innerHTML = liste.map(conv => {
        const dernierMsg = conv.messages[conv.messages.length - 1];
        const initiale = conv.nom.charAt(0).toUpperCase();
        const classeAvatar = conv.type === "admin" ? "admin" : (conv.type === "prof" ? "prof" : "");

        return `
            <div class="conv-item ${conversationActive === conv.id ? 'selectionnee' : ''}" onclick="ouvrirConversation(${conv.id})">
                <div class="conv-avatar ${classeAvatar}">${initiale}</div>
                <div class="conv-details">
                    <div class="conv-nom">
                        <span>${conv.nom}</span>
                        <span class="conv-temps">${dernierMsg ? dernierMsg.heure.split(',').pop().trim() : ''}</span>
                    </div>
                    <div class="conv-dernier">${dernierMsg ? dernierMsg.texte : 'Aucun message'}</div>
                </div>
                ${conv.nonLus > 0 ? `<div class="badge-nonlu">${conv.nonLus}</div>` : ''}
            </div>
        `;
    }).join("");

    majStats();
}

// ── OUVRIR UNE CONVERSATION ──
function ouvrirConversation(id) {
    conversationActive = id;
    const conv = conversations.find(c => c.id === id);
    conv.nonLus = 0;

    document.getElementById("chatVide").style.display = "none";
    document.getElementById("chatHeader").style.display = "flex";
    document.getElementById("messagesZone").style.display = "flex";
    document.getElementById("messageInput").style.display = "flex";

    document.getElementById("chatAvatar").textContent = conv.nom.charAt(0).toUpperCase();
    document.getElementById("chatNom").textContent = conv.nom;
    document.getElementById("chatRole").textContent = conv.role;

    afficherMessages(conv);
    afficherConversations();
}

// ── AFFICHER MESSAGES ──
function afficherMessages(conv) {
    const zone = document.getElementById("messagesZone");
    zone.innerHTML = conv.messages.map(msg => `
        <div class="message-bulle ${msg.envoye ? 'message-envoye' : 'message-recu'}">
            ${msg.texte}
            <div class="message-heure">${msg.heure}</div>
        </div>
    `).join("");
    zone.scrollTop = zone.scrollHeight;
}

// ── ENVOYER MESSAGE ──
function envoyerMessage() {
    const input = document.getElementById("inputTexte");
    const texte = input.value.trim();

    if (!texte || conversationActive === null) return;

    const conv = conversations.find(c => c.id === conversationActive);
    const maintenant = new Date();
    const heure = maintenant.getHours().toString().padStart(2, "0") + ":" + maintenant.getMinutes().toString().padStart(2, "0");

    conv.messages.push({ texte, envoye: true, heure });

    input.value = "";
    afficherMessages(conv);
    afficherConversations();
}

function envoyerSiEnter(e) {
    if (e.key === "Enter") envoyerMessage();
}

// ── RECHERCHER ──
function rechercherConv(valeur) {
    afficherConversations(valeur);
}

// ── NOUVELLE CONVERSATION ──
function ouvrirNouvelleConv() {
    document.getElementById("modal").classList.add("active");
}

function fermerModal() {
    document.getElementById("modal").classList.remove("active");
}

document.getElementById("modal").addEventListener("click", function(e) {
    if (e.target === this) fermerModal();
});

function creerConversation() {
    const destinataire = document.getElementById("destinataire").value;
    const sujet = document.getElementById("sujet").value.trim();
    const message = document.getElementById("premierMessage").value.trim();

    if (!destinataire || !message) {
        afficherToast("Veuillez choisir un destinataire et écrire un message !", "error");
        return;
    }

    const type = destinataire === "Administration" ? "admin" : "prof";
    const role = destinataire === "Administration" ? "Service administratif" : "Enseignant";

    const nouvelleConv = {
        id: Date.now(),
        nom: destinataire,
        type: type,
        role: sujet ? sujet : role,
        nonLus: 0,
        messages: [
            { texte: message, envoye: true, heure: "Maintenant" }
        ]
    };

    conversations.unshift(nouvelleConv);
    fermerModal();
    document.getElementById("destinataire").value = "";
    document.getElementById("sujet").value = "";
    document.getElementById("premierMessage").value = "";

    afficherConversations();
    ouvrirConversation(nouvelleConv.id);
    afficherToast("✅ Message envoyé !");
}

// ── STATS ──
function majStats() {
    const totalConv = conversations.length;
    const totalNonLus = conversations.reduce((acc, c) => acc + c.nonLus, 0);

    document.getElementById("nbConversations").textContent = totalConv;
    document.getElementById("nbNonLus").textContent = totalNonLus;
}

// ── TOAST ──
function afficherToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast active" + (type === "error" ? " error" : "");
    setTimeout(() => toast.className = "toast", 3000);
}

// ── INIT ──
afficherConversations();
