// script.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('inscriptionForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validation côté client
            let errors = [];
            
            const nom = document.getElementById('nom').value;
            const prenom = document.getElementById('prenom').value;
            const email = document.getElementById('email').value;
            const mdp = document.getElementById('mdp').value;
            const siret = document.getElementById('siret').value;
            const codeNaf = document.getElementById('code_naf').value;
            
            // Validation des champs obligatoires
            if (!nom) errors.push("Le nom est requis");
            if (!prenom) errors.push("Le prénom est requis");
            if (!email) errors.push("L'email est requis");
            if (!mdp) errors.push("Le mot de passe est requis");
            
            // Validation du format d'email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                errors.push("Format d'email invalide");
            }
            
            // Validation du mot de passe
            if (mdp && mdp.length < 8) {
                errors.push("Le mot de passe doit contenir au moins 8 caractères");
            }
            
            // Validation du SIRET (si fourni)
            if (siret && (!/^[0-9]{14}$/.test(siret))) {
                errors.push("Le SIRET doit contenir 14 chiffres");
            }
            
            // Validation du code NAF (si fourni)
            if (codeNaf && (!/^[0-9]{4}[A-Z]$/.test(codeNaf))) {
                errors.push("Le code NAF doit être au format 0000A");
            }
            
            // Afficher les erreurs ou soumettre le formulaire
            if (errors.length > 0) {
                e.preventDefault();
                alert("Erreurs de validation:\n" + errors.join("\n"));
            }
        });
    }
});