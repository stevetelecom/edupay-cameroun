// ── Validation temps réel des formulaires EduPay ──
document.addEventListener('DOMContentLoaded', function() {

  // 1. Validation live sur les champs requis / email / téléphone
  document.querySelectorAll('form').forEach(function(form) {
    var inputs = form.querySelectorAll('.inp[required], .inp[type="email"], .select[required]');

    inputs.forEach(function(input) {
      input.addEventListener('blur', function() {
        validateField(input);
      });
      input.addEventListener('input', function() {
        // Si déjà marqué invalide, revalider en direct pour feedback immédiat
        if (input.classList.contains('is-invalid')) {
          validateField(input);
        }
      });
    });

    // 2. Confirmation mot de passe — comparaison live
    var pwd  = form.querySelector('input[name="password"]');
    var pwd2 = form.querySelector('input[name="password_confirmation"]');
    if (pwd && pwd2) {
      var checkMatch = function() {
        if (pwd2.value.length === 0) {
          pwd2.classList.remove('is-valid', 'is-invalid');
          return;
        }
        if (pwd.value === pwd2.value) {
          pwd2.classList.remove('is-invalid');
          pwd2.classList.add('is-valid');
        } else {
          pwd2.classList.remove('is-valid');
          pwd2.classList.add('is-invalid');
        }
      };
      pwd.addEventListener('input', checkMatch);
      pwd2.addEventListener('input', checkMatch);
    }

    // 3. État "loading" sur le bouton submit au moment de l'envoi
    form.addEventListener('submit', function() {
      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && form.checkValidity()) {
        submitBtn.classList.add('is-loading');
      }
    });
  });

  function validateField(input) {
    var value = input.value.trim();
    var isEmpty = value.length === 0;

    if (input.hasAttribute('required') && isEmpty) {
      markInvalid(input);
      return;
    }

    if (input.type === 'email' && value && !isValidEmail(value)) {
      markInvalid(input);
      return;
    }

    if (input.name === 'telephone' && value && !isValidPhone(value)) {
      markInvalid(input);
      return;
    }

    if (!isEmpty) {
      markValid(input);
    } else {
      input.classList.remove('is-valid', 'is-invalid');
    }
  }

  function markValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    removeErrorMsg(input);
  }

  function markInvalid(input) {
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');
  }

  function removeErrorMsg(input) {
    var next = input.parentElement.querySelector('.field-error-msg');
    if (next) next.classList.remove('show');
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function isValidPhone(value) {
    // Format camerounais : 6XXXXXXXX ou +2376XXXXXXXX
    var cleaned = value.replace(/\s/g, '');
    return /^(\+237)?[62-9]\d{8}$/.test(cleaned);
  }
});
