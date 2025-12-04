<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Résultats CVE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <header class="header">
    <div class="container text-center">
      <h1>CVE Results</h1>
      <a href="index.php" class="btn btn-outline-primary mt-2">
        <i class="bi bi-arrow-left"></i> New search
      </a>
    </div>
  </header>
  <main class="main-content">
      <div id="results" class="row g-4"></div>
  </main>

  <div class="modal fade" id="cveModal" tabindex="-1" aria-labelledby="cveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="cveModalLabel">CVE</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="cveModalBody">
          <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
        </div>
        <div class="modal-footer">
          <a id="cveModalNvdLink" href="#" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> NVD</a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="pocModal" tabindex="-1" aria-labelledby="pocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title" id="pocModalLabel"><i class="bi bi-github"></i> PoCs GitHub</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="pocModalBody">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const params = new URLSearchParams(window.location.search);
    const cveIds = params.get('cveIds') ? params.get('cveIds').split(',') : [];
    const resultsDiv = document.getElementById('results');
    const vulnStore = {};

    async function fetchCve(cveId) {
      const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=${encodeURIComponent(cveId)}`;
      const resp = await fetch(url);
      if (!resp.ok) return null;
      const data = await resp.json();
      return data.vulnerabilities && data.vulnerabilities.length > 0 ? data.vulnerabilities[0] : null;
    }

    async function checkKevExists(cveId) {
      try {
        const resp = await fetch(`https://kevin.gtfkd.com/kev/exists?cve=${encodeURIComponent(cveId)}`);
        if (!resp.ok) return false;
        const data = await resp.json();
        return data.exists === true;
      } catch {
        return false;
      }
    }

    async function fetchKevDetails(cveId) {
      try {
        const resp = await fetch(`https://kevin.gtfkd.com/kev/${encodeURIComponent(cveId)}`);
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
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6">
            <h6><i class="bi bi-bug"></i> Weaknesses (CWE)</h6>
            ${buildCWESection(vuln.cve.weaknesses)}
          </div>
          <div class="col-12 col-md-6">
            ${buildAffectedTech(vuln.cve.configurations)}
          </div>
        </div>
        ${buildKEVAlert(kevData)}
      `;
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.detail-btn');
      if (!btn) return;
      const cveId = btn.dataset.cve;
      const modal = new bootstrap.Modal(document.getElementById('cveModal'));
      const body = document.getElementById('cveModalBody');
      const nvdLink = document.getElementById('cveModalNvdLink');
      const modalTitle = document.getElementById('cveModalLabel');
      const record = vulnStore[cveId];
      if (!record) return;
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

    async function showResults() {
      if (cveIds.length === 0) {
        resultsDiv.innerHTML = '<div class="col-12"><div class="alert alert-warning">No CVE identifier provided.</div></div>';
        return;
      }
      resultsDiv.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
      const cards = [];
      const totalCards = cveIds.length;
      
      let colClass = 'col-12';
      if (totalCards === 1) {
        colClass = 'col-12 col-md-8 col-lg-6 mx-auto';
      } else if (totalCards === 2) {
        colClass = 'col-12 col-md-6';
      } else if (totalCards === 3) {
        colClass = 'col-12 col-md-6 col-lg-4';
      } else {
        colClass = 'col-12 col-sm-6 col-lg-4 col-xl-3';
      }

      for (const cveId of cveIds) {
        const [vuln, kevExists] = await Promise.all([
          fetchCve(cveId),
          checkKevExists(cveId)
        ]);
        
        let kevData = null;
        if (kevExists) {
          kevData = await fetchKevDetails(cveId);
        }

        if (!vuln) {
          cards.push(`
            <div class="${colClass}">
              <div class="card border-danger h-100">
                <div class="card-body">
                  <h5 class="card-title text-danger">${cveId}</h5>
                  <p class="card-text">No data found for this CVE.</p>
                  ${kevData ? buildKEVAlert(kevData) : ''}
                </div>
                <div class="card-footer bg-light">
                  ${buildPocButton(cveId)}
                </div>
              </div>
            </div>
          `);
          continue;
        }
        const desc = vuln?.cve.descriptions?.find(d => d.lang === 'en')?.value || 'No description available';
        const published = vuln?.cve.published ? new Date(vuln.cve.published).toLocaleDateString('en-US') : 'N/A';
        const cvssV3 = vuln?.cve.metrics?.cvssMetricV31?.[0] || vuln?.cve.metrics?.cvssMetricV3?.[0];
        const cvssV2 = vuln?.cve.metrics?.cvssMetricV2?.[0];
        const cvssData = cvssV3?.cvssData || cvssV2?.cvssData;
        const impactScore = cvssV3?.impactScore || cvssV2?.impactScore || 0;
        const exploitabilityScore = cvssV3?.exploitabilityScore || cvssV2?.exploitabilityScore || 0;
        const severity = cvssData?.baseSeverity || cvssV2?.baseSeverity || 'N/A';
        const cvssWithScores = cvssData ? { ...cvssData, impactScore, exploitabilityScore } : null;

        if (vuln) {
          vulnStore[vuln.cve.id] = { vuln, kevData, kevExists, cvssWithScores, cvssData, desc, published };
        }

        if (!vuln) {
          continue;
        }

        cards.push(`
          <div class="${colClass}">
            <div class="card h-100 cve-card ${kevExists ? 'border-danger' : ''}">
              <div class="card-header ${kevExists ? 'bg-danger' : 'bg-primary'} text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  ${vuln.cve.id}
                  ${kevExists ? '<i class="bi bi-exclamation-triangle-fill ms-2" title="Exploit actif"></i>' : ''}
                </h5>
                ${getSeverityBadge(severity)}
              </div>
              <div class="card-body">
                <p class="text-muted mb-1"><i class="bi bi-calendar3"></i> ${published}</p>
                <p class="card-text">${desc.substring(0, 160)}${desc.length > 160 ? '…' : ''}</p>
              </div>
              <div class="card-footer bg-light d-flex justify-content-between">
                <div>
                  <a href="https://nvd.nist.gov/vuln/detail/${vuln.cve.id}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right"></i> NVD
                  </a>
                  ${buildPocButton(vuln.cve.id)}
                  ${kevExists ? `<a href="https://www.cisa.gov/known-exploited-vulnerabilities-catalog" target="_blank" class="btn btn-sm btn-outline-danger ms-2"><i class="bi bi-shield-exclamation"></i> KEV</a>` : ''}
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary detail-btn" data-cve="${vuln.cve.id}">
                  <i class="bi bi-arrows-fullscreen"></i> Détails
                </button>
              </div>
            </div>
          </div>
        `);
      }
      resultsDiv.innerHTML = cards.join('');
    }

    showResults();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
