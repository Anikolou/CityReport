document.addEventListener("DOMContentLoaded", function() {
    const upvoteButtons = document.querySelectorAll(".upvote_button");
    
    upvoteButtons.forEach(button => {
        // Προσθέτουμε τη λέξη async εδώ για να μπορούμε να χρησιμοποιήσουμε το await μέσα
        button.addEventListener("click", async function() {
            const ticketId = this.getAttribute("data-ticket");
            const btn = this;
            
            let formData = new FormData();
            formData.append("ticket_id", ticketId);
            
            // Ξεκινάει το μπλοκ ελέγχου
            try {
                // Περιμένουμε (await) να ολοκληρωθεί το upvote_db_cookie
                const response = await fetch("upvote_db_cookie.php", {
                    method: "POST",
                    body: formData
                });
                
                // Περιμένουμε (await) να μεταφραστεί η απάντηση σε JSON
                const data = await response.json();
                
                if (data.status === "success") {
                    // Ανανέωση του μετρητή
                    btn.querySelector(".vote-count").textContent = data.new_count;
                    
                    // Απενεργοποίηση του κουμπιού
                    btn.setAttribute("disabled", "true");
                    btn.classList.remove("btn-outline-primary");
                    btn.classList.add("btn-secondary");
                } else {
                    // Μήνυμα σε περίπτωση που έχει ήδη ψηφίσει
                    alert(data.message);
                }
                
            } catch (error) {
                // Αν κάτι πάει στραβά (π.χ. πέσει το ίντερνετ ή σκάσει ο server), το πιάνουμε εδώ
                console.error("Σφάλμα:", error);
            }
        });
    });
});