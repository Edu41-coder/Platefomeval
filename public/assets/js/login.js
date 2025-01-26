console.log('login.js - File loaded immediately');

// Preserve console logs with delay
const originalLog = console.log;
const originalError = console.error;

// Custom logging function with delay
function delayedLog(message, data = null) {
    let logMessage = `[${new Date().toISOString()}] ${message}`;
    if (data !== null) {
        if (typeof data === 'object') {
            logMessage += '\n' + JSON.stringify(data, null, 2);
        } else {
            logMessage += ': ' + data;
        }
    }
    originalLog(logMessage);
}

// Override console methods
console.log = delayedLog;
console.error = (message) => delayedLog(`ERROR: ${message}`);

document.addEventListener('DOMContentLoaded', () => {
    delayedLog('=== LOGIN.JS INITIALIZATION ===');

    // Get form elements
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginButton = document.getElementById('loginButton');
    const errorDiv = document.getElementById('loginError');

    delayedLog('Form elements found:', {
        form: form ? 'Found' : 'Missing',
        emailInput: emailInput ? 'Found' : 'Missing',
        passwordInput: passwordInput ? 'Found' : 'Missing',
        loginButton: loginButton ? 'Found' : 'Missing',
        errorDiv: errorDiv ? 'Found' : 'Missing'
    });

    // Prevent form submission
    if (form) {
        form.onsubmit = (e) => {
            e.preventDefault();
            return false;
        };
    }

    // Get CSRF token from meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    let csrfToken = metaToken ? metaToken.getAttribute('content') : null;
    delayedLog('CSRF Token from meta tag:', csrfToken);

    // Function to show error message
    const showError = (message) => {
        delayedLog('Showing error message:', message);
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            console.error('Error div not found for message:', message);
            alert(message);
        }
    };

    // Function to clear error message
    const clearError = () => {
        delayedLog('Clearing error messages');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.add('d-none');
        }
    };

    if (!form || !emailInput || !passwordInput || !loginButton) {
        console.error('Required form elements not found');
        return;
    }

    loginButton.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        delayedLog('=== LOGIN ATTEMPT STARTED ===');

        // Clear any previous errors
        clearError();

        // Disable button and show spinner
        loginButton.disabled = true;
        const btnText = loginButton.querySelector('.btn-text');
        const btnLoader = loginButton.querySelector('.btn-loader');
        if (btnText) btnText.classList.add('invisible');
        if (btnLoader) btnLoader.classList.remove('d-none');

        try {
            // Get form data
            const email = emailInput.value.trim();
            const password = passwordInput.value;

            delayedLog('Form data:', {
                email: email ? email : 'Missing',
                password: password ? '[PRESENT]' : 'Missing',
                csrfToken: csrfToken ? csrfToken : 'Missing'
            });

            // Validate form data
            if (!email) {
                throw new Error('Veuillez entrer votre email');
            }
            if (!password) {
                throw new Error('Veuillez entrer votre mot de passe');
            }
            if (!csrfToken) {
                throw new Error('Token CSRF manquant');
            }

            // Get base path from window.appConfig or default
            const basePath = window.appConfig?.basePath || '/Plateformeval';
            const loginUrl = `${basePath}/public/login`;
            delayedLog('Login URL:', loginUrl);

            const formData = {
                email,
                password,
                csrf_token: csrfToken
            };

            delayedLog('Request payload:', formData);

            try {
                delayedLog('Sending fetch request...');
                const response = await fetch(loginUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData),
                    credentials: 'same-origin'
                });

                delayedLog('Response received:', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries([...response.headers])
                });

                const contentType = response.headers.get('Content-Type');
                delayedLog('Response content type:', contentType);

                const rawResponse = await response.text();
                delayedLog('Raw response:', rawResponse);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${rawResponse}`);
                }

                let data;
                try {
                    data = JSON.parse(rawResponse);
                    delayedLog('Parsed response data:', data);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    delayedLog('Failed to parse response:', rawResponse);
                    throw new Error('Réponse invalide du serveur');
                }

                if (data.success) {
                    delayedLog('Login successful:', data);
                    
                    // Update CSRF token if provided
                    if (data.data?.csrf_token) {
                        const newToken = data.data.csrf_token;
                        if (metaToken) {
                            metaToken.setAttribute('content', newToken);
                        }
                        csrfToken = newToken;
                        delayedLog('Updated CSRF token:', newToken);
                    }

                    // Handle redirect
                    const redirectUrl = data.data?.redirect;
                    if (redirectUrl) {
                        delayedLog('Redirecting to:', redirectUrl);
                        // Add a delay before redirect to see the logs
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        window.location.href = redirectUrl;
                    } else {
                        console.error('No redirect URL provided in response');
                        throw new Error('Erreur de redirection après connexion');
                    }
                } else {
                    delayedLog('Login failed:', data);
                    throw new Error(data.message || 'Erreur de connexion');
                }
            } catch (fetchError) {
                console.error('Fetch error:', fetchError);
                throw new Error(`Erreur de communication avec le serveur: ${fetchError.message}`);
            }
        } catch (error) {
            delayedLog('=== LOGIN ERROR ===', error);
            showError(error.message || 'Erreur lors de la connexion');
        } finally {
            // Re-enable button and hide spinner after a short delay
            setTimeout(() => {
                loginButton.disabled = false;
                if (btnText) btnText.classList.remove('invisible');
                if (btnLoader) btnLoader.classList.add('d-none');
            }, 500);
        }
    });
});
