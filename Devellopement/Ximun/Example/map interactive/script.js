let map; // carte globale

function geocodeAdresse() {
    const adresse = document.getElementById("adresse").value;
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(adresse)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                console.log("Latitude :", lat);
                console.log("Longitude :", lon);

                // Si la carte existe déjà, on la met à jour
                if (!map) {
                    map = L.map('map').setView([lat, lon], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                } else {
                    map.setView([lat, lon], 13);
                }

                // Supprimer anciens marqueurs si besoin
                L.marker([lat, lon]).addTo(map)
                  .bindPopup("Adresse trouvée ici")
                  .openPopup();

            } else {
                console.error("Adresse non trouvée.");
                alert("Adresse introuvable !");
            }
        });
}

