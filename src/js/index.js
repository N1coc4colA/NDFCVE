const cveInputsContainer = document.getElementById('cveInputsContainer');
const cveFileInput = document.getElementById('cveFileInput');
const cveForm = document.getElementById('cveForm');
const clearCVEsBtn = document.getElementById('clearCVEsBtn');
const cvePattern = /^CVE-\d{4}-\d{4,}$/;

// KEV checker configuration
// CIRCL CVE API base (we'll append the CVE ID)
// const CIRCL_BASE = 'https://cve.circl.lu/api/cve/';
const CIRCL_BASE = 'api/circl_proxy.php?cve=';
const KEV_DEBOUNCE_MS = 450; // debounce typing to avoid many requests

// simple debounce helper
function debounce(fn, wait) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

// Check CIRCL CVE API for a single input element. Adds/removes .kev-not-present class based on response.
async function checkKevForInput(inputElem) {
    if (!inputElem) return;
    const val = String(inputElem.value || '').trim().toUpperCase();
    // clear class for empty or invalid values
    if (!cvePattern.test(val)) {
        inputElem.classList.remove('kev-not-present');
        inputElem.removeAttribute('data-kev-checked');
        return;
    }

    // mark as checking to avoid duplicate markers
    inputElem.setAttribute('data-kev-checked', 'checking');

    try {
        const resp = await fetch(CIRCL_BASE + encodeURIComponent(val), { cache: 'no-store' });
        // If CIRCL responds 404, treat as not found (mark red)
        if (resp.status === 404) {
            inputElem.classList.add('kev-not-present');
            inputElem.setAttribute('data-kev-checked', 'false');
            return;
        }

        if (!resp.ok) {
            // other HTTP error: don't mark as not-present; mark error
            inputElem.classList.remove('kev-not-present');
            inputElem.setAttribute('data-kev-checked', 'error');
            return;
        }

        const json = await resp.json();
        // CIRCL returns an empty object {} for non-existent CVE IDs.
        if (json && typeof json === 'object' && Object.keys(json).length === 0) {
            inputElem.classList.add('kev-not-present');
            inputElem.setAttribute('data-kev-checked', 'false');
        } else if (json && typeof json === 'object') {
            // non-empty object => CVE exists
            inputElem.classList.remove('kev-not-present');
            inputElem.setAttribute('data-kev-checked', 'true');
        } else {
            // unexpected payload: clear marker
            inputElem.classList.remove('kev-not-present');
            inputElem.setAttribute('data-kev-checked', 'unknown');
        }
    } catch (err) {
        // network or parse error: treat as unknown (don't mark as not-present)
        inputElem.classList.remove('kev-not-present');
        inputElem.setAttribute('data-kev-checked', 'error');
    }
}

// Debounced wrapper for typing-driven checks
const debouncedCheck = debounce((input) => checkKevForInput(input), KEV_DEBOUNCE_MS);

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

        // run a debounced KEV check for the input being edited
        if (e.target.classList.contains('cve-input')) {
            debouncedCheck(e.target);
        }
    }
});

// Also listen for focusout to run immediate check (user finished editing)
cveInputsContainer.addEventListener('focusout', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('cve-input')) {
        checkKevForInput(e.target);
    }
});

function addCveInput(baseValue = "", append = false) {
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

    if (append) {
        cveInputsContainer.appendChild(inputGroup);
    } else {
        cveInputsContainer.insertBefore(inputGroup, cveInputsContainer.firstChild);
    }

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
            // if a pre-filled value was provided and it's a valid CVE, check KEV immediately
            checkKevForInput(inputElem);
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

clearCVEsBtn.addEventListener('click', function() {
    cveInputsContainer.innerHTML = '';
    addCveInput('');
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

            if (valid.length === 0) {
                // show one empty input if no valid entries
                addCveInput('');
                alert('No valid CVE identifiers found in the file.');
                return;
            }

            valid.forEach(id => {
                addCveInput(id, true);
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
        groups.forEach((g) => {
            const btn = g.querySelector('.remove-btn');
            if (btn) btn.classList.toggle('d-none', groups.length === 0);
            // run KEV check for any pre-filled inputs on load
            const input = g.querySelector('.cve-input');
            if (input && input.value && cvePattern.test(input.value.trim())) {
                checkKevForInput(input);
            }
        });
    }
})();
