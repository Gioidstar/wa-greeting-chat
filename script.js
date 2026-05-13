
// Country codes data
var waCountries = [
  {name:"Indonesia",code:"ID",dial:"62"},
  {name:"United States",code:"US",dial:"1"},
  {name:"United Kingdom",code:"GB",dial:"44"},
  {name:"Singapore",code:"SG",dial:"65"},
  {name:"Malaysia",code:"MY",dial:"60"},
  {name:"Australia",code:"AU",dial:"61"},
  {name:"Japan",code:"JP",dial:"81"},
  {name:"South Korea",code:"KR",dial:"82"},
  {name:"China",code:"CN",dial:"86"},
  {name:"India",code:"IN",dial:"91"},
  {name:"Thailand",code:"TH",dial:"66"},
  {name:"Vietnam",code:"VN",dial:"84"},
  {name:"Philippines",code:"PH",dial:"63"},
  {name:"Germany",code:"DE",dial:"49"},
  {name:"France",code:"FR",dial:"33"},
  {name:"Italy",code:"IT",dial:"39"},
  {name:"Spain",code:"ES",dial:"34"},
  {name:"Netherlands",code:"NL",dial:"31"},
  {name:"Belgium",code:"BE",dial:"32"},
  {name:"Switzerland",code:"CH",dial:"41"},
  {name:"Austria",code:"AT",dial:"43"},
  {name:"Sweden",code:"SE",dial:"46"},
  {name:"Norway",code:"NO",dial:"47"},
  {name:"Denmark",code:"DK",dial:"45"},
  {name:"Finland",code:"FI",dial:"358"},
  {name:"Poland",code:"PL",dial:"48"},
  {name:"Portugal",code:"PT",dial:"351"},
  {name:"Ireland",code:"IE",dial:"353"},
  {name:"Russia",code:"RU",dial:"7"},
  {name:"Turkey",code:"TR",dial:"90"},
  {name:"Saudi Arabia",code:"SA",dial:"966"},
  {name:"UAE",code:"AE",dial:"971"},
  {name:"Qatar",code:"QA",dial:"974"},
  {name:"Kuwait",code:"KW",dial:"965"},
  {name:"Bahrain",code:"BH",dial:"973"},
  {name:"Oman",code:"OM",dial:"968"},
  {name:"Egypt",code:"EG",dial:"20"},
  {name:"South Africa",code:"ZA",dial:"27"},
  {name:"Nigeria",code:"NG",dial:"234"},
  {name:"Kenya",code:"KE",dial:"254"},
  {name:"Ghana",code:"GH",dial:"233"},
  {name:"Morocco",code:"MA",dial:"212"},
  {name:"Brazil",code:"BR",dial:"55"},
  {name:"Mexico",code:"MX",dial:"52"},
  {name:"Argentina",code:"AR",dial:"54"},
  {name:"Colombia",code:"CO",dial:"57"},
  {name:"Chile",code:"CL",dial:"56"},
  {name:"Peru",code:"PE",dial:"51"},
  {name:"Canada",code:"CA",dial:"1"},
  {name:"New Zealand",code:"NZ",dial:"64"},
  {name:"Pakistan",code:"PK",dial:"92"},
  {name:"Bangladesh",code:"BD",dial:"880"},
  {name:"Sri Lanka",code:"LK",dial:"94"},
  {name:"Nepal",code:"NP",dial:"977"},
  {name:"Myanmar",code:"MM",dial:"95"},
  {name:"Cambodia",code:"KH",dial:"855"},
  {name:"Laos",code:"LA",dial:"856"},
  {name:"Brunei",code:"BN",dial:"673"},
  {name:"Timor-Leste",code:"TL",dial:"670"},
  {name:"Hong Kong",code:"HK",dial:"852"},
  {name:"Taiwan",code:"TW",dial:"886"},
  {name:"Macao",code:"MO",dial:"853"},
  {name:"Israel",code:"IL",dial:"972"},
  {name:"Jordan",code:"JO",dial:"962"},
  {name:"Lebanon",code:"LB",dial:"961"},
  {name:"Iraq",code:"IQ",dial:"964"},
  {name:"Iran",code:"IR",dial:"98"},
  {name:"Afghanistan",code:"AF",dial:"93"},
  {name:"Greece",code:"GR",dial:"30"},
  {name:"Czech Republic",code:"CZ",dial:"420"},
  {name:"Romania",code:"RO",dial:"40"},
  {name:"Hungary",code:"HU",dial:"36"},
  {name:"Ukraine",code:"UA",dial:"380"},
  {name:"Croatia",code:"HR",dial:"385"},
  {name:"Serbia",code:"RS",dial:"381"},
  {name:"Bulgaria",code:"BG",dial:"359"},
  {name:"Slovakia",code:"SK",dial:"421"},
  {name:"Slovenia",code:"SI",dial:"386"},
  {name:"Lithuania",code:"LT",dial:"370"},
  {name:"Latvia",code:"LV",dial:"371"},
  {name:"Estonia",code:"EE",dial:"372"},
  {name:"Iceland",code:"IS",dial:"354"},
  {name:"Luxembourg",code:"LU",dial:"352"},
  {name:"Malta",code:"MT",dial:"356"},
  {name:"Cyprus",code:"CY",dial:"357"},
  {name:"Tunisia",code:"TN",dial:"216"},
  {name:"Algeria",code:"DZ",dial:"213"},
  {name:"Libya",code:"LY",dial:"218"},
  {name:"Ethiopia",code:"ET",dial:"251"},
  {name:"Tanzania",code:"TZ",dial:"255"},
  {name:"Uganda",code:"UG",dial:"256"},
  {name:"Mozambique",code:"MZ",dial:"258"},
  {name:"Zimbabwe",code:"ZW",dial:"263"},
  {name:"Botswana",code:"BW",dial:"267"},
  {name:"Namibia",code:"NA",dial:"264"},
  {name:"Senegal",code:"SN",dial:"221"},
  {name:"Ivory Coast",code:"CI",dial:"225"},
  {name:"Cameroon",code:"CM",dial:"237"},
  {name:"Venezuela",code:"VE",dial:"58"},
  {name:"Ecuador",code:"EC",dial:"593"},
  {name:"Bolivia",code:"BO",dial:"591"},
  {name:"Paraguay",code:"PY",dial:"595"},
  {name:"Uruguay",code:"UY",dial:"598"},
  {name:"Costa Rica",code:"CR",dial:"506"},
  {name:"Panama",code:"PA",dial:"507"},
  {name:"Guatemala",code:"GT",dial:"502"},
  {name:"Honduras",code:"HN",dial:"504"},
  {name:"El Salvador",code:"SV",dial:"503"},
  {name:"Dominican Republic",code:"DO",dial:"1"},
  {name:"Cuba",code:"CU",dial:"53"},
  {name:"Jamaica",code:"JM",dial:"1"},
  {name:"Trinidad and Tobago",code:"TT",dial:"1"},
  {name:"Mongolia",code:"MN",dial:"976"},
  {name:"Uzbekistan",code:"UZ",dial:"998"},
  {name:"Kazakhstan",code:"KZ",dial:"7"},
  {name:"Georgia",code:"GE",dial:"995"},
  {name:"Armenia",code:"AM",dial:"374"},
  {name:"Azerbaijan",code:"AZ",dial:"994"},
  {name:"Fiji",code:"FJ",dial:"679"},
  {name:"Papua New Guinea",code:"PG",dial:"675"},
];

// Convert country code to flag emoji
function countryFlag(code) {
  return String.fromCodePoint(...[...code.toUpperCase()].map(c => 0x1F1E6 - 65 + c.charCodeAt(0)));
}

// Toggle chat box visibility
function toggleChat() {
  const chatBox = document.getElementById('wa-chat-box');
  if (chatBox.style.display === 'block') {
    chatBox.style.display = 'none';
  } else {
    chatBox.style.display = 'block';
  }
}

// Initialize country code dropdown and service group cascading dropdowns
document.addEventListener('DOMContentLoaded', function () {
  // Country code dropdown
  var countryList = document.getElementById('wa-country-list');
  var countryDropdown = document.getElementById('wa-country-dropdown');
  var countrySelected = document.getElementById('wa-country-selected');
  var countryFlag_el = document.getElementById('wa-country-flag');
  var countryCodeInput = document.getElementById('wa-country-code');
  var countrySearch = document.getElementById('wa-country-search');

  function renderCountryList(filter) {
    countryList.innerHTML = '';
    var q = (filter || '').toLowerCase();
    waCountries.forEach(function(c) {
      if (q && c.name.toLowerCase().indexOf(q) === -1 && c.dial.indexOf(q) === -1) return;
      var li = document.createElement('li');
      li.innerHTML = '<span class="cc-flag">' + countryFlag(c.code) + '</span>' +
        '<span class="cc-name">' + c.name + '</span>' +
        '<span class="cc-dial">+' + c.dial + '</span>';
      li.addEventListener('click', function() {
        countryFlag_el.textContent = countryFlag(c.code);
        countryCodeInput.value = c.dial;
        countryDropdown.classList.remove('open');
        countrySearch.value = '';
      });
      countryList.appendChild(li);
    });
  }

  renderCountryList('');

  countrySelected.addEventListener('click', function(e) {
    e.stopPropagation();
    var isOpen = countryDropdown.classList.contains('open');
    countryDropdown.classList.toggle('open');
    if (!isOpen) {
      countrySearch.value = '';
      renderCountryList('');
      setTimeout(function() { countrySearch.focus(); }, 50);
    }
  });

  countrySearch.addEventListener('input', function() {
    renderCountryList(this.value);
  });

  countrySearch.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  document.addEventListener('click', function() {
    countryDropdown.classList.remove('open');
  });

  // Service group cascading dropdowns
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
  const name = document.getElementById('wa-name').value.trim();
  if (!name) {
    document.getElementById('error-name').textContent = 'Name is required';
    isValid = false;
  } else if (name === '-') {
    document.getElementById('error-name').textContent = 'Please enter a valid name';
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
  }

  // Company validation
  const company = document.getElementById('wa-company').value.trim();
  if (!company) {
    document.getElementById('error-company').textContent = 'Company is required';
    isValid = false;
  } else if (company === '-') {
    document.getElementById('error-company').textContent = 'Please enter a valid company name';
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
  const countryCode = document.getElementById('wa-country-code').value;
  let rawNumber = document.getElementById('wa-number').value.trim();
  
  // Rule: Hapus semua angka 0 di depan nomor HP (contoh: 0812 -> 812)
  rawNumber = rawNumber.replace(/^0+/, '');
  
  const number = countryCode + rawNumber;
  const message = document.getElementById('wa-message').value;

  // Set button to loading state
  const submitButton = document.querySelector('#wa-chat-box button');
  const originalButtonText = submitButton.textContent.trim();
  submitButton.classList.add('loading');
  submitButton.disabled = true;

  // Fetch fresh nonce (page cache may serve stale nonce), then submit
  const nonceForm = new FormData();
  nonceForm.append('action', 'wa_greeting_nonce');

  fetch(waGreeting.ajax_url, {
    method: 'POST',
    body: nonceForm,
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(nonceData => {
    const freshNonce = nonceData.data.nonce;

    // Save form data to WordPress
    const formData = new FormData();
    formData.append('action', 'wa_greeting_save');
    formData.append('nonce', freshNonce);
    formData.append('name', name);
    formData.append('email', email);
    formData.append('company', company);
    formData.append('service_group', serviceGroup);
    formData.append('plugin', plugin);
    formData.append('number', number);
    formData.append('message', message);
    formData.append('url', window.location.href);

    return fetch(waGreeting.ajax_url, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
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
      const waUrl = `https://wa.me/${data.data.admin_wa}?text=${encodeURIComponent(waMessage)}`;
      window.open(waUrl, '_blank');

      // Reset form
      document.getElementById('wa-name').value = '';
      document.getElementById('wa-email').value = '';
      document.getElementById('wa-company').value = '';
      document.getElementById('wa-service-group').selectedIndex = 0;
      document.getElementById('wa-plugin').innerHTML = '<option value="" selected disabled>Choose Service</option>';
      document.getElementById('wa-service-wrapper').style.display = 'none';
      document.getElementById('wa-number').value = '';
      document.getElementById('wa-country-code').value = '62';
      document.getElementById('wa-country-flag').textContent = countryFlag('ID');
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
