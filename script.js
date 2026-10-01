const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const toggleLink = document.getElementById('toggleLink');
const toggleText = document.getElementById('toggleText');
const formSubtitle = document.getElementById('formSubtitle');
const studentInputs = document.querySelectorAll('input[name="studentStatus"]');
const institutionGroup = document.getElementById('institutionGroup');
const residenceGroup = document.getElementById('residenceGroup');

function switchForm(mode) {
  if (mode === 'register') {
    loginForm.classList.add('hidden-form');
    registerForm.classList.remove('hidden-form');
    toggleText.textContent = 'Already have an account?';
    toggleLink.textContent = 'Sign in';
    formSubtitle.textContent = 'Create your account to get started.';
  } else {
    registerForm.classList.add('hidden-form');
    loginForm.classList.remove('hidden-form');
    toggleText.textContent = 'Not registered?';
    toggleLink.textContent = 'Create account';
    formSubtitle.textContent = 'Welcome back. Please log in to continue.';
  }
}

function toggleStudentFields() {
  const selectedValue = document.querySelector('input[name="studentStatus"]:checked')?.value;

  if (selectedValue === 'yes') {
    institutionGroup.classList.remove('hidden');
    residenceGroup.classList.add('hidden');
  } else {
    institutionGroup.classList.add('hidden');
    residenceGroup.classList.remove('hidden');
  }
}

loginForm.addEventListener('submit', (event) => {
  event.preventDefault();

  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  if (!email || !password) {
    alert('Please enter both email and password.');
    return;
  }

  alert(`Welcome back, ${email}! Your login was successful.`);
  loginForm.reset();
});

registerForm.addEventListener('submit', (event) => {
  event.preventDefault();

  const fullName = document.getElementById('fullName').value.trim();
  const regEmail = document.getElementById('regEmail').value.trim();
  const phoneNumber = document.getElementById('phoneNumber').value.trim();
  const studentStatus = document.querySelector('input[name="studentStatus"]:checked')?.value;
  const institution = document.getElementById('institution').value.trim();
  const residence = document.getElementById('residence').value.trim();

  if (!fullName || !regEmail || !phoneNumber) {
    alert('Please fill in your full name, email, and phone number.');
    return;
  }

  if (studentStatus === 'yes' && !institution) {
    alert('Please enter your institution.');
    return;
  }

  if (studentStatus === 'no' && !residence) {
    alert('Please enter your area of residence.');
    return;
  }

  alert(`Account created successfully for ${fullName}!`);
  registerForm.reset();
  toggleStudentFields();
  switchForm('login');
});

toggleLink.addEventListener('click', (event) => {
  event.preventDefault();
  const mode = registerForm.classList.contains('hidden-form') ? 'register' : 'login';
  switchForm(mode);
});

studentInputs.forEach((input) => {
  input.addEventListener('change', toggleStudentFields);
});

toggleStudentFields();
