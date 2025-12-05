
const vulnStore = {};

async function fetchGitHubPocs(cveId) {
    try {
        const query = encodeURIComponent(`${cveId} poc OR exploit OR vulnerability`);
        const resp = await fetch(`https://api.github.com/search/repositories?q=${query}&sort=stars&order=desc&per_page=10`);
        if (!resp.ok) return [];
        const data = await resp.json();
        return data.items || [];
    } catch {
        return [];
    }
}

function buildPocButton(cveId) {
    return `
        <button type="button" class="btn btn-sm btn-outline-dark ms-2 poc-btn" data-cve="${cveId}">
          <i class="bi bi-github"></i> PoCs
        </button>
      `;
}

function buildCWESection(weaknesses) {
    if (!weaknesses || weaknesses.length === 0) return '<p class="text-muted small">Aucune CWE disponible</p>';
    const cweList = weaknesses.flatMap(w => w.description || [])
        .filter(d => {
            const value = d.value || '';
            return !value.startsWith('NVD-CWE-') && value.match(/^CWE-\d+$/);
        })
        .map(d => {
            const cweId = d.value.match(/CWE-(\d+)/)?.[1];
            return `<a href="https://cwe.mitre.org/data/definitions/${cweId}.html" target="_blank" class="badge bg-light text-dark border me-1 text-decoration-none cwe-badge">${d.value}</a>`;
        })
        .join('');

    if (!cweList) return '<p class="text-muted small">Aucune CWE disponible</p>';
    return `<div class="cwe-section mt-2">${cweList}</div>`;
}

function buildAffectedTech(configurations) {
    if (!configurations || configurations.length === 0) return '<p class="text-muted small">No affected technology available</p>';
    const cpes = [];
    configurations.forEach(config => {
        config.nodes?.forEach(node => {
            node.cpeMatch?.forEach(match => {
                if (match.vulnerable && match.criteria) {
                    const parts = match.criteria.split(':');
                    if (parts.length >= 5) {
                        cpes.push(`${parts[3]}:${parts[4]} ${parts[5] !== '*' ? parts[5] : ''}`);
                    }
                }
            });
        });
    });
    const uniqueCpes = [...new Set(cpes)].slice(0, 10);
    return `
      <h6 class="mb-2"><i class="bi bi-cpu"></i> Affected Technologies</h6>
        <div class="affected-tech mt-2">
          <div class="d-flex flex-wrap gap-1">
            ${uniqueCpes.map(cpe => `<span class="badge bg-secondary">${cpe}</span>`).join('')}
          </div>
        </div>
      `;
}

async function fetchCve(cveId) {
    const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=${encodeURIComponent(cveId)}`;
    const resp = await fetch(url);
    if (!resp.ok) return null;
    const data = await resp.json();
    return data.vulnerabilities && data.vulnerabilities.length > 0 ? data.vulnerabilities[0] : null;
}

async function fetchKevDetails(cveId) {
    try {
        const resp = await fetch(`api/kev_details_proxy?cve=${encodeURIComponent(cveId)}`);
        if (!resp.ok) return null;
        return await resp.json();
    } catch {
        return null;
    }
}

function getSeverityBadge(severity) {
    const colors = {
        'CRITICAL': 'danger',
        'HIGH': 'warning',
        'MEDIUM': 'info',
        'LOW': 'secondary'
    };
    return `<span class="badge bg-${colors[severity] || 'secondary'}">${severity || 'N/A'}</span>`;
}

function buildKEVAlert(kevData) {
    if (!kevData) return '';
    return `
        <div class="alert alert-danger mt-3" role="alert">
          <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
            <div class="flex-grow-1">
              <strong>Active exploit (KEV)</strong>
              <div class="mt-2 small">
                ${kevData.dateAdded ? `<p class="mb-1"><i class="bi bi-calendar-plus"></i> <strong>Added:</strong> ${new Date(kevData.dateAdded).toLocaleDateString('en-US')}</p>` : ''}
                ${kevData.dueDate ? `<p class="mb-1"><i class="bi bi-calendar-check"></i> <strong>Due date:</strong> ${new Date(kevData.dueDate).toLocaleDateString('en-US')}</p>` : ''}
                ${kevData.vulnerabilityName ? `<p class="mb-1"><i class="bi bi-tag"></i> <strong>Name:</strong> ${kevData.vulnerabilityName}</p>` : ''}
                ${kevData.requiredAction ? `<p class="mb-1"><i class="bi bi-shield-exclamation"></i> <strong>Required action:</strong> ${kevData.requiredAction}</p>` : ''}
                ${kevData.knownRansomwareCampaignUse ? `<p class="mb-1 text-danger"><i class="bi bi-virus"></i> <strong>Ransomware:</strong> ${kevData.knownRansomwareCampaignUse}</p>` : ''}
                ${kevData.notes ? `<p class="mb-0"><i class="bi bi-info-circle"></i> <strong>Notes:</strong> ${kevData.notes}</p>` : ''}
              </div>
            </div>
          </div>
        </div>
      `;
}

function renderPocResults(pocs, cveId) {
    if (pocs.length === 0) {
        return `<div class="alert alert-info"><i class="bi bi-info-circle"></i> No PoC found on GitHub for ${cveId}</div>`;
    }
    return `
        <p class="text-muted mb-3">${pocs.length} result(s) found for <strong>${cveId}</strong></p>
        <div class="list-group">
          ${pocs.map(repo => `
            <a href="${repo.html_url}" target="_blank" class="list-group-item list-group-item-action">
              <div class="d-flex w-100 justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1"><i class="bi bi-folder"></i> ${repo.full_name}</h6>
                  <p class="mb-1 small text-muted">${repo.description || 'No description'}</p>
                  <small class="text-muted">
                    <i class="bi bi-code-slash"></i> ${repo.language || 'N/A'}
                    <span class="ms-2"><i class="bi bi-calendar"></i> ${new Date(repo.updated_at).toLocaleDateString('en-US')}</span>
                  </small>
                </div>
                <div class="text-end">
                  <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> ${repo.stargazers_count}</span>
                  <span class="badge bg-secondary"><i class="bi bi-diagram-2"></i> ${repo.forks_count}</span>
                </div>
              </div>
            </a>
          `).join('')}
        </div>
      `;
}

function buildMetricsChart(cvss) {
    if (!cvss) return '';
    const score = cvss.baseScore || 0;
    const impact = cvss.impactScore || 0;
    const exploitability = cvss.exploitabilityScore || 0;

    const soften = (c) => Math.round(255 - (255 - c) * 0.6); // mélange avec du blanc
    const getGradientColor = (value, max = 10) => {
        const v = Math.min(value, max);
        let red, green, blue = 0;
        if (v <= 3.33) {
            const t = v / 3.33;
            red = Math.round(255 * t);
            green = 255;
        } else if (v <= 6.67) {
            const t = (v - 3.33) / 3.34;
            red = 255;
            green = Math.round(255 - 90 * t);
        } else {
            const t = (v - 6.67) / 3.33;
            red = 255;
            green = Math.round(165 * (1 - t));
        }
        return `rgb(${soften(red)}, ${soften(green)}, ${soften(blue)})`;
    };

    return `
        <div class="metrics-visual mt-3">
          <div class="metric-bar mb-2">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>CVSS Score</strong></small>
              <small><strong>${score}/10</strong></small>
            </div>
            <div class="progress" style="height: 20px;">
              <div class="progress-bar" style="width: ${score * 10}%; background-color: ${getGradientColor(score)}"></div>
            </div>
          </div>
          <div class="metric-bar mb-2">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>Impact</strong></small>
              <small><strong>${impact.toFixed(1)}/10</strong></small>
            </div>
            <div class="progress" style="height: 15px;">
              <div class="progress-bar" style="width: ${impact * 10}%; background-color: ${getGradientColor(impact)}"></div>
            </div>
          </div>
          <div class="metric-bar">
            <div class="d-flex justify-content-between mb-1">
              <small><strong>Exploitabilité</strong></small>
              <small><strong>${exploitability.toFixed(1)}/10</strong></small>
            </div>
            <div class="progress" style="height: 15px;">
              <div class="progress-bar" style="width: ${exploitability * 10}%; background-color: ${getGradientColor(exploitability)}"></div>
            </div>
          </div>
        </div>
      `;
}

function buildCVSSDetails(cvss) {
    if (!cvss) return '';

    const getColorClass = (metric, value) => {
        const colors = {
            attackVector: { 'NETWORK': 'danger', 'ADJACENT_NETWORK': 'warning', 'LOCAL': 'warning', 'PHYSICAL': 'success' },
            attackComplexity: { 'LOW': 'danger', 'HIGH': 'success' },
            privilegesRequired: { 'NONE': 'danger', 'LOW': 'warning', 'HIGH': 'success' },
            userInteraction: { 'NONE': 'danger', 'REQUIRED': 'success' },
            confidentialityImpact: { 'HIGH': 'danger', 'LOW': 'warning', 'NONE': 'success' },
            integrityImpact: { 'HIGH': 'danger', 'LOW': 'warning', 'NONE': 'success' },
            availabilityImpact: { 'HIGH': 'danger', 'LOW': 'warning', 'NONE': 'success' },
            scope: { 'CHANGED': 'danger', 'UNCHANGED': 'success' }
        };
        return colors[metric]?.[value] || 'secondary';
    };

    const metrics = [
        { key: 'attackVector', label: 'Attack Vector', abbr: 'AV', icon: 'bi-globe' },
        { key: 'attackComplexity', label: 'Attack Complexity', abbr: 'AC', icon: 'bi-gear' },
        { key: 'privilegesRequired', label: 'Privileges Required', abbr: 'PR', icon: 'bi-person-lock' },
        { key: 'userInteraction', label: 'User Interaction', abbr: 'UI', icon: 'bi-cursor' },
        { key: 'confidentialityImpact', label: 'Confidentiality', abbr: 'C', icon: 'bi-eye-slash' },
        { key: 'integrityImpact', label: 'Integrity', abbr: 'I', icon: 'bi-shield-lock' },
        { key: 'availabilityImpact', label: 'Availability', abbr: 'A', icon: 'bi-power' }
    ];

    const rows = metrics.map(m => {
        const value = cvss[m.key] || 'N/A';
        const colorClass = getColorClass(m.key, value);
        return `
          <div class="cvss-row d-flex justify-content-between align-items-center py-1">
            <div class="cvss-label">
              <i class="bi ${m.icon} me-2"></i>
              <span class="fw-medium">${m.abbr}</span>
              <span class="text-muted ms-1 small">(${m.label})</span>
            </div>
            <div class="cvss-value">
              <span class="badge bg-light text-dark border">${value}</span>
              <span class="cvss-indicator bg-${colorClass}"></span>
            </div>
          </div>
        `;
    }).join('');

    return `
        <div class="cvss-details mt-2">
          ${rows}
        </div>
      `;
}

function renderDetailContent(vuln, kevData, kevExists, cvssWithScores, cvssData, desc, published) {
    return `
        <div class="mb-3">
          ${kevExists ? '<span class="badge bg-danger mb-2">Active exploit</span>' : ''}
          <p class="text-muted mb-2"><i class="bi bi-calendar3"></i> Published: ${published}</p>
          <p>${desc}</p>
        </div>
        <div class="mb-3">
          ${buildMetricsChart(cvssWithScores)}
          ${buildCVSSDetails(cvssData)}
        </div>
        <div class="col mb-3">
          <div class="row mb-3">
            <h6><i class="bi bi-bug"></i> Weaknesses (CWE)</h6>
            ${buildCWESection(vuln.cve.weaknesses)}
          </div>
          <div class="row mb-3">
            ${buildAffectedTech(vuln.cve.configurations)}
          </div>
        </div>
        ${buildKEVAlert(kevData)}
      `;
}

async function checkKevExists(cveId) {
    try {
        const resp = await fetch(`api/kev_proxy.php?cve=${encodeURIComponent(cveId)}`);
        if (!resp.ok) return false;
        const data = await resp.json();
        return data.exists === true;
    } catch {
        return false;
    }
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.detail-btn');
    if (!btn) {
        return;
    }

    const cveId = btn.dataset.cve;
    const modal = new bootstrap.Modal(document.getElementById('cveModal'));
    const body = document.getElementById('cveModalBody');
    const nvdLink = document.getElementById('cveModalNvdLink');
    const modalTitle = document.getElementById('cveModalLabel');
    const record = vulnStore[cveId];
    if (!record) {
        return;
    }

    const { vuln, kevData, kevExists, cvssWithScores, cvssData, desc, published } = record;
    modalTitle.textContent = vuln.cve.id;
    body.innerHTML = renderDetailContent(vuln, kevData, kevExists, cvssWithScores, cvssData, desc, published);
    nvdLink.href = `https://nvd.nist.gov/vuln/detail/${vuln.cve.id}`;
    modal.show();
});

document.addEventListener('click', async (e) => {
    if (e.target.closest('.poc-btn')) {
        const btn = e.target.closest('.poc-btn');
        const cveId = btn.dataset.cve;
        const modalBody = document.getElementById('pocModalBody');
        const modalLabel = document.getElementById('pocModalLabel');

        modalLabel.innerHTML = `<i class="bi bi-github"></i> GitHub PoCs - ${cveId}`;
        modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Searching...</p></div>';

        const modal = new bootstrap.Modal(document.getElementById('pocModal'));
        modal.show();

        const pocs = await fetchGitHubPocs(cveId);
        modalBody.innerHTML = renderPocResults(pocs, cveId);
    }
});





