// Met à jour le texte de la recherche en direct
function searchFunction() {
    let searchInput = document.getElementById("search").value;
    document.getElementById("search-text").textContent = searchInput;
}

// Met à jour la valeur du slider en direct
function updateSlider() {
    let sliderValue = document.getElementById("slider").value;
    document.getElementById("slider-value").textContent = sliderValue;
}
