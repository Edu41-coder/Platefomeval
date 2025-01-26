class FormValidator {
  constructor(form) {
    this.form = form;
  }

  validateField(field, passwordMeter = null) {
    const value = field.value.trim();
    const validators = {
      prenom: {
        isValid: value.length >= 2 && /^[a-zA-ZÀ-ÿ\s'-]+$/.test(value),
        message: "Le prénom doit contenir au moins 2 caractères valides",
      },
      nom: {
        isValid: value.length >= 2 && /^[a-zA-ZÀ-ÿ\s'-]+$/.test(value),
        message: "Le nom doit contenir au moins 2 caractères valides",
      },
      email: {
        isValid: /^[a-zA-Z0-9._%+-]+@.*univ-fr\.net$/.test(value),
        message: "Veuillez utiliser votre adresse email universitaire (@univ-fr.net)",
      },
      password: {
        isValid: true,
        message: "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre",
      },
      password_confirm: {
        isValid: value === this.form.querySelector("#password")?.value,
        message: "Les mots de passe ne correspondent pas",
      },
    };

    const validation = validators[field.name] || { isValid: true, message: "" };
    
    // Ajoutez un log pour chaque validation
    console.log(`Validation de ${field.name}: ${validation.isValid ? 'réussie' : 'échouée'}`);
    if (!validation.isValid) {
      console.log(`Message d'erreur: ${validation.message}`);
    }

    this.updateFieldValidation(field, validation);
    return validation.isValid;
  }

  updateFieldValidation(field, { isValid, message }) {
    // Retirer les anciennes classes
    field.classList.remove(
      "is-invalid",
      "is-valid",
      "animate__animated",
      "animate__shakeX",
      "animate__fadeIn"
    );

    // Ajouter les nouvelles classes avec animation
    if (isValid) {
      field.classList.add("is-valid", "animate__animated", "animate__fadeIn");
    } else {
      field.classList.add("is-invalid", "animate__animated", "animate__shakeX");
      const feedback = field.closest(".mb-3")?.querySelector(".invalid-feedback");
      if (feedback) {
        feedback.textContent = message;
        feedback.classList.add("animate__animated", "animate__fadeIn");
      }
    }

    // Nettoyer les classes d'animation
    setTimeout(() => {
      field.classList.remove(
        "animate__animated",
        "animate__shakeX",
        "animate__fadeIn"
      );
      const feedback = field.closest(".mb-3")?.querySelector(".invalid-feedback");
      if (feedback) {
        feedback.classList.remove("animate__animated", "animate__fadeIn");
      }
    }, 1000);
  }

  validateForm() {
    return Array.from(this.form.querySelectorAll("input[required]")).every(
      (input) => this.validateField(input)
    );
  }

  setupLiveValidation(passwordMeter) {
    this.form.querySelectorAll("input[required]").forEach((input) => {
      ["blur", "input"].forEach((eventType) => {
        input.addEventListener(eventType, () => {
          if (eventType === "blur" || input.classList.contains("is-invalid")) {
            this.validateField(input, passwordMeter);
          }
        });
      });
    });
  }

  validatePasswordConfirm(field) {
    const password = document.getElementById('password').value;
    const confirmPassword = field.value;
    
    if (confirmPassword !== password) {
        throw new Error('Les mots de passe ne correspondent pas');
    }
    return true;
  }
}

class RegisterForm {
  constructor(formId) {
    this.form = document.getElementById(formId);
    if (!this.form) return;

    this.validator = new FormValidator(this.form);
    this.initializeComponents();
    this.setupEventListeners();
  }

  initializeComponents() {
    const passwordInput = this.form.querySelector("#password");
    const strengthMeter = this.form.querySelector(".password-strength");

    if (passwordInput && strengthMeter) {
      this.passwordMeter = new PasswordStrengthMeter(passwordInput, strengthMeter);
      
      // Ajouter l'événement input pour mettre à jour la force du mot de passe
      passwordInput.addEventListener('input', () => {
        this.passwordMeter.update(passwordInput.value);
      });
    }

    // Initialisation de l'alerte d'erreur avec animation Bootstrap
    if (!document.getElementById("registerError")) {
      const errorDiv = document.createElement("div");
      errorDiv.id = "registerError";
      errorDiv.className =
        "alert alert-danger alert-dismissible fade animate__animated animate__fadeIn d-none";
      errorDiv.setAttribute("role", "alert");
      this.form.insertBefore(errorDiv, this.form.firstChild);
    }
  }

  setupEventListeners() {
    this.validator.setupLiveValidation(this.passwordMeter);
    this.form.addEventListener("submit", (e) => this.handleSubmit(e));

    // Validation du mot de passe de confirmation en temps réel
    const confirmPassword = this.form.querySelector("#password_confirm");
    if (confirmPassword) {
      this.form.querySelector("#password").addEventListener("input", () => {
        if (confirmPassword.value) {
          this.validator.validateField(confirmPassword, this.passwordMeter);
        }
      });
    }
  }

  async handleSubmit(e) {
    e.preventDefault();
    console.log('Form submission started');

    if (!this.validator.validateForm()) {
      console.log('Form validation failed');
      return;
    }

    const submitBtn = this.form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector(".spinner-border");
    const btnText = submitBtn.querySelector(".btn-text");
    const errorDiv = document.getElementById("registerError");

    try {
      this.updateSubmitButton(submitBtn, spinner, btnText, true);
      console.log('Submitting form data...');
      const formData = new FormData(this.form);
      console.log('=== Form Data ===');
      formData.forEach((value, key) => {
        console.log(`${key}:`, key.includes('password') ? '***' : value);
      });
      
      // Vérifier que tous les champs requis sont présents
      const requiredFields = ['prenom', 'nom', 'email', 'password', 'password_confirm', 'csrf_token'];
      for (const field of requiredFields) {
        if (!formData.has(field)) {
          console.error(`Champ manquant: ${field}`);
        }
      }

      const response = await this.submitData();
      console.log('Response received:', response);
      const data = await response.json();
      console.log('Response data:', data);

      if (!response.ok) {
        throw new Error(data.message || 'Une erreur est survenue');
      }

      this.showSuccess();
      setTimeout(() => {
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      }, 2000);

    } catch (error) {
      this.showError(errorDiv, error.message);
    } finally {
      this.updateSubmitButton(submitBtn, spinner, btnText, false);
    }
  }

  updateSubmitButton(button, spinner, text, isLoading) {
    if (isLoading) {
      button.disabled = true;
      text.classList.add("animate__animated", "animate__fadeOut");
      setTimeout(() => {
        spinner.classList.remove("d-none");
        text.textContent = "Inscription en cours...";
        text.classList.remove("animate__fadeOut");
        text.classList.add("animate__fadeIn");
      }, 200);
    } else {
      text.classList.add("animate__animated", "animate__fadeOut");
      setTimeout(() => {
        spinner.classList.add("d-none");
        text.textContent = "S'inscrire";
        button.disabled = false;
        text.classList.remove("animate__fadeOut");
        text.classList.add("animate__fadeIn");
      }, 200);
    }
  }

  showError(errorElement, message) {
    if (!errorElement) return;

    errorElement.innerHTML = `
              <div class="animate__animated animate__fadeIn">
                  ${message}
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          `;
    errorElement.classList.remove("d-none");
    errorElement.classList.add("show");

    setTimeout(() => {
      const alertContent = errorElement.querySelector("div");
      alertContent.classList.remove("animate__fadeIn");
      alertContent.classList.add("animate__fadeOut");

      setTimeout(() => {
        const bsAlert = new bootstrap.Alert(errorElement);
        bsAlert.close();
      }, 500);
    }, 4500);
  }

  showSuccess() {
    const successDiv = document.createElement("div");
    successDiv.className =
      "alert alert-success animate__animated animate__fadeIn";
    successDiv.textContent = "Inscription réussie ! Redirection...";
    this.form.insertBefore(successDiv, this.form.firstChild);
  }

  async submitData() {
    const formData = new FormData(this.form);
    console.log('=== Form Data ===');
    formData.forEach((value, key) => {
      console.log(`${key}:`, key.includes('password') ? '***' : value);
    });

    // Utiliser directement l'URL du formulaire sans modification
    const url = this.form.getAttribute('action');
    
    return fetch(url, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });
  }
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");
  if (form) {
    new RegisterForm("registerForm");
  }
});