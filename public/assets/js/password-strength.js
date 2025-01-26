class PasswordStrengthMeter {
    constructor(passwordInput, strengthMeter) {
        this.passwordInput = passwordInput;
        this.progressBar = strengthMeter.querySelector(".progress-bar");
        this.strengthText = strengthMeter.querySelector(".strength-text") || null;

        // Éléments de vérification
        this.checks = {
            length: { element: document.getElementById('length-check') },
            letter: { element: document.getElementById('letter-check') },
            number: { element: document.getElementById('number-check') }
        };

        this.strengthClasses = {
            0: "bg-danger animate__animated animate__fadeIn",
            1: "bg-warning animate__animated animate__fadeIn",
            2: "bg-info animate__animated animate__fadeIn",
            3: "bg-success animate__animated animate__fadeIn",
            4: "bg-success animate__animated animate__fadeIn",
            5: "bg-success animate__animated animate__fadeIn",
        };
    }

    update(password) {
        const strength = this.checkStrength(password);
        this.updateUI(strength);
        this.updateChecks(password);
        return strength.score >= 3;
    }

    checkStrength(password) {
        let score = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            numbers: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
        };

        score = Object.values(checks).filter(Boolean).length;

        return {
            score,
            feedback: this.getFeedback(score),
            checks,
        };
    }

    updateChecks(password) {
        const validations = {
            length: password.length >= 8,
            letter: /[A-Za-z]/.test(password),
            number: /[0-9]/.test(password)
        };

        for (const [key, isValid] of Object.entries(validations)) {
            const element = this.checks[key].element;
            if (element) {
                const icon = element.querySelector('i');
                if (icon) {
                    icon.className = isValid ? 
                        'fas fa-check-circle me-2 text-success' : 
                        'fas fa-times-circle me-2 text-danger';
                }
                element.classList.toggle('text-success', isValid);
                element.classList.toggle('text-danger', !isValid);
            }
        }
    }

    getFeedback(score) {
        const feedbacks = {
            0: "Très faible",
            1: "Faible",
            2: "Moyen",
            3: "Fort",
            4: "Très fort",
            5: "Excellent",
        };
        return feedbacks[score] || "";
    }

    updateUI({ score, feedback }) {
        const percentage = (score / 5) * 100;

        // Animation de la barre de progression
        this.progressBar.style.transition = "width 0.3s ease-in-out";
        this.progressBar.style.width = `${percentage}%`;
        this.progressBar.className = `progress-bar ${this.strengthClasses[score]}`;

        // Animation du texte
        if (this.strengthText) {
            this.strengthText.classList.add("animate__animated", "animate__fadeIn");
            this.strengthText.textContent = feedback;
            this.strengthText.className =
                "strength-text small animate__animated animate__fadeIn " +
                (score >= 3 ? "text-success" : "text-danger");
        }

        // Nettoyage des classes d'animation
        setTimeout(() => {
            this.progressBar.classList.remove("animate__animated", "animate__fadeIn");
            if (this.strengthText) {
                this.strengthText.classList.remove("animate__animated", "animate__fadeIn");
            }
        }, 1000);
    }
}

// Export pour les modules ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PasswordStrengthMeter;
}