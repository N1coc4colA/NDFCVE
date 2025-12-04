<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>CVE Analyzer - Home</title>
    <link rel="icon" href="assets/icons/favicon-64.svg" type="image/svg+xml">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/style.css">
  </head>
  <body>
    <header class="header">
      <div class="container text-center">
        <h1>CVE Analyzer</h1>
      </div>
    </header>

    <main class="main-content">
      <div class="cve-form-container">
        <h2>Enter CVE Identifiers</h2>
        <form id="cveForm">
          <div id="cveInputsContainer">
            <div class="cve-input-group d-flex align-items-center gap-2">
              <input 
                type="text" 
                class="form-control cve-input" 
                placeholder="CVE-YYYY-NNNN" 
                pattern="CVE-\d{4}-\d{4,}"
                required
              >
            </div>
          </div>
          <button type="submit" class="btn btn-search mt-3">
            Search
          </button>
        </form>
      </div>
    </main>

    <script>
      const cveInputsContainer = document.getElementById('cveInputsContainer');
      const cveForm = document.getElementById('cveForm');
      const cvePattern = /^CVE-\d{4}-\d{4,}$/;

      cveInputsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('cve-input')) {
          const allInputGroups = cveInputsContainer.querySelectorAll('.cve-input-group');
          const firstInputGroup = allInputGroups[0];
          const firstInput = firstInputGroup.querySelector('.cve-input');
          if (e.target === firstInput && cvePattern.test(firstInput.value.trim())) {
            if (!firstInputGroup.querySelector('.remove-btn')) {
              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.className = 'remove-btn';
              removeBtn.onclick = function() { removeInput(this); };
              removeBtn.innerHTML = '<i class="bi bi-x-circle" style="font-size: 1.5rem;"></i>';
              firstInputGroup.appendChild(removeBtn);
            }
            addCveInput();
          }
        }
      });

      function addCveInput() {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'cve-input-group d-flex align-items-center gap-2';
        inputGroup.innerHTML = `
          <input 
            type="text" 
            class="form-control cve-input" 
            placeholder="CVE-YYYY-NNNN" 
            pattern="CVE-\\d{4}-\\d{4,}"
          >
        `;
        cveInputsContainer.insertBefore(inputGroup, cveInputsContainer.firstChild);
        inputGroup.querySelector('.cve-input').focus();
      }

      function removeInput(button) {
        const inputGroups = cveInputsContainer.querySelectorAll('.cve-input-group');
        if (inputGroups.length > 1) {
          button.closest('.cve-input-group').remove();
        }
      }

      cveForm.addEventListener('submit', function(e) {
        e.preventDefault();
      });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>
