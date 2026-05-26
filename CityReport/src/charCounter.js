// Περιμένουμε πρώτα να φορτώσει όλο το HTML (DOM) πριν τρέξουμε τον κώδικα
document.addEventListener('DOMContentLoaded', function() {
    
    // Βρίσκουμε όλα τα πεδία που έχουν την κλάση 'check-length'
    const inputsToValidate = document.querySelectorAll('.check-length');

    // Για κάθε ένα πεδίο...
    inputsToValidate.forEach(function(inputField) {
        
        // ...προσθέτουμε το event listener που ενεργοποιείται όταν γράφουμε κάτι (input)
        inputField.addEventListener('input', function() {
            
            // Διαβάζουμε τα όρια από τα data attributes του HTML
            const minChars = parseInt(this.getAttribute('data-min'));
            const maxChars = parseInt(this.getAttribute('data-max'));
            //Ορίζουμε που θα επιστρεφεί το μήνυμα στο html file
            const targetFeedback = this.getAttribute('data-feedback');
            
            // Βρίσκουμε το σωστό div για το μήνυμα
            const feedbackMessage = document.getElementById(targetFeedback);
            
            // Μετράμε τους χαρακτήρες του πεδίου που γράφουμε
            let charCount = this.value.length; 

            // Η λογική ελέγχου
            if (charCount > 0 && charCount < minChars) {
                feedbackMessage.innerHTML = `Αυξήστε την έκταση αυτού του κειμένου στους ${minChars} χαρακτήρες ή περισσότερο (αυτήν τη στιγμή χρησιμοποιείτε ${charCount}).`;
                feedbackMessage.style.display = "block";
            } 
            else if (charCount > maxChars) {
                feedbackMessage.innerHTML = `Μειώστε την έκταση του κειμένου. Επιτρέπονται μέχρι ${maxChars} χαρακτήρες (χρησιμοποιείτε ${charCount}).`;
                feedbackMessage.style.display = "block";
            } 
            else {
                feedbackMessage.style.display = "none";
            }
        });
    });
});