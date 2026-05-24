//κώδικας για φόρτωση του χάρτη στο footer
//περιμένουμε πρώτα να γίνει render το DOM και τότε εκτελείται ο κώδικας.
document.addEventListener("DOMContentLoaded" , function(){
                // Αρχικοποίηση χάρτη με τις συντεταγμένες του Δημαρχείου
                var footerMap = L.map('footer-map').setView([37.9415, 23.6528], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(footerMap);
    
                var marker = L.marker([37.9415, 23.6528]).addTo(footerMap);
});