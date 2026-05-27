document.addEventListener('DOMContentLoaded',function() {
    const popWindow = document.getElementById('custom-image-window');
    const image = document.getElementById('custom-window-img');
    const closeButton = document.getElementById('custom-window-close');
    const buttons = document.querySelectorAll('.custom-popup-btn'); //ΠΡΟΣΟΧΗ θέλουμε το js file (δηλαδή να πεταχτεί το παράθυρο) να εκτελεστεί όταν ο χρήστης θα πατήσει σε κάθε αναφορά το button προβολή φωτογραφίας. Εμεις αυτό που θα κάνουμε θα είναι να αλλάζουμε την φώτο του παραθύρου ανάλογα με το σε ποια αναφορά ανήκει το κουμπί που πάτησε ο χρήστης

    //Για κάθε κουμπί προβολή φωτογραφίας λοιπον...
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            //Την φωτογραφία την βρίσκουμε από το path της στον φάκελο uploads (Το path είναι φορτωμένο στο κουμπί που ακούει στον event listener που κάνει trigger το file) 
            const imageSrc = this.getAttribute('data-image-src');
            //Αφού πήραμε το path από το attribute του button το φορτώνουμε στο attribute του παραθύρου που έχει γραφτεί στο browse.php (Είναι κρυφό πριν πατηθεί το κουμπί).
            image.src = imageSrc;
            //Λίγη css για όμορφη εμφάνιση του κρυφού παραθύρου στον χρήστη
            popWindow.style.display = 'flex'; //Κεντραρισμένο το παράθυρο
        });
    });

    //Eventlistener και για το closeButton (Ουσιαστικά πειράζουμε το css attribute να μην φαίνεται)
    closeButton.addEventListener('click', function(){
        popWindow.style.display = 'none';
    });
});