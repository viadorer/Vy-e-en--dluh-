document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to elements as they come into view
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all sections
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });

    // Form handling
    const propertyForm = document.getElementById('propertyForm');
    if (propertyForm) {
        propertyForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate phone number
            const phoneInput = document.getElementById('phone');
            const phoneRegex = /^(\+420)?[\s-]?\d{3}[\s-]?\d{3}[\s-]?\d{3}$/;
            
            if (!phoneRegex.test(phoneInput.value)) {
                showNotification('Prosím zadejte platné telefonní číslo ve formátu: +420 XXX XXX XXX nebo XXX XXX XXX', 'error');
                return false;
            }

            // Show loading state
            const submitButton = propertyForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Odesílám...';
            submitButton.disabled = true;
            
            try {
                // Get form data
                const formData = {
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    phone: phoneInput.value,
                    location: document.getElementById('location').value,
                    propertyType: document.getElementById('propertyType').value,
                    message: document.getElementById('message').value
                };

                // Send data to the server
                const response = await fetch('send-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Děkujeme za registraci! Budeme vás kontaktovat co nejdříve.', 'success');
                    propertyForm.reset();
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                showNotification(error.message || 'Došlo k chybě při odesílání formuláře. Prosím zkuste to znovu.', 'error');
            } finally {
                // Restore button state
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Notification system
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type} fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-md z-50 transform transition-all duration-300 translate-y-0`;
    
    // Set background color based on type
    if (type === 'success') {
        notification.classList.add('bg-green-500', 'text-white');
    } else if (type === 'error') {
        notification.classList.add('bg-red-500', 'text-white');
    }

    // Add content
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                ${type === 'success' 
                    ? '<i class="fas fa-check-circle text-xl"></i>'
                    : '<i class="fas fa-exclamation-circle text-xl"></i>'}
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button class="inline-flex text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;

    // Add close button functionality
    const closeButton = notification.querySelector('button');
    closeButton.addEventListener('click', () => notification.remove());

    // Add to document
    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
