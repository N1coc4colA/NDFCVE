const cveInputsContainer = document.getElementById('cveInputsContainer');
const cveFileInput = document.getElementById('cveFileInput');
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
            addCveInput("");
        }
    }
});

function addCveInput(baseValue = "", baseIndex = 0) {
    const inputGroup = document.createElement('div');
    inputGroup.className = 'cve-input-group d-flex align-items-center gap-2';
    inputGroup.innerHTML = `
          <input 
            type="text" 
            class="form-control form-control-lg cve-input" 
            placeholder="CVE-YYYY-NNNN" 
            pattern="CVE-\\d{4}-\\d{4,}"
            value="${baseValue}"
          >
        `;
    cveInputsContainer.insertBefore(inputGroup, cveInputsContainer.childNodes[baseIndex]);
    const inputElem = inputGroup.querySelector('.cve-input');
    inputElem.focus();

    if (baseValue !== "") {
        if (cvePattern.test(baseValue)) {
            if (!inputGroup.querySelector('.remove-btn')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-btn';
                removeBtn.onclick = function() { removeInput(this); };
                removeBtn.innerHTML = '<i class="bi bi-x-circle" style="font-size: 1.5rem;"></i>';
                inputGroup.appendChild(removeBtn);
            }
        }
    }
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

// Load file and populate inputs
if (cveFileInput) {
    cveFileInput.addEventListener('change', function(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            const text = ev.target.result || '';
            const lines = text.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            const valid = lines.map(l => l.toUpperCase()).filter(l => cvePattern.test(l));

            // Clear current inputs
            //cveInputsContainer.innerHTML = '';

            if (valid.length === 0) {
                // show one empty input if no valid entries
                addCveInput('');
                alert('No valid CVE identifiers found in the file.');
                return;
            }

            valid.forEach(id => {
                addCveInput(id, 1);
            });
        };
        reader.readAsText(file);
        // reset input so the same file can be reselected later
        cveFileInput.value = '';
    });
}

// Ensure at least one input exists on load
(function ensureInitial() {
    if (!cveInputsContainer.querySelector('.cve-input-group')) {
        addCveInput('');
    } else {
        // ensure existing group's remove buttons visibility
        const groups = cveInputsContainer.querySelectorAll('.cve-input-group');
        groups.forEach((g, idx) => {
            const btn = g.querySelector('.remove-btn');
            if (btn) btn.classList.toggle('d-none', groups.length === 0);
        });
    }
})();
