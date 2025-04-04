// Initialisation de la carte centrée sur une position (exemple : Paris)
var map = L.map('map').setView([48.8566, 2.3522], 13);

// Ajout d’un fond de carte OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Ajout d’un marqueur
var marker = L.marker([48.8566, 2.3522]).addTo(map);

// Ajout d’un popup au clic
marker.bindPopup("<b>Bienvenue à Paris !</b><br>Ceci est un exemple de carte interactive.").openPopup();
