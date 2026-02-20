
// Toggle chat box visibility
function toggleChat() {
  const chatBox = document.getElementById('wa-chat-box');
  if (chatBox.style.display === 'block') {
    chatBox.style.display = 'none';
  } else {
    chatBox.style.display = 'block';
  }
}

// Initialize service group cascading dropdowns
document.addEventListener('DOMContentLoaded', function () {
  const serviceGroupSelect = document.getElementById('wa-service-group');
  const serviceSelect = document.getElementById('wa-plugin');
  const serviceTree = waGreeting.service_tree || [];

  // Populate service group dropdown (parent terms)
  serviceTree.forEach(function (group) {
    const option = document.createElement('option');
    option.value = group.name;
    option.textContent = group.name;
    serviceGroupSelect.appendChild(option);
  });

  // When service group changes, populate and show service dropdown (child terms)
  var serviceWrapper = document.getElementById('wa-service-wrapper');

  serviceGroupSelect.addEventListener('change', function () {
    var selectedGroup = this.value;
    serviceSelect.innerHTML = '<option value="" selected disabled>Choose Service</option>';
    serviceWrapper.style.display = 'none';

    var group = serviceTree.find(function (g) {
      return g.name === selectedGroup;
    });

    if (group && group.children.length > 0) {
      group.children.forEach(function (child) {
        var option = document.createElement('option');
        option.value = child;
        option.textContent = child;
        serviceSelect.appendChild(option);
      });
      serviceWrapper.style.display = 'block';
    } else {
      serviceWrapper.style.display = 'none';
    }
  });
});

// Clear all error messages
function clearErrors() {
  const errors = document.getElementsByClassName('wa-error');
  for (let i = 0; i < errors.length; i++) {
    errors[i].textContent = '';
  }
}

// Validate form inputs
function validateForm() {
  let isValid = true;

  // Name validation
  const name = document.getElementById('wa-name').value;
  if (!name) {
    document.getElementById('error-name').textContent = 'Name is required';
    isValid = false;
  }

  // Email validation
  const email = document.getElementById('wa-email').value;
  if (!email) {
    document.getElementById('error-email').textContent = 'Email is required';
    isValid = false;
  } else if (!isValidEmail(email)) {
    document.getElementById('error-email').textContent = 'Please enter a valid email';
    isValid = false;
  } else if (isBlockedEmailDomain(email)) {
    const domain = email.split('@')[1].toLowerCase();
    document.getElementById('error-email').textContent = 'Email domain @' + domain + ' is not allowed. Please use a business email.';
    isValid = false;
  }

  // Company validation
  const company = document.getElementById('wa-company').value;
  if (!company) {
    document.getElementById('error-company').textContent = 'Company is required';
    isValid = false;
  }

  // Service Group validation
  const serviceGroup = document.getElementById('wa-service-group').value;
  if (!serviceGroup) {
    document.getElementById('error-service-group').textContent = 'Service group is required';
    isValid = false;
  }

  // Service validation (only if visible)
  var serviceWrapper = document.getElementById('wa-service-wrapper');
  if (serviceWrapper.style.display !== 'none') {
    const plugin = document.getElementById('wa-plugin').value;
    if (!plugin) {
      document.getElementById('error-service').textContent = 'Service selection is required';
      isValid = false;
    }
  }

  // WhatsApp number validation
  const number = document.getElementById('wa-number').value;
  if (!number) {
    document.getElementById('error-number').textContent = 'WhatsApp number is required';
    isValid = false;
  }

  // Message validation - minimum 5 words
  const message = document.getElementById('wa-message').value;
  const wordCount = message.trim().split(/\s+/).filter(word => word.length > 0).length;
  if (!message) {
    document.getElementById('error-message').textContent = 'Message is required';
    isValid = false;
  } else if (wordCount < 5) {
    document.getElementById('error-message').textContent = 'Message must be at least 5 words';
    isValid = false;
  }

  // Privacy policy acceptance validation
  const privacy = document.getElementById('wa-privacy').checked;
  if (!privacy) {
    document.getElementById('error-privacy').textContent = 'You must accept our privacy policy';
    isValid = false;
  }

  return isValid;
}

// Email validation helper
function isValidEmail(email) {
  const re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
  return re.test(email);
}

// Check if email domain is blocked
function isBlockedEmailDomain(email) {
  if (!waGreeting.blocked_domains || waGreeting.blocked_domains.length === 0) {
    return false;
  }
  const domain = email.split('@')[1].toLowerCase();
  return waGreeting.blocked_domains.includes(domain);
}

// Send WhatsApp message and save data
function sendWhatsapp() {
  clearErrors();

  // Validate the form
  if (!validateForm()) {
    return;
  }

  // Get form data
  const name = document.getElementById('wa-name').value;
  const email = document.getElementById('wa-email').value;
  const company = document.getElementById('wa-company').value;
  const serviceGroup = document.getElementById('wa-service-group').value;
  const serviceWrapperVisible = document.getElementById('wa-service-wrapper').style.display !== 'none';
  const plugin = serviceWrapperVisible ? document.getElementById('wa-plugin').value : serviceGroup;
  const number = document.getElementById('wa-number').value;
  const message = document.getElementById('wa-message').value;

  // Set button to loading state
  const submitButton = document.querySelector('#wa-chat-box button');
  const originalButtonText = submitButton.textContent.trim();
  submitButton.classList.add('loading');
  submitButton.disabled = true;

  // Save form data to WordPress
  const formData = new FormData();
  formData.append('action', 'wa_greeting_save');
  formData.append('name', name);
  formData.append('email', email);
  formData.append('company', company);
  formData.append('service_group', serviceGroup);
  formData.append('plugin', plugin);
  formData.append('number', number);
  formData.append('message', message);
  formData.append('url', window.location.href);

  fetch(waGreeting.ajax_url, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    // Reset loading state
    submitButton.classList.remove('loading');
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;

    if (data.success) {
      // Open WhatsApp with pre-filled message
      const serviceLine = serviceWrapperVisible ? `${serviceGroup} - ${plugin}` : serviceGroup;
      const waMessage = `Hello! My name is ${name} from ${company}. I'm interested in ${serviceLine} service. ${message}`;
      const waUrl = `https://wa.me/${waGreeting.admin_wa}?text=${encodeURIComponent(waMessage)}`;
      window.open(waUrl, '_blank');

      // Reset form
      document.getElementById('wa-name').value = '';
      document.getElementById('wa-email').value = '';
      document.getElementById('wa-company').value = '';
      document.getElementById('wa-service-group').selectedIndex = 0;
      document.getElementById('wa-plugin').innerHTML = '<option value="" selected disabled>Choose Service</option>';
      document.getElementById('wa-service-wrapper').style.display = 'none';
      document.getElementById('wa-number').value = '';
      document.getElementById('wa-message').value = '';
      document.getElementById('wa-privacy').checked = false;

      // Hide chat box after 1 second
      setTimeout(() => {
        toggleChat();
      }, 1000);
    } else {
      console.error('Error saving form data', data);
      const errorMsg = data.data && data.data.message
        ? data.data.message
        : 'There was an error submitting your form. Please try again.';
      alert(errorMsg);
    }
  })
  .catch(error => {
    // Reset loading state on error
    submitButton.classList.remove('loading');
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;

    console.error('Error:', error);
    alert('There was an error submitting your form. Please try again.');
  });
}
