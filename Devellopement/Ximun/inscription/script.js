// Variables globales
let map; 
let marker;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte avec une vue générale de la France
    map = L.map('map').setView([46.603354, 1.888334], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Gestion du formulaire
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
            const adresse = document.getElementById('adresse').value;
            const loc_x = document.getElementById('loc_x').value;
            const loc_y = document.getElementById('loc_y').value;
            
            // Réinitialiser les messages d'erreur
            document.querySelectorAll('.form-error').forEach(el => {
                el.textContent = "";
            });
            
            // Validation des champs obligatoires
            if (!nom) {
                errors.push("Le nom est requis");
                document.getElementById('error-nom').textContent = "Le nom est requis";
            }
            if (!prenom) {
                errors.push("Le prénom est requis");
                document.getElementById('error-prenom').textContent = "Le prénom est requis";
            }
            if (!email) {
                errors.push("L'email est requis");
                document.getElementById('error-email').textContent = "L'email est requis";
            }
            if (!mdp) {
                errors.push("Le mot de passe est requis");
                document.getElementById('error-mdp').textContent = "Le mot de passe est requis";
            }
            if (!adresse) {
                errors.push("L'adresse est requise");
                document.getElementById('error-adresse').textContent = "L'adresse est requise";
            }
            if (!loc_x || !loc_y) {
                errors.push("Veuillez vérifier l'adresse en cliquant sur le bouton");
                document.getElementById('error-adresse').textContent = "Veuillez vérifier l'adresse en cliquant sur le bouton";
            }
            
            // Validation du format d'email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                errors.push("Format d'email invalide");
                document.getElementById('error-email').textContent = "Format d'email invalide";
            }
            
            // Validation du mot de passe
            if (mdp && mdp.length < 8) {
                errors.push("Le mot de passe doit contenir au moins 8 caractères");
                document.getElementById('error-mdp').textContent = "Le mot de passe doit contenir au moins 8 caractères";
            }
            
            // Validation du SIRET (si fourni)
            if (siret && (!/^[0-9]{14}$/.test(siret))) {
                errors.push("Le SIRET doit contenir 14 chiffres");
                document.getElementById('error-siret').textContent = "Le SIRET doit contenir 14 chiffres";
            }
            
            // Validation du code NAF (si fourni)
            if (codeNaf && (!/^[0-9]{4}[A-Z]$/.test(codeNaf))) {
                errors.push("Le code NAF doit être au format 0000A");
                document.getElementById('error-code_naf').textContent = "Le code NAF doit être au format 0000A";
            }
            
            // Afficher les erreurs ou soumettre le formulaire
            if (errors.length > 0) {
                e.preventDefault();
                // L'affichage des erreurs se fait déjà sur chaque champ
            }
        });
    }

    // Si on revient avec des erreurs du serveur, les afficher
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('errors')) {
        try {
            const errors = JSON.parse(decodeURIComponent(urlParams.get('errors')));
            
            // Afficher les erreurs sur les champs correspondants
            if (errors.nom) document.getElementById('error-nom').textContent = errors.nom;
            if (errors.prenom) document.getElementById('error-prenom').textContent = errors.prenom;
            if (errors.email) document.getElementById('error-email').textContent = errors.email;
            if (errors.mdp) document.getElementById('error-mdp').textContent = errors.mdp;
            if (errors.siret) document.getElementById('error-siret').textContent = errors.siret;
            if (errors.code_naf) document.getElementById('error-code_naf').textContent = errors.code_naf;
            if (errors.adresse) document.getElementById('error-adresse').textContent = errors.adresse;
            
            // Afficher les erreurs générales
            if (errors.general) {
                const serverMessages = document.getElementById('server-messages');
                serverMessages.innerHTML = `<div class="message error">${errors.general}</div>`;
            }
            
            // Reprendre les valeurs soumises
            if (urlParams.has('submitted')) {
                const submitted = JSON.parse(decodeURIComponent(urlParams.get('submitted')));
                
                for (const [key, value] of Object.entries(submitted)) {
                    const input = document.getElementById(key);
                    if (input && key !== 'mdp') {
                        input.value = value;
                    }
                }
                
                // Si l'adresse a été soumise, essayer de la géocoder
                if (submitted.adresse) {
                    geocodeAdresse(submitted.adresse);
                }
            }
        } catch (e) {
            console.error("Erreur lors du parsing des erreurs:", e);
        }
    }
});

// Fonction pour géocoder l'adresse
function geocodeAdresse(manualAddress = null) {
    const adresse = manualAddress || document.getElementById("adresse").value;
    
    if (!adresse) {
        document.getElementById('error-adresse').textContent = "Veuillez entrer une adresse";
        return;
    }
    
    document.getElementById('error-adresse').textContent = "";
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(adresse)}&addressdetails=1`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                
                // Mettre à jour les champs cachés
                document.getElementById('loc_x').value = lat;
                document.getElementById('loc_y').value = lon;
                
                // Mettre à jour la carte
                map.setView([lat, lon], 13);
                
                // Supprimer l'ancien marqueur si présent
                if (marker) {
                    map.removeLayer(marker);
                }
                
                // Ajouter un nouveau marqueur
                marker = L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<strong>Adresse trouvée:</strong><br>${data[0].display_name}`)
                    .openPopup();
                    
            } else {
                document.getElementById('error-adresse').textContent = "Adresse introuvable, veuillez préciser";
                document.getElementById('loc_x').value = "";
                document.getElementById('loc_y').value = "";
            }
        })
        .catch(error => {
            console.error("Erreur lors de la géolocalisation:", error);
            document.getElementById('error-adresse').textContent = "Erreur lors de la géolocalisation, veuillez réessayer";
        });
}