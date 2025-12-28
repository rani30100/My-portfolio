// Créer des particules animées
    const particlesContainer = document.getElementById('particles');
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.width = Math.random() * 5 + 2 + 'px';
        particle.style.height = particle.style.width;
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        particlesContainer.appendChild(particle);
    }

// Gestion du formulaire de contact
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    const formMessage = document.getElementById('formMessage');

    button.textContent = 'Envoi en cours... ⏳';
    button.disabled = true;
    formMessage.textContent = '';

    const formData = new FormData(this);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        message: formData.get('message')
    };

    try {
        const response = await fetch('../src/contact.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const text = await response.text(); // Récupère d'abord le texte brut
        console.log('Réponse brute:', text);
        const result = JSON.parse(text);

        let result;
        try {
            result = JSON.parse(text); // Parse ensuite
        } catch (parseError) {
            console.error('Réponse brute du serveur:', text); 
            throw new Error('Impossible de parser la réponse JSON du serveur');
        }

        if (result.success) {
            button.textContent = 'Message envoyé ! ✅';
            button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            this.reset();
            formMessage.style.color = '#059669';
            formMessage.textContent = result.message;
        } else {
            console.error('Erreur serveur:', result.message);
            button.textContent = 'Erreur ❌';
            button.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            formMessage.style.color = '#dc2626';
            formMessage.textContent = result.message;
        }
    } catch (error) {
        console.error('Erreur complète:', error);
        button.textContent = 'Erreur ❌';
        button.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        formMessage.style.color = '#dc2626';
        formMessage.textContent = error.message;
    }

    // Remise à l’état initial du bouton après 2s
    setTimeout(() => {
        button.textContent = originalText;
        button.disabled = false;
        button.style.background = '';
    }, 2000);
});



    // Animation au scroll pour les cartes de projets
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.project-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // Animation des skills au hover
    document.querySelectorAll('.skill-tag').forEach(skill => {
        skill.addEventListener('mouseenter', function() {
            this.style.zIndex = '100';
        });
    });