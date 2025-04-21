// Fonction pour simuler les interactions du navigateur
document.addEventListener("DOMContentLoaded", () => {
    // Mettre à jour l'heure dans la barre de statut (si présente)
    function updateTime() {
      const timeElement = document.querySelector(".time")
      if (timeElement) {
        const now = new Date()
        let hours = now.getHours()
        const minutes = now.getMinutes().toString().padStart(2, "0")
        const ampm = hours >= 12 ? "PM" : "AM"
  
        hours = hours % 12
        hours = hours ? hours : 12 // l'heure '0' doit être '12'
  
        const timeString = `${hours}:${minutes} ${ampm}`
        timeElement.textContent = timeString
      }
    }
  
    // Mettre à jour l'heure si l'élément existe
    updateTime()
    setInterval(updateTime, 60000)
  
    // Simuler le clic sur les boutons du navigateur
    const backButton = document.querySelector(".browser-button.back")
    if (backButton) {
      backButton.addEventListener("click", () => {
        // Vérifier si nous sommes sur la page publication.php
        if (window.location.href.includes("publication.php")) {
          window.location.href = "portfolio.php"
        } else if (window.location.href.includes("portfolio.php")) {
          window.location.href = "profil.php"
        } else if (window.location.href.includes("modifier-publication.php")) {
          window.location.href = "publication.php?id=" + new URLSearchParams(window.location.search).get("id")
        } else if (window.location.href.includes("ajouter-publication.php")) {
          window.location.href = "portfolio.php"
        }
      })
    }
  
    // Simuler le clic sur les boutons de navigation
    const forwardButton = document.querySelector(".browser-button.forward")
    if (forwardButton) {
      forwardButton.addEventListener("click", () => {
        // Fonctionnalité à implémenter
        console.log("Forward button clicked")
      })
    }
  
    const refreshButton = document.querySelector(".browser-button.refresh")
    if (refreshButton) {
      refreshButton.addEventListener("click", () => {
        window.location.reload()
      })
    }
  
    const homeButton = document.querySelector(".browser-button.home")
    if (homeButton) {
      homeButton.addEventListener("click", () => {
        window.location.href = "index.php"
      })
    }
  
    // Gestion des publications dans la grille
    const postItems = document.querySelectorAll(".post-item")
    postItems.forEach((item) => {
      item.addEventListener("click", function () {
        const postId = this.getAttribute("data-id") || "1"
        window.location.href = `publication.php?id=${postId}`
      })
    })
  
    // Gestion du tri des avis
    const sortSelect = document.getElementById("sort")
    if (sortSelect) {
      sortSelect.addEventListener("change", function () {
        const reviewsContainer = document.querySelector(".reviews-container")
        const reviews = Array.from(reviewsContainer.querySelectorAll(".review"))
  
        if (this.value === "highest") {
          // Trier par note la plus haute
          reviews.sort((a, b) => {
            const noteA = Number.parseInt(a.getAttribute("data-note")) || 0
            const noteB = Number.parseInt(b.getAttribute("data-note")) || 0
            return noteB - noteA
          })
        } else if (this.value === "lowest") {
          // Trier par note la plus basse
          reviews.sort((a, b) => {
            const noteA = Number.parseInt(a.getAttribute("data-note")) || 0
            const noteB = Number.parseInt(b.getAttribute("data-note")) || 0
            return noteA - noteB
          })
        } else if (this.value === "recent") {
          // Trier par date la plus récente
          reviews.sort((a, b) => {
            const dateA = a.getAttribute("data-date") || ""
            const dateB = b.getAttribute("data-date") || ""
            return dateB.localeCompare(dateA)
          })
        }
  
        // Réinsérer les avis triés
        reviews.forEach((review) => {
          reviewsContainer.appendChild(review)
        })
      })
    }
  
    // Gestion des boutons d'action
    const addPostButton = document.querySelector(".add-post-button")
    if (addPostButton) {
      addPostButton.addEventListener("click", (e) => {
        e.preventDefault()
        window.location.href = "ajouter-publication.php"
      })
    }
  
    const editButton = document.querySelector(".edit-button")
    if (editButton) {
      editButton.addEventListener("click", function (e) {
        e.preventDefault()
        const url = this.getAttribute("href")
        window.location.href = url
      })
    }
  
    const deleteButton = document.querySelector(".delete-button")
    if (deleteButton) {
      deleteButton.addEventListener("click", function (e) {
        e.preventDefault()
        const postId = this.getAttribute("data-id")
        if (confirm("Êtes-vous sûr de vouloir supprimer ce post ?")) {
          window.location.href = "supprimer-publication.php?id=" + postId
        }
      })
    }
  
    // Gestion de l'ajout de photo
    const addPhotoButton = document.querySelector(".add-photo-button")
    if (addPhotoButton) {
      addPhotoButton.addEventListener("click", (e) => {
        e.preventDefault()
        alert('Fonctionnalité "Ajouter une photo" à implémenter')
      })
    }
  
    // Gestion de l'icône d'aide
    const helpIcon = document.querySelector(".help-icon")
    if (helpIcon) {
      helpIcon.addEventListener("click", () => {
        alert("Besoin d'aide ? Contactez-nous à support@lsd.com")
      })
    }
})