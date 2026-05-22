async function forwardGeocode(query) {
    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`;
    const response = await fetch(url);
    const results = await response.json();
    
    // returns: results[0].lat, results[0].lon, results[0].display_name
    return results; 
}

document.addEventListener("DOMContentLoaded", function() {
    const reportForm = document.getElementById('reportForm');
    const addressInput = document.getElementById('address');
    
    if (reportForm) {
        reportForm.addEventListener('submit', async function(event) {
            // 1. Σταματάμε την υποβολή της φόρμας
            event.preventDefault();
            
            const addressValue = addressInput.value.trim();
            addressInput.classList.remove('is-invalid');

            try {
                // 2. Καλούμε τη συνάρτηση 
                const results = await forwardGeocode(addressValue);

                // 3. Ελέγχουμε αν επιστράφηκαν δεδομένα
                if (results && results.length > 0) {
                    // Παίρνουμε το latitude και longitude 
                    const lat = results[0].lat;
                    const lon = results[0].lon;

                    // ΠΡΟΣΘΗΚΗ: Γράφουμε τις τιμές στα κρυφά πεδία της φόρμας στο index.php
                    // ώστε να τις πάρει το $_POST['latitude'] και $_POST['longitude'] στο insertIssue.php
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;
                    
                    // Υποβάλουμε τελικά τη φόρμα στο php
                    reportForm.submit();
                } else {
                    // Αν το API δεν βρει την διεύθυνση
                    addressInput.classList.add('is-invalid');
                }
            } catch (error) {
                console.error("Σφάλμα:", error);
                addressInput.classList.add('is-invalid');
            }
        });
    }
})