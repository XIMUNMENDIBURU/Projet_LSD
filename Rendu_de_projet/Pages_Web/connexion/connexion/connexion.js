// Declaration de la variable L
var L = L || {}

// Variables globales
let map
let marker
let mapInitialized = false

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", () => {
  // Gestion du formulaire
  const form = document.getElementById("inscriptionForm")

  if (form) {
    form.addEventListener("submit", (e) => {
      // Validation côté client
      const errors = []

      const nom = document.getElementById("nom").value
      const prenom = document.getElementById("prenom").value
      const email = document.getElementById("email").value
      const mdp = document.getElementById("mdp").value
      const siret = document.getElementById("siret").value
      const codeNaf = document.getElementById("code_naf").value
      const adresse = document.getElementById("adresse").value
      const loc_x = document.getElementById("loc_x").value
      const loc_y = document.getElementById("loc_y").value

      // Réinitialiser les messages d'erreur
      document.querySelectorAll(".form-error").forEach((el) => {
        el.textContent = ""
      })

      // Validation des champs obligatoires
      if (!nom) {
        errors.push("Le nom est requis")
        document.getElementById("error-nom").textContent = "Le nom est requis"
      }
      if (!prenom) {
        errors.push("Le prénom est requis")
        document.getElementById("error-prenom").textContent = "Le prénom est requis"
      }
      if (!email) {
        errors.push("L'email est requis")
        document.getElementById("error-email").textContent = "L'email est requis"
      }
      if (!mdp) {
        errors.push("Le mot de passe est requis")
        document.getElementById("error-mdp").textContent = "Le mot de passe est requis"
      }
      if (!adresse) {
        errors.push("L'adresse est requise")
        document.getElementById("error-adresse").textContent = "L'adresse est requise"
      }

      // Validation du format d'email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (email && !emailRegex.test(email)) {
        errors.push("Format d'email invalide")
        document.getElementById("error-email").textContent = "Format d'email invalide"
      }

      // Validation du mot de passe
      if (mdp && mdp.length < 8) {
        errors.push("Le mot de passe doit contenir au moins 8 caractères")
        document.getElementById("error-mdp").textContent = "Le mot de passe doit contenir au moins 8 caractères"
      }

      // Validation du SIRET (si fourni)
      if (siret && !/^[0-9]{14}$/.test(siret)) {
        errors.push("Le SIRET doit contenir 14 chiffres")
        document.getElementById("error-siret").textContent = "Le SIRET doit contenir 14 chiffres"
      }

      // Validation du code NAF (si fourni)
      if (codeNaf && !/^[0-9]{4}[A-Z]$/.test(codeNaf)) {
        errors.push("Le code NAF doit être au format 0000A")
        document.getElementById("error-code_naf").textContent = "Le code NAF doit être au format 0000A"
      }

      // Afficher les erreurs ou soumettre le formulaire
      if (errors.length > 0) {
        e.preventDefault()
        // L'affichage des erreurs se fait déjà sur chaque champ
      } else if (!loc_x || !loc_y) {
        // Si l'adresse n'a pas été géocodée, le faire automatiquement
        e.preventDefault()
        geocodeAdresse(adresse, () => {
          // Soumettre le formulaire après le géocodage
          form.submit()
        })
      }
    })
  }

  // Si on revient avec des erreurs du serveur, les afficher
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has("errors")) {
    try {
      const errors = JSON.parse(decodeURIComponent(urlParams.get("errors")))

      // Afficher les erreurs sur les champs correspondants
      if (errors.nom) document.getElementById("error-nom").textContent = errors.nom
      if (errors.prenom) document.getElementById("error-prenom").textContent = errors.prenom
      if (errors.email) document.getElementById("error-email").textContent = errors.email
      if (errors.mdp) document.getElementById("error-mdp").textContent = errors.mdp
      if (errors.siret) document.getElementById("error-siret").textContent = errors.siret
      if (errors.code_naf) document.getElementById("error-code_naf").textContent = errors.code_naf
      if (errors.adresse) document.getElementById("error-adresse").textContent = errors.adresse

      // Afficher les erreurs générales
      if (errors.general) {
        const serverMessages = document.getElementById("server-messages")
        serverMessages.innerHTML = `<div class="message error">${errors.general}</div>`
      }

      // Reprendre les valeurs soumises
      if (urlParams.has("submitted")) {
        const submitted = JSON.parse(decodeURIComponent(urlParams.get("submitted")))

        for (const [key, value] of Object.entries(submitted)) {
          const input = document.getElementById(key)
          if (input && key !== "mdp") {
            input.value = value
          }
        }
      }
    } catch (e) {
      console.error("Erreur lors du parsing des erreurs:", e)
    }
  }
})

// Fonction pour afficher/masquer la carte
function toggleMap(show) {
  const mapContainer = document.getElementById("map-container")
  mapContainer.style.display = show ? "flex" : "none"

  if (show && !mapInitialized) {
    initMap()
    mapInitialized = true
  }

  if (show) {
    const adresse = document.getElementById("adresse").value
    if (adresse) {
      geocodeAdresse(adresse)
    }
  }
}

// Fonction pour initialiser la carte
function initMap() {
  // Initialiser la carte avec une vue générale de la France
  map = L.map("map").setView([46.603354, 1.888334], 5)
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map)

  // Ajouter un événement de clic sur la carte
  map.on("click", (e) => {
    setMarker(e.latlng.lat, e.latlng.lng)
    reverseGeocode(e.latlng.lat, e.latlng.lng)
  })
}

// Fonction pour définir un marqueur
function setMarker(lat, lng) {
  // Mettre à jour les champs cachés
  document.getElementById("loc_x").value = lat
  document.getElementById("loc_y").value = lng

  // Supprimer l'ancien marqueur si présent
  if (marker) {
    map.removeLayer(marker)
  }

  // Ajouter un nouveau marqueur
  marker = L.marker([lat, lng]).addTo(map).bindPopup("Position sélectionnée").openPopup()

  // Centrer la carte sur le marqueur
  map.setView([lat, lng], 13)
}

// Fonction pour géocoder l'adresse
function geocodeAdresse(manualAddress = null, callback = null) {
  const adresse = manualAddress || document.getElementById("adresse").value

  if (!adresse) {
    document.getElementById("error-adresse").textContent = "Veuillez entrer une adresse"
    return
  }

  document.getElementById("error-adresse").textContent = ""
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(adresse)}&addressdetails=1`

  fetch(url, {
    headers: {
      "User-Agent": "LSD-Project/1.0",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.length > 0) {
        const lat = Number.parseFloat(data[0].lat)
        const lon = Number.parseFloat(data[0].lon)

        // Mettre à jour les champs cachés
        document.getElementById("loc_x").value = lat
        document.getElementById("loc_y").value = lon

        // Si la carte est initialisée, mettre à jour le marqueur
        if (mapInitialized) {
          setMarker(lat, lon)
        }

        // Si un callback est fourni, l'exécuter
        if (callback) {
          callback()
        }
      } else {
        document.getElementById("error-adresse").textContent = "Adresse introuvable, veuillez préciser"
        document.getElementById("loc_x").value = ""
        document.getElementById("loc_y").value = ""
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la géolocalisation:", error)
      document.getElementById("error-adresse").textContent = "Erreur lors de la géolocalisation, veuillez réessayer"
    })
}

// Fonction pour le géocodage inverse (obtenir une adresse à partir de coordonnées)
function reverseGeocode(lat, lon) {
  const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`

  fetch(url, {
    headers: {
      "User-Agent": "LSD-Project/1.0",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data && data.display_name) {
        document.getElementById("adresse").value = data.display_name
      }
    })
    .catch((error) => {
      console.error("Erreur lors du géocodage inverse:", error)
    })
}

// Fonction pour afficher l'aide
function showHelp() {
  alert(
    "Aide pour l'inscription:\n\n" +
      "- Les champs marqués d'un astérisque (*) sont obligatoires\n" +
      "- Le SIRET doit contenir 14 chiffres\n" +
      "- Le code NAF doit être au format 0000A\n" +
      "- Pour localiser votre adresse, cliquez sur le bouton 'Localiser'\n" +
      "- Vous pouvez également cliquer directement sur la carte pour sélectionner un emplacement",
  )
}
