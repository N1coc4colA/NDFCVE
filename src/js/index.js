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
    const cveInputs = Array.from(cveInputsContainer.querySelectorAll('.cve-input'))
        .map(input => input.value.trim())
        .filter(val => cvePattern.test(val));
    if (cveInputs.length === 0) return;
    // Redirige vers results.php avec les CVE en paramètre GET
    window.location.href = `results.php?cveIds=${encodeURIComponent(cveInputs.join(','))}`;
});
